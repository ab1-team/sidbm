<?php

namespace App\Http\Controllers;

use App\Models\Desa;
use App\Models\FungsiKelompok;
use App\Models\JenisKegiatan;
use App\Models\JenisProdukPinjaman;
use App\Models\JenisUsaha;
use App\Models\Kecamatan;
use App\Models\Kelompok;
use App\Models\StatusPinjaman;
use App\Models\TingkatKelompok;
use App\Utils\Tanggal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Session;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class LembagaLainController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $kelompok = Kelompok::where('jenis_produk_pinjaman', '=', '3')->with([
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

        $title = 'Daftar Lembaga';
        return view('lembaga_lain.index')->with(compact('title', 'status_pinjaman'));
    }

    public function register()
    {
        $kec = Kecamatan::where('id', Session::get('lokasi'))->first();
        $desa = Desa::where('kd_kec', 'LIKE', $kec['kd_kab'] . '%')->with('sebutan_desa', 'kec')->get();
        $fungsi_kelompok = FungsiKelompok::all();

        $desa_dipilih = 0;
        if (request()->get('desa')) {
            $desa_dipilih = request()->get('desa');
        }

        $title = 'Register Lembaga';
        return view('lembaga_lain.register')->with(compact('title', 'desa', 'fungsi_kelompok', 'desa_dipilih'));
    }

    public function generateKode()
    {
        $lokasi = Session::get('lokasi');
        $kd_desa = request()->get('kode');

        $jumlah_lembaga_by_kd_desa = Kelompok::where([['desa', $kd_desa]])->orderBy('kd_kelompok', 'DESC');
        if ($jumlah_lembaga_by_kd_desa->count() > 0) {
            $data_lembaga = $jumlah_lembaga_by_kd_desa->first();
            $kd_lembaga = $data_lembaga->kd_kelompok + 1;
        } else {
            $jumlah_lembaga = str_pad(Kelompok::where([['desa', $kd_desa]])->count() + 1, 4, "0", STR_PAD_LEFT);
            $kd_lembaga = $kd_desa . '03' . $jumlah_lembaga;
        }

        if (request()->get('kd_lembaga')) {
            $kd_kel = request()->get('kd_lembaga');
            $kelompok = Kelompok::where('kd_kelompok', $kd_kel);
            if ($kelompok->count() > 0) {
                $data_kel = $kelompok->first();

                if ($kd_desa == $data_kel->desa) {
                    $kd_lembaga = $data_kel->kd_kelompok;
                }
            }
        }

        return response()->json([
            'kode' => $kd_lembaga
        ], Response::HTTP_ACCEPTED);
    }

    public function store(Request $request)
    {
        $data = $request->only([
            "desa",
            "kode_lembaga",
            "nama_lembaga",
            "alamat_lembaga",
            "telpon",
            "kategori_pinjaman",
            "pimpinan",
            "penanggung_jawab",
            "nomor_identitas_lembaga"
        ]);

        $validate = Validator::make($data, [
            'desa' => 'required',
            'kode_lembaga' => 'required|unique:kelompok_' . Session::get('lokasi') . ',kd_kelompok',
            'nama_lembaga' => 'required',
            'alamat_lembaga' => 'required',
            'telpon' => 'required',
            'kategori_pinjaman' => 'required',
            'pimpinan' => 'required',
            'penanggung_jawab' => 'required',
            'nomor_identitas_lembaga' => 'nullable|string|max:100'
        ]);

        if ($validate->fails()) {
            return response()->json($validate->errors(), Response::HTTP_MOVED_PERMANENTLY);
        }

        $insert = [
            'lokasi' => Session::get('lokasi'),
            'desa' => $request->desa,
            'kd_kelompok' => $request->kode_lembaga,
            'nama_kelompok' => $request->nama_lembaga,
            'alamat_kelompok' => $request->alamat_lembaga,
            'telpon' => $request->telpon,
            'tgl_berdiri' => date('Y-m-d'),
            'jenis_produk_pinjaman' => 3,
            'jenis_usaha' => 1,
            'jenis_kegiatan' => 1,
            'tingkat_kelompok' => 1,
            'fungsi_kelompok' => $request->kategori_pinjaman,
            'ketua' => $request->pimpinan,
            'sekretaris' => $request->penanggung_jawab,
            'bendahara' => '',
            'uname' => 0,
            'pass' => $request->nomor_identitas_lembaga ?? 0,
            'online' => 'T',
            'nilai' => '0',
            'kunjungan' => '0',
            'lo' => date('Y-m-d H:i:s'),
            'id_user' => auth()->user()->id,
        ];

        $kel = Kelompok::create($insert);

        return response()->json([
            'msg' => $kel->nama_kelompok . ' berhasil disimpan',
            'kode_lembaga' => $kel->kd_kelompok + 1,
            'desa' => $kel->desa
        ], Response::HTTP_ACCEPTED);
    }

    public function show(Kelompok $lembaga_lain)
    {
        $lembaga_lain = $lembaga_lain->with([
            'pinkel' => function ($query) use ($lembaga_lain) {
                $query->where('jenis_pp', $lembaga_lain->jenis_produk_pinjaman);
            },
            'pinkel.sts',
            'pinkel.saldo'
        ])->where('id', $lembaga_lain->id)->first();
        $kec = Kecamatan::where('id', Session::get('lokasi'))->first();
        $desa = Desa::where('kd_kec', 'LIKE', $kec['kd_kab'] . '%')->with('sebutan_desa')->get();
        $fungsi_kelompok = FungsiKelompok::all();

        $desa_dipilih = $lembaga_lain->desa;
        $title = 'Lembaga ' . $lembaga_lain->nama_kelompok;
        return view('lembaga_lain.detail')->with(compact('title', 'lembaga_lain', 'desa', 'fungsi_kelompok', 'desa_dipilih'));
    }

    public function update(Request $request, Kelompok $lembaga_lain)
    {
        $data = $request->only([
            'desa',
            'kode_lembaga',
            'nama_lembaga',
            'alamat_lembaga',
            'telpon',
            'kategori_pinjaman',
            'pimpinan',
            'penanggung_jawab',
            'nomor_identitas_lembaga'
        ]);

        $rules = [
            'kode_lembaga' => 'required',
            'desa' => 'required',
            'nama_lembaga' => 'required',
            'alamat_lembaga' => 'required',
            'telpon' => 'required',
            'kategori_pinjaman' => 'required',
            'pimpinan' => 'required',
            'penanggung_jawab' => 'required',
            'nomor_identitas_lembaga' => 'nullable|string|max:100'
        ];

        if ($request->kode_lembaga != $lembaga_lain->kd_kelompok) {
            $rules['kode_lembaga'] = 'required|unique:kelompok_' . Session::get('lokasi') . ',kd_kelompok';
        }

        $validate = Validator::make($data, $rules);

        if ($validate->fails()) {
            return response()->json($validate->errors(), Response::HTTP_MOVED_PERMANENTLY);
        }

        $update = [
            'lokasi' => Session::get('lokasi'),
            'desa' => $request->desa,
            'kd_kelompok' => $request->kode_lembaga,
            'nama_kelompok' => $request->nama_lembaga,
            'alamat_kelompok' => $request->alamat_lembaga,
            'telpon' => $request->telpon,
            'fungsi_kelompok' => $request->kategori_pinjaman,
            'ketua' => $request->pimpinan,
            'sekretaris' => $request->penanggung_jawab,
            'pass' => $request->nomor_identitas_lembaga ?? $lembaga_lain->pass
        ];

        $lembaga = Kelompok::where('id', $lembaga_lain->id)->update($update);

        return response()->json([
            'msg' => 'Lembaga ' . $update['nama_kelompok'] . ' berhasil disimpan'
        ], Response::HTTP_ACCEPTED);
    }
}
