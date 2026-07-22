<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminInvoice;
use App\Models\AdminJenisPembayaran;
use App\Models\AdminRekening;
use App\Models\AdminTransaksi;
use App\Models\Kecamatan;
use App\Utils\Tanggal;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class InvoiceController extends Controller
{
    public function index()
    {
        $kecamatan = Kecamatan::with('kabupaten', 'kabupaten.wilayah')->orderBy('kd_kec', 'ASC')->get();
        $jenis_bayar = AdminJenisPembayaran::all();

        $batas_waktu = date('Y-m-d', strtotime('+1 month', strtotime(date('Y-m-d'))));
        $batas_waktu = Tanggal::tglIndo($batas_waktu);

        $nomor_invoice = date('ymd');
        $invoice = AdminInvoice::where('tgl_invoice', date('Y-m-d'))->count();
        $nomor_urut = str_pad($invoice + 1, '2', '0', STR_PAD_LEFT);
        $nomor_invoice .= $nomor_urut;

        $title = 'Buat Invoice';
        return view('admin.invoice.index')->with(compact('title', 'kecamatan', 'jenis_bayar', 'nomor_invoice', 'batas_waktu'));
    }

    public function store(Request $request)
    {
        $data = $request->only([
            'tgl_invoice',
            'nomor_invoice',
            'kecamatan',
            'jenis_pembayaran',
            'tgl_awal',
            'jumlah'
        ]);

        $validate = Validator::make($data, [
            'tgl_invoice' => 'required',
            'nomor_invoice' => 'required',
            'kecamatan' => 'required',
            'jenis_pembayaran' => 'required',
            'tgl_awal' => 'required',
            'jumlah' => 'required'
        ]);

        if ($validate->fails()) {
            if ($validate->fails()) {
                return response()->json($validate->errors(), Response::HTTP_MOVED_PERMANENTLY);
            }
        }

        $jp = AdminJenisPembayaran::where('id', $request->jenis_pembayaran)->first();

        $invoice = AdminInvoice::create([
            'lokasi' => $request->kecamatan,
            'nomor' => $request->nomor_invoice,
            'jenis_pembayaran' => $request->jenis_pembayaran,
            'tgl_invoice' => Tanggal::tglNasional($request->tgl_invoice),
            'tgl_lunas' => Tanggal::tglNasional($request->tgl_invoice),
            'status' => 'UNPAID',
            'jumlah' => str_replace(',', '', str_replace('.00', '', $request->jumlah)),
            'id_user' => auth()->guard('master')->user()->id
        ]);

        $tanggal = Tanggal::tglNasional($request->tgl_invoice);
        $nomor_invoice = date('ymd', strtotime($tanggal));
        $invoice = AdminInvoice::where('tgl_invoice', $tanggal)->count();
        $nomor_urut = str_pad($invoice + 1, '2', '0', STR_PAD_LEFT);
        $nomor_invoice .= $nomor_urut;

        $batas_waktu = date('Y-m-d', strtotime('+1 month', strtotime($tanggal)));

        $inv = AdminInvoice::where('status', 'UNPAID')->orderBy('idv', 'DESC')->first();
        return response()->json([
            'success' => true,
            'msg' => 'Invoice ' . $jp->nama_jp . ' No. ' . $request->nomor_invoice . ' berhasil disimpan.',
            'nomor' => $nomor_invoice,
            'batas_waktu' => Tanggal::tglIndo($batas_waktu),
            'id' => $inv->idv
        ]);
    }

    public function Unpaid()
    {
        if (request()->ajax()) {
            $invoice = AdminInvoice::where('status', 'UNPAID')->with('jp', 'kec', 'kec.kabupaten')->withSum('trx', 'jumlah');

            return DataTables::of($invoice)
                ->editColumn('tgl_invoice', function ($row) {
                    return Tanggal::tglIndo($row->tgl_invoice);
                })
                ->editColumn('jumlah', function ($row) {
                    return number_format($row->jumlah);
                })
                ->editColumn('kec.nama_kec', function ($row) {
                    $kec = '';
                    if ($row->kec) {
                        $kec .= $row->kec->nama_kec;
                        if ($row->kec->kabupaten) {
                            $kec .= ' - ' . $row->kec->kabupaten->nama_kab;
                        }
                    }

                    return $kec;
                })
                ->addColumn('saldo', function ($row) {
                    if ($row->trx_sum_jumlah) {
                        return number_format($row->jumlah - $row->trx_sum_jumlah);
                    }

                    return number_format($row->jumlah);
                })
                ->make(true);
        }

        $title = 'Invoice Unpaid';
        return view('admin.invoice.unpaid')->with(compact('title'));
    }

    public function Paid()
    {
        if (request()->ajax()) {
            $invoice = AdminInvoice::where('status', 'PAID')->with('jp', 'kec', 'kec.kabupaten')->withSum('trx', 'jumlah');

            return DataTables::of($invoice)
                ->editColumn('tgl_invoice', function ($row) {
                    return Tanggal::tglIndo($row->tgl_invoice);
                })
                ->editColumn('jumlah', function ($row) {
                    return number_format($row->jumlah);
                })
                ->editColumn('kec.nama_kec', function ($row) {
                    $kec = '';
                    if ($row->kec) {
                        $kec .= $row->kec->nama_kec;
                        if ($row->kec->kabupaten) {
                            $kec .= ' - ' . $row->kec->kabupaten->nama_kab;
                        }
                    }

                    return $kec;
                })
                ->addColumn('saldo', function ($row) {
                    if ($row->trx_sum_jumlah) {
                        return number_format($row->jumlah - $row->trx_sum_jumlah);
                    }

                    return number_format($row->jumlah);
                })
                ->make(true);
        }

        $title = 'Invoice Paid';
        return view('admin.invoice.paid')->with(compact('title'));
    }

    public function DetailUnpaid(AdminInvoice $invoice)
    {
        $invoice = AdminInvoice::where('idv', $invoice->idv)->with('kec', 'kec.kabupaten', 'jp')->withSum('trx', 'jumlah')->first();
        $rekening = AdminRekening::where('kd_rekening', '111.1001')->orwhere('kd_rekening', '121.1001')->orderBy('kd_rekening', 'DESC')->get();

        abort_unless($invoice, 404, 'Invoice tidak ditemukan di server aktif.');

        $title = 'Detail Invoice No. ' . $invoice->nomor;
        return view('admin.invoice.detail_unpaid')->with(compact('title', 'invoice', 'rekening'));
    }

    public function DetailPaid(AdminInvoice $invoice)
    {
        $invoice = AdminInvoice::where('idv', $invoice->idv)->with('kec', 'kec.kabupaten', 'jp')->withSum('trx', 'jumlah')->first();

        abort_unless($invoice, 404, 'Invoice tidak ditemukan di server aktif.');

        $title = 'Detail Invoice No. ' . $invoice->nomor;
        return view('admin.invoice.detail_paid')->with(compact('title', 'invoice'));
    }

    public function simpan(Request $request, AdminInvoice $invoice)
    {
        $data = $request->only([
            'tgl_terima',
            'jumlah',
            'keterangan',
            'metode_pembayaran'
        ]);

        $validate = Validator::make($data, [
            'tgl_terima' => 'required',
            'jumlah' => 'required',
            'keterangan' => 'required',
            'metode_pembayaran' => 'required'
        ]);

        if ($validate->fails()) {
            if ($validate->fails()) {
                return response()->json($validate->errors(), Response::HTTP_MOVED_PERMANENTLY);
            }
        }

        $jumlah = str_replace(',', '', str_replace('.00', '', $request->jumlah));
        $invoice = AdminInvoice::where('idv', $invoice->idv)->withSum('trx', 'jumlah')->first();

        $rek = AdminRekening::where('kd_rekening', $request->metode_pembayaran)->first();
        $rek_debit = $rek->kd_rekening;
        $rek_kredit = $rek->pasangan;

        $saldo = $invoice->jumlah - ($invoice->trx_sum_jumlah + $jumlah);
        $persen = round($jumlah / $invoice->jumlah * 100);

        $lunas = false;
        if (($invoice->trx_sum_jumlah + $jumlah) >= $invoice->jumlah) {
            $lunas = true;
            $inv = AdminInvoice::where('idv', $invoice->idv)->update([
                'tgl_lunas' => Tanggal::tglNasional($request->tgl_terima),
                'status' => 'PAID'
            ]);
        }

        $trx = AdminTransaksi::create([
            'tgl_transaksi' => Tanggal::tglNasional($request->tgl_terima),
            'rekening_debit' => $rek_debit,
            'rekening_kredit' => $rek_kredit,
            'idv' => $invoice->idv,
            'keterangan_transaksi' => $request->keterangan . ' (' . $persen . '%)',
            'jumlah' => $jumlah,
            'urutan' => '0',
            'id_user' => auth()->guard('master')->user()->id
        ]);

        return response()->json([
            'success' => true,
            'msg' => 'Pembayaran Invoice Berhasil Disimpan.',
            'lunas' => $lunas,
            'saldo' => number_format($saldo),
            '_saldo' => $saldo,
            'id' => $invoice->idv
        ]);
    }

    public function InvoiceNo()
    {
        $tanggal = Tanggal::tglNasional(request()->get('tgl_invoice'));

        $nomor_invoice = date('ymd', strtotime($tanggal));
        $invoice = AdminInvoice::where('tgl_invoice', $tanggal)->count();
        $nomor_urut = str_pad($invoice + 1, '2', '0', STR_PAD_LEFT);
        $nomor_invoice .= $nomor_urut;

        $batas_waktu = date('Y-m-d', strtotime('+1 month', strtotime($tanggal)));
        return response()->json([
            'nomor' => $nomor_invoice,
            'batas_waktu' => Tanggal::tglIndo($batas_waktu)
        ]);
    }

    public function Tagihan()
    {
        $jenis_pembayaran = (request()->get('jenis_pembayaran') > 0) ? request()->get('jenis_pembayaran') : 4;
        $kecamatan = (request()->get('kecamatan') > 0) ? request()->get('kecamatan') : 1;
        $kec = Kecamatan::where('id', $kecamatan)->first();

        if ($jenis_pembayaran == 1) {
            $jumlah = 12500000;
        } elseif ($jenis_pembayaran == 2) {
            $jumlah = $kec->biaya_tahunan;
        } elseif ($jenis_pembayaran == 3) {
            $jumlah = 0;
        } else {
            $jumlah = 500000;
        }

        $jenis_bayar = AdminJenisPembayaran::where('id', $jenis_pembayaran)->first();

        return response()->json([
            'jumlah' => number_format($jumlah, 2),
            'jenis_pembayaran' => $jenis_bayar->nama_jp,
            'nama_kec' => $kec->nama_kec,
            'nama_lembaga' => $kec->nama_lembaga_sort,
            'tgl_pakai' => Tanggal::tglIndo($kec->tgl_pakai)
        ]);
    }
}
