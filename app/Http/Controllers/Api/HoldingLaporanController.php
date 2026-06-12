<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AkunLevel1;
use App\Models\ArusKas;
use App\Models\Calk;
use App\Models\Kecamatan;
use App\Models\Rekening;
use App\Models\Saldo;
use App\Models\Transaksi;
use App\Models\User;
use App\Utils\Keuangan;
use App\Utils\Tanggal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HoldingLaporanController extends Controller
{
    /**
     * Validasi param periode: tahun (required), bulan (optional), hari (optional).
     * Return [data, tgl_kondisi].
     */
    private function validatePeriode(Request $request): array
    {
        $data = $request->validate([
            'tahun' => 'required|integer|min:2000|max:2100',
            'bulan' => 'nullable|integer|min:1|max:12',
            'hari'  => 'nullable|integer|min:1|max:31',
        ]);

        $data['bulanan'] = true;
        if (empty($data['bulan'])) {
            $data['bulanan'] = false;
            $data['bulan'] = 12;
        }

        $data['harian'] = true;
        if (empty($data['hari'])) {
            $data['harian'] = false;
            $data['hari'] = (int) date('t', strtotime($data['tahun'].'-'.str_pad($data['bulan'], 2, '0', STR_PAD_LEFT).'-01'));
        }

        $bulanPad = str_pad($data['bulan'], 2, '0', STR_PAD_LEFT);
        $hariPad  = str_pad($data['hari'], 2, '0', STR_PAD_LEFT);
        $data['tgl_kondisi'] = $data['tahun'].'-'.$bulanPad.'-'.$hariPad;

        return $data;
    }

    private function kecamatan(Request $request): Kecamatan
    {
        $kec = $request->attributes->get('holding_kecamatan');
        abort_unless($kec, 500, 'Kecamatan tidak ditemukan pada request.');
        return $kec;
    }

    /**
     * GET /api/v1/holding/laporan/neraca
     * Query: tahun, bulan?, hari?, file_type?=1
     */
    public function neraca(Request $request): JsonResponse
    {
        $data = $this->validatePeriode($request);
        $data['file_type'] = $request->input('file_type', '1');
        $kec = $this->kecamatan($request);
        $data['kec'] = $kec;

        $data['akun1'] = AkunLevel1::where('lev1', '<=', '3')->with([
            'akun2',
            'akun2.akun3',
            'akun2.akun3.rek',
            'akun2.akun3.rek.kom_saldo' => function ($q) use ($data) {
                $q->where('tahun', $data['tahun'])
                  ->where(function ($q) use ($data) {
                      $q->where('bulan', '0')->orWhere('bulan', $data['bulan']);
                  });
            },
        ])->orderBy('kode_akun', 'ASC')->get();

        $payload = $this->serializeNeraca($data);

        return response()->json([
            'success'      => true,
            'laporan'      => 'Neraca',
            'kecamatan'    => $kec->nama_kec,
            'tgl_kondisi'  => $data['tgl_kondisi'],
            'sub_judul'    => 'Per '.$data['hari'].' '.Tanggal::namaBulan($data['tgl_kondisi']).' '.Tanggal::tahun($data['tgl_kondisi']),
            'data'         => $payload,
        ]);
    }

    /**
     * GET /api/v1/holding/laporan/laba-rugi
     * Query: tahun, bulan?, hari?
     */
    public function labaRugi(Request $request): JsonResponse
    {
        $data = $this->validatePeriode($request);
        $kec = $this->kecamatan($request);
        $data['kec'] = $kec;

        $jenis = $data['bulanan'] ? 'Bulanan' : 'Tahunan';
        $keuangan = new Keuangan;

        $pph = $keuangan->pph($data['tgl_kondisi'], $jenis);
        $lr  = $keuangan->laporan_laba_rugi($data['tgl_kondisi'], $jenis);

        $pendapatan  = $lr['pendapatan']      ?? [];
        $beban       = $lr['beban']           ?? [];
        $pendapatanNOP = $lr['pendapatan_non_ops'] ?? [];
        $bebanNOP    = $lr['beban_non_ops']   ?? [];

        $totalPendapatan = collect($pendapatan)->sum(fn($i) => (float) ($i['saldo_sekarang'] ?? 0));
        $totalBeban      = collect($beban)->sum(fn($i) => (float) ($i['saldo_sekarang'] ?? 0));
        $labaKotor       = $totalPendapatan - $totalBeban;

        $totalPendapatanNOP = collect($pendapatanNOP)->sum(fn($i) => (float) ($i['saldo_sekarang'] ?? 0));
        $totalBebanNOP      = collect($bebanNOP)->sum(fn($i) => (float) ($i['saldo_sekarang'] ?? 0));

        $pajak = (float) ($pph['bulan_ini'] ?? 0);
        $labaBersih = $labaKotor + $totalPendapatanNOP - $totalBebanNOP - $pajak;

        return response()->json([
            'success'   => true,
            'laporan'   => 'Laba Rugi',
            'kecamatan' => $kec->nama_kec,
            'periode'   => [
                'jenis'       => $jenis,
                'tgl_kondisi' => $data['tgl_kondisi'],
                'sub_judul'   => $data['bulanan']
                    ? 'Periode '.Tanggal::tglLatin($data['tahun'].'-01-01').' S.D '.Tanggal::tglLatin($data['tgl_kondisi'])
                    : 'Tahun '.Tanggal::tahun($data['tgl_kondisi']),
            ],
            'data' => [
                'pendapatan'           => $pendapatan,
                'beban'                => $beban,
                'total_pendapatan'     => $totalPendapatan,
                'total_beban'          => $totalBeban,
                'laba_kotor'           => $labaKotor,
                'pendapatan_non_ops'   => $pendapatanNOP,
                'beban_non_ops'        => $bebanNOP,
                'total_pendapatan_nop' => $totalPendapatanNOP,
                'total_beban_nop'      => $totalBebanNOP,
                'pph'                  => $pajak,
                'laba_bersih'          => $labaBersih,
            ],
        ]);
    }

    /**
     * GET /api/v1/holding/laporan/arus-kas
     * Query: tahun, bulan?, hari?, semester?=null|1|2
     */
    public function arusKas(Request $request): JsonResponse
    {
        $data = $this->validatePeriode($request);
        $kec = $this->kecamatan($request);
        $data['kec'] = $kec;

        $keuangan = new Keuangan;
        $semester = $request->input('semester');

        $thn = (int) $data['tahun'];
        $bln = (int) $data['bulan'];

        $jenis = 'Tahunan';
        $awal  = 'TAHUN';
        $tgl_lalu = $thn.'-00-00';

        if ($semester == '1') {
            $jenis = 'Semester I';
            $data['tgl_kondisi'] = $thn.'-06-30';
            $data['sub_judul'] = 'Semester I Tahun '.$thn;
            $data['hari'] = 30; $data['bulan'] = 6;
        } elseif ($semester == '2') {
            $jenis = 'Semester II';
            $data['tgl_kondisi'] = $thn.'-12-31';
            $data['sub_judul'] = 'Semester II Tahun '.$thn;
            $data['hari'] = 31; $data['bulan'] = 12;
        } elseif ($data['bulanan']) {
            $jenis = 'Bulanan';
            $awal  = 'BULAN';
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($thn.'-'.$bln.'-01').' '.$thn;
            $blnLalu = $bln - 1;
            $thnLalu = $thn;
            if ($blnLalu <= 0) { $blnLalu = 12; $thnLalu = $thn - 1; }
            $tgl_lalu = $thnLalu.'-'.str_pad($blnLalu, 2, '0', STR_PAD_LEFT).'-'.date('t', strtotime($thnLalu.'-'.$blnLalu.'-01'));
        } else {
            $data['sub_judul'] = 'Tahun '.$thn;
        }

        $arusKas = ArusKas::where('sub', '0')->with('child')->orderBy('id', 'ASC')->get();
        $saldoBulanLalu = $keuangan->saldoKas($tgl_lalu);

        $items = $arusKas->map(function ($ak) use ($keuangan, $data) {
            $saldo = 0.0;
            foreach ($ak->child as $child) {
                // Asumsikan tiap child memiliki field saldo_total di tabel arus_kas_detail
                $saldo += (float) ($child->saldo_total ?? 0);
            }
            return [
                'id'      => $ak->id,
                'parent'  => $ak->parent,
                'kategori'=> $ak->kategori ?? $ak->nama,
                'nama'    => $ak->nama,
                'sub'     => (int) $ak->sub,
                'saldo'   => $saldo,
            ];
        });

        $totalMasuk  = $items->where('parent', 'masuk')->sum('saldo');
        $totalKeluar = $items->where('parent', 'keluar')->sum('saldo');
        $netto       = $totalMasuk - $totalKeluar;

        return response()->json([
            'success'   => true,
            'laporan'   => 'Arus Kas',
            'kecamatan' => $kec->nama_kec,
            'periode'   => [
                'jenis'        => $jenis,
                'tgl_kondisi'  => $data['tgl_kondisi'],
                'sub_judul'    => $data['sub_judul'],
            ],
            'data' => [
                'saldo_awal'      => (float) $saldoBulanLalu,
                'arus_kas'        => $items->values(),
                'total_masuk'     => $totalMasuk,
                'total_keluar'    => $totalKeluar,
                'kenaikan_penurunan' => $netto,
                'saldo_akhir'     => (float) $saldoBulanLalu + $netto,
            ],
        ]);
    }

    /**
     * GET /api/v1/holding/laporan/perubahan-ekuitas
     * Query: tahun, bulan?, hari?
     */
    public function perubahanEkuitas(Request $request): JsonResponse
    {
        $data = $this->validatePeriode($request);
        $kec = $this->kecamatan($request);
        $data['kec'] = $kec;

        $keuangan = new Keuangan;

        $rekening = Rekening::where('lev1', '3')
            ->where(function ($q) use ($data) {
                $q->whereNull('tgl_nonaktif')->orWhere('tgl_nonaktif', '>', $data['tgl_kondisi']);
            })
            ->with(['kom_saldo' => function ($q) use ($data) {
                $q->where('tahun', $data['tahun'])
                  ->where(function ($q) use ($data) {
                      $q->where('bulan', '0')->orWhere('bulan', $data['bulan']);
                  });
            }])
            ->get();

        $rows = $rekening->map(function ($r) {
            $debit = 0.0; $kredit = 0.0;
            $debit0 = 0.0; $kredit0 = 0.0;
            foreach ($r->kom_saldo as $ks) {
                if ((int) $ks->bulan === 0) {
                    $debit0  = (float) $ks->debit;
                    $kredit0 = (float) $ks->kredit;
                } else {
                    $debit  = (float) $ks->debit;
                    $kredit = (float) $ks->kredit;
                }
            }
            $saldoAwal = $kredit0 - $debit0;
            $saldo     = $saldoAwal + ($kredit - $debit);
            if ($r->kode_akun === '3.2.02.01') $saldo = 0;

            return [
                'kode_akun'  => $r->kode_akun,
                'nama_akun'  => $r->nama_akun,
                'saldo_awal' => $saldoAwal,
                'saldo_akhir'=> $saldo,
                'mutasi'     => $saldo - $saldoAwal,
            ];
        });

        $ekuitasAwal   = $rows->sum('saldo_awal');
        $ekuitasAkhir  = $rows->sum('saldo_akhir');
        $labaRugi = (new Keuangan)->laporan_laba_rugi($data['tgl_kondisi'], $data['bulanan'] ? 'Bulanan' : 'Tahunan');
        $surplus  = collect($labaRugi['pendapatan'] ?? [])->sum(fn($i) => (float) ($i['saldo_sekarang'] ?? 0))
                 - collect($labaRugi['beban']      ?? [])->sum(fn($i) => (float) ($i['saldo_sekarang'] ?? 0));

        return response()->json([
            'success'   => true,
            'laporan'   => 'Perubahan Ekuitas',
            'kecamatan' => $kec->nama_kec,
            'periode'   => [
                'tgl_kondisi' => $data['tgl_kondisi'],
                'sub_judul'   => $data['bulanan']
                    ? 'Bulan '.Tanggal::namaBulan($data['tgl_kondisi']).' '.Tanggal::tahun($data['tgl_kondisi'])
                    : 'Tahun '.Tanggal::tahun($data['tgl_kondisi']),
            ],
            'data' => [
                'ekuitas_awal'   => $ekuitasAwal,
                'laba_rugi'      => $surplus,
                'setoran'        => $rows->where('kode_akun', '3.2.01.01')->sum('mutasi'),
                'penarikan'      => $rows->where('kode_akun', '3.2.01.02')->sum('mutasi'),
                'dividen'        => $rows->where('kode_akun', '3.2.01.03')->sum('mutasi'),
                'koreksi'        => $rows->where('kode_akun', '3.2.02.01')->sum('mutasi'),
                'komponen_ekuitas' => $rows->values(),
                'ekuitas_akhir'  => $ekuitasAkhir + $surplus,
            ],
        ]);
    }

    /**
     * GET /api/v1/holding/laporan/calk
     * Query: tahun, bulan?, hari?
     */
    public function calk(Request $request): JsonResponse
    {
        $data = $this->validatePeriode($request);
        $kec = $this->kecamatan($request);
        $data['kec'] = $kec;

        $trx = Transaksi::where('keterangan_transaksi', 'LIKE', '%tahun '.((int) $data['tahun'] - 1))
            ->where('rekening_debit', '3.2.01.01')->first();
        $tglMad = $trx ? $trx->tgl_transaksi : $data['tgl_kondisi'];

        $akun1 = AkunLevel1::where('lev1', '<=', '3')->with([
            'akun2',
            'akun2.akun3',
            'akun2.akun3.rek' => function ($q) use ($data) {
                $q->whereNull('tgl_nonaktif')->orWhere('tgl_nonaktif', '>', $data['tgl_kondisi']);
            },
            'akun2.akun3.rek.kom_saldo' => function ($q) use ($data) {
                $q->where('tahun', $data['tahun'])
                  ->where(function ($q) use ($data) {
                      $q->where('bulan', '0')->orWhere('bulan', $data['bulan']);
                  });
            },
        ])->orderBy('kode_akun', 'ASC')->get();

        $keterangan = Calk::where('lokasi', $kec->id)
            ->where('tanggal', 'LIKE', $data['tahun'].'-'.str_pad($data['bulan'], 2, '0', STR_PAD_LEFT).'%')
            ->first();

        $calk = json_decode($kec->calk, true);
        $pointA = $calk['calk']['A'] ?? '';

        $penandatangan = [
            'sekretaris' => User::where(['level' => '1', 'jabatan' => '2', 'lokasi' => $kec->id])->first(),
            'bendahara'  => User::where(['level' => '1', 'jabatan' => '3', 'lokasi' => $kec->id])->first(),
            'pengawas'   => User::where(['level' => '3', 'jabatan' => '1', 'lokasi' => $kec->id])->first(),
            'direktur'   => User::where(['level' => '2', 'jabatan' => '65', 'lokasi' => $kec->id])->first(),
        ];

        $saldoCalk = Saldo::where('kode_akun', $kec->kd_kec)
            ->where('tahun', $data['tahun'])->get();

        return response()->json([
            'success'   => true,
            'laporan'   => 'Catatan Atas Laporan Keuangan (CALK)',
            'kecamatan' => $kec->nama_kec,
            'periode'   => [
                'tgl_kondisi' => $data['tgl_kondisi'],
                'sub_judul'   => $data['bulanan']
                    ? 'Bulan '.Tanggal::namaBulan($data['tgl_kondisi']).' Tahun '.$data['tahun']
                    : 'Tahun '.$data['tahun'],
                'tgl_mad'     => $tglMad,
            ],
            'data' => [
                'point_a'         => $pointA,
                'catatan'         => $keterangan->catatan ?? null,
                'rincian_akun'    => $akun1,
                'saldo_calk'      => $saldoCalk,
                'penandatangan'   => $penandatangan,
            ],
        ]);
    }

    /**
     * Ringkas AkunLevel1 + child ke JSON: kode_akun, nama_akun, lev1, akun2[]
     * (Helper untuk endpoint neraca; serializer minimal.)
     */
    private function serializeNeraca(array $data): array
    {
        return $data['akun1']->map(function ($a1) {
            return [
                'kode_akun' => $a1->kode_akun,
                'nama_akun' => $a1->nama_akun,
                'lev1'      => $a1->lev1,
                'akun2'     => $a1->akun2->map(function ($a2) {
                    return [
                        'kode_akun' => $a2->kode_akun,
                        'nama_akun' => $a2->nama_akun,
                        'akun3'     => $a2->akun3->map(function ($a3) {
                            return [
                                'kode_akun' => $a3->kode_akun,
                                'nama_akun' => $a3->nama_akun,
                                'rekening'  => $a3->rek->map(function ($r) {
                                    $debit = 0.0; $kredit = 0.0;
                                    $d0 = 0.0; $k0 = 0.0;
                                    foreach ($r->kom_saldo as $ks) {
                                        if ((int) $ks->bulan === 0) {
                                            $d0 = (float) $ks->debit;
                                            $k0 = (float) $ks->kredit;
                                        } else {
                                            $debit  = (float) $ks->debit;
                                            $kredit = (float) $ks->kredit;
                                        }
                                    }
                                    if (in_array($r->lev1, ['1', '5'], true)) {
                                        $saldo = ($d0 - $k0) + ($debit - $kredit);
                                    } else {
                                        $saldo = ($k0 - $d0) + ($kredit - $debit);
                                    }
                                    return [
                                        'kode_akun' => $r->kode_akun,
                                        'nama_akun' => $r->nama_akun,
                                        'saldo'     => $saldo,
                                    ];
                                })->values(),
                            ];
                        })->values(),
                    ];
                })->values(),
            ];
        })->values()->all();
    }
}
