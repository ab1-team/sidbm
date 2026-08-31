<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Verifikasi struktur & perhitungan grouping per desa pada
 * Keuangan::ptkPojkKolekDetail() — replika persis logika klasifikasi
 * (tunggakan, selisih bulan, kategori 5 kolektibilitas POJK, agregasi
 * desa -> JPP -> total) dijalankan terhadap dataset sintetis, tanpa DB.
 *
 * Struktur output yang diharapkan (sesuai request 3EB0774FA9FA3D8EB9D580):
 * JPP -> desa -> rows kelompok, dengan subtotal per desa & total per JPP.
 */
class PtkPojkKolekDetailStructureTest extends TestCase
{
    /** Replika logika inti ptkPojkKolekDetail per baris pinjaman. */
    private function classify(array $pinkel, string $tgl_kondisi): array
    {
        $saldo_pokok = $pinkel['alokasi'];
        $saldo_jasa = $pinkel['pros_jasa'] > 0 ? $pinkel['alokasi'] * ($pinkel['pros_jasa'] / 100) : 0;
        $sum_pokok = 0;
        $sum_jasa = 0;
        if ($pinkel['saldo']) {
            $sum_pokok = $pinkel['saldo']['sum_pokok'];
            $sum_jasa = $pinkel['saldo']['sum_jasa'];
            $saldo_pokok = $pinkel['saldo']['saldo_pokok'];
            $saldo_jasa = $pinkel['saldo']['saldo_jasa'];
        }
        if ($saldo_jasa < 0) {
            $saldo_jasa = 0;
        }

        $target_pokok = $pinkel['target']['target_pokok'] ?? 0;
        $target_jasa = $pinkel['target']['target_jasa'] ?? 0;
        $wajib_pokok = $pinkel['target']['wajib_pokok'] ?? 0;
        $wajib_jasa = $pinkel['target']['wajib_jasa'] ?? 0;
        $angsuran_ke = $pinkel['target']['angsuran_ke'] ?? 0;

        $tunggakan_pokok = $target_pokok - $sum_pokok;
        if ($tunggakan_pokok < 0) {
            $tunggakan_pokok = 0;
        }
        $tunggakan_jasa = $target_jasa - $sum_jasa;
        if ($tunggakan_jasa < 0) {
            $tunggakan_jasa = 0;
        }

        $pross = $pinkel['alokasi'] > 0 ? ($saldo_pokok / $pinkel['alokasi']) : 0;

        if ($pinkel['tgl_lunas'] <= $tgl_kondisi && in_array($pinkel['status'], ['L', 'R', 'H'], true)) {
            $tunggakan_pokok = 0;
            $tunggakan_jasa = 0;
            $saldo_pokok = 0;
            $saldo_jasa = 0;
        }

        $tgl_angsur = $tgl_kondisi;
        if (! empty($pinkel['target'])) {
            $tgl_angsur = $pinkel['target']['jatuh_tempo'];
        }
        $tgl_akhir = new \DateTime($tgl_kondisi);
        if ($saldo_pokok == 0) {
            $tgl_akhir = new \DateTime($tgl_angsur);
        }
        $tgl_awal = new \DateTime($pinkel['tgl_cair']);
        $selisih = $tgl_akhir->diff($tgl_awal);
        $selisih_bulan = ($selisih->y * 12) + $selisih->m;

        $_kolek = 0;
        if ($wajib_pokok != '0') {
            $_kolek = $tunggakan_pokok / $wajib_pokok;
        }
        $bulan_tunggak = round($_kolek + ($selisih_bulan - $angsuran_ke));
        if ($saldo_pokok == 0) {
            $bulan_tunggak = 0;
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
            'saldo_pokok' => (float) $saldo_pokok,
            'tunggakan_pokok' => (float) $tunggakan_pokok,
            'tunggakan_jasa' => (float) $tunggakan_jasa,
            'pross' => $pross,
            'bulan_tunggak' => (int) $bulan_tunggak,
            'kategori' => $kategori,
        ];
    }

