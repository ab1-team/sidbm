<?php

namespace App\Http\Controllers;

use App\Models\AdminInvoice;
use App\Models\AkunLevel1;
use App\Models\JenisProdukPinjaman;
use App\Models\Kecamatan;
use App\Models\Mobile;
use App\Models\Personalia;
use App\Models\Rekening;
use App\Models\RencanaAngsuran;
use App\Models\TandaTanganLaporan;
use App\Models\User;
use App\Models\Whatsapp;
use App\Utils\Keuangan;
use App\Utils\Pinjaman;
use App\Utils\Tanggal;
use Cookie;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PDF;
use Yajra\DataTables\DataTables;

class SopController extends Controller
{
    public function index()
    {
        $kec = Kecamatan::where('id', Session::get('lokasi'))->with('ttd', 'personalia', 'wa_session')->first();
        $token = $kec->token;

        $instance_name = $kec->wa_session->instance_name ?? null;

        $title = 'Personalisasi SOP';

        return view('sop.index')->with(compact('title', 'kec', 'token', 'instance_name'));
    }

    public function coa()
    {
        $title = 'Chart Of Account (CoA)';

        if (request()->ajax()) {
            $akun1 = AkunLevel1::with([
                'akun2',
                'akun2.akun3',
                'akun2.akun3.rek',
            ])->get();

            $coa = [];
            foreach ($akun1 as $ak1) {
                $akun_level_1 = [
                    'id' => $ak1->kode_akun,
                    'text' => $ak1->kode_akun.'. '.$ak1->nama_akun,
                    'children' => [],
                ];

                foreach ($ak1->akun2 as $ak2) {
                    $akun2 = [
                        'id' => $ak2->kode_akun,
                        'text' => $ak2->kode_akun.'. '.$ak2->nama_akun,
                        'children' => [],
                    ];

                    foreach ($ak2->akun3 as $ak3) {
                        $akun3 = [
                            'id' => $ak3->kode_akun,
                            'text' => $ak3->kode_akun.'. '.$ak3->nama_akun,
                            'children' => [],
                        ];

                        foreach ($ak3->rek as $rek) {
                            $akun4 = [
                                'id' => $rek->kode_akun,
                                'text' => $rek->kode_akun.'. '.$rek->nama_akun,
                            ];

                            array_push($akun3['children'], $akun4);
                        }
                        array_push($akun2['children'], $akun3);
                    }
                    array_push($akun_level_1['children'], $akun2);
                }
                array_push($coa, $akun_level_1);
            }

            return response()->json($coa);
        }

        return view('sop.coa')->with(compact('title'));
    }

    public function customCalk()
    {
        $kec = Kecamatan::where('id', Session::get('lokasi'))->first();
        $calk = json_decode($kec->calk, true);
        $pointA = $calk['calk']['A'] ?? '';

        $title = 'Custom CALK';

        return view('sop.custom_calk')->with(compact('title', 'kec', 'pointA'));
    }

    public function setCustomCalk(Kecamatan $kec, Request $request)
    {
        $data = $request->only([
            'calk',
        ]);

        $calk = json_decode($kec->calk, true);
        $calk['calk']['A'] = $data['calk'];

        $update = Kecamatan::where('id', $kec->id)->update([
            'calk' => json_encode($calk),
        ]);

        return response()->json([
            'success' => true,
            'msg' => 'Custom CALK Berhasil Diperbarui.',
        ]);
    }

    public function updateCoa(Request $request, Rekening $rekening)
    {
        $data = $request->only([
            'id_akun',
            'nama_akun',
        ]);

        $nama_akun = str_replace($data['id_akun'].'. ', '', $data['nama_akun']);
        $nama_akun = trim($nama_akun);

        if ($rekening->nama_akun != $nama_akun && $rekening->kode_akun == $data['id_akun']) {
            Rekening::where('kode_akun', $rekening->kode_akun)->update([
                'nama_akun' => $nama_akun,
            ]);

            return response()->json([
                'success' => true,
                'msg' => 'Akun dengan kode '.$data['id_akun'].' berhasil diperbarui',
                'nama_akun' => $data['id_akun'].'. '.$nama_akun,
                'id' => $data['id_akun'],
            ]);
        }

        return response()->json([
            'success' => false,
            'msg' => 'Akun gagal diperbarui',
        ]);
    }

