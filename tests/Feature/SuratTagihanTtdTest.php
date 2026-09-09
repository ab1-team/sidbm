<?php

namespace Tests\Feature;

use App\Http\Controllers\PinjamanKelompokController;
use App\Models\Kecamatan;
use App\Models\PinjamanKelompok;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Mockery;
use Session;
use Tests\TestCase;

class SuratTagihanTtdTest extends TestCase
{
    public function test_ttd_tagihan_image_helper_converts_supabase_file(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        Http::fake([
            'http://supabase.test/ttd_tagihan/301.png' => Http::response($png, 200),
        ]);

        $controller = app(PinjamanKelompokController::class);
        $method = new \ReflectionMethod($controller, 'supabaseToBase64');
        $method->setAccessible(true);

        $this->assertSame(
            'data:image/png;base64,'.base64_encode($png),
            $method->invoke($controller, 'http://supabase.test/ttd_tagihan/301.png')
        );
    }

    public function test_view_renders_base64_when_ttd_tagihan_is_filled(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        Http::fake([
            'http://supabase.test/ttd_tagihan/301.png' => Http::response($png, 200),
        ]);

        Session::put('lokasi', 301);
        $fixture = $this->fixture([
            'ttd_tagihan' => 'http://supabase.test/ttd_tagihan/301.png',
        ]);
        $fixture['ttd_tagihan_img'] = 'data:image/png;base64,AAA';
        $this->assertNotEmpty($fixture['ttd_tagihan_img']);
        $view = view('perguliran.dokumen.tagihan', $fixture)->render();

        $this->assertStringContainsString('data:image/png;base64,', $view);
        $this->assertStringNotContainsString('http://supabase.test/ttd_tagihan/301.png', $view);
    }

    public function test_view_renders_without_error_when_ttd_tagihan_is_empty(): void
    {
        Session::put('lokasi', 301);
        $view = view('perguliran.dokumen.tagihan', $this->fixture([
            'ttd_tagihan' => null,
        ]))->render();

        $this->assertStringNotContainsString('ttd_tagihan_img', $view);
    }

    private function fixture(array $kecamatan): array
    {
        $kelompok = Mockery::mock();
        $kelompok->nama_kelompok = 'Kelompok Test';
        $kelompok->ketua = 'Ketua Test';
        $kelompok->struktur_kelompok = null;
        $kelompok->alamat_kelompok = 'Alamat';
        $kelompok->d = (object) ['nama_desa' => 'Desa Test'];

        $pinkel = new PinjamanKelompok();
        $pinkel->id = 9001;
        $pinkel->struktur_kelompok = null;
        $pinkel->alokasi = 1000000;
        $pinkel->pros_jasa = 10;
        $pinkel->jangka = 10;
        $pinkel->tgl_cair = '2026-01-01';
        $pinkel->kelompok = $kelompok;
        $pinkel->jpp = (object) ['nama_jpp' => 'Produk Test'];
        $pinkel->sis_pokok = (object) ['nama_sistem' => 'Sistem Test'];

        $kec = new Kecamatan();
        $kec->id = 301;
        $kec->nama_kec = 'Test';
        $kec->sebutan_level_1 = 'Kepala';
        $kec->nama_lembaga_sort = 'DBM Test';
        foreach ($kecamatan as $key => $value) {
            $kec->{$key} = $value;
        }

        $dir = new User();
        $dir->namadepan = 'Budi';
        $dir->namabelakang = 'Santoso';

        return [
            'pinkel' => $pinkel,
            'kec' => $kec,
            'dir' => $dir,
            'report' => '',
            'logo' => 'data:image/png;base64,',
            'nama_lembaga' => 'DBM Test',
            'nama_kecamatan' => 'Kecamatan Test',
            'nomor_usaha' => 'SK Test',
            'info' => 'Info Test',
            'real' => null,
            'ra' => null,
            'type' => 'html',
            'judul' => 'Surat Tagihan Test',
            'ttd_tagihan_img' => null,
        ];
    }
}
