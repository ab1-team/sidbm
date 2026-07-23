<?php

namespace Tests\Feature;

use App\Models\PinjamanKelompok;
use App\Services\GenerateService;
use Illuminate\Support\Facades\DB;
use Session;
use Tests\TestCase;

class GenerateServicePinkel11093Test extends TestCase
{
    /**
     * Test GenerateService::preview() untuk pinkel 11093 lokasi 301
     * (server mysql_b). Verifikasi output match "Image tujuan":
     *
     *   angs 1-6 reg:    16.333.500 / 2.450.000
     *   angs 6 hapus:     9.334.500 / 1.400.000  (row kedua, tgl 2026-07-23)
     *   angs 7-12 pasca: 14.777.500 / ~2.216.500 (sisa 88.665.000 / 6)
     *   total Σ:         196.000.000 / 29.400.000
     *
     * Row hapus masuk sbg angs_ke yg SAMA dgn reguler di bulan itu
     * (bukan bergeser ke $i+1).
     */
    public function test_preview_pinkel_11093_lokasi_301_match_target()
    {
        Session::put('lokasi', 301);

        $pinkel = PinjamanKelompok::on('mysql_b')
            ->where('id', 11093)
            ->with([
                'kelompok',
                'kelompok.d',
                'pinjaman_anggota',
                'sis_pokok',
                'sis_jasa',
                'trx' => function ($q) {
                    $q->where('idtp', '!=', '0');
                },
                'trx_penghapusan',
            ])
            ->first();

        if (!$pinkel) {
            $this->markTestSkipped('pinkel 11093 tidak ada di mysql_b.rencana_angsuran_301');
        }

        $svc = new GenerateService();
        $res = $svc->preview($pinkel);

        // Ambil row angsuran > 0, sort by tgl (krn ada 2 row dgn angs_ke sama)
        $rows = collect($res['rencana'])
            ->filter(fn ($r) => ($r['angsuran_ke'] ?? 0) > 0)
            ->sortBy(fn ($r) => [intval($r['angsuran_ke']), strtotime($r['jatuh_tempo'])])
            ->values()
            ->all();

        // Total 13 rows: 6 reguler + 1 hapus (di slot angs 6) + 6 pasca-hapus
        $this->assertCount(13, $rows, 'expected 13 rows (incl 1 hapus row), got '.count($rows));

        // Row 1-6 reguler (angs 1..6)
        foreach ([1, 2, 3, 4, 5, 6] as $ke) {
            $row = collect($rows)->first(fn ($r) =>
                (int) $r['angsuran_ke'] === $ke && str_starts_with($r['jatuh_tempo'], $this->regTgl($ke))
            );
            $this->assertNotNull($row, "reg row angs={$ke} missing");
            $this->assertEquals(16333500, (int) $row['wajib_pokok'], "pokok angs={$ke}");
            $this->assertEquals(2450000, (int) $row['wajib_jasa'], "jasa angs={$ke}");
        }

        // Row hapus sbg angs=6 kedua, tgl 2026-07-23
        $hapus = collect($rows)->first(fn ($r) =>
            (int) $r['angsuran_ke'] === 6 && str_starts_with($r['jatuh_tempo'], '2026-07-23')
        );
        $this->assertNotNull($hapus, 'row hapus angs=6 tgl 2026-07-23 missing');
        $this->assertEquals(9334500, (int) $hapus['wajib_pokok'], 'pokok hapus');
        $this->assertEquals(1400000, (int) $hapus['wajib_jasa'], 'jasa hapus');

        // Row pasca-hapus (angs 7-12) — 6 row reguler dgn pok ~14.777.500
        $pascas = collect($rows)->filter(fn ($r) =>
            (int) $r['angsuran_ke'] >= 7 && (int) $r['angsuran_ke'] <= 12
            && str_starts_with($r['jatuh_tempo'], '2026-08')
            || (
                (int) $r['angsuran_ke'] >= 7
                && strtotime($r['jatuh_tempo']) > strtotime('2026-07-23')
            )
        )->values()->all();

        // Filter ulang: pasca = angs >=7 dan TIDAK ada di 2026-07-21 (reguler angs 6)
        $pascas = collect($rows)->filter(fn ($r) =>
            (int) $r['angsuran_ke'] >= 7
        )->values()->all();

        $this->assertCount(6, $pascas, 'expected 6 rows pasca-hapus, got '.count($pascas));

        foreach ($pascas as $r) {
            $pok = (int) $r['wajib_pokok'];
            $jas = (int) $r['wajib_jasa'];
            $this->assertEqualsWithDelta(14777500, $pok, 1000, "pokok pasca (got {$pok})");
            $this->assertEqualsWithDelta(2216500, $jas, 1000, "jasa pasca (got {$jas})");
        }

        // Σ total
        $sum_p = array_sum(array_column($rows, 'wajib_pokok'));
        $sum_j = array_sum(array_column($rows, 'wajib_jasa'));
        $this->assertEqualsWithDelta(196000000, $sum_p, 1000, "Σ pokók: {$sum_p}");
        $this->assertEqualsWithDelta(29400000, $sum_j, 1000, "Σ jasa: {$sum_j}");
    }

    private function regTgl(int $ke): string
    {
        // angs 1 = 2026-02-21, ..., angs 6 = 2026-07-21
        $bulan = 1 + $ke;
        return "2026-".str_pad((string) $bulan, 2, '0', STR_PAD_LEFT)."-21";
    }
}
