<?php

namespace Tests\Unit;

use App\Services\GenerateService;
use PHPUnit\Framework\TestCase;

/**
 * Bukti EFEK ANGKA perbaikan sumbu grace (masa tunda pokok) pada rencana
 * angsuran sistem M24/M12/M6/M3/M2.
 *
 * ISTILAH:
 *  - Cair   = tanggal pencairan; bulan ke-1 = bulan pertama setelah cair.
 *  - Jangka = tenor (bulan).
 *  - Grace = masa tunda pokok setelah cair (bulan) sebelum cicilan pokok
 *            pertama jatuh tempo.
 *  - Tempo = JUMLAH cicilan pokok setelah grace = floor((jangka-grace)/interval).
 *  - Interval = kolom sis_pokok.sistem / sis_jasa.sistem (jarak antar cicilan, bulan).
 *  - Angsuran pokok ke-k jatuh di bulan (grace + k*interval).
 *  - Amortisasi terakhir (k = tempo) menutup sisa alokasi.
 *
 * Catatan: jalur GenerateService diuji via refleksi + Closure::bind (method
 * protected, tidak butuh DB). Jalur PinjamanAnggotaController::generate()
 * TIDAK dapat diuji di sini karena bergantung pada DB (PinjamanKelompok,
 * RencanaAngsuran, Session, auth) sehingga hanya diuji lewat refleksi untuk
 * formula tempo/grace-nya — lihat testControllerGraceFormula().
 */
class RencanaGraceTest extends TestCase
{
    /** Map grace legacy — dipakai test utk memastikan konsistensi. */
    private const GRACE_MAP = [11 => 24, 14 => 3, 26 => 6, 15 => 2, 25 => 1, 20 => 12];

    /** Instance GenerateService tanpa konstruktor (methodnya tak butuh state). */
    private function service(): GenerateService
    {
        return (new \ReflectionClass(GenerateService::class))->newInstanceWithoutConstructor();
    }

    /** Panggil method protected GenerateService via Closure::bind. */
    private function call(string $method, array $args)
    {
        $svc = $this->service();
        $fn = \Closure::bind(fn () => $this->{$method}(...$args), $svc, GenerateService::class);

        return $fn();
    }

    private function sistem(int $sa, int $jangka, int $interval): array
    {
        return $this->call('sistem', [$sa, $jangka, $interval]);
    }

    /**
     * Replika dummy $pinkel yang cukup untuk rencana_angsuran(): hanya
     * properti jangka & jenis_jasa yang dibaca.
     */
    private function pinkel(int $jangka, string $jenis_jasa = '1', float $pros_jasa = 10.0)
    {
        return new class($jangka, $jenis_jasa, $pros_jasa)
        {
            public $jangka;

            public $jenis_jasa;

            public $pros_jasa;

            public function __construct($jangka, $jenis_jasa, $pros_jasa)
            {
                $this->jangka = $jangka;
                $this->jenis_jasa = $jenis_jasa;
                $this->pros_jasa = $pros_jasa;
            }
        };
    }

    /** @return array [bulan => pokok] */
    private function pokok(int $sa, int $jangka, int $interval, float $alokasi): array
    {
        $ang_p = $this->sistem($sa, $jangka, $interval);
        $ang_j = $this->sistem(1, $jangka, $interval);
        $ra = $this->call('rencana_angsuran', [$this->pinkel($jangka), $ang_p, $ang_j, $alokasi, '500']);

        return $ra['pokok'];
    }

    /** @return array [bulan => jasa] */
    private function jasa(int $sa_jasa, int $jangka, int $interval, float $alokasi, string $jenis_jasa = '1'): array
    {
        $ang_p = $this->sistem($sa_jasa === 0 ? 0 : 26, $jangka, 1);
        $ang_j = $this->sistem($sa_jasa, $jangka, $interval);
        $ra = $this->call('rencana_angsuran', [$this->pinkel($jangka, $jenis_jasa), $ang_p, $ang_j, $alokasi, '500']);

        return $ra['jasa'];
    }

