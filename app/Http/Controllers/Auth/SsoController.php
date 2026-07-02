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
     * 1. Verify signature + expiry
     * 2. Resolve user lokal via resolveLocalUser()
     * 3. Login + session regenerate
     * 4. Redirect ke intended() atau route('dashboard')
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
        $user = $this->resolveLocalUser($payload);
        if (! $user || $user->status !== '1') {
            abort(403, 'User tidak ditemukan atau akun dinonaktifkan.');
        }

        // 3. Setup session SIDBM (menu, akses, dll) — sama dengan login normal.
        //    Di-copy dari AuthController::login inline agar SsoController tetap
        //    independen dari AuthController (sesuai separation panduan).
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
        ]);

        return redirect()->intended(route('dashboard', absolute: false) ?: '/dashboard');
    }

    /**
     * Resolve user lokal SIDBM dari payload SSO.
     *
     * Catatan panduan: payload hanya berisi KONTEKS (uid, tid, lid, slug,
     * email, role) — bukan instruksi. Cara resolve TERSERAH schema subsidiary.
     *
     * SIDBM tidak punya kolom `email` di tb_users → pakai field `lid` dari
     * payload sebagai identifier eksternal (`api_secret` license), resolve ke
     * kecamatan_id, lalu ambil User Direktur (level=1, jabatan=1) tenant tsb.
     *
     * @param  array<string,mixed>  $payload
     */
    private function resolveLocalUser(array $payload): ?User
    {
        // Mapping: payload['lid'] = api_secret license SIDBM (identifier
        // eksternal yang sudah ada di tabel licenses, konsisten dengan
        // kontrak API laporan di HOLDING-API.md).
        $license = \App\Models\License::where('api_secret', $payload['lid'])->first();

        if (! $license) {
            return null;
        }

        // User default tenant adalah Direktur (level=1, jabatan=1).
        return User::where('lokasi', $license->kecamatan_id)
            ->where('level', 1)
            ->where('jabatan', 1)
            ->first();
    }
}