    public function appToken(Request $request, Kecamatan $kec)
    {
        $token = $request->token;
        $lokasi = $kec->id;

        $unique_id = uniqid();
        $hashToken = Hash::make($token);
        $expiredAt = date('Y-m-d H:i:s', strtotime('+1 day'));

        Mobile::insert([
            'lokasi' => $lokasi,
            'unique_id' => $unique_id,
            'aktivasi' => $hashToken,
            'expired_at' => $expiredAt,
        ]);

        return response()->json([
            'success' => true,
            'msg' => 'Token berhasil diaktifkan',
        ]);
    }

    public function lembaga(Request $request, Kecamatan $kec)
    {
        $data = $request->only([
            'nama_bumdesma',
            'nomor_badan_hukum',
            'telpon',
            'email',
            'alamat',
            'peraturan_desa',
            'npwp',
            'tanggal_npwp',
        ]);

        $validate = Validator::make($data, [
            'nama_bumdesma' => 'required',
            'nomor_badan_hukum' => 'required',
            'telpon' => 'required',
            'email' => 'required',
            'alamat' => 'required',
            'peraturan_desa' => 'required',
            'npwp' => 'required',
            'tanggal_npwp' => 'required',
        ]);

        if ($validate->fails()) {
            return response()->json($validate->errors(), Response::HTTP_MOVED_PERMANENTLY);
        }

        $calk = [
            'peraturan_desa' => $request->peraturan_desa,
            'D' => [
                '1' => [
                    'd' => [
                        '1' => 0,
                        '2' => 0,
                        '3' => 0,
                    ],
                ],
                '2' => [
                    'a' => 0,
                    'b' => 0,
                    'c' => 0,
                ],
            ],
        ];

        $kecamatan = Kecamatan::where('id', $kec->id)->update([
            'nama_lembaga_sort' => ucwords(strtolower($data['nama_bumdesma'])),
            'nama_lembaga_long' => ucwords(strtolower($data['nama_bumdesma'])),
            'nomor_bh' => $data['nomor_badan_hukum'],
            'telpon_kec' => $data['telpon'],
            'email_kec' => $data['email'],
            'alamat_kec' => $data['alamat'],
            'npwp' => $data['npwp'],
            'tgl_npwp' => Tanggal::tglNasional($data['tanggal_npwp']),
            'calk' => json_encode($calk),
        ]);

        Session::put('nama_lembaga', ucwords(strtolower($data['nama_bumdesma'])));

        return response()->json([
            'success' => true,
            'msg' => 'Identitas Lembaga Berhasil Diperbarui.',
            'nama_lembaga' => ucwords(strtolower($data['nama_bumdesma'])),
        ]);
    }

    public function personalia(Request $request, Kecamatan $kec)
    {
        $data = $request->only([
            'sebutan',
            'nama',
        ]);

        $validate = Validator::make($data, [
            'sebutan' => 'required|array',
            'nama' => 'required|array',
        ]);

        if ($validate->fails()) {
            return response()->json($validate->errors(), Response::HTTP_MOVED_PERMANENTLY);
        }

        Personalia::where('lokasi', $kec->id)->delete();
        foreach ($data['sebutan'] as $key => $sebutan) {
            if (trim($sebutan) == '' || trim($data['nama'][$key]) == '') {
                continue;
            }

            Personalia::create([
                'lokasi' => $kec->id,
                'sebutan' => ucwords(strtolower($sebutan)),
                'nama' => $data['nama'][$key],
            ]);
        }

        return response()->json([
            'success' => true,
            'msg' => 'Personalia Berhasil Diperbarui.',
        ]);
    }

