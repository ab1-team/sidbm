<?php

namespace App\Services;

class ProvisiPencairanService
{
    public const LOKASI_WEDUNG = 15;

    public const KODE_KAS_TUNAI = '1.1.01.01';

    public const KODE_PENDAPATAN_PROVISI = '4.1.01.07';

    public const PERSEN_PROVISI = 0.5;

    /**
     * Nominal provisi = 0,5% dari alokasi pencairan, dibulatkan round PHP.
     */
    public static function nominal($alokasiPencairan): int
    {
        $alokasi = (int) round((float) str_replace([',', '.00'], '', (string) $alokasiPencairan));

        return (int) round($alokasi * self::PERSEN_PROVISI / 100);
    }

    /**
     * Hanya pencairan DBM Wedung (lokasi 15) dari status W ke A.
     */
    public static function berlaku($lokasi, $statusAsal, $statusRequest): bool
    {
        return (int) $lokasi === self::LOKASI_WEDUNG
            && (string) $statusAsal === 'W'
            && (string) $statusRequest === 'A';
    }

    /**
     * Payload jurnal provisi; relasi mengikuti format jurnal pencairan existing.
     */
    public static function payload(array $konteks): array
    {
        $nominal = self::nominal($konteks['alokasi_pencairan']);
        $namaKelompok = (string) $konteks['nama_kelompok'];

        return [
            'tgl_transaksi' => (string) $konteks['tgl_transaksi'],
            'rekening_debit' => self::KODE_KAS_TUNAI,
            'rekening_kredit' => self::KODE_PENDAPATAN_PROVISI,
            'idtp' => '0',
            'id_pinj' => $konteks['id_pinj'],
            'id_pinj_i' => '0',
            'keterangan_transaksi' => (string) 'Provisi 0,5% Pencairan Kel. '.$namaKelompok,
            'relasi' => (string) $namaKelompok.' ['.$konteks['id_pinj'].'] '.$konteks['ketua_kelompok'],
            'jumlah' => (string) $nominal,
            'urutan' => '0',
            'id_user' => $konteks['id_user'],
        ];
    }

    /**
     * Idempotensi: lookup jurnal provisi aktif untuk pinjaman yang sama.
     */
    public static function sudahAda($idPinjaman): bool
    {
        return \App\Models\Transaksi::where('id_pinj', $idPinjaman)
            ->where('rekening_debit', self::KODE_KAS_TUNAI)
            ->where('rekening_kredit', self::KODE_PENDAPATAN_PROVISI)
            ->whereNull('deleted_at')
            ->exists();
    }

    /**
     * Catat jurnal provisi bila berlaku dan belum ada (anti-duplikat).
     * Return true bila jurnal provisi dibuat.
     */
    public static function catat(array $konteks): bool
    {
        if (! self::berlaku($konteks['lokasi'] ?? null, $konteks['status_asal'] ?? null, $konteks['status_request'] ?? null)) {
            return false;
        }

        if (self::sudahAda($konteks['id_pinj'])) {
            return false;
        }

        \App\Models\Transaksi::create(self::payload($konteks));

        return true;
    }
}
