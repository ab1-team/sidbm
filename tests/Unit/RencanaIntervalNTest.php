<?php

namespace Tests\Unit;

use App\Services\GenerateService;
use Tests\TestCase;

/**
 * Sistem angsuran interval-N (id 8 = "8 Bulan" = satu kali dalam delapan
 * bulan, dst.) harus menghasilkan jatuh tempo tiap N bulan, BUKAN baris
 * bulanan dengan nilai nol.
 *
 * Keluhan produksi: pinjaman sistem_angsuran=8 tetap terjadwal bulanan
 * (8 baris: 7 nol + 1 berisi di bulan ke-8). Ekspektasi sesuai master:
 * baris angsuran hanya pada bulan kelipatan interval (pokok ATAU jasa).
 *
 * Loop yang diuji = buildRencanaV1 (jalur paling sederhana tanpa penghapusan;
 * jalur V2 memakai logika slot yang sama) — diakses via refleksi karena
 * protected dan butuh relasi DB di jalur penuh.
 */
class RencanaIntervalNTest extends TestCase
{
    private function service(): GenerateService
    {
        return (new \ReflectionClass(GenerateService::class))->newInstanceWithoutConstructor();
    }

    private function invoke(string $method, array $args)
    {
        $svc = $this->service();
        $fn = \Closure::bind(fn () => $this->{$method}(...$args), $svc, GenerateService::class);

        return $fn();
    }

    /** Pinkel dummy: properti yang dibaca buildRencanaV1. */
    private function pinkel(int $jangka, int $sa_pokok, int $sa_jasa, string $jenis_jasa = '1', float $pros_jasa = 12.0, float $alokasi = 50000000.0, string $tglCair = '2026-11-19')
    {
        return new class($jangka, $sa_pokok, $sa_jasa, $jenis_jasa, $pros_jasa, $alokasi, $tglCair)
        {
            public $jangka;

            public $sistem_angsuran;

            public $sa_jasa;

            public $jenis_jasa;

            public $pros_jasa;

            public $alokasi;

            public $id = 999;

            public $status = 'W';

            public $tgl_cair;

            public $pinjaman_anggota = [];

            public $kelompok;

            public function __construct($jangka, $sa_pokok, $sa_jasa, $jenis_jasa, $pros_jasa, $alokasi, $tglCair)
            {
                $this->jangka = $jangka;
                $this->sistem_angsuran = $sa_pokok;
                $this->sa_jasa = $sa_jasa;
                $this->jenis_jasa = $jenis_jasa;
                $this->pros_jasa = $pros_jasa;
                $this->alokasi = $alokasi;
                $this->tgl_cair = $tglCair;
                // kelompok->d dipakai detail_pinjaman; objek kosong = tanpa jadwal desa.
                $this->kelompok = (object) ['d' => (object) ['jadwal_angsuran_desa' => 0]];
            }
        };
    }

    /** Jalankan buildRencanaV1 (via Closure::bind) dan kembalikan baris angsuran (tanpa row 0). */
    private function baris(int $jangka, int $sa_pokok, int $sa_jasa, float $alokasi, string $tglCair = '2026-11-19', string $jenis_jasa = '1', float $pros_jasa = 12.0, int $interval = 8, int $intervalJasa = 8): array
    {
        $pinkel = $this->pinkel($jangka, $sa_pokok, $sa_jasa, $jenis_jasa, $pros_jasa, $alokasi, $tglCair);

        // sis_pokok/sis_jasa = relasi; V1 membaca ->sistem-nya (kolom sistem master).
        $sisP = (object) ['sistem' => $interval];
        $sisJ = (object) ['sistem' => $intervalJasa];
        $pinkel->sis_pokok = $sisP;
        $pinkel->sis_jasa = $sisJ;

        $kec = (object) ['batas_angsuran' => 0, 'pembulatan' => '500'];

        $res = $this->invoke('buildRencanaV1', [$pinkel, $kec]);

        return array_values(array_filter($res['rencana'], fn ($r) => $r['angsuran_ke'] != 0));
    }

