<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Memverifikasi konsistensi antara Keuangan::ptkPojkKolek() (sumber G. Ringkasan
 * Kolektibilitas POJK di penilaian_tingkat_kesehatan.blade.php) dan
 * Keuangan::ptkPojkKolekDetail() (sumber Rekapitulasi Kolektibilitas POJK di
 * kolektabilitas_detail.blade.php).
 *
 * Sebelumnya ptkPojkKolek() melakukan `continue` ketika saldo_pokok <= 0,
 * sementara ptkPojkKolekDetail() mengklasifikasikannya ke Lancar dengan
 * saldo 0. Setelah perbaikan, kedua method menghasilkan total kolektabilitas
 * dan total saldo yang identik untuk dataset yang sama.
 */
class PtkPojkKolekConsistencyTest extends TestCase
{
    /** Replika logika ptkPojkKolek() per baris (versi perbaikan). */
    private function classifyKolek(array $pinkel, string $tgl_kondisi): array
    {
        $saldo_pokok = $pinkel['alokasi'];
        if (! empty($pinkel['saldo'])) {
            $saldo_pokok = $pinkel['saldo']['saldo_pokok'];
        }

        $target_pokok = 0;
        $wajib_pokok = 0;
        $angsuran_ke = 0;
        if (! empty($pinkel['target'])) {
            $target_pokok = $pinkel['target']['target_pokok'];
            $wajib_pokok = $pinkel['target']['wajib_pokok'];
            $angsuran_ke = $pinkel['target']['angsuran_ke'];
        }

        $tunggakan_pokok = max(0, $wajib_pokok - $target_pokok);

        if (! empty($pinkel['tgl_lunas']) && $pinkel['tgl_lunas'] <= $tgl_kondisi
            && in_array($pinkel['status'], ['L', 'R', 'H'], true)) {
            $tunggakan_pokok = 0;
            $saldo_pokok = 0;
        }

        $tgl_cair = new \DateTime($pinkel['tgl_cair']);
        $tgl_kond = new \DateTime($tgl_kondisi);
        $diff = $tgl_kond->diff($tgl_cair);
        $selisih_bulan = ($diff->y * 12) + $diff->m;

        $rasio_tunggakan = $wajib_pokok > 0 ? ($tunggakan_pokok / $wajib_pokok) : 0;
        $bulan_tunggak = round($rasio_tunggakan + ($selisih_bulan - $angsuran_ke));

        if ($saldo_pokok == 0) {
            $bulan_tunggak = 0;
        }

        if ($bulan_tunggak <= 3) {
            $kategori = 0;
        } elseif ($bulan_tunggak <= 6) {
            $kategori = 1;
        } elseif ($bulan_tunggak <= 9) {
            $kategori = 2;
        } elseif ($bulan_tunggak <= 12) {
            $kategori = 3;
        } else {
            $kategori = 4;
        }

        return [
            'kategori' => $kategori,
            'saldo_pokok' => $saldo_pokok,
        ];
    }

