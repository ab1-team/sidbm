<?php

namespace App\Http\Controllers\Kabupaten;

use App\Http\Controllers\Controller;
use App\Models\JenisLaporan;
use App\Models\Kabupaten;
use App\Models\Kecamatan;
use App\Models\Rekening;
use App\Models\Wilayah;
use App\Utils\Keuangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Session;

class KabupatenController extends Controller
{
    public function index()
    {
        $keuangan = new Keuangan;
        $tahun = date('Y');
        $bulan = date('m');

        $kd_prov = Session::get('kd_prov');
        $kd_kab = Session::get('kd_kab');

        $saldo_kec = [];
        $wilayah = Wilayah::where('kode', 'like', $kd_kab.'%')->whereRaw('LENGTH(kode)=8')->with('kec')->orderBy('nama')->get();
        foreach ($wilayah as $wl) {
            $saldo_kec[$wl->kode] = [
                'nama' => $wl->nama,
                'kode' => $wl->kode,
                'laba_rugi' => [
                    'pendapatan' => 0,
                    'biaya' => 0,
                ],
                'surplus' => 0,
                'used_dbm' => false,
            ];

            if ($wl->kec) {
                Session::put('lokasi', $wl->kec->id);

                config(['tenant.suffix' => "_{$wl->kec->id}"]);

                $laba_rugi = Rekening::where('lev1', '>=', '4')->with([
                    'kom_saldo' => function ($query) use ($tahun, $bulan) {
                        $query->where('tahun', $tahun)->where(function ($query) use ($bulan) {
                            $query->where('bulan', '0')->orwhere('bulan', $bulan);
                        });
                    },
                    'saldo' => function ($query) use ($tahun, $bulan) {
                        $query->where([
                            ['tahun', $tahun],
                            ['bulan', ($bulan - 1)],
                        ]);
                    },
                ])->orderBy('kode_akun', 'ASC')->get();

                $pendapatan = 0;
                $biaya = 0;
                foreach ($laba_rugi as $lb) {
                    $saldo = $keuangan->komSaldo($lb);
                    if ($lb->lev1 == 5) {
                        $biaya += $saldo;
                    } else {
                        $pendapatan += $saldo;
                    }
                }

                $saldo_kec[$wl->kode]['laba_rugi'] = [
                    'pendapatan' => $pendapatan,
                    'biaya' => $biaya,
                ];

                $saldo_kec[$wl->kode]['surplus'] = $pendapatan - $biaya;
                $saldo_kec[$wl->kode]['used_dbm'] = true;
            }
        }

        $title = Session::get('nama_kab').' Page';

        return view('kabupaten.index')->with(compact('title', 'saldo_kec', 'keuangan'));
    }

    public function tandaTangan()
    {
        $kd_kab = Session::get('kd_kab');
        $kab = Kabupaten::where('kd_kab', $kd_kab)->first();

        $title = 'Pengaturan Tanda Tangan Laporan';

        return view('kabupaten.tanda_tangan')->with(compact('title', 'kab'));
    }

    public function simpanTandaTangan(Request $request)
    {
        $data = $request->only([
            'tanda_tangan',
        ]);

        $data['tanda_tangan'] = preg_replace('/<table[^>]*>/', '<table class="p0" border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px;">', $data['tanda_tangan'], 1);
        $data['tanda_tangan'] = preg_replace('/height:\s*[^;]+;?/', '', $data['tanda_tangan']);

        $data['tanda_tangan'] = str_replace('colgroup', 'tr', $data['tanda_tangan']);
        $data['tanda_tangan'] = preg_replace('/<col([^>]*)>/', '<td$1>&nbsp;</td>', $data['tanda_tangan']);

        $kd_kab = Session::get('kd_kab');
        $tanda_tangan = Kabupaten::where('kd_kab', $kd_kab)->update([
            'tanda_tangan' => json_encode($data['tanda_tangan']),
        ]);

        return response()->json([
            'success' => true,
            'msg' => 'Tanda Tangan Berhasil diperbarui',
        ]);
    }

