<?php

namespace Tests\Unit;

use App\Utils\Keuangan;
use PHPUnit\Framework\TestCase;

/**
 * Pembulatan yang MENGIKAT satu pinjaman.
 *
 * Kontrak:
 *   1. pinjaman.pembulatan = NULL/kosong → pakai kecamatan.pembulatan
 *      (perilaku lama, seluruh pinjaman existing tidak berubah).
 *   2. pinjaman.pembulatan terisi → pakai nilai itu, mengabaikan kecamatan.
 *   3. format legacy '+1000' (ke atas) / '-500' (ke bawah) dipahami.
 */
class PembulatanPinjamanTest extends TestCase
{
    private function pinjaman($pembulatan)
    {
        return new class($pembulatan)
        {
            public $pembulatan;

            public function __construct($pembulatan)
            {
                $this->pembulatan = $pembulatan;
            }
        };
    }

    private function kecamatan($pembulatan)
    {
        return (object) ['pembulatan' => $pembulatan];
    }

    public function test_null_mewarisi_kecamatan(): void
    {
        $this->assertSame('1000', Keuangan::pembulatanPinjaman($this->pinjaman(null), $this->kecamatan('1000')));
        $this->assertSame('1000', Keuangan::pembulatanPinjaman($this->pinjaman(''), $this->kecamatan('1000')));
    }

    public function test_nilai_eksplisit_mengalahkan_kecamatan(): void
    {
        $this->assertSame('500', Keuangan::pembulatanPinjaman($this->pinjaman('500'), $this->kecamatan('1000')));
        $this->assertSame('+1000', Keuangan::pembulatanPinjaman($this->pinjaman('+1000'), $this->kecamatan('500')));
        $this->assertSame('-500', Keuangan::pembulatanPinjaman($this->pinjaman('-500'), $this->kecamatan('1000')));
    }

    public function test_eksplisit_null_tanpa_fallback_ke_kecamatan(): void
    {
        $this->assertNull(Keuangan::pembulatanEksplisit($this->pinjaman(null)));
        $this->assertNull(Keuangan::pembulatanEksplisit($this->pinjaman('')));
        $this->assertSame('+1000', Keuangan::pembulatanEksplisit($this->pinjaman('+1000')));
    }

    public function test_efek_angka_override_mengubah_hasil_bulatkan(): void
    {
        // Angka acuan diukur dari rumus legacy (tests dijalankan pada nilai
        // pembulatan() murni): 1234 auto500=1000, +500=1500, -500=1000.
        $this->assertSame(1000.0, Keuangan::bulatkan(1234, '500'));
        $this->assertSame(1500.0, Keuangan::bulatkan(1234, '+500'));
        $this->assertSame(1000.0, Keuangan::bulatkan(1234, '-500'));
    }

    public function test_system_tanda_dipahami_pembulatan(): void
    {
        // Acuan diukur: 1550 auto500=1500, +500=1500, -500=1000;
        // auto1000=2000, +1000=2000, -1000=1000.
        $this->assertSame(1500.0, Keuangan::pembulatan(1550, '500'));
        $this->assertSame(1500.0, Keuangan::pembulatan(1550, '+500'));
        $this->assertSame(1000.0, Keuangan::pembulatan(1550, '-500'));
        $this->assertSame(2000.0, Keuangan::pembulatan(1550, '1000'));
        $this->assertSame(2000.0, Keuangan::pembulatan(1550, '+1000'));
        $this->assertSame(1000.0, Keuangan::pembulatan(1550, '-1000'));
    }

    public function test_profil_rasio_476_cocok_tabel_acuan(): void
    {
        // Tabel acuan owner: per Rp1 juta → pokok rutin 85rb, jasa 15rb;
        // pokok bulan ke-12 = sisa (65rb per juta); total bulan ke-12 = 80rb/juta;
        // Σ pokok = alokasi persis.
        $pinjaman = $this->pinjaman('r85-15');
        $profil = Keuangan::profilAngsuranRasio($pinjaman);

        $this->assertNotNull($profil);
        $this->assertSame(85000, $profil['pokok_per_juta']);
        $this->assertSame(15000, $profil['jasa_per_juta']);

        foreach ([1000000 => [85000, 15000, 65000, 80000],
                  10000000 => [850000, 150000, 650000, 800000],
                  100000000 => [8500000, 1500000, 6500000, 8000000]] as $a => [$rp, $rj, $p12, $t12]) {
            $j = Keuangan::jadwalProfilRasio($a, 12, $profil);

            $this->assertSame((float) $rp, $j['pokok'][1], "pokok rutin alokasi $a");
            $this->assertSame((float) $rj, $j['jasa'][1], "jasa rutin alokasi $a");
            $this->assertSame((float) $p12, $j['pokok'][12], "pokok bulan 12 alokasi $a");
            $this->assertSame((float) $t12, $j['pokok'][12] + $j['jasa'][12], "total bulan 12 alokasi $a");
            $this->assertSame((float) $a, array_sum($j['pokok']), "Σ pokok = alokasi $a");
            // 11 bulan rutin identik.
            for ($i = 1; $i <= 11; $i++) {
                $this->assertSame((float) $rp, $j['pokok'][$i]);
                $this->assertSame((float) $rj, $j['jasa'][$i]);
            }
        }
    }

    public function test_profil_rasio_tidak_aktif_pada_format_lain(): void
    {
        $this->assertNull(Keuangan::profilAngsuranRasio($this->pinjaman(null)));
        $this->assertNull(Keuangan::profilAngsuranRasio($this->pinjaman('500')));
        $this->assertNull(Keuangan::profilAngsuranRasio($this->pinjaman('+1000')));
        $this->assertNull(Keuangan::profilAngsuranRasio($this->pinjaman('-500')));
        $this->assertNull(Keuangan::profilAngsuranRasio($this->pinjaman('rx-y')));
    }

    public function test_normalisasi_pembulatan_dari_form(): void
    {
        // Profil rasio & bertanda: dipakai apa adanya (sistem diabaikan).
        $this->assertSame('r85-15', Keuangan::normalisasiPembulatan('r85-15', '+'));
        $this->assertSame('+1000', Keuangan::normalisasiPembulatan('1000', '+'));
        $this->assertSame('-500', Keuangan::normalisasiPembulatan('500', '-'));
        // Angka polos tanpa sistem → polos juga (format legacy sah).
        $this->assertSame('1000', Keuangan::normalisasiPembulatan('1000', null));
        // Kosong → NULL (warisan kecamatan).
        $this->assertNull(Keuangan::normalisasiPembulatan('', '+'));
        $this->assertNull(Keuangan::normalisasiPembulatan(null, '+'));
    }
}
