<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Controller;
use App\Models\Kecamatan;
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

    /**
     * Consume SSO token dari Holding App.
     *
     * Flow:
     * 1. Verify HMAC signature + expiry (SsoTokenVerifier)
     * 2. Resolve license lokal by payload['lid']
     * 3. Resolve user Direktur tenant (level=1, jabatan=1) by kecamatan_id
     * 4. Auth::loginUsingId + session regenerate + setup session vars SIDBM
     * 5. Redirect ke /dashboard
     */
    public function consume(Request $request, AuthController $auth)
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

        // 2. Resolve license lokal SIDBM
        $license = License::find($payload['lid']);
        if (! $license || ! $license->is_active || $license->isExpired()) {
            abort(403, 'Lisensi tidak aktif atau sudah kedaluwarsa.');
        }

        // 3. Resolve user Direktur (level=1, jabatan=1) untuk kecamatan tenant
        $kecamatanId = $license->kecamatan_id;
        $user = User::where('lokasi', $kecamatanId)
            ->where('level', 1)
            ->where('jabatan', 1)
            ->first();

        if (! $user) {
            Log::warning('SSO user direktur not found', [
                'kecamatan_id' => $kecamatanId,
                'payload_uid' => $payload['uid'],
            ]);
            abort(403, 'User Direktur tidak ditemukan di subsidiary ini.');
        }

        if ($user->status !== '1') {
            abort(403, 'Akun Direktur telah dinonaktifkan.');
        }

        $kec = Kecamatan::find($kecamatanId);
        if (! $kec) {
            abort(404, 'Lembaga tidak ditemukan.');
        }

        // 4. Login
        Auth::loginUsingId($user->id, remember: false);
        $request->session()->regenerate();

        // 5. Build session vars pakai helper yang sama dengan login normal
        $url = $request->getHost();
        $sessionData = $auth->buildSessionData($user, $url, $kec);

        $tokenKec = $kec->token;
        if (strlen($kec->token) < 45) {
            $tokenKec = 'dbm-'.str_replace('.', '', $kec->kd_kec).'-'.str_pad($kec->id, 3, '0', STR_PAD_LEFT);
        }

        $icon = $kec->logo ? '/storage/logo/'.$kec->logo : '/assets/img/icon/favicon.png';

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

        // 6. Audit log
        Log::info('SSO auto-login success', [
            'user_id' => $user->id,
            'kecamatan_id' => $kecamatanId,
            'license_id' => $license->id,
            'payload_uid' => $payload['uid'],
            'payload_email' => $payload['email'],
        ]);

        return redirect('/dashboard')->with('pesan', 'Selamat Datang '.$user->namadepan.' '.$user->namabelakang);
    }
}