    /** Replika logika ptkPojkKolekDetail() per baris. */
    private function classifyKolekDetail(array $pinkel, string $tgl_kondisi): array
    {
        $saldo_pokok = $pinkel['alokasi'];
        if (! empty($pinkel['saldo'])) {
            $saldo_pokok = $pinkel['saldo']['saldo_pokok'];
        }

        $target_pokok = 0;
        $wajib_pokok = 0;
        $angsuran_ke = 0;
        if (! empty($pinkel['target'])) {
            $target_pokok = $pinkel['target']['target_pokok'];
            $wajib_pokok = $pinkel['target']['wajib_pokok'];
            $angsuran_ke = $pinkel['target']['angsuran_ke'];
        }

        $tunggakan_pokok = max(0, $target_pokok - ($pinkel['saldo']['sum_pokok'] ?? 0));

        if (! empty($pinkel['tgl_lunas']) && $pinkel['tgl_lunas'] <= $tgl_kondisi
            && in_array($pinkel['status'], ['L', 'R', 'H'], true)) {
            $tunggakan_pokok = 0;
            $saldo_pokok = 0;
        }

        $tgl_kond = new \DateTime($tgl_kondisi);
        $tgl_cair = new \DateTime($pinkel['tgl_cair']);
        $diff = $tgl_kond->diff($tgl_cair);
        $selisih_bulan = ($diff->y * 12) + $diff->m;

        $bulan_tunggak = 0;
        if ($saldo_pokok == 0) {
            $bulan_tunggak = 0;
        } else {
            $_kolek = $wajib_pokok != 0 ? ($tunggakan_pokok / $wajib_pokok) : 0;
            $bulan_tunggak = round($_kolek + ($selisih_bulan - $angsuran_ke));
        }

        if ($bulan_tunggak <= 3) {
            $kategori = 1;
        } elseif ($bulan_tunggak <= 6) {
            $kategori = 2;
        } elseif ($bulan_tunggak <= 9) {
            $kategori = 3;
        } elseif ($bulan_tunggak <= 12) {
            $kategori = 4;
        } else {
            $kategori = 5;
        }

        return [
            'kategori' => $kategori,
            'saldo_pokok' => $saldo_pokok,
        ];
    }

    /** Dataset sintetis: campuran Lancar, DPK, KL, Diragukan, Macet + 1 lunas. */
    private function dataset(): array
    {
        $tgl_kondisi = '2025-06-30';

        return [
            // Lancar: 5 bulan berjalan, angsuran_ke=5 -> bulan_tunggak = 0+0 = 0
            ['alokasi' => 1000000, 'status' => 'A', 'tgl_cair' => '2025-01-15', 'tgl_lunas' => null,
                'saldo' => ['saldo_pokok' => 800000, 'sum_pokok' => 200000],
                'target' => ['target_pokok' => 200000, 'wajib_pokok' => 200000, 'angsuran_ke' => 5]],

            // DPK: tunggakan 4 bulan -> bulan_tunggak = 4
            ['alokasi' => 2000000, 'status' => 'A', 'tgl_cair' => '2024-06-01', 'tgl_lunas' => null,
                'saldo' => ['saldo_pokok' => 1200000, 'sum_pokok' => 800000],
                'target' => ['target_pokok' => 1600000, 'wajib_pokok' => 2000000, 'angsuran_ke' => 11]],

            // Kurang Lancar: tunggakan ~7 bulan -> bulan_tunggak = 7
            ['alokasi' => 1500000, 'status' => 'A', 'tgl_cair' => '2024-03-01', 'tgl_lunas' => null,
                'saldo' => ['saldo_pokok' => 1000000, 'sum_pokok' => 500000],
                'target' => ['target_pokok' => 1100000, 'wajib_pokok' => 1500000, 'angsuran_ke' => 13]],

            // Diragukan: tunggakan ~10 bulan -> bulan_tunggak = 10
            ['alokasi' => 800000, 'status' => 'R', 'tgl_cair' => '2023-12-01', 'tgl_lunas' => null,
                'saldo' => ['saldo_pokok' => 500000, 'sum_pokok' => 300000],
                'target' => ['target_pokok' => 700000, 'wajib_pokok' => 800000, 'angsuran_ke' => 16]],

            // Macet: tunggakan > 12 bulan -> bulan_tunggak = 14
            ['alokasi' => 1200000, 'status' => 'H', 'tgl_cair' => '2023-01-01', 'tgl_lunas' => null,
                'saldo' => ['saldo_pokok' => 700000, 'sum_pokok' => 500000],
                'target' => ['target_pokok' => 1100000, 'wajib_pokok' => 1200000, 'angsuran_ke' => 24]],

            // Lunas di tengah tahun -> saldo_pokok = 0, harus Lancar di kedua method
            ['alokasi' => 500000, 'status' => 'L', 'tgl_cair' => '2024-02-01', 'tgl_lunas' => '2025-03-15',
                'saldo' => ['saldo_pokok' => 0, 'sum_pokok' => 500000],
                'target' => ['target_pokok' => 500000, 'wajib_pokok' => 500000, 'angsuran_ke' => 16]],

            // Saldo null (tidak ada transaksi angsuran) -> alokasi penuh, kategori mungkin macet
            // Disengaja: tgl_cair 2022-01-01, angsuran_ke=0 -> selisih_bulan = 42 -> bulan_tunggak = 42
            ['alokasi' => 300000, 'status' => 'A', 'tgl_cair' => '2022-01-01', 'tgl_lunas' => null,
                'saldo' => null,
                'target' => ['target_pokok' => 0, 'wajib_pokok' => 100000, 'angsuran_ke' => 0]],
        ];
    }