    /** Replika agregasi JPP -> desa -> total dari ptkPojkKolekDetail. */
    private function aggregate(array $rows): array
    {
        $detail_per_jpp = [];
        $tot = ['alokasi' => 0, 'saldo' => 0, 'kolek1' => 0, 'kolek2' => 0, 'kolek3' => 0, 'kolek4' => 0, 'kolek5' => 0];

        foreach ($rows as $pinkel) {
            $c = $this->classify($pinkel, '2026-08-31');
            $key = 'kolek'.$c['kategori'];

            if (! isset($detail_per_jpp[$pinkel['jenis_pp']])) {
                $detail_per_jpp[$pinkel['jenis_pp']] = [
                    'nama_jpp' => $pinkel['nama_jpp'],
                    'desa' => [],
                    'tot' => ['alokasi' => 0, 'saldo' => 0, 'kolek1' => 0, 'kolek2' => 0, 'kolek3' => 0, 'kolek4' => 0, 'kolek5' => 0],
                ];
            }
            if (! isset($detail_per_jpp[$pinkel['jenis_pp']]['desa'][$pinkel['kd_desa']])) {
                $detail_per_jpp[$pinkel['jenis_pp']]['desa'][$pinkel['kd_desa']] = [
                    'kd_desa' => $pinkel['kd_desa'],
                    'kode_desa' => $pinkel['kode_desa'],
                    'nama_desa' => $pinkel['nama_desa'],
                    'sebutan_desa' => $pinkel['sebutan_desa'],
                    'rows' => [],
                    'tot' => ['alokasi' => 0, 'saldo' => 0, 'kolek1' => 0, 'kolek2' => 0, 'kolek3' => 0, 'kolek4' => 0, 'kolek5' => 0],
                ];
            }

            $detail_per_jpp[$pinkel['jenis_pp']]['desa'][$pinkel['kd_desa']]['rows'][] = $c;
            $detail_per_jpp[$pinkel['jenis_pp']]['desa'][$pinkel['kd_desa']]['tot']['alokasi'] += (float) $pinkel['alokasi'];
            $detail_per_jpp[$pinkel['jenis_pp']]['desa'][$pinkel['kd_desa']]['tot']['saldo'] += $c['saldo_pokok'];
            $detail_per_jpp[$pinkel['jenis_pp']]['desa'][$pinkel['kd_desa']]['tot'][$key] += $c['saldo_pokok'];

            $detail_per_jpp[$pinkel['jenis_pp']]['tot']['alokasi'] += (float) $pinkel['alokasi'];
            $detail_per_jpp[$pinkel['jenis_pp']]['tot']['saldo'] += $c['saldo_pokok'];
            $detail_per_jpp[$pinkel['jenis_pp']]['tot'][$key] += $c['saldo_pokok'];

            $tot['alokasi'] += (float) $pinkel['alokasi'];
            $tot['saldo'] += $c['saldo_pokok'];
            $tot[$key] += $c['saldo_pokok'];
        }

        foreach ($detail_per_jpp as &$jppItem) {
            uasort($jppItem['desa'], function ($a, $b) {
                return [$a['kode_desa'], $a['nama_desa']] <=> [$b['kode_desa'], $b['nama_desa']];
            });
            $jppItem['desa'] = array_values($jppItem['desa']);
        }
        unset($jppItem);

        return ['detail' => array_values($detail_per_jpp), 'total' => $tot];
    }