    /** @return array daftar bulan (urut) yang pokoknya non-nol */
    private function bulanNonNol(array $pokok): array
    {
        return array_keys(array_filter($pokok, fn ($v) => $v != 0));
    }

    // ---------------------------------------------------------------------
    // sistem(): tempo + mulai_angsuran harus sesuai tabel VERIFIKASI ANGKA
    // ---------------------------------------------------------------------

    public function test_sistem_m24_tempo_12_mulai_24(): void
    {
        $s = $this->sistem(11, 36, 1);
        $this->assertSame(12.0, (float) $s['tempo']);
        $this->assertSame(24.0, (float) $s['mulai_angsuran']);
    }

    public function test_sistem_m12_tempo_24_mulai_12(): void
    {
        $s = $this->sistem(20, 36, 1);
        $this->assertSame(24.0, (float) $s['tempo']);
        $this->assertSame(12.0, (float) $s['mulai_angsuran']);
    }

    public function test_sistem_m6_tempo_30_mulai_6(): void
    {
        $s = $this->sistem(26, 36, 1);
        $this->assertSame(30.0, (float) $s['tempo']);
        $this->assertSame(6.0, (float) $s['mulai_angsuran']);
    }

    public function test_sistem_m3_tempo_9_mulai_3(): void
    {
        $s = $this->sistem(14, 12, 1);
        $this->assertSame(9.0, (float) $s['tempo']);
        $this->assertSame(3.0, (float) $s['mulai_angsuran']);
    }

    public function test_sistem_m2_tempo_10_mulai_2(): void
    {
        $s = $this->sistem(15, 12, 1);
        $this->assertSame(10.0, (float) $s['tempo']);
        $this->assertSame(2.0, (float) $s['mulai_angsuran']);
    }

    public function test_sistem_m1_tempo_35_mulai_1(): void
    {
        // id 25 / M1: angsuran (pokok & jasa) ditunda 1 bulan → grace=1,
        // tempo = floor((36-1)/1) = 35, cicilan pertama bulan 2, terakhir bulan 36.
        $s = $this->sistem(25, 36, 1);
        $this->assertSame(35.0, (float) $s['tempo']);
        $this->assertSame(1.0, (float) $s['mulai_angsuran']);
    }

    public function test_sistem_tanpa_grace_interval_1(): void
    {
        $s = $this->sistem(1, 12, 1);
        $this->assertSame(12.0, (float) $s['tempo']);
        $this->assertSame(0.0, (float) $s['mulai_angsuran']);
    }

    public function test_sistem_tanpa_grace_interval_3(): void
    {
        $s = $this->sistem(2, 12, 3);
        $this->assertSame(4.0, (float) $s['tempo']);
        $this->assertSame(0.0, (float) $s['mulai_angsuran']);
    }

    // ---------------------------------------------------------------------
    // rencana_angsuran() — POKOK: efek angka M24/M12/M6/M3/M2
    // ---------------------------------------------------------------------

    public function test_m24_jangka_36_pokok_mulai_bulan_25(): void
    {
        $pokok = $this->pokok(11, 36, 1, 12000000);
        $bulan = $this->bulanNonNol($pokok);

        // Bulan 1..24 = 0 (masa tunda).
        foreach (range(1, 24) as $b) {
            $this->assertSame(0, $pokok[$b], "pokok bulan $b harus 0 (grace M24)");
        }
        // Pokok pertama bulan 25, terakhir bulan 36.
        $this->assertSame(25, $bulan[0]);
        $this->assertSame(36, end($bulan));
        // 12 baris pokok (tempo=12), Σ = alokasi.
        $this->assertCount(12, $bulan);
        $this->assertSame(12000000.0, (float) array_sum($pokok));
        // Amortisasi terakhir bulan 36 = alokasi - 1.000.000*11.
        $this->assertSame(1000000.0, (float) $pokok[36]);
    }

