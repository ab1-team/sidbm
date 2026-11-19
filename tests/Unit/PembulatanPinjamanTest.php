<?php

namespace Tests\Unit;

use App\Utils\Keuangan;
use Tests\TestCase;

class PembulatanPinjamanTest extends TestCase
{
    private function pinjaman($pembulatan): object
    {
        return (object) ['pembulatan' => $pembulatan];
    }

    /** NULL → warisi kecamatan.pembulatan (Session lokasi 301 = '500'). */
    public function test_null_mewarisi_kecamatan(): void
    {
        config(['angsuran_khusus.profil_rasio' => []]);
        session(['lokasi' => 301]);

        $this->assertSame(1500.0, Keuangan::pembulatan(1550, Keuangan::pembulatanPinjaman($this->pinjaman(null), (object) ['pembulatan' => '500'])));
    }

    /** Pinjaman eksplisit menang atas kecamatan. */
    public function test_override_pinjaman_menang(): void
    {
        $this->assertSame(2000.0, Keuangan::pembulatan(1550, '+1000'));
        $this->assertSame(2000.0, Keuangan::pembulatan(1550, '1000'));
        $this->assertSame(1000.0, Keuangan::pembulatan(1550, '-500'));
    }

    public function test_pembulatan_eksplisit_tanpa_fallback(): void
    {
        $this->assertNull(Keuangan::pembulatanEksplisit($this->pinjaman(null)));
        $this->assertSame('+1000', Keuangan::pembulatanEksplisit($this->pinjaman('+1000')));
        $this->assertSame('-500', Keuangan::pembulatanEksplisit($this->pinjaman('-500')));
        $this->assertSame('500', Keuangan::pembulatanEksplisit($this->pinjaman('500')));
    }

    public function test_bulatkan_menghormati_override(): void
    {
        $this->assertSame(2000.0, Keuangan::bulatkan(1234, '+1000'));
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

    /**
     * Profil angsuran khusus datang dari config per lokasi, BUKAN dari
     * kolom pembulatan pinjaman (pemisahan dua request owner).
     */
    public function test_profil_angsuran_dari_config_bukan_kolom_pembulatan(): void
    {
        // Kolom pembulatan 'r85-15' TIDAK lagi dikenali sebagai profil.
        $this->assertNull(Keuangan::profilAngsuranRasio($this->pinjaman('r85-15'), 12));
        $this->assertNull(Keuangan::profilAngsuranRasio($this->pinjaman('500'), 12));

        // Config 476 menghasilkan profil untuk tenor 12.
        $pin476 = (object) [];
        session(['lokasi' => 476]);
        $profil = Keuangan::profilAngsuranRasio($pin476, 12);

        $this->assertNotNull($profil);
        $this->assertSame(85000, $profil['pokok_per_juta']);
        $this->assertSame(15000, $profil['jasa_per_juta']);
        $this->assertSame(12, $profil['jangka']);
    }

    public function test_profil_angsuran_tabel_acuan(): void
    {
        // Tabel acuan owner: per Rp1 juta → pokok rutin 85rb, jasa 15rb;
        // pokok bulan ke-12 = sisa (65rb per juta); total bulan ke-12 = 80rb/juta;
        // Σ pokok = alokasi persis.
        $profil = ['pokok_per_juta' => 85000, 'jasa_per_juta' => 15000, 'jangka' => 12];

        foreach ([1000000 => [85000, 15000, 65000, 80000],
                  10000000 => [850000, 150000, 650000, 800000],
                  100000000 => [8500000, 1500000, 6500000, 8000000]] as $a => [$rp, $rj, $p12, $t12]) {
            $j = Keuangan::jadwalProfilRasio($a, 12, $profil);

            $this->assertSame((float) $rp, $j['pokok'][1], "pokok rutin alokasi $a");
            $this->assertSame((float) $rj, $j['jasa'][1], "jasa rutin alokasi $a");
            $this->assertSame((float) $p12, $j['pokok'][12], "pokok bulan 12 alokasi $a");
            $this->assertSame((float) $t12, $j['pokok'][12] + $j['jasa'][12], "total bulan 12 alokasi $a");
            $this->assertSame((float) $a, array_sum($j['pokok']), "Σ pokok = alokasi $a");
            for ($i = 1; $i <= 11; $i++) {
                $this->assertSame((float) $rp, $j['pokok'][$i]);
                $this->assertSame((float) $rj, $j['jasa'][$i]);
            }
        }
    }

    public function test_profil_angsuran_tenor_lain_tidak_aktif(): void
    {
        session(['lokasi' => 476]);
        $this->assertNull(Keuangan::profilAngsuranRasio((object) [], 24));
    }

    public function test_normalisasi_pembulatan_dari_form(): void
    {
        // Bertanda: apa adanya. Polos: digabung sistem. Kosong: NULL.
        $this->assertSame('+1000', Keuangan::normalisasiPembulatan('1000', '+'));
        $this->assertSame('-500', Keuangan::normalisasiPembulatan('500', '-'));
        $this->assertSame('1000', Keuangan::normalisasiPembulatan('1000', null));
        $this->assertNull(Keuangan::normalisasiPembulatan('', '+'));
        $this->assertNull(Keuangan::normalisasiPembulatan(null, '+'));
        // 'r85-15' bukan lagi format pembulatan sah — diperlakukan polos.
        $this->assertSame('+r85-15', Keuangan::normalisasiPembulatan('r85-15', '+'));
    }
}