    public function test_sistem_8_jangka_8_pembayaran_hanya_di_bulan_ke8(): void
    {
        $rows = $this->baris(8, 8, 8, 50000000, '2026-11-19');

        // Baris bulanan tetap ada (bulan 1..8); hanya bulan ke-8 berisi.
        $this->assertCount(8, $rows, 'sistem 8-bulanan tetap punya 8 baris bulanan');
        $this->assertSame('2027-07-19', $rows[7]['jatuh_tempo'], 'jatuh tempo = cair + 8 bulan');

        for ($i = 0; $i < 7; $i++) {
            $this->assertSame(0.0, (float) $rows[$i]['wajib_pokok'], 'bulan ke-'.($i + 1).' tanpa pokok');
            $this->assertSame(0.0, (float) $rows[$i]['wajib_jasa'], 'bulan ke-'.($i + 1).' tanpa jasa');
            $this->assertSame(0.0, (float) $rows[$i]['target_pokok'], 'target pokok belum naik');
            $this->assertSame(0.0, (float) $rows[$i]['target_jasa'], 'target jasa belum naik');
        }

        $this->assertSame(50000000.0, (float) $rows[7]['wajib_pokok']);
        // jasa = 12% x 50jt = 6jt (sekali bayar).
        $this->assertEqualsWithDelta(6000000.0, (float) $rows[7]['wajib_jasa'], 1.0);
        $this->assertSame(50000000.0, (float) $rows[7]['target_pokok']);
    }

    public function test_sistem_8_jangka_8_anuitas_pembayaran_hanya_di_bulan_ke8(): void
    {
        // jenis_jasa=3 (anuitas): total jasa tetap hasil anuitas bulanan,
        // tetapi seluruhnya dibayar di bulan kelipatan interval.
        $rows = $this->baris(8, 8, 8, 50000000, '2026-11-19', '3', 12.0, 8, 8);

        $this->assertCount(8, $rows, 'anuitas interval 8 tetap 8 baris bulanan');
        $this->assertSame('2027-07-19', $rows[7]['jatuh_tempo'], 'jatuh tempo = cair + 8 bulan');

        for ($i = 0; $i < 7; $i++) {
            $this->assertSame(0.0, (float) $rows[$i]['wajib_pokok'], 'bulan ke-'.($i + 1).' tanpa pokok');
            $this->assertSame(0.0, (float) $rows[$i]['wajib_jasa'], 'bulan ke-'.($i + 1).' tanpa jasa');
        }

        $bulanan = \App\Utils\Keuangan::jadwalAnuitas(50000000, 12.0, 8, '500');
        $this->assertSame(50000000.0, (float) $rows[7]['wajib_pokok']);
        $this->assertEqualsWithDelta(array_sum($bulanan['jasa']), (float) $rows[7]['wajib_jasa'], 1.0, 'jasa = total anuitas bulanan');
    }

    public function test_sistem_8_jangka_8_anuitas_jasa_bulanan_tetap_bulanan(): void
    {
        // Loan 1364: pokok 8-bulanan, jasa 1-bulanan. Pokok hanya di bulan 8,
        // jasa tetap muncul tiap bulan.
        $rows = $this->baris(8, 8, 1, 50000000, '2026-11-19', '3', 12.0, 8, 1);

        $this->assertCount(8, $rows);
        $pokokNonNol = array_values(array_filter($rows, fn ($r) => $r['wajib_pokok'] != 0));
        $this->assertCount(1, $pokokNonNol, 'pokok hanya di satu baris');
        $this->assertSame('2027-07-19', $pokokNonNol[0]['jatuh_tempo']);

        $bulanan = \App\Utils\Keuangan::jadwalAnuitas(50000000, 12.0, 8, '500');
        foreach ($rows as $i => $r) {
            $this->assertGreaterThan(0, (float) $r['wajib_jasa'], 'jasa bulan ke-'.($i + 1).' harus terisi');
            $this->assertEqualsWithDelta($bulanan['jasa'][$i + 1], (float) $r['wajib_jasa'], 1.0);
        }
        $this->assertEqualsWithDelta(array_sum($bulanan['jasa']), (float) array_sum(array_column($rows, 'wajib_jasa')), 1.0);
    }

