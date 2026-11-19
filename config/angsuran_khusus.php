<?php

/**
 * Aturan perhitungan angsuran KHUSUS per lokasi/kecamatan.
 *
 * TERPISAH dari fitur pembulatan per pinjaman (kolom `pembulatan` di
 * tabel pinjaman — format 500 / +1000 / -500 / NULL). Entri di sini
 * adalah PROFIL PERHITUNGAN ANGSURAN untuk seluruh pinjaman pada
 * lokasi tersebut, jadi menambah perlakuan khusus lokasi lain cukup
 * menambah entri di file ini tanpa menyentuh logika perhitungan.
 *
 * profil_rasio — pokok & jasa bulanan proporsional kelipatan alokasi;
 * pokok angsuran terakhir = sisa pokok sehingga Σ pokok tepat alokasi.
 *   - pokok_per_juta : rupiah pokok per Rp1 juta alokasi per bulan
 *   - jasa_per_juta  : rupiah jasa  per Rp1 juta alokasi per bulan
 *   - jangka         : tenor (bulan) yang dipakai profil ini; pinjaman
 *                      di lokasi itu dengan tenor lain tetap memakai
 *                      jalur perhitungan normal.
 *
 * Contoh lokasi 476: alokasi Rp1 juta, tenor 12 bulan →
 * pokok rutin Rp85.000 + jasa Rp15.000 per bulan; pokok bulan ke-12
 * = sisa Rp65.000 (total bulan terakhir Rp80.000).
 */
return [
    'profil_rasio' => [
        476 => [
            'pokok_per_juta' => 85000,
            'jasa_per_juta' => 15000,
            'jangka' => 12,
        ],
    ],
];
