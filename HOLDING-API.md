# API Holding — SI DBM

Dokumentasi endpoint laporan keuangan tenant untuk di-integrasikan ke **aplikasi holding pusat**.

Tujuan: client (holding) hanya merender JSON → view. Tidak ada kalkulasi sisi klien.

---

## Daftar Isi

1. [Autentikasi](#1-autentikasi)
2. [Konsep Umum](#2-konsep-umum)
3. [Periode & Tanggal Kondisi](#3-periode--tanggal-kondisi)
4. [Endpoint](#4-endpoint)
   - [4.1 Neraca](#41-neraca)
   - [4.2 Laba Rugi](#42-laba-rugi)
   - [4.3 Arus Kas](#43-arus-kas)
   - [4.4 Perubahan Ekuitas](#44-perubahan-ekuitas)
   - [4.5 CALK (Bagian C)](#45-calk-bagian-c)
5. [Aturan Rendering](#5-aturan-rendering)
6. [Contoh Kode Klien (PHP)](#6-contoh-kode-klien-php)
7. [Lampiran A: Ringkasan Field per Endpoint](#lampiran-a-ringkasan-field-per-endpoint)
8. [Lampiran B: Troubleshooting](#lampiran-b-troubleshooting)
9. [Lampiran C: Pola Holding License Middleware](#lampiran-c-pola-holding-license-middleware)
   - [C.1 Skema Database](#c1-skema-database-minimum)
   - [C.2 Kontrak Header](#c2-kontrak-header-request)
   - [C.3 Pseudocode Framework-Agnostic](#c3-pseudocode-framework-agnostic)
   - [C.4 Tiga Pola Isolasi Tenant](#c4-isolasi-tenant-tiga-pola-yang-dipakai-sidbm)
   - [C.5 Pattern Keuangan Utility](#c5-pattern-khusus-sidbm-keuangan-utility)
   - [C.6 Checklist Implementasi](#c6-checklist-implementasi-untuk-tim-lain)
   - [C.7 Snippet per Framework](#c7-framework-specific-snippets)
   - [C.8 Anti-Pattern](#c8-anti-pattern-yang-jangan-dilakukan)
   - [C.9 Referensi File SIDBM](#c9-referensi-file-di-sidbm)

---

## 1. Autentikasi

Setiap request harus membawa **dua header**:

| Header              | Nilai                                | Sumber                                                              |
|---------------------|--------------------------------------|---------------------------------------------------------------------|
| `X-Holding-Token`   | `api_secret` dari master License     | `licenses.api_secret` (lihat menu Master → License)                 |
| `X-Holding-Tenant`  | Slug tenant (`web_kec` atau `web_alternatif`) | `kecamatan.web_kec` atau `kecamatan.web_alternatif`     |

### Cara mendapatkan kredensial

1. Login ke **Master SIDBM** (`/master`).
2. Buka menu **License** di sidebar.
3. **Tambah License** untuk kecamatan target:
   - Pilih kecamatan dari dropdown.
   - Isi `API Secret` dengan token yang diberikan aplikasi holding pusat (bukan di-generate lokal).
   - `is_active` aktifkan.
   - `expired_at` kosongkan jika tidak ada batas waktu.
4. Kirim `X-Holding-Token` = nilai `api_secret` tersebut.
5. Kirim `X-Holding-Tenant` = `web_kec` atau `web_alternatif` dari tabel `kecamatan` untuk tenant tsb.

### Response bila gagal auth

| HTTP | Arti                                          |
|------|-----------------------------------------------|
| 401  | Token tidak cocok, tenant tidak ditemukan, atau license non-aktif / expired |

---

## 2. Konsep Umum

### Struktur JSON universal

Setiap response berhasil:

```json
{
  "success": true,
  "laporan": "<nama laporan>",
  "kecamatan": "<nama_kec>",
  "periode": { "...": "..." },
  "ringkasan": { "...": "..." },
  "data": [ /* atau objek, lihat per endpoint */ ]
}
```

### Tipe data saldo

- Semua angka saldo = **float** (rupiah, tanpa format).
- Tanda: debit (+) / kredit (−) **sesuai konvensi akuntansi tenant** (`lev1` 1=Aset debit, 2/3=Liab/Ekuitas kredit, 4=Pendapatan, 5=Beban).
- Untuk ditampilkan: format dengan `number_format($n, 2)`. Tidak ada simbol Rp / koma pemisah desimal di JSON.

### Aturan `lev1`

| lev1 | Nama umum | Posisi normal |
|------|-----------|---------------|
| `1`  | Aset      | Debit         |
| `2`  | Liabilitas| Kredit        |
| `3`  | Ekuitas   | Kredit        |
| `4`  | Pendapatan| Kredit        |
| `5`  | Beban     | Debit         |

Endpoint **hanya mengembalikan `lev1 <= 3`** (Neraca, Perubahan Ekuitas, CALK). Laba Rugi pakai `lev1` 4/5.

### Special case `3.2.02.01` (Laba Rugi Tahun Berjalan)

Rekening `3.2.02.01` adalah akun **koreksi ekuitas** yang diisi otomatis dari hasil **Laba Rugi**. Untuk laporan dengan `tgl_kondisi`, saldo rekening ini **di-override** dengan nilai `laba_rugi(tgl_kondisi)`.

Endpoint yang memakai aturan ini: **Neraca**, **Perubahan Ekuitas**, **CALK Bagian C**.

---

## 3. Periode & Tanggal Kondisi

Semua endpoint menerima query:

| Param   | Wajib | Tipe    | Default                                   | Contoh         |
|---------|-------|---------|-------------------------------------------|----------------|
| `tahun` | Ya    | int     | —                                         | `2025`         |
| `bulan` | Tidak | int 1-12| `12` (tahunan)                            | `6`            |
| `hari`  | Tidak | int 1-31| Hari terakhir bulan tsb (atau `31` des)   | `30`           |
| `semester` (khusus arus-kas) | Tidak | `1`/`2` | — | `1` (Sem I) / `2` (Sem II) |

### Aturan turun `tgl_kondisi`

- `bulan` kosong → tahunan → `bulan=12`, `hari=31` (atau `tahun-12-31`).
- `hari` kosong → akhir bulan `tahun-bulan`.
- `tgl_kondisi` final berformat `YYYY-MM-DD`. Contoh: `tahun=2025&bulan=6&hari=30` → `2025-06-30`.

### Label periode

Tiap response menyertakan `periode.sub_judul` (string siap tampil):

| Mode           | Contoh sub_judul                                       |
|----------------|--------------------------------------------------------|
| Bulanan        | `Bulan Juni 2025`                                      |
| Tahunan        | `Tahun 2025`                                           |
| Semester I     | `Semester I Tahun 2025`                                |
| Semester II    | `Semester II Tahun 2025`                               |
| Laba Rugi (bulanan) | `Periode 01 Januari 2025 S.D 30 Juni 2025`        |
| Laba Rugi (tahunan) | `Tahun 2025`                                       |
| Neraca         | `Per 30 Juni 2025`                                     |

---

## 4. Endpoint

Base URL: `https://<host-tenant>/api/v1/holding/laporan`

### 4.1 Neraca

```
GET /neraca?tahun=2025&bulan=6&hari=30
```

#### Response

```json
{
  "success": true,
  "laporan": "Neraca",
  "kecamatan": "Kecamatan ABC",
  "tgl_kondisi": "2025-06-30",
  "sub_judul": "Per 30 Juni 2025",
  "ringkasan": {
    "total_aset": 150000000.0,
    "total_liabilitas_ekuitas": 150000000.0,
    "selisih": 0.0
  },
  "data": [
    {
      "kode_akun": "1",
      "nama_akun": "Aset",
      "lev1": "1",
      "saldo": 150000000.0,
      "akun2": [
        {
          "kode_akun": "1.1",
          "nama_akun": "Aset Lancar",
          "saldo": 80000000.0,
          "akun3": [
            { "kode_akun": "1.1.01", "nama_akun": "Kas", "saldo": 50000000.0 },
            { "kode_akun": "1.1.02", "nama_akun": "Bank", "saldo": 30000000.0 }
          ]
        }
      ]
    }
  ]
}
```

#### Cara render (mengikuti view tenant)

| Baris    | Sumber                               | Style         |
|----------|--------------------------------------|---------------|
| Header   | `data[]` lev1 nama_akun              | Bold          |
| Subtotal | `akun2.saldo`                        | Indent 1      |
| Rincian  | `akun3[].saldo`                      | Indent 2      |
| **Jumlah {lev1 nama}** | `data[].saldo`             | Bold, border  |
| **Jumlah Liabilitas + Ekuitas** | `ringkasan.total_liabilitas_ekuitas` | Bold, border |

> **Catatan:** `rekening` (level 4) **tidak** di-include di Neraca. View tenant hanya menghitung per-rekening secara internal lalu agregat ke `akun3.saldo`.

---

### 4.2 Laba Rugi

```
GET /laba-rugi?tahun=2025&bulan=6&hari=30
```

#### Response

```json
{
  "success": true,
  "laporan": "Laba Rugi",
  "kecamatan": "Kecamatan ABC",
  "periode": {
    "jenis": "Bulanan",
    "tgl_kondisi": "2025-06-30",
    "sub_judul": "Periode 01 Januari 2025 S.D 30 Juni 2025"
  },
  "ringkasan": {
    "pendapatan": 50000000.0,
    "beban": 30000000.0,
    "pendapatan_non_ops": 0.0,
    "beban_non_ops": 0.0,
    "lr_operasional": {
      "s_d_bulan_lalu": 10000000.0,
      "periode_ini": 5000000.0,
      "s_d_sekarang": 15000000.0
    },
    "lr_non_operasional": {
      "s_d_bulan_lalu": 0.0,
      "periode_ini": 0.0,
      "s_d_sekarang": 0.0
    },
    "sebelum_pajak": {
      "s_d_bulan_lalu": 10000000.0,
      "periode_ini": 5000000.0,
      "s_d_sekarang": 15000000.0
    },
    "pph": {
      "s_d_bulan_lalu": 0.0,
      "periode_ini": 0.0,
      "s_d_sekarang": 0.0
    },
    "setelah_pajak": {
      "s_d_bulan_lalu": 10000000.0,
      "periode_ini": 5000000.0,
      "s_d_sekarang": 15000000.0
    }
  },
  "data": {
    "pendapatan": [
      {
        "kode_akun": "4.1",
        "nama_akun": "Pendapatan Operasional",
        "saldo_bln_lalu": 30000000.0,
        "saldo_periode_ini": 10000000.0,
        "saldo": 40000000.0,
        "rekening": [
          {
            "kode_akun": "4.1.01",
            "nama_akun": "Pendapatan Pinjaman",
            "saldo_bln_lalu": 25000000.0,
            "saldo_periode_ini": 8000000.0,
            "saldo": 33000000.0
          }
        ]
      }
    ],
    "beban": [],
    "pendapatan_non_ops": [],
    "beban_non_ops": []
  }
}
```

#### Cara render (mengikuti view tenant)

Empat section berurutan: `pendapatan` → `beban` → `pendapatan_non_ops` → `beban_non_ops`.

Tiap baris tampilkan **3 kolom** angka:

| Kolom             | Sumber              |
|-------------------|---------------------|
| s/d bulan lalu    | `saldo_bln_lalu`    |
| Periode ini       | `saldo_periode_ini` |
| s/d sekarang      | `saldo`             |

Hitungan ringkasan di `ringkasan.*` — render sebagai baris total sesuai struktur A/B/C:

| Baris                  | Sumber                                  |
|------------------------|-----------------------------------------|
| **A. Laba Rugi Operasional** | `ringkasan.lr_operasional.*` (3 kolom) |
| **B. Laba Rugi Non Operasional** | `ringkasan.lr_non_operasional.*` |
| **C. Sebelum Pajak**   | `ringkasan.sebelum_pajak.*`             |
| **PPh**                | `ringkasan.pph.*`                       |
| **C. Setelah Pajak**   | `ringkasan.setelah_pajak.*`             |

---

### 4.3 Arus Kas

```
GET /arus-kas?tahun=2025&bulan=6&hari=30
GET /arus-kas?tahun=2025&semester=1   # Semester I, tgl_kondisi=YYYY-06-30
GET /arus-kas?tahun=2025&semester=2   # Semester II, tgl_kondisi=YYYY-12-31
GET /arus-kas?tahun=2025              # Tahunan, tgl_kondisi=YYYY-12-31
```

#### Response

```json
{
  "success": true,
  "laporan": "Arus Kas",
  "kecamatan": "Kecamatan ABC",
  "periode": {
    "jenis": "Bulanan",
    "tgl_kondisi": "2025-06-30",
    "sub_judul": "Bulan Juni 2025"
  },
  "ringkasan": {
    "saldo_awal": 10000000.0,
    "total_masuk": 50000000.0,
    "total_keluar": 30000000.0,
    "kas_operasi": 15000000.0,
    "kas_investasi": 0.0,
    "kas_pendanaan": 0.0,
    "kenaikan_penurunan": 15000000.0,
    "saldo_akhir": 25000000.0,
    "group": [
      { "nama": "Arus Kas Masuk dari Aktivitas Operasi", "saldo": 50000000.0 },
      { "nama": "Arus Kas Keluar untuk Aktivitas Operasi", "saldo": 35000000.0 }
    ]
  },
  "data": [
    {
      "id": 1,
      "parent": "saldo_awal",
      "kategori": null,
      "nama": "Saldo Awal Bulan",
      "sub": 0,
      "saldo": 10000000.0,
      "detail": []
    },
    {
      "id": 2,
      "parent": "masuk",
      "kategori": "operasi",
      "nama": "Penerimaan Pinjaman",
      "sub": 0,
      "saldo": 50000000.0,
      "detail": [
        { "id": 10, "kode_akun": null, "nama_akun": "Pinjaman Kelompok A", "saldo": 30000000.0 },
        { "id": 11, "kode_akun": null, "nama_akun": "Pinjaman Kelompok B", "saldo": 20000000.0 }
      ]
    }
  ]
}
```

#### Cara render (mengikuti view tenant)

| Baris                              | Sumber                              |
|------------------------------------|-------------------------------------|
| Saldo Awal                         | `data[0]` (id=1, parent=saldo_awal) |
| Tiap parent                        | `data[]` lain → `nama` + `saldo`    |
| **Jumlah Aktivitas Operasi**       | `ringkasan.kas_operasi`             |
| **Jumlah Aktivitas Investasi**     | `ringkasan.kas_investasi`           |
| **Jumlah Aktivitas Pendanaan**     | `ringkasan.kas_pendanaan`           |
| **Kenaikan/Penurunan Kas**         | `ringkasan.kenaikan_penurunan`      |
| **Saldo Akhir Kas**                | `ringkasan.saldo_akhir`             |

Field `detail[]` di tiap parent berisi baris-baris child (sumber dana / penggunaan dana). Tampilkan sebagai sub-row indent.

---

### 4.4 Perubahan Ekuitas

```
GET /perubahan-ekuitas?tahun=2025&bulan=6&hari=30
```

#### Response

```json
{
  "success": true,
  "laporan": "Perubahan Ekuitas",
  "kecamatan": "Kecamatan ABC",
  "periode": {
    "tgl_kondisi": "2025-06-30",
    "sub_judul": "Bulan Juni 2025"
  },
  "ringkasan": {
    "ekuitas_awal": 100000000.0,
    "setoran": 5000000.0,
    "penarikan": -2000000.0,
    "dividen": 0.0,
    "koreksi": 0.0,
    "laba_rugi": 12000000.0,
    "ekuitas_akhir": 115000000.0
  },
  "data": [
    { "kode_akun": "3.1.01.01", "nama_akun": "Modal Disetor", "saldo_awal": 50000000.0, "saldo_akhir": 53000000.0, "mutasi": 3000000.0 },
    { "kode_akun": "3.1.01.02", "nama_akun": "Modal Belum Disetor", "saldo_awal": 10000000.0, "saldo_akhir": 12000000.0, "mutasi": 2000000.0 },
    { "kode_akun": "3.2.01.01", "nama_akun": "Tambahan Modal Disetor", "saldo_awal": 0.0, "saldo_akhir": 5000000.0, "mutasi": 5000000.0 },
    { "kode_akun": "3.2.01.02", "nama_akun": "Penarikan Modal", "saldo_awal": 0.0, "saldo_akhir": -2000000.0, "mutasi": -2000000.0 },
    { "kode_akun": "3.2.01.03", "nama_akun": "Dividen", "saldo_awal": 0.0, "saldo_akhir": 0.0, "mutasi": 0.0 },
    { "kode_akun": "3.2.02.01", "nama_akun": "Laba Rugi Tahun Berjalan", "saldo_awal": 40000000.0, "saldo_akhir": 52000000.0, "mutasi": 12000000.0 }
  ]
}
```

#### Cara render (mengikuti view tenant)

Tiap row di `data[]` punya 3 kolom angka: `saldo_awal`, `mutasi`, `saldo_akhir`.

| Baris                          | Sumber                              |
|--------------------------------|-------------------------------------|
| Tiap rekening ekuitas          | `data[]`                            |
| **Ekuitas Awal**               | `ringkasan.ekuitas_awal`            |
| **Setoran Modal**              | `ringkasan.setoran`                 |
| **Penarikan Modal**            | `ringkasan.penarikan`               |
| **Dividen**                    | `ringkasan.dividen`                 |
| **Koreksi**                    | `ringkasan.koreksi`                 |
| **Laba Rugi Tahun Berjalan**   | `ringkasan.laba_rugi`               |
| **Modal Akhir**                | `ringkasan.ekuitas_akhir`           |

> **Catatan:** `3.2.02.01` (Laba Rugi Tahun Berjalan) mengikuti special case (lihat §2). `saldo_akhir` = `laba_rugi(tgl_kondisi)`.

---

### 4.5 CALK (Bagian C)

```
GET /calk?tahun=2025&bulan=6&hari=30
```

> Endpoint ini khusus untuk **Bagian C** (rincian akun per rekening, mirip neraca). Untuk Bagian A/B (narasi, kebijakan, dll) tetap di-handle di sisi klien atau modul internal holding.

#### Response

```json
{
  "success": true,
  "laporan": "Catatan Atas Laporan Keuangan (CALK)",
  "kecamatan": "Kecamatan ABC",
  "periode": {
    "tgl_kondisi": "2025-06-30",
    "sub_judul": "Bulan Juni Tahun 2025",
    "tgl_mad": "2024-04-15"
  },
  "ringkasan": {
    "point_a": "Per 30 Juni 2025, kondisi keuangan Kecamatan ABC...",
    "total_aset": 150000000.0,
    "total_liabilitas_ekuitas": 150000000.0,
    "selisih": 0.0
  },
  "data": {
    "point_a": "Per 30 Juni 2025, kondisi keuangan Kecamatan ABC...",
    "catatan": "<narasi Bagian B dalam HTML/Markdown — null jika belum diisi>",
    "rincian_akun": [
      {
        "kode_akun": "1",
        "nama_akun": "Aset",
        "lev1": "1",
        "saldo": 150000000.0,
        "akun2": [
          {
            "kode_akun": "1.1",
            "nama_akun": "Aset Lancar",
            "saldo": 80000000.0,
            "akun3": [
              {
                "kode_akun": "1.1.01",
                "nama_akun": "Kas",
                "saldo": 50000000.0,
                "rekening": [
                  { "kode_akun": "1.1.01.01", "nama_akun": "Kas Besar", "saldo": 30000000.0 },
                  { "kode_akun": "1.1.01.02", "nama_akun": "Kas Kecil", "saldo": 20000000.0 }
                ]
              }
            ]
          }
        ]
      }
    ],
    "saldo_calk": [ /* collection Saldo::where('kode_akun', kd_kec)->where('tahun', tahun) */ ],
    "penandatangan": {
      "sekretaris": { "id": 1, "name": "...", "...": "..." } /* null jika belum ada */,
      "bendahara":  { "id": 2, "name": "...", "...": "..." },
      "pengawas":   null,
      "direktur":   { "id": 3, "name": "...", "...": "..." }
    }
  }
}
```

#### Cara render (mengikuti view tenant, line 230-306)

Tiap `rincian_akun[]` adalah **pohon 4-level**: `lev1 → akun2 → akun3 → rekening`.

Tampilan:

| Baris                         | Sumber                                | Style      |
|-------------------------------|---------------------------------------|------------|
| Header lev1                   | `rincian_akun[].nama_akun`            | Bold       |
| Subheader akun2               | `akun2[].nama_akun`                   | Bold       |
| Rincian akun3                 | `akun3[].saldo` (agregat)             | Indent 1   |
| Rincian per rekening          | `akun3[].rekening[].saldo`            | Indent 2   |
| **Jumlah {lev1 nama}**       | `rincian_akun[].saldo`                | Bold       |
| **Jumlah Liab + Ekuitas**     | `ringkasan.total_liabilitas_ekuitas`  | Bold       |

`point_a` adalah teks narasi Bagian A — tampilkan di section atas.

`penandatangan` — bisa `null` per role; tampilkan nama bila ada, lewati bila tidak.

---

## 5. Aturan Rendering

1. **Tidak ada kalkulasi klien.** Semua angka final (subtotal, total, selisih) sudah di JSON via `ringkasan`.
2. **Format angka:** `number_format($n, 2, ',', '.')` (desimal koma, ribuan titik) untuk tampilan Indonesia. JSON selalu pakai `.` untuk desimal.
3. **Tanda negatif:** tampilkan dengan prefix `-` (jangan kurung).
4. **Saldo 0:** tampilkan `0,00` atau strip, konsisten dengan view tenant.
5. **Hierarki indent:** padding-left 16/32/48 px per level.
6. **Row total/grand total:** bold + border-top 1px.
7. **Sub_judul:** tampilkan di header laporan, di bawah judul utama.
8. **Tgl kondisi:** format `d F Y` (contoh: `30 Juni 2025`).

---

## 6. Contoh Kode Klien (PHP)

```php
<?php
// holding-api-client.php

class HoldingLaporanClient
{
    private string $baseUrl;
    private string $apiToken;
    private string $tenantSlug;

    public function __construct(string $baseUrl, string $apiToken, string $tenantSlug)
    {
        $this->baseUrl    = rtrim($baseUrl, '/');
        $this->apiToken   = $apiToken;
        $this->tenantSlug = $tenantSlug;
    }

    private function request(string $endpoint, array $params): array
    {
        $url = $this->baseUrl . '/api/v1/holding/laporan/' . $endpoint
             . '?' . http_build_query($params);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'X-Holding-Token: ' . $this->apiToken,
                'X-Holding-Tenant: ' . $this->tenantSlug,
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT        => 30,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200) {
            throw new RuntimeException("HTTP {$code}: {$body}");
        }
        $json = json_decode($body, true);
        if (!($json['success'] ?? false)) {
            throw new RuntimeException("API error: " . ($json['message'] ?? 'unknown'));
        }
        return $json;
    }

    public function neraca(int $tahun, ?int $bulan = null, ?int $hari = null): array
    {
        return $this->request('neraca', array_filter([
            'tahun' => $tahun, 'bulan' => $bulan, 'hari' => $hari,
        ]));
    }

    public function labaRugi(int $tahun, ?int $bulan = null, ?int $hari = null): array
    {
        return $this->request('laba-rugi', array_filter([
            'tahun' => $tahun, 'bulan' => $bulan, 'hari' => $hari,
        ]));
    }

    public function arusKas(int $tahun, ?int $bulan = null, ?int $hari = null, ?int $semester = null): array
    {
        return $this->request('arus-kas', array_filter([
            'tahun' => $tahun, 'bulan' => $bulan, 'hari' => $hari, 'semester' => $semester,
        ]));
    }

    public function perubahanEkuitas(int $tahun, ?int $bulan = null, ?int $hari = null): array
    {
        return $this->request('perubahan-ekuitas', array_filter([
            'tahun' => $tahun, 'bulan' => $bulan, 'hari' => $hari,
        ]));
    }

    public function calk(int $tahun, ?int $bulan = null, ?int $hari = null): array
    {
        return $this->request('calk', array_filter([
            'tahun' => $tahun, 'bulan' => $bulan, 'hari' => $hari,
        ]));
    }
}

// --- Penggunaan ---
$client = new HoldingLaporanClient(
    'https://app.sidbm.net',
    'DbRz5uVJsttoNDuSYbYPHjDMpkPxoDOx1P75dl4G', // api_secret dari Master → License
    'app.sidbm.net'                                 // web_kec atau web_alternatif
);

$neraca = $client->neraca(2025, 6, 30);
echo "Kecamatan: {$neraca['kecamatan']}\n";
echo "Sub Judul: {$neraca['sub_judul']}\n";
echo "Total Aset: " . number_format($neraca['ringkasan']['total_aset'], 2) . "\n";
echo "Selisih: " . number_format($neraca['ringkasan']['selisih'], 2) . "\n";
```

---

## Lampiran: Ringkasan Field per Endpoint

| Endpoint                | Hirarki                                                | Field total di `ringkasan`                                       |
|-------------------------|--------------------------------------------------------|------------------------------------------------------------------|
| `neraca`                | lev1 → akun2 → akun3 (tanpa rekening)                  | `total_aset`, `total_liabilitas_ekuitas`, `selisih`              |
| `laba-rugi`             | 4 section × (group + rekening)                         | `pendapatan`, `beban`, `lr_operasional` (3kolom), `lr_non_operasional` (3kolom), `sebelum_pajak` (3kolom), `pph` (3kolom), `setelah_pajak` (3kolom) |
| `arus-kas`              | rows flat + `detail[]`                                 | `saldo_awal`, `total_masuk`, `total_keluar`, `kas_operasi`, `kas_investasi`, `kas_pendanaan`, `kenaikan_penurunan`, `saldo_akhir`, `group[]` |
| `perubahan-ekuitas`     | rows flat (rekening 3.x)                               | `ekuitas_awal`, `setoran`, `penarikan`, `dividen`, `koreksi`, `laba_rugi`, `ekuitas_akhir` |
| `calk` (Bagian C)       | lev1 → akun2 → akun3 → rekening                        | `point_a`, `total_aset`, `total_liabilitas_ekuitas`, `selisih`   |

---

## Lampiran B: Troubleshooting

Kasus umum saat integrasi + cara diagnosis.

### B.1 Holding menampilkan "April-Mei kosong" padahal DB ada datanya

**Gejala:** subsidiary return HTTP 200 dengan `data[]` penuh (cek via curl), tapi view holding render kosong.

**Diagnosis:**

```bash
# 1. Verify response subsidiary tidak benar-benar kosong
curl -s -H "X-Holding-Token: <token>" \
     -H "X-Holding-Tenant: <tenant>" \
     "https://<host>/api/v1/holding/laporan/neraca?tahun=2026&bulan=4&hari=30" \
   | python -m json.tool | head -50
```

**Penyebab paling umum:** holding render baris via `data[].akun2[].akun3[].rekening[]`, tapi field `rekening` **tidak di-expose** di output neraca (lihat §4.1). Untuk neraca, render via `akun3.saldo` saja.

**Field `rekening` ada hanya di:** `laba-rugi`, `calk` (Bagian C), `arus-kas` (sebagai `detail[]`). **Tidak ada** di `neraca` dan `perubahan-ekuitas`.

### B.2 Error `Table 'xxx.rekening_0' doesn't exist`

**Penyebab:** middleware `HoldingLicense` tidak jalan sebelum controller. Suffix tabel fallback ke `_0` (default) yang tidak ada.

**Fix:** pastikan middleware terdaftar di route group:

```php
// routes/api.php
Route::middleware(['holding.license'])->prefix('v1/holding')->group(function () {
    // ...
});
```

### B.3 401 "Token tidak valid" padahal token baru

**Cek:**
1. `licenses.is_active = true` (bukan 0/false)
2. `licenses.expired_at` NULL atau > now
3. `kecamatan.web_kec` atau `web_alternatif` cocok dengan header `X-Holding-Tenant`
4. Tidak ada spasi/newline tak terlihat di `api_secret` (paste dari notepad kadang bawa newline)

**Debug query:**

```sql
SELECT l.id, l.api_secret, l.is_active, l.expired_at, k.web_kec, k.web_alternatif
FROM licenses l JOIN kecamatan k ON k.id = l.kecamatan_id
WHERE l.api_secret = '<token_paste>';
```

### B.4 Response lambat (> 5 detik)

**Penyebab umum:**
- Query `transaksi_*` tanpa index pada `tgl_transaksi` (pastikan ada composite index `(tgl_transaksi, rekening_debit)`)
- Reload `Rekening` dengan nested `akun3.rek.kom_saldo` — kalau `kom_saldo` ribuan baris, query bisa N+1

**Quick win:** tambahkan `LIMIT` di `validatePeriode` kalau client cuma butuh 1 bulan — tapi biasanya laporan keuangan butuh full range.



---

## Lampiran C: Pola Holding License Middleware

Panduan ini untuk tim yang ingin **membuat middleware setara `HoldingLicense` di aplikasi non-SIDBM** (framework lain, atau SIDBM yang akan datang dengan stack berbeda).

Tujuan akhir: endpoint laporan tenant hanya bisa diakses kalau:
1. `X-Holding-Token` cocok dengan `api_secret` di tabel `licenses`
2. `X-Holding-Tenant` cocok dengan `web_kec` / `web_alternatif` di tabel `kecamatan`
3. License `is_active = true` dan tidak `expired_at < now`
4. Query yang dijalankan otomatis **ter-isolasi per tenant** (tidak bocor data tenant lain)

### C.1 Skema Database Minimum

```sql
-- Tabel kecamatan (atau "tenant" / "lembaga" — nama bisa disesuaikan)
CREATE TABLE kecamatan (
  id              BIGINT PRIMARY KEY AUTO_INCREMENT,
  nama_kec        VARCHAR(100) NOT NULL,
  kd_kec          VARCHAR(20),                  -- kode BPS / wilayah
  web_kec         VARCHAR(100) UNIQUE,          -- URL primer untuk X-Holding-Tenant
  web_alternatif  VARCHAR(100) UNIQUE,          -- URL alternatif (opsional, juga valid)
  -- field lain sesuai bisnis...
);

-- Tabel license (token per kecamatan, 1 tenant = 1 license)
CREATE TABLE licenses (
  id            BIGINT PRIMARY KEY AUTO_INCREMENT,
  kecamatan_id  BIGINT NOT NULL UNIQUE,         -- 1 license per kecamatan
  api_secret    VARCHAR(255) NOT NULL,
  is_active     TINYINT(1) NOT NULL DEFAULT 1,
  expired_at    DATETIME NULL,                  -- NULL = tidak ada batas waktu
  created_at    TIMESTAMP NULL,
  updated_at    TIMESTAMP NULL,
  FOREIGN KEY (kecamatan_id) REFERENCES kecamatan(id)
);
CREATE INDEX idx_license_token ON licenses(api_secret);
```

**Catatan penting:**
- `api_secret` harus **diinput manual** oleh admin master, bukan di-generate lokal. Sumbernya: aplikasi holding pusat.
- `expired_at` nullable. `NULL` = tidak pernah expired.
- `is_active` boolean. Hard-revoke dengan set `false`.
- `web_kec` / `web_alternatif` di-allow null tapi untuk holding harus unique.

### C.2 Kontrak Header Request

| Header             | Tipe   | Wajib | Contoh                                |
|--------------------|--------|-------|---------------------------------------|
| `X-Holding-Token`  | string | Ya    | `DbRz5uVJsttoNDuSYbYPHjDMpkPxoDOx1P75dl4G` |
| `X-Holding-Tenant` | string | Ya    | `app.sidbm.net` atau `sidbm_baru.test`     |

**Response kalau gagal:**

| HTTP | Body                                                  | Arti                                |
|------|-------------------------------------------------------|-------------------------------------|
| 401  | `{"success": false, "message": "Token tidak valid."}` | Token tidak cocok / tenant tidak ditemukan |
| 403  | `{"success": false, "message": "Lisensi kedaluwarsa."}` | License expired                     |

### C.3 Pseudocode Framework-Agnostic

```python
def holding_license_middleware(request, next_handler):
    token = request.headers.get('X-Holding-Token')
    slug  = request.headers.get('X-Holding-Tenant')

    # 1. Validasi header ada
    if not token or not slug:
        return 401, "Token tidak valid."

    # 2. Lookup license + kecamatan (1 query)
    license = db.query("""
        SELECT l.*, k.id as kec_id, k.web_kec, k.web_alternatif
        FROM licenses l
        JOIN kecamatan k ON l.kecamatan_id = k.id
        WHERE l.api_secret = :token
          AND l.is_active = 1
          AND (l.expired_at IS NULL OR l.expired_at > NOW())
          AND (k.web_kec = :slug OR k.web_alternatif = :slug)
        LIMIT 1
    """, token=token, slug=slug)

    if not license:
        return 401, "Token tidak valid."

    # 3. Set tenant context untuk downstream query
    request.tenant = {
        'kecamatan_id': license.kec_id,
        'license_id':   license.id,
        'web_slug':     slug,
    }

    # 4. Patch connection / repository agar semua query auto-filter by tenant
    set_tenant_filter(license.kec_id)

    return next_handler(request)
```

**Tiga hal yang harus di-set sebelum `next_handler` dipanggil:**
1. `request.tenant` — identitas tenant untuk logging / audit
2. Filter global aktif (lihat C.4)
3. (Opsional) session/config storage supaya model bisa baca `lokasi` → suffix tabel

### C.4 Isolasi Tenant: Tiga Pola yang Dipakai SIDBM

Tiga pola yang umum di multi-tenant. SIDBM pakai **kombinasi #1 + #3**.

#### Pola A — Suffix Tabel (`tenant_{id}`)

Pola SIDBM: tabel per tenant dinamai `kelompok_{lokasi}`, `transaksi_{lokasi}`, `rekening_{lokasi}`.

**Kelebihan:** isolasi kuat (query cross-tenant = syntax error), mudah backup per tenant.
**Kekurangan:** banyak tabel di satu DB, migrasi schema harus loop semua tenant.

**Implementasi Laravel (trait `TenantAware`):**

```php
trait TenantAware
{
    public function getTenantSuffix(): string
    {
        // Prioritas 1: config global (di-set middleware)
        if ($suffix = config('tenant.suffix')) {
            return $suffix;
        }
        // Prioritas 2: session (untuk user web yg login)
        if (session()->has('lokasi')) {
            return '_' . session('lokasi');
        }
        // Prioritas 3: request attribute (untuk API lain)
        if ($suffix = request()->attributes->get('tenant_suffix')) {
            return $suffix;
        }
        // Fallback: tenant default (umumnya '0' / 'default')
        return '_0';
    }

    protected function setTenantTable(): void
    {
        $this->setTable($this->getBaseTableName() . $this->getTenantSuffix());
    }
}
```

**Middleware set suffix:**

```php
// app/Http/Middleware/HoldingLicense.php
public function handle(Request $request, Closure $next)
{
    // ... lookup license (lihat C.3) ...

    // Kunci: pakai kecamatan->id sebagai suffix, BUKAN nama
    config(['tenant.suffix' => '_' . $license->kecamatan->id]);

    $request->attributes->set('holding_kecamatan', $license->kecamatan);
    $request->attributes->set('holding_license', $license);

    return $next($request);
}
```

**Query manual di service (untuk utility class `Keuangan`):**

```php
// app/Utils/Keuangan.php
$sql = "SELECT ... FROM transaksi_" . Session::get('lokasi')
     . " WHERE tgl_transaksi BETWEEN ? AND ?";
```

> ⚠️ **Penting:** suffix tabel = `id` kecamatan, BUKAN `web_kec` / `web_alternatif`. `id` adalah integer cocok untuk suffix; slug bisa ada karakter non-aman.

#### Pola B — Kolom `tenant_id` di Tabel

Semua tenant share tabel, filter `WHERE tenant_id = ?`. Standar untuk SaaS modern.

```sql
-- Bukan: kelompok_1, kelompok_2, kelompok_3
-- Tapi:  kelompok dengan kolom tenant_id

CREATE TABLE kelompok (
  id          BIGINT PRIMARY KEY AUTO_INCREMENT,
  tenant_id   BIGINT NOT NULL,
  nama_kel    VARCHAR(100),
  -- ...
  INDEX idx_tenant (tenant_id)
);
```

**Middleware set global scope (Laravel):**

```php
// app/Http/Middleware/TenantScope.php
public function handle(Request $request, Closure $next)
{
    $tenantId = $request->attributes->get('tenant_id');
    if (!$tenantId) {
        return response()->json(['message' => 'Tenant tidak teridentifikasi'], 401);
    }
    // Pasang global scope ke semua model
    Model::addGlobalScope('tenant', function (Builder $builder) use ($tenantId) {
        $builder->where('tenant_id', $tenantId);
    });
    return $next($request);
}
```

**Kelebihan:** migrasi schema sekali, hemat storage, query analytics cross-tenant mudah.
**Kekurangan:** harus disiplin pasang scope di semua model (atau auto-apply via trait `BelongsToTenant`).

#### Pola C — Database Terpisah per Tenant

Paling aman, paling mahal. Tiap tenant punya DB sendiri.

**Middleware (Laravel):**

```php
config([
    'database.connections.tenant.host'     => $tenant->db_host,
    'database.connections.tenant.database' => $tenant->db_name,
    'database.connections.tenant.username' => $tenant->db_user,
    'database.connections.tenant.password' => $tenant->db_pass,
]);
DB::purge('tenant');
DB::reconnect('tenant');
```

Gunakan kalau: compliance严格要求隔离, atau volume data per tenant besar (10GB+).

### C.5 Pattern Khusus SIDBM: Keuangan Utility

SIDBM tidak hitung saldo di endpoint controller. Semua kalkulasi saldo ada di class `App\Utils\Keuangan` (PHP) atau `app/utils/keuangan.js` (JS). Controller cuma panggil helper, bungkus ke JSON.

**Kenapa?** Karena view tenant (Blade) juga panggil helper yang sama. **Satu source of truth = konsistensi otomatis** antara laporan di subsidiary vs laporan di holding.

**Contoh struktur:**

```
app/
  Utils/
    Keuangan.php        ← kalkulasi saldo (dipanggil view & controller)
    Tanggal.php         ← format tanggal Indonesia
  Http/
    Controllers/
      Api/
        HoldingLaporanController.php   ← panggil Keuangan, return JSON
```

**Prinsipnya:**
- Controller = transport layer (HTTP request → response)
- Utility / Service = business logic (kalkulasi, validasi domain)
- Model = data access (query DB)
- View = presentation (Blade / template)

Pisahkan tiga layer ini. Kalau holding audit data, mereka hanya boleh cek transport + presentation. Kalau angka beda, masalahnya di utility.

### C.6 Checklist Implementasi untuk Tim Lain

Saat membangun middleware + laporan di stack baru, cek poin-poin ini:

- [ ] **Tabel `licenses`** ada kolom `api_secret`, `is_active`, `expired_at` (nullable), FK ke tenant
- [ ] **Unique index** pada `licenses.api_secret` dan `kecamatan.web_kec` / `web_alternatif`
- [ ] **Middleware** jalankan SEBELUM controller (di Kernel / Router level)
- [ ] **Lookup 1 query** saja (join license + tenant), jangan N+1
- [ ] **403 vs 401** dibedakan: 401 = token salah, 403 = license expired
- [ ] **Header response `WWW-Authenticate`** di-set untuk client HTTP library
- [ ] **Tenant context di-set** sebelum query pertama (config / request attribute / session)
- [ ] **Test dengan token valid + tenant valid** → harusnya return data
- [ ] **Test dengan token invalid** → 401
- [ ] **Test dengan tenant invalid** (slug tidak ada) → 401
- [ ] **Test dengan license inactive** → 401
- [ ] **Test dengan license expired** → 403
- [ ] **Test cross-tenant**: token tenant A, coba akses resource tenant B → harusnya tidak bisa (kalau ada endpoint by-id)
- [ ] **Log setiap request** dengan `tenant_id` + `endpoint` untuk audit
- [ ] **Rate limit per tenant** (opsional tapi recommended)

### C.7 Framework-Specific Snippets

#### Laravel 11 (seperti SIDBM saat ini)

```php
// app/Http/Kernel.php — di array 'middleware'
'holding.api' => [
    \App\Http\Middleware\HoldingLicense::class,
],

// routes/api.php
Route::middleware('holding.api')->prefix('v1/holding')->group(function () {
    Route::get('laporan/neraca', [HoldingLaporanController::class, 'neraca']);
    // ...
});
```

#### Express.js (Node)

```javascript
// middleware/holdingLicense.js
async function holdingLicense(req, res, next) {
    const token = req.headers['x-holding-token'];
    const slug  = req.headers['x-holding-tenant'];
    if (!token || !slug) return res.status(401).json({message: 'Token tidak valid.'});

    const [rows] = await pool.query(`
        SELECT l.*, k.id as kec_id
        FROM licenses l JOIN kecamatan k ON l.kecamatan_id = k.id
        WHERE l.api_secret = ? AND l.is_active = 1
          AND (l.expired_at IS NULL OR l.expired_at > NOW())
          AND (k.web_kec = ? OR k.web_alternatif = ?)
        LIMIT 1
    `, [token, slug, slug]);

    if (!rows.length) return res.status(401).json({message: 'Token tidak valid.'});
    if (rows[0].expired_at && rows[0].expired_at < new Date()) {
        return res.status(403).json({message: 'Lisensi kedaluwarsa.'});
    }

    req.tenant = {kecamatan_id: rows[0].kec_id, license_id: rows[0].id};
    // Set Postgres schema search_path (kalau pakai schema-per-tenant)
    await pool.query(`SET app.current_tenant = ${rows[0].kec_id}`);
    next();
}
```

#### Django (Python)

```python
# middleware.py
class HoldingLicenseMiddleware:
    def __init__(self, get_response):
        self.get_response = get_response

    def __call__(self, request):
        token = request.headers.get('X-Holding-Token')
        slug  = request.headers.get('X-Holding-Tenant')
        if not token or not slug:
            return JsonResponse({'message': 'Token tidak valid.'}, status=401)

        license = License.objects.select_related('kecamatan').filter(
            api_secret=token,
            is_active=True,
        ).filter(
            Q(expired_at__isnull=True) | Q(expired_at__gt=timezone.now())
        ).filter(
            Q(kecamatan__web_kec=slug) | Q(kecamatan__web_alternatif=slug)
        ).first()

        if not license:
            return JsonResponse({'message': 'Token tidak valid.'}, status=401)
        if license.is_expired():
            return JsonResponse({'message': 'Lisensi kedaluwarsa.'}, status=403)

        request.tenant = {'kecamatan_id': license.kecamatan.id}
        connection.schema_name = f'tenant_{license.kecamatan.id}'  # django-tenants
        return self.get_response(request)
```

### C.8 Anti-Pattern (Yang Jangan Dilakukan)

❌ **Generate api_secret di subsidiary.** Selalu input manual dari holding pusat. Kalau subsidiary yang generate, holding pusat tidak bisa audit.

❌ **Token disimpan plain di query string.** Selalu header. Query string ke-log di server proxy / load balancer.

❌ **License tanpa `is_active`.** Hard delete license = tidak bisa di-revoke sementara untuk audit. Pakai boolean flag.

❌ **Tenant context di-set setelah query pertama.** Middleware harus set SEBELUM controller jalan. Kalau lupa, query pertama bocor data cross-tenant.

❌ **Suffix tabel dari `web_kec` / slug.** Pakai integer `id`. Slug bisa berubah / punya karakter aneh.

❌ **Satu license untuk banyak tenant.** 1 license = 1 tenant. Kalau 1 customer punya 3 cabang, 3 license.

❌ **Hitung saldo di controller endpoint.** Pisahin business logic ke utility/service class. Holding audit akan beda angka kalau business logic di-duplikasi.

### C.9 Referensi File di SIDBM

- Middleware: `app/Http/Middleware/HoldingLicense.php`
- Trait multi-tenant: `app/Traits/TenantAware.php`
- Utility saldo: `app/Utils/Keuangan.php`
- Controller laporan: `app/Http/Controllers/Api/HoldingLaporanController.php`
- Tabel migrasi (contoh): cek `database/migrations/*licenses*`
- Seed: cek `database/seeders/*License*`
