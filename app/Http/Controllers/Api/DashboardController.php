<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiEndpoint;
use App\Models\DataPemanfaat;
use App\Models\Kecamatan;
use App\Models\PinjamanAnggota;
use App\Models\PinjamanKelompok;
use App\Models\Rekening;
use App\Utils\Tanggal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $charts = $this->_saldo(date('Y-m-d'));

        return response()->json([
            'success' => true,
            'data' => $charts,
        ], 200);
    }

    public function proposal()
    {
        $user = request()->user();

        $page = request()->get('page') ?? 1;
        $perPage = request()->get('per_page') ?? 10;
        $sortBy = request()->get('sort_by') ?? 'id';
        $sortOrder = request()->get('sort_order') ?? 'asc';
        $search = request()->get('search') ?? '';

        $tb_pinkel = 'pinjaman_kelompok_'.$user->lokasi;
        $tb_pinj = 'pinjaman_anggota_'.$user->lokasi;
        $tb_kel = 'kelompok_'.$user->lokasi;

        $query = PinjamanKelompok::select(
            'pk.*',
            'k.nama_kelompok',
            'k.alamat_kelompok',
            'k.desa',
            'desa.nama_desa',
            'jenis_produk_pinjaman.nama_jpp',
            DB::raw('COUNT(pa.id) as jumlah_anggota')
        )->from($tb_pinkel.' as pk')
            ->join('jenis_produk_pinjaman', 'pk.jenis_pp', '=', 'jenis_produk_pinjaman.id')
            ->join($tb_kel.' as k', 'k.id', '=', 'pk.id_kel')
            ->join('desa', 'k.desa', '=', 'desa.kd_desa')
            ->leftJoin($tb_pinj.' as pa', 'pa.id_pinkel', '=', 'pk.id')
            ->where('pk.status', 'P')
            ->where(function ($query) use ($search) {
                $query->where('k.nama_kelompok', 'LIKE', '%'.$search.'%')
                    ->orWhere('jenis_produk_pinjaman.nama_jpp', 'LIKE', '%'.$search.'%')
                    ->orWhere('pk.id', 'LIKE', '%'.$search.'%');
            })
            ->groupBy('pk.id', 'k.nama_kelompok', 'k.alamat_kelompok', 'k.desa', 'desa.nama_desa', 'jenis_produk_pinjaman.nama_jpp');

        $data = $query->orderBy($sortBy, $sortOrder)->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    public function verifikasi()
    {
        $user = request()->user();

        $page = request()->get('page') ?? 1;
        $perPage = request()->get('per_page') ?? 10;
        $sortBy = request()->get('sort_by') ?? 'id';
        $sortOrder = request()->get('sort_order') ?? 'asc';
        $search = request()->get('search') ?? '';

        $tb_pinkel = 'pinjaman_kelompok_'.$user->lokasi;
        $tb_pinj = 'pinjaman_anggota_'.$user->lokasi;
        $tb_kel = 'kelompok_'.$user->lokasi;

        $query = PinjamanKelompok::select(
            'pk.*',
            'k.nama_kelompok',
            'k.alamat_kelompok',
            'k.desa',
            'desa.nama_desa',
            'jenis_produk_pinjaman.nama_jpp',
            DB::raw('COUNT(pa.id) as jumlah_anggota')
        )->from($tb_pinkel.' as pk')
            ->join('jenis_produk_pinjaman', 'pk.jenis_pp', '=', 'jenis_produk_pinjaman.id')
            ->join($tb_kel.' as k', 'k.id', '=', 'pk.id_kel')
            ->join('desa', 'k.desa', '=', 'desa.kd_desa')
            ->leftJoin($tb_pinj.' as pa', 'pa.id_pinkel', '=', 'pk.id')
            ->where('pk.status', 'V')
            ->where(function ($query) use ($search) {
                $query->where('k.nama_kelompok', 'LIKE', '%'.$search.'%')
                    ->orWhere('jenis_produk_pinjaman.nama_jpp', 'LIKE', '%'.$search.'%')
                    ->orWhere('pk.id', 'LIKE', '%'.$search.'%');
            })
            ->groupBy('pk.id', 'k.nama_kelompok', 'k.alamat_kelompok', 'k.desa', 'desa.nama_desa', 'jenis_produk_pinjaman.nama_jpp');

        $data = $query->orderBy($sortBy, $sortOrder)->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    public function waiting()
    {
        $user = request()->user();

        $page = request()->get('page') ?? 1;
        $perPage = request()->get('per_page') ?? 10;
        $sortBy = request()->get('sort_by') ?? 'id';
        $sortOrder = request()->get('sort_order') ?? 'asc';
        $search = request()->get('search') ?? '';

        $tb_pinkel = 'pinjaman_kelompok_'.$user->lokasi;
        $tb_pinj = 'pinjaman_anggota_'.$user->lokasi;
        $tb_kel = 'kelompok_'.$user->lokasi;

        $query = PinjamanKelompok::select(
            'pk.*',
            'k.nama_kelompok',
            'k.alamat_kelompok',
            'k.desa',
            'desa.nama_desa',
            'jenis_produk_pinjaman.nama_jpp',
            DB::raw('COUNT(pa.id) as jumlah_anggota')
        )->from($tb_pinkel.' as pk')
            ->join('jenis_produk_pinjaman', 'pk.jenis_pp', '=', 'jenis_produk_pinjaman.id')
            ->join($tb_kel.' as k', 'k.id', '=', 'pk.id_kel')
            ->join('desa', 'k.desa', '=', 'desa.kd_desa')
            ->leftJoin($tb_pinj.' as pa', 'pa.id_pinkel', '=', 'pk.id')
            ->where('pk.status', 'W')
            ->where(function ($query) use ($search) {
                $query->where('k.nama_kelompok', 'LIKE', '%'.$search.'%')
                    ->orWhere('jenis_produk_pinjaman.nama_jpp', 'LIKE', '%'.$search.'%')
                    ->orWhere('pk.id', 'LIKE', '%'.$search.'%');
            })
            ->groupBy('pk.id', 'k.nama_kelompok', 'k.alamat_kelompok', 'k.desa', 'desa.nama_desa', 'jenis_produk_pinjaman.nama_jpp');

        $data = $query->orderBy($sortBy, $sortOrder)->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    public function aktif()
    {
        $user = request()->user();

        $page = request()->get('page') ?? 1;
        $perPage = request()->get('per_page') ?? 10;
        $sortBy = request()->get('sort_by') ?? 'id';
        $sortOrder = request()->get('sort_order') ?? 'asc';
        $search = request()->get('search') ?? '';

        $tb_pinkel = 'pinjaman_kelompok_'.$user->lokasi;
        $tb_pinj = 'pinjaman_anggota_'.$user->lokasi;
        $tb_kel = 'kelompok_'.$user->lokasi;

        $query = PinjamanKelompok::select(
            'pk.*',
            'k.nama_kelompok',
            'k.alamat_kelompok',
            'k.desa',
            'desa.nama_desa',
            'jenis_produk_pinjaman.nama_jpp',
            DB::raw('COUNT(pa.id) as jumlah_anggota')
        )->from($tb_pinkel.' as pk')
            ->join('jenis_produk_pinjaman', 'pk.jenis_pp', '=', 'jenis_produk_pinjaman.id')
            ->join($tb_kel.' as k', 'k.id', '=', 'pk.id_kel')
            ->join('desa', 'k.desa', '=', 'desa.kd_desa')
            ->leftJoin($tb_pinj.' as pa', 'pa.id_pinkel', '=', 'pk.id')
            ->where('pk.status', 'A')
            ->where(function ($query) use ($search) {
                $query->where('k.nama_kelompok', 'LIKE', '%'.$search.'%')
                    ->orWhere('jenis_produk_pinjaman.nama_jpp', 'LIKE', '%'.$search.'%')
                    ->orWhere('pk.id', 'LIKE', '%'.$search.'%');
            })
            ->groupBy('pk.id', 'k.nama_kelompok', 'k.alamat_kelompok', 'k.desa', 'desa.nama_desa', 'jenis_produk_pinjaman.nama_jpp');

        $data = $query->orderBy($sortBy, $sortOrder)->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    public function lunas()
    {
        $user = request()->user();

        $page = request()->get('page') ?? 1;
        $perPage = request()->get('per_page') ?? 10;
        $sortBy = request()->get('sort_by') ?? 'id';
        $sortOrder = request()->get('sort_order') ?? 'asc';
        $search = request()->get('search') ?? '';

        $tb_pinkel = 'pinjaman_kelompok_'.$user->lokasi;
        $tb_pinj = 'pinjaman_anggota_'.$user->lokasi;
        $tb_kel = 'kelompok_'.$user->lokasi;
        $tb_real = 'real_angsuran_'.$user->lokasi;

        $query = PinjamanKelompok::select(
            'pk.*',
            'k.nama_kelompok',
            'k.alamat_kelompok',
            'k.desa',
            'desa.nama_desa',
            'jenis_produk_pinjaman.nama_jpp',
            DB::raw('COUNT(pa.id) as jumlah_anggota')
        )->from($tb_pinkel.' as pk')
            ->join('jenis_produk_pinjaman', 'pk.jenis_pp', '=', 'jenis_produk_pinjaman.id')
            ->join($tb_kel.' as k', 'k.id', '=', 'pk.id_kel')
            ->join('desa', 'k.desa', '=', 'desa.kd_desa')
            ->leftJoin($tb_pinj.' as pa', 'pa.id_pinkel', '=', 'pk.id')
            ->where('pk.status', 'A')
            ->whereRaw('pk.alokasi<=(SELECT SUM(realisasi_pokok) FROM '.$tb_real.' WHERE '.$tb_real.'.loan_id='.'pk.id)')
            ->where(function ($query) use ($search) {
                $query->where('k.nama_kelompok', 'LIKE', '%'.$search.'%')
                    ->orWhere('jenis_produk_pinjaman.nama_jpp', 'LIKE', '%'.$search.'%')
                    ->orWhere('pk.id', 'LIKE', '%'.$search.'%');
            })
            ->groupBy('pk.id', 'k.nama_kelompok', 'k.alamat_kelompok', 'k.desa', 'desa.nama_desa', 'jenis_produk_pinjaman.nama_jpp');

        $data = $query->orderBy($sortBy, $sortOrder)->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    public function nunggak()
    {
        $user = request()->user();
        $tgl = date('Y-m-d');

        $page = request()->get('page') ?? 1;
        $perPage = request()->get('per_page') ?? 10;
        $sortBy = request()->get('sort_by') ?? 'id';
        $sortOrder = request()->get('sort_order') ?? 'asc';
        $search = request()->get('search') ?? '';

        $tb_pinkel = 'pinjaman_kelompok_'.$user->lokasi;
        $tb_rencana = 'rencana_angsuran_'.$user->lokasi;
        $tb_realiasi = 'real_angsuran_'.$user->lokasi;
        $tb_kel = 'kelompok_'.$user->lokasi;

        $dataTunggakan = PinjamanKelompok::from("$tb_pinkel as pinkel")
            ->select([
                'pinkel.id',
                'pinkel.alokasi',
                'pinkel.tgl_cair',
                'kelompok.nama_kelompok',
                'kelompok.ketua',
                'kelompok.alamat_kelompok',
                'desa.nama_desa',
                'jenis_produk_pinjaman.nama_jpp',
                DB::raw('COALESCE(target_sum.total_wajib_pokok, 0) as target_pokok'),
                DB::raw('COALESCE(target_sum.total_wajib_jasa, 0) as target_jasa'),
                DB::raw('COALESCE(saldo_sum.total_realisasi_pokok, 0) as sum_pokok'),
                DB::raw('COALESCE(saldo_sum.total_realisasi_jasa, 0) as sum_jasa'),
                DB::raw('GREATEST(COALESCE(target_sum.total_wajib_pokok, 0) - COALESCE(saldo_sum.total_realisasi_pokok, 0), 0) as tunggakan_pokok'),
                DB::raw('GREATEST(COALESCE(target_sum.total_wajib_jasa, 0) - COALESCE(saldo_sum.total_realisasi_jasa, 0), 0) as tunggakan_jasa'),
            ])
            ->join('jenis_produk_pinjaman', 'pinkel.jenis_pp', '=', 'jenis_produk_pinjaman.id')
            ->join("$tb_kel as kelompok", 'pinkel.id_kel', '=', 'kelompok.id')
            ->join('desa', 'kelompok.desa', '=', 'desa.kd_desa')
            ->leftJoin(DB::raw("(
                SELECT 
                    loan_id,
                    SUM(wajib_pokok) as total_wajib_pokok,
                    SUM(wajib_jasa) as total_wajib_jasa
                FROM $tb_rencana
                WHERE jatuh_tempo <= '$tgl'
                AND angsuran_ke != '0'
                GROUP BY loan_id
            ) as target_sum"), 'target_sum.loan_id', '=', 'pinkel.id')
            ->leftJoin(DB::raw("(
                SELECT 
                    loan_id,
                    SUM(realisasi_pokok) as total_realisasi_pokok,
                    SUM(realisasi_jasa) as total_realisasi_jasa
                FROM $tb_realiasi
                WHERE tgl_transaksi <= '$tgl'
                GROUP BY loan_id
            ) as saldo_sum"), 'saldo_sum.loan_id', '=', 'pinkel.id')
            ->where('pinkel.status', 'A')
            ->where('pinkel.tgl_cair', '<=', $tgl);

        if ($search) {
            $dataTunggakan->where(function ($query) use ($search) {
                $query->where('kelompok.nama_kelompok', 'like', "%$search%")
                    ->orWhere('kelompok.ketua', 'like', "%$search%")
                    ->orWhere('desa.nama_desa', 'like', "%$search%");
            });
        }

        $dataTunggakan = $dataTunggakan
            ->groupBy(
                'pinkel.id',
                'pinkel.alokasi',
                'pinkel.tgl_cair',
                'kelompok.nama_kelompok',
                'kelompok.ketua',
                'kelompok.alamat_kelompok',
                'desa.nama_desa',
                'jenis_produk_pinjaman.nama_jpp',
                'target_sum.total_wajib_pokok',
                'target_sum.total_wajib_jasa',
                'saldo_sum.total_realisasi_pokok',
                'saldo_sum.total_realisasi_jasa'
            )
            ->havingRaw('tunggakan_pokok > 0 OR tunggakan_jasa > 0');

        $allowedSorts = [
            'id' => 'pinkel.id',
            'tgl_cair' => 'pinkel.tgl_cair',
            'nama_kelompok' => 'kelompok.nama_kelompok',
            'ketua' => 'kelompok.ketua',
            'desa' => 'desa.nama_desa',
            'tunggakan_pokok' => 'tunggakan_pokok',
            'tunggakan_jasa' => 'tunggakan_jasa',
            'jumlah_anggota' => 'jumlah_anggota',
        ];

        $sortColumn = $allowedSorts[$sortBy] ?? 'pinkel.id';
        $dataTunggakan = $dataTunggakan->orderByRaw("$sortColumn $sortOrder");

        $dataTunggakan = $dataTunggakan->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $dataTunggakan,
        ], 200);
    }

    public function jatuhTempo()
    {
        $user = request()->user();
        $tgl = date('Y-m-d');

        $page = request()->get('page') ?? 1;
        $perPage = request()->get('per_page') ?? 10;
        $sortBy = request()->get('sort_by') ?? 'id';
        $sortOrder = request()->get('sort_order') ?? 'asc';
        $search = request()->get('search') ?? '';

        $tb_pinkel = 'pinjaman_kelompok_'.$user->lokasi;
        $tb_rencana = 'rencana_angsuran_'.$user->lokasi;
        $tb_realiasi = 'real_angsuran_'.$user->lokasi;
        $tb_kel = 'kelompok_'.$user->lokasi;

        $dataTunggakan = PinjamanKelompok::from("$tb_pinkel as pinkel")
            ->select([
                'pinkel.id',
                'pinkel.alokasi',
                'pinkel.tgl_cair',
                'kelompok.nama_kelompok',
                'kelompok.ketua',
                'kelompok.alamat_kelompok',
                'desa.nama_desa',
                'jenis_produk_pinjaman.nama_jpp',
                DB::raw('COALESCE(target_sum.total_wajib_pokok, 0) as target_pokok'),
                DB::raw('COALESCE(target_sum.total_wajib_jasa, 0) as target_jasa'),
                DB::raw('COALESCE(saldo_sum.total_realisasi_pokok, 0) as sum_pokok'),
                DB::raw('COALESCE(saldo_sum.total_realisasi_jasa, 0) as sum_jasa'),
                DB::raw('GREATEST(COALESCE(target_sum.total_wajib_pokok, 0) - COALESCE(saldo_sum.total_realisasi_pokok, 0), 0) as tunggakan_pokok'),
                DB::raw('GREATEST(COALESCE(target_sum.total_wajib_jasa, 0) - COALESCE(saldo_sum.total_realisasi_jasa, 0), 0) as tunggakan_jasa'),
            ])
            ->join('jenis_produk_pinjaman', 'pinkel.jenis_pp', '=', 'jenis_produk_pinjaman.id')
            ->join("$tb_kel as kelompok", 'pinkel.id_kel', '=', 'kelompok.id')
            ->join('desa', 'kelompok.desa', '=', 'desa.kd_desa')
            ->leftJoin(DB::raw("(
                SELECT 
                    loan_id,
                    SUM(wajib_pokok) as total_wajib_pokok,
                    SUM(wajib_jasa) as total_wajib_jasa
                FROM $tb_rencana
                WHERE jatuh_tempo = '$tgl'
                AND angsuran_ke != '0'
                GROUP BY loan_id
            ) as target_sum"), 'target_sum.loan_id', '=', 'pinkel.id')
            ->leftJoin(DB::raw("(
                SELECT 
                    loan_id,
                    SUM(realisasi_pokok) as total_realisasi_pokok,
                    SUM(realisasi_jasa) as total_realisasi_jasa
                FROM $tb_realiasi
                WHERE tgl_transaksi <= '$tgl'
                GROUP BY loan_id
            ) as saldo_sum"), 'saldo_sum.loan_id', '=', 'pinkel.id')
            ->where('pinkel.status', 'A')
            ->whereDay('pinkel.tgl_cair', date('d', strtotime($tgl)));

        if ($search) {
            $dataTunggakan->where(function ($query) use ($search) {
                $query->where('kelompok.nama_kelompok', 'like', "%$search%")
                    ->orWhere('kelompok.ketua', 'like', "%$search%")
                    ->orWhere('desa.nama_desa', 'like', "%$search%");
            });
        }

        $dataTunggakan = $dataTunggakan
            ->groupBy(
                'pinkel.id',
                'pinkel.alokasi',
                'pinkel.tgl_cair',
                'kelompok.nama_kelompok',
                'kelompok.ketua',
                'kelompok.alamat_kelompok',
                'desa.nama_desa',
                'jenis_produk_pinjaman.nama_jpp',
                'target_sum.total_wajib_pokok',
                'target_sum.total_wajib_jasa',
                'saldo_sum.total_realisasi_pokok',
                'saldo_sum.total_realisasi_jasa'
            )
            ->havingRaw('tunggakan_pokok > 0 OR tunggakan_jasa > 0');

        $allowedSorts = [
            'id' => 'pinkel.id',
            'tgl_cair' => 'pinkel.tgl_cair',
            'nama_kelompok' => 'kelompok.nama_kelompok',
            'ketua' => 'kelompok.ketua',
            'desa' => 'desa.nama_desa',
            'tunggakan_pokok' => 'tunggakan_pokok',
            'tunggakan_jasa' => 'tunggakan_jasa',
            'jumlah_anggota' => 'jumlah_anggota',
        ];

        $sortColumn = $allowedSorts[$sortBy] ?? 'pinkel.id';
        $dataTunggakan = $dataTunggakan->orderByRaw("$sortColumn $sortOrder");

        $dataTunggakan = $dataTunggakan->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $dataTunggakan,
        ], 200);
    }

    public function detailPinjaman($id)
    {
        $user = request()->user();

        $tb_pinkel = 'pinjaman_kelompok_'.$user->lokasi;
        $tb_kel = 'kelompok_'.$user->lokasi;

        $query = PinjamanKelompok::select(
            'pk.id',
            'pk.id_kel',
            'pk.jenis_pp',
            'pk.tgl_proposal',
            'pk.tgl_verifikasi',
            'pk.tgl_tunggu',
            'pk.tgl_cair',
            'pk.proposal',
            'pk.verifikasi',
            'pk.alokasi',
            'pk.jenis_jasa',
            'pk.pros_jasa',
            'pk.jangka',
            'pk.sistem_angsuran',
            'pk.sa_jasa',
            'k.nama_kelompok',
            'k.alamat_kelompok',
            'k.desa',
            'desa.nama_desa',
            'jenis_produk_pinjaman.nama_jpp',
            'jenis_jasa.nama_jj',
        )->from($tb_pinkel.' as pk')
            ->join('jenis_produk_pinjaman', 'pk.jenis_pp', '=', 'jenis_produk_pinjaman.id')
            ->join('jenis_jasa', 'pk.jenis_jasa', '=', 'jenis_jasa.id')
            ->join($tb_kel.' as k', 'k.id', '=', 'pk.id_kel')
            ->join('desa', 'k.desa', '=', 'desa.kd_desa')
            ->where('pk.id', $id)
            ->with([
                'sis_pokok',
                'sis_jasa',
                'pinjaman_anggota' => function ($query) {
                    $query->select('id', 'id_pinkel', 'nia', 'proposal', 'verifikasi', 'alokasi');
                },
                'pinjaman_anggota.anggota' => function ($query) {
                    $query->select('id', 'namadepan', 'nik');
                },
                'saldo',
                'target',
            ])->first();

        return response()->json([
            'success' => true,
            'data' => $query,
        ], 200);
    }

    public function tagihan()
    {
        $user = request()->user();

        $apiEndpoint = ApiEndpoint::first();
        $kec = Kecamatan::where('id', $user->lokasi)->first();
        $pesan_wa = json_decode($kec->whatsapp, true);

        $tanggal = date('Y-m-d', strtotime(request()->get('tanggal_tagihan')));
        $tgl_bayar = date('Y-m-d', strtotime(request()->get('tanggal_bayar')));
        $pesan = $pesan_wa['tagihan'];

        $pesan = strtr($pesan, [
            '{Tanggal Jatuh Tempo}' => $tanggal,
            '{Tanggal Bayar}' => $tgl_bayar,
            '{User Login}' => auth()->user()->namadepan.' '.auth()->user()->namabelakang,
            '{Telpon}' => auth()->user()->hp,
        ]);

        $sortBy = request()->get('sort_by') ?? 'id';
        $sortOrder = request()->get('sort_order') ?? 'asc';

        $tb_pinkel = 'pinjaman_kelompok_'.$user->lokasi;
        $tb_rencana = 'rencana_angsuran_'.$user->lokasi;
        $tb_realiasi = 'real_angsuran_'.$user->lokasi;
        $tb_kel = 'kelompok_'.$user->lokasi;

        $pinjaman = PinjamanKelompok::from("$tb_pinkel as pinkel")
            ->select([
                'pinkel.id',
                'pinkel.alokasi',
                'pinkel.tgl_cair',
                'kelompok.nama_kelompok',
                'kelompok.ketua',
                'kelompok.alamat_kelompok',
                'kelompok.telpon',
                'desa.nama_desa',
                'sebutan_desa.sebutan_desa',
                'jenis_produk_pinjaman.nama_jpp',
                DB::raw('COALESCE(target_sum.total_wajib_pokok, 0) as target_pokok'),
                DB::raw('COALESCE(target_sum.total_wajib_jasa, 0) as target_jasa'),
                DB::raw('COALESCE(saldo_sum.total_realisasi_pokok, 0) as sum_pokok'),
                DB::raw('COALESCE(saldo_sum.total_realisasi_jasa, 0) as sum_jasa'),
                DB::raw('GREATEST(COALESCE(target_sum.total_wajib_pokok, 0) - COALESCE(saldo_sum.total_realisasi_pokok, 0), 0) as tunggakan_pokok'),
                DB::raw('GREATEST(COALESCE(target_sum.total_wajib_jasa, 0) - COALESCE(saldo_sum.total_realisasi_jasa, 0), 0) as tunggakan_jasa'),
            ])
            ->join('jenis_produk_pinjaman', 'pinkel.jenis_pp', '=', 'jenis_produk_pinjaman.id')
            ->join("$tb_kel as kelompok", 'pinkel.id_kel', '=', 'kelompok.id')
            ->join('desa', 'kelompok.desa', '=', 'desa.kd_desa')
            ->join('sebutan_desa', 'desa.sebutan', '=', 'sebutan_desa.id')
            ->leftJoin(DB::raw("(
                SELECT 
                    loan_id,
                    SUM(wajib_pokok) as total_wajib_pokok,
                    SUM(wajib_jasa) as total_wajib_jasa
                FROM $tb_rencana
                WHERE jatuh_tempo = '$tanggal'
                AND angsuran_ke != '0'
                GROUP BY loan_id
            ) as target_sum"), 'target_sum.loan_id', '=', 'pinkel.id')
            ->leftJoin(DB::raw("(
                SELECT 
                    loan_id,
                    SUM(realisasi_pokok) as total_realisasi_pokok,
                    SUM(realisasi_jasa) as total_realisasi_jasa
                FROM $tb_realiasi
                WHERE tgl_transaksi <= '$tanggal'
                GROUP BY loan_id
            ) as saldo_sum"), 'saldo_sum.loan_id', '=', 'pinkel.id')
            ->where('pinkel.status', 'A')
            ->whereDay('pinkel.tgl_cair', date('d', strtotime($tanggal)));

        $pinjaman = $pinjaman
            ->groupBy(
                'pinkel.id',
                'pinkel.alokasi',
                'pinkel.tgl_cair',
                'kelompok.nama_kelompok',
                'kelompok.ketua',
                'kelompok.alamat_kelompok',
                'desa.nama_desa',
                'sebutan_desa.sebutan_desa',
                'jenis_produk_pinjaman.nama_jpp',
                'target_sum.total_wajib_pokok',
                'target_sum.total_wajib_jasa',
                'saldo_sum.total_realisasi_pokok',
                'saldo_sum.total_realisasi_jasa'
            )
            ->havingRaw('tunggakan_pokok > 0 OR tunggakan_jasa > 0');

        $allowedSorts = [
            'id' => 'pinkel.id',
            'tgl_cair' => 'pinkel.tgl_cair',
            'nama_kelompok' => 'kelompok.nama_kelompok',
            'ketua' => 'kelompok.ketua',
            'desa' => 'desa.nama_desa',
            'tunggakan_pokok' => 'tunggakan_pokok',
            'tunggakan_jasa' => 'tunggakan_jasa',
            'jumlah_anggota' => 'jumlah_anggota',
        ];

        $sortColumn = $allowedSorts[$sortBy] ?? 'pinkel.id';
        $pinjaman = $pinjaman->orderByRaw("$sortColumn $sortOrder")->get();

        $waSession = \App\Models\Whatsapp::where('lokasi', $kec->id)->first();
        $instanceName = $waSession->instance_name ?? null;
        $waApiKey = env('WA_GATEWAY_API_KEY');
        $waBase = rtrim(env('WA_GATEWAY_BASE', $apiEndpoint->whatsapp_api ?? 'https://wa-gateway.enpiistudio.com'), '/');

        return response()->json([
            'success' => true,
            'data' => [
                'url' => $instanceName ? $waBase.'/message/sendText/'.$instanceName : $waBase,
                'instance' => $instanceName,
                'apikey' => $waApiKey,
                'data' => $pinjaman,
                'token' => $kec->token,
                'pesan' => $pesan,
            ],
        ], 200);
    }

    public function pelunasan(Request $request, $idPinjaman)
    {
        $user = request()->user();

        $pinkel = PinjamanKelompok::where('id', $idPinjaman)->with('kelompok')->first();

        PinjamanAnggota::where('id_pinkel', $idPinjaman)->update([
            'status' => 'L',
            'tgl_lunas' => date('Y-m-d'),
        ]);

        DataPemanfaat::where('id_pinkel', $idPinjaman)->where('lokasi', $user->lokasi)->update([
            'status' => 'L',
        ]);

        PinjamanKelompok::where('id', $idPinjaman)->update([
            'status' => 'L',
            'tgl_lunas' => date('Y-m-d'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Validasi Pelunasan Piutang Kelompok '.$pinkel->kelompok->nama_kelompok.' berhasil.',
        ], 200);
    }

    private function _saldo($tgl)
    {
        $bulan = [];
        for ($i = 0; $i <= date('m'); $i++) {
            $bulan[$i] = [
                'pendapatan' => 0,
                'beban' => 0,
            ];
        }

        $rekening = Rekening::where('lev1', '>=', '4')->with([
            'kom_saldo' => function ($query) use ($tgl) {
                $tahun = date('Y', strtotime($tgl));
                $query->where([
                    ['tahun', $tahun],
                    ['bulan', '<=', date('m')],
                ])->orderBy('kode_akun', 'ASC')->orderBy('bulan', 'ASC');
            },
        ])->get();

        foreach ($rekening as $rek) {
            foreach ($rek->kom_saldo as $kom_saldo) {
                $debit = 0;
                if ($kom_saldo->debit) {
                    $debit = $kom_saldo->debit;
                }
                $kredit = 0;
                if ($kom_saldo->kredit) {
                    $kredit = $kom_saldo->kredit;
                }

                $saldo = $kredit - $debit;
                if ($rek->lev1 > '4') {
                    $saldo = $debit - $kredit;
                }

                if ($rek->lev1 == '4') {
                    $bulan[intval($kom_saldo->bulan)]['pendapatan'] += $saldo;
                } else {
                    $bulan[intval($kom_saldo->bulan)]['beban'] += $saldo;
                }
            }
        }

        $nama_bulan = [];
        $pendapatan = [];
        $beban = [];
        $surplus = [];
        foreach ($bulan as $key => $value) {
            $saldo_pendapatan = 0;
            $saldo_beban = 0;
            if ($key > 0) {
                $saldo_pendapatan = $value['pendapatan'] - $bulan[$key - 1]['pendapatan'];
                $saldo_beban = $value['beban'] - $bulan[$key - 1]['beban'];
            }

            $pendapatan[$key] = $saldo_pendapatan;
            $beban[$key] = $saldo_beban;
            $surplus[$key] = $saldo_pendapatan - $saldo_beban;

            if ($key == 0) {
                $nama_bulan[$key] = 'Awl';
            } else {
                $tanggal = date('Y-m-d', strtotime(date('Y').'-'.$key.'-01'));
                $nama_bulan[$key] = substr(Tanggal::namaBulan($tanggal), 0, 3);
            }
        }

        $saldo = [
            'nama_bulan' => $nama_bulan,
            'pendapatan' => $pendapatan,
            'beban' => $beban,
            'surplus' => $surplus,
        ];

        return $saldo;
    }
}