    public function test_sistem_8_jangka_8_dengan_jasa_bulanan(): void
    {
        // sa_jasa=1: baris jasa tetap tiap bulan; pokok hanya muncul di baris bulan ke-8.
        $rows = $this->baris(8, 8, 1, 50000000, '2026-11-19', '1', 12.0, 8, 1);

        $this->assertCount(8, $rows, 'sa_jasa bulanan = 8 baris');
        $pokokNonNol = array_values(array_filter($rows, fn ($r) => $r['wajib_pokok'] != 0));
        $this->assertCount(1, $pokokNonNol, 'pokok hanya di satu baris');
        $this->assertSame('2027-07-19', $pokokNonNol[0]['jatuh_tempo']);
        foreach ($rows as $r) {
            $this->assertEqualsWithDelta(750000.0, (float) $r['wajib_jasa'], 1.0, 'jasa bulanan = 6jt/8');
        }
    }

    public function test_sistem_8_jangka_16_dua_kali_bayar(): void
    {
        $rows = $this->baris(16, 8, 8, 50000000, '2026-11-19');

        $this->assertCount(16, $rows, 'jangka 16 = 16 baris bulanan');
        $nonZero = array_values(array_filter($rows, fn ($r) => $r['wajib_pokok'] != 0));
        $this->assertCount(2, $nonZero, 'jangka 16, interval 8 = 2 kali bayar');
        $this->assertSame('2027-07-19', $nonZero[0]['jatuh_tempo']);
        $this->assertSame('2028-03-19', $nonZero[1]['jatuh_tempo']);
        $this->assertSame(50000000.0, (float) array_sum(array_column($rows, 'wajib_pokok')));
    }

    public function test_sistem_4_jangka_8_dua_kali_bayar(): void
    {
        // interval 4 bulan (master id 3 = "4 Bulan", sistem = 4).
        $rows = $this->baris(8, 3, 3, 20000000, '2026-11-19', '1', 12.0, 4);

        $this->assertCount(8, $rows);
        $nonZero = array_values(array_filter($rows, fn ($r) => $r['wajib_pokok'] != 0));
        $this->assertCount(2, $nonZero);
        $this->assertSame('2027-03-19', $nonZero[0]['jatuh_tempo']);
        $this->assertSame('2027-07-19', $nonZero[1]['jatuh_tempo']);
        $this->assertSame(20000000.0, (float) array_sum(array_column($rows, 'wajib_pokok')));
    }

    public function test_sistem_3_jangka_12_empat_kali_bayar(): void
    {
        // master id 2 = "3 Bulan" → kolom sistem = 3 (interval 3 bulan).
        $rows = $this->baris(12, 2, 2, 9000000, '2026-11-19', '1', 12.0, 3, 3);

        $this->assertCount(12, $rows);
        $nonZero = array_values(array_filter($rows, fn ($r) => $r['wajib_pokok'] != 0));
        $this->assertCount(4, $nonZero);
        $this->assertSame('2027-02-19', $nonZero[0]['jatuh_tempo']);
        $this->assertSame('2027-05-19', $nonZero[1]['jatuh_tempo']);
        $this->assertSame('2027-08-19', $nonZero[2]['jatuh_tempo']);
        $this->assertSame('2027-11-19', $nonZero[3]['jatuh_tempo']);
        $this->assertSame(9000000.0, (float) array_sum(array_column($rows, 'wajib_pokok')));
    }

    public function test_sistem_bulanan_tidak_berubah(): void
    {
        // Regresi: sistem 1 (Bulanan) tetap 12 baris bulanan.
        $rows = $this->baris(12, 1, 1, 12000000, '2026-11-19', '1', 12.0, 1, 1);

        $this->assertCount(12, $rows);
        $this->assertSame('2026-12-19', $rows[0]['jatuh_tempo']);
        $this->assertSame('2027-11-19', $rows[11]['jatuh_tempo']);
        $this->assertSame(12000000.0, (float) array_sum(array_column($rows, 'wajib_pokok')));
    }
}