    public function test_grouping_jpp_desa_kelompok_dengan_subtotal_desa()
    {
        $rows = [
            // Desa 01 - Sukamaju: 2 kelompok
            ['jenis_pp' => 1, 'nama_jpp' => 'KUM', 'kd_desa' => 'D001', 'kode_desa' => '01', 'nama_desa' => 'Sukamaju', 'sebutan_desa' => 'Desa',
                'alokasi' => 10000000, 'pros_jasa' => 10, 'status' => 'A', 'tgl_cair' => '2026-05-01', 'tgl_lunas' => null,
                'saldo' => ['sum_pokok' => 2000000, 'sum_jasa' => 200000, 'saldo_pokok' => 8000000, 'saldo_jasa' => 800000],
                'target' => ['target_pokok' => 2000000, 'target_jasa' => 200000, 'wajib_pokok' => 2000000, 'wajib_jasa' => 200000, 'angsuran_ke' => 4, 'jatuh_tempo' => '2026-08-25']],
            ['jenis_pp' => 1, 'nama_jpp' => 'KUM', 'kd_desa' => 'D001', 'kode_desa' => '01', 'nama_desa' => 'Sukamaju', 'sebutan_desa' => 'Desa',
                'alokasi' => 6000000, 'pros_jasa' => 10, 'status' => 'A', 'tgl_cair' => '2026-06-01', 'tgl_lunas' => null,
                'saldo' => ['sum_pokok' => 1000000, 'sum_jasa' => 100000, 'saldo_pokok' => 5000000, 'saldo_jasa' => 500000],
                'target' => ['target_pokok' => 1000000, 'target_jasa' => 100000, 'wajib_pokok' => 1000000, 'wajib_jasa' => 100000, 'angsuran_ke' => 3, 'jatuh_tempo' => '2026-08-25']],
            // Desa 02 - Mekarsari: 1 kelompok macet
            ['jenis_pp' => 1, 'nama_jpp' => 'KUM', 'kd_desa' => 'D002', 'kode_desa' => '02', 'nama_desa' => 'Mekarsari', 'sebutan_desa' => 'Desa',
                'alokasi' => 5000000, 'pros_jasa' => 10, 'status' => 'A', 'tgl_cair' => '2025-01-10', 'tgl_lunas' => null,
                'saldo' => ['sum_pokok' => 0, 'sum_jasa' => 0, 'saldo_pokok' => 5000000, 'saldo_jasa' => 500000],
                'target' => ['target_pokok' => 0, 'target_jasa' => 0, 'wajib_pokok' => 416667, 'wajib_jasa' => 41667, 'angsuran_ke' => 0, 'jatuh_tempo' => '2026-01-10']],
        ];

        $res = $this->aggregate($rows);

        // struktur JPP -> desa
        $this->assertCount(1, $res['detail']);
        $jpp = $res['detail'][0];
        $this->assertSame('KUM', $jpp['nama_jpp']);
        $this->assertCount(2, $jpp['desa']);
        $this->assertSame('Sukamaju', $jpp['desa'][0]['nama_desa']);
        $this->assertCount(2, $jpp['desa'][0]['rows']);
        $this->assertSame('Mekarsari', $jpp['desa'][1]['nama_desa']);

        // urutan desa by kode_desa (01 < 02)
        $this->assertSame('01', $jpp['desa'][0]['kode_desa']);
        $this->assertSame('02', $jpp['desa'][1]['kode_desa']);

        // subtotal desa Sukamaju: saldo 8jt + 5jt = 13jt, semua lancar
        $d1 = $jpp['desa'][0]['tot'];
        $this->assertEquals(13000000, $d1['saldo']);
        $this->assertEquals(13000000, $d1['kolek1']);
        $this->assertEquals(0, $d1['kolek5']);

        // Mekarsari: belum bayar sama sekali sejak cair (19 bln) -> macet
        $d2 = $jpp['desa'][1]['tot'];
        $this->assertEquals(5000000, $d2['saldo']);
        $this->assertEquals(5000000, $d2['kolek5']);

        // total JPP = penjumlahan kedua desa
        $this->assertEquals($d1['saldo'] + $d2['saldo'], $jpp['tot']['saldo']);
        $this->assertEquals($d1['kolek5'] + $d2['kolek5'], $jpp['tot']['kolek5']);

        // total global
        $this->assertEquals(18000000, $res['total']['saldo']);
        $this->assertEquals(13000000, $res['total']['kolek1']);
        $this->assertEquals(5000000, $res['total']['kolek5']);
    }

