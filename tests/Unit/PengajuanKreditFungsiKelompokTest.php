<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Teks di dalam kurung setelah nama kelompok pada surat permohonan kredit
 * harus mengikuti fungsi kelompok:
 * - executing (fungsi_kelompok = '2') -> "(executing)"
 * - channeling (fungsi_kelompok = '1') -> tetap "(daftar anggota terlampir)"
 */
class PengajuanKreditFungsiKelompokTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function fungsiKelompokProvider(): array
    {
        return [
            'executing' => ['2', '(executing)'],
            'channeling' => ['1', '(daftar anggota terlampir)'],
        ];
    }

    #[DataProvider('fungsiKelompokProvider')]
    public function test_teks_dalam_kurung_mengikuti_fungsi_kelompok(string $fungsiKelompok, string $teksDiharapkan): void
    {
        $html = $this->normalizeSpasi($this->renderPengajuanKredit($fungsiKelompok));

        $this->assertStringContainsString(
            'ANGGREK PUTIH '.$teksDiharapkan.', dengan ini bermaksud',
            $html,
            'Fungsi kelompok '.$fungsiKelompok.' harus merender '.$teksDiharapkan
        );
    }

    public function test_executing_tidak_menampilkan_daftar_anggota_terlampir(): void
    {
        $html = $this->normalizeSpasi($this->renderPengajuanKredit('2'));

        $this->assertStringNotContainsString('ANGGREK PUTIH (daftar anggota terlampir)', $html);
    }

    public function test_channeling_tidak_menampilkan_executing(): void
    {
        $html = $this->normalizeSpasi($this->renderPengajuanKredit('1'));

        $this->assertStringNotContainsString('ANGGREK PUTIH (executing)', $html);
    }

    /**
     * Render Blade memecah teks menjadi banyak baris/indentasi. Normalisasi
     * whitespace membuat asersi fokus pada logika teks, bukan format render.
     */
    private function normalizeSpasi(string $html): string
    {
        $tanpaTag = strip_tags($html);
        $tanpaEntitas = html_entity_decode($tanpaTag, ENT_QUOTES, 'UTF-8');

        return trim((string) preg_replace('/\s+/', ' ', $tanpaEntitas));
    }

    private function renderPengajuanKredit(string $fungsiKelompok): string
    {
        $data = [
            'judul' => 'Surat Permohonan Kredit SPP',
            'nama_kabupaten' => 'Kabupaten Test',
            'tanda_tangan' => '',
            'kec' => (object) [
                'nama_lembaga_sort' => 'Bumdesma Negara Lkd',
                'nama_kec' => 'Negara',
                'alamat_kec' => 'Jl. Test No. 1',
                'sebutan_level_1' => 'Direktur',
                'sebutan_kec' => 'Kecamatan',
            ],
            'pinkel' => (object) [
                'id' => 1,
                'jenis_pp' => '1',
                'struktur_kelompok' => null,
                'tgl_proposal' => '2026-11-17',
                'jpp' => (object) [
                    'nama_jpp' => 'SPP',
                    'deskripsi_jpp' => 'Simpan Pinjam Khusus Perempuan',
                ],
                'kelompok' => (object) [
                    'kd_kelompok' => '001',
                    'nama_kelompok' => 'ANGGREK PUTIH',
                    'alamat_kelompok' => 'Loloan Barat',
                    'telpon' => '08123456789',
                    'ketua' => 'Mujayanah',
                    'sekretaris' => 'Pahriah',
                    'fungsi_kelompok' => $fungsiKelompok,
                    'd' => (object) [
                        'nama_desa' => 'Loloan Barat',
                        'sebutan_desa' => (object) ['sebutan_desa' => 'Desa'],
                    ],
                ],
                'proposal' => 125000000,
                'jangka' => 12,
                'sis_pokok' => (object) [
                    'id' => '1',
                    'nama_sistem' => 'Bulanan',
                    'deskripsi_sistem' => 'Angsuran bulanan',
                ],
                'pinjaman_anggota_count' => 10,
            ],
        ];

        return view('perguliran.dokumen.pengajuan_kredit', $data)->render();
    }
}
