<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Controller;
use App\Models\Kecamatan;
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

    /**
     * Consume SSO token dari Holding App.
     *
     * Flow (sesuai .guide/sso-subsidiary-guide.md):
     * 1. Verify HMAC signature + expiry via SsoTokenVerifier — INI SATU-SATUNYA
     *    yang wajib (pakai shared SSO_SECRET).
     * 2. Resolve user lokal — cara TERSERAH. SIDBM pakai: domain request →
     *    Kecamatan → User Direktur (level=1, jabatan=1).
     * 3. Login + session regenerate
     * 4. Redirect ke intended() atau '/dashboard'
     */
    public function consume(Request $request)
    {
        $token = (string) $request->query('token', '');
        if ($token === '') {
            abort(400, 'Token SSO tidak ditemukan.');
        }

        // 1. Verify signature + expiry
        $payload = $this->verifier->decode($token);
        if ($payload === null) {
            Log::warning('SSO token invalid or expired', [
                'ip' => $request->ip(),
                'ua' => $request->userAgent(),
            ]);
            abort(401, 'Token SSO tidak valid atau sudah kedaluwarsa.');
        }

        // 2. Resolve user lokal SIDBM
        $user = $this->resolveLocalUser($request, $payload);
        if (! $user || $user->status !== '1') {
            abort(403, 'User tidak ditemukan atau akun dinonaktifkan.');
        }

        // 3. Setup session SIDBM (menu, akses, dll) — sama dengan login normal.
        $kec = Kecamatan::find($user->lokasi);
        if (! $kec) {
            abort(404, 'Lembaga tidak ditemukan.');
        }

        $url = $request->getHost();
        $auth = app(AuthController::class);
        $sessionData = $auth->buildSessionData($user, $url, $kec);

        $tokenKec = $kec->token;
        if (strlen($kec->token) < 45) {
            $tokenKec = 'dbm-'.str_replace('.', '', $kec->kd_kec).'-'.str_pad($kec->id, 3, '0', STR_PAD_LEFT);
        }

        $icon = $kec->logo ? '/storage/logo/'.$kec->logo : '/assets/img/icon/favicon.png';

        // 4. Login
        Auth::login($user, remember: false);
        $request->session()->regenerate();

        session([
            'nama_lembaga' => str_replace('DBM ', '', $kec->nama_lembaga_sort),
            'nama' => $user->namadepan.' '.$user->namabelakang,
            'foto' => $user->foto,
            'logo' => $kec->logo,
            'lokasi' => $user->lokasi,
            'lokasi_user' => $user->lokasi,
            'menu' => $sessionData['menu'],
            'tombol' => $sessionData['MenuTombol'],
            'akses_menu' => $sessionData['Menu'],
            'icon' => $icon,
            'config' => $sessionData['config'],
            'token' => $tokenKec,
        ]);

        // 5. Audit log
        Log::info('SSO auto-login success', [
            'user_id' => $user->id,
            'kecamatan_id' => $user->lokasi,
            'payload_uid' => $payload['uid'],
            'payload_lid' => $payload['lid'],
        ]);

        return redirect()->intended(route('dashboard', absolute: false) ?: '/dashboard');
    }

    /**
     * Resolve user lokal SIDBM.
     *
     * Setelah SSO_SECRET verified → cara lookup TERSERAH. SIDBM pakai domain
     * request: cocokkan host dengan `web_kec` atau `web_alternatif` di tabel
     * kecamatan → ambil kecamatan_id → ambil User Direktur (level=1, jabatan=1).
     *
     * @param  array<string,mixed>  $payload
     */
    private function resolveLocalUser(Request $request, array $payload): ?User
    {
        $host = $request->getHost();

        $kec = Kecamatan::where('web_kec', $host)
            ->orWhere('web_alternatif', $host)
            ->first();

        if (! $kec) {
            Log::warning('SSO kecamatan not found for host', [
                'host' => $host,
                'payload_lid' => $payload['lid'],
            ]);

            return null;
        }

        return User::where('lokasi', $kec->id)
            ->where('level', 1)
            ->where('jabatan', 1)
            ->first();
    }
}
