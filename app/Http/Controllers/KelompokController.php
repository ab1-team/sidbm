<?php

namespace App\Http\Controllers;

use App\Models\Desa;
use App\Models\FungsiKelompok;
use App\Models\JenisKegiatan;
use App\Models\JenisProdukPinjaman;
use App\Models\JenisUsaha;
use App\Models\Kecamatan;
use App\Models\Kelompok;
use App\Models\PinjamanKelompok;
use App\Models\StatusPinjaman;
use App\Models\TingkatKelompok;
use App\Utils\Tanggal;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Session;
use Yajra\DataTables\DataTables;

class KelompokController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (request()->ajax()) {
            $kelompok = Kelompok::where('jenis_produk_pinjaman', '!=', '3')->with([
                'd',
                'd.sebutan_desa',
                'kegiatan',
                'pinjaman' => function ($query) {
                    $query->orderBy('tgl_proposal', 'DESC');
                },
                'pinjaman.sts'
            ])->get();
            return DataTables::of($kelompok)
                ->addColumn('status', function ($row) {
                    $pinjaman = $row->pinjaman;

                    $status = '<span class="badge badge-secondary">n</span>';
                    if ($row->pinjaman) {
                        $status_pinjaman = $pinjaman->status;

                        $badge = ($pinjaman->sts) ? $pinjaman->sts->warna_status : '';
                        $status = '<span class="badge badge-' . $badge . '">' . $status_pinjaman . '</span>';
                    }

                    return $status;
                })
                ->editColumn('alamat_kelompok', function ($row) {
                    return $row->alamat_kelompok . ' ' . $row->d->sebutan_desa->sebutan_desa . ' ' . $row->d->nama_desa;
                })
                ->rawColumns(['status'])
                ->make(true);
        }

        $status_pinjaman = StatusPinjaman::all();

        $title = 'Daftar Kelompok';
        return view('kelompok.index')->with(compact('title', 'status_pinjaman'));
    }

    public function register()
    {
        $title = 'Register Kelompok';
        return view('kelompok.register')->with(compact('title'));
    }

    public function generateKode()
    {
        $lokasi = Session::get('lokasi');
        $kd_desa = request()->get('kode');

        $jumlah_kelompok_by_kd_desa = Kelompok::where([['desa', $kd_desa]])->orderBy('kd_kelompok', 'DESC');
        if ($jumlah_kelompok_by_kd_desa->count() > 0) {
            $data_kelompok = $jumlah_kelompok_by_kd_desa->first();
            $kd_kelompok = $data_kelompok->kd_kelompok + 1;
        } else {
            $jumlah_kelompok = str_pad(Kelompok::where([['desa', $kd_desa]])->count() + 1, 4, "0", STR_PAD_LEFT);
            $kd_kelompok = $kd_desa . $jumlah_kelompok;
        }

        if (request()->get('kd_kelompok')) {
            $kd_kel = request()->get('kd_kelompok');
            $kelompok = Kelompok::where('kd_kelompok', $kd_kel);
            if ($kelompok->count() > 0) {
                $data_kel = $kelompok->first();

                if ($kd_desa == $data_kel->desa) {
                    $kd_kelompok = $data_kel->kd_kelompok;
                }
            }
        }

        return response()->json([
            'kd_kelompok' => $kd_kelompok
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $kec = Kecamatan::where('id', Session::get('lokasi'))->first();
        $desa = Desa::where('kd_kec', $kec['kd_kec'])->with('sebutan_desa')->get();
        $jenis_produk_pinjaman = JenisProdukPinjaman::where([
            ['lokasi', '0'],
            ['id', '!=', '3']
        ])->orWhere('lokasi', Session::get('lokasi'))->orderBy('id', 'ASC')->get();
        $jenis_usaha = JenisUsaha::all();
        $jenis_kegiatan = JenisKegiatan::all();
        $tingkat_kelompok = TingkatKelompok::all();
        $fungsi_kelompok = FungsiKelompok::all();

        $desa_dipilih = 0;
        if (request()->get('desa')) {
            $desa_dipilih = request()->get('desa');
        }
        return view('kelompok.create')->with(compact('desa', 'jenis_produk_pinjaman', 'jenis_usaha', 'jenis_kegiatan', 'tingkat_kelompok', 'fungsi_kelompok', 'desa_dipilih'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->only([
            'desa',
            'kode_kelompok',
            'nama_kelompok',
            'jenis_produk_pinjaman',
            'alamat_kelompok',
            'telpon',
            'tgl_berdiri',
            'ketua',
            'sekretaris',
            'bendahara',
            'jenis_usaha',
            'jenis_kegiatan',
            'tingkat_kelompok',
            'fungsi_kelompok'
        ]);

        $validate = Validator::make($data, [
            'desa' => 'required',
            'kode_kelompok' => 'required|unique:kelompok_' . Session::get('lokasi') . ',kd_kelompok',
            'nama_kelompok' => 'required',
            'jenis_produk_pinjaman' => 'required',
            'alamat_kelompok' => 'required',
            'telpon' => 'required',
            'tgl_berdiri' => 'required',
            'ketua' => 'required',
            'sekretaris' => 'required',
            'bendahara' => 'required',
            'jenis_usaha' => 'required',
            'jenis_kegiatan' => 'required',
            'tingkat_kelompok' => 'required',
            'fungsi_kelompok' => 'required'
        ]);

        if ($validate->fails()) {
            return response()->json($validate->errors(), Response::HTTP_MOVED_PERMANENTLY);
        }

        $insert = [
            'lokasi' => Session::get('lokasi'),
            'desa' => $request->desa,
            'kd_kelompok' => $request->kode_kelompok,
            'nama_kelompok' => trim(str_replace('  ', ' ', $request->nama_kelompok)),
            'alamat_kelompok' => $request->alamat_kelompok,
            'telpon' => $request->telpon,
            'tgl_berdiri' => Tanggal::tglNasional($request->tgl_berdiri),
            'jenis_produk_pinjaman' => $request->jenis_produk_pinjaman,
            'jenis_usaha' => $request->jenis_usaha,
            'jenis_kegiatan' => $request->jenis_kegiatan,
            'tingkat_kelompok' => $request->tingkat_kelompok,
            'fungsi_kelompok' => $request->fungsi_kelompok,
            'ketua' => $request->ketua,
            'sekretaris' => $request->sekretaris,
            'bendahara' => $request->bendahara,
            'uname' => $request->kode_kelompok,
            'pass' => $request->kode_kelompok,
            'online' => 'T',
            'nilai' => '0',
            'kunjungan' => '0',
            'lo' => date('Y-m-d H:i:s'),
            'id_user' => auth()->user()->id,
        ];

        $kel = Kelompok::create($insert);

        return response()->json([
            'msg' => 'Kelompok ' . $kel->nama_kelompok . ' berhasil disimpan',
            'kode_kelompok' => $kel->kd_kelompok + 1,
            'desa' => $kel->desa
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Kelompok $kelompok)
    {
        $kelompok = $kelompok->with([
            'pinkel',
            'pinkel.sts',
            'pinkel.saldo',
            'pinkel.sis_pokok'
        ])->where('id', $kelompok->id)->first();
        $kec = Kecamatan::where('id', Session::get('lokasi'))->first();
        $desa = Desa::where('kd_kec', $kec['kd_kec'])->with('sebutan_desa')->get();
        $jenis_produk_pinjaman = JenisProdukPinjaman::where([
            ['lokasi', '0'],
            ['id', '!=', '3']
        ])->orderBy('id', 'ASC')->get();
        $jenis_usaha = JenisUsaha::all();
        $jenis_kegiatan = JenisKegiatan::all();
        $tingkat_kelompok = TingkatKelompok::all();
        $fungsi_kelompok = FungsiKelompok::all();

        $desa_dipilih = $kelompok->desa;

        $title = 'Kelompok ' . $kelompok->nama_kelompok;
        return view('kelompok.detail')->with(compact('title', 'kelompok', 'desa', 'jenis_produk_pinjaman', 'jenis_usaha', 'jenis_kegiatan', 'tingkat_kelompok', 'fungsi_kelompok', 'desa_dipilih'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Kelompok $kelompok)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Kelompok $kelompok)
    {
        $data = $request->only([
            'desa',
            'kode_kelompok',
            'nama_kelompok',
            'jenis_produk_pinjaman',
            'alamat_kelompok',
            'telpon',
            'tgl_berdiri',
            'ketua',
            'sekretaris',
            'bendahara',
            'jenis_usaha',
            'jenis_kegiatan',
            'tingkat_kelompok',
            'fungsi_kelompok'
        ]);

        $rules = [
            'desa' => 'required',
            'nama_kelompok' => 'required',
            'jenis_produk_pinjaman' => 'required',
            'alamat_kelompok' => 'required',
            'telpon' => 'required',
            'tgl_berdiri' => 'required',
            'ketua' => 'required',
            'sekretaris' => 'required',
            'bendahara' => 'required',
            'jenis_usaha' => 'required',
            'jenis_kegiatan' => 'required',
            'tingkat_kelompok' => 'required',
            'fungsi_kelompok' => 'required'
        ];

        if ($request->kode_kelompok != $kelompok->kd_kelompok) {
            $rules['kode_kelompok'] = 'required|unique:kelompok_' . Session::get('lokasi') . ',kd_kelompok';
        }

        $validate = Validator::make($data, $rules);

        if ($validate->fails()) {
            return response()->json($validate->errors(), Response::HTTP_MOVED_PERMANENTLY);
        }

        $update = [
            'lokasi' => Session::get('lokasi'),
            'desa' => $request->desa,
            'kd_kelompok' => $request->kode_kelompok,
            'nama_kelompok' => $request->nama_kelompok,
            'alamat_kelompok' => $request->alamat_kelompok,
            'telpon' => $request->telpon,
            'tgl_berdiri' => Tanggal::tglNasional($request->tgl_berdiri),
            'jenis_produk_pinjaman' => $request->jenis_produk_pinjaman,
            'jenis_usaha' => $request->jenis_usaha,
            'jenis_kegiatan' => $request->jenis_kegiatan,
            'tingkat_kelompok' => $request->tingkat_kelompok,
            'fungsi_kelompok' => $request->fungsi_kelompok,
            'ketua' => $request->ketua,
            'sekretaris' => $request->sekretaris,
            'bendahara' => $request->bendahara
        ];

        $kel = Kelompok::where('id', $kelompok->id)->update($update);

        return response()->json([
            'msg' => 'Kelompok ' . $update['nama_kelompok'] . ' berhasil disimpan'
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Kelompok $kelompok)
    {
        Kelompok::where('id', $kelompok->id)->delete();

        return response()->json([
            'success' => true,
            'msg' => 'Kelompok ' . $kelompok->nama_kelompok . ' berhasil Dihapus'
        ]);
    }

    public function detailKelompok($id)
    {
        $pinkel = PinjamanKelompok::where('id', $id)->with([
            'kelompok',
            'kelompok.d',
            'jpp',
            'sis_pokok',
            'target' => function ($query) {
                $query->where('angsuran_ke', '1');
            }
        ])->firstOrFail();

        return [
            'label' => 'Detail Kelompok ' . $pinkel->kelompok->nama_kelompok,
            'view' => view('kelompok.detail_kelompok')->with(compact('pinkel'))->render()
        ];
    }
}
