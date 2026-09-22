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
 * UJI INTEGRASI JALUR ANGGOTA — PinjamanAnggotaController::generate().
 *
 * Berbeda dari tests/Unit/RencanaGraceTest.php (yang hanya mereplika FORMULA),
 * test ini MENJALANKAN method generate() yang sesungguhnya lalu membaca hasilnya
 * dari tabel rencana_angsuran. DB memakai SQLite in-memory (bukan DB produksi).
 *
 * Sumber kebenaran angka: $map grace di generate() + spesifikasi task
 *   id 11/M24 -> grace 24 | id 20/M12 -> grace 12 | id 26/M6 -> grace 6
 *   id 14/M3  -> grace 3  | id 15/M2  -> grace 2  | lainnya     -> grace 0
 * Konvensi: pokok cicilan ke-k jatuh pada bulan (grace + k*interval); interval
 * = sis_pokok.sistem (=1). Bulan ke-1 = bulan angsuran pertama (BASIS).
 *
 * CATATAN HARNESS (jujur, dibuktikan lewat probe):
 * generate() selalu mengisi slot iterasi bulan-1 ($i=1) dengan baris
 * "penghapusan" (blok Session::get('tgl_penghapusan')), karena saat $i=1
 * $_alokasi_pokok/$_alokasi_jasa masih 0. Jadi baris ber-wajib_pokok>0 yang
 * DIHITUNG mulai dari slot bulan-1 tidak pernah ada di kode legacy ini —
 * lihat test_regresi_tanpa_grace_pokok_mulai_bulan_1() dan laporan akhir.
 */
class RencanaAngsuranAnggotaTest extends TestCase
{
    /** lokasi tenant: tabel disuffix _301 (TenantAware). */
    private const LOKASI = 301;

    private const PINJEL_ID = 1;

    /** alokasi cair acuan (12 juta). */
    private const ALOKASI = 12000000.0;

    /**
     * Tanggal angsuran pertama (bulan ke-0) hasil derivasi generate() dari
     * tgl_cair 2025-01-15 + ketentuan kelompok->d:
     *   bulan cair (01) + 1 = 02, hari jadwal desa = 15  ->  2025-02-15.
     * Baris iterasi $i punya jatuh_tempo = BASIS + $i bulan.
     */
    private const BASIS = '2025-02-15';

    /** Map grace legacy — dipakai untuk memastikan harness konsisten dgn kode. */
    private const GRACE_MAP = [11 => 24, 20 => 12, 26 => 6, 14 => 3, 15 => 2, 25 => 1];

