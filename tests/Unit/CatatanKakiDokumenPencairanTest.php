<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CatatanKakiDokumenPencairanTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function disbursementReportProvider(): array
    {
        return [
            'SPK' => ['spk'],
            'berita acara pencairan' => ['BaPencairan'],
            'rencana angsuran' => ['rencanaAngsuran'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function excludedReportProvider(): array
    {
        return [
            'kwitansi' => ['kuitansi'],
            'kwitansi anggota' => ['kuitansiAnggota'],
            'cover pencairan' => ['coverPencairan'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonDisbursementReportProvider(): array
    {
        return [
            'form verifikasi' => ['check'],
            'profil kelompok proposal' => ['profilKelompok'],
        ];
    }

    #[DataProvider('disbursementReportProvider')]
    public function test_dokumen_pencairan_menampilkan_catatan_kaki(string $report): void
    {
        $html = $this->renderBaseLayout($report);

        $this->assertStringContainsString('No. SPK: 001/SPK/DBM/III/2026', $html);
        $this->assertStringContainsString('Tgl. Cair: 25 Maret 2026', $html);
    }

    #[DataProvider('excludedReportProvider')]
    public function test_dokumen_yang_dikecualikan_tidak_menampilkan_catatan_kaki(string $report): void
    {
        $html = $this->renderBaseLayout($report);

        $this->assertStringNotContainsString('No. SPK:', $html);
        $this->assertStringNotContainsString('Tgl. Cair:', $html);
    }

    #[DataProvider('nonDisbursementReportProvider')]
    public function test_dokumen_non_pencairan_tidak_menampilkan_catatan_kaki(string $report): void
    {
        $html = $this->renderBaseLayout($report, 'dokumen_proposal');

        $this->assertStringNotContainsString('No. SPK:', $html);
        $this->assertStringNotContainsString('Tgl. Cair:', $html);
    }

    public function test_catatan_kaki_kustom_diutamakan(): void
    {
        $this->assertStringContainsString(
            'Footer kustom',
            Blade::render(
                <<<'BLADE'
                @extends('perguliran.dokumen.layout.base')
                @section('content')
                    Konten dokumen
                @endsection
                @section('footer')
                    Footer kustom
                @endsection
                BLADE,
                $this->baseData('spk')
            )
        );
    }

    public function test_referensi_pinkel_utama_tidak_terpengaruh_loop_konten(): void
    {
        $data = $this->baseData('baPendanaan');
        $data['pinkel']->tgl_tunggu = '2026-03-20';
        $data['kec'] = (object) [
            'nama_lembaga_sort' => 'Lembaga Test',
            'sebutan_kec' => 'Kecamatan',
            'nama_kec' => 'Test',
            'sebutan_level_1' => 'Direktur',
        ];
        $data['pinjaman'] = [
            $this->pinjamanLain('999/SPK/DBM/III/2026', 'Kelompok Lain', '2026-03-31'),
            $this->pinjamanLain('998/SPK/DBM/III/2026', 'Kelompok Lainnya', '2026-03-30'),
        ];
        $data['pendanaan'] = [];
        $data['dir'] = (object) [
            'namadepan' => 'Direktur',
            'namabelakang' => 'Test',
        ];
        $data['tanda_tangan'] = '';

        $html = view('perguliran.dokumen.ba_pendanaan', $data)->render();

        $this->assertStringContainsString('No. SPK: 001/SPK/DBM/III/2026', $html);
        $this->assertStringContainsString('Tgl. Cair: 25 Maret 2026', $html);
        $this->assertStringContainsString('Kelompok Lain', $html);
        $this->assertStringNotContainsString('No. SPK: 999/SPK/DBM/III/2026', $html);
        $this->assertStringNotContainsString('No. SPK: 998/SPK/DBM/III/2026', $html);
    }

    private function pinjamanLain(string $spkNo, string $namaKelompok, string $tglCair): object
    {
        return (object) [
            'spk_no' => $spkNo,
            'tgl_cair' => $tglCair,
            'alokasi' => 1000000,
            'jangka' => 12,
            'pinjaman_anggota_count' => 5,
            'kelompok' => (object) [
                'nama_kelompok' => $namaKelompok,
                'alamat_kelompok' => 'Alamat Lain',
                'ketua' => 'Ketua Lain',
                'd' => (object) [
                    'nama_desa' => 'Desa Lain',
                    'sebutan_desa' => (object) ['sebutan_desa' => 'Desa'],
                ],
            ],
            'jpp' => (object) ['nama_jpp' => 'JPP Lain'],
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderBaseLayout(string $report, ?string $jenis = 'dokumen_pencairan'): string
    {
        return Blade::render(
            <<<'BLADE'
            @extends('perguliran.dokumen.layout.base')
            @section('content')
                Konten dokumen
            @endsection
            BLADE,
            $this->baseData($report, $jenis)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function baseData(string $report, ?string $jenis = 'dokumen_pencairan'): array
    {
        return [
            'type' => 'pdf',
            'judul' => 'DOKUMEN PENCAIRAN',
            'logo' => 'logo.png',
            'nama_lembaga' => 'Lembaga Test',
            'nama_kecamatan' => 'Kecamatan Test',
            'nomor_usaha' => 'SK Test',
            'info' => 'Info test',
            'report' => $report,
            'pinkel' => (object) [
                'spk_no' => '001/SPK/DBM/III/2026',
                'tgl_cair' => '2026-03-25',
            ],
            'jenis' => $jenis,
        ];
    }
}
