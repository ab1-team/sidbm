<?php

namespace Tests\Feature;

use App\Models\Kecamatan;
use App\Models\User;
use Illuminate\Http\Testing\File as TestFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TtdTagihanUploadTest extends TestCase
{
    public function test_upload_stores_file_and_updates_kecamatan_url(): void
    {
        putenv('SUPABASE_PUBLIC_URL=http://supabase.test');

        foreach (['mysql', 'mysql_b'] as $connection) {
            config(["database.connections.{$connection}.driver" => 'sqlite']);
            config(["database.connections.{$connection}.database" => ':memory:']);
            DB::purge($connection);

            DB::connection($connection)->statement(
                'CREATE TABLE kecamatan (id INTEGER PRIMARY KEY, ttd_tagihan TEXT NULL)'
            );
            DB::connection($connection)->statement(
                'CREATE TABLE kabupaten (id INTEGER PRIMARY KEY)'
            );
            DB::connection($connection)->statement(
                'CREATE TABLE admin_invoice (id INTEGER PRIMARY KEY, lokasi INTEGER NULL, status TEXT NULL, tgl_invoice TEXT NULL, tgl_lunas TEXT NULL)'
            );

            DB::connection($connection)->table('kecamatan')->insert(['id' => 301]);
        }

        Storage::fake('supabase');
        $image = TestFile::fake()->image('ttd.png');

        $user = User::factory()->make(['id' => 1, 'lokasi' => 301]);
        $this->app['auth']->guard('web')->setUser($user);
        $this->app['auth']->shouldUse('web');
        Session::put('lokasi', 301);

        $response = $this->post('/pengaturan/tanda_tangan/tagihan', [
            'ttd_tagihan' => $image,
        ]);

        $this->assertSame(301, Kecamatan::where('id', 301)->first()?->id);
        $this->assertSame('mysql', Kecamatan::where('id', 301)->first()->getConnectionName());
        $this->assertSame(1, DB::connection('mysql')->table('kecamatan')->where('id', 301)->update(['ttd_tagihan' => 'probe']));
        $this->assertSame('probe', DB::connection('mysql')->table('kecamatan')->where('id', 301)->value('ttd_tagihan'));
        DB::connection('mysql')->table('kecamatan')->where('id', 301)->update(['ttd_tagihan' => null]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'msg' => 'Tanda tangan & stempel surat tagihan berhasil disimpan.',
            'path' => 'http://supabase.test/ttd_tagihan/301.png',
        ]);
        Storage::disk('supabase')->assertExists('ttd_tagihan/301.png');

        $this->assertTrue(DB::connection('mysql')->table('kecamatan')->where('id', 301)->exists());

        foreach (['mysql', 'mysql_b'] as $connection) {
            $this->assertSame(
                'http://supabase.test/ttd_tagihan/301.png',
                DB::connection($connection)->table('kecamatan')->where('id', 301)->value('ttd_tagihan')
            );
        }
    }
}
