<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AkunLevel1;
use App\Models\ArusKas;
use App\Models\JenisLaporan;
use App\Models\Kabupaten;
use App\Models\Kecamatan;
use App\Models\Rekening;
use App\Models\Transaksi;
use App\Utils\Keuangan;
use App\Utils\Tanggal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use PDF;
use Session;

class KabupatenController extends Controller
{
    public function index($kd_prov, $kd_kab)
    {
        Session::put('jumlah', 0);
        Session::put('lokasi_terpilih', 0);
        Session::put('data_saldo', []);

        $laporan = JenisLaporan::where([
            ['file', '!=', '0'],
            ['kab', '!=', '0'],
        ])->orderBy('urut', 'ASC')->get();
        $kab = Kabupaten::where([
            ['kd_prov', $kd_prov],
            ['kd_kab', $kd_kab],
        ])->with([
            'wilayah',
            'kec' => function ($query) {
                $query->orderBy('kd_kec', 'ASC');
            },
        ])->first();

        $title = 'Pelaporan Kabupaten '.ucwords(strtolower($kab->nama_kab));

        return view('admin.kabupaten.index')->with(compact('title', 'laporan', 'kab'));
    }

    public function subLaporan($file)
    {
        if ($file == 3) {
            $rekening = Rekening::orderBy('kode_akun', 'ASC')->get();

            return view('admin.kabupaten.sub_laporan')->with(compact('file', 'rekening'));
        }

        if ($file == 14) {
            $data = [
                0 => [
                    'title' => '01. Januari - Maret',
                    'id' => '1,2,3',
                ],
                1 => [
                    'title' => '02. April - Juni',
                    'id' => '4,5,6',
                ],
                2 => [
                    'title' => '03. Juli - September',
                    'id' => '7,8,9',
                ],
                3 => [
                    'title' => '04. Oktober - Desember',
                    'id' => '10,11,12',
                ],
            ];

            return view('admin.kabupaten.sub_laporan')->with(compact('file', 'data'));
        }

        return view('admin.kabupaten.sub_laporan')->with(compact('file'));
    }

    public function data($lokasi)
    {
        $keuangan = new Keuangan;
        $tahun = request()->get('tahun');
        $bulan = request()->get('bulan');
        $laporan = request()->get('laporan');

        if ($lokasi && $tahun && $bulan) {
            Session::put('lokasi', $lokasi);
            config(['tenant.suffix' => '_'.$lokasi]);

            $kec = Kecamatan::where('id', $lokasi)->first();

            if ($laporan == 'arus_kas') {
                $data_saldo = (Session::get('data_saldo')) ? Session::get('data_saldo') : [];
                $awal_bulan = date('Y-m-d', strtotime($tahun.'-'.$bulan.'-01'));
                $akhir_bulan = date('Y-m-t', strtotime($awal_bulan));
                $akhir_bulan_lalu = date('Y-m-d', strtotime('-1 days', strtotime($awal_bulan)));

                $trx = Transaksi::where([
                    ['tgl_transaksi', '>=', $awal_bulan],
                    ['tgl_transaksi', '<=', $akhir_bulan],
                ])->get()->toArray();
                $saldo_bulan_lalu = $keuangan->saldoKas($akhir_bulan_lalu);

                $data_saldo[$kec->kd_kec] = [
                    'transaksi' => $trx,
                    'saldo_bulan_lalu' => $saldo_bulan_lalu,
                ];

                Session::put('data_saldo', $data_saldo);

                return response()->json([
                    'success' => true,
                    'lokasi' => Session::get('lokasi'),
                    'kd_kec' => $kec->kd_kec,
                    'msg' => 'Transaksi '.$kec->sebutan_kec.' '.$kec->nama_kec.' berhasil disimpan',
                ]);
            }

            $data_saldo = (Session::get('data_saldo')) ? Session::get('data_saldo') : [];
            $tgl_kondisi = $tahun.'-'.$bulan.'-01';
            $rekening = Rekening::where('lev1', '<=', '3')->with([
                'kom_saldo' => function ($query) use ($tahun, $bulan) {
                    $query->where('tahun', $tahun)->where(function ($query) use ($bulan) {
                        $query->where('bulan', '0')->orwhere('bulan', $bulan);
                    });
                },
            ])->orderBy('kode_akun', 'ASC')->get();

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
            $jumlah_rekening = (count($rekening) + count($laba_rugi));

            if (Session::get('jumlah') < $jumlah_rekening) {
                Session::put('jumlah', $jumlah_rekening);
                Session::put('lokasi_terpilih', $lokasi);
            }
            $data_saldo[$kec->kd_kec] = [
                'saldo' => $rekening,
                'laba_rugi' => $laba_rugi,
            ];

            Session::put('data_saldo', $data_saldo);

            return response()->json([
                'success' => true,
                'lokasi' => Session::get('lokasi'),
                'kd_kec' => $kec->kd_kec,
                'msg' => 'Saldo '.$kec->sebutan_kec.' '.$kec->nama_kec.' berhasil disimpan',
            ]);
        }

        abort(404);
    }

