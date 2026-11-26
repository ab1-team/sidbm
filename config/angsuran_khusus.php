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
 *
 * angsuran_eksak — profil TANPA pembulatan kecamatan:
 *   - pokok = alokasi / jangka
 *   - jasa  = alokasi * pros_jasa / 100 / jangka
 *   - dibulatkan ke rupiah bulat dengan round(), bukan Keuangan::pembulatan()
 *   - jangka           : tenor yang dipakai profil ini
 *   - pros_jasa        : persentase jasa total sepanjang tenor
 *   - alokasi_min      : profil hanya untuk alokasi di atas nominal ini;
 *                        pinjaman kecil tetap memakai pembulatan kecamatan
 *   - tanpa_pembulatan : penanda jalur eksak
 *
 * Contoh lokasi 476: alokasi Rp75 juta, tenor 24 bulan, jasa 36% →
 * 24× pokok Rp3.125.000 + jasa Rp1.125.000 (Σ jasa Rp27 juta).
 */
return [
    'profil_rasio' => [
        476 => [
            'pokok_per_juta' => 85000,
            'jasa_per_juta' => 15000,
            'jangka' => 12,
        ],
    ],
    'angsuran_eksak' => [
        476 => [
            'jangka' => 24,
            'pros_jasa' => 36,
            'alokasi_min' => 20000000,
            'tanpa_pembulatan' => true,
        ],
    ],
];
