<?php

namespace App\Http\Controllers;

use App\Models\Kecamatan;
use App\Models\PinjamanKelompok;
use App\Models\RealAngsuran;
use App\Models\RencanaAngsuran;
use App\Services\GenerateService;
use App\Utils\Keuangan;
use Illuminate\Http\Request;
use Session;
use URL;

class GenerateController extends Controller
{
    protected $generateService;

    public function __construct(GenerateService $generateService)
    {
        $this->generateService = $generateService;
    }

    public function index()
    {
        $domain = request()->getHost();
        $domainId = str_replace('.net', '.id', $domain);

        $tenantFromB = \Illuminate\Support\Facades\DB::connection('mysql_b')
            ->table('kecamatan')
            ->where('web_kec', $domainId)
            ->orWhere('web_alternatif', $domainId)
            ->first();

        if ($tenantFromB) {
            $kec = Kecamatan::on('mysql_b')->find($tenantFromB->id);
            // Lokasi pakai server B — switch default connection supaya
            // GenerateService baca/tulis ke mysql_b, bukan mysql.
            \Illuminate\Support\Facades\Config::set('database.default', 'mysql_b');
            \Illuminate\Support\Facades\DB::setDefaultConnection('mysql_b');
        } else {
            $kec = Kecamatan::where('web_kec', $domain)
                ->orWhere('web_alternatif', $domain)
                ->first();
        }

        Session::put('lokasi', $kec->id);

        $logo = '/assets/img/icon/favicon.png';
        if ($kec->logo) {
            $logo = '/storage/logo/' . $kec->logo;
        }

        $table = 'pinjaman_kelompok_' . Session::get('lokasi');

        $database = \Illuminate\Support\Facades\DB::connection()->getDatabaseName();
        $strukturTabel = \Illuminate\Support\Facades\DB::select("
            SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_NAME = '$table' AND TABLE_SCHEMA='$database'
            ORDER BY ORDINAL_POSITION;
        ");

        $struktur = array_map(function ($kolom) {
            return $kolom->COLUMN_NAME;
        }, $strukturTabel);

        return view('generate.index')->with(compact('logo', 'struktur'));
    }

    public function generate(Request $request, $offset = 0)
    {
        // Pastikan pakai mysql_b kalau lokasi di server B (sama logikanya
        // dgn index() — kalau route di-skip langsung ke generate, default
        // masih mysql).
        $this->ensureServerBConnection();

        $result = $this->generateService->generate($request->all(), (int) $offset);

        $data_pinjaman = $result['data_pinjaman'];
        $data = $request->all();
        $offset = $result['offset'];
        $limit = $result['limit'];

        return view('generate.generate')->with(compact('data_pinjaman', 'data', 'offset', 'limit'));
    }

    /**
     * Kalau lokasi (session) ada di mysql_b, switch default connection
     * ke mysql_b supaya GenerateService.generate -> PinjamanKelompok::where
     * query dari server yg benar (bukan mysql yg kosong utk lokasi 301).
     */
    protected function ensureServerBConnection()
    {
        $lokasi = Session::get('lokasi');
        if (!$lokasi) {
            return;
        }
        $existsInB = \Illuminate\Support\Facades\DB::connection('mysql_b')
            ->table('kecamatan')
            ->where('id', $lokasi)
            ->exists();
        if ($existsInB) {
            \Illuminate\Support\Facades\Config::set('database.default', 'mysql_b');
            \Illuminate\Support\Facades\DB::setDefaultConnection('mysql_b');
        }
    }
}