    public function preview(Request $request, $kd_kab)
    {
        $data = $request->only([
            'tahun',
            'bulan',
            'laporan',
            'sub_laporan',
            'type',
        ]);

        Session::put('lokasi', Session::get('lokasi_terpilih'));
        config(['tenant.suffix' => '_'.Session::get('lokasi_terpilih')]);

        $data['kab'] = Kabupaten::where([
            ['kd_kab', $kd_kab],
        ])->with([
            'wilayah',
            'kec' => function ($query) {
                $query->orderBy('kd_kec', 'ASC');
            },
        ])->first();

        if ($data['tahun'] == null) {
            abort(404);
        }

        $data['bulanan'] = true;
        if ($data['bulan'] == null) {
            $data['bulanan'] = false;
            $data['bulan'] = '12';
        }

        $data['logo'] = $data['kab']->logo ? $this->supabaseToBase64($data['kab']->logo) : null;
        $data['hari'] = date('t', strtotime($data['tahun'].'-'.$data['bulan'].'-01'));
        $data['tgl_kondisi'] = $data['tahun'].'-'.$data['bulan'].'-'.$data['hari'];

        $file = $request->laporan;

        return $this->$file($data);
    }

    private function supabaseToBase64($url)
    {
        $response = Http::withOptions([
            'verify' => false,
        ])->get($url);

        if (! $response->successful()) {
            return null;
        }

        $binary = $response->body();

        $extension = pathinfo($url, PATHINFO_EXTENSION);
        $mime = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
        ][$extension] ?? 'application/octet-stream';

