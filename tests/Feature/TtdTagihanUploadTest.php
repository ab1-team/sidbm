<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\Testing\File as TestFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TtdTagihanUploadTest extends TestCase
{
    private string $dbFile;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('SUPABASE_PUBLIC_URL=http://supabase.test');

        $this->dbFile = tempnam(sys_get_temp_dir(), 'ttdtest').'.sqlite';
        touch($this->dbFile);

        foreach (['mysql', 'mysql_b'] as $connection) {
            config(["database.connections.{$connection}.driver" => 'sqlite']);
            config(["database.connections.{$connection}.database" => $this->dbFile]);
            DB::purge($connection);
        }

        DB::connection('mysql')->statement(
            'CREATE TABLE IF NOT EXISTS kecamatan (id INTEGER PRIMARY KEY, ttd_tagihan TEXT NULL)'
        );
        DB::connection('mysql')->statement(
            'CREATE TABLE IF NOT EXISTS kabupaten (id INTEGER PRIMARY KEY)'
        );
        DB::connection('mysql')->statement(
            'CREATE TABLE IF NOT EXISTS admin_invoice (id INTEGER PRIMARY KEY, lokasi INTEGER NULL, status TEXT NULL, tgl_invoice TEXT NULL, tgl_lunas TEXT NULL)'
        );

        DB::connection('mysql')->table('kecamatan')->insert(['id' => 301]);
    }

    protected function tearDown(): void
    {
        foreach (['mysql', 'mysql_b'] as $connection) {
            DB::purge($connection);
        }

        if (isset($this->dbFile) && file_exists($this->dbFile)) {
            @unlink($this->dbFile);
        }

        parent::tearDown();
    }

    public function test_upload_stores_file_and_updates_kecamatan_url(): void
    {
        Storage::fake('supabase');
        $image = TestFile::fake()->image('ttd.png');

        $user = User::factory()->make(['id' => 1, 'lokasi' => 301]);
        $this->app['auth']->guard('web')->setUser($user);
        $this->app['auth']->shouldUse('web');
        Session::put('lokasi', 301);

        $response = $this->post('/pengaturan/tanda_tangan/tagihan', [
            'ttd_tagihan' => $image,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'msg' => 'Tanda tangan & stempel surat tagihan berhasil disimpan.',
            'path' => 'http://supabase.test/ttd_tagihan/301.png',
        ]);
        Storage::disk('supabase')->assertExists('ttd_tagihan/301.png');

        foreach (['mysql', 'mysql_b'] as $connection) {
            $this->assertSame(
                'http://supabase.test/ttd_tagihan/301.png',
                DB::connection($connection)->table('kecamatan')->where('id', 301)->value('ttd_tagihan')
            );
        }
    }

    public function test_upload_without_file_returns_validation_error(): void
    {
        $user = User::factory()->make(['id' => 1, 'lokasi' => 301]);
        $this->app['auth']->guard('web')->setUser($user);
        $this->app['auth']->shouldUse('web');
        Session::put('lokasi', 301);

        $response = $this->post('/pengaturan/tanda_tangan/tagihan', []);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'msg' => 'ttd tagihan tidak boleh kosong.',
        ]);
    }

    public function test_unauthenticated_user_cannot_upload(): void
    {
        $response = $this->post('/pengaturan/tanda_tangan/tagihan', []);
        $response->assertRedirect('/');

        $jsonResponse = $this->postJson('/pengaturan/tanda_tangan/tagihan', []);
        $jsonResponse->assertStatus(401);
    }
}
