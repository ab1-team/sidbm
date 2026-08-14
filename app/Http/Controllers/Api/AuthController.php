<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuTombol;
use App\Models\Mobile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function auth(Request $request)
    {
        $data = $request->only([
            'username',
            'password',
        ]);

        $validate = Validator::make($data, [
            'username' => 'required',
            'password' => 'required',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Ada form yang belum diisi',
                'form_error' => $validate->errors(),
            ], 400);
        }

        $token = request()->header('token');
        $code = request()->header('code');

        $getToken = Mobile::where('unique_id', $code)->with('kec')->first();
        if ($getToken) {
            if (Hash::check($token, $getToken->aktivasi)) {
                $kecamatan = $getToken->kec;
                $lokasi = $kecamatan->id;

                $invoice = \App\Models\AdminInvoice::where([
                    ['lokasi', $lokasi],
                    ['status', 'UNPAID'],
                ])->orderBy('tgl_invoice', 'ASC')->first();

                if (! $invoice) {
                    $invoice = \App\Models\AdminInvoice::on('mysql_b')->where([
                        ['lokasi', $lokasi],
                        ['status', 'UNPAID'],
                    ])->orderBy('tgl_invoice', 'ASC')->first();
                }

                if ($invoice && date('Y-m-d') >= $invoice->tgl_invoice) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Akses diblokir: Invoice belum terbayar.',
                    ], 403);
                }

                $user = User::where([['uname', $data['username']], ['lokasi', $lokasi]])->first();
                if ($user) {
                    if ($data['password'] === $user->pass) {
                        if (Auth::loginUsingId($user->id)) {
                            $token = $user->createToken($token)->plainTextToken;

                            $AksesMenu = explode(',', $user->akses_menu);
                            $Menu = Menu::whereNotIn('id', $AksesMenu)->pluck('akses')->toArray();

                            $AksesTombol = explode(',', $user->akses_tombol);
                            $MenuTombol = MenuTombol::whereNotIn('id', $AksesTombol)->pluck('akses')->toArray();

                            unset($user['pass']);

                            return response()->json([
                                'success' => true,
                                'message' => 'Selamat Datang '.$user->namadepan.' '.$user->namabelakang,
                                'data' => [
                                    'user' => [
                                        'id' => $user->id,
                                        'nama' => $user->namadepan.' '.$user->namabelakang,
                                        'jabatan' => $user->j->nama_jabatan,
                                        'level_id' => $user->level,
                                        'jabatan_id' => $user->jabatan,
                                        'menu' => $Menu,
                                        'menu_tombol' => $MenuTombol,
                                        'profil' => $user->foto,
                                    ],
                                    'token' => $token,
                                    'token_type' => 'Bearer',
                                ],
                            ], 200);
                        }
                    }
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Username atau password salah.',
                ], 422);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Token tidak valid.',
        ], 422);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil logout.',
        ], 200);
    }
}