        return "data:$mime;base64,".base64_encode($binary);
    }

    public function neraca($data)
    {
        $keuangan = new Keuangan;
        $neraca = [];

        $akun1 = AkunLevel1::where('lev1', '<=', '3')->with([
            'akun2',
            'akun2.akun3',
        ])->orderBy('kode_akun', 'ASC')->get();

        foreach ($akun1 as $lev1) {
            $neraca[$lev1->kode_akun] = [
                'kode_akun' => $lev1->kode_akun,
                'nama_akun' => $lev1->nama_akun,
            ];

            foreach ($lev1->akun2 as $lev2) {
                $neraca[$lev1->kode_akun]['child'][$lev2->kode_akun] = [
                    'kode_akun' => $lev2->kode_akun,
                    'nama_akun' => $lev2->nama_akun,
                ];

                foreach ($lev2->akun3 as $lev3) {
                    $neraca[$lev1->kode_akun]['child'][$lev2->kode_akun]['child'][$lev3->kode_akun] = [
                        'kode_akun' => $lev3->kode_akun,
                        'nama_akun' => $lev3->nama_akun,
                        'child' => [],
                    ];
                }
            }
        }

        $data_saldo = Session::get('data_saldo');
        foreach ($data['kab']->kec as $kec) {
            foreach ($data_saldo[$kec->kd_kec]['saldo'] as $rek) {
                $saldo = $keuangan->komSaldo($rek);
                if ($rek->kode_akun == '3.2.02.01') {
                    $saldo = 0;
                    foreach ($data_saldo[$kec->kd_kec]['laba_rugi'] as $lb) {
                        if ($lb->lev1 == 5) {
                            $saldo -= $keuangan->komSaldo($lb);
                        } else {
                            $saldo += $keuangan->komSaldo($lb);
                        }
                    }
                }

                $kode_akun = explode('.', $rek->kode_akun);
                $lev1 = $kode_akun[0];
                $lev2 = $kode_akun[1];
                $lev3 = $kode_akun[2];

                $akun_level1 = $lev1.'.0.00.00';
                $akun_level2 = $lev1.'.'.$lev2.'.00.00';
                $akun_level3 = $lev1.'.'.$lev2.'.'.$lev3.'.00';

                if (! array_key_exists($rek->kode_akun, $neraca[$akun_level1]['child'][$akun_level2]['child'][$akun_level3]['child'])) {
                    $neraca[$akun_level1]['child'][$akun_level2]['child'][$akun_level3]['child'][$rek->kode_akun] = [
                        'kode_akun' => $rek->kode_akun,
                        'nama_akun' => $rek->nama_akun,
                        'saldo' => $saldo,
                    ];
                } else {
                    $neraca[$akun_level1]['child'][$akun_level2]['child'][$akun_level3]['child'][$rek->kode_akun]['saldo'] += $saldo;
                }
            }
        }

        $data['neraca'] = $neraca;
        $data['sub_judul'] = 'Per '.date('t', strtotime($data['tgl_kondisi'])).' '.Tanggal::namaBulan($data['tgl_kondisi']).' '.Tanggal::tahun($data['tgl_kondisi']);
        $data['tgl'] = Tanggal::namaBulan($data['tgl_kondisi']).' '.Tanggal::tahun($data['tgl_kondisi']);

        $view = view('admin.kabupaten.laporan.views.neraca', $data)->render();
        $pdf = PDF::loadHTML($view);

        return $pdf->stream();
    }

    public function laba_rugi($data)
    {
        $data_laba_rugi = [];
        $pph = [
            'saldo' => 0,
            'saldo_bln_lalu' => 0,
        ];
        $akun1 = AkunLevel1::where('lev1', '>', '3')->with([
            'akun2',
        ])->orderBy('kode_akun', 'ASC')->get();

        foreach ($akun1 as $lev1) {
            foreach ($lev1->akun2 as $lev2) {
                // Pendapatan
                if ($lev2->lev1 == '4' && $lev2->lev2 == '1') {
                    $data_laba_rugi['pendapatan'][$lev2->kode_akun] = [
                        'kode_akun' => $lev2->kode_akun,
                        'nama_akun' => $lev2->nama_akun,
                        'rek' => [],
                    ];
                }

                // Beban
                if ($lev2->lev1 == '5' && ($lev2->lev2 == '1' || $lev2->lev2 == '2')) {
                    $data_laba_rugi['beban'][$lev2->kode_akun] = [
                        'kode_akun' => $lev2->kode_akun,
                        'nama_akun' => $lev2->nama_akun,
                        'rek' => [],
                    ];
                }

                // Pendapatan Non Operasional
                if ($lev2->lev1 == '4' && ($lev2->lev2 == '2' || $lev2->lev2 == '3')) {
                    $data_laba_rugi['pendapatan_non_ops'][$lev2->kode_akun] = [
                        'kode_akun' => $lev2->kode_akun,
                        'nama_akun' => $lev2->nama_akun,
                        'rek' => [],
                    ];
                }

                // Beban Non Operasional
                if ($lev2->lev1 == '5' && $lev2->lev2 == '3') {
                    $data_laba_rugi['beban_non_ops'][$lev2->kode_akun] = [
                        'kode_akun' => $lev2->kode_akun,
                        'nama_akun' => $lev2->nama_akun,
                        'rek' => [],
                    ];
                }
            }
        }

        $data_saldo = Session::get('data_saldo');
        foreach ($data['kab']->kec as $kec) {
            // if ($kec->kd_kec != '35.07.07') continue;
            foreach ($data_saldo[$kec->kd_kec]['laba_rugi'] as $rek) {
                $awal_debit = 0;
                $awal_kredit = 0;

                $saldo_debit = 0;
                $saldo_kredit = 0;

                $kredit_bulan_lalu = 0;
                $debit_bulan_lalu = 0;

                foreach ($rek->kom_saldo as $kom_saldo) {
                    if ($kom_saldo->bulan == 0) {
                        $awal_debit += floatval($kom_saldo->debit);
                        $awal_kredit += floatval($kom_saldo->kredit);
                    } else {
                        $saldo_debit += floatval($kom_saldo->debit);
                        $saldo_kredit += floatval($kom_saldo->kredit);
                    }
                }

                if ($rek->lev1 == 1 || $rek->lev1 == '5') {
                    $saldo_awal = $awal_debit - $awal_kredit;
                    $saldo_sd_bulan_ini = $saldo_awal + ($saldo_debit - $saldo_kredit);
                } else {
                    $saldo_awal = $awal_kredit - $awal_debit;
                    $saldo_sd_bulan_ini = $saldo_awal + ($saldo_kredit - $saldo_debit);
                }

                if ($rek->saldo && $data['bulan'] > 1) {
                    $debit_bulan_lalu += $rek->saldo->debit;
                    $kredit_bulan_lalu += $rek->saldo->kredit;
                }

                if ($rek->lev1 == 1 || $rek->lev1 == '5') {
                    $saldo_bulan_lalu = $saldo_awal + ($debit_bulan_lalu - $kredit_bulan_lalu);
                } else {
                    $saldo_bulan_lalu = $saldo_awal + ($kredit_bulan_lalu - $debit_bulan_lalu);
                }

                $kode_akun = explode('.', $rek->kode_akun);
                $lev1 = $kode_akun[0];
                $lev2 = $kode_akun[1];
                $lev3 = $kode_akun[2];

                $akun_level1 = $lev1.'.0.00.00';
                $akun_level2 = $lev1.'.'.$lev2.'.00.00';
                $akun_level3 = $lev1.'.'.$lev2.'.'.$lev3.'.00';

                // Pendapatan
                if ($lev1 == '4' && $lev2 == '1') {
                    $laba_rugi = 'pendapatan';
                }

                // Beban
                if ($lev1 == '5' && ($lev2 == '1' || $lev2 == '2')) {
                    $laba_rugi = 'beban';
                }

                // Pendapatan Non Operasional
                if ($lev1 == '4' && ($lev2 == '2' || $lev2 == '3')) {
                    $laba_rugi = 'pendapatan_non_ops';
                }

                // Beban Non Operasional
                if ($lev1 == '5' && $lev2 == '3') {
                    $laba_rugi = 'beban_non_ops';
                }

                if ($rek->kode_akun == '5.4.01.01') {
                    $pph = [
                        'kode_akun' => $rek->kode_akun,
                        'nama_akun' => $rek->nama_akun,
                        'saldo' => $saldo_sd_bulan_ini,
                        'saldo_bln_lalu' => $saldo_bulan_lalu,
                    ];

                    $pph['saldo'] += $saldo_sd_bulan_ini;
                    $pph['saldo_bln_lalu'] += $saldo_bulan_lalu;
                } else {
                    if (! array_key_exists($rek->kode_akun, $data_laba_rugi[$laba_rugi][$akun_level2]['rek'])) {
                        $data_laba_rugi[$laba_rugi][$akun_level2]['rek'][$rek->kode_akun] = [
                            'kode_akun' => $rek->kode_akun,
                            'nama_akun' => $rek->nama_akun,
                            'saldo' => $saldo_sd_bulan_ini,
                            'saldo_bln_lalu' => $saldo_bulan_lalu,
                        ];
                    } else {
                        $data_laba_rugi[$laba_rugi][$akun_level2]['rek'][$rek->kode_akun]['saldo'] += $saldo_sd_bulan_ini;
                        $data_laba_rugi[$laba_rugi][$akun_level2]['rek'][$rek->kode_akun]['saldo_bln_lalu'] += $saldo_bulan_lalu;
                    }
                }
            }
        }

        $data['laba_rugi'] = [
            'pendapatan' => $data_laba_rugi['pendapatan'],
            'beban' => $data_laba_rugi['beban'],
            'pendapatan_non_ops' => $data_laba_rugi['pendapatan_non_ops'],
            'beban_non_ops' => $data_laba_rugi['beban_non_ops'],
        ];

        $data['pph'] = $pph;
        $data['sub_judul'] = 'Periode '.Tanggal::tglLatin($data['tahun'].'-01-01').' S.D '.Tanggal::tglLatin($data['tgl_kondisi']);
        $data['tgl'] = Tanggal::namaBulan($data['tgl_kondisi']).' '.Tanggal::tahun($data['tgl_kondisi']);
        $data['bulan_lalu'] = date('Y-m-t', strtotime('-1 month', strtotime($data['tahun'].'-'.$data['bulan'].'-10')));
        $data['header_lalu'] = 'Bulan Lalu';
        $data['header_sekarang'] = 'Bulan Ini';

        $view = view('admin.kabupaten.laporan.views.laba_rugi', $data)->render();
        $pdf = PDF::loadHTML($view);

        return $pdf->stream();
    }

    public function arus_kas($data)
    {
        $keuangan = new Keuangan;
        $data_saldo = Session::get('data_saldo');
        $arus_kas = ArusKas::where('rekening', '!=', '0')->orderBy('id', 'ASC')->get();
        $saldo_bulan_lalu = 0;

        $data_transaksi = [];
        foreach ($data['kab']->kec as $kec) {
            foreach ($data_saldo[$kec->kd_kec]['transaksi'] as $trx) {

                $key = $trx['rekening_debit'].'#'.$trx['rekening_kredit'];
                if (! array_key_exists($key, $data_transaksi)) {
                    $data_transaksi[$key] = [
                        'rekening_debit' => $trx['rekening_debit'],
                        'rekening_kredit' => $trx['rekening_kredit'],
                        'jumlah' => floatval($trx['jumlah']),
                    ];
                } else {
                    $data_transaksi[$key]['jumlah'] += floatval($trx['jumlah']);
                }
            }

            $saldo_bulan_lalu += $data_saldo[$kec->kd_kec]['saldo_bulan_lalu'];
        }

        $data_arus_kas = [];
        foreach ($arus_kas as $ak) {
            $rekening = explode('#', $ak->rekening);

            if (! array_key_exists($ak->id, $data_arus_kas)) {
                $data_arus_kas[$ak->id] = [
                    'jumlah' => 0,
                ];
            }

            foreach ($rekening as $rek) {
                $rk = explode('~', $rek);
                $rek_debit = str_replace('%', '', $rk[0]);
                $rek_kredit = str_replace('%', '', $rk[1]);

                foreach ($data_transaksi as $d_trx) {
                    if (Keuangan::startWith($d_trx['rekening_debit'], $rek_debit) && Keuangan::startWith($d_trx['rekening_kredit'], $rek_kredit)) {
                        $data_arus_kas[$ak->id]['jumlah'] += floatval($d_trx['jumlah']);
                    }
                }
            }
        }

        $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($data['tgl_kondisi']).' '.Tanggal::tahun($data['tgl_kondisi']);
        $data['tgl'] = Tanggal::namaBulan($data['tgl_kondisi']).' '.Tanggal::tahun($data['tgl_kondisi']);

        $data['data_arus_kas'] = $data_arus_kas;
        $data['arus_kas'] = ArusKas::where('sub', '0')->with('child')->orderBy('id', 'ASC')->get();
        $data['saldo_bulan_lalu'] = $saldo_bulan_lalu;

        $data['keuangan'] = $keuangan;
        $view = view('admin.kabupaten.laporan.views.arus_kas', $data)->render();
        $pdf = PDF::loadHTML($view);

        return $pdf->stream();
    }

    public function LPM($data)
    {
        $lpm = [];
        $keuangan = new Keuangan;

        $data_saldo = Session::get('data_saldo');
        foreach ($data['kab']->kec as $kec) {
            foreach ($data_saldo[$kec->kd_kec]['saldo'] as $rek) {
                if ($rek->lev1 != '3') {
                    continue;
                }

                $saldo = $keuangan->komSaldo($rek);
                if ($rek->kode_akun == '3.2.02.01') {
                    $saldo = 0;
                    foreach ($data_saldo[$kec->kd_kec]['laba_rugi'] as $lb) {
                        if ($lb->lev1 == 5) {
                            $saldo -= $keuangan->komSaldo($lb);
                        } else {
                            $saldo += $keuangan->komSaldo($lb);
                        }
                    }
                }

                if (! array_key_exists($rek->kode_akun, $lpm)) {
                    $lpm[$rek->kode_akun] = [
                        'kode_akun' => $rek->kode_akun,
                        'nama_akun' => $rek->nama_akun,
                        'saldo' => $saldo,
                    ];
                } else {
                    $lpm[$rek->kode_akun]['saldo'] += $saldo;
                }
            }
        }

        $data['perubahan_modal'] = $lpm;
        $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($data['tgl_kondisi']).' '.Tanggal::tahun($data['tgl_kondisi']);
        $data['tgl'] = Tanggal::namaBulan($data['tgl_kondisi']).' '.Tanggal::tahun($data['tgl_kondisi']);

        $data['keuangan'] = $keuangan;
        $view = view('admin.kabupaten.laporan.views.perubahan_modal', $data)->render();
        $pdf = PDF::loadHTML($view);

        return $pdf->stream();
    }
}
