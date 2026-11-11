<?php

namespace Tests\Feature;

use App\Services\ProvisiPencairanService;
use Tests\TestCase;

class ProvisiPencairanWedungTest extends TestCase
{
    private function konteks(array $override = []): array
    {
        return array_merge([
            'lokasi' => 15,
            'status_asal' => 'W',
            'status_request' => 'A',
            'alokasi_pencairan' => '108000000',
            'tgl_transaksi' => '2026-11-10',
            'id_pinj' => 987,
            'nama_kelompok' => 'DBM Wedung',
            'ketua_kelompok' => 'Sutrisno',
            'id_user' => 5,
        ], $override);
    }

    public function test_nominal_provisi_setengah_persen_dibulatkan_round(): void
    {
        $this->assertSame(540000, ProvisiPencairanService::nominal('108000000'));
        $this->assertSame(575000, ProvisiPencairanService::nominal('115000000'));
        $this->assertSame(540000, ProvisiPencairanService::nominal('108,000,000.00'));
        $this->assertSame(536, ProvisiPencairanService::nominal('107200'));
    }

    public function test_payload_jurnal_provisi_lengkap_dan_benar(): void
    {
        $payload = ProvisiPencairanService::payload($this->konteks());

        $this->assertSame('1.1.01.01', $payload['rekening_debit']);
        $this->assertSame('4.1.01.07', $payload['rekening_kredit']);
        $this->assertSame('540000', $payload['jumlah']);
        $this->assertSame(987, $payload['id_pinj']);
        $this->assertSame('0', $payload['id_pinj_i']);
        $this->assertSame('0', $payload['idtp']);
        $this->assertSame('0', $payload['urutan']);
        $this->assertSame(5, $payload['id_user']);
        $this->assertSame('2026-11-10', $payload['tgl_transaksi']);
        $this->assertStringContainsString('Provisi 0,5%', $payload['keterangan_transaksi']);
        $this->assertStringContainsString('DBM Wedung', $payload['keterangan_transaksi']);
        $this->assertSame('DBM Wedung [987] Sutrisno', $payload['relasi']);
    }

    public function test_guard_tenant_dan_status_asal(): void
    {
        $this->assertTrue(ProvisiPencairanService::berlaku(15, 'W', 'A'));
        $this->assertFalse(ProvisiPencairanService::berlaku(14, 'W', 'A'));
        $this->assertFalse(ProvisiPencairanService::berlaku(0, 'W', 'A'));
        $this->assertFalse(ProvisiPencairanService::berlaku(15, 'A', 'A'));
        $this->assertFalse(ProvisiPencairanService::berlaku(15, 'V', 'W'));
    }

    public function test_call_site_status_a_memakai_service_setelah_jurnal_pencairan(): void
    {
        $source = file_get_contents(base_path('app/Http/Controllers/PinjamanKelompokController.php'));
        $start = strpos($source, "\n        if (\$request->status == 'A') {");
        $this->assertNotFalse($start);
        $end = strpos($source, '} elseif ($request->status == \'W\') {', $start);
        $this->assertNotFalse($end);
        $blok = substr($source, $start, $end - $start);

        $posJurnalUtama = strpos($blok, 'Transaksi::create([');
        $posService = strpos($blok, 'ProvisiPencairanService::catat(');
        $this->assertNotFalse($posJurnalUtama);
        $this->assertNotFalse($posService);
        $this->assertGreaterThan($posJurnalUtama, $posService, 'Provisi harus dicatat setelah jurnal pencairan utama.');

        foreach ([
            "'lokasi' => Session::get('lokasi')",
            "'status_asal' => \$perguliran->status",
            "'status_request' => \$request->status",
            "'alokasi_pencairan' => \$alokasi_pencairan",
            "'tgl_transaksi' => Tanggal::tglNasional(\$data[\$tgl])",
            "'id_pinj' => \$perguliran->id",
            "'nama_kelompok' => \$perguliran->kelompok->nama_kelompok",
        ] as $wajib) {
            $this->assertStringContainsString($wajib, $blok);
        }

        // Jurnal pencairan utama tidak berubah: reversal rekening + nominal alokasi utuh.
        $this->assertStringContainsString("'rekening_debit' => (string) \$request->debet", $blok);
        $this->assertStringContainsString("'rekening_kredit' => (string) \$request->sumber_pembayaran", $blok);
        $this->assertSame(1, substr_count($blok, 'Transaksi::create(['));
        $this->assertStringNotContainsString('1.1.01.01', explode('ProvisiPencairanService::catat', $blok)[0]);
        $this->assertStringNotContainsString("'alokasi' => \$alokasi -", $blok);
    }
}