    public function pengelola(Request $request, Kecamatan $kec)
    {
        $data = $request->only([
            'sebutan_pengawas',
            'sebutan_verifikator',
            'kepala_lembaga',
            'kabag_administrasi',
            'kabag_keuangan',
            'bkk_bkm',
        ]);

        $validate = Validator::make($data, [
            'sebutan_pengawas' => 'required',
            'sebutan_verifikator' => 'required',
            'kepala_lembaga' => 'required',
            'kabag_administrasi' => 'required',
            'kabag_keuangan' => 'required',
            'bkk_bkm' => 'required',
        ]);

        if ($validate->fails()) {
            return response()->json($validate->errors(), Response::HTTP_MOVED_PERMANENTLY);
        }

        $kecamatan = Kecamatan::where('id', $kec->id)->update([
            'nama_bp_long' => ucwords(strtolower($data['sebutan_pengawas'])),
            'nama_bp_sort' => ucwords(strtolower($data['sebutan_pengawas'])),
            'nama_tv_long' => ucwords(strtolower($data['sebutan_verifikator'])),
            'nama_tv_sort' => ucwords(strtolower($data['sebutan_verifikator'])),
            'sebutan_level_1' => ucwords(strtolower($data['kepala_lembaga'])),
            'sebutan_level_2' => ucwords(strtolower($data['kabag_administrasi'])),
            'sebutan_level_3' => ucwords(strtolower($data['kabag_keuangan'])),
            'disiapkan' => ucwords(strtolower($data['bkk_bkm'])),
        ]);

        return response()->json([
            'success' => true,
            'msg' => 'Sebutan Pengelola Berhasil Diperbarui.',
        ]);
    }

    public function pinjaman(Request $request, Kecamatan $kec)
    {
        $data = $request->only([
            'default_jasa',
            'default_jangka',
            'pembulatan',
            'sistem',
        ]);

        $validate = Validator::make($data, [
            'default_jasa' => 'required',
            'default_jangka' => 'required',
            'pembulatan' => 'required',
        ]);

        if ($validate->fails()) {
            return response()->json($validate->errors(), Response::HTTP_MOVED_PERMANENTLY);
        }

        $data['pembulatan'] = "$data[sistem]$data[pembulatan]";

        $kecamatan = Kecamatan::where('id', $kec->id)->update([
            'def_jasa' => $data['default_jasa'],
            'def_jangka' => $data['default_jangka'],
            'pembulatan' => $data['pembulatan'],
        ]);

        return response()->json([
            'success' => true,
            'msg' => 'Sistem Pinjaman Berhasil Diperbarui.',
        ]);
    }

    public function asuransi(Request $request, Kecamatan $kec)
    {
        $data = $request->only([
            'nama_asuransi',
            'jenis_asuransi',
            'usia_maksimal',
            'presentase_premi',
        ]);

        $validate = Validator::make($data, [
            'nama_asuransi' => 'required',
            'jenis_asuransi' => 'required',
            'usia_maksimal' => 'required',
            'presentase_premi' => 'required',
        ]);

        if ($validate->fails()) {
            return response()->json($validate->errors(), Response::HTTP_MOVED_PERMANENTLY);
        }

        $kecamatan = Kecamatan::where('id', $kec->id)->update([
            'nama_asuransi_p' => $data['nama_asuransi'],
            'pengaturan_asuransi' => $data['jenis_asuransi'],
            'usia_mak' => $data['usia_maksimal'],
            'besar_premi' => $data['presentase_premi'],
        ]);

        return response()->json([
            'success' => true,
            'msg' => 'Pengaturan Asuransi Berhasil Diperbarui.',
        ]);
    }

    public function spk(Request $request, Kecamatan $kec)
    {
        $data = $request->only([
            'spk',
        ]);

        $validate = Validator::make($data, [
            'spk' => 'required',
        ]);

        if ($validate->fails()) {
            return response()->json($validate->errors(), Response::HTTP_MOVED_PERMANENTLY);
        }

        $spk = json_encode($data['spk']);

        $kecamatan = Kecamatan::where('id', $kec->id)->update([
            'redaksi_spk' => $spk,
        ]);

        return response()->json([
            'success' => true,
            'msg' => 'Redaksi Dokumen SPK Berhasil Diperbarui.',
        ]);
    }

    public function logo(Request $request, Kecamatan $kec)
    {
        $data = $request->only([
            'logo_kec',
        ]);

        $validate = Validator::make($data, [
            'logo_kec' => 'required|image|mimes:jpg,png,jpeg|max:4096',
        ]);

        if ($request->file('logo_kec')) {
            $extension = $request->file('logo_kec')->getClientOriginalExtension();

            $filename = time().'_'.$kec->id.'_'.date('Ymd').'.'.$extension;
            $path = $request->file('logo_kec')->storeAs('logo', $filename, 'supabase');

            $relativePath = str_replace(env('SUPABASE_PUBLIC_URL').'/', '', $kec->logo);
            if (Storage::disk('supabase')->exists($relativePath)) {
                if ($relativePath != 'logo/1.png') {
                    Storage::disk('supabase')->delete($relativePath);
                }
            }

            $publicUrl = env('SUPABASE_PUBLIC_URL').'/'.$path;
            $kecamatan = Kecamatan::where('id', $kec->id)->update([
                'logo' => $publicUrl,
            ]);

            Session::put('logo', $publicUrl);

            return response()->json([
                'success' => true,
                'msg' => 'Logo berhasil diperbarui.',
            ]);
        }

        return response()->json([
            'success' => false,
            'msg' => 'Logo gagal diperbarui',
        ]);
    }

