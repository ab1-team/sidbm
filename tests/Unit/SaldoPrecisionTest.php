<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Verifikasi toleransi presisi saldo pada validasi transaksi jurnal umum.
 *
 * Saldo dihitung di TransaksiController::saldo() memakai float arithmetic
 * (Keuangan::komSaldo) yang dapat menghasilkan pecahan sen seperti
 * 57193880.05999999 atau 57193880.058. Badge UI membulatkan ke 2 desimal
 * (Rp. 57,193,880.06), lalu user mengetik nominal yang sama persis. Validasi
 * lama membandingkan float mentah (`saldo >= nominal`) sehingga
 * 57193880.05999999 >= 57193880.06 bernilai false -> false positive
 * "Nominal transaksi melebihi saldo".
 *
 * Perbaikan: saldo backend dibulatkan 2 desimal & #saldo_trx menyimpan nilai
 * yang sama, sedangkan #SimpanTransaksi membandingkan dalam satuan sen
 * (Math.round(val * 100)) sehingga selisih float terkecil tidak lagi
 * menyebabkan false positive.
 */
class SaldoPrecisionTest extends TestCase
{
    /**
     * Saldo float mentah dari komSaldo harus dibulatkan tepat ke 2 desimal
     * sehingga nilai yang dikirim ke frontend sama dengan tampilan badge.
     */
    public function test_pembulatan_saldo_controller_menghasilkan_presisi_2_desimal(): void
    {
        $this->assertSame(57193880.06, round(floatval(57193880.05999999), 2));
        $this->assertSame(57193880.06, round(floatval(57193880.058), 2));
        $this->assertSame(57193880.06, round(floatval(57193880.06), 2));

        // Tidak boleh ada sisa pecahan di luar 2 desimal.
        $rounded = round(floatval(57193880.05999999), 2);
        $this->assertSame(0.0, round($rounded * 100, 0) - $rounded * 100);
    }

    /**
     * Method saldo() controller harus mengembalikan nilai yang sudah
     * dibulatkan 2 desimal, bukan float mentah hasil komSaldo().
     */
    public function test_method_saldo_controller_membulatkan_nilai_saldo(): void
    {
        $method = new ReflectionMethod(
            'App\Http\Controllers\TransaksiController',
            'saldo'
        );

        $this->assertStringContainsString(
            "round(floatval(\$total_saldo), 2)",
            $method->getFileName() !== false
                ? file_get_contents($method->getFileName())
                : ''
        );
    }

    /**
     * Replika perbandingan #SimpanTransaksi: nominal >= 0 memakai
     * (saldo_sen >= nominal_sen). Kedua representasi saldo yang sama di
     * tampilan tidak boleh dianggap melebihi saldo.
     */
    public function test_perbandingan_sen_tidak_false_positive_saat_nilai_tampilan_sama(): void
    {
        $saldo_sen = $this->toCents(57193880.05999999);
        $nominal_sen = $this->toCents(57193880.06);

        $this->assertSame(5719388006, $saldo_sen);
        $this->assertSame(5719388006, $nominal_sen);
        $this->assertTrue($saldo_sen >= $nominal_sen);

        // Variasi pecahan sen lain yang tetap membulat ke 57,193,880.06.
        $this->assertSame(5719388006, $this->toCents(57193880.058));
        $this->assertTrue($this->toCents(57193880.058) >= $nominal_sen);
    }

    /**
     * Nominal yang benar-benar lebih besar dari saldo tetap ditolak.
     */
    public function test_perbandingan_sen_masih_menolak_nominal_yang_melebihi_saldo(): void
    {
        $this->assertFalse($this->toCents(57193880.06) >= $this->toCents(57193880.07));
        $this->assertFalse($this->toCents(57193880.05) >= $this->toCents(57193880.06));
    }

    /**
     * Perbandingan lama berbasis float mentah memang gagal — regresi guard
     * untuk memastikan akar masalah benar dan perbaikan dibutuhkan.
     */
    public function test_perbandingan_float_mentah_lama_menghasilkan_false_positive(): void
    {
        $this->assertFalse(57193880.05999999 >= 57193880.06);
    }

    /**
     * Replika Math.round(value * 100) JavaScript: bulatkan ke bilangan bulat
     * terdekat (half away from zero untuk nilai positif).
     */
    private function toCents(float $value): int
    {
        return (int) round($value * 100, 0, PHP_ROUND_HALF_UP);
    }
}