    public function test_kelasifikasi_lunas_dan_tunggakan()
    {
        $tgl = '2026-08-31';

        // Pinjaman lunas: saldo & tunggakan = 0
        $lunas = $this->classify([
            'alokasi' => 10000000, 'pros_jasa' => 10, 'status' => 'L', 'tgl_cair' => '2026-01-05', 'tgl_lunas' => '2026-07-01',
            'saldo' => ['sum_pokok' => 10000000, 'sum_jasa' => 1000000, 'saldo_pokok' => 0, 'saldo_jasa' => 0],
            'target' => ['target_pokok' => 10000000, 'target_jasa' => 1000000, 'wajib_pokok' => 2500000, 'wajib_jasa' => 250000, 'angsuran_ke' => 4, 'jatuh_tempo' => '2026-07-25'],
        ], $tgl);
        $this->assertEquals(0, $lunas['saldo_pokok']);
        $this->assertEquals(0, $lunas['bulan_tunggak']);
        $this->assertSame(1, $lunas['kategori']);

        // Tunggakan penuh 1 angsuran dr wajib 2.5jt, angsuran_ke=4, umur 7 bln:
        // tunggakan = target(7.5jt) - sudah bayar(5jt) = 2.5jt;
        // bulan_tunggak = round(1 + (7-4)) = 4 -> DPK (kategori 2)
        $dpk = $this->classify([
            'alokasi' => 10000000, 'pros_jasa' => 10, 'status' => 'A', 'tgl_cair' => '2026-01-25', 'tgl_lunas' => null,
            'saldo' => ['sum_pokok' => 5000000, 'sum_jasa' => 500000, 'saldo_pokok' => 2500000, 'saldo_jasa' => 250000],
            'target' => ['target_pokok' => 7500000, 'target_jasa' => 750000, 'wajib_pokok' => 2500000, 'wajib_jasa' => 250000, 'angsuran_ke' => 4, 'jatuh_tempo' => '2026-08-25'],
        ], $tgl);
        $this->assertEquals(2500000, $dpk['tunggakan_pokok']);
        $this->assertEquals(4, $dpk['bulan_tunggak']);
        $this->assertSame(2, $dpk['kategori']);

        // Lancar: tidak ada tunggakan, angsuran berjalan sesuai jadwal
        $lancar = $this->classify([
            'alokasi' => 10000000, 'pros_jasa' => 10, 'status' => 'A', 'tgl_cair' => '2026-01-25', 'tgl_lunas' => null,
            'saldo' => ['sum_pokok' => 8000000, 'sum_jasa' => 800000, 'saldo_pokok' => 2000000, 'saldo_jasa' => 200000],
            'target' => ['target_pokok' => 8000000, 'target_jasa' => 800000, 'wajib_pokok' => 2000000, 'wajib_jasa' => 200000, 'angsuran_ke' => 4, 'jatuh_tempo' => '2026-08-25'],
        ], $tgl);
        $this->assertEquals(0, $lancar['tunggakan_pokok']);
        $this->assertEquals(3, $lancar['bulan_tunggak']);
        $this->assertSame(1, $lancar['kategori']);
    }

    public function test_desa_tanpa_relasi_masih_masuk_grup_lain()
    {
        $rows = [
            ['jenis_pp' => 2, 'nama_jpp' => 'SPP', 'kd_desa' => '0', 'kode_desa' => '', 'nama_desa' => '-', 'sebutan_desa' => 'Desa',
                'alokasi' => 4000000, 'pros_jasa' => 0, 'status' => 'A', 'tgl_cair' => '2026-07-01', 'tgl_lunas' => null,
                'saldo' => ['sum_pokok' => 0, 'sum_jasa' => 0, 'saldo_pokok' => 4000000, 'saldo_jasa' => 0],
                'target' => ['target_pokok' => 0, 'target_jasa' => 0, 'wajib_pokok' => 0, 'wajib_jasa' => 0, 'angsuran_ke' => 0, 'jatuh_tempo' => '2026-07-01']],
            ['jenis_pp' => 1, 'nama_jpp' => 'KUM', 'kd_desa' => 'D001', 'kode_desa' => '01', 'nama_desa' => 'Sukamaju', 'sebutan_desa' => 'Desa',
                'alokasi' => 2000000, 'pros_jasa' => 10, 'status' => 'A', 'tgl_cair' => '2026-07-01', 'tgl_lunas' => null,
                'saldo' => ['sum_pokok' => 0, 'sum_jasa' => 0, 'saldo_pokok' => 2000000, 'saldo_jasa' => 200000],
                'target' => ['target_pokok' => 0, 'target_jasa' => 0, 'wajib_pokok' => 0, 'wajib_jasa' => 0, 'angsuran_ke' => 0, 'jatuh_tempo' => '2026-07-01']],
        ];

        $res = $this->aggregate($rows);

        // 2 JPP terpisah
        $this->assertCount(2, $res['detail']);
        $this->assertSame('SPP', $res['detail'][0]['nama_jpp']);
        $this->assertSame('KUM', $res['detail'][1]['nama_jpp']);

        // tiap JPP punya grup desanya masing-masing
        $this->assertCount(1, $res['detail'][0]['desa']);
        $this->assertCount(1, $res['detail'][1]['desa']);
        $this->assertEquals(4000000, $res['detail'][0]['tot']['saldo']);
        $this->assertEquals(2000000, $res['detail'][1]['tot']['saldo']);

        // global menjumlahkan semua JPP
        $this->assertEquals(6000000, $res['total']['saldo']);
        $this->assertEquals(6000000, $res['total']['kolek1']);
    }
}
