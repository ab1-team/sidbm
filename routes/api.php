<?php

use App\Http\Controllers\Api\AngsuranController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BasisDataController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\HoldingLaporanController;
use App\Http\Controllers\Api\LaporanController;
use App\Http\Controllers\Api\MobileActivationController;
use App\Http\Controllers\Api\PengaturanController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/cek-update', [MobileActivationController::class, 'cekUpdate']);
Route::PUT('/generate-token/{id_kec}', [MobileActivationController::class, 'generate']);
Route::post('/mobile-token-activation', [MobileActivationController::class, 'activation']);
Route::get('/ambil-data-lokasi', [MobileActivationController::class, 'ambilDataLokasi']);

Route::post('/auth', [AuthController::class, 'auth']);

Route::group(['middleware' => ['tenant', 'auth:sanctum'], 'prefix' => 'v1'], function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/proposal', [DashboardController::class, 'proposal']);
    Route::get('/dashboard/verifikasi', [DashboardController::class, 'verifikasi']);
    Route::get('/dashboard/waiting', [DashboardController::class, 'waiting']);
    Route::get('/dashboard/aktif', [DashboardController::class, 'aktif']);
    Route::get('/dashboard/lunas', [DashboardController::class, 'lunas']);
    Route::get('/dashboard/detail-pinjaman/{id}', [DashboardController::class, 'detailPinjaman']);
    Route::get('/dashboard/nunggak', [DashboardController::class, 'nunggak']);
    Route::get('/dashboard/jatuh-tempo', [DashboardController::class, 'jatuhTempo']);
    Route::get('/dashboard/tagihan', [DashboardController::class, 'tagihan']);
    Route::put('/dashboard/pelunasan/{idPinjaman}', [DashboardController::class, 'pelunasan']);

    Route::get('/basis-data', [BasisDataController::class, 'index']);
    Route::get('/basis-data/desa', [BasisDataController::class, 'desa']);
    Route::get('/basis-data/penduduk', [BasisDataController::class, 'penduduk']);
    Route::get('/basis-data/penduduk/{id}', [BasisDataController::class, 'detailPenduduk']);
    Route::get('/basis-data/kelompok', [BasisDataController::class, 'kelompok']);
    Route::get('/basis-data/lembaga-lain', [BasisDataController::class, 'lembagaLain']);
    Route::get('/basis-data/kelompok/{id}', [BasisDataController::class, 'detailKelompok']);

    Route::get('/angsuran', [AngsuranController::class, 'index']);
    Route::get('/angsuran/search', [AngsuranController::class, 'search']);
    Route::get('/angsuran/{pinjaman_id}', [AngsuranController::class, 'pinjaman']);
    Route::post('/angsuran', [AngsuranController::class, 'simpan']);
    Route::post('/angsuran/{idtp}', [AngsuranController::class, 'detailAngsuran']);

    Route::get('/laporan', [LaporanController::class, 'index']);
    Route::post('/laporan/preview', [LaporanController::class, 'preview']);

    Route::get('/pengaturan', [PengaturanController::class, 'index']);
    Route::get('/pengaturan/chart-of-account', [PengaturanController::class, 'chartOfAccount']);
    Route::put('/pengaturan/update-user', [PengaturanController::class, 'updateUser']);
    Route::put('/pengaturan/update-foto-user', [PengaturanController::class, 'updateFotoUser']);
    Route::put('/pengaturan/update-tempat-lahir', [PengaturanController::class, 'updateTempatLahir']);
    Route::put('/pengaturan/update-alamat', [PengaturanController::class, 'updateAlamat']);
    Route::put('pengaturan/update-password', [PengaturanController::class, 'updatePassword']);

    Route::post('/auth/logout', [AuthController::class, 'logout']);
});

/*
|--------------------------------------------------------------------------
| Holding API (laporan keuangan)
|--------------------------------------------------------------------------
| Endpoint ini dipakai oleh sistem holding untuk menarik laporan keuangan
| tenant (Bumdesma). Autentikasi via header:
|   X-Holding-Token   : api_secret license
|   X-Holding-Tenant  : slug tenant
| Middleware holding.license memvalidasi token + tenant + is_active + expired_at.
*/
Route::group(['middleware' => 'holding.license', 'prefix' => 'v1/holding/laporan'], function () {
    Route::get('neraca',             [HoldingLaporanController::class, 'neraca']);
    Route::get('laba-rugi',          [HoldingLaporanController::class, 'labaRugi']);
    Route::get('arus-kas',           [HoldingLaporanController::class, 'arusKas']);
    Route::get('perubahan-ekuitas',  [HoldingLaporanController::class, 'perubahanEkuitas']);
    Route::get('calk',               [HoldingLaporanController::class, 'calk']);
});
