<?php

namespace Tests\Feature;

use App\Http\Controllers\ServiceWorkerController;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class ServiceWorkerFallbackTest extends TestCase
{
    private const STORAGE_URL = 'https://enstorage.enpiistudio.com/api/v1/s3/test.jpg';

    protected function setUp(): void
    {
        parent::setUp();

        // Overlay: arahkan koneksi DB tenant ke SQLite in-memory (skema kosong)
        // agar test tidak bergantung pada server MySQL tenant.
        $this->app['config']->set(
            'database.connections.mysql_b',
            [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ]
        );

        Schema::connection('mysql_b')->create('kecamatan', function ($table) {
            $table->increments('id');
            $table->string('web_kec')->nullable();
            $table->string('web_alternatif')->nullable();
            $table->string('logo')->nullable();
        });

        Schema::connection('mysql_b')->create('kabupaten', function ($table) {
            $table->increments('id');
            $table->string('web_kab')->nullable();
            $table->string('web_kab_alternatif')->nullable();
        });
    }

    private function invokePrivate(string $method, array $args)
    {
        $reflection = new ReflectionMethod(ServiceWorkerController::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs(new ServiceWorkerController(), $args);
    }

    public function test_storage_to_base64_returns_null_on_timeout_without_throwing()
    {
        Http::fake(function () {
            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $result = $this->invokePrivate('storageToBase64', [self::STORAGE_URL]);

        $this->assertNull($result);
    }

    public function test_get_image_from_storage_returns_null_on_error()
    {
        Http::fake(function () {
            throw new ConnectionException('cURL error 28');
        });

        $result = $this->invokePrivate('getImageFromStorage', [self::STORAGE_URL]);

        $this->assertNull($result);
    }

    public function test_cache_files_endpoint_does_not_contain_data_uri()
    {
        $response = $this->get('/cache-files');

        $response->assertStatus(200);

        $files = $response->json();
        $this->assertIsArray($files);

        foreach ($files as $file) {
            $this->assertIsString($file);
            $this->assertStringStartsNotWith('data:', $file, 'Endpoint /cache-files tidak boleh mengembalikan data URI.');
        }
    }

    public function test_manifest_returns_static_icons_fast()
    {
        // Manifest memakai icon statis lokal, tidak boleh ada request HTTP ke storage eksternal.
        Http::preventStrayRequests();

        $start = microtime(true);
        $response = $this->get('/manifest.json');
        $elapsed = microtime(true) - $start;

        $response->assertStatus(200);

        $icons = $response->json('icons');
        $this->assertIsArray($icons);
        $this->assertNotEmpty($icons);

        foreach ($icons as $icon) {
            $src = $icon['src'];
            $this->assertIsString($src);
            $this->assertStringStartsNotWith('data:', $src, 'Icon manifest tidak boleh berupa data URI.');
            $this->assertStringNotContainsString('://', $src, 'Icon manifest tidak boleh berupa URL eksternal.');
            $this->assertStringStartsWith('/assets/img/icon/', $src);
        }

        $this->assertLessThan(0.1, $elapsed, 'Endpoint /manifest.json harus merespons di bawah 0.1 detik.');
    }

    public function test_logo_route_returns_fallback_image_on_storage_failure()
    {
        Http::fake(function () {
            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $response = $this->get('/logo');

        $response->assertStatus(200);
        $this->assertStringContainsString('image/png', $response->headers->get('Content-Type'));
    }

    public function test_safe_resize_returns_null_on_storage_timeout()
    {
        Http::fake(function () {
            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $result = $this->invokePrivate('safeResize', [self::STORAGE_URL, 192, 192]);

        $this->assertTrue(
            $result === null || is_string($result),
            'safeResize harus mengembalikan null atau string fallback, bukan throw.'
        );
    }
}
