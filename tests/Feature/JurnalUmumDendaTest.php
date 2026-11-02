<?php

namespace Tests\Feature;

use Tests\TestCase;

class JurnalUmumDendaTest extends TestCase
{
    private function rekeningJenisSatu(): string
    {
        $source = file_get_contents(base_path('app/Http/Controllers/TransaksiController.php'));

        $start = strpos($source, 'public function rekening($id)');
        $this->assertNotFalse($start, 'Method rekening() tidak ditemukan.');

        $end = strpos($source, 'public function ', $start + 10);
        $body = substr($source, $start, $end - $start);

        $jenis1 = strpos($body, 'if ($id == 1) {');
        $this->assertNotFalse($jenis1, 'Cabang jenis 1 pada rekening() tidak ditemukan.');

        $jenis2 = strpos($body, '} elseif ($id == 2) {', $jenis1);
        $this->assertNotFalse($jenis2, 'Cabang jenis 2 pada rekening() tidak ditemukan.');

        return substr($body, $jenis1, $jenis2 - $jenis1);
    }

    private function activeLines(string $source): array
    {
        $lines = [];
        foreach (preg_split('/\R/', $source) as $line) {
            $trimmed = ltrim($line);
            if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*') || str_starts_with($trimmed, '/*')) {
                continue;
            }
            $lines[] = trim($line);
        }

        return $lines;
    }

    public function test_jenis_1_tidak_lagi_mengecualikan_semua_pendapatan_4_1_01(): void
    {
        $lines = $this->activeLines($this->rekeningJenisSatu());

        $hits = array_values(array_filter($lines, function ($line) {
            return str_contains($line, '4.1.01%') && str_contains($line, 'NOT LIKE');
        }));

        $this->assertSame([], $hits, 'Sumber Dana jenis 1 masih mengecualikan pendapatan jasa: '.implode(' | ', $hits));
    }

    public function test_jenis_1_tetap_mengecualikan_pendapatan_jasa(): void
    {
        $source = $this->rekeningJenisSatu();

        foreach (['4.1.01.01', '4.1.01.02', '4.1.01.03'] as $kode) {
            $this->assertMatchesRegularExpression(
                "/\['kode_akun',\s*'!=',\s*'".preg_quote($kode, '/')."'\s*\]/",
                $source,
                'Pendapatan jasa '.$kode.' harus tetap dikecualikan dari Sumber Dana jenis 1.'
            );
        }
    }

    public function test_rekening_denda_tidak_dikecualikan(): void
    {
        $source = $this->rekeningJenisSatu();

        foreach (['4.1.01.04', '4.1.01.05', '4.1.01.06'] as $kode) {
            $this->assertStringNotContainsString($kode, $source, 'Rekening denda '.$kode.' tidak boleh dikecualikan.');
        }
    }

    public function test_exclude_lain_jenis_1_tetap_utuh(): void
    {
        $source = $this->rekeningJenisSatu();

        foreach (['2.1.04.01', '2.1.04.02', '2.1.04.03', '2.1.02.01', '2.1.03.01'] as $kode) {
            $this->assertMatchesRegularExpression(
                "/\['kode_akun',\s*'!=',\s*'".preg_quote($kode, '/')."'\s*\]/",
                $source,
                'Exclude lama '.$kode.' harus dipertahankan pada jenis 1.'
            );
        }
    }
}
