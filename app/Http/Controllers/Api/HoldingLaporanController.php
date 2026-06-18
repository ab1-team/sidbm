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
     * Hitung saldo satu rekening dengan special case 3.2.02.01 = laba_rugi.
     * Sumber kebenaran: Keuangan::komSaldo (dipakai semua view tenant).
     */
    private function saldoRekening($rek, Keuangan $keuangan, string $tglKondisi): float
    {
        $saldo = $keuangan->komSaldo($rek);
        if ($rek->kode_akun === '3.2.02.01') {
            $saldo = $keuangan->laba_rugi($tglKondisi);
        }
        return (float) $saldo;
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

        $keuangan = new Keuangan;
        $akun1 = AkunLevel1::where('lev1', '<=', '3')->with([
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

        $payload = $akun1->map(function ($a1) use ($keuangan, $data) {
            $sumAkun1 = 0.0;
            $akun2 = $a1->akun2->map(function ($a2) use ($keuangan, $data, &$sumAkun1) {
                $akun3 = $a2->akun3->map(function ($a3) use ($keuangan, $data, &$sumAkun1) {
                    $sumSaldo = 0.0;
                    // Hitung per-rekening untuk kalkulasi internal (special case 3.2.02.01),
                    // tapi tidak expose 'rekening' di output — view neraca tidak menampilkannya.
                    $a3->rek->each(function ($r) use ($keuangan, $data, &$sumSaldo) {
                        $sumSaldo += $this->saldoRekening($r, $keuangan, $data['tgl_kondisi']);
                    });
                    return [
                        'kode_akun' => $a3->kode_akun,
                        'nama_akun' => $a3->nama_akun,
                        'saldo'     => $sumSaldo,
                    ];
                })->values();
                $sumAkun3 = $akun3->sum('saldo');
                $sumAkun1 += $sumAkun3;
                return [
                    'kode_akun' => $a2->kode_akun,
                    'nama_akun' => $a2->nama_akun,
                    'saldo'     => $sumAkun3,
                    'akun3'     => $akun3,
                ];
            })->values();
            return [
                'kode_akun' => $a1->kode_akun,
                'nama_akun' => $a1->nama_akun,
                'lev1'      => $a1->lev1,
                'saldo'     => $sumAkun1,
                'akun2'     => $akun2,
            ];
        })->values();

        // Ringkasan untuk render baris "Jumlah Aset" / "Jumlah Liabilitas + Ekuitas"
        // (sama dengan view tenant: lev1 == '1' → debit, else → kredit)
        $totalAset = (float) $payload->where('lev1', '1')->sum('saldo');
        $totalLiabEkuitas = (float) $payload->where('lev1', '!=', '1')->sum('saldo');

        return response()->json([
            'success'      => true,
            'laporan'      => 'Neraca',
            'kecamatan'    => $kec->nama_kec,
            'tgl_kondisi'  => $data['tgl_kondisi'],
            'sub_judul'    => 'Per '.$data['hari'].' '.Tanggal::namaBulan($data['tgl_kondisi']).' '.Tanggal::tahun($data['tgl_kondisi']),
            'ringkasan'    => [
                'total_aset'              => $totalAset,
                'total_liabilitas_ekuitas'=> $totalLiabEkuitas,
                'selisih'                 => $totalAset - $totalLiabEkuitas,
            ],
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

        // Helper: flatten sum + keep struktur per-rekening
        $flatten = function (array $bagian) {
            $rows = [];
            $total = 0.0;
            $totalLalu = 0.0;
            foreach ($bagian as $group) {
                $groupTotal = 0.0;
                $groupTotalLalu = 0.0;
                $rekening = [];
                foreach ($group['rek'] ?? [] as $kode => $r) {
                    $saldo = (float) ($r['saldo'] ?? 0);
                    $saldoLalu = (float) ($r['saldo_bln_lalu'] ?? 0);
                    $groupTotal += $saldo;
                    $groupTotalLalu += $saldoLalu;
                    $rekening[] = [
                        'kode_akun'        => $r['kode_akun'] ?? $kode,
                        'nama_akun'        => $r['nama_akun'] ?? '',
                        'saldo_bln_lalu'   => $saldoLalu,
                        'saldo_periode_ini'=> $saldo - $saldoLalu,
                        'saldo'            => $saldo,
                    ];
                }
                $total += $groupTotal;
                $totalLalu += $groupTotalLalu;
                $rows[] = [
                    'kode_akun'         => $group['kode_akun'] ?? '',
                    'nama_akun'         => $group['nama_akun'] ?? '',
                    'saldo_bln_lalu'    => $groupTotalLalu,
                    'saldo_periode_ini' => $groupTotal - $groupTotalLalu,
                    'saldo'             => $groupTotal,
                    'rekening'          => $rekening,
                ];
            }
            return ['rows' => $rows, 'total' => $total, 'total_lalu' => $totalLalu];
        };

        $pendapatan     = $flatten($lr['pendapatan']      ?? []);
        $beban          = $flatten($lr['beban']           ?? []);
        $pendapatanNOP  = $flatten($lr['pendapatan_non_ops'] ?? []);
        $bebanNOP       = $flatten($lr['beban_non_ops']   ?? []);

        // A. Laba Rugi Operasional (4.1 - 5.1 - 5.2) = Pendapatan - Beban
        $lrOperasional_sdLalu     = $pendapatan['total_lalu'] - $beban['total_lalu'];
        $lrOperasional_periodeIni = ($pendapatan['total'] - $pendapatan['total_lalu']) - ($beban['total'] - $beban['total_lalu']);
        $lrOperasional_total      = $pendapatan['total']      - $beban['total'];

        // B. Laba Rugi Non Operasional (4.2/4.3 - 5.3) = PendapatanNOP - BebanNOP
        $lrNonOp_sdLalu     = $pendapatanNOP['total_lalu'] - $bebanNOP['total_lalu'];
        $lrNonOp_periodeIni = ($pendapatanNOP['total'] - $pendapatanNOP['total_lalu']) - ($bebanNOP['total'] - $bebanNOP['total_lalu']);
        $lrNonOp_total      = $pendapatanNOP['total']      - $bebanNOP['total'];

        // C. Sebelum Pajak
        $sebelumPajak_sdLalu     = $lrOperasional_sdLalu     + $lrNonOp_sdLalu;
        $sebelumPajak_periodeIni = $lrOperasional_periodeIni + $lrNonOp_periodeIni;
        $sebelumPajak_total      = $lrOperasional_total      + $lrNonOp_total;

        // PPh
        $pph_bulan_lalu       = (float) ($pph['bulan_lalu'] ?? 0);
        $pph_sekarang         = (float) ($pph['bulan_ini'] ?? 0);
        $pph_periode_ini      = $pph_sekarang - $pph_bulan_lalu;

        // C. Setelah Pajak
        $setelahPajak_sdLalu     = $sebelumPajak_sdLalu     - $pph_bulan_lalu;
        $setelahPajak_periodeIni = $sebelumPajak_periodeIni - $pph_periode_ini;
        $setelahPajak_total      = $sebelumPajak_total      - $pph_sekarang;

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
            'ringkasan' => [
                'pendapatan'         => $pendapatan['total'],
                'beban'              => $beban['total'],
                'pendapatan_non_ops' => $pendapatanNOP['total'],
                'beban_non_ops'      => $bebanNOP['total'],
                'lr_operasional'     => [
                    's_d_bulan_lalu'   => $lrOperasional_sdLalu,
                    'periode_ini'      => $lrOperasional_periodeIni,
                    's_d_sekarang'     => $lrOperasional_total,
                ],
                'lr_non_operasional' => [
                    's_d_bulan_lalu'   => $lrNonOp_sdLalu,
                    'periode_ini'      => $lrNonOp_periodeIni,
                    's_d_sekarang'     => $lrNonOp_total,
                ],
                'sebelum_pajak'      => [
                    's_d_bulan_lalu'   => $sebelumPajak_sdLalu,
                    'periode_ini'      => $sebelumPajak_periodeIni,
                    's_d_sekarang'     => $sebelumPajak_total,
                ],
                'pph'                => [
                    's_d_bulan_lalu'   => $pph_bulan_lalu,
                    'periode_ini'      => $pph_periode_ini,
                    's_d_sekarang'     => $pph_sekarang,
                ],
                'setelah_pajak'      => [
                    's_d_bulan_lalu'   => $setelahPajak_sdLalu,
                    'periode_ini'      => $setelahPajak_periodeIni,
                    's_d_sekarang'     => $setelahPajak_total,
                ],
            ],
            'data' => [
                'pendapatan'           => $pendapatan['rows'],
                'beban'                => $beban['rows'],
                'pendapatan_non_ops'   => $pendapatanNOP['rows'],
                'beban_non_ops'        => $bebanNOP['rows'],
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
        $saldoBulanLalu = (float) $keuangan->saldoKas($tgl_lalu);

        $saldoAwalLabel = 'Saldo Awal ' . ($awal === 'BULAN' ? 'Bulan' : 'Tahun');

        // Build struktur: parent (Operasi/Investasi/Pendanaan) → group → child rows.
        // Samakan dengan view tenant yang hitung `array_saldo[]` per group utama
        // (group_index 0..6) untuk "Jumlah Aktivitas Operasi/Investasi/Pendanaan".
        $rows = [];
        $groupTotals = []; // urut penemuan group utama (id 22/52/66 = akhir Operasi/Investasi/Pendanaan)
        $groupIdx = 0;
        $currentGroupTotal = 0.0;
        $currentGroupName = null;

        foreach ($arusKas as $ak) {
            $parent = $ak->parent ?: ($ak->id == 1 ? 'saldo_awal' : null);
            $isSaldoAwal = (int) $ak->id === 1;

            // Hitung child rows
            $childRows = [];
            $akTotal = 0.0;
            foreach ($ak->child as $child) {
                $saldo = (float) $keuangan->arus_kas($child->rekening, $data['tgl_kondisi'], $jenis);
                $akTotal += $saldo;
                $childRows[] = [
                    'id'       => (int) $child->id,
                    'kode_akun'=> $child->kode_akun ?? null,
                    'nama_akun'=> $child->nama_akun ?? $child->nama,
                    'saldo'    => $saldo,
                ];
            }
            // Item saldo awal langsung di parent (id=1), tidak punya child
            if ($isSaldoAwal) {
                $akTotal = $saldoBulanLalu;
            }

            // Akumulasi total group: view tenant simpan $j_saldo per group utama,
            // lalu di-reset setelah lewat id 1, 16, 46, 61.
            if (in_array((int) $ak->id, [1, 16, 46, 61], true)) {
                // Simpan group sebelumnya
                if ($currentGroupName !== null) {
                    $groupTotals[] = [
                        'nama'  => $currentGroupName,
                        'saldo' => $currentGroupTotal,
                    ];
                }
                $currentGroupTotal = 0.0;
                $currentGroupName = $ak->nama_akun;
            } else {
                $currentGroupTotal += $akTotal;
            }

            $rows[] = [
                'id'       => (int) $ak->id,
                'parent'   => $parent,
                'kategori' => $ak->kategori ?? null,
                'nama'     => $isSaldoAwal ? $saldoAwalLabel : $ak->nama_akun,
                'sub'      => (int) $ak->sub,
                'saldo'    => $akTotal,
                'detail'   => $childRows,
            ];
        }
        // Simpan group terakhir
        if ($currentGroupName !== null) {
            $groupTotals[] = [
                'nama'  => $currentGroupName,
                'saldo' => $currentGroupTotal,
            ];
        }

        // Hitung total masuk/keluar (exclude saldo awal, sesuai view tenant)
        $totalMasuk  = (float) collect($rows)->where('parent', 'masuk')->sum('saldo');
        $totalKeluar = (float) collect($rows)->where('parent', 'keluar')->sum('saldo');

        // Hitung Kas Bersih per aktivitas (mengikuti id penanda 22/52/66 di view tenant)
        // Index groupTotals berurut: 0=Operasi, 1=Investasi, 2=Pendanaan
        $kasOperasi    = isset($groupTotals[0]) && isset($groupTotals[1]) && isset($groupTotals[2])
            ? $groupTotals[0]['saldo'] - ($groupTotals[1]['saldo'] + $groupTotals[2]['saldo'])
            : 0.0;
        $kasInvestasi  = isset($groupTotals[3]) && isset($groupTotals[4])
            ? $groupTotals[3]['saldo'] - $groupTotals[4]['saldo']
            : 0.0;
        $kasPendanaan  = isset($groupTotals[5]) && isset($groupTotals[6])
            ? $groupTotals[5]['saldo'] - $groupTotals[6]['saldo']
            : 0.0;
        $kenaikan      = $kasOperasi + $kasInvestasi + $kasPendanaan;
        $saldoAkhir    = $kenaikan + $saldoBulanLalu;

        return response()->json([
            'success'   => true,
            'laporan'   => 'Arus Kas',
            'kecamatan' => $kec->nama_kec,
            'periode'   => [
                'jenis'        => $jenis,
                'tgl_kondisi'  => $data['tgl_kondisi'],
                'sub_judul'    => $data['sub_judul'],
            ],
            'ringkasan' => [
                'saldo_awal'         => $saldoBulanLalu,
                'total_masuk'        => $totalMasuk,
                'total_keluar'       => $totalKeluar,
                'kas_operasi'        => $kasOperasi,
                'kas_investasi'      => $kasInvestasi,
                'kas_pendanaan'      => $kasPendanaan,
                'kenaikan_penurunan' => $kenaikan,
                'saldo_akhir'        => $saldoAkhir,
                'group'              => $groupTotals,
            ],
            'data' => $rows,
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

        $rows = $rekening->map(function ($r) use ($keuangan, $data) {
            $saldo = $this->saldoRekening($r, $keuangan, $data['tgl_kondisi']);
            $saldoAwal = (float) $keuangan->komSaldoAwal($r);
            return [
                'kode_akun'  => $r->kode_akun,
                'nama_akun'  => $r->nama_akun,
                'saldo_awal' => $saldoAwal,
                'saldo_akhir'=> $saldo,
                'mutasi'     => $saldo - $saldoAwal,
            ];
        });

        $ekuitasAwal  = (float) $rows->sum('saldo_awal');
        $ekuitasAkhir = (float) $rows->sum('saldo_akhir');

        // Laba rugi = selisih akhir - awal - mutasi
        $labaRugiBersih = $ekuitasAkhir - $ekuitasAwal - (float) $rows->sum('mutasi');

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
            'ringkasan' => [
                'ekuitas_awal'   => $ekuitasAwal,
                'setoran'        => (float) $rows->where('kode_akun', '3.2.01.01')->sum('mutasi'),
                'penarikan'      => (float) $rows->where('kode_akun', '3.2.01.02')->sum('mutasi'),
                'dividen'        => (float) $rows->where('kode_akun', '3.2.01.03')->sum('mutasi'),
                'koreksi'        => (float) $rows->where('kode_akun', '3.2.02.01')->sum('mutasi'),
                'laba_rugi'      => $labaRugiBersih,
                'ekuitas_akhir'  => $ekuitasAkhir,
            ],
            'data' => $rows->values(),
        ]);
    }

    /**
     * GET /api/v1/holding/laporan/calk
     * Query: tahun, bulan?, hari?
     *
     * Bagian C: struktur akun1 (lev1..akun2..akun3..rekening) lengkap seperti
     * neraca, dengan special case 3.2.02.01 = laba_rugi. Sama dengan view tenant
     * `pelaporan.view.calk` (line 230-306).
     */
    public function calk(Request $request): JsonResponse
    {
        $data = $this->validatePeriode($request);
        $kec = $this->kecamatan($request);
        $data['kec'] = $kec;

        $keuangan = new Keuangan;

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

        $calkJson = json_decode($kec->calk, true);
        $pointA = $calkJson['calk']['A'] ?? '';

        // Bagian C: struktur akun1..akun2..akun3..rekening (sama persis view tenant line 230-306)
        $bagianC = $akun1->map(function ($a1) use ($keuangan, $data) {
            $sumAkun1 = 0.0;
            $akun2 = $a1->akun2->map(function ($a2) use ($keuangan, $data, &$sumAkun1) {
                $akun3 = $a2->akun3->map(function ($a3) use ($keuangan, $data, &$sumAkun1) {
                    $sumSaldo = 0.0;
                    $rekening = $a3->rek->map(function ($r) use ($keuangan, $data, &$sumSaldo) {
                        $saldo = $this->saldoRekening($r, $keuangan, $data['tgl_kondisi']);
                        $sumSaldo += $saldo;
                        return [
                            'kode_akun' => $r->kode_akun,
                            'nama_akun' => $r->nama_akun,
                            'saldo'     => $saldo,
                        ];
                    })->values();
                    return [
                        'kode_akun' => $a3->kode_akun,
                        'nama_akun' => $a3->nama_akun,
                        'saldo'     => $sumSaldo,
                        'rekening'  => $rekening,
                    ];
                })->values();
                $sumAkun3 = $akun3->sum('saldo');
                $sumAkun1 += $sumAkun3;
                return [
                    'kode_akun' => $a2->kode_akun,
                    'nama_akun' => $a2->nama_akun,
                    'saldo'     => $sumAkun3,
                    'akun3'     => $akun3,
                ];
            })->values();
            return [
                'kode_akun' => $a1->kode_akun,
                'nama_akun' => $a1->nama_akun,
                'lev1'      => $a1->lev1,
                'saldo'     => $sumAkun1,
                'akun2'     => $akun2,
            ];
        })->values();

        // Ringkasan bagian C (sama dengan "Jumlah Aset" / "Jumlah Liabilitas + Ekuitas" di view)
        $totalAset = (float) $bagianC->where('lev1', '1')->sum('saldo');
        $totalLiabEkuitas = (float) $bagianC->where('lev1', '!=', '1')->sum('saldo');

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
            'ringkasan' => [
                'point_a'                 => $pointA,
                'total_aset'              => $totalAset,
                'total_liabilitas_ekuitas'=> $totalLiabEkuitas,
                'selisih'                 => $totalAset - $totalLiabEkuitas,
            ],
            'data' => [
                'point_a'         => $pointA,
                'catatan'         => $keterangan->catatan ?? null,
                'rincian_akun'    => $bagianC,
                'saldo_calk'      => $saldoCalk,
                'penandatangan'   => $penandatangan,
            ],
        ]);
    }
}
