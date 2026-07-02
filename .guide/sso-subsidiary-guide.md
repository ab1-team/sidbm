# Panduan SSO Auto-Login — Holding → Subsidiary

> Dokumen ini untuk **tim developer subsidiary** yang sudah/belum mengimplementasikan
> 5 endpoint laporan (`/api/v1/holding/laporan/*`) dan ingin menambahkan fitur
> **auto-login satu-klik** dari Holding App.
>
> Fitur ini membuat tenant_owner / tenant_staff di Holding cukup klik tombol
> "Buka Aplikasi" di dashboard, dan otomatis sudah login di subsidiary — tanpa
> diminta ketik email/password lagi.

---

## Overview Arsitektur

```
┌──────────────────┐                ┌─────────────────────┐
│  Holding App     │                │  Subsidiary Anda    │
│  (Laravel)       │                │  (Laravel/etc)      │
│                  │                │                     │
│  User klik       │                │  /auth/sso?token=.. │
│  "Buka Aplikasi" │ ──redirect────▶│  ↓                  │
│                  │                │  1. Verify HMAC sig │
│  - Generate      │                │  2. Check exp       │
│    signed token  │                │  3. Auth::login()   │
│  - TTL 5 menit   │                │  4. Redirect home   │
│  - HMAC-SHA256   │                │                     │
└──────────────────┘                └─────────────────────┘
       │                                       │
       │   shared secret (SSO_SECRET)          │
       └───────────────────────────────────────┘
```

**Holding** = sign token dengan shared secret.
**Subsidiary** = verify signature → consume token → `Auth::login()` user lokal → redirect.

---

## Yang Perlu Anda Siapkan

### 1. Konfigurasi Shared Secret

Anda butuh **1 shared secret** yang **harus identik** dengan yang ada di Holding.

**Di Holding** (`.env`):
```
SSO_SECRET=<random string 32-64 char>
```

**Di Subsidiary Anda** (`.env`):
```
SSO_SECRET=<value yang sama persis>
```

**Cara generate secret yang aman:**
```bash
php -r "echo bin2hex(random_bytes(32));"
# Contoh output: 9f3a8b2c1d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a
```

**Penting:**
- Jangan pakai `APP_KEY` Laravel — harus key terpisah khusus untuk SSO.
- Secret harus **panjang minimum 32 char** (256 bit) dan random.
- **Jaga kerahasiaannya** — siapa pun yang punya secret bisa forge token SSO.
- Kalau secret bocor → regenerate di **kedua sisi** secara bersamaan (Holding + semua subsidiary).

---

### 2. Implementasi Route `/auth/sso`

Tambah route baru yang menerima token dari Holding:

```php
// routes/web.php (di subsidiary Anda)

use App\Http\Controllers\Auth\SsoController;

Route::get('/auth/sso', [SsoController::class, 'consume'])
    ->name('auth.sso')
    ->middleware('guest'); // Hanya user BELUM login yang boleh consume
```

