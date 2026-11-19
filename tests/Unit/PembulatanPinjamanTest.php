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
}
