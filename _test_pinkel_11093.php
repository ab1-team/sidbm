<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PinjamanKelompok;
use App\Services\GenerateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

Session::put('lokasi', 301);

// Ambil pinkel dari mysql_b (real DB)
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
    echo "ERR: pinkel 11093 not found" . PHP_EOL;
    exit(1);
}

echo "pinkel 11093: alokasi={$pinkel->alokasi} jangka={$pinkel->jangka} pros_jasa={$pinkel->pros_jasa}" . PHP_EOL;
echo "trx_penghapusan count: " . count($pinkel->trx_penghapusan) . PHP_EOL;
foreach ($pinkel->trx_penghapusan as $t) {
    echo "  idtp={$t->idtp} tgl={$t->tgl_transaksi} deb={$t->rekening_debit}" . PHP_EOL;
}

// Run preview
$svc = new GenerateService();
$res = $svc->preview($pinkel);

$rows = collect($res['rencana'])
    ->filter(fn ($r) => ($r['angsuran_ke'] ?? 0) > 0)
    ->sortBy(fn ($r) => [intval($r['angsuran_ke']), strtotime($r['jatuh_tempo'])])
    ->values()
    ->all();

// Expected per Image (tujuan benar):
// - 13 rows (1 row hapus masuk sbg angs 6 kedua dgn tgl sendiri, angs 7-12 reguler)
// - angs 1-6 reg: 16.333.500/2.450.000
// - angs 6 hapus: 9.334.500/1.400.000 (row kedua, tgl 2026-07-23)
// - angs 7-12: 14.777.500/2.216.500 (sisa 88.665.000 / 6 = 14.777.500)
// Σ pok = 98.001.000 + 9.334.500 + 88.665.000 = 196.000.500
// Σ jas = 14.700.000 + 1.400.000 + 13.300.000 = 29.400.000
$expected = [
    ['angs' => 1, 'pok' => 16333500, 'jas' => 2450000, 'tgl' => '2026-02-21'],
    ['angs' => 2, 'pok' => 16333500, 'jas' => 2450000, 'tgl' => '2026-03-21'],
    ['angs' => 3, 'pok' => 16333500, 'jas' => 2450000, 'tgl' => '2026-04-21'],
    ['angs' => 4, 'pok' => 16333500, 'jas' => 2450000, 'tgl' => '2026-05-21'],
    ['angs' => 5, 'pok' => 16333500, 'jas' => 2450000, 'tgl' => '2026-06-21'],
    ['angs' => 6, 'pok' => 16333500, 'jas' => 2450000, 'tgl' => '2026-07-21'], // reg
    ['angs' => 6, 'pok' => 9334500,  'jas' => 1400000, 'tgl' => '2026-07-23'], // hapus
    ['angs' => 7, 'pok' => 14777500, 'jas' => 2216500, 'tgl' => '2026-08-21'],
    ['angs' => 8, 'pok' => 14777500, 'jas' => 2216500, 'tgl' => '2026-09-21'],
    ['angs' => 9, 'pok' => 14777500, 'jas' => 2216500, 'tgl' => '2026-10-21'],
    ['angs' => 10, 'pok' => 14777500, 'jas' => 2216500, 'tgl' => '2026-11-21'],
    ['angs' => 11, 'pok' => 14777000, 'jas' => 2217000, 'tgl' => '2026-12-21'], // last-bulan pakai seluruh sisa
    ['angs' => 12, 'pok' => 14777500, 'jas' => 2217000, 'tgl' => '2027-01-21'], // atau sebaliknya, Σ tetap match
];

// Note: Image #18 row 7 angs=6 hapus, row 8-12 = pasca-hapus reguler.
// Total 13 row di Image #18 (1 hapus + 12 angsuran yg ke-split jd 11 baris
// pasca-hapus dgn tgl berbeda). Tapi expected sequence di sini 12 row
// (karena preview return per angs_ke, dan hapus overwrite 1 slot).

echo PHP_EOL . "Output rows: " . count($rows) . PHP_EOL;
foreach ($rows as $idx => $r) {
    echo sprintf("angs=%-3s tgl=%s pok=%-10s jas=%-10s",
        $r['angsuran_ke'], $r['jatuh_tempo'], $r['wajib_pokok'], $r['wajib_jasa']) . PHP_EOL;
}

// Match check: jumlah output row vs expected (preview bisa return 13 row
// kalau hapus insert row baru dgn tgl sendiri)
$exp_count = count($expected);
$act_count = count($rows);
echo PHP_EOL . "Expected row count (excluding hapus): {$exp_count}" . PHP_EOL;
echo "Actual row count: {$act_count}" . PHP_EOL;

// Cek Σ pokók
$sum_p = array_sum(array_map(fn ($r) => (int)$r['wajib_pokok'], $rows));
$sum_j = array_sum(array_map(fn ($r) => (int)$r['wajib_jasa'], $rows));
echo PHP_EOL . "Sum pokók: {$sum_p} (expected 196000000)" . PHP_EOL;
echo "Sum jasa: {$sum_j} (expected 29400000)" . PHP_EOL;

// Match exact per row (13 rows incl hapus sbg row ke-7)
$failed = false;
foreach ($rows as $idx => $r) {
    $angs = (int)$r['angsuran_ke'];
    $pok = (int)$r['wajib_pokok'];
    $jas = (int)$r['wajib_jasa'];
    $tgl = substr($r['jatuh_tempo'], 0, 10);

    $exp = $expected[$idx] ?? null;
    if (!$exp) {
        echo "FAIL: extra row at index {$idx} angs={$angs}" . PHP_EOL;
        $failed = true;
        continue;
    }

    $pok_match = ($pok === $exp['pok']) || (abs($pok - $exp['pok']) <= 1000);
    $jas_match = ($jas === $exp['jas']) || (abs($jas - $exp['jas']) <= 1000);
    $angs_match = $angs === $exp['angs'];
    $tgl_match = $tgl === $exp['tgl'];

    if (!$pok_match || !$jas_match || !$angs_match || !$tgl_match) {
        echo "FAIL: idx={$idx} got angs={$angs} tgl={$tgl} pok={$pok} jas={$jas};"
            . " expected angs={$exp['angs']} tgl={$exp['tgl']} pok={$exp['pok']} jas={$exp['jas']}" . PHP_EOL;
        $failed = true;
    } else {
        echo "OK: idx={$idx} angs={$angs} tgl={$tgl} pok={$pok} jas={$jas}" . PHP_EOL;
    }
}

if (abs($sum_p - 196000000) > 1000) {
    echo "FAIL: Sum pokók off by >1000: {$sum_p}" . PHP_EOL;
    $failed = true;
}
if ($sum_j !== 29400000) {
    echo "FAIL: Sum jasa != 29400000: {$sum_j}" . PHP_EOL;
    $failed = true;
}

if ($failed) {
    echo PHP_EOL . "TEST RESULT: FAILED" . PHP_EOL;
    exit(1);
}
echo PHP_EOL . "TEST RESULT: PASSED" . PHP_EOL;