    public function whatsapp($token)
    {
        // Legacy route, kept for backward compatibility (no-op on Evolution migration).
        return response()->json([
            'success' => true,
            'msg' => 'Legacy endpoint disabled. Use Evolution API endpoints.',
        ]);
    }

    public function beritaAcara(Request $request, Kecamatan $kec)
    {
        $data = $request->only([
            'ba',
        ]);

        $validate = Validator::make($data, [
            'ba' => 'required',
        ]);

        if ($validate->fails()) {
            return response()->json($validate->errors(), Response::HTTP_MOVED_PERMANENTLY);
        }

        $ba = $data['ba'];
        $ba = str_replace('<p>', '<div>', $ba);
        $ba = str_replace('</p>', '</div>', $ba);

        if ($ba != '<div><br></div>') {
            $ba = json_encode($ba);
            $kecamatan = Kecamatan::where('id', $kec->id)->update([
                'berita_acara' => $ba,
            ]);

            return response()->json([
                'success' => true,
                'msg' => 'Berita Acara Berhasil Diperbarui.',
            ]);
        }

        return response()->json([
            'success' => false,
            'msg' => 'Berita Acara Gagal Diperbarui.',
        ]);
    }

    public function tanggungRenteng(Request $request, Kecamatan $kec)
    {
        $data = $request->only([
            'tanggung-renteng',
        ]);

        $validate = Validator::make($data, [
            'tanggung-renteng' => 'required',
        ]);

        if ($validate->fails()) {
            return response()->json($validate->errors(), Response::HTTP_MOVED_PERMANENTLY);
        }

        $data['tanggung-renteng'] = str_replace('<ol>', '', $data['tanggung-renteng']);
        $data['tanggung-renteng'] = str_replace('</ol>', '', $data['tanggung-renteng']);
        $data['tanggung-renteng'] = str_replace('<ul>', '', $data['tanggung-renteng']);
        $data['tanggung-renteng'] = str_replace('</ul>', '', $data['tanggung-renteng']);

        $tanggung_renteng = json_encode($data['tanggung-renteng']);

        $kecamatan = Kecamatan::where('id', $kec->id)->update([
            'tanggung_renteng' => $tanggung_renteng,
        ]);

        return response()->json([
            'success' => true,
            'msg' => 'Pengaturan Tanggung Renteng Pinjaman Berhasil Diperbarui.',
        ]);
    }

    public function ttdPelaporan()
    {
        $title = 'Pengaturan Tanda Tangan Pelaporan';
        $kec = Kecamatan::where('id', Session::get('lokasi'))->with('ttd')->first();
        $ttd = TandaTanganLaporan::where([['lokasi', Session::get('lokasi')]])->first();

        $tanggal = false;
        if ($ttd) {
            $str = strpos($ttd->tanda_tangan_pelaporan, '{tanggal}');

            if ($str !== false) {
                $tanggal = true;
            }
        }

        return view('sop.partials.ttd_pelaporan')->with(compact('title', 'kec', 'tanggal'));
    }

    public function ttdSpk()
    {
        $title = 'Pengaturan Tanda Tangan SPK';
        $kec = Kecamatan::where('id', Session::get('lokasi'))->with('ttd')->first();
        $keyword = Pinjaman::keyword();

        return view('sop.partials.ttd_spk')->with(compact('title', 'kec', 'keyword'));
    }

    public function RencanaPendapatanJasa()
    {
        $title = 'Pengaturan Rencana Pendapatan Jasa';
        $kec = Kecamatan::where('id', Session::get('lokasi'))->with('ttd')->first();

        return view('sop.partials.rencana_pendapatan_jasa')->with(compact('title', 'kec'));
    }

