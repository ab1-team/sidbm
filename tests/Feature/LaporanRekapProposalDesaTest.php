<?php

namespace Tests\Feature;

use App\Http\Controllers\PelaporanController;
use ReflectionClass;
use Tests\TestCase;

class LaporanRekapProposalDesaTest extends TestCase
{
    private function migrationSource(): string
    {
        $files = glob(database_path('migrations/*_add_rekap_proposal_desa_to_jenis_laporan_pinjaman.php'));
        $this->assertNotEmpty($files, 'Migration rekap_proposal_desa tidak ditemukan.');

        return file_get_contents($files[0]);
    }

    public function test_method_rekap_proposal_desa_ada(): void
    {
        $reflection = new ReflectionClass(PelaporanController::class);

        $this->assertTrue($reflection->hasMethod('rekap_proposal_desa'), 'Method rekap_proposal_desa() belum ada.');
        $this->assertTrue($reflection->getMethod('rekap_proposal_desa')->isPrivate());
    }

    public function test_view_rekap_proposal_desa_ada(): void
    {
        $path = resource_path('views/pelaporan/view/perkembangan_piutang/rekap_proposal_desa.blade.php');

        $this->assertFileExists($path);
        $this->assertStringContainsString('pelaporan.layout.base', file_get_contents($path));
    }

    public function test_method_merender_view_rekap_proposal_desa(): void
    {
        $source = file_get_contents(base_path('app/Http/Controllers/PelaporanController.php'));

        $this->assertStringContainsString("view('pelaporan.view.perkembangan_piutang.rekap_proposal_desa'", $source);
    }

    public function test_rekap_memuat_proposal_aktif_sampai_tanggal_kondisi(): void
    {
        $source = file_get_contents(base_path('app/Http/Controllers/PelaporanController.php'));
        $start = strpos($source, 'private function rekap_proposal_desa');
        $end = strpos($source, 'private function waiting_list', $start);
        $method = substr($source, $start, $end - $start);

        $this->assertStringContainsString("whereDate(\$data['tb_pinkel'].'.tgl_proposal', '<=', \$data['tgl_kondisi'])", $method);
        $this->assertStringNotContainsString('whereBetween', $method, 'Rekap adalah snapshot proposal aktif, bukan daftar proposal baru pada periode terpilih.');
        $this->assertStringContainsString("sistem_angsuran', '!=', '12'", $method, 'Proposal mingguan tetap dipisahkan dari rekap bulanan.');
    }

    public function test_migration_mendaftarkan_rekap_proposal_desa(): void
    {
        $source = $this->migrationSource();

        $this->assertStringContainsString('firstOrCreate', $source, 'Migration harus idempoten memakai firstOrCreate.');
        $this->assertStringContainsString("'rekap_proposal_desa'", $source);
        $this->assertStringContainsString("'Rekap Proposal Per Desa'", $source);
        $this->assertMatchesRegularExpression('/public function down\(\)/', $source);
    }
}
