<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiEndpoint;
use App\Models\Kecamatan;
use App\Models\Kelompok;
use App\Models\PinjamanAnggota;
use App\Models\PinjamanKelompok;
use App\Models\RealAngsuran;
use App\Models\Rekening;
use App\Models\RencanaAngsuran;
use App\Models\Transaksi;
use App\Models\User;
use App\Utils\Keuangan;
use Dompdf\Dompdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Log;
use PDF;

class AngsuranController extends Controller
{
    public function index()
    {
        $tanggal = date('Y-m-d');

        $kelompok = 'kelompok_'.request()->user()->lokasi;
        $pinjamanKelompok = 'pinjaman_kelompok_'.request()->user()->lokasi;
        $realAngsuran = 'real_angsuran_'.request()->user()->lokasi;

        $daftarPinjaman = PinjamanKelompok::from("$pinjamanKelompok as pk")
            ->select(
                'pk.id',
                'k.nama_kelompok',
                'k.ketua',
                'k.kd_kelompok',
                'pk.tgl_cair',
                DB::raw('MAX(ra.tgl_transaksi) as tgl_transaksi_terakhir')
            )
            ->join("$kelompok as k", 'k.id', '=', 'pk.id_kel')
            ->leftJoin("$realAngsuran as ra", 'ra.loan_id', '=', 'pk.id')
            ->where('pk.status', 'A')
            ->whereRaw('DAY(pk.tgl_cair) = ?', [date('d', strtotime($tanggal))])
            ->groupBy('pk.id', 'k.nama_kelompok', 'k.ketua', 'k.kd_kelompok', 'pk.tgl_cair')
            ->havingRaw('MAX(ra.tgl_transaksi) IS NULL OR DATE(MAX(ra.tgl_transaksi)) != ?', [$tanggal])
            ->limit(10)
            ->orderBy('pk.tgl_cair', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $daftarPinjaman,
        ], 200);
    }

    public function search()
    {
        $keyword = request()->get('keyword');

        $lokasi = request()->user()->lokasi;
        $pk = "pinjaman_kelompok_{$lokasi}";
        $k = "kelompok_{$lokasi}";
        $keyword = strtolower($keyword);

        $pinjamanKelompok = PinjamanKelompok::from("$pk as pk")
            ->select(
                'pk.id',
                'k.nama_kelompok',
                'k.ketua',
                'k.kd_kelompok',
                'pk.tgl_cair',
            )
            ->join("$k as k", 'k.id', '=', 'pk.id_kel')
            ->where('pk.status', 'A')
            ->where(function ($q) use ($keyword) {
                $q->whereRaw('LOWER(k.nama_kelompok) LIKE ?', ["%$keyword%"])
                    ->orWhereRaw('LOWER(k.kd_kelompok) LIKE ?', ["%$keyword%"])
                    ->orWhereRaw('LOWER(k.ketua) LIKE ?', ["%$keyword%"]);
            })
            ->limit(10)
            ->orderBy('k.nama_kelompok', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $pinjamanKelompok,
        ], 200);
    }

    public function pinjaman($pinjaman_id)
    {
        $tanggal = date('Y-m-d');
        if (request()->get('tanggal')) {
            $tanggal = request()->get('tanggal');
        }

        $pinjaman = PinjamanKelompok::where('id', $pinjaman_id)
            ->with([
                'kelompok.d.sebutan_desa',
            ])
            ->first();

        if (! $pinjaman) {
            return response()->json([
                'success' => false,
                'message' => 'Pinjaman tidak ditemukan',
            ], 404);
        }

        $target = RencanaAngsuran::where([
            ['loan_id', $pinjaman_id],
            ['jatuh_tempo', '<=', $tanggal],
        ])->orderBy('jatuh_tempo', 'DESC')->first();

        $target_pokok = 0;
        $target_jasa = 0;
        if ($target) {
            $target_pokok = $target->target_pokok;
            $target_jasa = $target->target_jasa;
        }

        $saldo = RealAngsuran::where([
            ['loan_id', $pinjaman_id],
            ['tgl_transaksi', '<=', $tanggal],
        ])->orderBy('tgl_transaksi', 'DESC')->orderBy('id', 'DESC')->first();

        $sum_pokok = 0;
        $sum_jasa = 0;
        if ($saldo) {
            $sum_pokok = $saldo->sum_pokok;
            $sum_jasa = $saldo->sum_jasa;
        }

        $wajib_pokok = ($target_pokok - $sum_pokok < 0) ? 0 : $target_pokok - $sum_pokok;
        $wajib_jasa = ($target_jasa - $sum_jasa < 0) ? 0 : $target_jasa - $sum_jasa;

        $rekeningKas = Rekening::where('kode_akun', 'like', '1.1.01%')->where('kode_akun', '!=', '1.1.01.02')->get();

        $metodeAngsuran = [];
        foreach ($rekeningKas as $rk) {
            if ($rk->kode_akun == '1.1.01.01') {
                $metode = 'Tunai';
            } else {
                $namaAkun = trim(str_replace('kas di', '', strtolower($rk->nama_akun)));
                $metode = 'Transfer '.ucwords($namaAkun);
            }

            $metodeAngsuran[] = [
                'label' => $metode,
                'value' => $rk->kode_akun,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'pinjaman' => $pinjaman,
                'angsuran' => [
                    'wajib_pokok' => $wajib_pokok,
                    'wajib_jasa' => $wajib_jasa,
                    'target_pokok' => $target_pokok,
                    'target_jasa' => $target_jasa,
                    'sum_pokok' => $sum_pokok,
                    'sum_jasa' => $sum_jasa,
                ],
                'metode_angsuran' => $metodeAngsuran,
            ],
        ], 200);
    }

    public function simpan(Request $request)
    {
        $data = $request->only([
            'id',
            'id_kelompok',
            'jenis_pp',
            'alokasi',
            'pros_jasa',
            'tanggal_angsuran',
            'angsuran_pokok',
            'angsuran_jasa',
            'angsuran_denda',
            'metode_angsuran',
            'keterangan_angsuran',
            'penyetor',
        ]);

        $validate = Validator::make($data, [
            'id' => 'required',
            'jenis_pp' => 'required|in:1,2,3',
            'alokasi' => 'required|numeric',
            'pros_jasa' => 'required|numeric',
            'tanggal_angsuran' => 'required|date',
            'angsuran_pokok' => 'required|numeric',
            'angsuran_jasa' => 'required|numeric',
            'angsuran_denda' => 'required|numeric',
            'metode_angsuran' => 'required',
            'keterangan_angsuran' => 'required|string',
            'penyetor' => 'required',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Ada form yang belum diisi',
                'form_error' => $validate->errors(),
            ], 422);
        }

        if ($data['angsuran_pokok'] == 0 && $data['angsuran_jasa'] == 0 && $data['angsuran_denda'] == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Angsuran tidak boleh bernilai 0',
            ], 422);
        }

        $user = request()->user();
        $apiEndpoint = ApiEndpoint::first();
        $kec = Kecamatan::where('id', $user->lokasi)->first();
        $kelompok = Kelompok::where('id', $data['id_kelompok'])->with('d.sebutan_desa')->first();
        $pinjamanAnggota = PinjamanAnggota::where('id_pinkel', $data['id'])->get();
        $target = RencanaAngsuran::where('loan_id', $data['id'])
            ->where('jatuh_tempo', '<=', $data['tanggal_angsuran'])
            ->orderBy('jatuh_tempo', 'DESC')
            ->first();
        $saldo = RealAngsuran::where('loan_id', $data['id'])
            ->where('tgl_transaksi', '<=', $data['tanggal_angsuran'])
            ->orderBy('tgl_transaksi', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();

        $targetPokok = $target->target_pokok ?? 0;
        $targetJasa = $target->target_jasa ?? 0;
        $sumPokok = $saldo->sum_pokok ?? 0;
        $sumJasa = $saldo->sum_jasa ?? 0;

        $tunggakanPokok = max(0, $targetPokok - $sumPokok);
        $tunggakanJasa = max(0, $targetJasa - $sumJasa);

        $rekeningMap = [
            '1' => ['pokok' => '1.1.03.01', 'jasa' => '4.1.01.01', 'denda' => '4.1.01.04'],
            '2' => ['pokok' => '1.1.03.02', 'jasa' => '4.1.01.02', 'denda' => '4.1.01.05'],
            '3' => ['pokok' => '1.1.03.03', 'jasa' => '4.1.01.03', 'denda' => '4.1.01.06'],
        ];

        $rekening = $rekeningMap[$data['jenis_pp']];
        $kasUmum = $data['metode_angsuran'];
        $userId = auth()->id();

        $angsuran = null;
        $maxRetries = 3;
        for ($retryCount = 0; $retryCount < $maxRetries; $retryCount++) {
            try {
                $angsuran = DB::transaction(function () use ($data, $kasUmum, $rekening, $userId) {
                    $idtp = Transaksi::where('idtp', '!=', 0)
                        ->lockForUpdate()
                        ->max('idtp') + 1;

                    $transaksi = [];
                    $baseTransaksi = [
                        'tgl_transaksi' => $data['tanggal_angsuran'],
                        'rekening_debit' => $kasUmum,
                        'idtp' => $idtp,
                        'id_pinj' => $data['id'],
                        'id_pinj_i' => '0',
                        'relasi' => $data['penyetor'],
                        'urutan' => '0',
                        'id_user' => $userId,
                    ];

                    if ($data['angsuran_pokok'] > 0) {
                        $transaksi[] = array_merge($baseTransaksi, [
                            'rekening_kredit' => $rekening['pokok'],
                            'keterangan_transaksi' => 'Angs. (P) '.$data['keterangan_angsuran'],
                            'jumlah' => $data['angsuran_pokok'],
                        ]);
                    }

                    if ($data['angsuran_jasa'] > 0) {
                        $transaksi[] = array_merge($baseTransaksi, [
                            'rekening_kredit' => $rekening['jasa'],
                            'keterangan_transaksi' => 'Angs. (J) '.$data['keterangan_angsuran'],
                            'jumlah' => $data['angsuran_jasa'],
                        ]);
                    }

                    if ($data['angsuran_denda'] > 0) {
                        $transaksi[] = array_merge($baseTransaksi, [
                            'rekening_kredit' => $rekening['denda'],
                            'keterangan_transaksi' => 'Denda '.$data['keterangan_angsuran'],
                            'jumlah' => $data['angsuran_denda'],
                        ]);
                    }

                    Transaksi::insert($transaksi);

                    return ['idtp' => $idtp, 'transaksi' => $transaksi];
                });

                break;
            } catch (Exception $e) {
                Log::warning('Transaksi gagal, percobaan ke-'.($retryCount + 1).': '.$e->getMessage());

                if ($retryCount >= $maxRetries - 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Transaksi gagal. Silahkan coba lagi.',
                        'loan_id' => $data['id'],
                    ], 500);
                }

                usleep(500000);
            }
        }

        if (! $angsuran || ! $angsuran['transaksi']) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi gagal. Silahkan coba lagi.',
                'loan_id' => $data['id'],
            ], 500);
        }

        $idtp = $angsuran['idtp'];
        $transaksi = $angsuran['transaksi'];

        $jasaPinjaman = ($data['pros_jasa'] / 100) * $data['alokasi'];

        foreach ($pinjamanAnggota as $pa) {
            $prosPokokAnggota = ($pa->alokasi / $data['alokasi']) * 100;
            $pokokAnggota = round(($prosPokokAnggota / 100) * $data['angsuran_pokok'], 2);

            $prosJasaAnggota = 0;
            if ($jasaPinjaman != 0) {
                $prosJasaAnggota = round((($pa->pros_jasa / 100 * $pa->alokasi) / $jasaPinjaman) * 100, 2);
            }
            $jasaAnggota = round(($prosJasaAnggota / 100) * $data['angsuran_jasa'], 2);

            $komPokok = json_decode($pa->kom_pokok, true) ?: [];
            $komJasa = json_decode($pa->kom_jasa, true) ?: [];

            $komPokok[$idtp] = $pokokAnggota;
            $komJasa[$idtp] = $jasaAnggota;

            $pa->update([
                'kom_pokok' => $komPokok,
                'kom_jasa' => $komJasa,
            ]);
        }

        $rekPokok = ['1.1.03.01', '1.1.03.02', '1.1.03.03'];
        $rekJasa = ['1.1.03.04', '1.1.03.05', '1.1.03.06', '4.1.01.01', '4.1.01.02', '4.1.01.03'];

        $alokasiPokok = (int) $data['alokasi'];
        $alokasiJasa = (int) ($data['pros_jasa'] == 0 ? 0 : $data['alokasi'] * ($data['pros_jasa'] / 100));

        $realAngsuran = [
            'id' => $idtp,
            'loan_id' => $data['id'],
            'tgl_transaksi' => $data['tanggal_angsuran'],
            'realisasi_pokok' => 0,
            'realisasi_jasa' => 0,
            'sum_pokok' => $sumPokok,
            'sum_jasa' => $sumJasa,
            'saldo_pokok' => $alokasiPokok - $sumPokok,
            'saldo_jasa' => $alokasiJasa - $sumJasa,
            'tunggakan_pokok' => $tunggakanPokok,
            'tunggakan_jasa' => $tunggakanJasa,
            'lu' => $data['tanggal_angsuran'],
            'id_user' => $userId,
        ];

        foreach ($transaksi as $trx) {
            if (in_array($trx['rekening_kredit'], $rekPokok)) {
                $sumPokok += $trx['jumlah'];
                $tunggakanPokok = max(0, $tunggakanPokok - $trx['jumlah']);

                $realAngsuran['realisasi_pokok'] = $trx['jumlah'];
                $realAngsuran['sum_pokok'] = $sumPokok;
                $realAngsuran['saldo_pokok'] = $alokasiPokok - $sumPokok;
                $realAngsuran['tunggakan_pokok'] = $tunggakanPokok;
            }

            if (in_array($trx['rekening_kredit'], $rekJasa)) {
                $sumJasa += $trx['jumlah'];
                $tunggakanJasa = max(0, $tunggakanJasa - $trx['jumlah']);

                $realAngsuran['realisasi_jasa'] = $trx['jumlah'];
                $realAngsuran['sum_jasa'] = $sumJasa;
                $realAngsuran['saldo_jasa'] = $alokasiJasa - $sumJasa;
                $realAngsuran['tunggakan_jasa'] = $tunggakanJasa;
            }
        }

        RealAngsuran::insert($realAngsuran);

        $whatsapp = false;
        $nama_kelompok = $kelompok->nama_kelompok;
        $desa = $kelompok->d->sebutan_desa->sebutan_desa.' '.$kelompok->d->nama_desa;

        $pesan_wa = json_decode($kec->whatsapp, true);
        $pesan = $pesan_wa['angsuran'];
        $pesan = strtr($pesan, [
            '{Nama Kelompok}' => $nama_kelompok,
            '{Nama Desa}' => $desa,
            '{Angsuran Pokok}' => number_format($data['angsuran_pokok'], 0, ',', '.'),
            '{Angsuran Jasa}' => number_format($data['angsuran_jasa'], 0, ',', '.'),
            '{Tanggal Angsuran}' => date('Y-m-d', strtotime($data['tanggal_angsuran'])),
            '{User Login}' => $user->namadepan.' '.$user->namabelakang,
            '{Telpon}' => $user->hp,
        ]);

        $waSession = \App\Models\Whatsapp::where('lokasi', $kec->id)->first();
        $instanceName = $waSession->instance_name ?? null;

        if (strlen($kelompok->telpon) >= 11 && strlen(auth()->user()->hp) >= 11 && (Keuangan::startWith($kelompok->telpon, '08') || Keuangan::startWith($kelompok->telpon, '628'))) {
            $whatsapp = true;
        }

        return response()->json([
            'success' => true,
            'message' => 'Angsuran kelompok '.$data['keterangan_angsuran'].' berhasil diposting.',
            'data' => [
                'idtp' => $idtp,
                'send_whatsapp' => $whatsapp,
                'telpon' => $kelompok->telpon,
                'pesan' => $pesan,
                'instance' => $instanceName,
            ],
        ]);
    }

    public function detailAngsuran(Request $request, $idtp)
    {
        $user = request()->user();

        $data['kertas'] = '80';
        $data['real'] = RealAngsuran::where('id', $idtp)->with('trx', 'trx.user')->firstOrFail();
        $data['pinkel'] = PinjamanKelompok::where('id', $data['real']->loan_id)->with([
            'kelompok',
            'kelompok.d',
            'kelompok.d.sebutan_desa',
            'jpp',
            'sis_pokok',
        ])->first();

        $data['ra_bulan_ini'] = RencanaAngsuran::where([
            ['loan_id', $data['real']->loan_id],
            ['jatuh_tempo', '<=', date('Y-m-t', strtotime($data['real']->tgl_transaksi))],
        ])->orderBy('jatuh_tempo', 'DESC')->first();
        $data['angsuran'] = RencanaAngsuran::where([
            ['loan_id', $data['real']->loan_id],
            ['target_pokok', '<=', $data['real']->sum_pokok],
        ])->orderBy('jatuh_tempo', 'DESC')->first();

        $data['user'] = User::where('id', $data['real']->id_user)->first();
        $data['kec'] = Kecamatan::where('id', $user->lokasi)->with('kabupaten')->first();
        $data['keuangan'] = new Keuangan;

        $lebarKertas = 226.772;
        if ($data['kertas'] != '80') {
            $lebarKertas = 164.409;
        }

        $dompdf = new Dompdf();
        $dompdf->setPaper([0, 0, $lebarKertas, 566.929]);

        $GLOBALS['bodyHeight'] = 0;

        $dompdf->setCallbacks(
            [
                'myCallbacks' => [
                    'event' => 'end_frame', 'f' => function ($infos) {
                        $frame = $infos->get_frame();

                        if (strtolower($frame->get_node()->nodeName) === 'body') {
                            $padding_box = $frame->get_padding_box();
                            $GLOBALS['bodyHeight'] += $padding_box['h'];

                            return;
                        }
                    },
                ],
            ]
        );

        $html = view('transaksi.jurnal_angsuran.dokumen.struk_thermal', $data)->render();

        $dompdf->loadHtml($html);
        $dompdf->render();
        unset($dompdf);

        $pdf = PDF::loadHTML($html);
        $pdf->setPaper([0, 0, $lebarKertas, $GLOBALS['bodyHeight'] + 80]);

        return $pdf->stream();
    }
}