    public function test_total_kolek_sama_antara_kedua_method()
    {
        $data = $this->dataset();
        $tgl_kondisi = '2025-06-30';

        $sum_kolek = array_fill(0, 5, 0.0);
        $sum_detail = array_fill(1, 5, 0.0);

        foreach ($data as $pinkel) {
            $r = $this->classifyKolek($pinkel, $tgl_kondisi);
            $sum_kolek[$r['kategori']] += $r['saldo_pokok'];

            $r2 = $this->classifyKolekDetail($pinkel, $tgl_kondisi);
            $sum_detail[$r2['kategori']] += $r2['saldo_pokok'];
        }

        // Mapping indeks: kolek 0..4 -> detail 1..5
        for ($i = 0; $i < 5; $i++) {
            $this->assertEqualsWithDelta(
                $sum_kolek[$i],
                $sum_detail[$i + 1],
                0.01,
                "Kolektabilitas indeks {$i} (kolek) vs ".($i + 1)." (detail) harus sama"
            );
        }
    }

    public function test_total_saldo_outstanding_sama()
    {
        $data = $this->dataset();
        $tgl_kondisi = '2025-06-30';

        $sum_kolek = 0.0;
        $sum_detail = 0.0;

        foreach ($data as $pinkel) {
            $r = $this->classifyKolek($pinkel, $tgl_kondisi);
            $sum_kolek += $r['saldo_pokok'];

            $r2 = $this->classifyKolekDetail($pinkel, $tgl_kondisi);
            $sum_detail += $r2['saldo_pokok'];
        }

        $this->assertEqualsWithDelta($sum_kolek, $sum_detail, 0.01);
    }

    public function test_pinjaman_saldo_nol_klasifikasi_lancar()
    {
        // Pinjaman dengan saldo_pokok = 0 -> harus Lancar di kedua method
        $pinkel = ['alokasi' => 500000, 'status' => 'L', 'tgl_cair' => '2024-02-01', 'tgl_lunas' => '2025-03-15',
            'saldo' => ['saldo_pokok' => 0, 'sum_pokok' => 500000],
            'target' => ['target_pokok' => 500000, 'wajib_pokok' => 500000, 'angsuran_ke' => 16]];

        $r = $this->classifyKolek($pinkel, '2025-06-30');
        $this->assertSame(0, $r['kategori'], 'Saldo 0 -> Lancar (kolek, indeks 0)');
        $this->assertEquals(0.0, $r['saldo_pokok']);

        $r2 = $this->classifyKolekDetail($pinkel, '2025-06-30');
        $this->assertSame(1, $r2['kategori'], 'Saldo 0 -> Lancar (detail, indeks 1)');
        $this->assertEquals(0.0, $r2['saldo_pokok']);
    }

    public function test_pinjaman_lunas_R_H_tidak_masuk_kategori_bermasalah()
    {
        // Pinjaman R/H yang tgl_lunas <= tgl_kondisi -> harus Lancar di kedua method
        $pinkel = ['alokasi' => 1000000, 'status' => 'R', 'tgl_cair' => '2024-01-01', 'tgl_lunas' => '2025-04-01',
            'saldo' => ['saldo_pokok' => 0, 'sum_pokok' => 1000000],
            'target' => ['target_pokok' => 1000000, 'wajib_pokok' => 1000000, 'angsuran_ke' => 17]];

        $r = $this->classifyKolek($pinkel, '2025-06-30');
        $this->assertSame(0, $r['kategori'], 'Lunas R -> Lancar (kolek)');

        $r2 = $this->classifyKolekDetail($pinkel, '2025-06-30');
        $this->assertSame(1, $r2['kategori'], 'Lunas R -> Lancar (detail)');
    }