    public function profil()
    {
        $kd_kab = Session::get('kd_kab');
        $kab = Kabupaten::where('kd_kab', $kd_kab)->first();

        $title = 'Profil Kabupaten';

        return view('kabupaten.profil')->with(compact('title', 'kab'));
    }

    public function simpanProfil(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_lembaga' => 'nullable|max:50',
            'alamat_kab' => 'nullable|max:100',
            'telpon_kab' => 'nullable|max:20',
            'email_kab' => 'nullable|email|max:50',
            'password' => 'nullable|min:6|max:50',
            'password_konfirmasi' => 'nullable|min:6|max:50|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'msg' => $validator->errors()->first(),
            ], 422);
        }

        $data = $request->only([
            'nama_lembaga',
            'alamat_kab',
            'telpon_kab',
            'email_kab',
        ]);

        if ($request->filled('password')) {
            $data['pass'] = $request->password;
        }

        Kabupaten::where('kd_kab', Session::get('kd_kab'))->update($data);

        return response()->json([
            'success' => true,
            'msg' => 'Profil Berhasil diperbarui',
        ]);
    }

    public function kecamatan($kd_kec)
    {
        $kec = Kecamatan::where('kd_kec', $kd_kec)->with('kabupaten')->first();
        $laporan = JenisLaporan::where('file', '!=', '0');
        if ($kec->id != '277') {
            $laporan = $laporan->where('id', '!=', '32');
        }
        $laporan = $laporan->orderBy('urut', 'ASC')->get();

        if (! $kec) {
            $kec = Wilayah::where('kode', $kd_kec)->first();

            $title = 'Kecamatan Belum Terdaftar';

            return view('kabupaten._kecamatan')->with(compact('title', 'kec'));
        }

        $kab = $kec->kabupaten;
        $nama_kec = $kec->sebutan_kec.' '.$kec->nama_kec;
        if (Keuangan::startWith($kab->nama_kab, 'KOTA') || Keuangan::startWith($kab->nama_kab, 'KAB')) {
            $nama_kec .= ' '.ucwords(strtolower($kab->nama_kab));
        } else {
            $nama_kec .= ' Kabupaten '.ucwords(strtolower($kab->nama_kab));
        }

        Session::put('lokasi', $kec->id);
        config(['tenant.suffix' => "_{$kec->id}"]);

        $title = 'Pelaporan '.$kec->sebutan_kec.' '.$kec->nama_kec;

        return view('kabupaten.kecamatan')->with(compact('title', 'kec', 'laporan', 'nama_kec'));
    }

    public function simpanLogo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'logo' => 'required|image|mimes:jpg,png,jpeg|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'msg' => $validator->errors()->first(),
            ], 422);
        }

        $kab = Kabupaten::where('kd_kab', Session::get('kd_kab'))->first();

        if (! $kab) {
            abort(404);
        }

        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            $extension = $request->file('logo')->getClientOriginalExtension();
            $filename = time().'_'.$kab->id.'_'.date('Ymd').'.'.$extension;
            $path = $request->file('logo')->storeAs('logo_kab', $filename, 'supabase');

            $relativePath = str_replace(env('SUPABASE_PUBLIC_URL').'/', '', $kab->logo);
            if ($relativePath && Storage::disk('supabase')->exists($relativePath)) {
                Storage::disk('supabase')->delete($relativePath);
            }

            $publicUrl = env('SUPABASE_PUBLIC_URL').'/'.$path;
            Kabupaten::where('kd_kab', Session::get('kd_kab'))->update([
                'logo' => $publicUrl,
            ]);

            return response()->json([
                'success' => true,
                'msg' => 'Logo berhasil diperbarui.',
                'path' => $publicUrl,
            ]);
        }

        return response()->json([
            'success' => false,
            'msg' => 'Logo gagal diperbarui',
        ]);
    }
}
