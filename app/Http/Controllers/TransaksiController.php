<?php

namespace App\Http\Controllers;

use App\Models\AkunLevel1;
use App\Models\AkunLevel2;
use App\Models\Desa;
use App\Models\Ebudgeting;
use App\Models\Inventaris;
use App\Models\JenisTransaksi;
use App\Models\Kecamatan;
use App\Models\PinjamanAnggota;
use App\Models\PinjamanKelompok;
use App\Models\RealAngsuran;
use App\Models\Rekening;
use App\Models\RencanaAngsuran;
use App\Models\Saldo;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\GenerateService;
use App\Utils\Inventaris as UtilsInventaris;
use App\Utils\Keuangan;
use App\Utils\Tanggal;
use DB;
use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use PDF;
use Session;

class TransaksiController extends Controller
{
    protected $generateService;

    protected const KODE_DENDA = ['4.1.01.04', '4.1.01.05', '4.1.01.06'];

    protected function resolveRealForStruk($id)
    {
        if (! is_numeric($id)) {
            abort(404);
        }

        // 1. Real row langsung di DB aktif — kasus Pokok/Jasa normal
        try {
            $real = RealAngsuran::where('id', $id)->first();
            if ($real) {
                return $real;
            }
        } catch (\Throwable $e) {
            // Tabel suffix tidak ada di DB aktif — lanjut fallback DB lain
        }

        // 1b. Fallback DB: coba connection satunya (mysql <-> mysql_b)
        foreach (['mysql', 'mysql_b'] as $alt) {
            if ($alt === config('database.default')) {
                continue;
            }
            try {
                $real = RealAngsuran::on($alt)->where('id', $id)->first();
                if ($real) {
                    return $real;
                }
            } catch (\Throwable $e) {
                // skip
            }
        }

        // 2. Angs. Denda: tidak ada row real_angsuran. Cari transaksi Denda
        //    berdasar idtp == $id (idtp Denda == real_angsuran.id untuk Pokok/Jasa
        //    dengan nomor urut yang kebetulan sama; untuk Denda idtp merujuk ke loan).
        $denda = null;
        foreach (['mysql', 'mysql_b'] as $alt) {
            try {
                $denda = Transaksi::on($alt)->where('idtp', $id)
                    ->whereIn('rekening_kredit', self::KODE_DENDA)
                    ->orderBy('tgl_transaksi', 'DESC')
                    ->first();
                if ($denda) {
                    break;
                }
            } catch (\Throwable $e) {
                // skip
            }
        }

        if (! $denda) {
            abort(404);
        }

        // 3. Snapshot real terakhir untuk loan tsb sebelum/sama dengan tgl transaksi Denda
        $real = null;
        foreach (['mysql', 'mysql_b'] as $alt) {
            try {
                $real = RealAngsuran::on($alt)->where('loan_id', $denda->id_pinj)
                    ->where('tgl_transaksi', '<=', $denda->tgl_transaksi)
                    ->orderBy('tgl_transaksi', 'DESC')
                    ->first();
                if ($real) {
                    break;
                }
            } catch (\Throwable $e) {
                // skip
            }
        }

        if (! $real) {
            // Loan belum pernah bayar Pokok/Jasa: synthesize snapshot dari pinkel
            $pinkel = null;
            foreach (['mysql', 'mysql_b'] as $pal) {
                try {
                    $pinkel = \App\Models\PinjamanKelompok::on($pal)->where('id', $denda->id_pinj)->first();
                    if ($pinkel) {
                        break;
                    }
                } catch (\Throwable $e) {
                    // skip
                }
            }
            if ($pinkel) {
                $real = new \App\Models\RealAngsuran;
                $real->setAttribute('loan_id', $pinkel->id);
                $real->setAttribute('sum_pokok', 0);
                $real->setAttribute('sum_jasa', 0);
                $real->setAttribute('saldo_pokok', $pinkel->alokasi);
                $real->setAttribute('saldo_jasa', ($pinkel->alokasi * $pinkel->pros_jasa) / 100);
            }
        }

        if (! $real) {
            abort(404);
        }

        // 4. Build record turunan: Pokok/Jasa = 0, tgl_transaksi = tgl Denda,
        //    trx = [transaksi Denda].
        $real->setAttribute('realisasi_pokok', 0);
        $real->setAttribute('realisasi_jasa', 0);
        $real->setAttribute('tgl_transaksi', $denda->tgl_transaksi);
        $real->setAttribute('id', $denda->idt);
        $real->setRelation('trx', collect([$denda->loadMissing('user')]));

        return $real;
    }

