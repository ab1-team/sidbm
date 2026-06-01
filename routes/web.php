<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\KabupatenController as AdminKabupatenController;
use App\Http\Controllers\Admin\KecamatanController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AnggotaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DesaController;
use App\Http\Controllers\GenerateController;
use App\Http\Controllers\Kabupaten\AuthController as KabupatenAuthController;
use App\Http\Controllers\Kabupaten\KabupatenController;
use App\Http\Controllers\Kabupaten\LaporanController;
use App\Http\Controllers\KelompokController;
use App\Http\Controllers\LembagaLainController;
use App\Http\Controllers\PelaporanController;
use App\Http\Controllers\PinjamanAnggotaController;
use App\Http\Controllers\PinjamanKelompokController;
use App\Http\Controllers\ServiceWorkerController;
use App\Http\Controllers\SopController;
use App\Http\Controllers\TandaTanganController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\UploadAplikasiController;
use App\Http\Controllers\UserController;
use App\Models\Kecamatan;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/master', [AdminAuthController::class, 'index'])->middleware('guest');
Route::post('/master/login', [AdminAuthController::class, 'login'])->middleware('guest');

Route::group(['prefix' => 'master', 'as' => 'master.', 'middleware' => 'master'], function () {
    Route::get('/dashboard', [AdminController::class, 'index']);
    Route::get('/simpan_saldo', [DashboardController::class, 'simpanSaldo']);

    Route::get('/kecamatan/{kd_prov}/{kd_kab}/{kd_kec}', [KecamatanController::class, 'index']);

    Route::get('/kabupaten/{kd_prov}/{kd_kab}/', [AdminKabupatenController::class, 'index']);
    Route::get('/kabupaten/laporan/sub_laporan/{laporan}/', [AdminKabupatenController::class, 'subLaporan']);
    Route::get('/kabupaten/laporan/data/{lokasi}/', [AdminKabupatenController::class, 'data']);
    Route::post('/kabupaten/laporan/preview/{kd_kab}', [AdminKabupatenController::class, 'preview']);

    Route::post('/users/akses_tombol/{user}', [AdminUserController::class, 'AksesTombol']);
    Route::post('/users/hak_akses/{user}', [AdminUserController::class, 'HakAkses']);
    Route::get('/users/lokasi/{kd_kec}', [AdminUserController::class, 'DaftarUser']);
    Route::resource('/users', AdminUserController::class);

    Route::get('/laporan', [AdminController::class, 'laporan']);

    Route::get('/buat_invoice', [InvoiceController::class, 'index']);
    Route::get('/nomor_invoice', [InvoiceController::class, 'InvoiceNo']);
    Route::get('/jumlah_tagihan', [InvoiceController::class, 'Tagihan']);

    Route::get('/unpaid', [InvoiceController::class, 'Unpaid']);
    Route::get('/{invoice}/unpaid', [InvoiceController::class, 'DetailUnpaid']);

    Route::get('/paid', [InvoiceController::class, 'Paid']);
    Route::get('/{invoice}/paid', [InvoiceController::class, 'DetailPaid']);

    Route::post('/buat_invoice', [InvoiceController::class, 'store']);
    Route::put('/{invoice}/simpan', [InvoiceController::class, 'simpan']);

    Route::get('/upload_aplikasi', [UploadAplikasiController::class, 'index']);
    Route::post('/upload_aplikasi/upload', [UploadAplikasiController::class, 'upload'])->name('version.upload');
    Route::post('/upload_aplikasi/store', [UploadAplikasiController::class, 'store'])->name('version.store');
    Route::get('/upload_aplikasi/list', [UploadAplikasiController::class, 'list'])->name('version.list');
    Route::delete('/upload_aplikasi/{id}', [UploadAplikasiController::class, 'destroy'])->name('version.destroy');

    Route::get('/transaksi', [AdminController::class, 'transaksi']);

    Route::resource('/menu', MenuController::class);

    Route::post('/logout', [AdminAuthController::class, 'logout']);
});

Route::get('/kab', [KabupatenAuthController::class, 'index'])->middleware('guest');
Route::post('/kab/login', [KabupatenAuthController::class, 'login'])->middleware('guest');

