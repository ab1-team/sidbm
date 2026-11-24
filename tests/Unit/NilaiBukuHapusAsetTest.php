<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Verifikasi kalkulasi Nilai Buku pada form Hapus Inventaris (jurnal umum).
 *
 * Nilai buku pada dropdown opsi bisa berupa DESIMAL: Inventaris::nilaiBuku()
 * membulatkan penyusutan ke 2 desimal (round($harga_satuan / $umur, 2)) dan
 * menampilkan floor 1 rupiah untuk aset yang sudah habis umur ekonomis.
 * Contoh nyata (screenshot user): 2 unit meja, NB total 1.00 -> per unit 0.5.
 *
 * Handler lama memakai parseInt pada kedua tempat:
 *   1. parseInt(value[2])  -> NB desimal terpotong (1.00 -> 1, 0.75 -> 0)
 *   2. parseInt($('#harsat').val()) saat #unit berubah -> "0.5" dibaca 0
 * sehingga mengubah Jumlah (unit) dari 2 menjadi 1 menghasilkan
 * Nilai Buku 0.00 dan submit ditolak "Nominal transaksi tidak boleh nol".
 *
 * Perbaikan: parseFloat pada kedua tempat.
 */
class NilaiBukuHapusAsetTest extends TestCase
{
    private string $blade;

    protected function setUp(): void
    {
        // Test ini sengaja TIDAK extends TestCase Laravel: cukup membaca file,
        // tidak menyentuh DB (suite lokal butuh MySQL yang tidak selalu hidup).
        $path = dirname(__DIR__, 2).'/resources/views/transaksi/jurnal_umum/index.blade.php';
        $this->blade = file_get_contents($path);
    }

    /**
     * Simulasi perilaku JS: NB per unit desimal dikali unit parsial
     * harus menghasilkan nilai buku non-nol.
     */
    public function test_nilai_buku_desimal_tidak_terpotong_menjadi_nol(): void
    {
        // NB total 1.00 untuk 2 unit -> per unit 0.5 (identik skenario user).
        $this->assertSame(0.5, floatval('0.5') * 1);
        $this->assertSame(0, intval('0.5') * 1, 'parseInt("0.5") memang 0 — ini bug yang diperbaiki');

        // NB desimal dari penyusutan round 2 desimal.
        $this->assertSame(1500.75, floatval('1500.75') * 1);
        $this->assertNotSame(1500.75, intval('1500.75'));
    }

    /**
     * Handler #nama_barang dan #unit di blade wajib memakai parseFloat,
     * bukan parseInt, untuk nilai buku dan harga satuan.
     */
    public function test_blade_memakai_parse_float_untuk_nilai_buku_dan_harsat(): void
    {
        $this->assertStringContainsString('var nilai_buku = parseFloat(value[2])', $this->blade);
        $this->assertStringContainsString("var harga = parseFloat(\$('#harsat').val())", $this->blade);

        // Regresi lama tidak boleh kembali.
        $this->assertStringNotContainsString('var nilai_buku = parseInt(value[2])', $this->blade);
        $this->assertStringNotContainsString("var harga = parseInt(\$('#harsat').val())", $this->blade);
    }
}