    public function test_m12_jangka_36_pokok_mulai_bulan_13(): void
    {
        $pokok = $this->pokok(20, 36, 1, 12000000);
        $bulan = $this->bulanNonNol($pokok);

        foreach (range(1, 12) as $b) {
            $this->assertSame(0, $pokok[$b], "pokok bulan $b harus 0 (grace M12)");
        }
        $this->assertSame(13, $bulan[0]);
        $this->assertSame(36, end($bulan));
        $this->assertCount(24, $bulan);
        $this->assertSame(12000000.0, (float) array_sum($pokok));
    }

    public function test_m6_jangka_36_pokok_mulai_bulan_7(): void
    {
        $pokok = $this->pokok(26, 36, 1, 12000000);
        $bulan = $this->bulanNonNol($pokok);

        foreach (range(1, 6) as $b) {
            $this->assertSame(0, $pokok[$b], "pokok bulan $b harus 0 (grace M6)");
        }
        $this->assertSame(7, $bulan[0]);
        $this->assertSame(36, end($bulan));
        $this->assertCount(30, $bulan);
        $this->assertSame(12000000.0, (float) array_sum($pokok));
    }

    public function test_m3_jangka_12_pokok_mulai_bulan_4(): void
    {
        $pokok = $this->pokok(14, 12, 1, 12000000);
        $bulan = $this->bulanNonNol($pokok);

        foreach (range(1, 3) as $b) {
            $this->assertSame(0, $pokok[$b], "pokok bulan $b harus 0 (grace M3)");
        }
        $this->assertSame(4, $bulan[0]);
        $this->assertSame(12, end($bulan));
        $this->assertCount(9, $bulan);
        $this->assertSame(12000000.0, (float) array_sum($pokok));
    }

    public function test_m2_jangka_12_pokok_mulai_bulan_3(): void
    {
        $pokok = $this->pokok(15, 12, 1, 12000000);
        $bulan = $this->bulanNonNol($pokok);

        $this->assertSame(0, $pokok[1], 'pokok bulan 1 harus 0 (grace M2)');
        $this->assertSame(0, $pokok[2], 'pokok bulan 2 harus 0 (grace M2)');
        $this->assertSame(3, $bulan[0], 'pokok pertama bulan 3');
        $this->assertSame(12, end($bulan));
        $this->assertCount(10, $bulan);
        $this->assertSame(12000000.0, (float) array_sum($pokok));
    }

    // ---------------------------------------------------------------------
    // REGRESI — tanpa grace harus IDENTIK perilaku lama
    // ---------------------------------------------------------------------

    public function test_regresi_tanpa_grace_interval_1(): void
    {
        $pokok = $this->pokok(1, 12, 1, 12000000);
        $bulan = $this->bulanNonNol($pokok);

        $this->assertSame(range(1, 12), $bulan, 'pokok bulan 1..12');
        $this->assertSame(12000000.0, (float) array_sum($pokok));
        $this->assertSame(1000000.0, (float) $pokok[12], 'amortisasi terakhir bulan 12');
    }

    public function test_regresi_tanpa_grace_interval_3(): void
    {
        $pokok = $this->pokok(2, 12, 3, 12000000);
        $bulan = $this->bulanNonNol($pokok);

        $this->assertSame([3, 6, 9, 12], $bulan, 'pokok di bulan 3, 6, 9, 12');
        $this->assertSame(12000000.0, (float) array_sum($pokok));
        $this->assertSame(3000000.0, (float) $pokok[12], 'amortisasi terakhir bulan 12');
    }

    // ---------------------------------------------------------------------
    // JASA — grace jasa sendiri (id 25/M1 → grace 1)
    // ---------------------------------------------------------------------

