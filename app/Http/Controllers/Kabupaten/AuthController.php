<?php

namespace App\Http\Controllers\Kabupaten;

use App\Http\Controllers\Controller;
use App\Models\Kabupaten;
use App\Models\Wilayah;
use App\Support\TenantResolver;
use App\Utils\Keuangan;
use Auth;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function index()
    {
        $kab = TenantResolver::kabupatenForDomain(request()->getHost());

        if (! $kab) {
            abort(404, 'Lembaga atau domain tidak terdaftar');
        }

        config(['database.default' => $kab->getConnectionName()]);

        if (Keuangan::startWith($kab->nama_kab, 'KOTA') || Keuangan::startWith($kab->nama_kab, 'KAB')) {
            $nama_kab = ucwords(strtolower($kab->nama_kab));
        } else {
            $nama_kab = ' Kabupaten ' . ucwords(strtolower($kab->nama_kab));
        }

        return view('kabupaten.auth.login')->with(compact('nama_kab'));
    }

    public function login(Request $request)
    {
        $data = $request->only([
            'username', 'password'
        ]);

        $validate = $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        $kab = TenantResolver::kabupatenForDomain($request->getHost());

        if (! $kab) {
            abort(404, 'Lembaga atau domain tidak terdaftar');
        }

        config(['database.default' => $kab->getConnectionName()]);

        $login_kab = Kabupaten::on($kab->getConnectionName())
            ->where('uname', $data['username'])
            ->first();
        if ($login_kab) {
            if ($login_kab->pass == $kab->pass && $login_kab->pass === $data['password']) {
                if (Auth::guard('kab')->loginUsingId($login_kab->id)) {
                    $request->session()->regenerate();

                    $kecamatan = Wilayah::where('kode', 'LIKE', $login_kab->kd_kab . '%')->whereRaw('LENGTH(kode) = 8')->orderBy('nama', 'ASC')->get();
                    session([
                        'nama_kab' => ucwords(strtolower($login_kab->nama_kab)),
                        'kecamatan' => $kecamatan,
                        'kd_kab' => $login_kab->kd_kab,
                        'kd_prov' => $login_kab->kd_prov,
                    ]);

                    return redirect('/kab/dashboard')->with([
                        'pesan' => 'Login Kabupaten ' . ucwords(strtolower($login_kab->nama_kab)) . ' Berhasil'
                    ]);
                }
            }
        }

        $error = 'Username atau Password Salah';
        return redirect()->back()->with('error', $error);
    }

    public function logout(Request $request)
    {
        $user = auth()->guard('kab')->user()->nama_kab;
        Auth::guard('kab')->logout();

        return redirect('/kab')->with('pesan', 'Terima Kasih');
    }
}
