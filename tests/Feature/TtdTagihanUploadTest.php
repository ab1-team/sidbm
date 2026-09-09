<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\Testing\File as TestFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Illuminate\Support\Facades\Http;
use App\Support\TenantResolver;
use App\Models\AdminInvoice;
use App\Models\Kecamatan;
use Session;
use Tests\TestCase;

class TtdTagihanUploadTest extends TestCase
{
    public function test_upload_stores_file_and_updates_kecamatan_url(): void
    {
        fwrite(STDERR, "BEFORE RESOLVER\n");
        $resolver = Mockery::mock('alias:'.TenantResolver::class);
        $resolver->shouldReceive('resolveByDomain')->andReturn(null);
        $resolver->shouldReceive('markAsKabupaten');
        fwrite(STDERR, "AFTER RESOLVER\n");

        $invoice = Mockery::mock('alias:'.AdminInvoice::class);
        $invoice->shouldReceive('on')->andReturnSelf();
        $invoice->shouldReceive('where')->andReturnSelf();
        $invoice->shouldReceive('orderBy')->andReturnSelf();
        $invoice->shouldReceive('first')->andReturn(null);
        fwrite(STDERR, "AFTER INVOICE\n");

        $instance = new Kecamatan;
        $kecamatan = Mockery::mock(Kecamatan::class.'[update]');
        $kecamatan->where = function () use ($kecamatan) {
            return $kecamatan;
        };
        $kecamatan->shouldReceive('update')->with(['ttd_tagihan' => 'http://supabase.test/ttd_tagihan/301.png'])->once();
        fwrite(STDERR, "AFTER KECAMATAN\n");

        $image = TestFile::create('ttd.png', 100, 100, 'image/png');
        Storage::fake('supabase');
        $_ENV['SUPABASE_PUBLIC_URL'] = 'http://supabase.test';
        fwrite(STDERR, "AFTER STORAGE\n");
        Http::fake([
            'http://supabase.test/ttd_tagihan/301.png' => Http::response($image->getContent(), 200),
        ]);
        fwrite(STDERR, "AFTER HTTP\n");

        $user = User::factory()->make(['id' => 1, 'lokasi' => 301]);
        $this->actingAs($user, 'web');
        Session::put('lokasi', 301);
        fwrite(STDERR, "AFTER SESSION\n");

        $response = $this->post('/pengaturan/tanda_tangan/tagihan', [
            'ttd_tagihan' => $image,
        ]);
        fwrite(STDERR, "AFTER POST\n");

        $response->assertOk();
        fwrite(STDERR, "AFTER STATUS\n");
        $response->assertJson([
            'success' => true,
            'msg' => 'Tanda tangan & stempel surat tagihan berhasil disimpan.',
            'path' => 'http://supabase.test/ttd_tagihan/301.png',
        ]);
        fwrite(STDERR, "AFTER JSON\n");
        Storage::disk('supabase')->assertExists('ttd_tagihan/301.png');
        fwrite(STDERR, "AFTER STORAGE ASSERT\n");
    }
}