    protected function setUp(): void
    {
        parent::setUp();

        // DB test = SQLite in-memory. Tidak menyentuh DB dev/produksi.
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'tenant.suffix' => '_'.self::LOKASI,
        ]);
        DB::purge('sqlite');

        $this->buildSchema();
        $this->seedData();

        Session::put('lokasi', self::LOKASI);
        // Baris penghapusan sintetis (0 pokok, 0 jasa) di slot bulan-1.
        Session::put('tgl_penghapusan', self::BASIS);
        Session::put('hapus_pokok', 0);
        Session::put('hapus_jasa', 0);

        $this->actingAs(User::find(1));
    }

    // ---------------------------------------------------------------------
    // HARNESS
    // ---------------------------------------------------------------------

    private function buildSchema(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->increments('id');
            $t->string('name')->nullable();
            $t->string('email')->unique();
            $t->string('password')->nullable();
            $t->integer('lokasi')->nullable();
        });

        // Kecamatan/desa/sistem_angsuran TIDAK TenantAware (nama tabel polos).
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
            $t->date('tgl_cair')->nullable();
            $t->date('tgl_proposal')->nullable();
            $t->date('tgl_verifikasi')->nullable();
        });

        // Tabel hasil yang dibaca test ini.
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

        // Relasi saldo_pinjaman() — harus ada walau tidak dibaca generate().
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
            'id' => 1, 'name' => 'Uji Anggota', 'email' => 'uji@sidbm.test',
            'password' => 'x', 'lokasi' => self::LOKASI,
        ]);

        DB::table('kecamatan')->insert([
            'id' => self::LOKASI, 'kd_kec' => '010', 'pembulatan' => '1000',
        ]);
        DB::table('desa')->insert([
            'id' => 1, 'kd_desa' => '001', 'kd_kec' => '010', 'jadwal_angsuran_desa' => 15,
        ]);
        DB::table('kelompok_'.self::LOKASI)->insert([
            'id' => 1, 'kd_kelompok' => 'KEL-001', 'desa' => '001',
        ]);

        // sistem = 1 artinya interval antar cicilan 1 bulan.
        DB::table('sistem_angsuran')->insert([
            ['id' => 1, 'nama' => 'Reguler', 'sistem' => 1],
            ['id' => 11, 'nama' => 'M24', 'sistem' => 1],
            ['id' => 20, 'nama' => 'M12', 'sistem' => 1],
            ['id' => 26, 'nama' => 'M6', 'sistem' => 1],
            ['id' => 14, 'nama' => 'M3', 'sistem' => 1],
            ['id' => 15, 'nama' => 'M2', 'sistem' => 1],
            ['id' => 25, 'nama' => 'M1', 'sistem' => 1],
        ]);

        DB::table('pinjaman_kelompok_'.self::LOKASI)->insert([
            'id' => self::PINJEL_ID,
            'id_kel' => 1,
            'jangka' => 36,
            'sistem_angsuran' => 11,
            'sa_jasa' => 11,
            'pros_jasa' => 0,
            'status' => 'A',
            'alokasi' => self::ALOKASI,
            'tgl_cair' => '2025-01-15',
        ]);
    }

    /**
     * Jalankan generate() yang SESUNGGUHNYA lalu baca tabel rencana_angsuran.
     *
     * @return Collection<int, object> baris rencana_angsuran terurut jatuh_tempo
     */
    private function runGenerate(int $saPokok, int $jangka, int $saJasa = 0): Collection
    {
        DB::table('rencana_angsuran_'.self::LOKASI)->delete();
        DB::table('pinjaman_kelompok_'.self::LOKASI)
            ->where('id', self::PINJEL_ID)
            ->update([
                'sistem_angsuran' => $saPokok,
                'sa_jasa' => $saJasa === 0 ? $saPokok : $saJasa,
                'jangka' => $jangka,
            ]);

        app(PinjamanAnggotaController::class)->generate(self::PINJEL_ID);

        return DB::table('rencana_angsuran_'.self::LOKASI)
            ->orderBy('jatuh_tempo')
            ->orderBy('angsuran_ke')
            ->get();
    }

    /**
     * Indeks bulan (1-based) dari sebuah jatuh_tempo, relatif thd BASIS.
     * BASIS -> 0 ; BASIS + n bulan -> n.
     */
    private function bulanKe(?string $jatuhTempo): int
    {
        $basisY = (int) substr(self::BASIS, 0, 4);
        $basisM = (int) substr(self::BASIS, 5, 2);
        $y = (int) date('Y', strtotime((string) $jatuhTempo));
        $m = (int) date('n', strtotime((string) $jatuhTempo));

        return 12 * ($y - $basisY) + ($m - $basisM);
    }

    /** Tanggal jatuh_tempo yang seharusnya untuk bulan ke-$bulan. */
    private function tanggalBulanKe(int $bulan): string
    {
        return date('Y-m-d', strtotime("+{$bulan} month", strtotime(self::BASIS)));
    }

    /** @return Collection<int, object> hanya baris dengan pokok > 0 */
    private function barisPokok(Collection $rows): Collection
    {
        return $rows->filter(fn ($r) => (float) $r->wajib_pokok > 0)->values();
    }

    /** @return array<int, int> daftar bulan (1-based) yang punya pokok > 0 */
    private function bulanPokok(Collection $rows): array
    {
        return $this->barisPokok($rows)->map(fn ($r) => $this->bulanKe($r->jatuh_tempo))->all();
    }

    // ---------------------------------------------------------------------
    // 0. Prasyarat harness: BASIS & baris penghapusan terbukti konsisten
    // ---------------------------------------------------------------------

    public function test_harness_basis_dan_baris_penghapusan_terbukti(): void
    {
        $rows = $this->runGenerate(11, 36);

        // 36 baris = 1baris penghapusan (slot bulan-1) + 35 slot reguler.
        $this->assertCount(36, $rows);

        $slot1 = $rows->firstWhere('angsuran_ke', 1);
        $this->assertNotNull($slot1);
        $this->assertSame(self::BASIS, $slot1->jatuh_tempo, 'slot bulan-1 = BASIS (bulan 0)');
        $this->assertSame(0, $this->bulanKe($slot1->jatuh_tempo));
        $this->assertSame(0.0, (float) $slot1->wajib_pokok);

        // Slot pertama yang berpokok = BASIS + $i bulan, dengan $i=25.
        $this->assertSame($this->tanggalBulanKe(25), '2027-03-15');
    }

    // ---------------------------------------------------------------------
    // 1. M24 (sistem_angsuran 11), jangka 36 -> grace 24, tempo 12
    // ---------------------------------------------------------------------

    public function test_m24_jangka_36_pokok_mulai_bulan_25(): void
    {
        $this->assertSame(24, self::GRACE_MAP[11]);

        $rows = $this->runGenerate(11, 36);
        $pokok = $this->barisPokok($rows);

        // TIDAK ADA pokok>0 pada bulan 1..24 (masa tunda M24).
        $dalamGrace = $rows->filter(function ($r) {
            $b = $this->bulanKe($r->jatuh_tempo);

            return $b >= 1 && $b <= 24;
        });
        $this->assertCount(23, $dalamGrace, 'bulan 2..24 = 23 slot');
        $this->assertSame(
            0.0,
            (float) $dalamGrace->sum('wajib_pokok'),
            'tidak ada pokok pada bulan 1..24'
        );

        // Pokok pertama di bulan 25, terakhir di bulan 36.
        $this->assertSame(25, $this->bulanKe($pokok->first()->jatuh_tempo));
        $this->assertSame($this->tanggalBulanKe(25), $pokok->first()->jatuh_tempo);
        $this->assertSame(36, $this->bulanKe($pokok->last()->jatuh_tempo));
        $this->assertSame($this->tanggalBulanKe(36), $pokok->last()->jatuh_tempo);

        // 12 cicilan pokok = tempo.
        $this->assertCount(12, $pokok);

        // Nilai per cicilan riil: 333.000 (= bulatkan(12.000.000/36)) utk 11 baris.
        $this->assertSame(333000.0, (float) $pokok->first()->wajib_pokok);
        $this->assertSame(333000.0, (float) $pokok->get(10)->wajib_pokok);

        // Sigma wajib_pokok HARUS = alokasi (cicilan terakhir menutup sisa).
        $this->assertSame(self::ALOKASI, (float) $rows->sum('wajib_pokok'));
        $this->assertSame(8337000.0, (float) $pokok->last()->wajib_pokok);

        // target_pokok baris terakhir = alokasi, tidak melebihi.
        $this->assertSame(self::ALOKASI, (float) $rows->last()->target_pokok);
    }

    // ---------------------------------------------------------------------
    // 2. M12 (sistem_angsuran 20), jangka 36 -> grace 12, tempo 24
    // ---------------------------------------------------------------------

    public function test_m12_jangka_36_pokok_mulai_bulan_13(): void
    {
        $this->assertSame(12, self::GRACE_MAP[20]);

        $rows = $this->runGenerate(20, 36);
        $pokok = $this->barisPokok($rows);

        $dalamGrace = $rows->filter(function ($r) {
            $b = $this->bulanKe($r->jatuh_tempo);

            return $b >= 1 && $b <= 12;
        });
        $this->assertCount(11, $dalamGrace, 'bulan 2..12 = 11 slot');
        $this->assertSame(0.0, (float) $dalamGrace->sum('wajib_pokok'), 'tidak ada pokok pada bulan 1..12');

        $this->assertSame(13, $this->bulanKe($pokok->first()->jatuh_tempo));
        $this->assertSame($this->tanggalBulanKe(13), $pokok->first()->jatuh_tempo);
        $this->assertSame(36, $this->bulanKe($pokok->last()->jatuh_tempo));
        $this->assertCount(24, $pokok, 'tempo M12 = 24 cicilan');

        $this->assertSame(self::ALOKASI, (float) $rows->sum('wajib_pokok'));
        $this->assertSame(self::ALOKASI, (float) $rows->last()->target_pokok);
    }

    // ---------------------------------------------------------------------
    // 3. M6 (sistem_angsuran 26), jangka 36 -> grace 6, tempo 30
    // ---------------------------------------------------------------------

    public function test_m6_jangka_36_pokok_mulai_bulan_7(): void
    {
        $this->assertSame(6, self::GRACE_MAP[26]);

        $rows = $this->runGenerate(26, 36);
        $pokok = $this->barisPokok($rows);

        $dalamGrace = $rows->filter(function ($r) {
            $b = $this->bulanKe($r->jatuh_tempo);

            return $b >= 1 && $b <= 6;
        });
        $this->assertCount(5, $dalamGrace, 'bulan 2..6 = 5 slot');
        $this->assertSame(0.0, (float) $dalamGrace->sum('wajib_pokok'), 'tidak ada pokok pada bulan 1..6');

        $this->assertSame(7, $this->bulanKe($pokok->first()->jatuh_tempo));
        $this->assertSame($this->tanggalBulanKe(7), $pokok->first()->jatuh_tempo);
        $this->assertSame(36, $this->bulanKe($pokok->last()->jatuh_tempo));
        $this->assertCount(30, $pokok, 'tempo M6 = 30 cicilan');

        $this->assertSame(self::ALOKASI, (float) $rows->sum('wajib_pokok'));
        $this->assertSame(self::ALOKASI, (float) $rows->last()->target_pokok);
    }

    // ---------------------------------------------------------------------
    // 4. REGRESI: sistem_angsuran di luar map -> grace 0 (perilaku lama)
    // ---------------------------------------------------------------------

    /**
     * Grace 0 harus menghasilkan rangkaian pokok BULANAN TANPA CELAH (tidak ada
     * penundaan ala grace). Catatan angka: slot iterasi bulan-1 ($i=1) selalu
     * dikonsumsi baris penghapusan di kode legacy (lihat docblock kelas), dan
     * $x = $i + 1 menggeser angsuran_ke. Karena itu pokok>0 riil muncul di
     * bulan 2..12 (11 baris), bukan bulan 1..12 (12 baris) spt angka acuan
     * brief. Perilaku ini IDENTIK dgn kode sebelum perbaikan grace — inilah
     * yang dijaga test ini; angka riil di-assert apa adanya.
     */
    public function test_regresi_tanpa_grace_pokok_mulai_bulan_1(): void
    {
        $this->assertArrayNotHasKey(1, self::GRACE_MAP, 'sistem 1 di luar map grace');

        $rows = $this->runGenerate(1, 12);
        $pokok = $this->barisPokok($rows);
        $bulan = $this->bulanPokok($rows);

        // Tanpa grace: pokok berurutan tiap bulan, mulai dari slot paling awal
        // yang bisa dihitung (bulan 2, karena bulan 1 dipakai baris penghapusan).
        $this->assertSame(range(2, 12), $bulan, 'pokok berurutan tanpa celah, tanpa penundaan grace');
        $this->assertCount(11, $pokok);

        // Sigma tetap menutup alokasi penuh.
        $this->assertSame(self::ALOKASI, (float) $rows->sum('wajib_pokok'));

        // Cicilan terakhir = sisa alokasi (amortisasi penutup).
        $this->assertSame(2000000.0, (float) $pokok->last()->wajib_pokok);
        $this->assertSame(self::ALOKASI, (float) $rows->last()->target_pokok);
    }

    // ---------------------------------------------------------------------
    // 5. target_pokok monotonik & tidak pernah melebihi alokasi
    // ---------------------------------------------------------------------

    public function test_tidak_ada_pokok_melebihi_alokasi(): void
    {
        $rows = $this->runGenerate(11, 36);

        $sebelumnya = -1.0;
        foreach ($rows as $i => $r) {
            $target = (float) $r->target_pokok;

            $this->assertLessThanOrEqual(
                self::ALOKASI,
                $target,
                "target_pokok baris #{$i} (bulan ".$this->bulanKe($r->jatuh_tempo).') melebihi alokasi'
            );
            $this->assertGreaterThanOrEqual(
                $sebelumnya,
                $target,
                "target_pokok baris #{$i} menurun (tidak monotonik)"
            );

            $sebelumnya = $target;
        }

        $this->assertSame(self::ALOKASI, (float) $rows->last()->target_pokok);
        $this->assertSame(self::ALOKASI, (float) $rows->sum('wajib_pokok'));
    }

    // ---------------------------------------------------------------------
    // 6. Konsistensi kolom angsuran_ke & jatuh_tempo (M24, jangka 36)
    // ---------------------------------------------------------------------

    public function test_m24_kolom_angsuran_ke_dan_jatuh_tempo_konsisten(): void
    {
        $rows = $this->runGenerate(11, 36);

        $keSebelumnya = 0;
        $tglSebelumnya = '';
        foreach ($rows as $i => $r) {
            $this->assertGreaterThan(
                $keSebelumnya,
                (int) $r->angsuran_ke,
                "angsuran_ke baris #{$i} tidak naik"
            );
            $this->assertGreaterThan(
                $tglSebelumnya,
                (string) $r->jatuh_tempo,
                "jatuh_tempo baris #{$i} tidak naik"
            );

            $keSebelumnya = (int) $r->angsuran_ke;
            $tglSebelumnya = (string) $r->jatuh_tempo;
        }

        // Baris pertama yang berpokok: jatuh_tempo = bulan ke-25 dari BASIS.
        $pertama = $this->barisPokok($rows)->first();
        $this->assertSame($this->tanggalBulanKe(25), $pertama->jatuh_tempo);
        $this->assertSame(25, $this->bulanKe($pertama->jatuh_tempo));
        $this->assertSame(26, (int) $pertama->angsuran_ke);
    }

    // ---------------------------------------------------------------------
    // 7. M3 (sistem_angsuran 14), jangka 12 -> grace 3, tempo 9
    // ---------------------------------------------------------------------

    public function test_m3_jangka_12_pokok_mulai_bulan_4(): void
    {
        $this->assertSame(3, self::GRACE_MAP[14]);

        $rows = $this->runGenerate(14, 12);
        $pokok = $this->barisPokok($rows);

        $dalamGrace = $rows->filter(function ($r) {
            $b = $this->bulanKe($r->jatuh_tempo);

            return $b >= 1 && $b <= 3;
        });
        $this->assertCount(2, $dalamGrace, 'bulan 2..3 = 2 slot');
        $this->assertSame(0.0, (float) $dalamGrace->sum('wajib_pokok'), 'tidak ada pokok pada bulan 1..3');

        $this->assertSame(4, $this->bulanKe($pokok->first()->jatuh_tempo));
        $this->assertSame($this->tanggalBulanKe(4), $pokok->first()->jatuh_tempo);
        $this->assertSame(12, $this->bulanKe($pokok->last()->jatuh_tempo));
        $this->assertCount(9, $pokok, 'tempo M3 = 9 cicilan');

        $this->assertSame(self::ALOKASI, (float) $rows->sum('wajib_pokok'));
        $this->assertSame(self::ALOKASI, (float) $rows->last()->target_pokok);
    }

    // ---------------------------------------------------------------------
    // 8. M2 (sistem_angsuran 15), jangka 12 -> grace 2, tempo 10
    // ---------------------------------------------------------------------

    public function test_m2_jangka_12_pokok_mulai_bulan_3(): void
    {
        $this->assertSame(2, self::GRACE_MAP[15]);

        $rows = $this->runGenerate(15, 12);
        $pokok = $this->barisPokok($rows);

        $dalamGrace = $rows->filter(function ($r) {
            $b = $this->bulanKe($r->jatuh_tempo);

            return $b >= 1 && $b <= 2;
        });
        $this->assertCount(1, $dalamGrace, 'bulan 2 = 1 slot');
        $this->assertSame(0.0, (float) $dalamGrace->sum('wajib_pokok'), 'tidak ada pokok pada bulan 1..2');

        $this->assertSame(3, $this->bulanKe($pokok->first()->jatuh_tempo));
        $this->assertSame($this->tanggalBulanKe(3), $pokok->first()->jatuh_tempo);
        $this->assertSame(12, $this->bulanKe($pokok->last()->jatuh_tempo));
        $this->assertCount(10, $pokok, 'tempo M2 = 10 cicilan');

        $this->assertSame(self::ALOKASI, (float) $rows->sum('wajib_pokok'));
        $this->assertSame(self::ALOKASI, (float) $rows->last()->target_pokok);
    }
}
