<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CatatanKakiDokumenPencairanTest extends TestCase
{
    /**
     * Semua report yang lewat layout ini tunduk pada aturan status yang sama.
     *
     * @return array<int, string>
     */
    private const REPORTS = [
        'spk', 'kuitansi', 'coverPencairan', 'kartu_angsuran',
        'check', 'profilKelompok', 'pernyataanPeminjam', 'rencanaAngsuran',
    ];

    /**
     * @return array<string, array{string, string}>
     */
    public static function reportAgnosticPositiveProvider(): array
    {
        return self::cross(['W', 'A', 'L']);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function reportAgnosticNegativeProvider(): array
    {
        return self::cross(['P', 'V', 'T', 'R', 'H']);
    }

    /**
     * @param  array<int, string>  $statuses
     * @return array<string, array{string, string}>
     */
    private static function cross(array $statuses): array
    {
        $cases = [];
        foreach (self::REPORTS as $report) {
            foreach ($statuses as $status) {
                $cases[$report.' '.$status] = [$report, $status];
            }
        }

        return $cases;
    }

    #[DataProvider('reportAgnosticPositiveProvider')]
    public function test_semua_report_status_w_a_l_menampilkan_catatan_kaki(string $report, string $status): void
    {
        $html = $this->renderBaseLayout($report, 'dokumen_pencairan', $status);

        $this->assertStringContainsString('No. SPK:', $html);
        $this->assertStringContainsString('Tgl. Cair:', $html);
    }

    #[DataProvider('reportAgnosticNegativeProvider')]
    public function test_semua_report_selain_w_a_l_tidak_menampilkan_catatan_kaki(string $report, string $status): void
    {
        $html = $this->renderBaseLayout($report, 'dokumen_pencairan', $status);

        $this->assertStringNotContainsString('No. SPK:', $html);
        $this->assertStringNotContainsString('Tgl. Cair:', $html);
    }

    public function test_jenis_tidak_berpengaruh_pada_status_w(): void
    {
        foreach (['dokumen_proposal', 'dokumen_pencairan', null] as $jenis) {
            $html = $this->renderBaseLayout('spk', $jenis, 'W');

            $this->assertStringContainsString('No. SPK:', $html, 'jenis: '.var_export($jenis, true));
            $this->assertStringContainsString('Tgl. Cair:', $html, 'jenis: '.var_export($jenis, true));
        }
    }

    public function test_jenis_tidak_berpengaruh_pada_status_p(): void
    {
        foreach (['dokumen_proposal', 'dokumen_pencairan', null] as $jenis) {
            $html = $this->renderBaseLayout('spk', $jenis, 'P');

            $this->assertStringNotContainsString('No. SPK:', $html, 'jenis: '.var_export($jenis, true));
            $this->assertStringNotContainsString('Tgl. Cair:', $html, 'jenis: '.var_export($jenis, true));
        }
    }

    public function test_status_pinkel_menang_atas_query_string_w_tidak_tampil(): void
    {
        $data = $this->baseData('spk', 'dokumen_pencairan', 'P');
        $data['status'] = 'W';

        $html = $this->renderBlade($data);

        $this->assertStringNotContainsString('No. SPK:', $html);
        $this->assertStringNotContainsString('Tgl. Cair:', $html);
    }

    public function test_status_pinkel_menang_atas_query_string_p_tetap_tampil(): void
    {
        $data = $this->baseData('spk', 'dokumen_pencairan', 'W');
        $data['status'] = 'P';

        $html = $this->renderBlade($data);

        $this->assertStringContainsString('No. SPK:', $html);
        $this->assertStringContainsString('Tgl. Cair:', $html);
    }

    public function test_pinkel_null_tidak_error_dan_tanpa_catatan_kaki(): void
    {
        $data = $this->baseData('spk', 'dokumen_pencairan', 'W');
        $data['pinkel'] = null;

        $html = $this->renderBlade($data);

        $this->assertStringNotContainsString('No. SPK:', $html);
        $this->assertStringNotContainsString('Tgl. Cair:', $html);
    }

    #[DataProvider('customFooterProvider')]
    public function test_footer_kustom_diutamakan(string $status): void
    {
        $html = Blade::render(
            <<<'BLADE'
            @extends('perguliran.dokumen.layout.base')
            @section('content')
                Konten dokumen
            @endsection
            @section('footer')
                Footer kustom
            @endsection
            BLADE,
            $this->baseData('spk', 'dokumen_pencairan', $status)
        );

        $this->assertStringContainsString('Footer kustom', $html);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function customFooterProvider(): array
    {
        return [
            'status W' => ['W'],
            'status P' => ['P'],
        ];
    }

    private function renderBaseLayout(string $report, ?string $jenis = 'dokumen_pencairan', ?string $status = 'W'): string
    {
        return $this->renderBlade($this->baseData($report, $jenis, $status));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function renderBlade(array $data): string
    {
        return Blade::render(
            <<<'BLADE'
            @extends('perguliran.dokumen.layout.base')
            @section('content')
                Konten dokumen
            @endsection
            BLADE,
            $data
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function baseData(string $report, ?string $jenis = 'dokumen_pencairan', ?string $status = 'W'): array
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
                'status' => $status,
            ],
            'jenis' => $jenis,
            'status' => $status,
        ];
    }
}
