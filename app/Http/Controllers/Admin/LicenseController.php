<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kecamatan;
use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class LicenseController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $licenses = License::with(['kecamatan', 'kecamatan.kabupaten'])->get();

            return DataTables::of($licenses)
                ->addIndexColumn()
                ->editColumn('kecamatan', function ($row) {
                    $kec = $row->kecamatan;
                    if (!$kec) {
                        return '-';
                    }
                    $kab = $kec->kabupaten ? $kec->kabupaten->nama_kab : '';
                    return $kec->nama_kec . ($kab ? ' &mdash; ' . $kab : '');
                })
                ->editColumn('api_secret', function ($row) {
                    $secret = (string) $row->api_secret;
                    $masked = strlen($secret) > 8
                        ? substr($secret, 0, 4) . str_repeat('*', max(8, strlen($secret) - 8)) . substr($secret, -4)
                        : str_repeat('*', strlen($secret));
                    return '<code title="' . e($secret) . '">' . e($masked) . '</code>';
                })
                ->editColumn('is_active', function ($row) {
                    $badge = $row->is_active ? 'success' : 'secondary';
                    $text = $row->is_active ? 'Aktif' : 'Non-aktif';
                    return '<span class="badge badge-' . $badge . '">' . $text . '</span>';
                })
                ->editColumn('expired_at', function ($row) {
                    if (!$row->expired_at) {
                        return '-';
                    }
                    $expiredClass = $row->isExpired() ? 'text-danger fw-bold' : '';
                    return '<span class="' . $expiredClass . '">' . $row->expired_at->format('d/m/Y H:i') . '</span>';
                })
                ->addColumn('aksi', function ($row) {
                    return '
                        <button class="btn btn-sm btn-warning edit-license" data-id="' . $row->id . '">
                            <i class="material-icons" style="font-size:14px">edit</i>
                        </button>
                        <button class="btn btn-sm btn-danger hapus-license" data-id="' . $row->id . '">
                            <i class="material-icons" style="font-size:14px">delete</i>
                        </button>
                    ';
                })
                ->rawColumns(['kecamatan', 'api_secret', 'is_active', 'expired_at', 'aksi'])
                ->make(true);
        }

        $kecamatan = Kecamatan::with('kabupaten')
            ->orderBy('nama_kec', 'ASC')
            ->get()
            ->map(function ($k) {
                $kab = $k->kabupaten ? $k->kabupaten->nama_kab : '';
                $k->label = $k->nama_kec . ($kab ? ' — ' . $kab : '');
                return $k;
            });

        $title = 'Manajemen License';
        return view('admin.license.index')->with(compact('title', 'kecamatan'));
    }

    public function create()
    {
        $assignedIds = License::pluck('kecamatan_id')->toArray();
        $kecamatan = Kecamatan::with('kabupaten')
            ->whereNotIn('id', $assignedIds)
            ->orderBy('nama_kec', 'ASC')
            ->get()
            ->map(function ($k) {
                $kab = $k->kabupaten ? $k->kabupaten->nama_kab : '';
                $k->label = $k->nama_kec . ($kab ? ' — ' . $kab : '');
                return $k;
            });

        return response()->json([
            'view' => view('admin.license.form', [
                'license' => new License(),
                'kecamatan' => $kecamatan,
                'mode' => 'create',
            ])->render()
        ]);
    }

    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'kecamatan_id' => 'required|integer|unique:licenses,kecamatan_id',
            'api_secret' => 'required|string|max:255',
            'is_active' => 'required|boolean',
            'expired_at' => 'nullable|date',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validate->errors(),
            ], 422);
        }

        $license = License::create([
            'kecamatan_id' => $request->kecamatan_id,
            'api_secret' => $request->api_secret,
            'is_active' => $request->is_active,
            'expired_at' => $request->expired_at,
        ]);

        return response()->json([
            'success' => true,
            'msg' => 'License untuk ' . ($license->kecamatan->nama_kec ?? '-') . ' berhasil ditambahkan.',
        ]);
    }

    public function edit(License $license)
    {
        $license->load('kecamatan.kabupaten');
        // Kecamatan tidak boleh diganti saat edit (1 license = 1 kecamatan).
        $kecamatan = collect([$license->kecamatan])->map(function ($k) {
            $kab = $k->kabupaten ? $k->kabupaten->nama_kab : '';
            $k->label = $k->nama_kec . ($kab ? ' — ' . $kab : '');
            return $k;
        });

        return response()->json([
            'view' => view('admin.license.form', [
                'license' => $license,
                'kecamatan' => $kecamatan,
                'mode' => 'edit',
            ])->render()
        ]);
    }

    public function update(Request $request, License $license)
    {
        $validate = Validator::make($request->all(), [
            'kecamatan_id' => 'required|integer|unique:licenses,kecamatan_id,' . $license->id,
            'api_secret' => 'required|string|max:255',
            'is_active' => 'required|boolean',
            'expired_at' => 'nullable|date',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validate->errors(),
            ], 422);
        }

        $license->update([
            'kecamatan_id' => $request->kecamatan_id,
            'api_secret' => $request->api_secret,
            'is_active' => $request->is_active,
            'expired_at' => $request->expired_at,
        ]);

        return response()->json([
            'success' => true,
            'msg' => 'License berhasil diperbarui.',
        ]);
    }

    public function destroy(License $license)
    {
        $nama = $license->kecamatan->nama_kec ?? '-';
        $license->delete();

        return response()->json([
            'success' => true,
            'msg' => 'License ' . $nama . ' berhasil dihapus.',
        ]);
    }
}
