<?php

namespace Tests\Unit;

use App\Utils\Pinjaman;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Memverifikasi Pinjaman::satuanJangka() memilih satuan tenor yang benar.
 *
 * Konvensi: sistem_angsuran.id = 12 ("Mingguan") -> "Minggu"/"minggu".
 * Seluruh id lain (Bulanan, 3 Bulan, Musiman, M24, dst) -> "Bulan"/"bulan".
 *
 * Dipakai di seluruh dokumen pinjaman & rencana angsuran agar tenor produk
 * mingguan tidak tercetak "12 Bulan".
 */
class SatuanJangkaTest extends TestCase
{
    /** @return array<string, array{?string}> */
    public static function sistemProvider(): array
    {
        return [
            'mingguan' => ['12'],
            'bulanan' => ['1'],
            'tiga bulan' => ['2'],
            'musiman' => ['5'],
            '24 bulan' => ['16'],
            '36 bulan' => ['23'],
            'grace M24' => ['11'],
        ];
    }

    #[DataProvider('sistemProvider')]
    public function test_satuan_capital_ikut_sistem_angsuran(?string $id): void
    {
        $pinkel = $this->pinjamanDenganSistem($id);

        $expected = $id === '12' ? 'Minggu' : 'Bulan';

        $this->assertSame($expected, Pinjaman::satuanJangka($pinkel));
    }

    #[DataProvider('sistemProvider')]
    public function test_satuan_lowercase_ikut_sistem_angsuran(?string $id): void
    {
        $pinkel = $this->pinjamanDenganSistem($id);

        $expected = $id === '12' ? 'minggu' : 'bulan';

        $this->assertSame($expected, Pinjaman::satuanJangka($pinkel, false));
    }

    public function test_sis_pokok_null_fallback_ke_bulan(): void
    {
        $pinkel = new \stdClass;
        $pinkel->sis_pokok = null;

        $this->assertSame('Bulan', Pinjaman::satuanJangka($pinkel));
        $this->assertSame('bulan', Pinjaman::satuanJangka($pinkel, false));
    }

    public function test_pinjaman_null_tidak_fatal(): void
    {
        $this->assertSame('Bulan', Pinjaman::satuanJangka(null));
        $this->assertSame('bulan', Pinjaman::satuanJangka(null, false));
    }

    /**
     * Kolom `sistem_angsuran` & `jangka` bertipe varchar di DB, dan `id`
     * pada tabel `sistem_angsuran` juga varchar — helper harus tetap cocok
     * saat dibandingkan sebagai string.
     */
    public function test_id_numerik_tetap_dikenali_sebagai_mingguan(): void
    {
        $sistem = new \stdClass;
        $sistem->id = 12;

        $pinkel = new \stdClass;
        $pinkel->sis_pokok = $sistem;

        $this->assertSame('Minggu', Pinjaman::satuanJangka($pinkel));
    }

    private function pinjamanDenganSistem(?string $id): \stdClass
    {
        $sistem = $id === null ? null : new \stdClass;
        if ($sistem !== null) {
            $sistem->id = $id;
        }

        $pinkel = new \stdClass;
        $pinkel->sis_pokok = $sistem;

        return $pinkel;
    }
}