**Catatan:**
- Pakai `GET` (bukan `POST`) karena redirect dari Holding selalu GET.
- Path default: `/auth/sso`. Bisa dioverride per-license kalau perlu — lihat
  section [Override Path](#override-path-per-license-optional) di bawah.

---

### 3. Implementasi `SsoController`

Buat controller untuk consume token:

```php
// app/Http/Controllers/Auth/SsoController.php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\User;
use App\Services\SsoTokenVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SsoController extends Controller
{
    public function __construct(private readonly SsoTokenVerifier $verifier)
    {
    }

    public function consume(Request $request)
    {
        $token = (string) $request->query('token', '');
        if ($token === '') {
            abort(400, 'Token SSO tidak ditemukan.');
        }

        // 1. Verify signature + expiry
        $payload = $this->verifier->decode($token);
        if ($payload === null) {
            // Log untuk audit — siapa coba akses SSO gagal?
            Log::warning('SSO token invalid or expired', [
                'ip' => $request->ip(),
                'ua' => $request->userAgent(),
            ]);
            abort(401, 'Token SSO tidak valid atau sudah kedaluwarsa.');
        }

        // 2. Resolve license yang dipakai
        //    Payload 'lid' = TenantApplication.id di Holding (mapping ke license Anda)
        $license = License::find($payload['lid']);
        if (! $license || ! $license->is_active || $license->isExpired()) {
            abort(403, 'Lisensi tidak aktif atau sudah kedaluwarsa.');
        }

        // 3. Resolve user lokal subsidiary
        //    Opsi A: Map by email — cari user dengan email yang sama.
        //    Opsi B: Buat user on-demand dari payload (lihat catatan di bawah).
        $user = User::where('email', $payload['email'])->first();
        if (! $user) {
            abort(403, 'User tidak ditemukan di subsidiary ini.');
        }

        if (! $user->is_active) {
            abort(403, 'Akun Anda telah dinonaktifkan.');
        }

        // 4. Login + redirect ke dashboard
        Auth::login($user, remember: false); // SSO = single-use, no remember
        $request->session()->regenerate();

        // 5. Audit log
        Log::info('SSO auto-login success', [
            'user_id' => $user->id,
            'license_id' => $license->id,
            'payload_uid' => $payload['uid'],
        ]);

        return redirect()->intended(route('dashboard'));
    }
}
```

**Catatan penting:**
- **Tidak pakai `Auth::loginUsingId($payload['uid'])`** — itu pakai user_id dari
  Holding. Anda harus **resolve user lokal sendiri** (biasanya by email).
- `remember: false` — SSO token sudah single-use, tidak perlu long-lived cookie.
- Selalu `session->regenerate()` untuk mitigate session fixation.

---

### 4. Implementasi `SsoTokenVerifier` (Service)

Service untuk decode & verify signature. **Logika harus sama persis** dengan
`SsoSignedUrl` di Holding:

```php
// app/Services/SsoTokenVerifier.php
namespace App\Services;

class SsoTokenVerifier
{
    /**
     * Decode token, verify signature & expiry. Return payload kalau valid, null kalau tidak.
     *
     * @return array<string,mixed>|null
     */
    public function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }
        [$payloadB64, $sigB64] = $parts;

        // 1. Verify signature (constant-time)
        $expected = $this->sign($payloadB64);
        $provided = $this->b64urlDecode($sigB64);
        if ($provided === null || ! hash_equals($expected, $provided)) {
            return null;
        }

        // 2. Decode payload
        $payloadJson = $this->b64urlDecode($payloadB64);
        if ($payloadJson === null) {
            return null;
        }

        try {
            $payload = json_decode($payloadJson, true, 8, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (! is_array($payload)) {
            return null;
        }

        // 3. Expiry check
        if (! isset($payload['exp']) || time() > (int) $payload['exp']) {
            return null;
        }

        // 4. Required fields
        foreach (['uid', 'tid', 'lid', 'exp', 'email', 'role'] as $field) {
            if (! array_key_exists($field, $payload)) {
                return null;
            }
        }

        return $payload;
    }

    private function sign(string $payloadB64): string
    {
        $secret = (string) env('SSO_SECRET');

        return hash_hmac('sha256', $payloadB64, $secret, true);
    }

    private function b64urlDecode(string $b64): ?string
    {
        $padded = strtr($b64, '-_', '+/');
        $remainder = strlen($padded) % 4;
        if ($remainder !== 0) {
            $padded .= str_repeat('=', 4 - $remainder);
        }
        $decoded = base64_decode($padded, true);
        return $decoded === false ? null : $decoded;
    }
}
```

---

### 5. Struktur Payload Token

Token adalah string: `{base64url-payload}.{base64url-signature}`.

Payload (setelah decode JSON):

| Field   | Tipe     | Keterangan                                                  |
|---------|----------|-------------------------------------------------------------|
| `uid`   | int      | `User.id` di Holding (untuk audit only — jangan pakai utk login) |
| `tid`   | int      | `Tenant.id` di Holding (untuk audit only)                   |
| `lid`   | int      | `TenantApplication.id` di Holding — mapping ke `License` Anda |
| `slug`  | string   | `Tenant.slug` di Holding                                    |
| `email` | string   | Email user — gunakan untuk resolve user lokal Anda           |
| `role`  | string   | `tenant_owner` atau `tenant_staff` (untuk audit only)       |
| `iat`   | int      | Issued-at (Unix timestamp)                                  |
| `exp`   | int      | Expiry (Unix timestamp) — TTL default 5 menit              |
| `nonce` | string   | Random per-token untuk replay protection                    |

**JANGAN percaya** nilai `uid`, `role`, atau `tid` dari payload untuk authorization
— signature hanya menjamin payload tidak diubah di perjalanan. Anda harus
**resolve user dari database lokal** berdasarkan `email` (atau mapping table
lain yang Anda punya).

---

### 6. Override Path per-License (Opsional)

Default Holding redirect ke `{instance_url}/auth/sso`. Kalau Anda butuh path
berbeda per license (mis. tenant A pakai `/sso/v2`, tenant B pakai `/login/callback`):

1. Edit field `notes` di record `TenantApplication` di Holding, isi:
   ```
   sso_path=/sso/v2
   ```
2. Holding akan pakai path itu untuk license tsb.

Atau set env global di Holding:
```
SSO_PATH=/auth/sso
```

---

## Referensi Test

### Test Manual dari Browser

1. Login ke Holding sebagai `tenant_owner` (di subdomain tenant, mis. `acme.holding.test`).
2. Buka menu "Aplikasi Saya" → klik tombol "Buka Aplikasi" di salah satu license.
3. Browser harusnya redirect ke subsidiary, **dan Anda sudah login otomatis**
   (cek dengan lihat URL tujuan — kalau ke `/dashboard` alih-alih `/login`,
   SSO sukses).

### Test Manual via cURL

Simulasikan redirect Holding:

```bash
# Ganti <token> dengan hasil encode dari holding (lihat snippet di bawah)
curl -v "https://tenant.subsidiaryanda.com/auth/sso?token=<token>"
# Expected: 302 redirect ke /dashboard, dengan Set-Cookie session
```

Generate token untuk testing (snippet PHP cepat):

```php
// Jalankan di tinker Holding
$svc = new \App\Services\SsoSignedUrl();
$user = \App\Models\User::find(1);
$license = \App\Models\TenantApplication::find(1);
echo $svc->build($user, $license);
```

### Negative Tests (Pastikan reject dengan benar)

| Skenario                            | Expected behavior                |
|-------------------------------------|----------------------------------|
| Token kosong                        | 400 Bad Request                  |
| Signature dipalsukan                | 401 Unauthorized                 |
| Token expired (>5 menit)            | 401 Unauthorized                 |
| `lid` tidak ditemukan di DB lokal   | 403 Forbidden                    |
| License nonaktif di subsidiary      | 403 Forbidden                    |
| User `is_active=false`              | 403 Forbidden                    |
| User sudah login (session ada)      | Tetap login sebagai user existing (tapi tidak double-login) |

---

## Keamanan (Wajib Dipahami)

| Ancaman                          | Mitigasi di subsidiary                                                          |
|----------------------------------|----------------------------------------------------------------------------------|
| **Token replay** (dipakai 2x)    | Token TTL 5 menit — window sempit. Tambahan: track nonce di cache jika perlu.   |
| **Secret bocor**                 | Regenerate `SSO_SECRET` di Holding + semua subsidiary secara bersamaan.          |
| **Forged token**                 | HMAC-SHA256 verify pakai `hash_equals()` (constant-time, anti timing attack).   |
| **Session fixation**             | `session()->regenerate()` setelah `Auth::login()`.                              |
| **Open redirect**                | Hanya redirect ke `route('dashboard')` (atau named route internal) — bukan URL dari payload. |
| **User enumeration**             | Generic error message: "Token SSO tidak valid atau sudah kedaluwarsa."          |
| **CSRF**                         | Endpoint `GET` tapi tidak mengubah state sebelum verify — aman. Tidak butuh CSRF token. |
| **HTTPS required**               | Wajib HTTPS untuk produksi — token bocor lewat header kalau plaintext HTTP.    |

---

## Checklist Onboarding

- [ ] Generate `SSO_SECRET` random 32+ char
- [ ] Set `SSO_SECRET` di `.env` Holding
- [ ] Set `SSO_SECRET` di `.env` subsidiary Anda (nilai sama)
- [ ] Implement `SsoTokenVerifier` service (copy logika di atas)
- [ ] Implement `SsoController` dengan resolve user by email
- [ ] Tambah route `GET /auth/sso`
- [ ] Test manual: klik tombol "Buka Aplikasi" di Holding
- [ ] Test negative: token expired, signature rusak, license nonaktif
- [ ] Setup HTTPS untuk produksi (Let's Encrypt atau setara)
- [ ] Audit log: catat SSO attempt (sukses & gagal) untuk forensik
- [ ] Backup `.env` (termasuk `SSO_SECRET`) di vault terenkripsi

---

## Troubleshooting

### Browser stuck di `/auth/sso` dengan error 401

**Cek:**
- Apakah `SSO_SECRET` di subsidiary **persis sama** dengan di Holding? (case-sensitive, no trailing newline)
- Apakah waktu server subsidiary **sync** dengan Holding? Token expired kalau clock skew > 5 menit.
- Cek log Laravel: `tail -f storage/logs/laravel.log` — akan ada warning "SSO token invalid or expired".

### User bisa login SSO tapi selalu diarahkan ke login page subsidiary

**Cek:**
- Apakah route `dashboard` (atau named route yang Anda redirect) ada?
- Apakah middleware `auth` di dashboard route benar?
- Apakah `Auth::login()` dipanggil sebelum redirect?

### "User tidak ditemukan di subsidiary ini"

Artinya: email user Holding tidak ada di tabel `users` subsidiary.

**Solusi:**
- **Opsi A (Recommended):** Sinkronisasi user secara berkala — vendor bisa tambahkan
  endpoint `GET /api/v1/holding/users` di subsidiary kalau Anda butuh list user.
- **Opsi B:** Buat user on-demand dari payload (lihat catatan di bawah).
- **Opsi C:** Mapping table `sso_user_mappings (holding_email, local_user_id)`.

#### Catatan: Buat User On-Demand (Opsi B)

Kalau subsidiary Anda mau auto-provision user dari payload SSO:

```php
$user = User::firstOrCreate(
    ['email' => $payload['email']],
    [
        'name' => $payload['name'] ?? $payload['email'],
        'password' => bcrypt(Str::random(64)), // Random — user login via SSO only
        'role' => $this->mapRole($payload['role']), // tenant_owner → local_role
        'is_active' => true,
        'tenant_id' => $license->tenant_id, // Bind ke tenant dari license
    ]
);
```

**Penting:**
- User yang di-create via SSO **tidak punya password yang bisa dipakai manual**.
- Selalu bind ke `tenant_id` dari license — jangan percaya `tid` di payload.
- Map role Holding (`tenant_owner`, `tenant_staff`) ke role internal Anda.

---

## Referensi Tambahan

- **Kontrak API 5 endpoint laporan**: [./subsidiary-api-contract.md](./subsidiary-api-contract.md)
- **Panduan integrasi lengkap**: [./subsidiary-integration-guide.md](./subsidiary-integration-guide.md)
- **Source code Holding**: `app/Services/SsoSignedUrl.php` (sign) &
  `app/Http/Controllers/TenantAccessController.php` (generate & redirect)