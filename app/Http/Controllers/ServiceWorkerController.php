<?php

namespace App\Http\Controllers;

use App\Models\Kecamatan;
use Exception;
use Illuminate\Support\Facades\Http;

class ServiceWorkerController extends Controller
{
    public function manifest()
    {
        $url = request()->getHost();
        $kec = Kecamatan::where('web_kec', $url)->orwhere('web_alternatif', $url)->first();

        return response()->json([
            'name' => $kec->nama_lembaga_sort,
            'short_name' => 'SI DBM',
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#4CAF50',
            'description' => 'Sistem Informasi Dana Bergulir Masyarakat',
            'icons' => [
                [
                    'src' => $this->resize($kec->logo, 192, 192),
                    'type' => 'image/png',
                    'sizes' => '192x192',
                ],
                [
                    'src' => $this->resize($kec->logo, 512, 512),
                    'type' => 'image/png',
                    'sizes' => '512x512',
                ],
            ],
        ])->header('Content-Type', 'application/json');
    }

    public function assets()
    {
        $url = request()->getHost();
        $kec = Kecamatan::where('web_kec', $url)->orwhere('web_alternatif', $url)->first();

        return response()->json([
            '/',
            $this->resize($kec->logo, 192, 192),
            $this->resize($kec->logo, 512, 512),
        ]);
    }

    private function resize($logo, $width, $height)
    {
        if (! $logo) {
            $logo = '1.png';
        }

        // Cek apakah logo adalah URL cloud storage (EnStorage/Supabase)
        if ($this->isStorageUrl($logo)) {
            // Ambil gambar dari cloud storage
            $imageContent = $this->getImageFromStorage($logo);
            if (! $imageContent) {
                throw new Exception('Failed to fetch image from storage: '.$logo);
            }
        } else {
            // Ambil gambar dari local storage
            $imagePath = 'logo/'.$logo;
            $filePath = storage_path('app/public/'.$imagePath);

            if (! file_exists($filePath)) {
                throw new Exception('File not found: '.$filePath);
            }

            $imageContent = file_get_contents($filePath);
            if (! $imageContent) {
                throw new Exception('Failed to read image file: '.$filePath);
            }
        }

        // Buat image dari string
        $image = @imagecreatefromstring($imageContent);
        if (! $image) {
            throw new Exception('Failed to create image from content');
        }

        // Dapatkan dimensi asli
        $imageSize = getimagesizefromstring($imageContent);
        if (! $imageSize) {
            imagedestroy($image);
            throw new Exception('Failed to get image size');
        }

        [$originalWidth, $originalHeight] = $imageSize;

        // Buat gambar baru dengan ukuran yang diinginkan
        $resizedImage = imagecreatetruecolor($width, $height);

        // Preserve transparency untuk PNG
        imagealphablending($resizedImage, false);
        imagesavealpha($resizedImage, true);
        $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
        imagefilledrectangle($resizedImage, 0, 0, $width, $height, $transparent);

        // Resize gambar
        imagecopyresampled(
            $resizedImage,
            $image,
            0, 0, 0, 0,
            $width,
            $height,
            $originalWidth,
            $originalHeight
        );

        // Convert ke base64
        ob_start();
        imagepng($resizedImage); // Gunakan PNG untuk kualitas lebih baik
        $imageData = ob_get_contents();
        ob_end_clean();

        $base64Image = base64_encode($imageData);

        // Bersihkan memori
        imagedestroy($image);
        imagedestroy($resizedImage);

        return 'data:image/png;base64,'.$base64Image;
    }

    /**
     * Cek apakah URL merupakan URL cloud storage yang dikenali, yaitu
     * domain Supabase maupun domain EnStorage (enstorage.enpiistudio.com
     * atau domain dari env ENSTORAGE_URL).
     */
    private function isStorageUrl($url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $hosts = [
            'supabase.co',
            'enstorage.enpiistudio.com',
        ];

        $enstorageUrl = env('ENSTORAGE_URL', env('SUPABASE_URL'));
        if ($enstorageUrl) {
            $host = parse_url($enstorageUrl, PHP_URL_HOST);
            if ($host) {
                $hosts[] = $host;
            }
        }

        $host = parse_url($url, PHP_URL_HOST);
        if ($host && in_array($host, $hosts, true)) {
            return true;
        }

        foreach ($hosts as $needle) {
            if (strpos($url, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @deprecated Gunakan isStorageUrl(). Dipertahankan untuk kompatibilitas.
     */
    private function isSupabaseUrl($url)
    {
        return $this->isStorageUrl($url);
    }

    private function getImageFromStorage($url)
    {
        $response = Http::withOptions([
            'verify' => false,
        ])->timeout(30)->get($url);

        if (! $response->successful()) {
            return null;
        }

        return $response->body();
    }

    /**
     * @deprecated Gunakan getImageFromStorage(). Dipertahankan untuk kompatibilitas.
     */
    private function getImageFromSupabase($url)
    {
        return $this->getImageFromStorage($url);
    }

    /**
     * Ambil gambar dari cloud storage dan konversi menjadi data URI base64.
     */
    private function storageToBase64($url)
    {
        $imageContent = $this->getImageFromStorage($url);

        if (! $imageContent) {
            return null;
        }

        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? $url, PATHINFO_EXTENSION));
        $mime = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
        ][$extension] ?? 'application/octet-stream';

        return "data:$mime;base64,".base64_encode($imageContent);
    }

    /**
     * @deprecated Gunakan storageToBase64(). Dipertahankan untuk kompatibilitas.
     */
    private function supabaseToBase64($url)
    {
        return $this->storageToBase64($url);
    }
}
