<?php

namespace App\Http\Controllers;

use App\Models\DokumenPinjaman;
use App\Models\Kecamatan;
use App\Models\TandaTanganDokumen;
use App\Utils\Pinjaman;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Session;

class TandaTanganController extends Controller
{
    public function index()
    {
        $data['dokumenPinjaman'] = DokumenPinjaman::where('custom_ttd', '1')->get();
        $data['tandaTangan'] = TandaTanganDokumen::where('lokasi', Session::get('lokasi'))->pluck('tanda_tangan', 'dokumen_pinjaman_id')->toArray();
        $data['keyword'] = Pinjaman::keyword();
        $data['kec'] = Kecamatan::where('id', Session::get('lokasi'))->first();

        $data['title'] = "Pengaturan Tanda Tangan";
        return view('tanda_tangan.index')->with($data);
    }

    public function store(Request $request)
    {
        $data = $request->all();

        $data['tanda_tangan'] = preg_replace('/<table[^>]*>/', '<table class="p0" border="0" width="100%" cellspacing="0" cellpadding="0">', $data['tanda_tangan'], 1);
        $data['tanda_tangan'] = preg_replace('/height:\s*[^;]+;?/', '', $data['tanda_tangan']);

        $data['tanda_tangan'] = str_replace('colgroup', 'tr', $data['tanda_tangan']);
        $data['tanda_tangan'] = preg_replace('/<col([^>]*)>/', '<td$1>&nbsp;</td>', $data['tanda_tangan']);

        $tandaTanganDokumen = TandaTanganDokumen::updateOrCreate([
            'lokasi' => Session::get('lokasi'),
            'dokumen_pinjaman_id' => $data['dokumen'],
            'jenis_laporan' => $data['jenis_laporan'],
        ], [
            'lokasi' => Session::get('lokasi'),
            'dokumen_pinjaman_id' => $data['dokumen'],
            'jenis_laporan' => $data['jenis_laporan'],
            'tanda_tangan' => json_encode($data['tanda_tangan'])
        ]);

        return response()->json([
            'success' => true,
            'msg' => 'Tanda tangan berhasil disimpan.',
            'data' => $tandaTanganDokumen,
            'tanda_tangan' => json_encode($data['tanda_tangan'])
        ]);
    }

    public function storeTtdTagihan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ttd_tagihan' => 'required|image|mimes:jpg,png,jpeg|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'msg' => $validator->errors()->first(),
            ], 422);
        }

        $lokasi = Session::get('lokasi');
        $kecamatan = Kecamatan::where('id', $lokasi)->first();

        if (! $kecamatan) {
            abort(404);
        }

        if ($request->hasFile('ttd_tagihan') && $request->file('ttd_tagihan')->isValid()) {
            $extension = $request->file('ttd_tagihan')->getClientOriginalExtension();
            $filename = $lokasi.'.'.$extension;

            foreach (['jpg', 'jpeg', 'png'] as $oldExtension) {
                $oldPath = 'ttd_tagihan/'.$lokasi.'.'.$oldExtension;
                if ($oldExtension !== $extension && Storage::disk('supabase')->exists($oldPath)) {
                    Storage::disk('supabase')->delete($oldPath);
                }
            }

            $path = $request->file('ttd_tagihan')->storeAs('ttd_tagihan', $filename, 'supabase');
            $publicUrl = env('SUPABASE_PUBLIC_URL').'/'.$path;

            Kecamatan::where('id', $kecamatan->id)
                ->toBase()
                ->update(['ttd_tagihan' => $publicUrl]);

            return response()->json([
                'success' => true,
                'msg' => 'Tanda tangan & stempel surat tagihan berhasil disimpan.',
                'path' => $publicUrl,
            ]);
        }

        return response()->json([
            'success' => false,
            'msg' => 'Tanda tangan & stempel surat tagihan gagal disimpan.',
        ]);
    }
}