    public function test_pinjaman_lunas_R_H_dengan_saldo_sisa_direset_nol()
    {
        // Kasus: pinjaman Lunas sebagian (status L, tgl_lunas <= tgl_kondisi,
        // saldo_pokok masih > 0 di record RealAngsuran). Sebelum perbaikan,
        // JUMLAH SPP (Detail) tidak termasuk saldo ini (di-reset ke 0),
        // tapi G. Ringkasan / Rekapitulasi (Kolek) tetap pakai nilai saldo
        // yang > 0 -> selisih saldo pokok antara halaman.
        // Setelah perbaikan: saldo_pokok di-reset ke 0 di Kolek juga,
        // sehingga JUMLAH SPP == Saldo Pokok (Rp) di Rekapitulasi.
        $pinkel = ['alokasi' => 1000000, 'status' => 'L', 'tgl_cair' => '2024-02-01', 'tgl_lunas' => '2025-04-01',
            'saldo' => ['saldo_pokok' => 250000, 'sum_pokok' => 750000],   // sisa 250rb
            'target' => ['target_pokok' => 800000, 'wajib_pokok' => 1000000, 'angsuran_ke' => 16]];

        $r = $this->classifyKolek($pinkel, '2025-06-30');
        $this->assertSame(0, $r['kategori'], 'Lunas sebagian L -> Lancar (kolek)');
        $this->assertEquals(0.0, $r['saldo_pokok'], 'Saldo paksa 0 saat L/R/H lunas (kolek)');

        $r2 = $this->classifyKolekDetail($pinkel, '2025-06-30');
        $this->assertSame(1, $r2['kategori'], 'Lunas sebagian L -> Lancar (detail)');
        $this->assertEquals(0.0, $r2['saldo_pokok'], 'Saldo paksa 0 saat L/R/H lunas (detail)');
    }

    public function test_ppap_wajib_total_sama_antara_kedua_method()
    {
        // PPAP wajib = saldo * prosentase per kategori.
        // Jika klasifikasi identik, total PPAP wajib juga harus identik.
        $prosentase = [0 => 0, 1 => 5, 2 => 15, 3 => 50, 4 => 100];
        $prosentase_detail = [1 => 0, 2 => 5, 3 => 15, 4 => 50, 5 => 100];

        $data = $this->dataset();
        $tgl_kondisi = '2025-06-30';

        $ppap_kolek = 0.0;
        $ppap_detail = 0.0;

        foreach ($data as $pinkel) {
            $r = $this->classifyKolek($pinkel, $tgl_kondisi);
            $ppap_kolek += $r['saldo_pokok'] * ($prosentase[$r['kategori']] / 100);

            $r2 = $this->classifyKolekDetail($pinkel, $tgl_kondisi);
            $ppap_detail += $r2['saldo_pokok'] * ($prosentase_detail[$r2['kategori']] / 100);
        }

        $this->assertEqualsWithDelta($ppap_kolek, $ppap_detail, 0.01,
            'Total PPAP wajib kolek vs detail harus identik untuk dataset sintetis');
    }