    public function PreviewPendapatanJasa(Request $request)
    {
        $data = $request->only([
            'tahun',
            'jenis',
        ]);

        $tb_ra = 'rencana_angsuran_'.Session::get('lokasi');
        $tb_pinj = 'pinjaman_kelompok_'.Session::get('lokasi');

        $data['kec'] = Kecamatan::where('id', Session::get('lokasi'))->with('kabupaten')->first();
        $data['jenis_pp'] = JenisProdukPinjaman::where(function ($query) {
            $query->where('lokasi', '0')
                ->where('kecuali', 'NOT LIKE', '%#'.session('lokasi').'#%');
        })
            ->orWhere(function ($query) {
                $query->where('lokasi', session('lokasi'))
                    ->where('kecuali', 'NOT LIKE', '%#'.session('lokasi').'#%');
            })
            ->get();
        $data['rencana'] = RencanaAngsuran::select($tb_ra.'.*', $tb_pinj.'.jenis_pp')->join($tb_pinj, $tb_pinj.'.id', '=', $tb_ra.'.loan_id')
            ->where($tb_ra.'.jatuh_tempo', 'LIKE', $data['tahun'].'-%')
            ->whereNotIn($tb_pinj.'.status', ['P', 'V', 'W'])
            ->orderBy($tb_ra.'.jatuh_tempo')->get();

        $data['logo'] = $data['kec']->logo;
        $data['nama_lembaga'] = $data['kec']->nama_lembaga_sort;
        $data['nama_kecamatan'] = $data['kec']->sebutan_kec.' '.$data['kec']->nama_kec;

        if (Keuangan::startWith($data['kec']->kabupaten->nama_kab, 'KOTA') || Keuangan::startWith($data['kec']->kabupaten->nama_kab, 'KAB')) {
            $data['nama_kecamatan'] .= ' '.ucwords(strtolower($data['kec']->kabupaten->nama_kab));
            $data['nama_kabupaten'] = ucwords(strtolower($data['kec']->kabupaten->nama_kab));
        } else {
            $data['nama_kecamatan'] .= ' Kabupaten '.ucwords(strtolower($data['kec']->kabupaten->nama_kab));
            $data['nama_kabupaten'] = ' Kabupaten '.ucwords(strtolower($data['kec']->kabupaten->nama_kab));
        }

        $data['nomor_usaha'] = 'SK Kemenkumham RI No.'.$data['kec']->nomor_bh;
        $data['info'] = $data['kec']->alamat_kec.', Telp.'.$data['kec']->telpon_kec;
        $data['email'] = $data['kec']->email_kec;
        $data['kab'] = $data['kec']->kabupaten;

        $data['pendapatan_jasa'] = [];

        foreach ($data['jenis_pp'] as $jpp) {
            $data['pendapatan_jasa'][$jpp->id] = [
                'nama' => $jpp->nama_jpp,
                'data' => [
                    1 => [
                        'pokok' => 0,
                        'jasa' => 0,
                    ],
                    2 => [
                        'pokok' => 0,
                        'jasa' => 0,
                    ],
                    3 => [
                        'pokok' => 0,
                        'jasa' => 0,
                    ],
                    4 => [
                        'pokok' => 0,
                        'jasa' => 0,
                    ],
                    5 => [
                        'pokok' => 0,
                        'jasa' => 0,
                    ],
                    6 => [
                        'pokok' => 0,
                        'jasa' => 0,
                    ],
                    7 => [
                        'pokok' => 0,
                        'jasa' => 0,
                    ],
                    8 => [
                        'pokok' => 0,
                        'jasa' => 0,
                    ],
                    9 => [
                        'pokok' => 0,
                        'jasa' => 0,
                    ],
                    10 => [
                        'pokok' => 0,
                        'jasa' => 0,
                    ],
                    11 => [
                        'pokok' => 0,
                        'jasa' => 0,
                    ],
                    12 => [
                        'pokok' => 0,
                        'jasa' => 0,
                    ],
                ],
            ];
        }

        foreach ($data['rencana'] as $ra) {
            $bulan = intval(Tanggal::bulan($ra->jatuh_tempo));

            $data['pendapatan_jasa'][$ra->jenis_pp]['data'][$bulan]['pokok'] += $ra->wajib_pokok;
            $data['pendapatan_jasa'][$ra->jenis_pp]['data'][$bulan]['jasa'] += $ra->wajib_jasa;
        }

        $view = view('sop.partials.preview.rencana_pendapatan_jasa', $data);

        if ($data['jenis'] == 'pdf') {
            $pdf = PDF::loadHTML($view)->setPaper('A4', 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    public function simpanTtdPelaporan(Request $request)
    {
        $data = $request->only([
            'field',
            'tanda_tangan',
        ]);

        if ($data['field'] == 'tanda_tangan_pelaporan') {
            $data['tanda_tangan'] = preg_replace('/<table[^>]*>/', '<table class="p0" border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px;">', $data['tanda_tangan'], 1);
        } else {
            $data['tanda_tangan'] = preg_replace('/<table[^>]*>/', '<table class="p0" border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 12px;">', $data['tanda_tangan'], 1);
        }
        $data['tanda_tangan'] = preg_replace('/height:\s*[^;]+;?/', '', $data['tanda_tangan']);

        $data['tanda_tangan'] = str_replace('colgroup', 'tr', $data['tanda_tangan']);
        $data['tanda_tangan'] = preg_replace('/<col([^>]*)>/', '<td$1>&nbsp;</td>', $data['tanda_tangan']);

        $ttd = TandaTanganLaporan::where('lokasi', Session::get('lokasi'))->count();
        if ($ttd <= 0) {
            $insert = [
                'lokasi' => Session::get('lokasi'),
            ];

            if ($data['field'] == 'tanda_tangan_pelaporan') {
                $insert['tanda_tangan_spk'] = '';
                $insert['tanda_tangan_pelaporan'] = json_encode($data['tanda_tangan']);
            } else {
                $insert['tanda_tangan_pelaporan'] = '';
                $insert['tanda_tangan_spk'] = json_encode($data['tanda_tangan']);
            }

            $tanda_tangan = TandaTanganLaporan::create($insert);
        } else {
            // dd($data['tanda_tangan']);
            $tanda_tangan = TandaTanganLaporan::where('lokasi', Session::get('lokasi'))->update([
                $data['field'] => json_encode($data['tanda_tangan']),
            ]);
        }

        return response()->json([
            'success' => true,
            'msg' => ucwords(str_replace('_', ' ', $data['field'])).' Berhasil diperbarui',
        ]);
    }

    public function invoice()
    {
        if (request()->ajax()) {
            $invoice = AdminInvoice::where('lokasi', Session::get('lokasi'))->with('jp')->withSum('trx', 'jumlah')->get();

            return DataTables::of($invoice)
                ->editColumn('tgl_invoice', function ($row) {
                    return Tanggal::tglIndo($row->tgl_invoice);
                })
                ->editColumn('jumlah', function ($row) {
                    return number_format($row->jumlah);
                })
                ->editColumn('status', function ($row) {
                    if ($row->status == 'PAID') {
                        return '<span class="badge badge-success">'.$row->status.'</span>';
                    }

                    return '<span class="badge badge-danger">'.$row->status.'</span>';
                })
                ->addColumn('saldo', function ($row) {
                    if ($row->trx_sum_jumlah) {
                        return number_format($row->jumlah - $row->trx_sum_jumlah);
                    }

                    return number_format($row->jumlah);
                })
                ->rawColumns(['status'])
                ->make(true);
        }

        $title = 'Daftar Invoice';

        return view('sop.invoice')->with(compact('title'));
    }

    public function calk(Request $request, Kecamatan $kec)
    {
        $data = $request->only([
            'peraturan_desa',
            'bantuan_rumah_tangga',
            'pengembangan_kapasitas',
            'pelatihan_masyarakat',
            'peningkatan_modal',
            'penambahan_investasi',
            'pendirian_unit_usaha',
        ]);

        $validate = Validator::make($data, [
            'peraturan_desa' => 'required',
            'bantuan_rumah_tangga' => 'required',
            'pengembangan_kapasitas' => 'required',
            'pelatihan_masyarakat' => 'required',
            'peningkatan_modal' => 'required',
            'penambahan_investasi' => 'required',
            'pendirian_unit_usaha' => 'required',
        ]);

        if ($validate->fails()) {
            return response()->json($validate->errors(), Response::HTTP_MOVED_PERMANENTLY);
        }

        $data = [
            'peraturan_desa' => $request->peraturan_desa,
            'D' => [
                '1' => [
                    'd' => [
                        '1' => $request->bantuan_rumah_tangga,
                        '2' => $request->pengembangan_kapasitas,
                        '3' => $request->pelatihan_masyarakat,
                    ],
                ],
                '2' => [
                    'a' => str_replace(',', '', $request->peningkatan_modal),
                    'b' => str_replace(',', '', $request->penambahan_investasi),
                    'c' => str_replace(',', '', $request->pendirian_unit_usaha),
                ],
            ],
        ];

        $kec = Kecamatan::where('id', $kec->id)->update([
            'calk' => json_encode($data),
        ]);

        return response()->json([
            'success' => true,
            'msg' => 'Pengaturan CALK Berhasil Diperbarui.',
        ]);
    }

    public function pesanWhatsapp(Request $request, Kecamatan $kec)
    {
        if ($kec->id != Session::get('lokasi')) {
            abort(404);
        }

        $data = $request->only([
            'tagihan',
            'angsuran',
        ]);

        $validate = Validator::make($data, [
            'tagihan' => 'required',
            'angsuran' => 'required',
        ]);

        if ($validate->fails()) {
            return response()->json($validate->errors(), Response::HTTP_MOVED_PERMANENTLY);
        }

        $wa = [
            'tagihan' => $data['tagihan'],
            'angsuran' => $data['angsuran'],
        ];

        Kecamatan::where('id', $kec->id)->update([
            'whatsapp' => $wa,
        ]);

        return response()->json([
            'success' => true,
            'msg' => 'Pengaturan Pesan Whatsapp Berhasil Diperbarui.',
        ]);
    }

    public function detailInvoice($inv)
    {
        $inv = AdminInvoice::where('idv', $inv)->with('jp')->first();

        $title = 'Invoice #'.$inv->nomor.' - '.$inv->jp->nama_jp;

        return view('sop.detail_invoice')->with(compact('title', 'inv'));
    }

    public function localView($key, $val = '')
    {
        if (Cookie::has('config')) {
            $config = json_decode(request()->cookie('config'), true);
            cookie()->forget('config');
        } else {
            $config = [
                'sidebarColor' => 'success',
                'sidebarType' => 'bg-gradient-dark',
                'navbarFixed' => 'position-sticky blur shadow-blur mt-4 left-auto top-1 z-index-sticky',
                'sidebarMini' => '',
                'darkMode' => '',
            ];
        }

        $config[$key] = $val;

        $cookie = cookie('config', json_encode($config), 60 * 24 * 365);
        Session::put('config', json_encode($config));

        return response()->json([
            'success' => true,
            'msg' => 'Pengaturan Halaman berhasil disimpan',
        ])->cookie($cookie);
    }

    public function save_whatsapp_session(Request $request)
    {
        $lokasi = Session::get('lokasi');
        if (! $lokasi) {
            return response()->json(['success' => false, 'msg' => 'Lokasi session tidak ditemukan']);
        }

        $kec = Kecamatan::where('id', $lokasi)->first();
        if (! $kec) {
            return response()->json(['success' => false, 'msg' => 'Kecamatan tidak ditemukan']);
        }

        $apiKey = env('WA_GATEWAY_API_KEY');
        $base = rtrim(env('WA_GATEWAY_BASE', 'https://wa-gateway.enpiistudio.com'), '/');

        if (! $apiKey) {
            return response()->json(['success' => false, 'msg' => 'WA_GATEWAY_API_KEY belum di-set di .env']);
        }

        $instanceName = 'sidbm-'.\Illuminate\Support\Str::slug($kec->nama_lembaga_sort ?? $kec->nama_kec ?? 'kec').'-'.$kec->id;

        try {
            // 1) Create instance — use Guzzle explicit so we control headers fully.
            //    NOTE: enpii Cloudflare WAF blocks User-Agent: GuzzleHttp/7 → use browser-like UA.
            $client = new \GuzzleHttp\Client([
                'timeout' => 15,
                'http_errors' => false,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                ],
            ]);

            $createRes = $client->post($base.'/create-instance', [
                'headers' => [
                    'Authorization' => 'Basic '.base64_encode($apiKey),
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'body' => json_encode([
                    'instance' => $instanceName,
                    'lokasi' => $lokasi,
                ]),
            ]);

            $createBodyRaw = (string) $createRes->getBody();
            $createBody = json_decode($createBodyRaw, true);
            \Log::info('Evolution create instance', [
                'instance' => $instanceName,
                'status' => $createRes->getStatusCode(),
                'has_qr' => ! empty($createBody['qrcode']['base64'] ?? null),
            ]);

            $createStatus = $createRes->getStatusCode();
            if ($createStatus !== 200 && $createStatus !== 201 && $createStatus !== 403 && $createStatus !== 409) {
                $errMsg = $createBody['response']['message'] ?? $createBodyRaw;
                if (is_array($errMsg)) {
                    $errMsg = json_encode($errMsg);
                }

                return response()->json([
                    'success' => false,
                    'msg' => 'Gagal membuat instance (HTTP '.$createStatus.'): '.$errMsg,
                ]);
            }

            $instanceBody = $createBody['data']['instance'] ?? [];
            $qr = $instanceBody['qr'] ?? null;
            $pairingCode = $instanceBody['pairingCode'] ?? ($instanceBody['code'] ?? null);
            $status = $instanceBody['status'] ?? null;

            // If create returned no QR (instance already existed and was close), restart + poll connect
            if (! $qr) {
                try {
                    $client->post($base.'/instance/restart/'.$instanceName, [
                        'headers' => ['apikey' => $apiKey, 'Accept' => 'application/json'],
                    ]);
                } catch (\Exception $e) {
                    \Log::warning('Evolution restart error: '.$e->getMessage());
                }

                for ($i = 0; $i < 5; $i++) {
                    sleep(2);
                    $connectRes = $client->get($base.'/instance/connect/'.$instanceName, [
                        'headers' => ['apikey' => $apiKey, 'Accept' => 'application/json'],
                    ]);
                    $connectBodyRaw = (string) $connectRes->getBody();
                    $connectBody = json_decode($connectBodyRaw, true);
                    \Log::info('Evolution connect', [
                        'attempt' => $i + 1,
                        'status' => $connectRes->getStatusCode(),
                        'has_qr' => ! empty($connectBody['qrcode']['base64'] ?? null),
                    ]);

                    $qr = $connectBody['qrcode']['base64']
                        ?? $connectBody['base64']
                        ?? null;
                    $pairingCode = $connectBody['qrcode']['pairingCode']
                        ?? $connectBody['pairingCode']
                        ?? $connectBody['qrcode']['code']
                        ?? $connectBody['code']
                        ?? null;

                    if ($qr) {
                        break;
                    }
                }
            }

            // 3) Persist
            Whatsapp::updateOrCreate(
                ['lokasi' => $lokasi],
                [
                    'nama' => $kec->nama_lembaga_sort ?? 'BUMDESMA',
                    'instance_name' => $instanceName,
                    'status' => 'pending',
                ]
            );

            return response()->json([
                'success' => true,
                'instance' => $instanceName,
                'qr' => $qr,
                'pairingCode' => $pairingCode,
                'state' => $status ?? null,
            ]);
        } catch (\Exception $e) {
            \Log::error('Evolution save_whatsapp_session error: '.$e->getMessage());

            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    public function delete_whatsapp_session(Request $request)
    {
        $lokasi = $request->input('lokasi', Session::get('lokasi'));
        if ($lokasi === null || $lokasi === '') {
            return response()->json([
                'success' => false,
                'deleted' => 0,
                'message' => 'Lokasi tidak ditemukan.',
            ], 422);
        }

        $wa = Whatsapp::where('lokasi', $lokasi)->first();
        $apiKey = env('WA_GATEWAY_API_KEY');
        $base = rtrim(env('WA_GATEWAY_BASE', 'https://wa-gateway.enpiistudio.com'), '/');

        if ($wa && $wa->instance_name && $apiKey) {
            $client = new \GuzzleHttp\Client([
                'timeout' => 10,
                'http_errors' => false,
                'headers' => [
                    'Authorization' => 'Basic '.base64_encode($apiKey),
                    'Accept' => 'application/json',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                ],
            ]);
            try {
                $client->delete($base.'/delete-instance', [
                    'query' => ['instance' => $wa->instance_name],
                ]);
            } catch (\Exception $e) {
                \Log::warning('Gateway delete error: '.$e->getMessage());
            }
        }

        $deleted = Whatsapp::where('lokasi', $lokasi)->delete();

        return response()->json([
            'success' => true,
            'deleted' => $deleted,
        ]);
    }
}
