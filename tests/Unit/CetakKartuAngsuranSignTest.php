<?php

namespace Tests\Unit;

use Tests\TestCase;

class CetakKartuAngsuranSignTest extends TestCase
{
    /**
     * Test 1: Transaksi Tunai (rekening_debit = '1.1.01.01') menghasilkan
     * prefix 'TN-<id>' pada baris aktif yang dicetak ($real->id == $idtp).
     */
    public function test_transaksi_tunai_menghasilkan_prefix_tn_pada_baris_aktif(): void
    {
        $real = $this->createRealisasi(101, '1.1.01.01');
        $html = $this->renderKartuAngsuran(101, [$real]);

        $xpath = $this->parseXPath($html);
        $row = $this->findRealisasiRow($xpath, 101);

        $this->assertNotNull($row, 'Baris realisasi 101 tidak ditemukan.');
        $this->assertSame('TN-101', $this->getSignCellText($row));
        $this->assertStringNotContainsString('opacity: 0;', $row->getAttribute('style'));
    }

    /**
     * Test 2: Transaksi Transfer Bank (rekening_debit = '1.1.01.04' atau akun kas bank lainnya)
     * menghasilkan prefix 'TF-<id>' pada baris aktif yang dicetak ($real->id == $idtp).
     */
    public function test_transaksi_transfer_bank_menghasilkan_prefix_tf_pada_baris_aktif(): void
    {
        $bankAccounts = [
            '1.1.01.03', // Kas di Bank Ops
            '1.1.01.04', // Kas di Bank SPP
            '1.1.01.05', // Kas di Bank Bumkalma
        ];

        foreach ($bankAccounts as $index => $rekeningDebit) {
            $id = 200 + $index;
            $real = $this->createRealisasi($id, $rekeningDebit);
            $html = $this->renderKartuAngsuran($id, [$real]);

            $xpath = $this->parseXPath($html);
            $row = $this->findRealisasiRow($xpath, $id);

            $this->assertNotNull($row, "Baris realisasi {$id} ({$rekeningDebit}) tidak ditemukan.");
            $this->assertSame("TF-{$id}", $this->getSignCellText($row));
            $this->assertStringNotContainsString('opacity: 0;', $row->getAttribute('style'));
        }
    }

    /**
     * Test 3: Transaksi dengan relasi 'transaksi' null menghasilkan
     * fallback 'TF-<id>' tanpa error/crash PHP 8.
     */
    public function test_transaksi_null_menghasilkan_fallback_prefix_tf(): void
    {
        $real = $this->createRealisasi(301, null);
        $html = $this->renderKartuAngsuran(301, [$real]);

        $xpath = $this->parseXPath($html);
        $row = $this->findRealisasiRow($xpath, 301);

        $this->assertNotNull($row, 'Baris realisasi 301 tidak ditemukan.');
        $this->assertSame('TF-301', $this->getSignCellText($row));
        $this->assertStringNotContainsString('opacity: 0;', $row->getAttribute('style'));
    }

    /**
     * Test 4: Memastikan baris $real->id == $idtp tidak memiliki style="opacity: 0;",
     * sedangkan baris lain memiliki style="opacity: 0;".
     */
    public function test_baris_aktif_tidak_memiliki_opacity_0_sedangkan_baris_lain_memiliki_opacity_0(): void
    {
        $real1 = $this->createRealisasi(401, '1.1.01.01');
        $real2 = $this->createRealisasi(402, '1.1.01.04');
        $real3 = $this->createRealisasi(403, null);

        $activeId = 402;
        $html = $this->renderKartuAngsuran($activeId, [$real1, $real2, $real3]);

        $xpath = $this->parseXPath($html);

        $row1 = $this->findRealisasiRow($xpath, 401);
        $row2 = $this->findRealisasiRow($xpath, 402);
        $row3 = $this->findRealisasiRow($xpath, 403);

        $this->assertNotNull($row1);
        $this->assertNotNull($row2);
        $this->assertNotNull($row3);

        // Baris aktif (402) tidak memiliki opacity 0
        $this->assertStringNotContainsString('opacity: 0;', $row2->getAttribute('style'));
        $this->assertSame('TF-402', $this->getSignCellText($row2));

        // Baris non-aktif (401 dan 403) memiliki opacity 0
        $this->assertStringContainsString('opacity: 0;', $row1->getAttribute('style'));
        $this->assertSame('TN-401', $this->getSignCellText($row1));

        $this->assertStringContainsString('opacity: 0;', $row3->getAttribute('style'));
        $this->assertSame('TF-403', $this->getSignCellText($row3));
    }

