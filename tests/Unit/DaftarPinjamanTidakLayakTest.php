<?php

namespace Tests\Unit;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PelaporanController;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

/**
 * Verifikasi fitur "Daftar Pinjaman Tidak Layak (Kelompok)" pada SIDBM Legacy:
 *  - Struktur Blade dashboard/index.blade.php memuat tab #tidak_layak & tbody #tbTidakLayak.
 *  - View pelaporan.view.perkembangan_piutang.tidak_layak dapat dirender tanpa error.
 *  - Method DashboardController::pinjaman() menangani status 'T' (tgl_tunggu/alokasi).
 *  - Method PelaporanController::tidak_layak() aman terhadap data kosong.
 *
 * Semua test bebas DB (tanpa koneksi mysql).
 */
class DaftarPinjamanTidakLayakTest extends TestCase
{
    private function dashboardBlade(): string
    {
        return file_get_contents(resource_path('views/dashboard/index.blade.php'));
    }

    public function test_blade_dashboard_memiliki_tab_dan_tbody_tidak_layak()
    {
        $blade = $this->dashboardBlade();

        $this->assertStringContainsString('href="#tidak_layak"', $blade);
        $this->assertStringContainsString('aria-controls="tidak_layak"', $blade);
        $this->assertStringContainsString('id="tidak_layak"', $blade);
        $this->assertStringContainsString('id="tbTidakLayak"', $blade);
        $this->assertStringContainsString('Tidak Layak', $blade);
    }

    public function test_blade_dashboard_memiliki_kartu_pinjaman_tidak_layak()
    {
        $blade = $this->dashboardBlade();

        // Kartu khusus "Pinjaman Tidak Layak" harus tampil di permukaan dashboard (bukan hanya di modal).
        $this->assertStringContainsString('id="btnTidakLayak"', $blade);
        $this->assertStringContainsString('Pinjaman Tidak Layak', $blade);
        $this->assertStringContainsString('tidak layak didanai', $blade);
        $this->assertStringContainsString('{{ $tidak_layak }} Kelompok', $blade);
        $this->assertStringContainsString('Status T', $blade);

        // Click handler kartu tidak layak: buka modal, pilih tab, set laporan.
        $this->assertStringContainsString("$(document).on('click', '#btnTidakLayak'", $blade);
        $this->assertStringContainsString(
            '$(\'#pinjaman .nav-pills a[href="#tidak_layak"]\').tab(\'show\')',
            $blade
        );
        $this->assertStringContainsString("setLaporan('5', 'tidak_layak')", $blade);
    }

    public function test_ajax_dashboard_memuat_pinjaman_status_t()
    {
        $blade = $this->dashboardBlade();

        $this->assertStringContainsString("'/dashboard/pinjaman?status=T'", $blade);
        $this->assertStringContainsString("\$('#tbTidakLayak').html(result.table)", $blade);
    }

    public function test_view_pelaporan_tidak_layak_render_tanpa_error()
    {
        $jenis_pp = [
            (object) [
                'nama_jpp' => 'SPP',
                'pinjaman_kelompok' => collect([
                    (object) [
                        'kd_desa' => 'D001',
                        'kode_desa' => '01',
                        'nama_desa' => 'Sukamaju',
                        'sebutan_desa' => 'Desa',
                        'jenis_pp' => 2,
                        'alokasi' => 5000000,
                        'nama_kelompok' => 'Kelompok Makmur',
                        'id' => 123,
                        'tgl_tunggu' => '2026-01-15',
                    ],
                ]),
            ],
        ];

        $html = view('pelaporan.view.perkembangan_piutang.tidak_layak', [
            'type' => 'html',
            'laporan' => 'tidak_layak',
            'tgl' => '2026',
            'sub_judul' => 'Tahun 2026',
            'tanda_tangan' => '',
            'logo' => '',
            'nama_lembaga' => 'Lembaga Test',
            'nama_kecamatan' => 'Kecamatan Test',
            'nomor_usaha' => 'SK Test',
            'info' => 'Info Test',
            'jenis_pp' => $jenis_pp,
        ])->render();

        $this->assertStringContainsString('DAFTAR PINJAMAN TIDAK LAYAK SPP', $html);
        $this->assertStringContainsString('Kelompok Makmur', $html);
        $this->assertStringContainsString('Sukamaju', $html);
        $this->assertStringContainsString('5,000,000.00', $html);
    }

    public function test_view_pelaporan_tidak_layak_render_dengan_data_kosong()
    {
        $html = view('pelaporan.view.perkembangan_piutang.tidak_layak', [
            'type' => 'html',
            'laporan' => 'tidak_layak',
            'tgl' => '2026',
            'sub_judul' => 'Tahun 2026',
            'tanda_tangan' => '',
            'logo' => '',
            'nama_lembaga' => 'Lembaga Test',
            'nama_kecamatan' => 'Kecamatan Test',
            'nomor_usaha' => 'SK Test',
            'info' => 'Info Test',
            'jenis_pp' => collect(),
        ])->render();

        $this->assertIsString($html);
        $this->assertStringNotContainsString('DAFTAR PINJAMAN TIDAK LAYAK', $html);
    }

    public function test_dashboard_pinjaman_menangani_status_t()
    {
        $method = new \ReflectionMethod(DashboardController::class, 'pinjaman');
        $src = file_get_contents((new \ReflectionClass(DashboardController::class))->getFileName());

        $this->assertNotNull($method);
        $this->assertStringContainsString("elseif (\$status == 'T') {", $src);
        $this->assertStringContainsString("warna_status ?? 'danger'", $src);
    }

    public function test_sub_laporan_memuat_tidak_layak()
    {
        // Tanpa migration: opsi tidak_layak harus selalu ada pada dropdown sub laporan
        // menu Laporan Perkembangan Piutang ($file = 5).
        // Test bebas DB: model di-mock sehingga tidak menyentuh koneksi mysql.
        $jenisLaporanPinjaman = Mockery::mock('alias:'.\App\Models\JenisLaporanPinjaman::class);
        $jenisLaporanPinjaman->shouldReceive('where->orderBy->get')->andReturn(collect());

        $jenisLaporan = Mockery::mock('alias:'.\App\Models\JenisLaporan::class);
        $jenisLaporan->shouldReceive('where->first')->andReturn(null);

        $controller = app(PelaporanController::class);

        request()->merge([
            'tahun' => '2026',
            'bulan' => '01',
        ]);

        $view = $controller->subLaporan(5);
        $html = $view->render();

        $this->assertStringContainsString('value="tidak_layak"', $html);
        $this->assertStringContainsString('Daftar Pinjaman Tidak Layak', $html);
    }

    public function test_pelaporan_tidak_layak_aman_tanpa_kec()
    {
        // Tanpa DB: method dibangun agar tidak query JenisProdukPinjaman jika kec kosong,
        // sehingga hanya view yang dirender (tidak menyentuh koneksi mysql).
        $controller = app(PelaporanController::class);

        $out = $controller->tidak_layak([
            'tahun' => '2026',
            'bulan' => '01',
            'hari' => '15',
            'bulanan' => true,
            'type' => 'html',
            'laporan' => 'tidak_layak',
            'kec' => null,
            'tgl' => '2026',
            'sub_judul' => 'Tahun 2026',
            'logo' => '',
            'nama_lembaga' => 'Lembaga Test',
            'nama_kecamatan' => 'Kecamatan Test',
            'nomor_usaha' => 'SK Test',
            'info' => 'Info Test',
        ]);

        $this->assertIsString($out);
        $this->assertSame(0, DB::connection()->transactionLevel());
    }
}
