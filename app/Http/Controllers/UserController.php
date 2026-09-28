<?php

namespace App\Http\Controllers;

use App\Models\Kecamatan;
use App\Models\Pendidikan;
use App\Models\User;
use App\Utils\Tanggal;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Session;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $kec = Kecamatan::where('id', Session::get('lokasi'))->first();
        $user = User::where('id', auth()->user()->id)->with('l', 'j', 'kec', 'kec.kabupaten')->first();
        $pendidikan = Pendidikan::all();

        $pass = $this->RandomString(strlen($user->pass));

        $title = 'Profil User';

        return view('profil.index')->with(compact('title', 'kec', 'user', 'pendidikan', 'pass'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(User $profil)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $profil)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $profil)
    {
        $type = $request->type;

        if ($type == 'data_diri') {
            $data = $request->only([
                'nik',
                'nama_depan',
                'nama_belakang',
                'inisial',
                'tempat_lahir',
                'tanggal_lahir',
                'alamat',
                'telpon',
                'pendidikan',
                'menjabat_sejak',
            ]);

            $rules = [
                'nik' => 'required',
                'inisial' => 'required',
                'nama_depan' => 'required',
                'tempat_lahir' => 'required',
                'tanggal_lahir' => 'required',
                'telpon' => 'required',
                'pendidikan' => 'required',
                'menjabat_sejak' => 'required',
            ];

            if ($request->nik != $profil->nik) {
                $rules['nik'] = 'required|unique:users';
            }

            if ($request->inisial != $profil->ins) {
                $rules['inisial'] = [
                    'required',
                    Rule::unique('users', 'ins')->where('lokasi', $profil->lokasi),
                ];
            }

            $validate = Validator::make($data, $rules);

            if ($validate->fails()) {
                return response()->json($validate->errors(), Response::HTTP_MOVED_PERMANENTLY);
            }

            $user = User::where('id', $profil->id)->update([
                'nik' => $request->nik,
                'ins' => $request->inisial,
                'namadepan' => $request->nama_depan,
                'namabelakang' => $request->nama_belakang,
                'tempat_lahir' => $request->tempat_lahir,
                'tgl_lahir' => Tanggal::tglNasional($request->tanggal_lahir),
                'hp' => $request->telpon,
                'pendidikan' => $request->pendidikan,
                'sejak' => Tanggal::tglNasional($request->menjabat_sejak),
            ]);

            Session::put('nama', $request->nama_depan.' '.$request->nama_belakang);

            return response()->json([
                'success' => true,
                'msg' => 'Data Diri berhasil diperbarui.',
                'user' => User::where('id', $profil->id)->first(),
            ]);
        } elseif ($type == 'data_user') {
            $data = $request->only([
                'username',
                'password_baru',
                'konfirmasi_password',
            ]);

            $rules = [
                'username' => 'required',
                'password_baru' => 'same:konfirmasi_password',
                'konfirmasi_password' => 'same:password_baru',
            ];

            if ($request->username != $profil->uname) {
                $rules['username'] = 'required|unique:users,uname';
            }

            $validate = Validator::make($data, $rules);

            if ($validate->fails()) {
                return response()->json($validate->errors(), Response::HTTP_MOVED_PERMANENTLY);
            }

            if ($request->password_baru == $profil->pass) {
                return response()->json([
                    'success' => false,
                    'msg' => 'Password baru dan Password lama tidak boleh sama',
                ]);
            }

            $update['uname'] = $request->username;
            if ($request->password_baru != '') {
                $update['pass'] = $request->password_baru;
            }

            $user = User::where('id', $profil->id)->update($update);

            return response()->json([
                'success' => true,
                'msg' => 'Username dan Password berhasil diperbarui. Silahkan login dengan Username dan Password yang baru.',
            ]);
        } else {
            $data = $request->only([
                'logo',
            ]);

            $validate = Validator::make($data, [
                'logo' => 'required|image|mimes:jpg,png,jpeg|max:4096',
            ]);

            if ($request->file('logo')->isValid()) {
                $extension = $request->file('logo')->getClientOriginalExtension();

                $filename = time().'_'.$profil->lokasi.'_'.date('Ymd').'.'.$extension;
                $disk = config('filesystems.cloud_disk', 'enstorage');
                $path = $request->file('logo')->storeAs('profil', $filename, $disk);

                $publicUrl = Storage::disk($disk)->url($path);
                $baseUrl = rtrim(Storage::disk($disk)->url(''), '/');
                $relativePath = $profil->foto ? str_replace($baseUrl.'/', '', $profil->foto) : null;
                if ($relativePath && Storage::disk($disk)->exists($relativePath)) {
                    Storage::disk($disk)->delete($relativePath);
                }

                $user = User::where('id', $profil->id)->update([
                    'foto' => $publicUrl,
                ]);

                Session::put('foto', $publicUrl);

                return response()->json([
                    'success' => true,
                    'path' => $publicUrl,
                ]);
            }

            return response()->json([
                'success' => false,
                'msg' => 'Logo gagal diperbarui',
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $profil)
    {
        //
    }

    public function RandomString($length)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';

        $maxIndex = strlen($characters) - 1;

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $maxIndex)];
        }

        return $randomString;
    }
}