Route::group(['prefix' => 'kab', 'as' => 'kab.', 'middleware' => 'kab'], function () {
    Route::get('/dashboard', [KabupatenController::class, 'index']);
    Route::get('/tanda_tangan', [KabupatenController::class, 'tandaTangan']);
    Route::post('/tanda_tangan/simpan', [KabupatenController::class, 'simpanTandaTangan']);

    Route::get('/simpan_saldo', [DashboardController::class, 'simpanSaldo']);
    Route::get('/kecamatan/{kd_kec}', [KabupatenController::class, 'kecamatan']);

    Route::get('/laporan', [LaporanController::class, 'index']);
    Route::get('/laporan/sub_laporan/{laporan}/', [LaporanController::class, 'subLaporan']);
    Route::get('/laporan/data/{lokasi}/', [LaporanController::class, 'data']);
    Route::post('/laporan/preview/{kd_kab}', [LaporanController::class, 'preview']);

    Route::post('/logout', [KabupatenAuthController::class, 'logout']);
});


Route::group(['middleware' => 'tenant'], function () {
    Route::get('/', [AuthController::class, 'index'])->middleware('guest')->name('/');
    Route::get('/login', [AuthController::class, 'index'])->middleware('guest');
    Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
    Route::get('/app', [AuthController::class, 'app']);
    Route::get('/download-app', [AuthController::class, 'downloadApp']);
    
    Route::get('/pelaporan', [PelaporanController::class, 'index'])->middleware('basic');
    Route::get('/pelaporan/sub_laporan/{file}', [PelaporanController::class, 'subLaporan'])->middleware('basic');
    Route::post('/pelaporan/preview', [PelaporanController::class, 'preview'])->middleware('basic');
    Route::post('/pelaporan/preview/{lokasi?}', [PelaporanController::class, 'preview'])->middleware('basic');

    Route::get('/pelaporan/ba_bumdesma', [PelaporanController::class, 'beritaAcara'])->middleware('auth');
    Route::get('/pelaporan/mou', [PelaporanController::class, 'mou'])->middleware('auth');
    Route::get('/pelaporan/ts', [PelaporanController::class, 'ts'])->middleware('auth');

    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth');
    Route::get('/piutang_jasa', [DashboardController::class, 'piutang'])->middleware('auth');
    Route::get('/pelaporan/invoice/{invoice}', [PelaporanController::class, 'invoice']);
    Route::get('/simpan_saldo', [DashboardController::class, 'simpanSaldo'])->middleware('auth');

    Route::post('/dashboard/jatuh_tempo', [DashboardController::class, 'jatuhTempo'])->middleware('auth');
    Route::post('/dashboard/nunggak', [DashboardController::class, 'nunggak'])->middleware('auth');
    Route::post('/dashboard/tagihan', [DashboardController::class, 'tagihan'])->middleware('auth');
    Route::get('/dashboard/pinjaman', [DashboardController::class, 'pinjaman'])->middleware('auth');
    Route::get('/dashboard/pemanfaat', [DashboardController::class, 'pemanfaat'])->middleware('auth');

    Route::get('/pengaturan/sop', [SopController::class, 'index'])->middleware('auth');
    Route::get('/pengaturan/coa', [SopController::class, 'coa'])->middleware('auth');
    Route::put('/pengaturan/coa/{rekening}', [SopController::class, 'updateCoa'])->middleware('auth');
    Route::get('/pengaturan/ttd_pelaporan', [SopController::class, 'ttdPelaporan'])->middleware('auth');
    Route::get('/pengaturan/ttd_spk', [SopController::class, 'ttdSpk'])->middleware('auth');
    Route::get('/pengaturan/custom_calk', [SopController::class, 'customCalk'])->middleware('auth');
    Route::put('/pengaturan/custom_calk/{kec}', [SopController::class, 'setCustomCalk'])->middleware('auth');

    Route::put('/pengaturan/app-token/{kec}', [SopController::class, 'appToken'])->middleware('auth');
    Route::put('/pengaturan/lembaga/{kec}', [SopController::class, 'lembaga'])->middleware('auth');
    Route::put('/pengaturan/personalia/{kec}', [SopController::class, 'personalia'])->middleware('auth');
    Route::put('/pengaturan/pengelola/{kec}', [SopController::class, 'pengelola'])->middleware('auth');
    Route::put('/pengaturan/pinjaman/{kec}', [SopController::class, 'pinjaman'])->middleware('auth');
    Route::put('/pengaturan/asuransi/{kec}', [SopController::class, 'asuransi'])->middleware('auth');
    Route::put('/pengaturan/spk/{kec}', [SopController::class, 'spk'])->middleware('auth');
    Route::put('/pengaturan/logo/{kec}', [SopController::class, 'logo'])->middleware('auth');
    Route::put('/pengaturan/calk/{kec}', [SopController::class, 'calk'])->middleware('auth');
    Route::put('/pengaturan/pesan_whatsapp/{kec}', [SopController::class, 'pesanWhatsapp'])->middleware('auth');
    Route::put('/pengaturan/berita_acara/{kec}', [SopController::class, 'beritaAcara'])->middleware('auth');
    Route::put('/pengaturan/tanggung_renteng/{kec}', [SopController::class, 'tanggungRenteng'])->middleware('auth');
    Route::get('/pengaturan/local_view/{key}/{val?}', [SopController::class, 'localView'])->middleware('auth');

    Route::post('/pengaturan/whatsapp/save_device', [SopController::class, 'save_whatsapp_session'])->middleware('auth');
    Route::post('/pengaturan/whatsapp/delete_session', [SopController::class, 'delete_whatsapp_session'])->middleware('auth');
    Route::post('/pengaturan/whatsapp/{token}', [SopController::class, 'whatsapp'])->middleware('auth');

    Route::get('/pengaturan/invoice', [SopController::class, 'invoice'])->middleware('auth');
    Route::get('/pengaturan/{inv}/invoice', [SopController::class, 'detailInvoice'])->middleware('auth');

    Route::post('/pengaturan/sop/simpanttdpelaporan', [SopController::class, 'simpanTtdPelaporan'])->middleware('auth');

    Route::get('/pengaturan/proyeksi_pendapatan_jasa', [SopController::class, 'RencanaPendapatanJasa'])->middleware('auth');
    Route::post('/pengaturan/proyeksi_pendapatan_jasa', [SopController::class, 'PreviewPendapatanJasa'])->middleware('auth');

    Route::get('/pengaturan/panduan_transaksi', [\App\Http\Controllers\PanduanController::class, 'index'])->middleware('auth');

    Route::get('/pengaturan/tanda_tangan', [TandaTanganController::class, 'index'])->middleware('auth');
    Route::post('/pengaturan/simpan_tanda_tangan', [TandaTanganController::class, 'store'])->middleware('auth');

    Route::resource('/database/desa', DesaController::class)->middleware('auth');

    Route::get('/database/lembaga_lain/register_lembaga', [LembagaLainController::class, 'register'])->middleware('auth');
    Route::get('/database/lembaga_lain/generatekode', [LembagaLainController::class, 'generateKode'])->middleware('auth');
    Route::resource('/database/lembaga_lain', LembagaLainController::class)->middleware('auth');

    Route::get('/database/kelompok/detail_kelompok/{id}', [KelompokController::class, 'detailKelompok'])->middleware('auth');
    Route::get('/database/kelompok/register_kelompok', [KelompokController::class, 'register'])->middleware('auth');
    Route::get('/database/kelompok/generatekode', [KelompokController::class, 'generateKode'])->middleware('auth');
    Route::resource('/database/kelompok', KelompokController::class)->middleware('auth');

    Route::get('/database/penduduk/register_penduduk', [AnggotaController::class, 'register'])->middleware('auth');
    Route::get('/database/penduduk/cari_nik', [AnggotaController::class, 'cariNik'])->middleware('auth');
    Route::post('/database/penduduk/{nik}/blokir', [AnggotaController::class, 'blokir'])->middleware('auth');
    Route::resource('/database/penduduk', AnggotaController::class)->middleware('auth');

    Route::get('/register_proposal', [PinjamanKelompokController::class, 'create'])->middleware('auth');
    Route::get('/register_proposal/{id_kel}', [PinjamanKelompokController::class, 'register'])->middleware('auth');
    Route::get('/daftar_kelompok', [PinjamanKelompokController::class, 'DaftarKelompok'])->middleware('auth');

    Route::get('/detail/{perguliran}', [PinjamanKelompokController::class, 'detail'])->middleware('auth');
    Route::get('/perguliran/proposal', [PinjamanKelompokController::class, 'proposal'])->middleware('auth');
    Route::get('/perguliran/verified', [PinjamanKelompokController::class, 'verified'])->middleware('auth');
    Route::get('/perguliran/waiting', [PinjamanKelompokController::class, 'waiting'])->middleware('auth');
    Route::get('/perguliran/aktif', [PinjamanKelompokController::class, 'aktif'])->middleware('auth');
    Route::get('/perguliran/lunas', [PinjamanKelompokController::class, 'lunas'])->middleware('auth');
    Route::get('/perguliran/generate/{id_pinj}', [PinjamanKelompokController::class, 'generate'])->middleware('auth');
    Route::get('/lunas/{perguliran}', [PinjamanKelompokController::class, 'pelunasan'])->middleware('auth');
    Route::get('/cetak_keterangan_lunas/{perguliran}', [PinjamanKelompokController::class, 'keterangan'])->middleware('auth');
    Route::get('/perguliran/catatan/{perguliran}', [PinjamanKelompokController::class, 'catatan'])->middleware('auth');
    Route::delete('/perguliran/catatan/{perguliran}', [PinjamanKelompokController::class, 'deleteCatatan'])->middleware('auth');

    Route::get('/perguliran/cari_kelompok', [PinjamanKelompokController::class, 'cariKelompok'])->middleware('auth');
    Route::post('/perguliran/catatan_bimbingan/{perguliran}', [PinjamanKelompokController::class, 'catatanBimbingan'])->middleware('auth');
    Route::post('/perguliran/simpan_data/{id}', [PinjamanKelompokController::class, 'simpan'])->middleware('auth');
    Route::post('/perguliran/rescedule', [PinjamanKelompokController::class, 'rescedule'])->middleware('auth');
    Route::post('/perguliran/hapus', [PinjamanKelompokController::class, 'hapus'])->middleware('auth');
    Route::resource('/perguliran', PinjamanKelompokController::class)->middleware('auth');

    Route::get('/perguliran/dokumen/kartu_angsuran/{id}', [PinjamanKelompokController::class, 'kartuAngsuran'])->middleware('auth');
    Route::get('/perguliran/dokumen/kartu_angsuran/{id}/{idtp}', [PinjamanKelompokController::class, 'cetakPadaKartu'])->middleware('auth');

    Route::get('/perguliran/dokumen/kartu_angsuran_anggota/{id}/{nia?}', [PinjamanKelompokController::class, 'kartuAngsuranAnggota'])->middleware('auth');

    Route::get('/perguliran/dokumen/cetak_kartu_angsuran_anggota/{id}/{idtp}/{nia?}', [PinjamanKelompokController::class, 'cetakKartuAngsuranAnggota'])->middleware('auth');

    Route::post('/perguliran/dokumen', [PinjamanKelompokController::class, 'dokumen'])->middleware('auth');

    Route::post('/perguliran/kembali_proposal/{id}', [PinjamanKelompokController::class, 'kembaliProposal'])->middleware('auth');
    Route::post('/perguliran/tidak_layak_cair/{id}', [PinjamanKelompokController::class, 'tidakLayak'])->middleware('auth');

    Route::get('/pinjaman_anggota/ambil_daftar_pemanfaat', [PinjamanAnggotaController::class, 'ambilDaftarPemanfaat'])->middleware('auth');
    Route::get('/pinjaman_anggota/register/{id_pinkel}', [PinjamanAnggotaController::class, 'create'])->middleware('auth');
    Route::get('/pinjaman_anggota/cari_pemanfaat', [PinjamanAnggotaController::class, 'cariPemanfaat'])->middleware('auth');
    Route::get('/hapus_pemanfaat/{id}', [PinjamanAnggotaController::class, 'hapus'])->middleware('auth');

    Route::get('/pinjaman_anggota/form_hapus/{pinj}', [PinjamanAnggotaController::class, 'form_penghapusan'])->middleware('auth');

    Route::post('/pinjaman_anggota/import', [PinjamanAnggotaController::class, 'import'])->middleware('auth');
    Route::resource('/pinjaman_anggota', PinjamanAnggotaController::class)->middleware('auth');

    Route::post('/lunaskan_pemanfaat/{pinjaman}', [PinjamanAnggotaController::class, 'lunaskan'])->middleware('auth');
    Route::post('/hapus_pemanfaat/{pinjaman}', [PinjamanAnggotaController::class, 'penghapusan'])->middleware('auth');

    Route::get('/transaksi/jurnal_umum', [TransaksiController::class, 'jurnalUmum'])->middleware('auth');
    Route::get('/transaksi/jurnal_angsuran', [TransaksiController::class, 'jurnalAngsuran'])->middleware('auth');
    Route::get('/transaksi/tutup_buku', [TransaksiController::class, 'jurnalTutupBuku'])->middleware('auth');
    Route::get('/trasaksi/saldo/{kode_akun}', [TransaksiController::class, 'saldo'])->middleware('auth');

    Route::get('/transaksi/ambil_rekening/{id}', [TransaksiController::class, 'rekening'])->middleware('auth');
    Route::get('/transaksi/form_nominal/', [TransaksiController::class, 'form'])->middleware('auth');
    Route::get('/transaksi/form_angsuran/{id_pinkel}', [TransaksiController::class, 'formAngsuran'])->middleware('auth');
    Route::get('/transaksi/angsuran/target/{id_pinkel}', [TransaksiController::class, 'targetAngsuran'])->middleware('auth');

    Route::get('/transaksi/data/{idt}', [TransaksiController::class, 'data'])->middleware('auth');

    Route::get('/transaksi/tutup_buku/saldo_awal/{tahun}', [TransaksiController::class, 'saldoAwal'])->middleware('auth');
    Route::post('/transaksi/tutup_buku/saldo', [TransaksiController::class, 'saldoTutupBuku'])->middleware('auth');
    Route::post('/transaksi/tutup_buku', [TransaksiController::class, 'simpanTutupBuku'])->middleware('auth');

    Route::post('/transaksi/simpan_laba', [TransaksiController::class, 'simpanAlokasiLaba'])->middleware('auth');
    Route::post('/transaksi/reversal', [TransaksiController::class, 'reversal'])->middleware('auth');
    Route::post('/transaksi/hapus', [TransaksiController::class, 'hapus'])->middleware('auth');

    Route::get('/transaksi/angsuran/lpp/{id}', [TransaksiController::class, 'lpp'])->middleware('auth');
    Route::get('/transaksi/angsuran/detail_angsuran/{id}', [TransaksiController::class, 'detailAngsuran'])->middleware('auth');
    Route::get('/transaksi/detail_transaksi/', [TransaksiController::class, 'detailTransaksi'])->middleware('auth');

    Route::post('/transaksi/angsuran', [TransaksiController::class, 'angsuran'])->middleware('auth');
    Route::post('/transaksi/angsuran/cetak_bkm', [TransaksiController::class, 'cetakBkm'])->middleware('auth');

    Route::get('/transaksi/generate_real/{id_pinkel}', [TransaksiController::class, 'generateReal'])->middleware('auth');
    Route::get('/transaksi/regenerate_real/{id_pinkel}', [TransaksiController::class, 'realisasi'])->middleware('auth');

    Route::get('/transaksi/angsuran/form_anggota/{id_pinkel}', [TransaksiController::class, 'formAnggota'])->middleware('auth');
    Route::get('/angsuran/notifikasi/{idtp}', [TransaksiController::class, 'notifikasi'])->middleware('auth');

    Route::get('/transaksi/dokumen/kuitansi/{id}', [TransaksiController::class, 'kuitansi'])->middleware('auth');
    Route::get('/transaksi/dokumen/kuitansi_thermal/{id}', [TransaksiController::class, 'kuitansi_thermal'])->middleware('auth');
    Route::get('/transaksi/dokumen/bkk/{id}', [TransaksiController::class, 'bkk'])->middleware('auth');
    Route::get('/transaksi/dokumen/bkm/{id}', [TransaksiController::class, 'bkm'])->middleware('auth');
    Route::get('/transaksi/dokumen/bm/{id}', [TransaksiController::class, 'bm'])->middleware('auth');

    Route::get('/transaksi/dokumen/struk/{id}', [TransaksiController::class, 'struk'])->middleware('auth');
    Route::get('/transaksi/dokumen/struk_matrix/{id}', [TransaksiController::class, 'strukMatrix'])->middleware('auth');
    Route::get('/transaksi/dokumen/struk_thermal/{id}', [TransaksiController::class, 'strukThermal'])->middleware('auth');
    Route::get('/transaksi/dokumen/bkm_angsuran/{id}', [TransaksiController::class, 'bkmAngsuran'])->middleware('auth');
    Route::get('/transaksi/dokumen/bkk_angsuran/{id}', [TransaksiController::class, 'bkkAngsuran'])->middleware('auth');
    Route::post('/transaksi/dokumen/cetak', [TransaksiController::class, 'cetak'])->middleware('auth');

    Route::get('/transaksi/ebudgeting', [TransaksiController::class, 'ebudgeting'])->middleware('auth');
    Route::post('/transaksi/anggaran', [TransaksiController::class, 'formAnggaran'])->middleware('auth');
    Route::post('/transaksi/simpan_anggaran', [TransaksiController::class, 'simpanAnggaran'])->middleware('auth');

    Route::get('/transaksi/taksiran_pajak', [TransaksiController::class, 'taksiranPajak'])->middleware('auth');
    Route::post('/transaksi/taksiran_pajak', [TransaksiController::class, 'cetakTaksiranPajak'])->middleware('auth');
    Route::get('/transaksi/pendapatan/{tahun}/{bulan}', [TransaksiController::class, 'pendapatan'])->middleware('auth');

    Route::resource('/transaksi', TransaksiController::class)->middleware('auth');

    Route::resource('/profil', UserController::class);

    Route::get('/sync/{lokasi}', [DashboardController::class, 'sync'])->middleware('auth');
    Route::get('/link', function () {
        $target = __DIR__.'/../storage/app/public';
        $shortcut = __DIR__.'/../public/storage';

        try {
            symlink($target, $shortcut);

            return response()->json('Symlink created successfully.');
        } catch (\Exception $e) {
            return response()->json('Failed to create symlink: '.$e->getMessage());
        }
    });

    Route::get('/user_lokasi', function () {
        $host = request()->getHost();
        $kec = Kecamatan::where('web_kec', request()->getHost())->orwhere('web_alternatif', request()->getHost())->with('kabupaten')->first();
        $users = User::where('lokasi', $kec->id)->with('l', 'j')->orderBy('level', 'ASC')->orderBy('jabatan', 'ASC')->get();

        Session::put('login', true);
        $http = 'http';
        if (request()->secure()) {
            $http .= 's';
        }

        return view('welcome', ['users' => $users, 'kec' => $kec, 'host' => $host, 'http' => $http]);
    });

    Route::get('/download/{file}', function ($file) {
        return response()->download(storage_path('app/public/docs/'.$file));
    })->name('download');

    Route::get('/generate', [GenerateController::class, 'index']);
    Route::post('/generate/save/{offset?}', [GenerateController::class, 'generate']);

    Route::get('/unpaid', [DashboardController::class, 'unpaid'])->middleware('auth');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');
    Route::get('/excel/{filename}/{lokasi}', [PinjamanKelompokController::class, 'excel']);

    Route::get('/manifest.json', [ServiceWorkerController::class, 'manifest']);
    Route::get('/cache-files', [ServiceWorkerController::class, 'assets']);

    Route::get('/{invoice}', [PelaporanController::class, 'invoice']);
});