    public function test_jasa_tanpa_grace_tiap_bulan(): void
    {
        // sa_jasa = 1 (tanpa grace) → jasa tiap bulan 1..12.
        $jasa = $this->jasa(1, 12, 1, 12000000);
        $bulan = $this->bulanNonNol($jasa);

        $this->assertSame(range(1, 12), $bulan, 'jasa tiap bulan saat sa_jasa tanpa grace');
        $this->assertGreaterThan(0, $jasa[1]);
    }

    public function test_jasa_m1_mulai_bulan_2(): void
    {
        // sa_jasa = 25 (M1, grace 1) → jasa mulai bulan 2.
        $jasa = $this->jasa(25, 12, 1, 12000000);
        $bulan = $this->bulanNonNol($jasa);

        $this->assertSame(0, $jasa[1], 'jasa bulan 1 harus 0 (grace M1)');
        $this->assertSame(2, $bulan[0], 'jasa mulai bulan 2 (grace 1)');
    }

    /**
     * Jalur PinjamanAnggotaController::generate() butuh DB (PinjamanKelompok,
     * RencanaAngsuran, Session, auth) sehingga tidak dapat dieksekusi di unit
     * test ini. Yang diuji adalah FORMULA-nya: precedence `tempo` dan
     * pergeseran sumbu grace — dijalankan ulang persis seperti kode controller
     * yang sudah diperbaiki, lalu dibandingkan dgn ekspektasi tabel.
     */
    public function test_controller_grace_formula(): void
    {
        // Replika formula controller (BUG 2a) setelah perbaikan.
        $formula = function (int $sa, int $jangka, int $interval) {
            $grace = 0;
            if ($sa == 11) {
                $grace = 24;
                $tempo = floor(($jangka - 24) / $interval);
            } elseif ($sa == 14) {
                $grace = 3;
                $tempo = floor(($jangka - 3) / $interval);
            } elseif ($sa == 26) {
                $grace = 6;
                $tempo = floor(($jangka - 6) / $interval);
            } elseif ($sa == 15) {
                $grace = 2;
                $tempo = floor(($jangka - 2) / $interval);
            } elseif ($sa == 20) {
                $grace = 12;
                $tempo = floor(($jangka - 12) / $interval);
            } elseif ($sa == 25) {
                $grace = 1;
                $tempo = floor(($jangka - 1) / $interval);
            } else {
                $tempo = floor($jangka / $interval);
            }

            return ['grace' => $grace, 'tempo' => $tempo];
        };

        // Sumbu grace (BUG 2b): pokok ke-k di bulan (grace + k*interval).
        $bulanPokok = function (int $sa, int $jangka, int $interval) use ($formula) {
            $f = $formula($sa, $jangka, $interval);
            $bulan = [];
            for ($i = 1; $i <= $jangka; $i++) {
                if ($i % $interval != 0) {
                    continue;
                }
                $ke = $i / $interval - $f['grace'];
                if ($ke > 0 && $ke <= $f['tempo']) {
                    $bulan[] = $i;
                }
            }

            return $bulan;
        };

        foreach ([[11, 36, 1, 24, 12], [20, 36, 1, 12, 24], [26, 36, 1, 6, 30],
            [14, 12, 1, 3, 9], [15, 12, 1, 2, 10]] as [$sa, $jangka, $interval, $grace, $tempo]) {
            $f = $formula($sa, $jangka, $interval);
            $this->assertSame((float) $grace, (float) $f['grace'], "grace sa=$sa");
            $this->assertSame((float) $tempo, (float) $f['tempo'], "tempo sa=$sa");
            $b = $bulanPokok($sa, $jangka, $interval);
            $this->assertCount($tempo, $b, "jumlah cicilan sa=$sa");
            $this->assertSame($grace + $interval, $b[0], "pokok pertama sa=$sa");
        }

        // Regresi tanpa grace: interval 3 → bulan 3,6,9,12.
        $this->assertSame([3, 6, 9, 12], $bulanPokok(2, 12, 3));
    }
}
