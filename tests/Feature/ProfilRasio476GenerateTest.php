<?php

namespace Tests\Feature;

use App\Http\Controllers\PinjamanAnggotaController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Session;
use Tests\TestCase;

/**
 * Aturan angsuran KHUSUS lokasi 476 (dari config/angsuran_khusus.php)
 * pada jalur generate() NYATA — terpisah dari pembulatan per pinjaman.
 *
 * Tenor 12 bulan, per Rp1 juta alokasi → pokok rutin Rp85.000 +
 * jasa Rp15.000; pokok bulan ke-12 = sisa. Meniru
 * RencanaAngsuranAnggotaTest (SQLite in-memory, tabel tenant 301) dengan
 * Session lokasi=476 supaya config profil 476 aktif; kolom `pembulatan`
 * pinjaman sengaja NULL — profil TIDAK berasal dari kolom itu.
 */
class ProfilRasio476GenerateTest extends TestCase
{
    private const LOKASI = 301;       // suffix tabel (skema uji)

    private const LOKASI_ATURAN = 476; // lokasi dengan aturan khusus di config

    private const PINJEL_ID = 1;

    private const ALOKASI = 1000000.0;

    private const BASIS = '2025-02-15';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'tenant.suffix' => '_'.self::LOKASI,
        ]);
        DB::purge('sqlite');

        $this->buildSchema();
        $this->seedData();

        Session::put('lokasi', self::LOKASI_ATURAN);
        Session::put('tgl_penghapusan', self::BASIS);
        Session::put('hapus_pokok', 0);
        Session::put('hapus_jasa', 0);

        $this->actingAs(User::find(1));
    }

    private function buildSchema(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->increments('id');
            $t->string('name')->nullable();
            $t->string('email')->unique();
            $t->string('password')->nullable();
            $t->integer('lokasi')->nullable();
        });

        Schema::create('kecamatan', function (Blueprint $t) {
            $t->increments('id');
            $t->char('kd_kec', 10)->nullable();
            $t->string('pembulatan')->nullable();
        });

        Schema::create('desa', function (Blueprint $t) {
            $t->increments('id');
            $t->char('kd_desa', 10)->nullable();
            $t->char('kd_kec', 10)->nullable();
            $t->integer('jadwal_angsuran_desa')->default(0);
        });

        Schema::create('sistem_angsuran', function (Blueprint $t) {
            $t->increments('id');
            $t->string('nama')->nullable();
            $t->integer('sistem')->default(1);
        });

        Schema::create('kelompok_'.self::LOKASI, function (Blueprint $t) {
            $t->increments('id');
            $t->char('kd_kelompok', 20)->nullable();
            $t->char('desa', 10)->nullable();
        });

        Schema::create('pinjaman_kelompok_'.self::LOKASI, function (Blueprint $t) {
            $t->increments('id');
            $t->integer('id_kel')->nullable();
            $t->integer('jangka')->nullable();
            $t->integer('sistem_angsuran')->nullable();
            $t->integer('sa_jasa')->nullable();
            $t->float('pros_jasa')->nullable();
            $t->string('status')->nullable();
            $t->float('alokasi')->nullable();
            $t->float('proposal')->nullable();
            $t->float('verifikasi')->nullable();
            $t->string('pembulatan')->nullable();
            $t->date('tgl_cair')->nullable();
            $t->date('tgl_proposal')->nullable();
            $t->date('tgl_verifikasi')->nullable();
        });

        Schema::create('rencana_angsuran_'.self::LOKASI, function (Blueprint $t) {
            $t->increments('id');
            $t->integer('loan_id')->nullable();
            $t->integer('angsuran_ke')->nullable();
            $t->date('jatuh_tempo')->nullable();
            $t->float('wajib_pokok')->nullable();
            $t->float('wajib_jasa')->nullable();
            $t->float('target_pokok')->nullable();
            $t->float('target_jasa')->nullable();
            $t->dateTime('lu')->nullable();
            $t->integer('id_user')->nullable();
        });

        Schema::create('penghapusan', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('lokasi')->nullable();
            $t->integer('id_pinj')->nullable();
            $t->integer('id_pinj_i')->nullable();
            $t->integer('nia')->nullable();
            $t->float('saldo_pinjaman')->nullable();
            $t->dateTime('tanggal')->nullable();
        });
    }

    private function seedData(): void
    {
        DB::table('users')->insert([
            'id' => 1, 'name' => 'Uji Rasio', 'email' => 'rasio@sidbm.test',
            'password' => 'x', 'lokasi' => self::LOKASI,
        ]);

        DB::table('kecamatan')->insert([
            'id' => self::LOKASI, 'kd_kec' => '010', 'pembulatan' => '500',
        ]);
        DB::table('desa')->insert([
            'id' => 1, 'kd_desa' => '001', 'kd_kec' => '010', 'jadwal_angsuran_desa' => 15,
        ]);
        DB::table('kelompok_'.self::LOKASI)->insert([
            'id' => 1, 'kd_kelompok' => 'KEL-001', 'desa' => '001',
        ]);
        DB::table('sistem_angsuran')->insert([
            ['id' => 1, 'nama' => 'Reguler', 'sistem' => 1],
        ]);

        DB::table('pinjaman_kelompok_'.self::LOKASI)->insert([
            'id' => self::PINJEL_ID,
            'id_kel' => 1,
            'jangka' => 12,
            'sistem_angsuran' => 1,
            'sa_jasa' => 1,
            'pros_jasa' => 18,
            'status' => 'A',
            'alokasi' => self::ALOKASI,
            'pembulatan' => null,
            'tgl_cair' => '2025-01-15',
        ]);
    }

    private function runGenerate(): Collection
    {
        DB::table('rencana_angsuran_'.self::LOKASI)->delete();

        app(PinjamanAnggotaController::class)->generate(self::PINJEL_ID);

        return DB::table('rencana_angsuran_'.self::LOKASI)
            ->orderBy('jatuh_tempo')
            ->orderBy('angsuran_ke')
            ->get();
    }

    /** Aturan 476 pada generate() nyata: 11×85rb + 1×65rb, jasa 15rb/bln. */
    public function test_generate_476_pokok_rutin_dan_sisa_terakhir(): void
    {
        $rows = $this->runGenerate();

        // Baris penghapusan slot bulan-1 (0/0) + 11 baris berangsuran, sesuai
        // perilaku legacy yang dijaga RencanaAngsuranAnggotaTest.
        $berangsur = $rows->filter(fn ($r) => (float) $r->wajib_pokok > 0);

        // Pokok rutin: semua baris kecuali terakhir = 85.000.
        $rutin = $berangsur->slice(0, $berangsur->count() - 1);
        $this->assertGreaterThanOrEqual(10, $rutin->count());
        foreach ($rutin as $r) {
            $this->assertSame(85000.0, (float) $r->wajib_pokok, 'pokok rutin');
            $this->assertSame(15000.0, (float) $r->wajib_jasa, 'jasa bulanan');
        }

        // Baris terakhir: pokok = sisa (65.000), jasa tetap 15.000.
        $terakhir = $berangsur->last();
        $this->assertSame(65000.0, (float) $terakhir->wajib_pokok, 'pokok terakhir = sisa');
        $this->assertSame(15000.0, (float) $terakhir->wajib_jasa);

        // Σ pokok = alokasi persis; Σ jasa = 180.000 (18%).
        $this->assertSame(self::ALOKASI, (float) $rows->sum('wajib_pokok'));
        $this->assertSame(180000.0, (float) $rows->sum('wajib_jasa'));
    }

    /** generate() nyata: 75jt/24 bulan mengabaikan pembulatan '+50000'. */
    public function test_generate_476_75jt_24_bulan_jadwal_eksak(): void
    {
        DB::table('pinjaman_kelompok_'.self::LOKASI)->where('id', self::PINJEL_ID)->update([
            'jangka' => 24,
            'pros_jasa' => 36,
            'alokasi' => 75000000.0,
            'pembulatan' => '+50000',
        ]);

        $rows = $this->runGenerate();
        $berangsur = $rows->filter(fn ($r) => (float) $r->wajib_pokok > 0);

        $this->assertCount(24, $berangsur);
        foreach ($berangsur as $r) {
            $this->assertSame(3125000.0, (float) $r->wajib_pokok, 'pokok rutin');
            $this->assertSame(1125000.0, (float) $r->wajib_jasa, 'jasa bulanan');
        }
        $this->assertSame(75000000.0, (float) $rows->sum('wajib_pokok'));
        $this->assertSame(27000000.0, (float) $rows->sum('wajib_jasa'));
    }
}
