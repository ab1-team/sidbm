<?php

namespace Tests\Unit;

use App\Services\GenerateService;
use App\Utils\Keuangan;
use PHPUnit\Framework\TestCase;

/**
 * Bukti EFEK ANGKA jadwal ANUITAS (jenis_jasa = 3).
 *
 * KONTRAK ANUITAS (paritas legacy `/root/workspace/lkm` GenerateController +
 * PinjamanKelompokController):
 *   1. Angsuran TOTAL (pokok + jasa) tetap setiap periode; jasa menurun,
 *      pokok naik.
 *   2. Suku bunga periodik i = (pros_jasa / 100) / jangka — pros_jasa legacy
 *      adalah PERSENTASE TOTAL sepanjang tenor, bukan per bulan.
 *   3. PMT = A*i / (1 - (1+i)^-n), dibulatkan lewat Keuangan::pembulatan.
 *   4. Jasa periode ke-k = pembulatan(saldo_pokok_awal * i).
 *   5. Pokok periode terakhir = sisa saldo (amortisasi penutup), sehingga
 *      Σ pokok == alokasi PERSIS.
 *   6. Interval `sistem_angsuran` / `sa_jasa` TIDAK dipakai untuk anuitas —
 *      anuitas selalu dipecah per bulan sebanyak $jangka periode (legacy
 *      berperilaku sama).
 *
 * Kasus acuan nyata: BUMDesma Mojogedang (kecamatan 357), loan 5835,
 * alokasi Rp 850.000.000, pros_jasa 2,8%, jangka 4 bulan, sistem_angsuran 3.
 * Jadwal yang SALAH (bug lama): jasa flat 4 x Rp 5.950.000 dan pokok
 * menumpuk di bulan ke-4 (Rp 850.000.000) — itu rumus FLAT, bukan anuitas.
 */
class RencanaAnuitasTest extends TestCase
{
    private function service(): GenerateService
    {
        return (new \ReflectionClass(GenerateService::class))->newInstanceWithoutConstructor();
    }

    private function call(string $method, array $args)
    {
        $svc = $this->service();

        return \Closure::bind(fn () => $this->{$method}(...$args), $svc, GenerateService::class)();
    }

    /** Dummy $pinkel minimal untuk rencana_angsuran() / totalJasaPinjaman(). */
    private function pinkel(int $jangka, string $jenis_jasa, float $pros_jasa)
    {
        return new class($jangka, $jenis_jasa, $pros_jasa)
        {
            public $jangka;

            public $jenis_jasa;

            public $pros_jasa;

            public $alokasi = 0;

            public $proposal = 0;

            public $verifikasi = 0;

            public $status = 'A';

            public $sistem_angsuran = 1;

            public $sa_jasa = 1;

            public function __construct($jangka, $jenis_jasa, $pros_jasa)
            {
                $this->jangka = $jangka;
                $this->jenis_jasa = $jenis_jasa;
                $this->pros_jasa = $pros_jasa;
            }
        };
    }

    private function sistem(int $sa, int $jangka, int $interval): array
    {
        return $this->call('sistem', [$sa, $jangka, $interval]);
    }

    // -----------------------------------------------------------------
    // 1. Kasus produksi Mojogedang 357 / loan 5835
    // -----------------------------------------------------------------

    public function test_anuitas_mojogedang_5835_angsuran_total_tetap_dan_jasa_menurun(): void
    {
        $alokasi = 850000000;
        $jangka = 4;
        $pros = 2.8;

        $ang_p = $this->sistem(3, $jangka, 4);   // sistem_angsuran = 3 (interval 4 bln)
        $ang_j = $this->sistem(1, $jangka, 1);   // sa_jasa = 1 (bulanan)

        $ra = $this->call('rencana_angsuran', [$this->pinkel($jangka, '3', $pros), $ang_p, $ang_j, $alokasi, '500']);

        $this->assertCount($jangka, array_filter($ra['pokok'], fn ($v) => true), 'anuitas harus punya satu baris pokok per bulan');

        // (1) total angsuran tetap: selisih maksimum hanya efek pembulatan 500
        $totals = [];
        for ($i = 1; $i <= $jangka; $i++) {
            $totals[$i] = $ra['pokok'][$i] + $ra['jasa'][$i];
        }
        $this->assertLessThanOrEqual(500, max($totals) - min($totals), 'angsuran total anuitas wajib tetap: '.json_encode($totals));

        // (2) jasa menurun tegas (saldo pokok berkurang)
        for ($i = 2; $i <= $jangka; $i++) {
            $this->assertLessThan($ra['jasa'][$i - 1], $ra['jasa'][$i], "jasa periode {$i} harus lebih kecil dari periode ".($i - 1));
        }

        // (3) pokok naik tegas
        for ($i = 2; $i <= $jangka; $i++) {
            $this->assertGreaterThan($ra['pokok'][$i - 1], $ra['pokok'][$i], "pokok periode {$i} harus lebih besar dari periode ".($i - 1));
        }

        // (4) pokok TIDAK boleh menumpuk di periode terakhir saja
        $this->assertLessThan($alokasi, $ra['pokok'][1], 'periode 1 wajib mengamortisasi pokok, bukan 0');

        // (5) Σ pokok == alokasi persis
        $this->assertSame($alokasi, (int) array_sum($ra['pokok']), 'Σ pokok wajib sama dengan alokasi');

        // (6) nilai acuan hasil hitung independen (i = 0,7%/bln, PMT = 216.231.500)
        $this->assertSame(216231500, (int) ($ra['pokok'][1] + $ra['jasa'][1]), 'PMT periode 1 harus 216.231.500');
        $this->assertSame(210281500, (int) $ra['pokok'][1], 'pokok periode 1 harus 210.281.500');
        $this->assertSame(5950000, (int) $ra['jasa'][1], 'jasa periode 1 = 850jt x 0,7% = 5.950.000');
    }

