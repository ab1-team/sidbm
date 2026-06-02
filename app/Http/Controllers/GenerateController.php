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

        $table = 'pinjaman_kelompok_' . Session::get('lokasi');

        $struktur = \Illuminate\Support\Facades\Schema::getColumnListing($table);

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
