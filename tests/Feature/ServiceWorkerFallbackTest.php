<?php

namespace Tests\Feature;

use App\Http\Controllers\ServiceWorkerController;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use ReflectionMethod;
use Tests\TestCase;

class ServiceWorkerFallbackTest extends TestCase
{
    private const STORAGE_URL = 'https://enstorage.enpiistudio.com/api/v1/s3/test.jpg';

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
