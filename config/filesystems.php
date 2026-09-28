<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Disk
    |--------------------------------------------------------------------------
    |
    | Disk cloud default untuk upload (logo, tanda tangan, foto profil, dll).
    |
    */

    'cloud_disk' => env('FILESYSTEM_CLOUD_DISK', 'enstorage'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been set up for each driver as an example of the required values.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

        /*
        |----------------------------------------------------------------------
        | EnStorage (S3-compatible cloud storage)
        |----------------------------------------------------------------------
        | Disk default untuk seluruh upload cloud pada aplikasi sidbm.
        | Menggantikan Supabase Storage, tetapi tetap membaca env SUPABASE_*
        | sebagai fallback agar kompatibel dengan deployment lama.
        */
        'enstorage' => [
            'driver' => 's3',
            'key' => env('ENSTORAGE_ACCESS_KEY', 'en_ascqaplo'),
            'secret' => env('ENSTORAGE_KEY', env('SUPABASE_S3_KEY')),
            'region' => env('ENSTORAGE_REGION', env('SUPABASE_S3_REGION', 'us-east-1')),
            'bucket' => env('ENSTORAGE_BUCKET', env('SUPABASE_S3_BUCKET', 'public')),
            'url' => env('ENSTORAGE_URL', env('SUPABASE_URL')),
            'endpoint' => env('ENSTORAGE_ENDPOINT', env('SUPABASE_S3_ENDPOINT', 'https://enstorage.enpiistudio.com/api/v1/s3')),
            'use_path_style_endpoint' => true,
            /*
             | API key ikut dikirim sebagai header `X-API-Key` pada setiap
             | request. Signature SigV4 tetap dikirim (supaya gateway dapat
             | memverifikasi identitas), tetapi API key menjamin autentikasi
             | tetap berhasil apa pun perlakuan proxy terhadap header yang
             | ikut ditandatangani.
             |
             | Kunci `http` (bukan `options`) yang benar: FilesystemManager
             | meneruskan seluruh konfigurasi disk ke konstruktor S3Client,
             | dan hanya `http` yang dibaca Guzzle untuk opsi koneksi.
             */
            'http' => [
                'headers' => [
                    'X-API-Key' => env('ENSTORAGE_KEY', env('SUPABASE_S3_KEY')),
                ],
            ],
            'throw' => true,
        ],

        /*
        |----------------------------------------------------------------------
        | Supabase (alias kompatibilitas)
        |----------------------------------------------------------------------
        | Disk lama tetap dipertahankan sebagai alias yang mengarah ke
        | konfigurasi EnStorage yang sama.
        */
        'supabase' => [
            'driver' => 's3',
            'key' => env('ENSTORAGE_KEY', env('SUPABASE_S3_KEY')),
            'secret' => env('ENSTORAGE_SECRET', env('SUPABASE_S3_SECRET')),
            'region' => env('ENSTORAGE_REGION', env('SUPABASE_S3_REGION', 'us-east-1')),
            'bucket' => env('ENSTORAGE_BUCKET', env('SUPABASE_S3_BUCKET', 'public')),
            'url' => env('ENSTORAGE_URL', env('SUPABASE_URL')),
            'endpoint' => env('ENSTORAGE_ENDPOINT', env('SUPABASE_S3_ENDPOINT', 'https://enstorage.enpiistudio.com/api/v1/s3')),
            'use_path_style_endpoint' => true,
            'http' => [
                'headers' => [
                    'X-API-Key' => env('ENSTORAGE_KEY', env('SUPABASE_S3_KEY')),
                ],
            ],
            'throw' => true,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
