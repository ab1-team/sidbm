<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AkunLevel1;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PengaturanController extends Controller
{
    public function index()
    {
        $user = request()->user();

        return response()->json([
            'status' => true,
            'data' => $user,
        ], 200);
    }

    public function chartOfAccount()
    {
        $akun1 = AkunLevel1::with([
            'akun2',
            'akun2.akun3',
            'akun2.akun3.rek',
        ])->get();

        $coa = [];
        foreach ($akun1 as $ak1) {
            $akun_level_1 = [
                'id' => $ak1->kode_akun,
                'text' => $ak1->kode_akun.'. '.$ak1->nama_akun,
                'children' => [],
            ];

            foreach ($ak1->akun2 as $ak2) {
                $akun2 = [
                    'id' => $ak2->kode_akun,
                    'text' => $ak2->kode_akun.'. '.$ak2->nama_akun,
                    'children' => [],
                ];

                foreach ($ak2->akun3 as $ak3) {
                    $akun3 = [
                        'id' => $ak3->kode_akun,
                        'text' => $ak3->kode_akun.'. '.$ak3->nama_akun,
                        'children' => [],
                    ];

                    foreach ($ak3->rek as $rek) {
                        $akun4 = [
                            'id' => $rek->kode_akun,
                            'text' => $rek->kode_akun.'. '.$rek->nama_akun,
                        ];

                        array_push($akun3['children'], $akun4);
                    }
                    array_push($akun2['children'], $akun3);
                }
                array_push($akun_level_1['children'], $akun2);
            }
            array_push($coa, $akun_level_1);
        }

        return response()->json([
            'status' => true,
            'data' => $coa,
        ], 200);
    }

    public function updateUser(Request $request)
    {
        $data = $request->only([
            'nik',
            'nama_lengkap',
            'inisial',
            'jenis_kelamin',
            'nomor_hp',
            'username',
        ]);

        $validate = Validator::make($data, [
            'nik' => 'required',
            'nama_lengkap' => 'required',
            'inisial' => 'required',
            'jenis_kelamin' => 'required',
            'nomor_hp' => 'required',
            'username' => 'required',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors(),
            ], 400);
        }

        $nama = explode(' ', $data['nama_lengkap']);
        $nama_depan = $nama[0];
        $nama_belakang = '';
        if (isset($nama[1])) {
            unset($nama[0]);
            $nama_belakang = implode(' ', $nama);
        }

        try {
            $update = User::where('id', request()->user()->id)->update([
                'nik' => $data['nik'],
                'namadepan' => $nama_depan,
                'namabelakang' => $nama_belakang,
                'ins' => $data['inisial'],
                'jk' => $data['jenis_kelamin'],
                'hp' => $data['nomor_hp'],
                'uname' => $data['username'],
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diubah',
                'data' => User::where('id', request()->user()->id)->first(),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Data gagal diubah',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function updateFotoUser(Request $request)
    {
        $user = request()->user();
        $data = $request->only([
            'foto',
        ]);

        $validate = Validator::make($data, [
            'foto' => 'required|image|mimes:jpg,png,jpeg|max:8192',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors(),
            ], 400);
        }

        $extension = $request->file('foto')->getClientOriginalExtension();

        $filename = time().'_'.$user->lokasi.'_'.date('Ymd').'.'.$extension;
        $disk = config('filesystems.cloud_disk', 'enstorage');
        $path = $request->file('foto')->storeAs('profil', $filename, $disk);

        $baseUrl = env('ENSTORAGE_PUBLIC_URL', env('SUPABASE_PUBLIC_URL'));
        $relativePath = str_replace($baseUrl.'/', '', $user->foto);
        if (Storage::disk($disk)->exists($relativePath)) {
            Storage::disk($disk)->delete($relativePath);
        }

        $publicUrl = $baseUrl.'/'.$path;
        $update = User::where('id', $user->id)->update([
            'foto' => $publicUrl,
        ]);

        if ($update) {
            return response()->json([
                'status' => true,
                'message' => 'Foto profil berhasil diubah.',
                'data' => [
                    'foto' => $publicUrl,
                ],
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'Foto profil gagal diubah.',
        ], 400);
    }

    public function updateTempatLahir(Request $request)
    {
        $data = $request->only([
            'tempat_lahir',
            'tanggal_lahir',
        ]);

        $validate = Validator::make($data, [
            'tempat_lahir' => 'required',
            'tanggal_lahir' => 'required',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors(),
            ], 400);
        }

        $update = User::where('id', request()->user()->id)->update([
            'tempat_lahir' => $data['tempat_lahir'],
            'tgl_lahir' => $data['tanggal_lahir'],
        ]);

        if ($update) {
            return response()->json([
                'status' => true,
                'message' => 'Tempat lahir berhasil diubah',
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'Tempat, tanggal lahir gagal diubah',
        ], 400);
    }

    public function updateAlamat(Request $request)
    {
        $data = $request->only([
            'alamat',
        ]);

        $validate = Validator::make($data, [
            'alamat' => 'required',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors(),
            ], 400);
        }

        $update = User::where('id', request()->user()->id)->update([
            'alamat' => $data['alamat'],
        ]);

        if ($update) {
            return response()->json([
                'status' => true,
                'message' => 'Alamat berhasil diubah',
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'Alamat gagal diubah',
        ], 400);
    }

    public function updatePassword(Request $request)
    {
        $data = $request->only([
            'password_lama',
            'password_baru',
            'konfirmasi_password',
        ]);

        $validate = Validator::make($data, [
            'password_lama' => 'required',
            'password_baru' => 'required',
            'konfirmasi_password' => 'required',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Ada form yang belum diisi',
                'form_error' => $validate->errors(),
            ], 422);
        }

        if ($data['password_lama'] == request()->user()->pass) {
            if ($data['password_baru'] === $data['konfirmasi_password']) {
                if ($data['password_baru'] == request()->user()->pass) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Password baru tidak boleh sama dengan password lama',
                    ], 400);
                }

                $update = User::where('id', request()->user()->id)->update([
                    'pass' => $data['password_baru'],
                ]);

                if ($update) {
                    return response()->json([
                        'status' => true,
                        'message' => 'Password berhasil diubah',
                    ], 200);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Password konfirmasi tidak cocok',
                ], 400);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Password lama tidak cocok',
            ], 400);
        }
    }
}
