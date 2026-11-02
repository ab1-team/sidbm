<?php

namespace Tests\Feature;

use Tests\TestCase;

class SaldoSyncDisabledTest extends TestCase
{
    private function indexBody(): array
    {
        $source = file_get_contents(base_path('app/Http/Controllers/DashboardController.php'));

        return preg_split('/\R/', $source);
    }

    private function activeLinesContaining(string $needle): array
    {
        $hits = [];
        foreach ($this->indexBody() as $no => $line) {
            $trimmed = ltrim($line);
            if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*') || str_starts_with($trimmed, '/*')) {
                continue;
            }
            if (str_contains($line, $needle)) {
                $hits[] = ($no + 1).': '.trim($line);
            }
        }

        return $hits;
    }

    public function test_index_does_not_call_sync(): void
    {
        $source = file_get_contents(base_path('app/Http/Controllers/DashboardController.php'));

        // Fokus hanya isi index(): potong dari deklarasi method sampai method berikutnya.
        preg_match('/public function index\(\)\s*\{(.*?)\n    \}/s', $source, $m);
        $this->assertNotEmpty($m, 'Method index() tidak ditemukan.');

        $active = [];
        foreach (preg_split('/\R/', $m[1]) as $line) {
            $trimmed = ltrim($line);
            if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*') || str_starts_with($trimmed, '/*')) {
                continue;
            }
            if (preg_match('/\$this->sync\s*\(/', $line)) {
                $active[] = trim($line);
            }
        }

        $this->assertSame([], $active, 'index() masih memanggil $this->sync( secara aktif: '.implode(' | ', $active));
        $this->assertSame([], $this->activeLinesContaining('$this->sync('), 'Masih ada pemanggilan $this->sync( aktif di DashboardController.');
    }

    public function test_sync_call_is_commented_with_marker(): void
    {
        $source = file_get_contents(base_path('app/Http/Controllers/DashboardController.php'));

        $this->assertStringContainsString('[2026-11] sync() dinonaktifkan', $source);
        $this->assertMatchesRegularExpression('/\/\/\s*\$this->sync\s*\(/', $source, 'Pemanggilan sync() harus tetap ada sebagai komentar (route /sync/{lokasi} tetap hidup).');
    }

    public function test_sync_method_still_exists(): void
    {
        $source = file_get_contents(base_path('app/Http/Controllers/DashboardController.php'));

        $this->assertMatchesRegularExpression('/public function sync\s*\(/', $source, 'Method sync() tidak boleh dihapus.');
    }
}
