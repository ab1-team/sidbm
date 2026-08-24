<?php

namespace App\Http\Controllers;

use App\Models\Kecamatan;
use App\Models\PinjamanKelompok;
use App\Models\RealAngsuran;
use App\Models\RencanaAngsuran;
use App\Services\GenerateService;
use App\Support\TenantResolver;
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
        $resolvedTenant = TenantResolver::resolveByDomain(request()->getHost());

        if ($resolvedTenant && $resolvedTenant['type'] === 'kecamatan') {
            $connection = $resolvedTenant['connection'];
            $kec = Kecamatan::on($connection)->find($resolvedTenant['tenant']->id);
        } else {
            abort(404, 'Lembaga tidak ditemukan');
        }

        config(['database.default' => $connection]);

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
        $result = $this->generateService->generate($request->all(), (int) $offset);

        $data_pinjaman = $result['data_pinjaman'];
        $data = $request->all();
        $offset = $result['offset'];
        $limit = $result['limit'];

        return view('generate.generate')->with(compact('data_pinjaman', 'data', 'offset', 'limit'));
    }
}
