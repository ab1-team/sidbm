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
        $kec = Kecamatan::where('id', Session::get('lokasi'))->first();

        $logo = '/assets/img/icon/favicon.png';
        if ($kec->logo) {
            $logo = '/storage/logo/' . $kec->logo;
        }

        $database = env('DB_DATABASE', 'siupk_dbm');
        $table = 'pinjaman_kelompok_' . Session::get('lokasi');

        $strukturTabel = \DB::select("
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
        $result = $this->generateService->generate($request->all(), (int) $offset);

        $data_pinjaman = $result['data_pinjaman'];
        $data = $request->all();
        $offset = $result['offset'];
        $limit = $result['limit'];

        return view('generate.generate')->with(compact('data_pinjaman', 'data', 'offset', 'limit'));
    }
}