    public function __construct(GenerateService $generateService)
    {
        $this->generateService = $generateService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function jurnalUmum()
    {
        $title = 'Jurnal Umum';
        $jenis_transaksi = JenisTransaksi::all();

        $kec = Kecamatan::where('id', Session::get('lokasi'))->first();

        return view('transaksi.jurnal_umum.index')->with(compact('title', 'jenis_transaksi', 'kec'));
    }

    public function jurnalAngsuran()
    {
        $title = 'Jurnal Angsuran';

        $rekening = Rekening::where([
            ['kode_akun', 'LIKE', '1.1.01.%'],
            ['lev4', '!=', '02'],
        ])->orderBy('kode_akun')->get();
        $kec = Kecamatan::where('id', Session::get('lokasi'))->with('wa_session')->first();
        if (request()->get('pinkel')) {
            $pinkel = PinjamanKelompok::where('id', request()->get('pinkel'))->with('kelompok');
            $pinkel = $pinkel->first();
        } else {
            $pinkel = '0';
        }

        $api = env('WA_GATEWAY_BASE', 'https://wa-gateway.enpiistudio.com');

        $wa_instance_name = $kec->wa_session->instance_name ?? null;

        return view('transaksi.jurnal_angsuran.index')->with(compact('title', 'rekening', 'pinkel', 'kec', 'api', 'wa_instance_name'));
    }

    public function ebudgeting()
    {
        $kec = Kecamatan::where('id', Session::get('lokasi'))->first();

        $title = 'E - Budgeting';

        return view('transaksi.ebudgeting.index')->with(compact('title', 'kec'));
    }

    public function jurnalTutupBuku()
    {
        $kec = Kecamatan::where('id', Session::get('lokasi'))->first();

        $title = 'Tutup Buku';

        return view('transaksi.tutup_buku.index')->with(compact('title', 'kec'));
    }

    // Inactive
    public function saldoAwal($tahun)
    {
        $kec = Kecamatan::where('id', Session::get('lokasi'))->first();

        $tgl_pakai = $kec->tgl_pakai;
        $tahun_pakai = Tanggal::tahun($tgl_pakai);

        $saldo = [];
        $data_id = [];

        $rekening = Rekening::all();
        foreach ($rekening as $rek) {
            for ($i = $tahun_pakai; $i <= $tahun; $i++) {
                $tahun_tb = $i - 1;
                $tb = 'tb'.$tahun_tb;
                $tbk = 'tbk'.$tahun_tb;

                $saldo[] = [
                    'id' => str_replace('.', '', $rek->kode_akun).$i.'00',
                    'kode_akun' => $rek->kode_akun,
                    'tahun' => intval($i),
                    'bulan' => '0',
                    'debit' => $rek->$tb,
                    'kredit' => $rek->$tbk,
                ];

                $data_id[] = str_replace('.', '', $rek->kode_akun).$i.'00';
            }
        }

        Saldo::whereIn('id', $data_id)->delete();
        Saldo::insert($saldo);

        echo '<script>window.close();</script>';
    }

    public function saldoTutupBuku(Request $request)
    {
        $keuangan = new Keuangan;
        $tgl_pakai = $request->tgl_pakai ?: date('Y-m-d');
        $tahun = $request->tahun;
        $tahun_lalu = $tahun - 1;
        $tahun_pakai = Tanggal::tahun($tgl_pakai);
        $bulan = date('m');
        if ($tahun < date('Y')) {
            $bulan = 12;
        }

        $akun1 = AkunLevel1::where('lev1', '<=', '3')->with([
            'akun2',
            'akun2.akun3',
            'akun2.akun3.rek',
            'akun2.akun3.rek.kom_saldo' => function ($query) use ($tahun, $bulan) {
                $query->where('tahun', $tahun)->where(function ($query) use ($bulan) {
                    $query->where('bulan', '0')->orwhere('bulan', $bulan);
                });
            },
        ])->orderBy('kode_akun', 'ASC')->get();

        $tgl_kondisi = $tahun.'-'.$bulan.'-'.date('t', strtotime($tahun.'-'.$bulan.'-01'));
        $surplus = $keuangan->laba_rugi($tgl_kondisi);

        $total_riwayat = ($tahun + 1) - $tahun_pakai;
        $jumlah_riwayat = count(Saldo::select('tahun')->whereRaw('LENGTH(kode_akun) = 9')->where('bulan', '0')->whereBetween('tahun', [$tahun_pakai, $tahun])->groupBy('tahun')->get());

        return response()->json([
            'success' => true,
            'view' => view('transaksi.tutup_buku.partials.saldo')->with(compact('akun1', 'surplus', 'tgl_kondisi', 'tahun', 'tahun_lalu', 'total_riwayat', 'jumlah_riwayat'))->render(),
        ]);
    }

    public function simpanTutupBuku(Request $request)
    {
        $keuangan = new Keuangan;

        $tahun = $request->tahun;
        $bulan = date('m');
        if ($tahun < date('Y')) {
            $bulan = 12;
        }
        $tgl_kondisi = $tahun.'-'.$bulan.'-'.date('t', strtotime($tahun.'-'.$bulan.'-01'));
        $surplus = $keuangan->laba_rugi($tgl_kondisi);

        $tahun_tb = $tahun + 1;
        $kec = Kecamatan::where('id', Session::get('lokasi'))->with([
            'desa',
            'desa.sebutan_desa',
            'desa.saldo' => function ($query) use ($tahun, $bulan) {
                $query->where('tahun', $tahun)->where('bulan', '<=', $bulan)->orderBy('bulan', 'ASC');
            },
            'saldo' => function ($query) use ($tahun) {
                $query->where('tahun', $tahun);
            },
        ])->first();
        $desa = $kec->desa;

        $trx = [];
        $data_id = [];
        $saldo_tutup_buku = [];
        // foreach ($desa as $d) {
        //     $id = str_replace('.', '', $d->kode_desa).$tahun_tb. 0;

        //     $saldo_debit = 0;
        //     if ($d->saldo) {
        //         $laba = $keuangan->sumPembagianLabaDesa($d);
        //         $saldo_debit = $laba['kredit'] + $laba['debit'];
        //     }

        //     $saldo_tutup_buku[$id] = [
        //         'id' => $id,
        //         'kode_akun' => $d->kode_desa,
        //         'tahun' => $tahun_tb,
        //         'bulan' => '0',
        //         'debit' => (string) $saldo_debit,
        //         'kredit' => 0,
        //     ];

        //     $data_id[] = $id;
        // }

        // foreach ($kec->saldo as $saldo) {
        //     $urut = substr($saldo->id, -1);
        //     $id = str_replace('.', '', $kec->kd_kec).$tahun_tb.str_pad($urut, '3', '0', STR_PAD_LEFT);

        //     if ($urut <= 3) {
        //         $saldo_tutup_buku[$id] = [
        //             'id' => $id,
        //             'kode_akun' => $kec->kd_kec,
        //             'tahun' => $tahun_tb,
        //             'bulan' => '0',
        //             'debit' => (string) floatval($saldo->kredit) + floatval($saldo->debit),
        //             'kredit' => 0,
        //         ];
        //     } else {
        //         $saldo_tutup_buku[$id] = [
        //             'id' => $id,
        //             'kode_akun' => $kec->kd_kec,
        //             'tahun' => $tahun_tb,
        //             'bulan' => '0',
        //             'debit' => (string) floatval($saldo->kredit) + floatval($saldo->debit),
        //             'kredit' => 0,
        //         ];
        //     }

        //     $data_id[] = $id;
        // }

        // Saldo::whereIn('id', $data_id)->delete();
        // Saldo::insert($saldo_tutup_buku);

        $success = false;
        $migrasi_saldo = false;
        if ($request->pembagian_laba == 'false') {
            $jumlah_riwayat = $request->jumlah_riwayat;
            $total_riwayat = $request->total_riwayat;

            if ($jumlah_riwayat < $total_riwayat) {
                $migrasi_saldo = true;
            }

            $tahun_tb = $tahun + 1;
            $kode_rekening = Rekening::where(function ($query) use ($tgl_kondisi) {
                $query->whereNull('tgl_nonaktif')->orwhere('tgl_nonaktif', '>', $tgl_kondisi);
            })->with([
                'kom_saldo' => function ($query) use ($tahun, $bulan) {
                    $query->where('tahun', $tahun)->where(function ($query) use ($bulan) {
                        $query->where('bulan', '0')->orwhere('bulan', $bulan);
                    });
                },
            ])->get();

            $data_id = [];
            $saldo_tutup_buku = [];
            foreach ($kode_rekening as $rek) {
                $saldo_awal_debit = 0;
                $saldo_awal_kredit = 0;
                $debit = 0;
                $kredit = 0;

                $bulan_tb = '0';
                if ($rek->lev1 >= 4) {
                    foreach ($rek->kom_saldo as $saldo) {
                        if ($saldo->bulan == 0) {
                            if ($saldo->debit != 0) {
                                $saldo_awal_debit = floatval($saldo->debit);
                            }
                            if ($saldo->kredit != 0) {
                                $saldo_awal_kredit = floatval($saldo->kredit);
                            }
                        } else {
                            if ($saldo->debit != 0) {
                                $debit = floatval($saldo->debit);
                            }
                            if ($saldo->kredit != 0) {
                                $kredit = floatval($saldo->kredit);
                            }
                        }
                    }

                    $saldo_debit = $debit;
                    $saldo_kredit = $kredit;

                    $id = str_replace('.', '', $rek->kode_akun).$tahun.'13';
                    if ($saldo_debit + $saldo_kredit != 0) {
                        $saldo_tutup_buku[] = [
                            'id' => $id,
                            'kode_akun' => $rek->kode_akun,
                            'tahun' => $tahun,
                            'bulan' => 13,
                            'debit' => (string) $saldo_debit,
                            'kredit' => (string) $saldo_kredit,
                        ];

                        $data_id[] = $id;
                    }
                }

                $saldo_awal_debit = 0;
                $saldo_awal_kredit = 0;
                $debit = 0;
                $kredit = 0;

                if ($rek->lev1 < 4 && $rek->kode_akun != '3.2.02.01') {
                    foreach ($rek->kom_saldo as $saldo) {
                        if ($saldo->bulan == 0) {
                            if ($saldo->debit != 0) {
                                $saldo_awal_debit = floatval($saldo->debit);
                            }
                            if ($saldo->kredit != 0) {
                                $saldo_awal_kredit = floatval($saldo->kredit);
                            }
                        } else {
                            if ($saldo->debit != 0) {
                                $debit = floatval($saldo->debit);
                            }
                            if ($saldo->kredit != 0) {
                                $kredit = floatval($saldo->kredit);
                            }
                        }
                    }
                }

                $saldo_debit = $saldo_awal_debit + $debit;
                $saldo_kredit = $saldo_awal_kredit + $kredit;

                if ($rek->kode_akun == '3.2.01.01') {
                    $saldo_kredit += $surplus;
                }

                $id = str_replace('.', '', $rek->kode_akun).$tahun_tb.'00';
                $saldo_tutup_buku[] = [
                    'id' => $id,
                    'kode_akun' => $rek->kode_akun,
                    'tahun' => $tahun_tb,
                    'bulan' => $bulan_tb,
                    'debit' => (string) $saldo_debit,
                    'kredit' => (string) $saldo_kredit,
                ];

                $data_id[] = $id;
            }

            Saldo::whereIn('id', $data_id)->delete();
            Saldo::insert($saldo_tutup_buku);

            $success = true;

            return redirect('/transaksi/tutup_buku')->with('success', 'Tutup Buku Tahun '.$tahun.' berhasil.');
        }

        $surplus = $keuangan->laba_rugi($tahun.'-13-00');

        $kec = Kecamatan::where('id', Session::get('lokasi'))->with([
            'saldo' => function ($query) use ($tahun) {
                $query->where('tahun', $tahun)->whereRaw('LENGTH(id) >= 13');
            },
        ])->first();
        $rekening = Rekening::where('kode_akun', 'like', '2.1.04%')->where(function ($query) use ($tgl_kondisi) {
            $query->whereNull('tgl_nonaktif')->orwhere('tgl_nonaktif', '>', $tgl_kondisi);
        })->get();
        $desa = Desa::where('kd_kec', $kec->kd_kec)->with([
            'saldo' => function ($query) use ($tahun) {
                $query->where('tahun', $tahun);
            },
            'sebutan_desa',
        ])->orderBy('kd_desa', 'ASC')->get();

        $title = 'Pembagian Laba';

        return view('transaksi.tutup_buku.tutup_buku')->with(compact('title', 'kec', 'surplus', 'rekening', 'desa', 'tgl_kondisi', 'tahun', 'migrasi_saldo', 'success'));
    }

    public function simpanAlokasiLaba(Request $request)
    {
        $keuangan = new Keuangan;
        $data = $request->only([
            'surplus',
            'masyarakat',
            'total_laba_bagian_masyarakat',
            'desa',
            'total_laba_bagian_desa',
            'total_laba_bagian_penyerta_modal',
            'laba_ditahan',
            'tgl_mad',
        ]);

        $data['tgl_mad'] = Tanggal::tglNasional($data['tgl_mad']);
        $tanggal = $request->tgl_kondisi ?: date('Y-m-d');
        $tahun = Tanggal::tahun($tanggal);
        $tahun_tb = $tahun + 1;
        $bulan = Tanggal::bulan($tanggal);

        $kec = Kecamatan::where('id', Session::get('lokasi'))->with([
            'desa',
            'desa.sebutan_desa',
            'desa.saldo' => function ($query) use ($tahun, $bulan) {
                $query->where('tahun', $tahun)->where('bulan', '<=', $bulan)->orderBy('bulan', 'ASC');
            },
            'saldo' => function ($query) use ($tahun) {
                $query->where('tahun', $tahun)->whereRaw('LENGTH(id) >= 13');
            },
        ])->first();
        $desa = $kec->desa;
        $rekening = Rekening::whereNull('tgl_nonaktif')->orwhere('tgl_nonaktif', '>', $tanggal)->with([
            'kom_saldo' => function ($query) use ($tahun, $bulan) {
                $query->where('tahun', $tahun)->where(function ($query) use ($bulan) {
                    $query->where('bulan', '0')->orwhere('bulan', $bulan);
                });
            },
        ])->get();

        $title_form = [
            1 => 'Kegiatan sosial kemasyarakatan dan bantuan RTM',
            2 => 'Pengembangan kapasitas kelompok SPP/UEP',
            3 => 'Pelatihan masyarakat, dan kelompok pemanfaat umum',
            4 => 'Penambahan Modal DBM',
            5 => 'Penambahan Investasi Usaha',
            6 => 'Pendirian Unit Usaha',
        ];

        $alokasi_laba = [
            '2.1.04.01' => str_replace(',', '', str_replace('.00', '', $data['total_laba_bagian_masyarakat'])),
            '2.1.04.02' => str_replace(',', '', str_replace('.00', '', $data['total_laba_bagian_desa'])),
            '2.1.04.03' => str_replace(',', '', str_replace('.00', '', $data['total_laba_bagian_penyerta_modal'])),
            '3.2.01.01' => 0,
        ];

        $laba_ditahan = $data['laba_ditahan']; // Ditambahkan ke 3.2.01.01
        foreach ($laba_ditahan as $key => $val) {
            $value = str_replace(',', '', str_replace('.00', '', $val));
            $alokasi_laba['3.2.01.01'] += floatval($value);
        }

        $alokasi_laba['3.2.01.01'] += floatval($alokasi_laba['2.1.04.01']);
        $alokasi_laba['3.2.01.01'] += floatval($alokasi_laba['2.1.04.02']);
        $alokasi_laba['3.2.01.01'] += floatval($alokasi_laba['2.1.04.03']);

        $pembagian_laba_desa = $data['desa'];
        $pembagian_laba_ditahan = $data['laba_ditahan'];
        $pembagian_laba_masyarakat = $data['masyarakat'];

        $trx = [];
        $data_id = [];
        $saldo_tutup_buku = [];
        foreach ($desa as $d) {
            $id = str_replace('.', '', $d->kode_desa).$tahun_tb. 0;
            $pembagian_desa = str_replace(',', '', str_replace('.00', '', $pembagian_laba_desa[$d->kd_desa]));

            $saldo_kredit = 0;
            if ($d->saldo) {
                $laba = $keuangan->sumPembagianLabaDesa($d);
                $saldo_kredit = $laba['kredit'];
            }

            if (intval($pembagian_desa) > 0) {
                $saldo_tutup_buku[] = [
                    'id' => $id,
                    'kode_akun' => $d->kode_desa,
                    'tahun' => $tahun_tb,
                    'bulan' => '0',
                    'debit' => (string) $saldo_kredit,
                    'kredit' => $pembagian_desa,
                ];

                $keterangan = 'Alokasi laba bagian '.$d->sebutan_desa->sebutan_desa.' '.$d->nama_desa.' tahun '.$tahun;
                $trx['insert'][] = [
                    'tgl_transaksi' => $data['tgl_mad'],
                    'rekening_debit' => '3.2.01.01',
                    'rekening_kredit' => '2.1.04.02',
                    'idtp' => '0',
                    'id_pinj' => '0',
                    'id_pinj_i' => '0',
                    'keterangan_transaksi' => $keterangan,
                    'relasi' => '-',
                    'jumlah' => floatval($pembagian_desa),
                    'urutan' => '0',
                    'id_user' => auth()->user()->id,
                ];

                $trx['delete'][] = $keterangan;
                $data_id[] = $id;
            }
        }

        foreach ($kec->saldo as $saldo) {
            $urut = substr($saldo->id, -1);

            $id = str_replace('.', '', $kec->kd_kec).$tahun_tb.str_pad($urut, '3', '0', STR_PAD_LEFT);
            if ($urut <= 3) {
                $saldo_tutup_buku[] = [
                    'id' => $id,
                    'kode_akun' => $kec->kd_kec,
                    'tahun' => $tahun_tb,
                    'bulan' => '0',
                    'debit' => (string) $saldo->kredit,
                    'kredit' => str_replace(',', '', str_replace('.00', '', $pembagian_laba_masyarakat[$urut])),
                ];

                $keterangan = $title_form[$urut].' tahun '.$tahun;
                if (str_replace(',', '', str_replace('.00', '', $pembagian_laba_masyarakat[$urut])) != '0') {
                    $trx['insert'][] = [
                        'tgl_transaksi' => $data['tgl_mad'],
                        'rekening_debit' => '3.2.01.01',
                        'rekening_kredit' => '2.1.04.01',
                        'idtp' => '0',
                        'id_pinj' => '0',
                        'id_pinj_i' => '0',
                        'keterangan_transaksi' => $keterangan,
                        'relasi' => '-',
                        'jumlah' => floatval(str_replace(',', '', str_replace('.00', '', $pembagian_laba_masyarakat[$urut]))),
                        'urutan' => '0',
                        'id_user' => auth()->user()->id,
                    ];
                }

                $trx['delete'][] = $keterangan;
            } else {
                $saldo_tutup_buku[] = [
                    'id' => $id,
                    'kode_akun' => $kec->kd_kec,
                    'tahun' => $tahun_tb,
                    'bulan' => '0',
                    'debit' => (string) $saldo->kredit,
                    'kredit' => str_replace(',', '', str_replace('.00', '', $pembagian_laba_ditahan[$urut])),
                ];
            }

            $data_id[] = $id;
        }

        foreach ($rekening as $rek) {
            $saldo_awal_debit = 0;
            $saldo_awal_kredit = 0;
            $debit = 0;
            $kredit = 0;

            if ($rek->lev1 < 4 && $rek->kode_akun != '3.2.02.01') {
                foreach ($rek->kom_saldo as $saldo) {
                    if ($saldo->bulan == 0) {
                        if ($saldo->debit > 0) {
                            $saldo_awal_debit = $saldo->debit;
                        }
                        if ($saldo->kredit > 0) {
                            $saldo_awal_kredit = $saldo->kredit;
                        }
                    } else {
                        if ($saldo->debit > 0) {
                            $debit = $saldo->debit;
                        }
                        if ($saldo->kredit > 0) {
                            $kredit = $saldo->kredit;
                        }
                    }
                }
            }

            $saldo_debit = floatval($saldo_awal_debit) + floatval($debit);
            $saldo_kredit = floatval($saldo_awal_kredit) + floatval($kredit);

            if (in_array($rek->kode_akun, array_keys($alokasi_laba))) {
                $id = str_replace('.', '', $rek->kode_akun).$tahun_tb.'00';

                if ($rek->kode_akun == '3.2.01.01') {
                    $saldo_kredit += floatval($alokasi_laba['3.2.01.01']);
                }

                if ($rek->kode_akun == '2.1.04.03') {
                    $keterangan = 'Laba Bagian Penyerta Modal tahun '.$tahun;
                    if (floatval($alokasi_laba['2.1.04.03']) != '0') {
                        $trx['insert'][] = [
                            'tgl_transaksi' => $data['tgl_mad'],
                            'rekening_debit' => '3.2.01.01',
                            'rekening_kredit' => '2.1.04.03',
                            'idtp' => '0',
                            'id_pinj' => '0',
                            'id_pinj_i' => '0',
                            'keterangan_transaksi' => $keterangan,
                            'relasi' => '-',
                            'jumlah' => floatval($alokasi_laba['2.1.04.03']),
                            'urutan' => '0',
                            'id_user' => auth()->user()->id,
                        ];
                    }

                    $trx['delete'][] = $keterangan;
                }

                $saldo_tutup_buku[] = [
                    'id' => $id,
                    'kode_akun' => $rek->kode_akun,
                    'tahun' => $tahun_tb,
                    'bulan' => '0',
                    'debit' => (string) $saldo_debit,
                    'kredit' => (string) $saldo_kredit,
                ];

                $data_id[] = $id;
            }

            if ($rek->lev1 >= 4) {
                $id = str_replace('.', '', $rek->kode_akun).$tahun_tb.'00';
                $saldo_tutup_buku[] = [
                    'id' => $id,
                    'kode_akun' => $rek->kode_akun,
                    'tahun' => $tahun_tb,
                    'bulan' => '0',
                    'debit' => 0,
                    'kredit' => 0,
                ];

                $data_id[] = $id;
            }
        }

        Saldo::whereIn('id', $data_id)->delete();
        Saldo::insert($saldo_tutup_buku);

        Transaksi::whereIn('keterangan_transaksi', $trx['delete'])->delete();
        Transaksi::insert($trx['insert']);

        return response()->json([
            'success' => true,
            'msg' => 'Alokasi Pembagian Laba tahun '.$tahun.' berhasil.',
        ]);
    }

    public function formAnggaran(Request $request)
    {
        $data = $request->only(['tahun', 'bulan']);

        $validate = Validator::make($data, [
            'tahun' => 'required',
            'bulan' => 'required',
        ]);

        if ($validate->fails()) {
            return response()->json($validate->errors(), Response::HTTP_MOVED_PERMANENTLY);
        }

        $cek = Ebudgeting::where([
            ['tahun', $request->tahun],
            ['bulan', $request->bulan],
        ])->orderBy('bulan', 'ASC')->orderBy('kode_akun', 'ASC');

        $jumlah = $cek->count();

        $tgl_kondisi = date('Y-m-t', strtotime($request->tahun.'-'.$request->bulan.'-01'));
        if ($jumlah > 0) {
            $akun1 = AkunLevel1::where('lev1', '>=', '4')->with([
                'akun2',
                'akun2.akun3',
                'akun2.akun3.rek' => function ($query) use ($tgl_kondisi) {
                    $query->whereNull('tgl_nonaktif')->orwhere('tgl_nonaktif', '>', $tgl_kondisi);
                },
                'akun2.akun3.rek.eb' => function ($query) use ($data) {
                    $query->where([
                        ['tahun', $data['tahun']],
                        ['bulan', $data['bulan']],
                    ]);
                },
            ])->orderBy('kode_akun', 'ASC')->get();
        } else {
            $akun1 = AkunLevel1::where('lev1', '>=', '4')->with([
                'akun2',
                'akun2.akun3',
                'akun2.akun3.rek' => function ($query) use ($tgl_kondisi) {
                    $query->whereNull('tgl_nonaktif')->orwhere('tgl_nonaktif', '>', $tgl_kondisi);
                },
                'akun2.akun3.rek.eb' => function ($query) use ($data) {
                    $query->where([
                        ['tahun', $data['tahun']],
                        ['bulan', $data['bulan'] - 1],
                    ]);
                },
            ])->orderBy('kode_akun', 'ASC')->get();
        }

        $bulan = $request->bulan;
        $tahun = $request->tahun;

        return response()->json([
            'success' => true,
            'view' => view('transaksi.ebudgeting.create')->with(compact('akun1', 'jumlah', 'tahun', 'bulan'))->render(),
        ]);
    }

    public function taksiranPajak()
    {
        $keuangan = new Keuangan;
        $kec = Kecamatan::where('id', Session::get('lokasi'))->first();
        $akun2 = AkunLevel2::where('lev1', '4')->with([
            'rek',
            'rek.kom_saldo' => function ($query) {
                $query->where('tahun', date('Y'))->where(function ($query) {
                    $query->where('bulan', (date('m') - 1))->orwhere('bulan', date('m'));
                })->orderBy('bulan', 'asc');
            },
        ])->get();

        $biaya = Rekening::where([
            ['lev1', '5'],
            ['lev2', '!=', '4'],
        ])->with([
            'kom_saldo' => function ($query) {
                $query->where('tahun', date('Y'))->where(function ($query) {
                    $query->where('bulan', (date('m') - 1))->orwhere('bulan', date('m'));
                })->orderBy('bulan', 'asc');
            },
        ])->get();

        $beban = 0;
        foreach ($biaya as $b) {
            $beban += $keuangan->saldoBulanIni($b);
        }

        $title = 'Taksiran Pajak';

        return view('transaksi.taksiran_pajak.index')->with(compact('title', 'kec', 'akun2', 'keuangan', 'beban'));
    }

    public function cetakTaksiranPajak(Request $request)
    {
        $data = $request->only([
            'nama_lembaga',
            'npwp',
            'tanggal_npwp',
            'tahun_pajak',
            'masa_pajak',
            'pph_final',
            'pph_badan',
            'rekening',
            'pendapatan',
            'beban',
            'laba',
            'pph',
        ]);

        $data['kode_akun'] = array_keys($data['rekening']);
        $data['akun2'] = AkunLevel2::where('lev1', '4')->with([
            'rek' => function ($query) use ($data) {
                $query->whereIn('kode_akun', $data['kode_akun']);
            },
        ])->get();

        if ($data['masa_pajak'] < '0') {
            $awal_tahun = $data['tahun_pajak'].'-01-01';
            $akhir_tahun = $data['tahun_pajak'].'-12-31';

            $data['bulan_masa_pajak'] = Tanggal::namaBulan($awal_tahun).' - '.Tanggal::namaBulan($akhir_tahun);
        } else {
            $data['bulan_masa_pajak'] = Tanggal::namaBulan($data['tahun_pajak'].'-'.$data['masa_pajak'].'-01');
        }

        $view = view('transaksi.taksiran_pajak.dokumen.taksiran_pajak', $data)->render();
        $pdf = PDF::loadHTML($view);

        return $pdf->stream();
    }

    public function pendapatan($tahun, $bulan)
    {
        $keuangan = new Keuangan;

        $bulan_lalu = $bulan - 1;
        if ($bulan < 0) {
            $bulan = 12;
            $bulan_lalu = 0;
        }

        $tgl_kondisi = date('Y-m-t', strtotime($tahun.'-'.$bulan.'-01'));
        $rekening = Rekening::where([
            ['lev1', '4'],
        ])->where(function ($query) use ($tgl_kondisi) {
            $query->whereNull('tgl_nonaktif')->orwhere('tgl_nonaktif', '>', $tgl_kondisi);
        })->with([
            'kom_saldo' => function ($query) use ($tahun, $bulan, $bulan_lalu) {
                $query->where('tahun', $tahun)->where(function ($query) use ($bulan, $bulan_lalu) {
                    $query->where('bulan', $bulan_lalu)->orwhere('bulan', $bulan);
                })->orderBy('bulan', 'asc');
            },
        ])->get();

        $pendapatan = [];
        foreach ($rekening as $rek) {
            if ($bulan == 12 && $bulan_lalu == 0) {
                $pendapatan[$rek->kode_akun] = $keuangan->komSaldo($rek);
            } else {
                $pendapatan[$rek->kode_akun] = $keuangan->saldoBulanIni($rek);
            }
        }

        $biaya = Rekening::where([
            ['lev1', '5'],
            ['lev2', '!=', '4'],
        ])->where(function ($query) use ($tgl_kondisi) {
            $query->whereNull('tgl_nonaktif')->orwhere('tgl_nonaktif', '>', $tgl_kondisi);
        })->with([
            'kom_saldo' => function ($query) use ($tahun, $bulan, $bulan_lalu) {
                $query->where('tahun', $tahun)->where(function ($query) use ($bulan, $bulan_lalu) {
                    $query->where('bulan', $bulan_lalu)->orwhere('bulan', $bulan);
                })->orderBy('bulan', 'asc');
            },
        ])->get();

        $beban = 0;
        foreach ($biaya as $b) {
            if ($bulan == 12 && $bulan_lalu == 0) {
                $beban += $keuangan->komSaldo($b);
            } else {
                $beban += $keuangan->saldoBulanIni($b);
            }
        }

        return response()->json([
            'success' => true,
            'pendapatan' => $pendapatan,
            'beban' => $beban,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $keuangan = new Keuangan;

        $tgl_transaksi = Tanggal::tglNasional($request->tgl_transaksi);
        $kec = Kecamatan::where('id', Session::get('lokasi'))->first();

        if (strtotime($tgl_transaksi) < strtotime($kec->tgl_pakai)) {
            return response()->json([
                'success' => false,
                'msg' => 'Tanggal transaksi tidak boleh sebelum Tanggal Pakai Aplikasi',
            ]);
        }

        if ((Keuangan::startWith($request->sumber_dana, '1.2.01.01') || Keuangan::startWith($request->sumber_dana, '1.2.02')) && Keuangan::startWith($request->disimpan_ke, '5.3.02.01') && $request->jenis_transaksi == '2') {
            $data = $request->only([
                'tgl_transaksi',
                'jenis_transaksi',
                'sumber_dana',
                'disimpan_ke',
                'harsat',
                'nama_barang',
                'alasan',
                'unit',
                'harga_jual',
                '_nilai_buku',
            ]);

            $validate = Validator::make($data, [
                'tgl_transaksi' => 'required',
                'jenis_transaksi' => 'required',
                'sumber_dana' => 'required',
                'disimpan_ke' => 'required',
                'harsat' => 'required',
                'nama_barang' => 'required',
                'alasan' => 'required',
                'unit' => 'required',
                'harga_jual' => 'required',
            ]);

            if ($validate->fails()) {
                return response()->json($validate->errors(), Response::HTTP_MOVED_PERMANENTLY);
            }

            $sumber_dana = $request->sumber_dana;
            $disimpan_ke = $request->disimpan_ke;
            $nilai_buku = $request->unit * $request->harsat;
            $status = $request->alasan;

            $nama_barang = explode('#', $request->nama_barang);
            $id_inv = $nama_barang[0];
            $jumlah_barang = $nama_barang[1];

            $inv = Inventaris::where('id', $id_inv)->first();

            $tgl_beli = $inv->tgl_beli;
            $harsat = $inv->harsat;
            $umur_ekonomis = $inv->umur_ekonomis;
            $sisa_unit = $jumlah_barang - $request->unit;
            $barang = $inv->nama_barang;
            $jenis = $inv->jenis;
            $kategori = $inv->kategori;

            $trx_penghapusan = [
                'tgl_transaksi' => (string) Tanggal::tglNasional($request->tgl_transaksi),
                'rekening_debit' => (string) $request->disimpan_ke,
                'rekening_kredit' => (string) $request->sumber_dana,
                'idtp' => '0',
                'id_pinj' => '0',
                'id_pinj_i' => '0',
                'keterangan_transaksi' => (string) 'Penghapusan '.$request->unit.' unit '.$barang.' ('.$id_inv.')'.' karena '.$status,
                'relasi' => (string) $request->relasi,
                'jumlah' => $nilai_buku,
                'urutan' => '0',
                'id_user' => auth()->user()->id,
            ];

            $update_inventaris = [
                'unit' => $sisa_unit,
                'tgl_validasi' => Tanggal::tglNasional($request->tgl_transaksi),
            ];

            $update_sts_inventaris = [
                'status' => ucwords($status),
                'tgl_validasi' => Tanggal::tglNasional($request->tgl_transaksi),
            ];

            $insert_inventaris = [
                'lokasi' => Session::get('lokasi'),
                'nama_barang' => $barang,
                'tgl_beli' => $tgl_beli,
                'unit' => $request->unit,
                'harsat' => $harsat,
                'umur_ekonomis' => $umur_ekonomis,
                'jenis' => $jenis,
                'kategori' => $kategori,
                'status' => ucwords($status),
                'tgl_validasi' => Tanggal::tglNasional($request->tgl_transaksi),
            ];

            $trx_penjualan = [
                'tgl_transaksi' => (string) Tanggal::tglNasional($request->tgl_transaksi),
                'rekening_debit' => '1.1.01.01',
                'rekening_kredit' => '4.2.01.04',
                'idtp' => '0',
                'id_pinj' => '0',
                'id_pinj_i' => '0',
                'keterangan_transaksi' => (string) 'Penjualan '.$request->unit.' unit '.$barang.' ('.$id_inv.')',
                'relasi' => (string) $request->relasi,
                'jumlah' => str_replace(',', '', str_replace('.00', '', $request->harga_jual)),
                'urutan' => '0',
                'id_user' => auth()->user()->id,
            ];

            if ($request->unit < $jumlah_barang) {
                if ($status != 'rusak') {
                    $transaksi = Transaksi::create($trx_penghapusan);
                }
                Inventaris::where('id', $id_inv)->update($update_inventaris);

                if ($status != 'revaluasi') {
                    Inventaris::create($insert_inventaris);
                }
            } else {
                if ($status != 'rusak') {
                    $transaksi = Transaksi::create($trx_penghapusan);
                }
                Inventaris::where('id', $id_inv)->update($update_sts_inventaris);
            }

            if ($status == 'revaluasi') {
                $harga_jual = floatval(str_replace(',', '', str_replace('.00', '', $request->harga_jual)));

                $insert_inventaris_baru = [
                    'lokasi' => Session::get('lokasi'),
                    'nama_barang' => $barang,
                    'tgl_beli' => Tanggal::tglNasional($request->tgl_transaksi),
                    'unit' => $request->unit,
                    'harsat' => $harga_jual / $request->unit,
                    'umur_ekonomis' => $umur_ekonomis,
                    'jenis' => $jenis,
                    'kategori' => $kategori,
                    'status' => 'Baik',
                    'tgl_validasi' => Tanggal::tglNasional($request->tgl_transaksi),
                ];

                if ($harga_jual != $request->_nilai_buku) {
                    $jumlah = $harga_jual - $request->_nilai_buku;
                    $trx_revaluasi = [
                        'tgl_transaksi' => (string) Tanggal::tglNasional($request->tgl_transaksi),
                        'rekening_debit' => '1.1.01.01',
                        'rekening_kredit' => '4.3.01.01',
                        'idtp' => '0',
                        'id_pinj' => '0',
                        'id_pinj_i' => '0',
                        'keterangan_transaksi' => (string) 'Revaluasi '.$request->unit.' unit '.$barang.' ('.$id_inv.')',
                        'relasi' => '',
                        'jumlah' => $jumlah,
                        'urutan' => '0',
                        'id_user' => auth()->user()->id,
                    ];

                    Transaksi::create($trx_revaluasi);
                }

                Inventaris::create($insert_inventaris_baru);
            }

            $msg = 'Penghapusan '.$request->unit.' unit '.$barang.' karena '.$status;
            if ($status == 'dijual') {
                $transaksi = Transaksi::create($trx_penjualan);
                $msg = 'Penjualan '.$request->unit.' unit '.$barang;
            }

            if ($status == 'rusak') {
                return response()->json([
                    'success' => true,
                    'msg' => $msg,
                    'view' => '',
                ]);
            }
        } else {
            if (Keuangan::startWith($request->disimpan_ke, '1.2.01') || Keuangan::startWith($request->disimpan_ke, '1.2.03')) {
                $data = $request->only([
                    'tgl_transaksi',
                    'jenis_transaksi',
                    'sumber_dana',
                    'disimpan_ke',
                    'relasi',
                    'nama_barang',
                    'jumlah',
                    'harga_satuan',
                    'umur_ekonomis',
                ]);

                $validate = Validator::make($data, [
                    'tgl_transaksi' => 'required',
                    'jenis_transaksi' => 'required',
                    'sumber_dana' => 'required',
                    'disimpan_ke' => 'required',
                    'nama_barang' => 'required',
                    'jumlah' => 'required',
                    'harga_satuan' => 'required',
                    'umur_ekonomis' => 'required',
                ]);

                if ($validate->fails()) {
                    return response()->json($validate->errors(), Response::HTTP_MOVED_PERMANENTLY);
                }

                $rek_simpan = Rekening::where('kode_akun', $request->disimpan_ke)->first();

                $insert = [
                    'tgl_transaksi' => (string) Tanggal::tglNasional($request->tgl_transaksi),
                    'rekening_debit' => (string) $request->disimpan_ke,
                    'rekening_kredit' => (string) $request->sumber_dana,
                    'idtp' => 0,
                    'id_pinj' => 0,
                    'id_pinj_i' => 0,
                    'keterangan_transaksi' => (string) '('.$rek_simpan->nama_akun.') '.$request->nama_barang,
                    'relasi' => (string) $request->relasi,
                    'jumlah' => str_replace(',', '', str_replace('.00', '', $request->harga_satuan)) * $request->jumlah,
                    'urutan' => 0,
                    'id_user' => auth()->user()->id,
                ];

                $inventaris = [
                    'lokasi' => Session::get('lokasi'),
                    'nama_barang' => $request->nama_barang,
                    'tgl_beli' => Tanggal::tglNasional($request->tgl_transaksi),
                    'unit' => $request->jumlah,
                    'harsat' => str_replace(',', '', str_replace('.00', '', $request->harga_satuan)),
                    'umur_ekonomis' => $request->umur_ekonomis,
                    'jenis' => str_pad($rek_simpan->lev3, 1, '0', STR_PAD_LEFT),
                    'kategori' => str_pad($rek_simpan->lev4, 1, '0', STR_PAD_LEFT),
                    'status' => 'Baik',
                    'tgl_validasi' => Tanggal::tglNasional($request->tgl_transaksi),
                ];

                $transaksi = Transaksi::create($insert);
                $inv = Inventaris::create($inventaris);

                $msg = 'Transaksi '.$rek_simpan->nama_akun.' ('.$insert['keterangan_transaksi'].') berhasil disimpan';
            } else {
                $data = $request->only([
                    'tgl_transaksi',
                    'jenis_transaksi',
                    'sumber_dana',
                    'disimpan_ke',
                    'relasi',
                    'keterangan',
                    'nominal',
                ]);

                $validate = Validator::make($data, [
                    'tgl_transaksi' => 'required',
                    'jenis_transaksi' => 'required',
                    'sumber_dana' => 'required',
                    'disimpan_ke' => 'required',
                    'nominal' => 'required',
                ]);

                if ($validate->fails()) {
                    return response()->json($validate->errors(), Response::HTTP_MOVED_PERMANENTLY);
                }

                $relasi = '';
                if ($request->relasi) {
                    $relasi = $request->relasi;
                }
                $insert = [
                    'tgl_transaksi' => (string) Tanggal::tglNasional($request->tgl_transaksi),
                    'rekening_debit' => (string) $request->disimpan_ke,
                    'rekening_kredit' => (string) $request->sumber_dana,
                    'idtp' => 0,
                    'id_pinj' => 0,
                    'id_pinj_i' => 0,
                    'keterangan_transaksi' => (string) $request->keterangan,
                    'relasi' => (string) $relasi,
                    'jumlah' => str_replace(',', '', str_replace('.00', '', $request->nominal)),
                    'urutan' => 0,
                    'id_user' => auth()->user()->id,
                ];

                $transaksi = Transaksi::create($insert);
                $msg = 'Transaksi '.$insert['keterangan_transaksi'].' berhasil disimpan';
            }
        }

        $trx = Transaksi::where('idt', $transaksi->idt)->with([
            'rek_debit',
            'rek_kredit',
        ])->first();
        $view = view('transaksi.jurnal_umum.partials.notifikasi')->with(compact('trx', 'keuangan'))->render();

        return response()->json([
            'success' => true,
            'msg' => $msg,
            'view' => $view,
        ]);
    }

    public function angsuran(Request $request)
    {
        $kec = Kecamatan::where('id', Session::get('lokasi'))->first();

        $data = $request->only([
            'id',
            'tgl_transaksi',
            'pokok',
            'jasa',
            'denda',
            'tujuan',
            'penyetor',
        ]);

        $validate = Validator::make($data, [
            'tgl_transaksi' => 'required',
            'pokok' => 'required',
            'jasa' => 'required',
            'denda' => 'required',
            'tujuan' => 'required',
            'penyetor' => 'required',
        ]);

        if ($validate->fails()) {
            return response()->json($validate->errors(), Response::HTTP_MOVED_PERMANENTLY);
        }

        $request->pokok = str_replace(',', '', str_replace('.00', '', $request->pokok));
        $request->jasa = str_replace(',', '', str_replace('.00', '', $request->jasa));
        $request->denda = str_replace(',', '', str_replace('.00', '', $request->denda));

        $request->total_pokok_anggota = str_replace(',', '', str_replace('.00', '', $request->total_pokok_anggota));
        $request->total_jasa_anggota = str_replace(',', '', str_replace('.00', '', $request->total_jasa_anggota));
        $request->total_denda_anggota = str_replace(',', '', str_replace('.00', '', $request->total_denda_anggota));

        if ($request->pokok <= 0 && $request->jasa <= 0 && $request->denda <= 0) {
            return response()->json([
                'success' => false,
                'msg' => 'Total Bayar tidak boleh nol',
            ]);
        }

        $tgl_transaksi = Tanggal::tglNasional($request->tgl_transaksi);

        $pinkel = PinjamanKelompok::where('id', $request->id)->with([
            'kelompok',
            'kelompok.d',
            'kelompok.d.sebutan_desa',
            'pinjaman_anggota',
            'saldo' => function ($query) use ($request, $tgl_transaksi) {
                $query->where([
                    ['loan_id', $request->id],
                    ['tgl_transaksi', '<=', $tgl_transaksi],
                ]);
            },
            'target' => function ($query) use ($request, $tgl_transaksi) {
                $query->where([
                    ['loan_id', $request->id],
                    ['jatuh_tempo', '<=', $tgl_transaksi],
                ]);
            },
        ])->first();
        $pinjaman_anggota = $pinkel->pinjaman_anggota;

        $sum_pokok = 0;
        $sum_jasa = 0;
        if ($pinkel->saldo) {
            $sum_pokok = $pinkel->saldo->sum_pokok;
            $sum_jasa = $pinkel->saldo->sum_jasa;
        }

        $target_pokok = 0;
        $target_jasa = 0;
        if ($pinkel->target) {
            $target_pokok = $pinkel->target->target_pokok;
            $target_jasa = $pinkel->target->target_jasa;
        }

        $tunggakan_pokok = $target_pokok - $sum_pokok;
        if ($tunggakan_pokok < '0') {
            $tunggakan_pokok = '0';
        }

        $tunggakan_jasa = $target_jasa - $sum_jasa;
        if ($tunggakan_jasa < '0') {
            $tunggakan_jasa = '0';
        }

        if (strtotime($tgl_transaksi) < strtotime($pinkel->tgl_cair)) {
            return response()->json([
                'success' => false,
                'msg' => 'Tanggal transaksi tidak boleh sebelum Tanggal Cair',
            ]);
        }

        $kas_umum = $data['tujuan'];
        if ($pinkel->jenis_pp == '1') {
            $poko_kredit = '1.1.03.01';
            $jasa_kredit = '4.1.01.01';
            $dend_kredit = '4.1.01.04';
        } elseif ($pinkel->jenis_pp == '2') {
            $poko_kredit = '1.1.03.02';
            $jasa_kredit = '4.1.01.02';
            $dend_kredit = '4.1.01.05';
        } else {
            $poko_kredit = '1.1.03.03';
            $jasa_kredit = '4.1.01.03';
            $dend_kredit = '4.1.01.06';
        }

        $_pokok = floatval($request->pokok) - floatval($request->total_pokok_anggota);
        $_jasa = floatval($request->jasa) - floatval($request->total_jasa_anggota);
        $_denda = floatval($request->denda) - floatval($request->total_denda_anggota);

        if (strtotime($tgl_transaksi) < strtotime($request->tgl_pakai_aplikasi)) {
            return response()->json([
                'success' => false,
                'msg' => 'Tanggal transaksi tidak boleh sebelum Tanggal Pakai Aplikasi',
            ]);
        }

        $trx = [
            'idtp' => false,
            'transaksi' => false,
        ];

        $maxRetries = 2;
        $retryCount = 0;
        while ($retryCount < $maxRetries) {
            try {
                $trx = DB::transaction(function () use ($request, $tgl_transaksi, $kas_umum, $poko_kredit, $jasa_kredit, $dend_kredit, $pinkel) {
                    DB::table('transaksi_'.Session::get('lokasi'))->lockForUpdate()->get();

                    $last_idtp = Transaksi::where('idtp', '!=', '0')->max('idtp');
                    $idtp = $last_idtp + 1;

                    $transaksi = [];
                    if ($request->pokok > 0) {
                        $transaksi[] = [
                            'tgl_transaksi' => (string) $tgl_transaksi,
                            'rekening_debit' => (string) $kas_umum,
                            'rekening_kredit' => (string) $poko_kredit,
                            'idtp' => $idtp,
                            'id_pinj' => $pinkel->id,
                            'id_pinj_i' => '0',
                            'keterangan_transaksi' => (string) 'Angs. (P) '.$pinkel->kelompok->nama_kelompok.' ('.$pinkel->id.')'.' ['.$pinkel->kelompok->d->nama_desa.']',
                            'relasi' => $request->penyetor,
                            'jumlah' => str_replace(',', '', str_replace('.00', '', $request->pokok)),
                            'urutan' => '0',
                            'id_user' => auth()->user()->id,
                        ];
                    }

                    if ($request->jasa > 0) {
                        // if ($request->jasa < $tunggakan_jasa) {
                        //     if ($pinkel->jenis_pp == '1') {
                        //         $jasa_kredit = '1.1.03.04';
                        //     } elseif ($pinkel->jenis_pp == '2') {
                        //         $jasa_kredit = '1.1.03.05';
                        //     } else {
                        //         $jasa_kredit = '1.1.03.06';
                        //     }

                        $transaksi[] = [
                            'tgl_transaksi' => (string) $tgl_transaksi,
                            'rekening_debit' => (string) $kas_umum,
                            'rekening_kredit' => (string) $jasa_kredit,
                            'idtp' => $idtp,
                            'id_pinj' => $pinkel->id,
                            'id_pinj_i' => '0',
                            'keterangan_transaksi' => (string) 'Angs. (J) '.$pinkel->kelompok->nama_kelompok.' ('.$pinkel->id.')'.' ['.$pinkel->kelompok->d->nama_desa.']',
                            'relasi' => $request->penyetor,
                            'jumlah' => str_replace(',', '', str_replace('.00', '', $request->jasa)),
                            'urutan' => '0',
                            'id_user' => auth()->user()->id,
                        ];
                        // } else {
                        //     $angs_jasa = ($request->jasa - $tunggakan_jasa > 0) ? ($request->jasa - $tunggakan_jasa) : $request->jasa;
                        //     $transaksi[] = [
                        //         'tgl_transaksi' => (string) $tgl_transaksi,
                        //         'rekening_debit' => (string) $kas_umum,
                        //         'rekening_kredit' => (string) $jasa_kredit,
                        //         'idtp' => $idtp,
                        //         'id_pinj' => $pinkel->id,
                        //         'id_pinj_i' => '0',
                        //         'keterangan_transaksi' => (string) 'Angs. ' . $pinkel->kelompok->nama_kelompok . ' - [' . $pinkel->kelompok->d->nama_desa . '] (J)',
                        //         'relasi' => $request->penyetor,,
                        //         'jumlah' => str_replace(',', '', str_replace('.00', '', $angs_jasa)),
                        //         'urutan' => '0',
                        //         'id_user' => auth()->user()->id
                        //     ];

                        //     if ($request->jasa - $angs_jasa > 0) {
                        //         $piutang_kredit = $jasa_kredit;
                        //         if ($pinkel->jenis_pp == '1') {
                        //             $piutang_kredit = '1.1.03.04';
                        //         } elseif ($pinkel->jenis_pp == '2') {
                        //             $piutang_kredit = '1.1.03.05';
                        //         } else {
                        //             $piutang_kredit = '1.1.03.06';
                        //         }

                        //         $piutang_jasa = $request->jasa - $angs_jasa;
                        //         $transaksi[] = [
                        //             'tgl_transaksi' => (string) $tgl_transaksi,
                        //             'rekening_debit' => (string) $kas_umum,
                        //             'rekening_kredit' => (string) $piutang_kredit,
                        //             'idtp' => $idtp,
                        //             'id_pinj' => $pinkel->id,
                        //             'id_pinj_i' => '0',
                        //             'keterangan_transaksi' => (string) 'Angs. ' . $pinkel->kelompok->nama_kelompok . ' - [' . $pinkel->kelompok->d->nama_desa . '] (J)',
                        //             'relasi' => $request->penyetor,,
                        //             'jumlah' => str_replace(',', '', str_replace('.00', '', $piutang_jasa)),
                        //             'urutan' => '0',
                        //             'id_user' => auth()->user()->id
                        //         ];
                        //     }
                        // }
                    }

                    if ($request->denda > 0) {
                        $transaksi[] = [
                            'tgl_transaksi' => (string) $tgl_transaksi,
                            'rekening_debit' => (string) $kas_umum,
                            'rekening_kredit' => (string) $dend_kredit,
                            'idtp' => $idtp,
                            'id_pinj' => $pinkel->id,
                            'id_pinj_i' => '0',
                            'keterangan_transaksi' => (string) 'Denda. '.$pinkel->kelompok->nama_kelompok.' ('.$pinkel->id.')'.' ['.$pinkel->kelompok->d->nama_desa.']',
                            'relasi' => $request->penyetor,
                            'jumlah' => str_replace(',', '', str_replace('.00', '', $request->denda)),
                            'urutan' => '0',
                            'id_user' => auth()->user()->id,
                        ];
                    }
                    Transaksi::insert($transaksi);

                    return [
                        'idtp' => $idtp,
                        'transaksi' => $transaksi,
                    ];
                });

                break;
            } catch (\Exception $e) {
                DB::rollback();

                Log::warning("Transaksi gagal, percobaan ke-$retryCount: ".$e->getMessage());
                $retryCount++;
                if ($retryCount >= $maxRetries) {
                    throw $e;
                }
                sleep(1);
            }
        }

        $idtp = $trx['idtp'];
        $transaksi = $trx['transaksi'];

        if ($transaksi) {
            $jasa_pinjaman = ($pinkel->pros_jasa / 100) * $pinkel->alokasi;
            foreach ($pinjaman_anggota as $pa) {
                $pokok_anggota = 0;
                if ($request->pokok_anggota) {
                    if ($request->pokok_anggota[$pa->id]) {
                        $pokok_anggota = floatval(str_replace(',', '', str_replace('.00', '', $request->pokok_anggota[$pa->id])));
                    }
                }

                $jasa_anggota = 0;
                if ($request->jasa_anggota) {
                    if ($request->jasa_anggota[$pa->id]) {
                        $jasa_anggota = str_replace(',', '', str_replace('.00', '', $request->jasa_anggota[$pa->id]));
                    }
                }

                if ($pokok_anggota <= 0) {
                    $pros_pokok_anggota = round(($pa->alokasi / $pinkel->alokasi) * 100, 2);
                    $pokok_anggota = round(($pros_pokok_anggota / 100) * $_pokok, 2);
                }

                if ($jasa_anggota <= 0) {
                    $pros_jasa_anggota = 0;
                    if ($jasa_pinjaman != 0) {
                        $pros_jasa_anggota = round(((floatval(str_replace(',', '.', $pa->pros_jasa)) / 100 * $pa->alokasi) / $jasa_pinjaman) * 100, 2);
                    }
                    $jasa_anggota = round(($pros_jasa_anggota / 100) * $_jasa, 2);
                }

                $kom_pokok = json_decode($pa->kom_pokok, true);
                $kom_jasa = json_decode($pa->kom_jasa, true);

                if (is_array($kom_pokok)) {
                    $kom_pokok[$idtp] = $pokok_anggota;
                } else {
                    $kom_pokok = [];
                    $kom_pokok[$idtp] = $pokok_anggota;
                }

                if (is_array($kom_jasa)) {
                    $kom_jasa[$idtp] = $jasa_anggota;
                } else {
                    $kom_jasa = [];
                    $kom_jasa[$idtp] = $jasa_anggota;
                }

                PinjamanAnggota::where('id', $pa->id)->update([
                    'kom_pokok' => $kom_pokok,
                    'kom_jasa' => $kom_jasa,
                ]);
            }

            $rek_pokok = ['1.1.03.01', '1.1.03.02', '1.1.03.03'];
            $rek_jasa = ['1.1.03.04', '1.1.03.05', '1.1.03.06', '4.1.01.01', '4.1.01.02', '4.1.01.03'];

            $alokasi_pokok = round($pinkel->alokasi, 2);
            $alokasi_jasa = round($pinkel->pros_jasa == 0 ? 0 : $pinkel->alokasi * ($pinkel->pros_jasa / 100), 2);

            $real_angsuran = [
                'id' => $idtp,
                'loan_id' => $pinkel->id,
                'tgl_transaksi' => $tgl_transaksi,
                'realisasi_pokok' => 0,
                'realisasi_jasa' => 0,
                'sum_pokok' => $sum_pokok,
                'sum_jasa' => $sum_jasa,
                'saldo_pokok' => ($alokasi_pokok - $sum_pokok),
                'saldo_jasa' => ($alokasi_jasa - $sum_jasa),
                'tunggakan_pokok' => $tunggakan_pokok,
                'tunggakan_jasa' => $tunggakan_jasa,
                'lu' => date('Y-m-d H:i:s', strtotime($tgl_transaksi)),
                'id_user' => auth()->user()->id,
            ];

            foreach ($transaksi as $key => $trx) {
                if (in_array($trx['rekening_kredit'], $rek_pokok)) {
                    $sum_pokok += $trx['jumlah'];
                    $alokasi_pokok -= $sum_pokok;
                    $tunggakan_pokok -= $trx['jumlah'];
                    if ($tunggakan_pokok <= 0) {
                        $tunggakan_pokok = 0;
                    }

                    $real_angsuran['realisasi_pokok'] = $trx['jumlah'];
                    $real_angsuran['sum_pokok'] = $sum_pokok;
                    $real_angsuran['saldo_pokok'] = $alokasi_pokok;
                    $real_angsuran['tunggakan_pokok'] = $tunggakan_pokok;
                }

                if (in_array($trx['rekening_kredit'], $rek_jasa)) {
                    $sum_jasa += $trx['jumlah'];
                    $alokasi_jasa -= $sum_jasa;
                    $tunggakan_jasa -= $trx['jumlah'];
                    if ($tunggakan_jasa <= 0) {
                        $tunggakan_jasa = 0;
                    }

                    $real_angsuran['realisasi_jasa'] = $trx['jumlah'];
                    $real_angsuran['sum_jasa'] = $sum_jasa;
                    $real_angsuran['saldo_jasa'] = $alokasi_jasa;
                    $real_angsuran['tunggakan_jasa'] = $tunggakan_jasa;
                }
            }

            RealAngsuran::insert($real_angsuran);

            $whatsapp = false;
            $pesan = '';
            if (strlen($pinkel->kelompok->telpon) >= 11 && strlen(auth()->user()->hp) >= 11 && (Keuangan::startWith($pinkel->kelompok->telpon, '08') || Keuangan::startWith($pinkel->kelompok->telpon, '628'))) {
                $nama_kelompok = $pinkel->kelompok->nama_kelompok;
                $desa = $pinkel->kelompok->d->sebutan_desa->sebutan_desa.' '.$pinkel->kelompok->d->nama_desa;

                $whatsapp = true;
                $pesan_wa = json_decode($kec->whatsapp, true);
                $pesan = $pesan_wa['angsuran'];
                $pesan = strtr($pesan, [
                    '{Nama Kelompok}' => $pinkel->kelompok->nama_kelompok,
                    '{Nama Desa}' => $pinkel->kelompok->d->sebutan_desa->sebutan_desa.' '.$pinkel->kelompok->d->nama_desa,
                    '{Angsuran Pokok}' => number_format($request->pokok),
                    '{Angsuran Jasa}' => number_format($request->jasa),
                    '{Tanggal Angsuran}' => Tanggal::tglIndo($tgl_transaksi),
                    '{User Login}' => auth()->user()->namadepan.' '.auth()->user()->namabelakang,
                    '{Telpon}' => auth()->user()->hp,
                ]);
            }

            return response()->json([
                'success' => true,
                'msg' => 'Angsuran kelompok '.$pinkel->kelompok->nama_kelompok.' ['.$pinkel->kelompok->d->nama_desa.'] berhasil diposting',
                'id_pinkel' => $pinkel->id,
                'idtp' => $idtp,
                'tgl_transaksi' => $tgl_transaksi,
                'whatsapp' => $whatsapp,
                'number' => $pinkel->kelompok->telpon,
                'nama_kelompok' => $pinkel->kelompok->nama_kelompok,
                'pesan' => $pesan,
            ]);
        }

        return response()->json([
            'success' => false,
            'msg' => 'Transaksi gagal. Silahkan coba lagi. Jika masih belum berhasil segera hubungi TS',
            'loan_id' => $request->id,
        ]);
    }

    public function notifikasi($idtp)
    {
        $trx = Transaksi::where('idtp', $idtp)->first();
        $view = view('transaksi.jurnal_angsuran.partials.notif', [
            'idtp' => $idtp,
            'id_pinkel' => $trx->id_pinj,
            'idt' => $trx->idt,
        ])->render();

        return response()->json([
            'view' => $view,
        ]);
    }

    public function simpanAnggaran(Request $request)
    {
        $data = $request->only(['tahun', 'bulan', 'jumlah']);
        $validate = Validator::make($data, [
            'tahun' => 'required',
            'bulan' => 'required',
        ]);

        if ($validate->fails()) {
            return response()->json($validate->errors(), Response::HTTP_MOVED_PERMANENTLY);
        }

        $insert = [];
        foreach ($request->jumlah as $kode_akun => $nominal) {
            $insert[] = [
                'kode_akun' => $kode_akun,
                'tahun' => $request->tahun,
                'bulan' => $request->bulan,
                'jumlah' => floatval(str_replace(',', '', $nominal)),
            ];
        }

        Ebudgeting::where([
            ['tahun', $request->tahun],
            ['bulan', $request->bulan],
        ])->delete();
        Ebudgeting::insert($insert);

        $nama_bulan = Tanggal::namaBulan($request->tahun.'-'.$request->bulan.'-01');

        return response()->json([
            'success' => true,
            'msg' => 'Rencana Anggaran bulan '.$nama_bulan.' berhasil disimpan.',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaksi $transaksi)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transaksi $transaksi)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaksi $transaksi)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaksi $transaksi)
    {
        //
    }

    public function rekening($id)
    {
        $jenis_transaksi = JenisTransaksi::where('id', $id)->firstOrFail();
        $label1 = 'Pilih Sumber Dana';

        $tahun = request()->get('tahun');
        $bulan = request()->get('bulan');

        $tgl_kondisi = date('Y-m-t', strtotime($tahun.'-'.$bulan.'-01'));
        if ($id == 1) {
            $rek1 = Rekening::where(function ($query) {
                $query->where(function ($query) {
                    $query->where('lev1', '2')->orwhere('lev1', '3')->orwhere('lev1', '4');
                })->where([
                    ['kode_akun', '!=', '2.1.04.01'],
                    ['kode_akun', '!=', '2.1.04.02'],
                    ['kode_akun', '!=', '2.1.04.03'],
                    ['kode_akun', '!=', '2.1.02.01'],
                    ['kode_akun', '!=', '2.1.03.01'],
                    ['kode_akun', 'NOT LIKE', '4.1.01%'],
                ]);
            })->where(function ($query) use ($tgl_kondisi) {
                $query->whereNull('tgl_nonaktif')->orwhere('tgl_nonaktif', '>', $tgl_kondisi);
            })->orderBy('kode_akun', 'ASC')->get();

            $rek2 = Rekening::where('lev1', '1')->orderBy('kode_akun', 'ASC')->get();

            $label2 = 'Disimpan Ke';
        } elseif ($id == 2) {
            $rek1 = Rekening::where(function ($query) {
                $query->where(function ($query) {
                    $query->where('lev1', '1')->orwhere('lev1', '2');
                })->where([
                    ['kode_akun', 'NOT LIKE', '2.1.04%'],
                ]);
            })->where(function ($query) use ($tgl_kondisi) {
                $query->whereNull('tgl_nonaktif')->orwhere('tgl_nonaktif', '>', $tgl_kondisi);
            })->orderBy('kode_akun', 'ASC')->get();

            $rek2 = Rekening::where('lev1', '2')->orwhere('lev1', '3')->orwhere('lev1', '5')->orderBy('kode_akun', 'ASC')->get();

            $label2 = 'Keperluan';
        } elseif ($id == 3) {
            $rek1 = Rekening::where(function ($query) use ($tgl_kondisi) {
                $query->whereNull('tgl_nonaktif')->orwhere('tgl_nonaktif', '>', $tgl_kondisi);
            })->get();

            $rek2 = Rekening::whereNotIn('kode_akun', ['1.1.03.01', '1.1.03.02', '1.1.03.03'])->where(function ($query) use ($tgl_kondisi) {
                $query->whereNull('tgl_nonaktif')->orwhere('tgl_nonaktif', '>', $tgl_kondisi);
            })->get();

            $label2 = 'Disimpan Ke';
        }

        return view('transaksi.jurnal_umum.partials.rekening')->with(compact('rek1', 'rek2', 'label1', 'label2'));
    }

    public function form()
    {
        $keuangan = new Keuangan;
        $tgl_transaksi = Tanggal::tglNasional(request()->get('tgl_transaksi'));
        $jenis_transaksi = request()->get('jenis_transaksi');
        $sumber_dana = request()->get('sumber_dana');
        $disimpan_ke = request()->get('disimpan_ke');

        if ((Keuangan::startWith($sumber_dana, '1.2.01.01') || Keuangan::startWith($sumber_dana, '1.2.02')) && Keuangan::startWith($disimpan_ke, '5.3.02.01') && $jenis_transaksi == 2) {
            $kode = explode('.', $sumber_dana);

            $jenis = intval($kode[2]);
            $kategori = intval($kode[3]);
            if (Keuangan::startWith($sumber_dana, '1.2.02')) {
                $jenis = intval($kode[2] - 1);
                $kategori = intval($kode[3] + 1);
            }

            $inventaris = Inventaris::where([
                ['jenis', $jenis],
                ['kategori', $kategori],
            ])->whereNotNull('tgl_beli')->where(function ($query) {
                $query->where('status', 'Baik')->orwhere('status', 'Rusak');
            })->get();

            return view('transaksi.jurnal_umum.partials.form_hapus_inventaris')->with(compact('inventaris', 'tgl_transaksi'));
        } else {
            if (Keuangan::startWith($disimpan_ke, '1.2.01') || Keuangan::startWith($disimpan_ke, '1.2.03')) {
                $kuitansi = false;
                $relasi = false;
                $files = 'bm';
                if (Keuangan::startWith($disimpan_ke, '1.1.01') && ! Keuangan::startWith($sumber_dana, '1.1.01')) {
                    $file = 'c_bkm';
                    $files = 'BKM';
                    $kuitansi = true;
                    $relasi = true;
                } elseif (! Keuangan::startWith($disimpan_ke, '1.1.01') && Keuangan::startWith($sumber_dana, '1.1.01')) {
                    $file = 'c_bkk';
                    $files = 'BKK';
                    $kuitansi = true;
                    $relasi = true;
                } elseif (Keuangan::startWith($disimpan_ke, '1.1.01') && Keuangan::startWith($sumber_dana, '1.1.01')) {
                    $file = 'c_bm';
                    $files = 'BM';
                } elseif (Keuangan::startWith($disimpan_ke, '1.1.02') && ! (Keuangan::startWith($sumber_dana, '1.1.01') || Keuangan::startWith($sumber_dana, '1.1.02'))) {
                    $file = 'c_bkm';
                    $files = 'BKM';
                    $kuitansi = true;
                    $relasi = true;
                } elseif (Keuangan::startWith($disimpan_ke, '1.1.02') && Keuangan::startWith($sumber_dana, '1.1.02')) {
                    $file = 'c_bm';
                    $files = 'BM';
                } elseif (Keuangan::startWith($disimpan_ke, '5.') && ! (Keuangan::startWith($sumber_dana, '1.1.01') || Keuangan::startWith($sumber_dana, '1.1.02'))) {
                    $file = 'c_bm';
                    $files = 'BM';
                } elseif (! (Keuangan::startWith($disimpan_ke, '1.1.01') || Keuangan::startWith($disimpan_ke, '1.1.02')) && Keuangan::startWith($sumber_dana, '1.1.02')) {
                    $file = 'c_bm';
                    $files = 'BM';
                } elseif (! (Keuangan::startWith($disimpan_ke, '1.1.01') || Keuangan::startWith($disimpan_ke, '1.1.02')) && Keuangan::startWith($sumber_dana, '4.')) {
                    $file = 'c_bm';
                    $files = 'BM';
                }

                return view('transaksi.jurnal_umum.partials.form_inventaris')->with(compact('relasi'));
            } else {
                $rek_sumber = Rekening::where('kode_akun', $sumber_dana)->first();
                $rek_simpan = Rekening::where('kode_akun', $disimpan_ke)->first();

                $keterangan_transaksi = '';
                if ($jenis_transaksi == 1) {
                    if (! empty($disimpan_ke)) {
                        $keterangan_transaksi = 'Dari '.$rek_sumber->nama_akun.' ke '.$rek_simpan->nama_akun;
                    }
                } elseif ($jenis_transaksi == 2) {
                    if (! empty($disimpan_ke)) {
                        $keterangan_transaksi = $rek_simpan->nama_akun;
                        $kd = substr($sumber_dana, 0, 6);
                        if ($kd == '1.1.01') {
                            $keterangan_transaksi = 'Bayar '.$rek_simpan->nama_akun;
                        }
                        if ($kd == '1.1.02') {
                            $keterangan_transaksi = 'Transfer '.$rek_simpan->nama_akun;
                        }
                    }
                } elseif ($jenis_transaksi == 3) {
                    if (! empty($disimpan_ke)) {
                        $keterangan_transaksi = 'Pemindahan Saldo '.$rek_sumber->nama_akun.' ke '.$rek_simpan->nama_akun;
                    }
                }

                $kuitansi = false;
                $relasi = false;
                $files = 'bm';
                if (Keuangan::startWith($disimpan_ke, '1.1.01') && ! Keuangan::startWith($sumber_dana, '1.1.01')) {
                    $file = 'c_bkm';
                    $files = 'BKM';
                    $kuitansi = true;
                    $relasi = true;
                } elseif (! Keuangan::startWith($disimpan_ke, '1.1.01') && Keuangan::startWith($sumber_dana, '1.1.01')) {
                    $file = 'c_bkk';
                    $files = 'BKK';
                    $kuitansi = true;
                    $relasi = true;
                } elseif (Keuangan::startWith($disimpan_ke, '1.1.01') && Keuangan::startWith($sumber_dana, '1.1.01')) {
                    $file = 'c_bm';
                    $files = 'BM';
                } elseif (Keuangan::startWith($disimpan_ke, '1.1.02') && ! (Keuangan::startWith($sumber_dana, '1.1.01') || Keuangan::startWith($sumber_dana, '1.1.02'))) {
                    $file = 'c_bkm';
                    $files = 'BKM';
                    $kuitansi = true;
                    $relasi = true;
                } elseif (Keuangan::startWith($disimpan_ke, '1.1.02') && Keuangan::startWith($sumber_dana, '1.1.02')) {
                    $file = 'c_bm';
                    $files = 'BM';
                } elseif (Keuangan::startWith($disimpan_ke, '5.') && ! (Keuangan::startWith($sumber_dana, '1.1.01') || Keuangan::startWith($sumber_dana, '1.1.02'))) {
                    $file = 'c_bm';
                    $files = 'BM';
                } elseif (! (Keuangan::startWith($disimpan_ke, '1.1.01') || Keuangan::startWith($disimpan_ke, '1.1.02')) && Keuangan::startWith($sumber_dana, '1.1.02')) {
                    $file = 'c_bm';
                    $files = 'BM';
                } elseif (! (Keuangan::startWith($disimpan_ke, '1.1.01') || Keuangan::startWith($disimpan_ke, '1.1.02')) && Keuangan::startWith($sumber_dana, '4.')) {
                    $file = 'c_bm';
                    $files = 'BM';
                }

                $susut = 0;
                if (in_array($disimpan_ke, ['5.1.07.08', '5.1.07.09', '5.1.07.10'])) {
                    $tanggal = date('Y-m-t', strtotime($tgl_transaksi));
                    if ($sumber_dana == '1.2.02.01') {
                        $kategori = '2';
                    } elseif ($sumber_dana == '1.2.02.02') {
                        $kategori = '3';
                    } else {
                        $kategori = '4';
                    }

                    $tgl = explode('-', $tanggal);
                    $tahun_lalu = $tgl[0];
                    $bulan_lalu = $tgl[1] - 1;
                    if ($bulan_lalu < 1) {
                        $tahun_lalu -= 1;
                        $bulan_lalu = 1;
                    }

                    $tanggal_lalu = $tahun_lalu.'-'.$bulan_lalu.'-01';
                    $tanggal_lalu = date('Y-m-t', strtotime($tanggal_lalu));

                    $penyusutan = UtilsInventaris::penyusutan($tanggal, $kategori);
                    $saldo = UtilsInventaris::penyusutan($tanggal_lalu, $kategori);
                    // $saldo = UtilsInventaris::saldoSusut($tanggal, $sumber_dana);

                    $susut = floatval($penyusutan) - floatval($saldo);
                    if ($susut < 0) {
                        $susut *= -1;
                    }
                    $keterangan_transaksi .= ' ('.Tanggal::namaBulan($tgl_transaksi).')';
                }

                return view('transaksi.jurnal_umum.partials.form_nominal')->with(compact('relasi', 'keterangan_transaksi', 'susut'));
            }
        }
    }

    public function formAngsuran($id_pinkel)
    {
        $pinkel = PinjamanKelompok::where('id', $id_pinkel)->with('kelompok')->withSum([
            'rencana' => function ($query) {
                $query->where('jatuh_tempo', '<=', date('Y-m-t'));
            },
        ], 'wajib_pokok')->withSum([
            'rencana' => function ($query) {
                $query->where('jatuh_tempo', '<=', date('Y-m-t'));
            },
        ], 'wajib_jasa')->firstOrFail();
        $real = RealAngsuran::where([
            ['loan_id', $id_pinkel],
            ['tgl_transaksi', '<=', date('Y-m-d')],
        ])->orderBy('tgl_transaksi', 'DESC')->orderBy('id', 'DESC');

        $alokasi_jasa = round($pinkel->alokasi * ($pinkel->pros_jasa / 100), 2);

        if ($real->count() > 0) {
            $real = $real->first();
        } else {
            $real->sum_pokok = 0;
            $real->sum_jasa = 0;
        }

        $target_pokok = $pinkel->rencana_sum_wajib_pokok;
        $target_jasa = $pinkel->rencana_sum_wajib_jasa;

        $saldo_pokok = round(($target_pokok - $real->sum_pokok > 0) ? $target_pokok - $real->sum_pokok : 0, 2);
        $saldo_jasa = round(($target_jasa - $real->sum_jasa > 0) ? $target_jasa - $real->sum_jasa : 0, 2);

        $sisa_pokok = round($pinkel->alokasi - $real->sum_pokok, 2);
        $sisa_jasa = round($alokasi_jasa - $real->sum_jasa, 2);

        if ($saldo_pokok > $sisa_pokok) {
            $saldo_pokok = $sisa_pokok;
        }

        if ($saldo_jasa > $sisa_jasa) {
            $saldo_jasa = $sisa_jasa;
        }

        $saldo_pokok = max(0, $saldo_pokok);
        $saldo_jasa = max(0, $saldo_jasa);

        $sum_pokok = $real->sum_pokok;
        $sum_jasa = $real->sum_jasa;

        $bendahara = $pinkel->kelompok->bendahara;
        if ($pinkel->struktur_kelompok) {
            $struktur_kelompok = json_decode($pinkel->struktur_kelompok, true);
            $bendahara = isset($struktur_kelompok['bendahara']) ? $struktur_kelompok['bendahara'] : '';
        }

        return response()->json([
            'saldo_pokok' => $saldo_pokok,
            'saldo_jasa' => $saldo_jasa,
            'sisa_pokok' => $sisa_pokok,
            'sisa_jasa' => $sisa_jasa,
            'sum_pokok' => $sum_pokok,
            'sum_jasa' => $sum_jasa,
            'alokasi_pokok' => $pinkel->alokasi,
            'alokasi_jasa' => $alokasi_jasa,
            'pinkel' => $pinkel,
            'penyetor' => $bendahara,
        ]);
    }

    public function formAnggota($id_pinkel)
    {
        $pinkel = PinjamanKelompok::where('id', $id_pinkel)->with([
            'kelompok',
            'pinjaman_anggota',
            'pinjaman_anggota.anggota',
        ])->firstOrFail();

        return response()->json([
            'success' => true,
            'title' => 'Angsuran per Anggota Kelompok '.$pinkel->kelompok->nama_kelompok.' - Loan ID. '.$pinkel->id,
            'view' => view('transaksi.jurnal_angsuran.partials.angsuran_anggota')->with(compact('pinkel'))->render(),
        ]);
    }

    public function targetAngsuran($id_pinkel)
    {
        if (request()->get('tanggal')) {
            $tanggal = request()->get('tanggal');
            $tahun = date('Y', strtotime(Tanggal::tglNasional($tanggal)));
            $bulan = date('m', strtotime(Tanggal::tglNasional($tanggal)));
            $hari = date('t', strtotime(Tanggal::tglNasional($tanggal)));

            $pinkel = PinjamanKelompok::where('id', $id_pinkel)->with('kelompok')->withSum([
                'rencana' => function ($query) use ($tahun, $bulan, $hari) {
                    $query->where('jatuh_tempo', '<=', $tahun.'-'.$bulan.'-'.$hari);
                },
            ], 'wajib_pokok')->withSum([
                'rencana' => function ($query) use ($tahun, $bulan, $hari) {
                    $query->where('jatuh_tempo', '<=', $tahun.'-'.$bulan.'-'.$hari);
                },
            ], 'wajib_jasa')->firstOrFail();
        } else {
            $pinkel = PinjamanKelompok::where('id', $id_pinkel)->with('kelompok')->withSum([
                'rencana' => function ($query) {
                    $query->where('jatuh_tempo', '<=', date('Y-m-t'));
                },
            ], 'wajib_pokok')->withSum([
                'rencana' => function ($query) {
                    $query->where('jatuh_tempo', '<=', date('Y-m-t'));
                },
            ], 'wajib_jasa')->firstOrFail();
        }

        $real = RealAngsuran::where([
            ['loan_id', $id_pinkel],
            ['tgl_transaksi', '<=', date('Y-m-d')],
        ])->orderBy('tgl_transaksi', 'DESC')->orderBy('id', 'DESC');

        if ($real->count() > 0) {
            $real = $real->first();
        } else {
            $real->sum_pokok = 0;
            $real->sum_jasa = 0;
        }

        $target_pokok = $pinkel->rencana_sum_wajib_pokok;
        $target_jasa = $pinkel->rencana_sum_wajib_jasa;

        $saldo_pokok = round(($target_pokok - $real->sum_pokok > 0) ? $target_pokok - $real->sum_pokok : 0, 2);
        $saldo_jasa = round(($target_jasa - $real->sum_jasa > 0) ? $target_jasa - $real->sum_jasa : 0, 2);

        $alokasi_jasa = round($pinkel->alokasi * ($pinkel->pros_jasa / 100), 2);
        $sisa_pokok = round($pinkel->alokasi - $real->sum_pokok, 2);
        $sisa_jasa = round($alokasi_jasa - $real->sum_jasa, 2);

        if ($saldo_pokok > $sisa_pokok) {
            $saldo_pokok = $sisa_pokok;
        }

        if ($saldo_jasa > $sisa_jasa) {
            $saldo_jasa = $sisa_jasa;
        }

        $saldo_pokok = max(0, $saldo_pokok);
        $saldo_jasa = max(0, $saldo_jasa);

        return response()->json([
            'saldo_pokok' => $saldo_pokok,
            'saldo_jasa' => $saldo_jasa,
        ]);
    }

    public function detailTransaksi(Request $request)
    {
        $keuangan = new Keuangan;

        $data['kode_akun'] = $request->kode_akun;
        $data['tahun'] = $request->tahun;
        $data['bulan'] = $request->bulan;
        $data['hari'] = $request->hari;

        if ($data['tahun'] == null) {
            abort(404);
        }

        $data['bulanan'] = true;
        if ($data['bulan'] == null) {
            $data['bulanan'] = false;
            $data['bulan'] = '12';
        }

        $data['harian'] = true;
        if ($data['hari'] == null) {
            $data['harian'] = false;
            $data['hari'] = date('t', strtotime($data['tahun'].'-'.$data['bulan'].'-01'));
        }

        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $tgl = $thn.'-';
        $data['judul'] = 'Laporan Tahunan';
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        $awal_bulan = $thn.'00-00';
        if ($data['bulanan']) {
            $tgl = $thn.'-'.$bln.'-';
            $data['judul'] = 'Laporan Bulanan';
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $bulan_lalu = date('m', strtotime('-1 month', strtotime($tgl.'01')));
            $awal_bulan = $thn.'-'.$bulan_lalu.'-'.date('t', strtotime($thn.'-'.$bulan_lalu));
            if ($bln == 1) {
                $awal_bulan = $thn.'00-00';
            }
        }

        if ($data['harian']) {
            $tgl = $thn.'-'.$bln.'-'.$hari;
            $data['judul'] = 'Laporan Harian';
            $data['sub_judul'] = 'Tanggal '.$hari.' '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::tglLatin($tgl);
            $awal_bulan = date('Y-m-d', strtotime('-1 day', strtotime($tgl)));
        }

        $data['tgl_kondisi'] = $data['tahun'].'-'.$data['bulan'].'-'.$data['hari'];

        $data['is_dir'] = (auth()->guard('web')->user()->level == 1 && (auth()->guard('web')->user()->jabatan == 1 || auth()->guard('web')->user()->jabatan == 3)) ? true : false;
        $data['is_ben'] = (auth()->guard('web')->user()->level == 1 && (auth()->guard('web')->user()->jabatan == 3)) ? true : false;

        $data['rek'] = Rekening::where('kode_akun', $data['kode_akun'])->first();
        $data['transaksi'] = Transaksi::where('tgl_transaksi', 'LIKE', '%'.$tgl.'%')->where(function ($query) use ($data) {
            $query->where('rekening_debit', $data['kode_akun'])->orwhere('rekening_kredit', $data['kode_akun']);
        })->with('user')->orderBy('tgl_transaksi', 'ASC')->orderBy('urutan', 'ASC')->orderBy('idt', 'ASC')->get();

        $data['keuangan'] = $keuangan;
        $data['saldo'] = $keuangan->saldoAwal($data['tgl_kondisi'], $data['kode_akun']);
        $data['d_bulan_lalu'] = $keuangan->saldoD($awal_bulan, $data['kode_akun']);
        $data['k_bulan_lalu'] = $keuangan->saldoK($awal_bulan, $data['kode_akun']);

        return [
            'label' => '<i class="fas fa-book"></i> '.$data['rek']->kode_akun.' - '.$data['rek']->nama_akun.' '.$data['sub_judul'],
            'view' => view('transaksi.jurnal_umum.partials.jurnal', $data)->render(),
            'cetak' => view('transaksi.jurnal_umum.partials._jurnal', $data)->render(),
        ];
    }

    public function data($idt)
    {
        $trx = Transaksi::where('idt', $idt)->first();
        $jumlah = $trx->jumlah;

        $id_pinj = $trx->id_pinj;
        $idtp = $trx->idtp;
        if ($idtp != '0') {
            $trx = Transaksi::where('idtp', $idtp)->orderBy('urutan', 'ASC')->sum('jumlah');
            $jumlah = $trx;
        }

        return response()->json([
            'idt' => $idt,
            'idtp' => $idtp,
            'id_pinj' => $id_pinj,
            'jumlah' => number_format($jumlah),
        ]);
    }

    public function reversal(Request $request)
    {
        $last_idtp = Transaksi::where('idtp', '!=', '0')->max('idtp');
        $idt = $request->rev_idt;
        $idtp = $request->rev_idtp;
        $id_pinj = $request->rev_id_pinj;
        $idtp_baru = $last_idtp + 1;

        $bulan = 0;
        $tahun = 0;
        $kode_akun = [];

        $angsuran = false;
        if ($idtp != '0') {
            $trx_reversal = [];
            $pinkel = PinjamanKelompok::where('id', $id_pinj)->with('pinjaman_anggota')->first();
            $transaksi = Transaksi::where('idtp', $idtp)->orderBy('idtp', 'ASC')->get();
            foreach ($transaksi as $trx) {
                $tgl = explode('-', $trx->tgl_transaksi);
                $tahun = $tgl[0];
                $bulan = $tgl[1];

                $kode_akun[$trx->rekening_debit] = $trx->rekening_debit;
                $kode_akun[$trx->rekening_kredit] = $trx->rekening_kredit;

                $trx_reversal[] = [
                    'tgl_transaksi' => (string) date('Y-m-d'),
                    'rekening_debit' => (string) $trx->rekening_debit,
                    'rekening_kredit' => (string) $trx->rekening_kredit,
                    'idtp' => $idtp_baru,
                    'id_pinj' => $trx->id_pinj,
                    'id_pinj_i' => $trx->id_pinj_i,
                    'keterangan_transaksi' => (string) 'KOREKSI idt ('.$idt.') : '.$trx->keterangan_transaksi,
                    'relasi' => (string) $trx->relasi,
                    'jumlah' => ($trx->jumlah * -1),
                    'urutan' => $trx->urutan,
                    'id_user' => auth()->user()->id,
                ];
            }
            $reversal = Transaksi::insert($trx_reversal);

            $pinjaman_anggota = $pinkel->pinjaman_anggota;
            foreach ($pinjaman_anggota as $pa) {
                $kom_pokok = json_decode($pa->kom_pokok, true);
                $kom_jasa = json_decode($pa->kom_jasa, true);

                if (is_array($kom_pokok)) {
                    $kom_pokok[$idtp_baru] = $kom_pokok[$idtp] * -1;
                } else {
                    $kom_pokok = [];
                }

                if (is_array($kom_jasa)) {
                    $kom_jasa[$idtp_baru] = $kom_jasa[$idtp] * -1;
                } else {
                    $kom_jasa = [];
                }

                PinjamanAnggota::where('id', $pa->id)->update([
                    'kom_pokok' => $kom_pokok,
                    'kom_jasa' => $kom_jasa,
                ]);
            }

            $angsuran = true;
        } else {
            $trx = Transaksi::where('idt', $idt)->first();

            $tgl = explode('-', $trx->tgl_transaksi);
            $tahun = $tgl[0];
            $bulan = $tgl[1];

            $kode_akun[$trx->rekening_debit] = $trx->rekening_debit;
            $kode_akun[$trx->rekening_kredit] = $trx->rekening_kredit;

            $reversal = Transaksi::create([
                'tgl_transaksi' => (string) date('Y-m-d'),
                'rekening_debit' => (string) $trx->rekening_debit,
                'rekening_kredit' => (string) $trx->rekening_kredit,
                'idtp' => $trx->idtp,
                'id_pinj' => $trx->id_pinj,
                'id_pinj_i' => $trx->id_pinj_i,
                'keterangan_transaksi' => (string) 'KOREKSI idt ('.$idt.') : '.$trx->keterangan_transaksi,
                'relasi' => (string) $trx->relasi,
                'jumlah' => ($trx->jumlah * -1),
                'urutan' => $trx->urutan,
                'id_user' => auth()->user()->id,
            ]);
        }

        if ($angsuran) {
            $this->regenerateReal($pinkel);
        }

        return response()->json([
            'success' => true,
            'msg' => 'Transaksi Reversal untuk id '.$idt.' dengan nominal berhasil.',
            'idtp' => $last_idtp + 1,
            'tgl_transaksi' => date('Y-m-d'),
            'id_pinkel' => $id_pinj,
            'kode_akun' => implode(',', $kode_akun),
            'bulan' => str_pad($bulan, 2, '0', STR_PAD_LEFT),
            'tahun' => $tahun,
        ]);
    }

    public function hapus(Request $request)
    {
        $idt = $request->del_idt;
        $idtp = $request->del_idtp;
        $id_pinj = $request->del_id_pinj;

        if ($idtp != '0') {
            $transaksi = Transaksi::where('idtp', $idtp)->get();
            $trx = Transaksi::where('idtp', $idtp)->delete();

            if ($id_pinj != '0') {
                $pinkel = PinjamanKelompok::where('id', $id_pinj)->with('pinjaman_anggota')->first();

                $pinjaman_anggota = $pinkel->pinjaman_anggota;
                foreach ($pinjaman_anggota as $pa) {
                    $kom_pokok = json_decode($pa->kom_pokok, true);
                    $kom_jasa = json_decode($pa->kom_jasa, true);

                    if (is_array($kom_pokok)) {
                        unset($kom_pokok[$idtp]);
                    } else {
                        $kom_pokok = [];
                    }

                    if (is_array($kom_jasa)) {
                        unset($kom_jasa[$idtp]);
                    } else {
                        $kom_jasa = [];
                    }

                    PinjamanAnggota::where('id', $pa->id)->update([
                        'kom_pokok' => $kom_pokok,
                        'kom_jasa' => $kom_jasa,
                    ]);
                }

                $this->regenerateReal($pinkel);
            }
        } else {
            if ($id_pinj != '0') {
                $pinkel = PinjamanKelompok::where('id', $id_pinj)->update([
                    'status' => 'W',
                ]);

                $pinj_anggota = PinjamanAnggota::where('id_pinkel', $id_pinj)->update([
                    'status' => 'W',
                    'kom_pokok' => 0,
                    'kom_jasa' => 0,
                ]);
            }

            $rek_inventaris = ['1.2.01.01', '1.2.01.02', '1.2.01.03', '1.2.01.04', '1.2.03.01', '1.2.03.02', '1.2.03.03', '1.2.03.04'];

            $trx = Transaksi::where('idt', $idt)->first();
            if (in_array($trx->rekening_debit, $rek_inventaris)) {
                $jenis = intval(explode('.', $trx->rekening_debit)[2]);
                $kategori = intval(explode('.', $trx->rekening_debit)[3]);
                $nama_barang = trim(explode(')', $trx->keterangan_transaksi)[1]);

                $inv = Inventaris::Where([
                    ['jenis', $jenis],
                    ['kategori', $kategori],
                    ['tgl_beli', $trx->tgl_transaksi],
                    ['nama_barang', $nama_barang],
                ])->delete();
            }

            $transaksi = Transaksi::where('idt', $idt)->get();
            $trx = Transaksi::where('idt', $idt)->delete();
        }

        $bulan = 0;
        $tahun = 0;
        $kode_akun = [];
        foreach ($transaksi as $trx) {
            $tgl = explode('-', $trx->tgl_transaksi);
            $tahun = $tgl[0];
            $bulan = $tgl[1];

            $kode_akun[$trx->rekening_debit] = $trx->rekening_debit;
            $kode_akun[$trx->rekening_kredit] = $trx->rekening_kredit;
        }
        $kode_akun = array_values($kode_akun);

        return response()->json([
            'success' => true,
            'msg' => 'Transaksi Berhasil Dihapus.',
            'kode_akun' => implode(',', $kode_akun),
            'bulan' => str_pad($bulan, 2, '0', STR_PAD_LEFT),
            'tahun' => $tahun,
        ]);
    }

    // Inactive
    public function simpanSaldo(array $kode_akun, $bulan, $tahun)
    {
        $date = $tahun.'-'.$bulan.'-01';
        $tgl_kondisi = date('Y-m-t', strtotime($date));

        $rekening = Rekening::withSum([
            'trx_debit' => function ($query) use ($tgl_kondisi, $tahun) {
                $query->whereBetween('tgl_transaksi', [$tahun.'-01-01', $tgl_kondisi]);
            },
        ], 'jumlah')->withSum([
            'trx_kredit' => function ($query) use ($tgl_kondisi, $tahun) {
                $query->whereBetween('tgl_transaksi', [$tahun.'-01-01', $tgl_kondisi]);
            },
        ], 'jumlah')->whereIn('kode_akun', $kode_akun)->orderBy('kode_akun', 'ASC')->get();

        foreach ($rekening as $rek) {
            $simpan_saldo = Saldo::where('tahun', $tahun)->whereBetween('bulan', [$bulan, '12'])->update([
                'debit' => $rek->trx_debit_sum_jumlah,
                'kredit' => $rek->trx_kredit_sum_jumlah,
            ]);
        }

        return true;
    }

    public function detailAngsuran($id)
    {
        $pinkel = PinjamanKelompok::where('id', $id)->with([
            'real' => function ($query) {
                $query->where('id', '!=', '0');
            },
            'real.trx',
            'kelompok',
        ])->first();

        $is_dir = (auth()->guard('web')->user()->level == 1 && (auth()->guard('web')->user()->jabatan == 1 || auth()->guard('web')->user()->jabatan == 3)) ? true : false;
        $is_ben = (auth()->guard('web')->user()->level == 1 && (auth()->guard('web')->user()->jabatan == 3)) ? true : false;

        return [
            'label' => '<i class="fas fa-book"></i> Detail Angsuran Kelompok '.$pinkel->kelompok->nama_kelompok,
            'label_cetak' => '<i class="fas fa-book"></i> Cetak Dokumen Angsuran Kelompok '.$pinkel->kelompok->nama_kelompok,
            'view' => view('transaksi.jurnal_angsuran.partials.detail')->with(compact('pinkel', 'is_dir', 'is_ben'))->render(),
            'cetak' => view('transaksi.jurnal_angsuran.partials._detail')->with(compact('pinkel'))->render(),
        ];
    }

    public function struk($id)
    {
        $data['real'] = $this->resolveRealForStruk($id);
        $data['pinkel'] = PinjamanKelompok::where('id', $data['real']->loan_id)->with([
            'kelompok',
            'kelompok.d',
            'kelompok.d.sebutan_desa',
            'jpp',
            'sis_pokok',
        ])->first();

        $data['ra_bulan_ini'] = RencanaAngsuran::where([
            ['loan_id', $data['real']->loan_id],
            ['jatuh_tempo', '<=', date('Y-m-t', strtotime($data['real']->tgl_transaksi))],
        ])->orderBy('jatuh_tempo', 'DESC')->first();
        $data['angsuran'] = RencanaAngsuran::where([
            ['loan_id', $data['real']->loan_id],
            ['target_pokok', '<=', $data['real']->sum_pokok],
        ])->orderBy('jatuh_tempo', 'DESC')->first();

        $data['user'] = User::where('id', $data['real']->id_user)->first();
        $data['kec'] = Kecamatan::where('id', Session::get('lokasi'))->with('kabupaten')->first();
        $data['keuangan'] = new Keuangan;

        return view('transaksi.jurnal_angsuran.dokumen.struk', $data);
    }

    public function strukMatrix($id)
    {
        $data['real'] = $this->resolveRealForStruk($id);
        $data['pinkel'] = PinjamanKelompok::where('id', $data['real']->loan_id)->with([
            'kelompok',
            'kelompok.d',
            'kelompok.d.sebutan_desa',
            'jpp',
            'sis_pokok',
        ])->first();

        $data['ra_bulan_ini'] = RencanaAngsuran::where([
            ['loan_id', $data['real']->loan_id],
            ['jatuh_tempo', '<=', date('Y-m-t', strtotime($data['real']->tgl_transaksi))],
        ])->orderBy('jatuh_tempo', 'DESC')->first();
        $data['angsuran'] = RencanaAngsuran::where([
            ['loan_id', $data['real']->loan_id],
            ['target_pokok', '<=', $data['real']->sum_pokok],
        ])->orderBy('jatuh_tempo', 'DESC')->first();

        $data['user'] = User::where('id', $data['real']->id_user)->first();
        $data['kec'] = Kecamatan::where('id', Session::get('lokasi'))->with('kabupaten')->first();
        $data['keuangan'] = new Keuangan;

        return view('transaksi.jurnal_angsuran.dokumen.struk_matrix', $data);
    }

    public function strukThermal($id)
    {
        $data['kertas'] = '80';
        if (request()->get('kertas')) {
            $data['kertas'] = request()->get('kertas');
        }

        $data['real'] = $this->resolveRealForStruk($id);
        $data['pinkel'] = PinjamanKelompok::where('id', $data['real']->loan_id)->with([
            'kelompok',
            'kelompok.d',
            'kelompok.d.sebutan_desa',
            'jpp',
            'sis_pokok',
        ])->first();

        $data['ra_bulan_ini'] = RencanaAngsuran::where([
            ['loan_id', $data['real']->loan_id],
            ['jatuh_tempo', '<=', date('Y-m-t', strtotime($data['real']->tgl_transaksi))],
        ])->orderBy('jatuh_tempo', 'DESC')->first();
        $data['angsuran'] = RencanaAngsuran::where([
            ['loan_id', $data['real']->loan_id],
            ['target_pokok', '<=', $data['real']->sum_pokok],
        ])->orderBy('jatuh_tempo', 'DESC')->first();

        $data['user'] = User::where('id', $data['real']->id_user)->first();
        $data['kec'] = Kecamatan::where('id', Session::get('lokasi'))->with('kabupaten')->first();
        $data['keuangan'] = new Keuangan;

        $lebarKertas = 226.772;
        if ($data['kertas'] != '80') {
            $lebarKertas = 164.409;
        }

        $dompdf = new Dompdf();
        $dompdf->setPaper([0, 0, $lebarKertas, 566.929]);

        $GLOBALS['bodyHeight'] = 0;

        $dompdf->setCallbacks(
            [
                'myCallbacks' => [
                    'event' => 'end_frame',
                    'f' => function ($infos) {
                        $frame = $infos->get_frame();

                        if (strtolower($frame->get_node()->nodeName) === 'body') {
                            $padding_box = $frame->get_padding_box();
                            $GLOBALS['bodyHeight'] += $padding_box['h'];

                            return;
                        }
                    },
                ],
            ]
        );

        $html = view('transaksi.jurnal_angsuran.dokumen.struk_thermal', $data)->render();

        $dompdf->loadHtml($html);
        $dompdf->render();
        unset($dompdf);

        $pdf = PDF::loadHTML($html);
        $pdf->setPaper([0, 0, $lebarKertas, $GLOBALS['bodyHeight'] + 80]);

        return $pdf->stream();
    }

    public function saldo($kode_akun)
    {
        $keuangan = new Keuangan;

        $total_saldo = 0;
        if (request()->get('tahun') || request()->get('bulan') || request()->get('hari')) {
            $data = [];
            $data['tahun'] = request()->get('tahun');
            $data['bulan'] = request()->get('bulan');
            $data['hari'] = request()->get('hari');
            $data['kode_akun'] = $kode_akun;

            $thn = $data['tahun'];
            $bln = $data['bulan'];
            $hari = $data['hari'];

            $tgl = $thn.'-'.$bln.'-';
            $bulan_lalu = date('m', strtotime('-1 month', strtotime($tgl.'01')));
            $awal_bulan = $thn.'-'.$bulan_lalu.'-'.date('t', strtotime($thn.'-'.$bulan_lalu));
            if ($bln == 1) {
                $awal_bulan = $thn.'00-00';
            }

            $data['tgl_kondisi'] = $tgl;
            $data['rek'] = Rekening::where('kode_akun', $data['kode_akun'])->with([
                'kom_saldo' => function ($query) use ($data) {
                    $query->where('tahun', $data['tahun'])->where(function ($query) use ($data) {
                        $query->where('bulan', '0')->orwhere('bulan', $data['bulan']);
                    });
                },
            ])->first();

            $total_saldo = $keuangan->komSaldo($data['rek']);
        }

        return response()->json([
            'saldo' => $total_saldo,
        ]);
    }

    public function kuitansi($id)
    {
        $keuangan = new Keuangan;

        $kec = Kecamatan::where('id', Session::get('lokasi'))->with('kabupaten')->first();
        $trx = Transaksi::where('idt', $id)->first();
        $user = User::where('id', $trx->id_user)->first();

        $jenis = 'BKM';
        $dari = ucwords($trx->relasi);
        $oleh = ucwords($user->namadepan.' '.$user->namabelakang);
        $dibayar = ucwords($trx->relasi);
        if ($trx->rekening_kredit == '1.1.01.01' or ($keuangan->startWith($trx->rekening_kredit, '1.1.02') || $keuangan->startWith($trx->rekening_kredit, '1.1.01'))) {
            $jenis = 'BKK';
            $dari = $kec->sebutan_level_3.' '.ucwords($kec->nama_lembaga_sort);
            $oleh = ucwords($trx->relasi);
            $dibayar = ucwords($user->namadepan.' '.$user->namabelakang);
        }

        $gambar = $kec->logo;

        return view('transaksi.dokumen.kuitansi')->with(compact('trx', 'kec', 'jenis', 'dari', 'oleh', 'dibayar', 'gambar', 'keuangan'));
    }

    public function kuitansi_thermal($id)
    {
        $kertas = '80';
        if (request()->get('kertas')) {
            $kertas = request()->get('kertas');
        }

        $keuangan = new Keuangan;

        $kec = Kecamatan::where('id', Session::get('lokasi'))->with('kabupaten')->first();
        $trx = Transaksi::where('idt', $id)->first();
        $user = User::where('id', $trx->id_user)->first();

        $jenis = 'BKM';
        $dari = ucwords($trx->relasi);
        $oleh = ucwords(auth()->user()->namadepan.' '.auth()->user()->namabelakang);
        $dibayar = ucwords($trx->relasi);
        if ($trx->rekening_kredit == '1.1.01.01' or ($keuangan->startWith($trx->rekening_kredit, '1.1.02') || $keuangan->startWith($trx->rekening_kredit, '1.1.01'))) {
            $jenis = 'BKK';
            $dari = $kec->sebutan_level_3.' '.ucwords($kec->nama_lembaga_sort);
            $oleh = ucwords($trx->relasi);
            $dibayar = ucwords($user->namadepan.' '.$user->namabelakang);
        }

        $gambar = $kec->logo;

        return view('transaksi.dokumen.kuitansi_thermal')->with(compact('trx', 'kec', 'jenis', 'dari', 'oleh', 'dibayar', 'gambar', 'keuangan', 'kertas'));
    }

    public function bkk($id)
    {
        $keuangan = new Keuangan;

        $kec = Kecamatan::where('id', Session::get('lokasi'))->with('kabupaten')->first();
        $trx = Transaksi::where('idt', $id)->with('rek_debit')->with('rek_kredit')->first();
        $user = User::where('id', $trx->id_user)->first();

        $dir = User::where([
            ['level', '1'],
            ['jabatan', '1'],
            ['lokasi', Session::get('lokasi')],
        ])->first();

        $sekr = User::where([
            ['level', '1'],
            ['jabatan', '3'],
            ['lokasi', Session::get('lokasi')],
        ])->first();

        $dir_utama = User::where([
            ['level', '2'],
            ['jabatan', '65'],
            ['lokasi', Session::get('lokasi')],
        ])->first();

        $gambar = $kec->logo;

        return view('transaksi.dokumen.bkk')->with(compact('trx', 'kec', 'dir', 'sekr', 'dir_utama', 'gambar', 'keuangan'));
    }

    public function bkm($id)
    {
        $keuangan = new Keuangan;

        $kec = Kecamatan::where('id', Session::get('lokasi'))->with('kabupaten')->first();
        $trx = Transaksi::where('idt', $id)->with('rek_debit')->with('rek_kredit')->first();
        $user = User::where('id', $trx->id_user)->first();

        $dir = User::where([
            ['level', '1'],
            ['jabatan', '1'],
            ['lokasi', Session::get('lokasi')],
        ])->first();

        $sekr = User::where([
            ['level', '1'],
            ['jabatan', '3'],
            ['lokasi', Session::get('lokasi')],
        ])->first();

        $dir_utama = User::where([
            ['level', '2'],
            ['jabatan', '65'],
            ['lokasi', Session::get('lokasi')],
        ])->first();

        $gambar = $kec->logo;

        return view('transaksi.dokumen.bkm')->with(compact('trx', 'kec', 'dir', 'sekr', 'dir_utama', 'gambar', 'keuangan'));
    }

    public function bm($id)
    {
        $keuangan = new Keuangan;

        $kec = Kecamatan::where('id', Session::get('lokasi'))->with('kabupaten')->first();
        $trx = Transaksi::where('idt', $id)->with('rek_debit')->with('rek_kredit')->first();
        $user = User::where('id', $trx->id_user)->first();

        $dir = User::where([
            ['level', '1'],
            ['jabatan', '1'],
            ['lokasi', Session::get('lokasi')],
        ])->first();

        $sekr = User::where([
            ['level', '1'],
            ['jabatan', '3'],
            ['lokasi', Session::get('lokasi')],
        ])->first();

        $dir_utama = User::where([
            ['level', '2'],
            ['jabatan', '65'],
            ['lokasi', Session::get('lokasi')],
        ])->first();

        $gambar = $kec->logo;

        return view('transaksi.dokumen.bm')->with(compact('trx', 'kec', 'dir', 'sekr', 'dir_utama', 'gambar', 'keuangan'));
    }

    public function bkmAngsuran($id)
    {
        $keuangan = new Keuangan;

        $kec = Kecamatan::where('id', Session::get('lokasi'))->with('kabupaten')->first();
        $trx = Transaksi::where('idt', $id)->with('pinkel', 'pinkel.kelompok', 'rek_debit', 'tr_idtp', 'tr_idtp.rek_kredit')->withSum('tr_idtp', 'jumlah')->first();
        $user = User::where('id', $trx->id_user)->first();

        $dir = User::where([
            ['level', '1'],
            ['jabatan', '1'],
            ['lokasi', Session::get('lokasi')],
        ])->first();

        $sekr = User::where([
            ['level', '1'],
            ['jabatan', '3'],
            ['lokasi', Session::get('lokasi')],
        ])->first();

        $dir_utama = User::where([
            ['level', '2'],
            ['jabatan', '65'],
            ['lokasi', Session::get('lokasi')],
        ])->first();

        $gambar = $kec->logo;

        return view('transaksi.jurnal_angsuran.dokumen.bkm')->with(compact('trx', 'kec', 'dir', 'sekr', 'dir_utama', 'gambar', 'keuangan'));
    }

    public function cetak(Request $request)
    {
        $keuangan = new Keuangan;
        $idt = $request->cetak;

        $data['kec'] = Kecamatan::where('id', Session::get('lokasi'))->with('kabupaten')->first();
        $data['transaksi'] = Transaksi::whereIn('idt', $idt)->with('rek_debit', 'rek_kredit', 'tr_idtp', 'tr_idtp.rek_kredit')->withSum('tr_idtp', 'jumlah')->get();

        $data['dir'] = User::where([
            ['level', '1'],
            ['jabatan', '1'],
            ['lokasi', Session::get('lokasi')],
        ])->first();

        $data['sekr'] = User::where([
            ['level', '1'],
            ['jabatan', '3'],
            ['lokasi', Session::get('lokasi')],
        ])->first();

        $logo = $data['kec']->logo;
        $data['gambar'] = $logo;
        $data['keuangan'] = $keuangan;

        $view = view('transaksi.dokumen.cetak', $data)->render();
        $pdf = PDF::loadHTML($view)->setPaper('A4', 'landscape');

        return $pdf->stream();
    }

    public function cetakBkm(Request $request)
    {
        $keuangan = new Keuangan;
        $idt = $request->cetak;

        $data['kec'] = Kecamatan::where('id', Session::get('lokasi'))->with('kabupaten')->first();
        $data['transaksi'] = Transaksi::whereIn('idt', $idt)->with('rek_debit', 'rek_kredit', 'tr_idtp', 'tr_idtp.rek_kredit')->withSum('tr_idtp', 'jumlah')->get();

        $data['dir'] = User::where([
            ['level', '1'],
            ['jabatan', '1'],
            ['lokasi', Session::get('lokasi')],
        ])->first();

        $data['sekr'] = User::where([
            ['level', '1'],
            ['jabatan', '3'],
            ['lokasi', Session::get('lokasi')],
        ])->first();

        $logo = $data['kec']->logo;
        $data['gambar'] = $logo;
        $data['keuangan'] = $keuangan;

        $view = view('transaksi.dokumen.cetak', $data)->render();
        $pdf = PDF::loadHTML($view)->setPaper('A4', 'landscape');

        return $pdf->stream();
    }

    public function cetakKuitansi(Request $request)
    {
    }

    public function lpp($id)
    {
        $data['bulan'] = date('Y-m-t');
        $data['kec'] = Kecamatan::where('id', Session::get('lokasi'))->with('kabupaten')->first();
        $data['pinkel'] = PinjamanKelompok::where('id', $id)->with([
            'kelompok',
            'kelompok.d',
            'kelompok.d.sebutan_desa',
            'jpp',
            'jasa',
            'target' => function ($query) {
                $query->where('angsuran_ke', '1');
            },
        ])->first();

        $data['rencana'] = RencanaAngsuran::where([
            ['loan_id', $id],
            ['angsuran_ke', '!=', '0'],
        ])->with([
            'real' => function ($query) {
                $query->orderBy('tgl_transaksi', 'DESC')->orderBy('id', 'DESC');
            },
            'ra',
        ])->get();

        $data['laporan'] = 'LPP Kelompok '.$data['pinkel']->kelompok->nama_kelompok;

        return view('transaksi.jurnal_angsuran.dokumen.lpp', $data);
    }

    public function generateReal($id_pinkel)
    {
        $pinkel = PinjamanKelompok::where('id', $id_pinkel)->first();
        if (! $pinkel) {
            return response()->json([
                'success' => false,
                'msg' => 'Error',
            ]);
        }

        $this->generateService->generateByLoan($pinkel);

        return response()->json([
            'success' => true,
        ]);
    }

    public function regenerateReal($pinkel)
    {
        if (! $pinkel) {
            return response()->json([
                'success' => false,
                'msg' => 'Error',
            ]);
        }

        $this->generateService->generateByLoan($pinkel);

        return response()->json([
            'success' => true,
        ]);
    }

    public function realisasi($id_pinkel)
    {
        $pinkel = PinjamanKelompok::where('id', $id_pinkel)->first();

        return $this->regenerateReal($pinkel);
    }
}