    /**
     * Render dokumen cetak kartu angsuran dengan data mock.
     *
     * @param  array<int, object>  $realisasi
     */
    private function renderKartuAngsuran(int $idtp, array $realisasi): string
    {
        $data = $this->baseData($idtp, $realisasi);

        return view('perguliran.dokumen.cetak_kartu_angsuran', $data)->render();
    }

    /**
     * Bangun mock data untuk view cetak_kartu_angsuran.
     *
     * @param  array<int, object>  $realisasi
     * @return array<string, mixed>
     */
    private function baseData(int $idtp, array $realisasi): array
    {
        return [
            'laporan' => 'Kartu Angsuran Test',
            'barcode' => 'mock_barcode_base64',
            'idtp' => $idtp,
            'kec' => (object) [
                'nama_lembaga_sort' => 'UPK',
                'nama_kec' => 'Kecamatan Test',
                'alamat_kec' => 'Jl. Test No. 1',
                'telpon_kec' => '08123456789',
                'sebutan_level_1' => 'Direktur',
            ],
            'dir' => (object) [
                'namadepan' => 'Budi',
                'namabelakang' => 'Santoso',
            ],
            'pinkel' => (object) [
                'id' => 1,
                'kelompok' => (object) [
                    'kd_kelompok' => '001',
                    'nama_kelompok' => 'Kelompok Mawar',
                    'alamat_kelompok' => 'Desa Makmur',
                    'telpon' => '0811111111',
                    'ketua' => 'Siti',
                ],
                'jpp' => (object) ['nama_jpp' => 'SPP'],
                'sis_pokok' => (object) ['id' => '1', 'nama_sistem' => 'Bulanan'],
                'pinjaman_anggota_count' => 5,
                'tgl_cair' => '2026-01-15',
                'jangka' => 2,
                'alokasi' => 10000000,
                'pros_jasa' => 12,
                'target' => (object) [
                    'wajib_pokok' => 500000,
                    'wajib_jasa' => 50000,
                    'jatuh_tempo' => '2026-02-15',
                ],
                'rencana' => [
                    (object) ['angsuran_ke' => 1, 'jatuh_tempo' => '2026-02-15', 'wajib_pokok' => 500000, 'wajib_jasa' => 50000],
                    (object) ['angsuran_ke' => 2, 'jatuh_tempo' => '2026-03-15', 'wajib_pokok' => 500000, 'wajib_jasa' => 50000],
                ],
                'rencana_count' => 2,
                'real' => $realisasi,
            ],
        ];
    }

    /**
     * Buat object mock realisasi angsuran.
     */
    private function createRealisasi(int $id, ?string $rekeningDebit = null): object
    {
        return (object) [
            'id' => $id,
            'tgl_transaksi' => '2026-03-15',
            'realisasi_pokok' => 500000,
            'tunggakan_pokok' => 0,
            'realisasi_jasa' => 50000,
            'tunggakan_jasa' => 0,
            'saldo_pokok' => 9000000,
            'saldo_jasa' => 900000,
            'transaksi' => $rekeningDebit !== null ? (object) ['rekening_debit' => $rekeningDebit] : null,
        ];
    }

    private function parseXPath(string $html): \DOMXPath
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);

        return new \DOMXPath($dom);
    }

    private function findRealisasiRow(\DOMXPath $xpath, int $id): ?\DOMElement
    {
        $nodes = $xpath->query("//tr[td[last()][contains(., '-{$id}')]]");

        return $nodes->length > 0 ? $nodes->item(0) : null;
    }

    private function getSignCellText(\DOMElement $row): string
    {
        $xpath = new \DOMXPath($row->ownerDocument);
        $tds = $xpath->query('.//td[last()]', $row);

        return $tds->length > 0 ? trim($tds->item(0)->textContent) : '';
    }
}
