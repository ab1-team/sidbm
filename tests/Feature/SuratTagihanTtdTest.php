<?php

namespace Tests\Feature;

use App\Http\Controllers\Kabupaten\KabupatenController;
use App\Http\Controllers\PinjamanKelompokController;
use App\Models\Kabupaten;
use App\Models\Kecamatan;
use App\Models\PinjamanKelompok;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Session;
use Tests\TestCase;

class SuratTagihanTtdTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Overlay: arahkan koneksi DB tenant ke SQLite in-memory (skema kosong)
        // agar test tidak bergantung pada server MySQL tenant.
        foreach (['mysql', 'mysql_b'] as $connection) {
            $this->app['config']->set(
                "database.connections.{$connection}",
                [
                    'driver' => 'sqlite',
                    'database' => ':memory:',
                    'prefix' => '',
                    'foreign_key_constraints' => false,
                ]
            );
            DB::purge($connection);
        }

        // Model PinjamanKelompok memakai suffix tenant; samakan dengan tabel di bawah.
        config(['tenant.suffix' => '_9001']);

        foreach (['mysql', 'mysql_b'] as $connection) {
            Schema::connection($connection)->create('kabupaten', function ($table) {
                $table->increments('id');
                $table->string('kd_kab')->nullable();
                $table->string('logo')->nullable();
            });

            Schema::connection($connection)->create('kelompok_9001', function ($table) {
                $table->increments('id');
                $table->string('nama_kelompok')->nullable();
                $table->string('ketua')->nullable();
                $table->string('alamat_kelompok')->nullable();
            });

            Schema::connection($connection)->create('pinjaman_kelompok_9001', function ($table) {
                $table->increments('id');
                $table->integer('id_kel')->nullable();
            });

            Schema::connection($connection)->create('pinjaman_anggota_9001', function ($table) {
                $table->increments('id');
                $table->integer('id_pinkel')->nullable();
            });

            Schema::connection($connection)->create('dokumen_pinjaman', function ($table) {
                $table->increments('id');
                $table->string('file')->nullable();
                $table->string('jenis_dokumen')->nullable();
            });
        }

        Storage::fake('enstorage');
    }

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

    public function test_storage_to_base64_helper_converts_enstorage_file(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        Http::fake([
            'https://enstorage.enpiistudio.com/storage/v1/object/public/ttd_tagihan/301.png' => Http::response($png, 200),
        ]);

        $controller = app(PinjamanKelompokController::class);
        $method = new \ReflectionMethod($controller, 'storageToBase64');
        $method->setAccessible(true);

        $this->assertSame(
            'data:image/png;base64,'.base64_encode($png),
            $method->invoke($controller, 'https://enstorage.enpiistudio.com/storage/v1/object/public/ttd_tagihan/301.png')
        );
    }

    public function test_supabase_to_base64_helper_delegates_to_storage_helper(): void
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

    public function test_simpan_logo_stores_upload_in_logo_kab_directory(): void
    {
        Session::put('kd_kab', '301');
        Session::put('lokasi', 301);
        Kabupaten::forceCreate(['id' => 301, 'kd_kab' => '301', 'logo' => null]);

        $disk = Storage::fake('enstorage');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        $file = UploadedFile::fake()->createWithContent('logo.png', $png);

        $response = app(KabupatenController::class)->simpanLogo(
            \Illuminate\Http\Request::create('/kabupaten/logo', 'POST', [], [], ['logo' => $file])
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('/logo_kab/', (string) $response->getData(true)['path']);
        $files = $disk->allFiles('logo_kab');
        $this->assertCount(1, $files);
        $this->assertStringStartsWith('logo_kab/', $files[0]);
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