    public function test_anuitas_total_jasa_lebih_kecil_dari_flat(): void
    {
        $alokasi = 850000000;
        $jangka = 4;
        $pros = 2.8;

        $ang_p = $this->sistem(1, $jangka, 1);
        $ang_j = $this->sistem(1, $jangka, 1);
        $anuitas = $this->call('rencana_angsuran', [$this->pinkel($jangka, '3', $pros), $ang_p, $ang_j, $alokasi, '500']);
        $flat = $this->call('rencana_angsuran', [$this->pinkel($jangka, '1', $pros), $ang_p, $ang_j, $alokasi, '500']);

        $totalJasaAnuitas = array_sum($anuitas['jasa']);
        $totalJasaFlat = array_sum($flat['jasa']);

        // flat memakai pembulatan per periode sehingga total bisa meleset <1 rupiah
        // akibat representasi float (perilaku lama, bukan bagian perbaikan ini).
        $this->assertEqualsWithDelta(23800000, $totalJasaFlat, 2, 'flat: 2,8% x 850jt = 23.800.000');
        $this->assertLessThan($totalJasaFlat, $totalJasaAnuitas, 'anuitas berbunga menurun: total jasa harus < flat');
        // Total jasa anuitas = Σ jasa jadwal (bukan 2,8% x alokasi). Angka acuan
        // dihitung independen: i=0,7%/bln, PMT 216.231.500.
        $this->assertEqualsWithDelta(14926500, $totalJasaAnuitas, 2, 'total jasa anuitas acuan = 14.926.500');
    }

    // -----------------------------------------------------------------
    // 2. Kasus umum: 12 bulan, 12% total (1%/bln)
    // -----------------------------------------------------------------

    public function test_anuitas_12_bulan_12_persen_konsisten(): void
    {
        $alokasi = 12000000;
        $jangka = 12;
        $pros = 12;

        $ang_p = $this->sistem(1, $jangka, 1);
        $ang_j = $this->sistem(1, $jangka, 1);
        $ra = $this->call('rencana_angsuran', [$this->pinkel($jangka, '3', $pros), $ang_p, $ang_j, $alokasi, '500']);

        $this->assertSame((int) $alokasi, (int) array_sum($ra['pokok']), 'Σ pokok = alokasi');

        $totals = [];
        for ($i = 1; $i <= $jangka; $i++) {
            $totals[$i] = $ra['pokok'][$i] + $ra['jasa'][$i];
        }
        // pembulatan per periode 500 → maksimum selisih antar periode 1000
        $this->assertLessThanOrEqual(1000, max($totals) - min($totals), 'angsuran total tetap: '.json_encode($totals));

        // i = 1% → PMT = 12.000.000 x 0,01 / (1 - 1,01^-12) ≈ 1.066.185
        $this->assertEqualsWithDelta(1066185, $totals[1], 500, 'PMT 12 bln @1%/bln');
        $this->assertSame(120000, (int) $ra['jasa'][1], 'jasa periode 1 = 12jt x 1%');
        $this->assertGreaterThan($ra['pokok'][1], $ra['pokok'][12], 'pokok terakhir > pokok pertama');
    }

    // -----------------------------------------------------------------
    // 3. Guard: anuitas tidak boleh menghasilkan saldo negatif / dobel tutup
    // -----------------------------------------------------------------

    public function test_anuitas_saldo_pokok_berakhir_nol_tanpa_negatif(): void
    {
        $alokasi = 9999999;
        $jangka = 7;
        $pros = 9.1;

        $ang_p = $this->sistem(1, $jangka, 1);
        $ang_j = $this->sistem(1, $jangka, 1);
        $ra = $this->call('rencana_angsuran', [$this->pinkel($jangka, '3', $pros), $ang_p, $ang_j, $alokasi, '500']);

        $saldo = $alokasi;
        for ($i = 1; $i <= $jangka; $i++) {
            $this->assertGreaterThanOrEqual(0, $saldo, "saldo pokok tidak boleh negatif di periode {$i}");
            $saldo -= $ra['pokok'][$i];
        }
        $this->assertSame(0, (int) round($saldo), 'saldo pokok berakhir tepat 0');
        $this->assertSame((int) $alokasi, (int) array_sum($ra['pokok']));
    }

    // -----------------------------------------------------------------
    // 4. Keuangan::totalJasaPinjaman — sumber tunggal total jasa
    // -----------------------------------------------------------------

    public function test_total_jasa_pinjaman_membedakan_flat_dan_anuitas(): void
    {
        $flat = $this->pinkel(4, '1', 2.8);
        $flat->alokasi = 850000000;
        $flat->sistem_angsuran = 1;
        $flat->sa_jasa = 1;

        $anuitas = $this->pinkel(4, '3', 2.8);
        $anuitas->alokasi = 850000000;
        $anuitas->sistem_angsuran = 1;
        $anuitas->sa_jasa = 1;

        $this->assertEqualsWithDelta(23800000.0, Keuangan::totalJasaPinjaman($flat), 2, 'flat: 2,8% x alokasi');
        $this->assertEqualsWithDelta(14926500.0, Keuangan::totalJasaPinjaman($anuitas), 2, 'anuitas: Σ jasa jadwal');
    }
}