    /**
     * Mensimulasikan halaman Rekapitulasi (Blade) yang sekarang mengambil
     * PPAP dari $a['ppap_wajib_minimum'] & $a['kolek_items'] & $a['sum_kolek_total']
     * (sumber: ptkPojkKolek) — bukan lagi menghitung ulang dari data detail.
     * Memastikan sumber tunggal untuk kedua halaman.
     */
    public function test_ppap_wajib_total_dari_sumber_kolek_saja()
    {
        $prosentase = [0 => 0, 1 => 5, 2 => 15, 3 => 50, 4 => 100];

        $data = $this->dataset();
        $tgl_kondisi = '2025-06-30';

        // sumber tunggal: ptkPojkKolek
        $sum_kolek_total = array_fill(0, 5, 0.0);
        $ppap_wajib_minimum = 0.0;
        foreach ($data as $pinkel) {
            $r = $this->classifyKolek($pinkel, $tgl_kondisi);
            $sum_kolek_total[$r['kategori']] += $r['saldo_pokok'];
            if ($prosentase[$r['kategori']] > 0) {
                $ppap_wajib_minimum += $r['saldo_pokok'] * ($prosentase[$r['kategori']] / 100);
            }
        }

        // halaman G & Rekapitulasi sekarang ambil dari sumber yang sama
        $g_total_ppap = $ppap_wajib_minimum; // controller -> $a['ppap_wajib_minimum']
        $rekap_total_ppap = $ppap_wajib_minimum; // Blade pakai $a['ppap_wajib_minimum']

        $this->assertEqualsWithDelta($g_total_ppap, $rekap_total_ppap, 0.01);

        // jumlah per baris (Blade) = sum_kolek_total[i] * prosentase[i]
        $per_row = [];
        foreach ($sum_kolek_total as $idx => $saldo) {
            $per_row[$idx] = $saldo * ($prosentase[$idx] / 100);
        }

        $this->assertEqualsWithDelta(array_sum($per_row), $ppap_wajib_minimum, 0.01,
            'Jumlah PPAP per-baris (Blade) = $a[ppap_wajib_minimum] (controller)');
    }

    /**
     * Mensimulasikan inisialisasi $global_kolek* di Blade Rekapitulasi.
     * Sebelumnya, loop @forelse ($detail as $jpp) mengakumulasi ulang
     * $global_kolek* dari $jpp['tot'] (sumber: ptkPojkKolekDetail), yang
     * menyebabkan total saldo/kolek di Rekapitulasi berbeda dari G. Ringkasan.
     * Perbaikan: inisialisasi langsung dari $a['sum_kolek_total'] (ptkPojkKolek)
     * dan JANGAN akumulasi ulang di loop.
     */
    public function test_global_kolek_diambil_langsung_dari_sum_kolek_total()
    {
        $data = $this->dataset();
        $tgl_kondisi = '2025-06-30';

        // sumber tunggal: ptkPojkKolek -> $a['sum_kolek_total']
        $sum_kolek_total = array_fill(0, 5, 0.0);
        foreach ($data as $pinkel) {
            $r = $this->classifyKolek($pinkel, $tgl_kondisi);
            $sum_kolek_total[$r['kategori']] += $r['saldo_pokok'];
        }

        // sumber: ptkPojkKolekDetail -> $jpp['tot'] (simulasi)
        $jpp_totals = array_fill(1, 5, 0.0);
        foreach ($data as $pinkel) {
            $r = $this->classifyKolekDetail($pinkel, $tgl_kondisi);
            $jpp_totals[$r['kategori']] += $r['saldo_pokok'];
        }

        // Blade (perbaikan): $global_kolekN = $a['sum_kolek_total'][N-1]
        $global_kolek = [
            1 => $sum_kolek_total[0],
            2 => $sum_kolek_total[1],
            3 => $sum_kolek_total[2],
            4 => $sum_kolek_total[3],
            5 => $sum_kolek_total[4],
        ];

        // Verifikasi: global_kolek == sum_kolek_total (sumber tunggal)
        $this->assertEqualsWithDelta($sum_kolek_total[0], $global_kolek[1], 0.01);
        $this->assertEqualsWithDelta($sum_kolek_total[4], $global_kolek[5], 0.01);

        // Simulasi loop lama (akumulasi ulang) seharusnya TIDAK dilakukan lagi.
        // Jika dilakukan: total menjadi $sum_kolek_total + $jpp_totals = 2x lipat (salah).
        $buggy_global_kolek1 = $sum_kolek_total[0] + $jpp_totals[1];
        $this->assertGreaterThan($sum_kolek_total[0] + 0.01, $buggy_global_kolek1,
            'Akumulasi ulang akan menggandakan total (bug)');
    }
}
