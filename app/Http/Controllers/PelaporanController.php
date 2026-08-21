<?php

namespace App\Http\Controllers;

use App\Models\AdminInvoice;
use App\Models\AkunLevel1;
use App\Models\ArusKas;
use App\Models\Calk;
use App\Models\Desa;
use App\Models\JenisLaporan;
use App\Models\JenisLaporanPinjaman;
use App\Models\JenisProdukPinjaman;
use App\Models\Kecamatan;
use App\Models\PinjamanKelompok;
use App\Models\Rekening;
use App\Models\Saldo;
use App\Models\TandaTanganDokumen;
use App\Models\Transaksi;
use App\Models\User;
use App\Utils\Keuangan;
use App\Utils\Pinjaman;
use App\Utils\Tanggal;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use PDF;
use Session;

class PelaporanController extends Controller
{
    public function index()
    {
        $id = '32';
        if (str_replace('_', '', config('tenant.suffix')) == '277') {
            $id = '0';
        }

        $status = '0';
        if (str_replace('_', '', config('tenant.suffix')) == '1') {
            $status = '2';
        }

        $laporan = JenisLaporan::where([
            ['file', '!=', '0'],
            ['status', '!=', $status],
            ['id', '!=', $id],
        ])->orderBy('urut', 'ASC')->get();
        $kec = Kecamatan::where('id', str_replace('_', '', config('tenant.suffix')))->first();

        $title = 'Pelaporan';

        return view('pelaporan.index')->with(compact('title', 'kec', 'laporan'));
    }

    public function subLaporan($file)
    {
        $tahun = request()->get('tahun');
        $bulan = request()->get('bulan');

        $data = [];
        $tgl_kondisi = date('Y-m-t', strtotime($tahun.'-'.$bulan.'-01'));
        if ($file == 1 || $file == 2) {
            $data = [
                [
                    'title' => 'Neraca Bumdesma Lkd',
                    'value' => 'neraca_1',
                ],
                [
                    'title' => 'Laba / Rugi Bumdesma Lkd',
                    'value' => 'laba_rugi',
                ],
                [
                    'title' => 'Arus Kas',
                    'value' => 'arus_kas',
                ],
                [
                    'title' => 'Perubahan Ekuitas',
                    'value' => 'LPM',
                ],
                [
                    'title' => 'Catatan Atas Laporan Keuangan',
                    'value' => 'calk',
                ],
                [
                    'title' => 'Neraca Saldo Bumdesma Lkd',
                    'value' => 'neraca_saldo',
                ],
            ];
        }

        if ($file == 3) {
            $rekening = Rekening::where('kode_akun', '!=', '3.2.02.01')->where(function ($query) use ($tgl_kondisi) {
                $query->whereNull('tgl_nonaktif')->orwhere('tgl_nonaktif', '>', $tgl_kondisi);
            })->orderBy('kode_akun', 'ASC')->get();
            foreach ($rekening as $rek) {
                $data[] = [
                    'title' => $rek->kode_akun.'. '.$rek->nama_akun,
                    'value' => 'BB_'.$rek->kode_akun.'_'.$rek->nama_akun,
                ];
            }
        }

        if ($file == 'calk') {
            $tahun = request()->get('tahun');
            $bulan = request()->get('bulan');

            $calk = Calk::where([
                ['lokasi', str_replace('_', '', config('tenant.suffix'))],
                ['tanggal', 'LIKE', $tahun.'-'.$bulan.'%'],
            ])->first();

            $keterangan = '';
            if ($calk) {
                $keterangan = $calk->catatan;
            }

            return view('pelaporan.partials.sub_laporan')->with(compact('file', 'keterangan'));
        }

        if ($file == 'neraca') {
            $data = [
                [
                    'title' => 'Neraca 1',
                    'value' => 'neraca_1',
                ],
                [
                    'title' => 'Neraca 2',
                    'value' => 'neraca_2',
                ],
            ];
        }

        if ($file == 5 || $file == 6) {
            $status = (str_replace('_', '', config('tenant.suffix')) != '1') ? ['status', '1'] : ['status', '>=', '0'];
            $jenis_laporan = JenisLaporanPinjaman::where([
                ['file', '!=', '0'],
                $status,
            ])->orderBy('urut', 'ASC')->get();
            $mingguan = [5, 6, 8, 9, 10];
            $nomor = 0;
            foreach ($jenis_laporan as $jl) {
                if ($file == 6 && ! in_array($jl->id, $mingguan)) {
                    continue;
                }

                $nomor++;
                $data[] = [
                    'title' => str_pad($nomor, 2, '0', STR_PAD_LEFT).'. '.$jl->nama_laporan,
                    'value' => $jl->file,
                ];
            }
        }

        if ($file == 14) {
            $data = [
                [
                    'title' => '01. Januari - Maret',
                    'value' => 'EB_1,2,3',
                ],
                [
                    'title' => '02. April - Juni',
                    'value' => 'EB_4,5,6',
                ],
                [
                    'title' => '03. Juli - September',
                    'value' => 'EB_7,8,9',
                ],
                [
                    'title' => '04. Oktober - Desember',
                    'value' => 'EB_10,11,12',
                ],
                [
                    'title' => '05. Rekap Januari - Desember',
                    'value' => 'EB_12',
                ],
            ];
        }

        if ($file == 'tutup_buku') {
            $data = [
                [
                    'title' => 'Pengalokasian Laba',
                    'value' => 'alokasi_laba',
                ],
                [
                    'title' => 'Jurnal Tutup Buku',
                    'value' => 'jurnal_tutup_buku',
                ],
                [
                    'title' => 'Neraca',
                    'value' => 'neraca_tutup_buku',
                ],
                [
                    'title' => 'Laba Rugi',
                    'value' => 'laba_rugi_tutup_buku',
                ],
                [
                    'title' => 'CALK',
                    'value' => 'CALK_tutup_buku',
                ],
                [
                    'title' => 'Perubahan Ekuitas',
                    'value' => 'LPM_tutup_buku',
                ],
            ];
        }

        if ($file == 'ptk_pojk') {
            $data = [
                [
                    'title' => 'Penilaian Tingkat Kesehatan POJK',
                    'value' => 'PTK_POJK',
                ],
            ];
        }

        $laporan = JenisLaporan::where('file', $file)->first();

        if (!$laporan) {
            $laporan = new JenisLaporan();
            $laporan->nama_laporan = 'Laporan';
        }

        return view('pelaporan.partials.sub_laporan')->with(compact('file', 'data', 'laporan'));
    }

    public function preview(Request $request, $lokasi = null)
    {
        if ($lokasi != null) {
            config(['tenant.suffix' => '_'.$lokasi]);
        }

        $data = $request->only([
            'tahun',
            'bulan',
            'hari',
            'laporan',
            'sub_laporan',
            'tahun_pinjaman_cair',
            'type',
        ]);

        if ($data['laporan'] == '30|calk' && strlen($data['sub_laporan']) > 22) {
            Calk::where([
                ['lokasi', str_replace('_', '', config('tenant.suffix'))],
                ['tanggal', 'LIKE', $data['tahun'].'-'.$data['bulan'].'%'],
            ])->delete();

            Calk::create([
                'lokasi' => str_replace('_', '', config('tenant.suffix')),
                'tanggal' => $data['tahun'].'-'.$data['bulan'].'-01',
                'catatan' => $data['sub_laporan'],
            ]);
        }

        // $data['hari'] = ($data['hari']) ?: 31;
        $kec = Kecamatan::where('id', str_replace('_', '', config('tenant.suffix')))->with([
            'kabupaten',
            'desa',
            'desa.saldo' => function ($query) use ($data) {
                $data['bulan'] = $data['bulan'] ?: 12;
                $query->where('tahun', $data['tahun'])->where('bulan', '<=', $data['bulan'])->orderBy('bulan', 'ASC');
            },
            'tanda_tangan',
            'personalia',
        ])->first();
        $kab = $kec->kabupaten;

        $jabatan = '1';
        $level = '1';

        $dir = User::where([
            ['lokasi', str_replace('_', '', config('tenant.suffix'))],
            ['jabatan', $jabatan],
            ['level', $level],
            ['sejak', '<=', date('Y-m-t', strtotime($request['tahun'].'-'.$request['bulan'].'-01'))],
        ])->first();

        $data['logo'] = $this->supabaseToBase64($kec->logo);
        $data['nama_lembaga'] = $kec->nama_lembaga_sort;
        $data['nama_kecamatan'] = $kec->sebutan_kec.' '.$kec->nama_kec;

        if (Keuangan::startWith($kab->nama_kab, 'KOTA') || Keuangan::startWith($kab->nama_kab, 'KOTA')) {
            $kabupaten_text = ' '.ucwords(strtolower($kab->nama_kab));
            $data['nama_kabupaten'] = ucwords(strtolower($kab->nama_kab));
        } else {
            $kabupaten_text = ' Kabupaten '.ucwords(strtolower($kab->nama_kab));
            $data['nama_kabupaten'] = 'Kabupaten '.ucwords(strtolower($kab->nama_kab));
        }

        // Cek panjang sebelum menambahkan kabupaten
        if (strlen($data['nama_kecamatan'].$kabupaten_text) > 50) {
            $data['nama_kecamatan'] .= '<br>'.$kabupaten_text;
        } else {
            $data['nama_kecamatan'] .= $kabupaten_text;
        }

        $data['nomor_usaha'] = 'SK Kemenkumham RI No.'.$kec->nomor_bh;
        $data['info'] = $kec->alamat_kec.', Telp.'.$kec->telpon_kec;
        $data['email'] = $kec->email_kec;
        $data['kec'] = $kec;
        $data['kab'] = $kab;
        $data['dir'] = $dir;

        if ($data['tahun'] == null) {
            abort(404);
        }

        $data['bulanan'] = true;
        if ($data['bulan'] == null) {
            $data['bulanan'] = false;
            $data['bulan'] = '12';
        }

        $data['harian'] = true;
        if ($data['hari'] == null) {
            $data['harian'] = false;
            $data['hari'] = date('t', strtotime($data['tahun'].'-'.$data['bulan'].'-01'));
        }

        if ($data['laporan'] == '1' || $data['laporan'] == '2') {
            $data['bulan'] = str_pad(6 * $data['laporan'], 2, '0', STR_PAD_LEFT);
        }

        $data['tgl_kondisi'] = $data['tahun'].'-'.$data['bulan'].'-'.$data['hari'];
        $data['tanggal_kondisi'] = $kec->nama_kec.', '.Tanggal::tglLatin($data['tgl_kondisi']);

        $file_laporan = $request->laporan;
        if (str_contains($request->laporan, '|')) {
            $file_laporan = explode('|', $request->laporan)[1];
        }

        $file = $file_laporan;
        if ($request->sub_laporan && $file != 'calk') {
            if (str_contains($request->sub_laporan, '_') && ! in_array($file, ['1', '2', '5', '6', 'tutup_buku'])) {
                $laporan = explode('_', $request->sub_laporan);

                if ($file == 3) {
                    $data['kode_akun'] = $laporan[1];
                    $file_laporan = 'Buku Besar '.$laporan[2].' ('.$laporan[1].')';
                }

                if ($file == 14) {
                    $data['sub'] = $laporan[1];
                    $file_laporan = 'E - Budgeting ';
                }

                if ($file == 'neraca') {
                    $data['file_type'] = $laporan[1];
                }

                $file = $laporan[0];
            } else {
                $jenis = '';
                if ($file == 6) {
                    $jenis = '_mingguan';
                }

                $file .= $jenis;
                if ($file == 5 || $file == 6) {
                    $file_laporan = $request->sub_laporan;
                }

                $file = $request->sub_laporan;
            }
        }

        $data['jenis_laporan'] = 'dokumen_pelaporan';

        $tanda_tangan = $kec->tanda_tangan->tanda_tangan ?? '';
        $data['tanda_tangan'] = Pinjaman::keyword($tanda_tangan, $data);

        $data['laporan'] = $file_laporan;

        return $this->$file($data);
    }

    private function cover(array $data)
    {
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['judul'] = 'Laporan Keuangan';
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['judul'] = 'Laporan Keuangan';
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $view = view('pelaporan.view.cover', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'portrait');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function surat_pengantar(array $data)
    {
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        if (strlen($hari) > 0 && strlen($bln) > 0) {
            $tgl = $thn.'-'.$bln.'-'.$hari;
            $data['judul'] = 'Laporan Harian';
            $data['sub_judul'] = 'Tanggal '.Tanggal::tglLatin($tgl);
            $data['tgl'] = Tanggal::tglLatin($tgl);
        } elseif (strlen($bln) > 0) {
            $tgl = $thn.'-'.$bln.'-'.$hari;
            $data['judul'] = 'Laporan Bulanan';
            $data['sub_judul'] = 'Tanggal '.Tanggal::tglLatin(date('Y-m-t', strtotime($thn.'-'.$bln.'-01')));
            $data['tgl'] = Tanggal::tglLatin(date('Y-m-t', strtotime($thn.'-'.$bln.'-01')));
        } else {
            $tgl = $thn.'-'.$bln.'-'.$hari;
            $data['judul'] = 'Laporan Tahunan';
            $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::tahun($tgl);
        }

        $data['dir_utama'] = User::where([
            ['level', '2'],
            ['jabatan', '65'],
            ['lokasi', str_replace('_', '', config('tenant.suffix'))],
        ])->first();

        $tanda_tangan = TandaTanganDokumen::where([
            ['lokasi', str_replace('_', '', config('tenant.suffix'))],
            ['dokumen_pinjaman_id', '0'],
            ['jenis_laporan', 'surat_pengantar'],
        ])->first();
        $data['tanda_tangan'] = Pinjaman::keyword($tanda_tangan->tanda_tangan ?? '', $data);

        $view = view('pelaporan.view.surat_pengantar', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'portrait');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function neraca(array $data)
    {
        $keuangan = new Keuangan;

        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = ($data['hari']);

        if ($bln == '1' && $hari == '1') {
            return $this->neraca_tutup_buku($data);
        }

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Per '.date('t', strtotime($tgl)).' '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);

        $data['saldo_debit'] = 0;
        $data['debit'] = 0;
        $data['saldo_kredit'] = 0;
        $data['kredit'] = 0;

        $data['akun1'] = AkunLevel1::where('lev1', '<=', '3')->with([
            'akun2',
            'akun2.akun3',
            'akun2.akun3.rek',
            'akun2.akun3.rek.kom_saldo' => function ($query) use ($data) {
                $query->where('tahun', $data['tahun'])->where(function ($query) use ($data) {
                    $query->where('bulan', '0')->orwhere('bulan', $data['bulan']);
                });
            },
        ])->orderBy('kode_akun', 'ASC')->get();

        if (! isset($data['file_type'])) {
            $data['file_type'] = '1';
        }

        $view = view('pelaporan.view.neraca.neraca'.$data['file_type'], $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'portrait');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function laba_rugi(array $data)
    {
        $keuangan = new Keuangan;

        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];
        $awal_tahun = $thn.'-01-01';

        if ($bln == '1' && $hari == '1') {
            return $this->laba_rugi_tutup_buku($data);
        }

        $tgl = $thn.'-'.$bln.'-'.$hari;
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Periode '.Tanggal::tglLatin($thn.'-01-01').' S.D '.Tanggal::tglLatin($data['tgl_kondisi']);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['bulan_lalu'] = date('Y-m-t', strtotime('-1 month', strtotime($thn.'-'.$bln.'-10')));
            $data['header_lalu'] = 'Bulan Lalu';
            $data['header_sekarang'] = 'Bulan Ini';
        } else {
            $data['sub_judul'] = 'Periode '.Tanggal::tglLatin($awal_tahun).' S.D '.Tanggal::tglLatin($data['tgl_kondisi']);
            $data['tgl'] = Tanggal::tahun($tgl);
            $data['bulan_lalu'] = ($thn - 1).'-12-31';
            $data['header_lalu'] = 'Tahun Lalu';
            $data['header_sekarang'] = 'Tahun Ini';
        }

        $jenis = 'Tahunan';
        if ($data['bulanan']) {
            $jenis = 'Bulanan';
        }

        $pph = $keuangan->pph($data['tgl_kondisi'], $jenis);
        $laba_rugi = $keuangan->laporan_laba_rugi($data['tgl_kondisi'], $jenis);

        $data['pph'] = [
            'bulan_lalu' => $pph['bulan_lalu'],
            'sekarang' => $pph['bulan_ini'],
        ];

        $data['pendapatan'] = $laba_rugi['pendapatan'];
        $data['beban'] = $laba_rugi['beban'];
        $data['pendapatanNOP'] = $laba_rugi['pendapatan_non_ops'];
        $data['bebanNOP'] = $laba_rugi['beban_non_ops'];

        $view = view('pelaporan.view.laba_rugi', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'portrait');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function arus_kas(array $data)
    {
        $keuangan = new Keuangan;

        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        $data['jenis'] = 'Tahunan';
        $data['awal'] = 'TAHUN';
        $tgl_lalu = ($thn).'-00-00';
        if ($data['bulanan'] && ! ($data['laporan'] == '1' || $data['laporan'] == '2')) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['jenis'] = 'Bulanan';
            $data['awal'] = 'BULAN';

            $bulan_lalu = $bln - 1;
            if ($bulan_lalu <= 0) {
                $bulan_lalu = 12;
                $thn -= 1;
            }

            $tgl_lalu = $thn.'-'.$bulan_lalu.'-'.date('t', strtotime($thn.'-'.$bulan_lalu.'-01'));
        }

        if ($data['laporan'] == '1') {
            $data['laporan'] = 'Arus Kas Semester I';
            $data['sub_judul'] = 'Semester I Tahun '.Tanggal::tahun($tgl);
            $data['jenis'] = 'Semester I';
            $data['tgl'] = Tanggal::tglLatin($thn.'-01-01').' S.D '.Tanggal::tglLatin($thn.'-06-30');
        }

        if ($data['laporan'] == '2') {
            $data['laporan'] = 'Arus Kas Semester II';
            $data['sub_judul'] = 'Semester II Tahun '.Tanggal::tahun($tgl);
            $data['jenis'] = 'Semester II';
            $data['tgl'] = Tanggal::tglLatin($thn.'-07-01').' S.D '.Tanggal::tglLatin($thn.'-12-31');
        }

        $data['keuangan'] = $keuangan;
        $data['arus_kas'] = ArusKas::where('sub', '0')->with('child')->orderBy('id', 'ASC')->get();

        $data['saldo_bulan_lalu'] = $keuangan->saldoKas($tgl_lalu);

        $view = view('pelaporan.view.arus_kas', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'portrait');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function LPM(array $data)
    {
        $data['laporan'] = 'Laporan Perubahan Ekuitas';
        $keuangan = new Keuangan;

        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        if ($bln == '1' && $hari == '1') {
            return $this->LPM_tutup_buku($data);
        }

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['keuangan'] = $keuangan;
        $data['rekening'] = Rekening::where('lev1', '3')->where(function ($query) use ($data) {
            $query->whereNull('tgl_nonaktif')->orwhere('tgl_nonaktif', '>', $data['tgl_kondisi']);
        })->with([
            'kom_saldo' => function ($query) use ($data) {
                $query->where('tahun', $data['tahun'])->where(function ($query) use ($data) {
                    $query->where('bulan', '0')->orwhere('bulan', $data['bulan']);
                });
            },
        ])->get();

        $view = view('pelaporan.view.perubahan_modal', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'portrait');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function CALK(array $data)
    {
        $keuangan = new Keuangan;

        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $trx = Transaksi::where([
            ['keterangan_transaksi', 'LIKE', '%tahun '.$data['tahun'] - 1],
            ['rekening_debit', '3.2.01.01'],
        ])->first();

        $data['tgl_mad'] = $data['tgl_kondisi'];
        if ($trx) {
            $data['tgl_mad'] = $trx->tgl_transaksi;
        }

        if ($bln == '1' && $hari == '1') {
            return $this->CALK_tutup_buku($data);
        }

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['tgl'] = Tanggal::tahun($tgl);
        $data['nama_tgl'] = 'Tahun '.$thn;
        $data['sub_judul'] = 'Tahun '.$thn;
        if ($data['bulanan']) {
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['nama_tgl'] = 'Bulan '.Tanggal::namaBulan($tgl).' Tahun '.$thn;
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' Tahun '.$thn;
        }

        $data['debit'] = 0;
        $data['kredit'] = 0;

        $data['akun1'] = AkunLevel1::where('lev1', '<=', '3')->with([
            'akun2',
            'akun2.akun3',
            'akun2.akun3.rek' => function ($query) use ($data) {
                $query->whereNull('tgl_nonaktif')->orwhere('tgl_nonaktif', '>', $data['tgl_kondisi']);
            },
            'akun2.akun3.rek.kom_saldo' => function ($query) use ($data) {
                $query->where('tahun', $data['tahun'])->where(function ($query) use ($data) {
                    $query->where('bulan', '0')->orwhere('bulan', $data['bulan']);
                });
            },
        ])->orderBy('kode_akun', 'ASC')->get();

        $data['keterangan'] = Calk::where([
            ['lokasi', str_replace('_', '', config('tenant.suffix'))],
            ['tanggal', 'LIKE', $data['tahun'].'-'.$data['bulan'].'%'],
        ])->first();

        $data['sekr'] = User::where([
            ['level', '1'],
            ['jabatan', '2'],
            ['lokasi', str_replace('_', '', config('tenant.suffix'))],
        ])->first();

        $data['bend'] = User::where([
            ['level', '1'],
            ['jabatan', '3'],
            ['lokasi', str_replace('_', '', config('tenant.suffix'))],
        ])->first();

        $data['pengawas'] = User::where([
            ['level', '3'],
            ['jabatan', '1'],
            ['lokasi', str_replace('_', '', config('tenant.suffix'))],
        ])->first();

        $data['dir_utama'] = User::where([
            ['level', '2'],
            ['jabatan', '65'],
            ['lokasi', str_replace('_', '', config('tenant.suffix'))],
        ])->first();

        $data['saldo_calk'] = Saldo::where([
            ['kode_akun', $data['kec']->kd_kec],
            ['tahun', $thn],
        ])->get();

        $calk = json_decode($data['kec']->calk, true);
        $data['pointA'] = $calk['calk']['A'] ?? '';

        $view = view('pelaporan.view.calk', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'portrait');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function calk_c(array $data)
    {
        $keuangan = new Keuangan;

        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $trx = Transaksi::where([
            ['keterangan_transaksi', 'LIKE', '%tahun '.$data['tahun'] - 1],
            ['rekening_debit', '3.2.01.01'],
        ])->first();

        $data['tgl_mad'] = $data['tgl_kondisi'];
        if ($trx) {
            $data['tgl_mad'] = $trx->tgl_transaksi;
        }

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['tgl'] = Tanggal::tahun($tgl);
        $data['nama_tgl'] = 'Tahun '.$thn;
        $data['sub_judul'] = 'Tahun '.$thn;
        if ($data['bulanan']) {
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['nama_tgl'] = 'Bulan '.Tanggal::namaBulan($tgl).' Tahun '.$thn;
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' Tahun '.$thn;
        }

        $data['debit'] = 0;
        $data['kredit'] = 0;

        $data['akun1'] = AkunLevel1::where('lev1', '<=', '3')->with([
            'akun2',
            'akun2.akun3',
            'akun2.akun3.rek' => function ($query) use ($data) {
                $query->whereNull('tgl_nonaktif')->orwhere('tgl_nonaktif', '>', $data['tgl_kondisi']);
            },
            'akun2.akun3.rek.kom_saldo' => function ($query) use ($data) {
                $query->where('tahun', $data['tahun'])->where(function ($query) use ($data) {
                    $query->where('bulan', '0')->orwhere('bulan', $data['bulan']);
                });
            },
        ])->orderBy('kode_akun', 'ASC')->get();

        $view = view('pelaporan.view.calk_c', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'portrait');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function jurnal_transaksi(array $data)
    {
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        if ($bln == '1' && $hari == '1') {
            return $this->jurnal_tutup_buku($data);
        }

        $tgl = $thn.'-'.$bln.'-'.$hari;
        if (! $data['bulanan']) {
            $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::tahun($tgl);
            $data['transaksi'] = Transaksi::whereBetween('tgl_transaksi', [
                $thn.'-01-01',
                $thn.'-12-31',
            ])->where(function ($query) {
                $query->where('rekening_debit', '!=', '0')->orwhere('rekening_kredit', '!=', '0');
            })->with('user', 'rek_debit', 'rek_kredit', 'angs', 'angs.rek_debit', 'angs.rek_kredit')->orderBy('tgl_transaksi', 'ASC')->orderBy('idt', 'ASC')->get();
        } else {
            if (! $data['harian']) {
                $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
                $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
                $data['transaksi'] = Transaksi::whereBetween('tgl_transaksi', [
                    $thn.'-'.$bln.'-01',
                    $thn.'-'.$bln.'-'.date('t', strtotime($thn.'-'.$bln.'-01')),
                ])->where(function ($query) {
                    $query->where('rekening_debit', '!=', '0')->orwhere('rekening_kredit', '!=', '0');
                })->with('user', 'rek_debit', 'rek_kredit', 'angs', 'angs.rek_debit', 'angs.rek_kredit')->orderBy('tgl_transaksi', 'ASC')->orderBy('idt', 'ASC')->get();
            } else {
                $data['sub_judul'] = 'Tanggal '.$hari.' Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
                $data['tgl'] = Tanggal::tglLatin($tgl);
                $data['transaksi'] = Transaksi::where('tgl_transaksi', $tgl)->where(function ($query) {
                    $query->where('rekening_debit', '!=', '0')->orwhere('rekening_kredit', '!=', '0');
                })->with('user', 'rek_debit', 'rek_kredit', 'angs', 'angs.rek_debit', 'angs.rek_kredit')->orderBy('tgl_transaksi', 'ASC')->orderBy('idt', 'ASC')->get();
            }
        }

        $view = view('pelaporan.view.jurnal_transaksi', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'portrait');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function BB(array $data)
    {
        $keuangan = new Keuangan;

        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $tgl = $thn.'-';
        $data['judul'] = 'Laporan Tahunan';
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        $awal_bulan = $thn.'00-00';
        if ($data['bulanan']) {
            $tgl = $thn.'-'.$bln.'-';
            $data['judul'] = 'Laporan Bulanan';
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $bulan_lalu = date('m', strtotime('-1 month', strtotime($tgl.'01')));
            $awal_bulan = $thn.'-'.$bulan_lalu.'-'.date('t', strtotime($thn.'-'.$bulan_lalu));
            if ($bln == 1) {
                $awal_bulan = $thn.'00-00';
            }
        }

        if ($data['harian']) {
            $tgl = $thn.'-'.$bln.'-'.$hari;
            $data['judul'] = 'Laporan Harian';
            $data['sub_judul'] = 'Tanggal '.$hari.' '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::tglLatin($tgl);
            $awal_bulan = $tgl;
            if ($tgl != $thn.'-01-01') {
                $awal_bulan = date('Y-m-d', strtotime('-1 day', strtotime($tgl)));
            }
        }

        $data['rek'] = Rekening::where('kode_akun', $data['kode_akun'])->first();
        $data['transaksi'] = Transaksi::where('tgl_transaksi', 'LIKE', '%'.$tgl.'%')->where(function ($query) use ($data) {
            $query->where('rekening_debit', $data['kode_akun'])->orwhere('rekening_kredit', $data['kode_akun']);
        })->with([
            'user',
            'kas_angs' => function ($query) {
                $query->where([
                    ['id_pinj', '!=', '0'],
                    ['idtp', '!=', '0'],
                    ['rekening_debit', 'NOT LIKE', '1.1.01.01'],
                ]);
            },
        ])->orderBy('tgl_transaksi', 'ASC')->orderBy('urutan', 'ASC')->orderBy('idt', 'ASC')->get();

        $data['saldo'] = $keuangan->saldoAwal($data['tgl_kondisi'], $data['kode_akun']);
        $data['d_bulan_lalu'] = $keuangan->saldoD($awal_bulan, $data['kode_akun']);
        $data['k_bulan_lalu'] = $keuangan->saldoK($awal_bulan, $data['kode_akun']);

        if ($tgl == $thn.'-01-01') {
            $data['d_bulan_lalu'] = '0';
            $data['k_bulan_lalu'] = '0';
        }

        $view = view('pelaporan.view.buku_besar', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'portrait');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function neraca_saldo(array $data)
    {
        $keuangan = new Keuangan;

        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['keuangan'] = $keuangan;
        $data['rekening'] = Rekening::whereNull('tgl_nonaktif')->orwhere('tgl_nonaktif', '>', $data['tgl_kondisi'])->orderBy('kode_akun', 'ASC')->with([
            'kom_saldo' => function ($query) use ($data) {
                $query->where('tahun', $data['tahun'])->where(function ($query) use ($data) {
                    $query->where('bulan', '0')->orwhere('bulan', $data['bulan']);
                });
            },
        ])->get();

        $view = view('pelaporan.view.neraca_saldo', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function penduduk(array $data)
    {
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['desa'] = Desa::where('kd_kec', $data['kec']->kd_kec)->with([
            'anggota',
            'anggota.u',
            'sebutan_desa',
        ])->get();

        $view = view('pelaporan.view.basis_data.penduduk', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function kelompok(array $data)
    {
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['desa'] = Desa::where('kd_kec', $data['kec']->kd_kec)->with([
            'kelompok' => function ($query) {
                $query->where('jenis_produk_pinjaman', '!=', '3');
            },
            'kelompok.tk',
            'sebutan_desa',
        ])->get();

        $view = view('pelaporan.view.basis_data.kelompok', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function lembaga_lain(array $data)
    {
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['desa'] = Desa::where('kd_kec', 'LIKE', $data['kec']->kd_kab.'%')->with([
            'kelompok' => function ($query) {
                $query->where('jenis_produk_pinjaman', '=', '3');
            },
            'sebutan_desa',
        ])->get();

        $view = view('pelaporan.view.basis_data.lembaga_lain', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function kelompok_aktif(array $data)
    {
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['jenis_pp'] = JenisProdukPinjaman::where('lokasi', '0')->with([
            'pinjaman_kelompok' => function ($query) use ($data) {
                $tb_pinkel = 'pinjaman_kelompok_'.$data['kec']->id;
                $tb_kel = 'kelompok_'.$data['kec']->id;
                $data['tb_pinkel'] = $tb_pinkel;

                $query->select($tb_pinkel.'.*', $tb_kel.'.nama_kelompok', $tb_kel.'.ketua', 'desa.nama_desa', 'desa.kd_desa', 'desa.kode_desa', 'sebutan_desa.sebutan_desa')
                    ->join($tb_kel, $tb_kel.'.id', '=', $tb_pinkel.'.id_kel')
                    ->join('desa', $tb_kel.'.desa', '=', 'desa.kd_desa')
                    ->join('sebutan_desa', 'sebutan_desa.id', '=', 'desa.sebutan')
                    ->withCount('pinjaman_anggota')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->where('tgl_transaksi', 'LIKE', '%'.$data['tahun'].'-'.$data['bulan'].'-%');
                    }], 'realisasi_pokok')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->where('tgl_transaksi', 'LIKE', '%'.$data['tahun'].'-'.$data['bulan'].'-%');
                    }], 'realisasi_jasa')
                    ->where(function ($query) use ($data) {
                        $query->where([
                            [$data['tb_pinkel'].'.status', 'A'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', $data['tgl_kondisi']],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', $data['tgl_kondisi']],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', $data['tgl_kondisi']],
                        ]);
                    })
                    ->orderBy($tb_kel.'.desa', 'ASC')
                    ->orderBy($tb_pinkel.'.tgl_cair', 'ASC');
            },
            'pinjaman_kelompok.saldo' => function ($query) use ($data) {
                $query->where('tgl_transaksi', '<=', $data['tgl_kondisi']);
            },
            'pinjaman_kelompok.target' => function ($query) use ($data) {
                $query->where('jatuh_tempo', '<=', $data['tgl_kondisi']);
            },
            'pinjaman_kelompok.sis_pokok',
        ])->get();

        $view = view('pelaporan.view.perkembangan_piutang.kelompok_aktif', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function pemanfaat_aktif(array $data)
    {
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['jenis_pp'] = JenisProdukPinjaman::where('lokasi', '0')->with([
            'pinjaman_anggota' => function ($query) use ($data) {
                $tb_pinj = 'pinjaman_anggota_'.$data['kec']->id;
                $tb_angg = 'anggota_'.$data['kec']->id;
                $tb_kel = 'kelompok_'.$data['kec']->id;
                $data['tb_pinj'] = $tb_pinj;

                $query->select(
                    $tb_pinj.'.*',
                    $tb_angg.'.namadepan',
                    $tb_angg.'.alamat',
                    $tb_angg.'.nik',
                    $tb_angg.'.kk',
                    $tb_kel.'.nama_kelompok',
                    'desa.nama_desa',
                    'desa.kd_desa',
                    'desa.kode_desa',
                    'sebutan_desa.sebutan_desa'
                )
                    ->join($tb_angg, $tb_angg.'.id', '=', $tb_pinj.'.nia')
                    ->join($tb_kel, $tb_kel.'.id', '=', $tb_pinj.'.id_kel')
                    ->join('desa', $tb_angg.'.desa', '=', 'desa.kd_desa')
                    ->join('sebutan_desa', 'sebutan_desa.id', '=', 'desa.sebutan')
                    ->where(function ($query) use ($data) {
                        $query->where([
                            [$data['tb_pinj'].'.status', 'A'],
                            [$data['tb_pinj'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                        ])->orwhere([
                            [$data['tb_pinj'].'.status', 'L'],
                            [$data['tb_pinj'].'.tgl_lunas', '>=', $data['tgl_kondisi']],
                        ])->orwhere([
                            [$data['tb_pinj'].'.status', 'R'],
                            [$data['tb_pinj'].'.tgl_lunas', '>=', $data['tgl_kondisi']],
                        ])->orwhere([
                            [$data['tb_pinj'].'.status', 'H'],
                            [$data['tb_pinj'].'.tgl_lunas', '>=', $data['tgl_kondisi']],
                        ]);
                    })
                    ->orderBy($tb_angg.'.desa', 'ASC')
                    ->orderBy($tb_pinj.'.id_pinkel', 'ASC')
                    ->orderBy($tb_pinj.'.id', 'ASC')
                    ->orderBy($tb_pinj.'.tgl_cair', 'ASC');
            },
        ])->get();

        $view = view('pelaporan.view.perkembangan_piutang.pemanfaat_aktif', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function proposal(array $data)
    {
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['jenis_pp'] = JenisProdukPinjaman::where('lokasi', '0')->with([
            'pinjaman_kelompok' => function ($query) use ($data) {
                $tb_pinkel = 'pinjaman_kelompok_'.$data['kec']->id;
                $tb_kel = 'kelompok_'.$data['kec']->id;
                $data['tb_pinkel'] = $tb_pinkel;

                $query->select($tb_pinkel.'.*', $tb_kel.'.nama_kelompok', $tb_kel.'.ketua', 'desa.nama_desa', 'desa.kd_desa', 'desa.kode_desa', 'sebutan_desa.sebutan_desa')
                    ->join($tb_kel, $tb_kel.'.id', '=', $tb_pinkel.'.id_kel')
                    ->join('desa', $tb_kel.'.desa', '=', 'desa.kd_desa')
                    ->join('sebutan_desa', 'sebutan_desa.id', '=', 'desa.sebutan')
                    ->with([
                        'pinjaman_anggota',
                        'pinjaman_anggota.anggota',
                        'pinjaman_anggota.anggota.d',
                        'pinjaman_anggota.anggota.d.sebutan_desa',
                    ])
                    ->where('status', 'P')
                    ->orderBy($tb_kel.'.desa', 'ASC')
                    ->orderBy($tb_pinkel.'.tgl_proposal', 'ASC');
            },
            'pinjaman_kelompok.sis_pokok',
        ])->get();

        $view = view('pelaporan.view.perkembangan_piutang.proposal', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function verifikasi(array $data)
    {
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['jenis_pp'] = JenisProdukPinjaman::where('lokasi', '0')->with([
            'pinjaman_kelompok' => function ($query) use ($data) {
                $tb_pinkel = 'pinjaman_kelompok_'.$data['kec']->id;
                $tb_kel = 'kelompok_'.$data['kec']->id;
                $data['tb_pinkel'] = $tb_pinkel;

                $query->select($tb_pinkel.'.*', $tb_kel.'.nama_kelompok', $tb_kel.'.ketua', 'desa.nama_desa', 'desa.kd_desa', 'desa.kode_desa', 'sebutan_desa.sebutan_desa')
                    ->join($tb_kel, $tb_kel.'.id', '=', $tb_pinkel.'.id_kel')
                    ->join('desa', $tb_kel.'.desa', '=', 'desa.kd_desa')
                    ->join('sebutan_desa', 'sebutan_desa.id', '=', 'desa.sebutan')
                    ->withCount('pinjaman_anggota')
                    ->where('status', 'V')
                    ->orderBy($tb_kel.'.desa', 'ASC')
                    ->orderBy($tb_pinkel.'.tgl_verifikasi', 'ASC');
            },
            'pinjaman_kelompok.sis_pokok',
        ])->get();

        $view = view('pelaporan.view.perkembangan_piutang.verifikasi', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function waiting(array $data)
    {
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['jenis_pp'] = JenisProdukPinjaman::where('lokasi', '0')->with([
            'pinjaman_kelompok' => function ($query) use ($data) {
                $tb_pinkel = 'pinjaman_kelompok_'.$data['kec']->id;
                $tb_kel = 'kelompok_'.$data['kec']->id;
                $data['tb_pinkel'] = $tb_pinkel;

                $query->select($tb_pinkel.'.*', $tb_kel.'.nama_kelompok', $tb_kel.'.ketua', 'desa.nama_desa', 'desa.kd_desa', 'desa.kode_desa', 'sebutan_desa.sebutan_desa')
                    ->join($tb_kel, $tb_kel.'.id', '=', $tb_pinkel.'.id_kel')
                    ->join('desa', $tb_kel.'.desa', '=', 'desa.kd_desa')
                    ->join('sebutan_desa', 'sebutan_desa.id', '=', 'desa.sebutan')
                    ->withCount('pinjaman_anggota')
                    ->where('status', 'W')
                    ->orderBy($tb_kel.'.desa', 'ASC')
                    ->orderBy($tb_pinkel.'.tgl_tunggu', 'ASC');
            },
            'pinjaman_kelompok.sis_pokok',
        ])->get();

        $view = view('pelaporan.view.perkembangan_piutang.waiting', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function pengajuan_proposal(array $data)
    {
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        // Filter per-periode: hanya proposal yang tgl_proposal-nya jatuh di dalam
        // periode laporan (per-hari / per-bulan / per-tahun), bukan kumulatif.
        $tgl_akhir = $data['tgl_kondisi'];
        if ($data['harian']) {
            $tgl_awal = $tgl_akhir;
        } elseif ($data['bulanan']) {
            $tgl_awal = $data['tahun'].'-'.$data['bulan'].'-01';
        } else {
            $tgl_awal = $data['tahun'].'-01-01';
            $tgl_akhir = $data['tahun'].'-12-31';
        }

        $data['jenis_pp'] = JenisProdukPinjaman::where('lokasi', '0')->with([
            'pinjaman_kelompok' => function ($query) use ($data, $tgl_awal, $tgl_akhir) {
                $tb_pinkel = 'pinjaman_kelompok_'.$data['kec']->id;
                $tb_kel = 'kelompok_'.$data['kec']->id;
                $data['tb_pinkel'] = $tb_pinkel;

                $query->select($tb_pinkel.'.*', $tb_kel.'.nama_kelompok', $tb_kel.'.ketua', 'desa.nama_desa', 'desa.kd_desa', 'desa.kode_desa', 'sebutan_desa.sebutan_desa')
                    ->join($tb_kel, $tb_kel.'.id', '=', $tb_pinkel.'.id_kel')
                    ->join('desa', $tb_kel.'.desa', '=', 'desa.kd_desa')
                    ->join('sebutan_desa', 'sebutan_desa.id', '=', 'desa.sebutan')
                    ->with([
                        'pinjaman_anggota',
                        'pinjaman_anggota.anggota',
                        'pinjaman_anggota.anggota.d',
                        'pinjaman_anggota.anggota.d.sebutan_desa',
                        'pinjaman_anggota.pinjaman_lain',
                    ])
                    ->where($tb_pinkel.'.sistem_angsuran', '!=', '12')->where(function ($query) use ($data, $tgl_awal, $tgl_akhir) {
                        $query->where([
                            [$data['tb_pinkel'].'.status', 'P'],
                        ])->whereBetween($data['tb_pinkel'].'.tgl_proposal', [$tgl_awal, $tgl_akhir]);
                    })
                    ->orderBy($tb_kel.'.desa', 'ASC')
                    ->orderBy($tb_pinkel.'.tgl_proposal', 'ASC');
            },
        ])->get();

        $view = view('pelaporan.view.perkembangan_piutang.rekap_pengajuan_proposal', $data)->render();
        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function waiting_list(array $data)
    {
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['jenis_pp'] = JenisProdukPinjaman::where('lokasi', '0')->with([
            'pinjaman_kelompok' => function ($query) use ($data) {
                $tb_pinkel = 'pinjaman_kelompok_'.$data['kec']->id;
                $tb_kel = 'kelompok_'.$data['kec']->id;
                $data['tb_pinkel'] = $tb_pinkel;

                $query->select($tb_pinkel.'.*', $tb_kel.'.nama_kelompok', $tb_kel.'.ketua', 'desa.nama_desa', 'desa.kd_desa', 'desa.kode_desa', 'sebutan_desa.sebutan_desa')
                    ->join($tb_kel, $tb_kel.'.id', '=', $tb_pinkel.'.id_kel')
                    ->join('desa', $tb_kel.'.desa', '=', 'desa.kd_desa')
                    ->join('sebutan_desa', 'sebutan_desa.id', '=', 'desa.sebutan')
                    ->with([
                        'pinjaman_anggota',
                        'pinjaman_anggota.anggota',
                        'pinjaman_anggota.anggota.d',
                        'pinjaman_anggota.anggota.d.sebutan_desa',
                        'pinjaman_anggota.pinjaman_lain',
                    ])
                    ->where($tb_pinkel.'.sistem_angsuran', '!=', '12')->where(function ($query) use ($data) {
                        $query->where([
                            [$data['tb_pinkel'].'.status', 'W'],
                            [$data['tb_pinkel'].'.tgl_tunggu', '<=', $data['tgl_kondisi']],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'A'],
                            [$data['tb_pinkel'].'.tgl_tunggu', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_cair', '>=', "$data[tahun]-01-01"],
                        ]);
                    })
                    ->orderBy($tb_kel.'.desa', 'ASC')
                    ->orderBy($tb_pinkel.'.tgl_tunggu', 'ASC');
            },
        ])->get();

        $view = view('pelaporan.view.perkembangan_piutang.waiting_list', $data)->render();
        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    public function tidak_layak(array $data)
    {
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['jenis_pp'] = JenisProdukPinjaman::where('lokasi', '0')->with([
            'pinjaman_kelompok' => function ($query) use ($data) {
                $tb_pinkel = 'pinjaman_kelompok_'.$data['kec']->id;
                $tb_kel = 'kelompok_'.$data['kec']->id;
                $data['tb_pinkel'] = $tb_pinkel;

                $query->select($tb_pinkel.'.*', $tb_kel.'.nama_kelompok', $tb_kel.'.ketua', 'desa.nama_desa', 'desa.kd_desa', 'desa.kode_desa', 'sebutan_desa.sebutan_desa')
                    ->join($tb_kel, $tb_kel.'.id', '=', $tb_pinkel.'.id_kel')
                    ->join('desa', $tb_kel.'.desa', '=', 'desa.kd_desa')
                    ->join('sebutan_desa', 'sebutan_desa.id', '=', 'desa.sebutan')
                    ->with([
                        'pinjaman_anggota',
                        'pinjaman_anggota.anggota',
                        'pinjaman_anggota.anggota.d',
                        'pinjaman_anggota.pinjaman_anggota_layak',
                        'pinjaman_anggota.pinjaman_anggota_layak.d',
                        'pinjaman_anggota.pinjaman_anggota_layak.d.sebutan_desa',
                    ])
                    ->where($tb_pinkel.'.sistem_angsuran', '!=', '12')->where(function ($query) use ($data) {
                        $query->where([
                            [$data['tb_pinkel'].'.status', 'T'],
                            [$data['tb_pinkel'].'.tgl_tunggu', '<=', $data['tgl_kondisi']],
                        ]);
                    })
                    ->orderBy($tb_kel.'.desa', 'ASC')
                    ->orderBy($tb_pinkel.'.tgl_tunggu', 'ASC');
            },
        ])->get();

        $view = view('pelaporan.view.perkembangan_piutang.tidak_layak', $data)->render();
        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function lunas(array $data)
    {
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['jenis_pp'] = JenisProdukPinjaman::where('lokasi', '0')->with([
            'pinjaman_kelompok' => function ($query) use ($data) {
                $tb_pinkel = 'pinjaman_kelompok_'.$data['kec']->id;
                $tb_kel = 'kelompok_'.$data['kec']->id;
                $data['tb_pinkel'] = $tb_pinkel;

                $query->select($tb_pinkel.'.*', $tb_kel.'.nama_kelompok', $tb_kel.'.ketua', 'desa.nama_desa', 'desa.kd_desa', 'desa.kode_desa', 'sebutan_desa.sebutan_desa')
                    ->join($tb_kel, $tb_kel.'.id', '=', $tb_pinkel.'.id_kel')
                    ->join('desa', $tb_kel.'.desa', '=', 'desa.kd_desa')
                    ->join('sebutan_desa', 'sebutan_desa.id', '=', 'desa.sebutan')
                    ->with([
                        'pinjaman_anggota',
                        'pinjaman_anggota.anggota',
                        'pinjaman_anggota.anggota.d',
                        'pinjaman_anggota.anggota.d.sebutan_desa',
                        'pinjaman_anggota.pinjaman_lain' => function ($query) use ($data) {
                            $query->where([
                                ['tgl_lunas', '<=', $data['tgl_kondisi']],
                            ]);
                        },
                    ])
                    ->where($tb_pinkel.'.sistem_angsuran', '!=', '12')->where(function ($query) use ($data) {
                        $query->where([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                        ]);
                    })
                    ->orderBy($tb_kel.'.desa', 'ASC')
                    ->orderBy($tb_pinkel.'.tgl_lunas', 'ASC');
            },
        ])->get();

        $view = view('pelaporan.view.perkembangan_piutang.lunas', $data)->render();
        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function pinjaman_per_kelompok(array $data)
    {
        $data['lpp'] = 'Bulan';
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['tgl_lalu'] = $data['tahun'].'-'.$data['bulan'].'-01';

        $data['jenis_pp'] = JenisProdukPinjaman::where('lokasi', '0')->with([
            'pinjaman_kelompok' => function ($query) use ($data) {
                $tb_pinkel = 'pinjaman_kelompok_'.$data['kec']->id;
                $tb_kel = 'kelompok_'.$data['kec']->id;
                $data['tb_pinkel'] = $tb_pinkel;

                $query->select($tb_pinkel.'.*', $tb_kel.'.nama_kelompok', $tb_kel.'.ketua', 'desa.nama_desa', 'desa.kd_desa', 'desa.kode_desa', 'sebutan_desa.sebutan_desa')
                    ->join($tb_kel, $tb_kel.'.id', '=', $tb_pinkel.'.id_kel')
                    ->join('desa', $tb_kel.'.desa', '=', 'desa.kd_desa')
                    ->join('sebutan_desa', 'sebutan_desa.id', '=', 'desa.sebutan')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->where('tgl_transaksi', 'LIKE', '%'.$data['tahun'].'-'.$data['bulan'].'-%');
                    }], 'realisasi_pokok')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->where('tgl_transaksi', 'LIKE', '%'.$data['tahun'].'-'.$data['bulan'].'-%');
                    }], 'realisasi_jasa')
                    ->where($tb_pinkel.'.sistem_angsuran', '!=', '12')->where(function ($query) use ($data) {
                        $query->where([
                            [$data['tb_pinkel'].'.status', 'A'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ]);
                    })
                    ->orderBy($tb_kel.'.desa', 'ASC')
                    ->orderBy($tb_pinkel.'.tgl_cair', 'ASC');
            },
            'pinjaman_kelompok.saldo' => function ($query) use ($data) {
                $query->where('tgl_transaksi', '<=', $data['tgl_kondisi']);
            },
            'pinjaman_kelompok.target' => function ($query) use ($data) {
                $query->where('jatuh_tempo', '<=', $data['tgl_kondisi']);
            },
        ])->get();

        $data['lunas'] = PinjamanKelompok::where([
            ['tgl_lunas', '<', $thn.'-01-01'],
            ['status', 'L'],
            ['sistem_angsuran', '!=', '12'],
        ])->with('saldo', 'target')->get();

        $view = view('pelaporan.view.perkembangan_piutang.lpp_kelompok', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function pinjaman_per_kelompok_mingguan(array $data)
    {
        $data['lpp'] = 'Minggu';
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['tgl_lalu'] = $data['tahun'].'-'.$data['bulan'].'-01';
        $data['week_start'] = date('Y-m-d', strtotime('last Sunday', strtotime($tgl)));
        $data['week_end'] = date('Y-m-d', strtotime('next Saturday', strtotime($data['week_start'])));

        $minggu_awal = str_pad(date('d', strtotime($data['week_start'])), 2, '0', STR_PAD_LEFT);
        $minggu_akhir = str_pad(date('d', strtotime($data['week_end'])), 2, '0', STR_PAD_LEFT);

        $data['sub_judul'] = 'Tanggal '.$minggu_awal.' sampai '.$minggu_akhir.' Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);

        $data['jenis_pp'] = JenisProdukPinjaman::where('lokasi', '0')->with([
            'pinjaman_kelompok' => function ($query) use ($data) {
                $tb_pinkel = 'pinjaman_kelompok_'.$data['kec']->id;
                $tb_kel = 'kelompok_'.$data['kec']->id;
                $data['tb_pinkel'] = $tb_pinkel;

                $query->select($tb_pinkel.'.*', $tb_kel.'.nama_kelompok', $tb_kel.'.ketua', 'desa.nama_desa', 'desa.kd_desa', 'desa.kode_desa', 'sebutan_desa.sebutan_desa')
                    ->join($tb_kel, $tb_kel.'.id', '=', $tb_pinkel.'.id_kel')
                    ->join('desa', $tb_kel.'.desa', '=', 'desa.kd_desa')
                    ->join('sebutan_desa', 'sebutan_desa.id', '=', 'desa.sebutan')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->whereBetween('tgl_transaksi', [$data['week_start'], $data['week_end']]);
                    }], 'realisasi_pokok')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->whereBetween('tgl_transaksi', [$data['week_start'], $data['week_end']]);
                    }], 'realisasi_jasa')
                    ->where($tb_pinkel.'.sistem_angsuran', '=', '12')->where(function ($query) use ($data) {
                        $query->where([
                            [$data['tb_pinkel'].'.status', 'A'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['week_end']],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['week_end']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['week_end']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['week_end']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['week_end']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['week_end']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['week_end']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ]);
                    })
                    ->orderBy($tb_kel.'.desa', 'ASC')
                    ->orderBy($tb_pinkel.'.tgl_cair', 'ASC');
            },
            'pinjaman_kelompok.saldo' => function ($query) use ($data) {
                $query->where('tgl_transaksi', '<', $data['week_end']);
            },
            'pinjaman_kelompok.target' => function ($query) use ($data) {
                $query->where('jatuh_tempo', '<', $data['week_end']);
            },
        ])->get();

        $data['lunas'] = PinjamanKelompok::where([
            ['tgl_lunas', '<', $thn.'-01-01'],
            ['status', 'L'],
            ['sistem_angsuran', '12'],
        ])->with('saldo', 'target')->get();

        $view = view('pelaporan.view.perkembangan_piutang.lpp_kelompok', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function pinjaman_per_desa(array $data)
    {
        $data['lpp'] = 'Bulan';
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['jenis_pp'] = JenisProdukPinjaman::where('lokasi', '0')->with([
            'pinjaman_kelompok' => function ($query) use ($data) {
                $tb_pinkel = 'pinjaman_kelompok_'.$data['kec']->id;
                $tb_kel = 'kelompok_'.$data['kec']->id;
                $data['tb_pinkel'] = $tb_pinkel;

                $query->select($tb_pinkel.'.*', $tb_kel.'.nama_kelompok', 'desa.nama_desa', 'desa.kd_desa', 'desa.kode_desa', 'sebutan_desa.sebutan_desa')
                    ->join($tb_kel, $tb_kel.'.id', '=', $tb_pinkel.'.id_kel')
                    ->join('desa', $tb_kel.'.desa', '=', 'desa.kd_desa')
                    ->join('sebutan_desa', 'sebutan_desa.id', '=', 'desa.sebutan')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->where('tgl_transaksi', 'LIKE', '%'.$data['tahun'].'-'.$data['bulan'].'-%');
                    }], 'realisasi_pokok')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->where('tgl_transaksi', 'LIKE', '%'.$data['tahun'].'-'.$data['bulan'].'-%');
                    }], 'realisasi_jasa')
                    ->where($tb_pinkel.'.sistem_angsuran', '!=', '12')->where(function ($query) use ($data) {
                        $query->where([
                            [$data['tb_pinkel'].'.status', 'A'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ]);
                    })
                    ->orderBy($tb_kel.'.desa', 'ASC')
                    ->orderBy($tb_pinkel.'.tgl_cair', 'ASC');
            },
            'pinjaman_kelompok.saldo' => function ($query) use ($data) {
                $query->where('tgl_transaksi', '<=', $data['tgl_kondisi']);
            },
            'pinjaman_kelompok.target' => function ($query) use ($data) {
                $query->where('jatuh_tempo', '<=', $data['tgl_kondisi']);
            },
        ])->get();

        $data['lunas'] = PinjamanKelompok::where([
            ['tgl_lunas', '<', $thn.'-01-01'],
            ['status', 'L'],
            ['sistem_angsuran', '!=', '12'],
        ])->with('saldo', 'target')->get();

        $view = view('pelaporan.view.perkembangan_piutang.lpp_desa', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function pinjaman_per_desa_mingguan(array $data)
    {
        $data['lpp'] = 'Minggu';
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['week_start'] = date('Y-m-d', strtotime('last Sunday', strtotime($tgl)));
        $data['week_end'] = date('Y-m-d', strtotime('next Saturday', strtotime($data['week_start'])));

        $minggu_awal = str_pad(date('d', strtotime($data['week_start'])), 2, '0', STR_PAD_LEFT);
        $minggu_akhir = str_pad(date('d', strtotime($data['week_end'])), 2, '0', STR_PAD_LEFT);

        $data['sub_judul'] = 'Tanggal '.$minggu_awal.' sampai '.$minggu_akhir.' Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);

        $data['jenis_pp'] = JenisProdukPinjaman::where('lokasi', '0')->with([
            'pinjaman_kelompok' => function ($query) use ($data) {
                $tb_pinkel = 'pinjaman_kelompok_'.$data['kec']->id;
                $tb_kel = 'kelompok_'.$data['kec']->id;
                $data['tb_pinkel'] = $tb_pinkel;

                $query->select($tb_pinkel.'.*', $tb_kel.'.nama_kelompok', 'desa.nama_desa', 'desa.kd_desa', 'desa.kode_desa', 'sebutan_desa.sebutan_desa')
                    ->join($tb_kel, $tb_kel.'.id', '=', $tb_pinkel.'.id_kel')
                    ->join('desa', $tb_kel.'.desa', '=', 'desa.kd_desa')
                    ->join('sebutan_desa', 'sebutan_desa.id', '=', 'desa.sebutan')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->whereBetween('tgl_transaksi', [$data['week_start'], $data['week_end']]);
                    }], 'realisasi_pokok')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->whereBetween('tgl_transaksi', [$data['week_start'], $data['week_end']]);
                    }], 'realisasi_jasa')
                    ->where($tb_pinkel.'.sistem_angsuran', '=', '12')->where(function ($query) use ($data) {
                        $query->where([
                            [$data['tb_pinkel'].'.status', 'A'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['week_end']],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['week_end']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['week_end']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['week_end']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['week_end']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['week_end']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['week_end']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ]);
                    })
                    ->orderBy($tb_kel.'.desa', 'ASC')
                    ->orderBy($tb_pinkel.'.tgl_cair', 'ASC');
            },
            'pinjaman_kelompok.saldo' => function ($query) use ($data) {
                $query->where('tgl_transaksi', '<=', $data['week_end']);
            },
            'pinjaman_kelompok.target' => function ($query) use ($data) {
                $query->where('jatuh_tempo', '<=', $data['week_end']);
            },
        ])->get();

        $data['lunas'] = PinjamanKelompok::where([
            ['tgl_lunas', '<', $thn.'-01-01'],
            ['status', 'L'],
            ['sistem_angsuran', '=', '12'],
        ])->with('saldo', 'target')->get();

        $view = view('pelaporan.view.perkembangan_piutang.lpp_desa', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function kolek_per_kelompok(array $data)
    {
        $data['lpp'] = 'Bulan';
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['jenis_pp'] = JenisProdukPinjaman::where('lokasi', '0')->with([
            'pinjaman_kelompok' => function ($query) use ($data) {
                $tb_pinkel = 'pinjaman_kelompok_'.$data['kec']->id;
                $tb_kel = 'kelompok_'.$data['kec']->id;
                $data['tb_pinkel'] = $tb_pinkel;

                $query->select($tb_pinkel.'.*', $tb_kel.'.nama_kelompok', 'desa.nama_desa', 'desa.kd_desa', 'desa.kode_desa', 'sebutan_desa.sebutan_desa')
                    ->join($tb_kel, $tb_kel.'.id', '=', $tb_pinkel.'.id_kel')
                    ->join('desa', $tb_kel.'.desa', '=', 'desa.kd_desa')
                    ->join('sebutan_desa', 'sebutan_desa.id', '=', 'desa.sebutan')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->where('tgl_transaksi', 'LIKE', '%'.$data['tahun'].'-'.$data['bulan'].'-%');
                    }], 'realisasi_pokok')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->where('tgl_transaksi', 'LIKE', '%'.$data['tahun'].'-'.$data['bulan'].'-%');
                    }], 'realisasi_jasa')
                    ->where($tb_pinkel.'.sistem_angsuran', '!=', '12')->where(function ($query) use ($data) {
                        $query->where([
                            [$data['tb_pinkel'].'.status', 'A'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ]);
                    })
                    ->orderBy($tb_kel.'.desa', 'ASC')
                    ->orderBy($tb_pinkel.'.tgl_cair', 'ASC');
            },
            'pinjaman_kelompok.saldo' => function ($query) use ($data) {
                $query->where('tgl_transaksi', '<=', $data['tgl_kondisi']);
            },
            'pinjaman_kelompok.target' => function ($query) use ($data) {
                $query->where('jatuh_tempo', '<=', $data['tgl_kondisi']);
            },
        ])->get();

        $view = view('pelaporan.view.perkembangan_piutang.kolek_kelompok', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = Session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function kolek_per_kelompok_v2(array $data)
    {
        $data['lpp'] = 'Bulan';
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['jenis_pp'] = JenisProdukPinjaman::where('lokasi', '0')->with([
            'pinjaman_kelompok' => function ($query) use ($data) {
                $tb_pinkel = 'pinjaman_kelompok_'.$data['kec']->id;
                $tb_kel = 'kelompok_'.$data['kec']->id;
                $data['tb_pinkel'] = $tb_pinkel;

                $query->select($tb_pinkel.'.*', $tb_kel.'.nama_kelompok', 'desa.nama_desa', 'desa.kd_desa', 'desa.kode_desa', 'sebutan_desa.sebutan_desa')
                    ->join($tb_kel, $tb_kel.'.id', '=', $tb_pinkel.'.id_kel')
                    ->join('desa', $tb_kel.'.desa', '=', 'desa.kd_desa')
                    ->join('sebutan_desa', 'sebutan_desa.id', '=', 'desa.sebutan')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->where('tgl_transaksi', 'LIKE', '%'.$data['tahun'].'-'.$data['bulan'].'-%');
                    }], 'realisasi_pokok')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->where('tgl_transaksi', 'LIKE', '%'.$data['tahun'].'-'.$data['bulan'].'-%');
                    }], 'realisasi_jasa')
                    ->where($tb_pinkel.'.sistem_angsuran', '!=', '12')->where(function ($query) use ($data) {
                        $query->where([
                            [$data['tb_pinkel'].'.status', 'A'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ]);
                    })
                    ->orderBy($tb_kel.'.desa', 'ASC')
                    ->orderBy($tb_pinkel.'.tgl_cair', 'ASC');
            },
            'pinjaman_kelompok.saldo' => function ($query) use ($data) {
                $query->where('tgl_transaksi', '<=', $data['tgl_kondisi']);
            },
            'pinjaman_kelompok.target' => function ($query) use ($data) {
                $query->where('jatuh_tempo', '<=', $data['tgl_kondisi']);
            },
        ])->get();

        $view = view('pelaporan.view.perkembangan_piutang.kolek_kelompok_v2', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function kolek_per_kelompok_mingguan(array $data)
    {
        $data['lpp'] = 'Minggu';
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['week_start'] = date('Y-m-d', strtotime('last Sunday', strtotime($tgl)));
        $data['week_end'] = date('Y-m-d', strtotime('next Saturday', strtotime($data['week_start'])));

        $minggu_awal = str_pad(date('d', strtotime($data['week_start'])), 2, '0', STR_PAD_LEFT);
        $minggu_akhir = str_pad(date('d', strtotime($data['week_end'])), 2, '0', STR_PAD_LEFT);

        $data['sub_judul'] = 'Tanggal '.$minggu_awal.' sampai '.$minggu_akhir.' Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        $data['tgl_kondisi'] = $data['week_end'];

        $data['jenis_pp'] = JenisProdukPinjaman::where('lokasi', '0')->with([
            'pinjaman_kelompok' => function ($query) use ($data) {
                $tb_pinkel = 'pinjaman_kelompok_'.$data['kec']->id;
                $tb_kel = 'kelompok_'.$data['kec']->id;
                $data['tb_pinkel'] = $tb_pinkel;

                $query->select($tb_pinkel.'.*', $tb_kel.'.nama_kelompok', 'desa.nama_desa', 'desa.kd_desa', 'desa.kode_desa', 'sebutan_desa.sebutan_desa')
                    ->join($tb_kel, $tb_kel.'.id', '=', $tb_pinkel.'.id_kel')
                    ->join('desa', $tb_kel.'.desa', '=', 'desa.kd_desa')
                    ->join('sebutan_desa', 'sebutan_desa.id', '=', 'desa.sebutan')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->whereBetween('tgl_transaksi', [$data['week_start'], $data['week_end']]);
                    }], 'realisasi_pokok')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->whereBetween('tgl_transaksi', [$data['week_start'], $data['week_end']]);
                    }], 'realisasi_jasa')
                    ->where($tb_pinkel.'.sistem_angsuran', '=', '12')->where(function ($query) use ($data) {
                        $query->where([
                            [$data['tb_pinkel'].'.status', 'A'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ]);
                    })
                    ->orderBy($tb_kel.'.desa', 'ASC')
                    ->orderBy($tb_pinkel.'.tgl_cair', 'ASC');
            },
            'pinjaman_kelompok.saldo' => function ($query) use ($data) {
                $query->where('tgl_transaksi', '<=', $data['tgl_kondisi']);
            },
            'pinjaman_kelompok.target' => function ($query) use ($data) {
                $query->where('jatuh_tempo', '<=', $data['tgl_kondisi']);
            },
        ])->get();

        $view = view('pelaporan.view.perkembangan_piutang.kolek_kelompok', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function kolek_per_kelompok_v2_mingguan(array $data)
    {
        $data['lpp'] = 'Minggu';
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['week_start'] = date('Y-m-d', strtotime('last Sunday', strtotime($tgl)));
        $data['week_end'] = date('Y-m-d', strtotime('next Saturday', strtotime($data['week_start'])));

        $minggu_awal = str_pad(date('d', strtotime($data['week_start'])), 2, '0', STR_PAD_LEFT);
        $minggu_akhir = str_pad(date('d', strtotime($data['week_end'])), 2, '0', STR_PAD_LEFT);

        $data['sub_judul'] = 'Tanggal '.$minggu_awal.' sampai '.$minggu_akhir.' Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        $data['tgl_kondisi'] = $data['week_end'];

        $data['jenis_pp'] = JenisProdukPinjaman::where('lokasi', '0')->with([
            'pinjaman_kelompok' => function ($query) use ($data) {
                $tb_pinkel = 'pinjaman_kelompok_'.$data['kec']->id;
                $tb_kel = 'kelompok_'.$data['kec']->id;
                $data['tb_pinkel'] = $tb_pinkel;

                $query->select($tb_pinkel.'.*', $tb_kel.'.nama_kelompok', 'desa.nama_desa', 'desa.kd_desa', 'desa.kode_desa', 'sebutan_desa.sebutan_desa')
                    ->join($tb_kel, $tb_kel.'.id', '=', $tb_pinkel.'.id_kel')
                    ->join('desa', $tb_kel.'.desa', '=', 'desa.kd_desa')
                    ->join('sebutan_desa', 'sebutan_desa.id', '=', 'desa.sebutan')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->whereBetween('tgl_transaksi', [$data['week_start'], $data['week_end']]);
                    }], 'realisasi_pokok')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->whereBetween('tgl_transaksi', [$data['week_start'], $data['week_end']]);
                    }], 'realisasi_jasa')
                    ->where($tb_pinkel.'.sistem_angsuran', '=', '12')->where(function ($query) use ($data) {
                        $query->where([
                            [$data['tb_pinkel'].'.status', 'A'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ]);
                    })
                    ->orderBy($tb_kel.'.desa', 'ASC')
                    ->orderBy($tb_pinkel.'.tgl_cair', 'ASC');
            },
            'pinjaman_kelompok.saldo' => function ($query) use ($data) {
                $query->where('tgl_transaksi', '<=', $data['tgl_kondisi']);
            },
            'pinjaman_kelompok.target' => function ($query) use ($data) {
                $query->where('jatuh_tempo', '<=', $data['tgl_kondisi']);
            },
        ])->get();

        $view = view('pelaporan.view.perkembangan_piutang.kolek_kelompok_v2', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function kolek_per_desa(array $data)
    {
        $data['lpp'] = 'Bulan';
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['jenis_pp'] = JenisProdukPinjaman::where('lokasi', '0')->with([
            'pinjaman_kelompok' => function ($query) use ($data) {
                $tb_pinkel = 'pinjaman_kelompok_'.$data['kec']->id;
                $tb_kel = 'kelompok_'.$data['kec']->id;
                $data['tb_pinkel'] = $tb_pinkel;

                $query->select($tb_pinkel.'.*', $tb_kel.'.nama_kelompok', 'desa.nama_desa', 'desa.kd_desa', 'desa.kode_desa', 'sebutan_desa.sebutan_desa')
                    ->join($tb_kel, $tb_kel.'.id', '=', $tb_pinkel.'.id_kel')
                    ->join('desa', $tb_kel.'.desa', '=', 'desa.kd_desa')
                    ->join('sebutan_desa', 'sebutan_desa.id', '=', 'desa.sebutan')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->where('tgl_transaksi', 'LIKE', '%'.$data['tahun'].'-'.$data['bulan'].'-%');
                    }], 'realisasi_pokok')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->where('tgl_transaksi', 'LIKE', '%'.$data['tahun'].'-'.$data['bulan'].'-%');
                    }], 'realisasi_jasa')
                    ->where($tb_pinkel.'.sistem_angsuran', '!=', '12')->where(function ($query) use ($data) {
                        $query->where([
                            [$data['tb_pinkel'].'.status', 'A'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ]);
                    })
                    ->orderBy($tb_kel.'.desa', 'ASC')
                    ->orderBy($tb_pinkel.'.tgl_cair', 'ASC');
            },
            'pinjaman_kelompok.saldo' => function ($query) use ($data) {
                $query->where('tgl_transaksi', '<=', $data['tgl_kondisi']);
            },
            'pinjaman_kelompok.target' => function ($query) use ($data) {
                $query->where('jatuh_tempo', '<=', $data['tgl_kondisi']);
            },
        ])->get();

        $view = view('pelaporan.view.perkembangan_piutang.kolek_desa', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function kolek_per_desa_v2(array $data)
    {
        $data['lpp'] = 'Bulan';
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['jenis_pp'] = JenisProdukPinjaman::where('lokasi', '0')->with([
            'pinjaman_kelompok' => function ($query) use ($data) {
                $tb_pinkel = 'pinjaman_kelompok_'.$data['kec']->id;
                $tb_kel = 'kelompok_'.$data['kec']->id;
                $data['tb_pinkel'] = $tb_pinkel;

                $query->select($tb_pinkel.'.*', $tb_kel.'.nama_kelompok', 'desa.nama_desa', 'desa.kd_desa', 'desa.kode_desa', 'sebutan_desa.sebutan_desa')
                    ->join($tb_kel, $tb_kel.'.id', '=', $tb_pinkel.'.id_kel')
                    ->join('desa', $tb_kel.'.desa', '=', 'desa.kd_desa')
                    ->join('sebutan_desa', 'sebutan_desa.id', '=', 'desa.sebutan')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->where('tgl_transaksi', 'LIKE', '%'.$data['tahun'].'-'.$data['bulan'].'-%');
                    }], 'realisasi_pokok')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->where('tgl_transaksi', 'LIKE', '%'.$data['tahun'].'-'.$data['bulan'].'-%');
                    }], 'realisasi_jasa')
                    ->where($tb_pinkel.'.sistem_angsuran', '!=', '12')->where(function ($query) use ($data) {
                        $query->where([
                            [$data['tb_pinkel'].'.status', 'A'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ]);
                    })
                    ->orderBy($tb_kel.'.desa', 'ASC')
                    ->orderBy($tb_pinkel.'.tgl_cair', 'ASC');
            },
            'pinjaman_kelompok.saldo' => function ($query) use ($data) {
                $query->where('tgl_transaksi', '<=', $data['tgl_kondisi']);
            },
            'pinjaman_kelompok.target' => function ($query) use ($data) {
                $query->where('jatuh_tempo', '<=', $data['tgl_kondisi']);
            },
        ])->get();

        $view = view('pelaporan.view.perkembangan_piutang.kolek_desa_v2', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function kolek_per_desa_mingguan(array $data)
    {
        $data['lpp'] = 'Minggu';
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['week_start'] = date('Y-m-d', strtotime('last Sunday', strtotime($tgl)));
        $data['week_end'] = date('Y-m-d', strtotime('next Saturday', strtotime($data['week_start'])));

        $minggu_awal = str_pad(date('d', strtotime($data['week_start'])), 2, '0', STR_PAD_LEFT);
        $minggu_akhir = str_pad(date('d', strtotime($data['week_end'])), 2, '0', STR_PAD_LEFT);

        $data['sub_judul'] = 'Tanggal '.$minggu_awal.' sampai '.$minggu_akhir.' Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        $data['tgl_kondisi'] = $data['week_end'];

        $data['jenis_pp'] = JenisProdukPinjaman::where('lokasi', '0')->with([
            'pinjaman_kelompok' => function ($query) use ($data) {
                $tb_pinkel = 'pinjaman_kelompok_'.$data['kec']->id;
                $tb_kel = 'kelompok_'.$data['kec']->id;
                $data['tb_pinkel'] = $tb_pinkel;

                $query->select($tb_pinkel.'.*', $tb_kel.'.nama_kelompok', 'desa.nama_desa', 'desa.kd_desa', 'desa.kode_desa', 'sebutan_desa.sebutan_desa')
                    ->join($tb_kel, $tb_kel.'.id', '=', $tb_pinkel.'.id_kel')
                    ->join('desa', $tb_kel.'.desa', '=', 'desa.kd_desa')
                    ->join('sebutan_desa', 'sebutan_desa.id', '=', 'desa.sebutan')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->where('tgl_transaksi', 'LIKE', '%'.$data['tahun'].'-'.$data['bulan'].'-%');
                    }], 'realisasi_pokok')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->where('tgl_transaksi', 'LIKE', '%'.$data['tahun'].'-'.$data['bulan'].'-%');
                    }], 'realisasi_jasa')
                    ->where($tb_pinkel.'.sistem_angsuran', '=', '12')->where(function ($query) use ($data) {
                        $query->where([
                            [$data['tb_pinkel'].'.status', 'A'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ]);
                    })
                    ->orderBy($tb_kel.'.desa', 'ASC')
                    ->orderBy($tb_pinkel.'.tgl_cair', 'ASC');
            },
            'pinjaman_kelompok.saldo' => function ($query) use ($data) {
                $query->where('tgl_transaksi', '<=', $data['tgl_kondisi']);
            },
            'pinjaman_kelompok.target' => function ($query) use ($data) {
                $query->where('jatuh_tempo', '<=', $data['tgl_kondisi']);
            },
        ])->get();

        $view = view('pelaporan.view.perkembangan_piutang.kolek_desa', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function kolek_per_desa_v2_mingguan(array $data)
    {
        $data['lpp'] = 'Minggu';
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['week_start'] = date('Y-m-d', strtotime('last Sunday', strtotime($tgl)));
        $data['week_end'] = date('Y-m-d', strtotime('next Saturday', strtotime($data['week_start'])));

        $minggu_awal = str_pad(date('d', strtotime($data['week_start'])), 2, '0', STR_PAD_LEFT);
        $minggu_akhir = str_pad(date('d', strtotime($data['week_end'])), 2, '0', STR_PAD_LEFT);

        $data['sub_judul'] = 'Tanggal '.$minggu_awal.' sampai '.$minggu_akhir.' Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        $data['tgl_kondisi'] = $data['week_end'];

        $data['jenis_pp'] = JenisProdukPinjaman::where('lokasi', '0')->with([
            'pinjaman_kelompok' => function ($query) use ($data) {
                $tb_pinkel = 'pinjaman_kelompok_'.$data['kec']->id;
                $tb_kel = 'kelompok_'.$data['kec']->id;
                $data['tb_pinkel'] = $tb_pinkel;

                $query->select($tb_pinkel.'.*', $tb_kel.'.nama_kelompok', 'desa.nama_desa', 'desa.kd_desa', 'desa.kode_desa', 'sebutan_desa.sebutan_desa')
                    ->join($tb_kel, $tb_kel.'.id', '=', $tb_pinkel.'.id_kel')
                    ->join('desa', $tb_kel.'.desa', '=', 'desa.kd_desa')
                    ->join('sebutan_desa', 'sebutan_desa.id', '=', 'desa.sebutan')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->where('tgl_transaksi', 'LIKE', '%'.$data['tahun'].'-'.$data['bulan'].'-%');
                    }], 'realisasi_pokok')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->where('tgl_transaksi', 'LIKE', '%'.$data['tahun'].'-'.$data['bulan'].'-%');
                    }], 'realisasi_jasa')
                    ->where($tb_pinkel.'.sistem_angsuran', '=', '12')->where(function ($query) use ($data) {
                        $query->where([
                            [$data['tb_pinkel'].'.status', 'A'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ]);
                    })
                    ->orderBy($tb_kel.'.desa', 'ASC')
                    ->orderBy($tb_pinkel.'.tgl_cair', 'ASC');
            },
            'pinjaman_kelompok.saldo' => function ($query) use ($data) {
                $query->where('tgl_transaksi', '<=', $data['tgl_kondisi']);
            },
            'pinjaman_kelompok.target' => function ($query) use ($data) {
                $query->where('jatuh_tempo', '<=', $data['tgl_kondisi']);
            },
        ])->get();

        $view = view('pelaporan.view.perkembangan_piutang.kolek_desa_v2', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function cadangan_penghapusan(array $data)
    {
        $data['lpp'] = 'Bulan';
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['jenis_pp'] = JenisProdukPinjaman::where('lokasi', '0')->with([
            'pinjaman_kelompok' => function ($query) use ($data) {
                $tb_pinkel = 'pinjaman_kelompok_'.$data['kec']->id;
                $tb_kel = 'kelompok_'.$data['kec']->id;
                $data['tb_pinkel'] = $tb_pinkel;

                $query->select($tb_pinkel.'.*', $tb_kel.'.nama_kelompok', 'desa.nama_desa', 'desa.kd_desa', 'desa.kode_desa', 'sebutan_desa.sebutan_desa')
                    ->join($tb_kel, $tb_kel.'.id', '=', $tb_pinkel.'.id_kel')
                    ->join('desa', $tb_kel.'.desa', '=', 'desa.kd_desa')
                    ->join('sebutan_desa', 'sebutan_desa.id', '=', 'desa.sebutan')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->where('tgl_transaksi', 'LIKE', '%'.$data['tahun'].'-'.$data['bulan'].'-%');
                    }], 'realisasi_pokok')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->where('tgl_transaksi', 'LIKE', '%'.$data['tahun'].'-'.$data['bulan'].'-%');
                    }], 'realisasi_jasa')
                    ->where($tb_pinkel.'.sistem_angsuran', '!=', '12')->where(function ($query) use ($data) {
                        $query->where([
                            [$data['tb_pinkel'].'.status', 'A'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ]);
                    })
                    ->orderBy($tb_kel.'.desa', 'ASC')
                    ->orderBy($tb_pinkel.'.tgl_cair', 'ASC');
            },
            'pinjaman_kelompok.saldo' => function ($query) use ($data) {
                $query->where('tgl_transaksi', '<=', $data['tgl_kondisi']);
            },
            'pinjaman_kelompok.target' => function ($query) use ($data) {
                $query->where('jatuh_tempo', '<=', $data['tgl_kondisi']);
            },
        ])->get();

        $view = view('pelaporan.view.perkembangan_piutang.cadangan_penghapusan', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function cadangan_penghapusan_v2(array $data)
    {
        $data['lpp'] = 'Bulan';
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['jenis_pp'] = JenisProdukPinjaman::where('lokasi', '0')->with([
            'pinjaman_kelompok' => function ($query) use ($data) {
                $tb_pinkel = 'pinjaman_kelompok_'.$data['kec']->id;
                $tb_kel = 'kelompok_'.$data['kec']->id;
                $data['tb_pinkel'] = $tb_pinkel;

                $query->select($tb_pinkel.'.*', $tb_kel.'.nama_kelompok', 'desa.nama_desa', 'desa.kd_desa', 'desa.kode_desa', 'sebutan_desa.sebutan_desa')
                    ->join($tb_kel, $tb_kel.'.id', '=', $tb_pinkel.'.id_kel')
                    ->join('desa', $tb_kel.'.desa', '=', 'desa.kd_desa')
                    ->join('sebutan_desa', 'sebutan_desa.id', '=', 'desa.sebutan')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->where('tgl_transaksi', 'LIKE', '%'.$data['tahun'].'-'.$data['bulan'].'-%');
                    }], 'realisasi_pokok')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->where('tgl_transaksi', 'LIKE', '%'.$data['tahun'].'-'.$data['bulan'].'-%');
                    }], 'realisasi_jasa')
                    ->where($tb_pinkel.'.sistem_angsuran', '!=', '12')->where(function ($query) use ($data) {
                        $query->where([
                            [$data['tb_pinkel'].'.status', 'A'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ]);
                    })
                    ->orderBy($tb_kel.'.desa', 'ASC')
                    ->orderBy($tb_pinkel.'.tgl_cair', 'ASC');
            },
            'pinjaman_kelompok.saldo' => function ($query) use ($data) {
                $query->where('tgl_transaksi', '<=', $data['tgl_kondisi']);
            },
            'pinjaman_kelompok.target' => function ($query) use ($data) {
                $query->where('jatuh_tempo', '<=', $data['tgl_kondisi']);
            },
        ])->get();

        $view = view('pelaporan.view.perkembangan_piutang.cadangan_penghapusan_v2', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function cadangan_penghapusan_mingguan(array $data)
    {
        $data['lpp'] = 'Minggu';
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['week_start'] = date('Y-m-d', strtotime('last Sunday', strtotime($tgl)));
        $data['week_end'] = date('Y-m-d', strtotime('next Saturday', strtotime($data['week_start'])));

        $minggu_awal = str_pad(date('d', strtotime($data['week_start'])), 2, '0', STR_PAD_LEFT);
        $minggu_akhir = str_pad(date('d', strtotime($data['week_end'])), 2, '0', STR_PAD_LEFT);

        $data['sub_judul'] = 'Tanggal '.$minggu_awal.' sampai '.$minggu_akhir.' Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        $data['tgl_kondisi'] = $data['week_end'];

        $data['jenis_pp'] = JenisProdukPinjaman::where('lokasi', '0')->with([
            'pinjaman_kelompok' => function ($query) use ($data) {
                $tb_pinkel = 'pinjaman_kelompok_'.$data['kec']->id;
                $tb_kel = 'kelompok_'.$data['kec']->id;
                $data['tb_pinkel'] = $tb_pinkel;

                $query->select($tb_pinkel.'.*', $tb_kel.'.nama_kelompok', 'desa.nama_desa', 'desa.kd_desa', 'desa.kode_desa', 'sebutan_desa.sebutan_desa')
                    ->join($tb_kel, $tb_kel.'.id', '=', $tb_pinkel.'.id_kel')
                    ->join('desa', $tb_kel.'.desa', '=', 'desa.kd_desa')
                    ->join('sebutan_desa', 'sebutan_desa.id', '=', 'desa.sebutan')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->where('tgl_transaksi', 'LIKE', '%'.$data['tahun'].'-'.$data['bulan'].'-%');
                    }], 'realisasi_pokok')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->where('tgl_transaksi', 'LIKE', '%'.$data['tahun'].'-'.$data['bulan'].'-%');
                    }], 'realisasi_jasa')
                    ->where($tb_pinkel.'.sistem_angsuran', '=', '12')->where(function ($query) use ($data) {
                        $query->where([
                            [$data['tb_pinkel'].'.status', 'A'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ]);
                    })
                    ->orderBy($tb_kel.'.desa', 'ASC')
                    ->orderBy($tb_pinkel.'.tgl_cair', 'ASC');
            },
            'pinjaman_kelompok.saldo' => function ($query) use ($data) {
                $query->where('tgl_transaksi', '<=', $data['tgl_kondisi']);
            },
            'pinjaman_kelompok.target' => function ($query) use ($data) {
                $query->where('jatuh_tempo', '<=', $data['tgl_kondisi']);
            },
        ])->get();

        $view = view('pelaporan.view.perkembangan_piutang.cadangan_penghapusan', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function cadangan_penghapusan_v2_mingguan(array $data)
    {
        $data['lpp'] = 'Minggu';
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['week_start'] = date('Y-m-d', strtotime('last Sunday', strtotime($tgl)));
        $data['week_end'] = date('Y-m-d', strtotime('next Saturday', strtotime($data['week_start'])));

        $minggu_awal = str_pad(date('d', strtotime($data['week_start'])), 2, '0', STR_PAD_LEFT);
        $minggu_akhir = str_pad(date('d', strtotime($data['week_end'])), 2, '0', STR_PAD_LEFT);

        $data['sub_judul'] = 'Tanggal '.$minggu_awal.' sampai '.$minggu_akhir.' Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        $data['tgl_kondisi'] = $data['week_end'];

        $data['jenis_pp'] = JenisProdukPinjaman::where('lokasi', '0')->with([
            'pinjaman_kelompok' => function ($query) use ($data) {
                $tb_pinkel = 'pinjaman_kelompok_'.$data['kec']->id;
                $tb_kel = 'kelompok_'.$data['kec']->id;
                $data['tb_pinkel'] = $tb_pinkel;

                $query->select($tb_pinkel.'.*', $tb_kel.'.nama_kelompok', 'desa.nama_desa', 'desa.kd_desa', 'desa.kode_desa', 'sebutan_desa.sebutan_desa')
                    ->join($tb_kel, $tb_kel.'.id', '=', $tb_pinkel.'.id_kel')
                    ->join('desa', $tb_kel.'.desa', '=', 'desa.kd_desa')
                    ->join('sebutan_desa', 'sebutan_desa.id', '=', 'desa.sebutan')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->where('tgl_transaksi', 'LIKE', '%'.$data['tahun'].'-'.$data['bulan'].'-%');
                    }], 'realisasi_pokok')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->where('tgl_transaksi', 'LIKE', '%'.$data['tahun'].'-'.$data['bulan'].'-%');
                    }], 'realisasi_jasa')
                    ->where($tb_pinkel.'.sistem_angsuran', '=', '12')->where(function ($query) use ($data) {
                        $query->where([
                            [$data['tb_pinkel'].'.status', 'A'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'L'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'R'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ])->orwhere([
                            [$data['tb_pinkel'].'.status', 'H'],
                            [$data['tb_pinkel'].'.tgl_lunas', '<=', $data['tgl_kondisi']],
                            [$data['tb_pinkel'].'.tgl_lunas', '>=', "$data[tahun]-01-01"],
                        ]);
                    })
                    ->orderBy($tb_kel.'.desa', 'ASC')
                    ->orderBy($tb_pinkel.'.tgl_cair', 'ASC');
            },
            'pinjaman_kelompok.saldo' => function ($query) use ($data) {
                $query->where('tgl_transaksi', '<=', $data['tgl_kondisi']);
            },
            'pinjaman_kelompok.target' => function ($query) use ($data) {
                $query->where('jatuh_tempo', '<=', $data['tgl_kondisi']);
            },
        ])->get();

        $view = view('pelaporan.view.perkembangan_piutang.cadangan_penghapusan_v2', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function pinjaman_hapus(array $data)
    {
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $kecId = $data['kec']->id;
        $tb_transaksi = "transaksi_{$kecId}";
        $tb_pinkel = "pinjaman_kelompok_{$kecId}";
        $tb_kel = "kelompok_{$kecId}";

        $data['jenis_pp'] = JenisProdukPinjaman::where('lokasi', '0')
            ->with([
                'pinjaman_kelompok' => function ($query) use ($data, $tb_transaksi, $tb_pinkel, $tb_kel) {
                    $query->select([
                        "{$tb_pinkel}.*",
                        "{$tb_kel}.nama_kelompok",
                        "{$tb_kel}.ketua",
                        'desa.nama_desa',
                        'desa.kd_desa',
                        'desa.kode_desa',
                        'sebutan_desa.sebutan_desa',
                        't.tgl_hapus',
                        't.jumlah',
                    ])
                        ->join($tb_kel, "{$tb_kel}.id", '=', "{$tb_pinkel}.id_kel")
                        ->join('desa', "{$tb_kel}.desa", '=', 'desa.kd_desa')
                        ->join('sebutan_desa', 'sebutan_desa.id', '=', 'desa.sebutan')
                        ->leftJoinSub(
                            DB::table($tb_transaksi.' as sub')
                                ->selectRaw('id_pinj, jumlah, tgl_transaksi as tgl_hapus')
                                ->where('tgl_transaksi', '<=', $data['tgl_kondisi'])
                                ->where('rekening_debit', 'LIKE', '1.1.04%')
                                ->where('rekening_kredit', 'LIKE', '1.1.03%')
                                ->whereRaw('tgl_transaksi = (SELECT MAX(tgl_transaksi) FROM '.$tb_transaksi.' WHERE id_pinj = sub.id_pinj)')
                                ->groupBy('id_pinj', 'jumlah', 'tgl_transaksi'),
                            't',
                            "{$tb_pinkel}.id",
                            '=',
                            't.id_pinj'
                        )
                        ->where("{$tb_pinkel}.sistem_angsuran", '!=', '12')
                        ->where("{$tb_pinkel}.status", 'H')
                        ->where('t.tgl_hapus', '<=', $data['tgl_kondisi'])
                        ->orderBy("{$tb_kel}.desa")
                        ->orderBy("{$tb_pinkel}.tgl_proposal");
                },
            ])
            ->get();

        $view = view('pelaporan.view.perkembangan_piutang.pinjaman_hapus', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function pinjaman_anggota_hapus(array $data)
    {
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $kecId = $data['kec']->id;
        $tb_transaksi = "transaksi_{$kecId}";
        $tb_pinj = "pinjaman_anggota_{$kecId}";
        $tb_angg = "anggota_{$kecId}";
        $tb_kel = "kelompok_{$kecId}";

        $data['jenis_pp'] = JenisProdukPinjaman::where('lokasi', '0')->with([
            'pinjaman_anggota' => function ($query) use ($data, $tb_transaksi, $tb_pinj, $tb_angg, $tb_kel) {
                $query->select(
                    "{$tb_pinj}.*",
                    "{$tb_angg}.namadepan",
                    "{$tb_angg}.alamat",
                    "{$tb_angg}.nik",
                    "{$tb_angg}.kk",
                    "{$tb_kel}.nama_kelompok",
                    'desa.nama_desa',
                    'desa.kd_desa',
                    'desa.kode_desa',
                    'sebutan_desa.sebutan_desa',
                    't.tgl_hapus',
                    't.jumlah'
                )
                    ->join($tb_angg, "{$tb_angg}.id", '=', "{$tb_pinj}.nia")
                    ->join($tb_kel, "{$tb_kel}.id", '=', "{$tb_pinj}.id_kel")
                    ->join('desa', "{$tb_angg}.desa", '=', 'desa.kd_desa')
                    ->join('sebutan_desa', 'sebutan_desa.id', '=', 'desa.sebutan')
                    ->leftJoinSub(
                        DB::table("{$tb_transaksi} as sub")
                            ->selectRaw('id_pinj_i, jumlah, tgl_transaksi as tgl_hapus')
                            ->where('tgl_transaksi', '<=', $data['tgl_kondisi'])
                            ->where('rekening_debit', 'LIKE', '1.1.04%')
                            ->where('rekening_kredit', 'LIKE', '1.1.03%')
                            ->whereRaw('tgl_transaksi = (SELECT MAX(tgl_transaksi) FROM '.$tb_transaksi.' WHERE id_pinj_i = sub.id_pinj_i)')
                            ->groupBy('id_pinj_i', 'jumlah', 'tgl_transaksi'),
                        't',
                        "{$tb_pinj}.id",
                        '=',
                        't.id_pinj_i'
                    )
                    ->where("{$tb_pinj}.sistem_angsuran", '!=', '12')
                    ->where("{$tb_pinj}.status", 'H')
                    ->orderBy("{$tb_angg}.desa")
                    ->orderBy("{$tb_pinj}.id_pinkel")
                    ->orderBy("{$tb_pinj}.id")
                    ->orderBy("{$tb_pinj}.tgl_cair");
            },
        ])->get();

        $view = view('pelaporan.view.perkembangan_piutang.pinjaman_anggota_hapus', $data)->render();
        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function rencana_realisasi(array $data)
    {
        $keuangan = new Keuangan;
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        $data['tgl_cair'] = $thn.'-';
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl_cair'] = $thn.'-'.$bln.'-';
        }

        if ($data['harian']) {
            $data['sub_judul'] = 'Tanggal '.$hari.' Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = $hari.' '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl_cair'] = $thn.'-'.$bln.'-'.$hari;
        }

        $data['jenis_pp'] = JenisProdukPinjaman::where('lokasi', '0')->with([
            'pinjaman_kelompok' => function ($query) use ($data) {
                $tb_pinkel = 'pinjaman_kelompok_'.$data['kec']->id;
                $tb_pinj = 'pinjaman_anggota_'.$data['kec']->id;
                $tb_kel = 'kelompok_'.$data['kec']->id;
                $data['tb_pinkel'] = $tb_pinkel;

                $query->select(
                    $tb_pinkel.'.*',
                    $tb_kel.'.nama_kelompok',
                    $tb_kel.'.ketua',
                    'desa.nama_desa',
                    'desa.kd_desa',
                    'desa.kode_desa',
                    'sebutan_desa.sebutan_desa',
                    DB::raw("(SELECT count(*) as jumlah FROM $tb_pinj WHERE $tb_pinj.id_pinkel=$tb_pinkel.id) as pinjaman_anggota_count")
                )
                    ->join($tb_kel, $tb_kel.'.id', '=', $tb_pinkel.'.id_kel')
                    ->join('desa', $tb_kel.'.desa', '=', 'desa.kd_desa')
                    ->join('sebutan_desa', 'sebutan_desa.id', '=', 'desa.sebutan')
                    ->where($tb_pinkel.'.sistem_angsuran', '!=', '12')->where(function ($query) use ($data) {
                        $query->where($data['tb_pinkel'].'.tgl_cair', 'LIKE', $data['tgl_cair'].'%')
                            ->where(function ($query) use ($data) {
                                $query->where($data['tb_pinkel'].'.status', 'A')
                                    ->orwhere($data['tb_pinkel'].'.status', 'L')
                                    ->orwhere($data['tb_pinkel'].'.status', 'H')
                                    ->orwhere($data['tb_pinkel'].'.status', 'R');
                            });
                    })
                    ->orderBy($tb_kel.'.desa', 'ASC')
                    ->orderBy($tb_pinkel.'.tgl_cair', 'ASC');
            },
            'pinjaman_kelompok.sis_pokok',
        ])->get();

        $data['keuangan'] = $keuangan;
        $view = view('pelaporan.view.perkembangan_piutang.rencana_realisasi', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function _rencana_realisasi(array $data)
    {
        $keuangan = new Keuangan;
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        if (strlen($hari) > 0 && strlen($bln) > 0) {
            $data['sub_judul'] = 'Tanggal '.Tanggal::tglLatin($tgl);
            $data['tgl'] = Tanggal::tglLatin($tgl);
        } elseif (strlen($bln) > 0) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        } else {
            $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::tahun($tgl);
        }

        $triwulan = [
            '01' => ['1', '2', '3'],
            '02' => ['1', '2', '3'],
            '03' => ['1', '2', '3'],
            '04' => ['4', '5', '6'],
            '05' => ['4', '5', '6'],
            '06' => ['4', '5', '6'],
            '07' => ['7', '8', '9'],
            '08' => ['7', '8', '9'],
            '09' => ['7', '8', '9'],
            '10' => ['10', '11', '12'],
            '11' => ['10', '11', '12'],
            '12' => ['10', '11', '12'],
        ];

        $bulan_tampil = $triwulan[$data['bulan']];
        $bulan1 = str_pad($bulan_tampil[0], 2, '0', STR_PAD_LEFT);
        $bulan3 = str_pad($bulan_tampil[2], 2, '0', STR_PAD_LEFT);

        $tgl_awal = $data['tahun'].'-'.$bulan1.'-01';
        $tgl_akhir = date('Y-m-t', strtotime($data['tahun'].'-'.$bulan3.'-01'));
        $data['tgl_akhir'] = $tgl_akhir;

        $data['jenis_pp'] = JenisProdukPinjaman::where('lokasi', '0')->with([
            'pinjaman_kelompok' => function ($query) use ($data) {
                $tb_pinkel = 'pinjaman_kelompok_'.$data['kec']->id;
                $tb_kel = 'kelompok_'.$data['kec']->id;
                $data['tb_pinkel'] = $tb_pinkel;

                $query->select(
                    $tb_pinkel.'.*',
                    $tb_kel.'.nama_kelompok',
                    $tb_kel.'.ketua',
                    'desa.nama_desa',
                    'desa.kd_desa',
                    'desa.kode_desa',
                    'sebutan_desa.sebutan_desa'
                )
                    ->join($tb_kel, $tb_kel.'.id', '=', $tb_pinkel.'.id_kel')
                    ->join('desa', $tb_kel.'.desa', '=', 'desa.kd_desa')
                    ->join('sebutan_desa', 'sebutan_desa.id', '=', 'desa.sebutan')
                    ->where($tb_pinkel.'.sistem_angsuran', '!=', '12')->where(function ($query) use ($data) {
                        $query->where([
                            [$data['tb_pinkel'].'.status', 'A'],
                            [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_akhir']],
                        ]);
                    })
                    ->orderBy($tb_kel.'.desa', 'ASC')
                    ->orderBy($tb_pinkel.'.id', 'ASC');
            },
            'pinjaman_kelompok.real' => function ($query) use ($tgl_awal, $tgl_akhir) {
                $query->whereBetween('tgl_transaksi', [$tgl_awal, $tgl_akhir]);
            },
            'pinjaman_kelompok.ra' => function ($query) use ($tgl_awal, $tgl_akhir) {
                $query->whereBetween('jatuh_tempo', [$tgl_awal, $tgl_akhir]);
            },
        ])->get();

        $data['keuangan'] = $keuangan;

        $view = view('pelaporan.view.perkembangan_piutang._rencana_realisasi', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function tagihan_hari_ini(array $data)
    {
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = $hari.' '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);

        $data['pinjaman'] = PinjamanKelompok::where('status', 'A')->whereDay('tgl_cair', date('d', strtotime($tgl)))->with([
            'target' => function ($query) use ($tgl) {
                $query->where([
                    ['jatuh_tempo', $tgl],
                    ['angsuran_ke', '!=', '0'],
                ]);
            },
            'saldo' => function ($query) use ($tgl) {
                $query->where('tgl_transaksi', '<=', $tgl);
            },
            'kelompok',
            'kelompok.d',
        ])->get();

        $view = view('pelaporan.view.perkembangan_piutang.jatuh_tempo', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function menunggak(array $data)
    {
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = $hari.' '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        if (! ($data['tahun_pinjaman_cair'] == '-' || $data['tahun_pinjaman_cair'] == '')) {
            $data['sub_judul'] = 'Pencairan Tahun '.$data['tahun_pinjaman_cair'];
        }

        $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);

        $data['jenis_pp'] = JenisProdukPinjaman::where('lokasi', '0')->with([
            'pinjaman_kelompok' => function ($query) use ($data) {
                $tb_pinkel = 'pinjaman_kelompok_'.$data['kec']->id;
                $tb_kel = 'kelompok_'.$data['kec']->id;
                $data['tb_pinkel'] = $tb_pinkel;

                $query->select($tb_pinkel.'.*', $tb_kel.'.nama_kelompok', $tb_kel.'.ketua', 'desa.nama_desa', 'desa.kd_desa', 'desa.kode_desa', 'sebutan_desa.sebutan_desa')
                    ->join($tb_kel, $tb_kel.'.id', '=', $tb_pinkel.'.id_kel')
                    ->join('desa', $tb_kel.'.desa', '=', 'desa.kd_desa')
                    ->join('sebutan_desa', 'sebutan_desa.id', '=', 'desa.sebutan')
                    ->withCount('pinjaman_anggota')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->where('tgl_transaksi', 'LIKE', '%'.$data['tahun'].'-'.$data['bulan'].'-%');
                    }], 'realisasi_pokok')
                    ->withSum(['real' => function ($query) use ($data) {
                        $query->where('tgl_transaksi', 'LIKE', '%'.$data['tahun'].'-'.$data['bulan'].'-%');
                    }], 'realisasi_jasa')
                    ->where($tb_pinkel.'.sistem_angsuran', '!=', '12')->where(function ($query) use ($data) {
                        if ($data['tahun_pinjaman_cair'] == '-' || $data['tahun_pinjaman_cair'] == '') {
                            $query->where([
                                [$data['tb_pinkel'].'.status', 'A'],
                                [$data['tb_pinkel'].'.tgl_cair', '<=', $data['tgl_kondisi']],
                            ])->orwhere([
                                [$data['tb_pinkel'].'.status', 'L'],
                                [$data['tb_pinkel'].'.tgl_lunas', '>=', $data['tgl_kondisi']],
                            ])->orwhere([
                                [$data['tb_pinkel'].'.status', 'R'],
                                [$data['tb_pinkel'].'.tgl_lunas', '>=', $data['tgl_kondisi']],
                            ])->orwhere([
                                [$data['tb_pinkel'].'.status', 'H'],
                                [$data['tb_pinkel'].'.tgl_lunas', '>=', $data['tgl_kondisi']],
                            ]);
                        } else {
                            $query->whereYear('tgl_cair', $data['tahun_pinjaman_cair'])->where('status', 'A');
                        }
                    })
                    ->orderBy($tb_kel.'.desa', 'ASC')
                    ->orderBy($tb_pinkel.'.id_kel', 'ASC')
                    ->orderBy($tb_pinkel.'.tgl_cair', 'ASC');
            },
            'pinjaman_kelompok.saldo' => function ($query) use ($data) {
                $query->where('tgl_transaksi', '<=', $data['tgl_kondisi']);
            },
            'pinjaman_kelompok.target' => function ($query) use ($data) {
                $query->where('jatuh_tempo', '<=', $data['tgl_kondisi']);
            },
            'pinjaman_kelompok.sis_pokok',
        ])->get();

        $view = view('pelaporan.view.perkembangan_piutang.tunggakan', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function ati(array $data)
    {
        $data['laporan'] = 'Aset Tetap dan Inventaris';
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['inventaris'] = Rekening::where('kode_akun', 'LIKE', '1.2.01%')->where(function ($query) use ($data) {
            $query->whereNull('tgl_nonaktif')->orwhere('tgl_nonaktif', '>', $data['tgl_kondisi']);
        })
            ->with([
                'inventaris' => function ($query) use ($data) {
                    $query->where([
                        ['jenis', '1'],
                        ['status', '!=', '0'],
                        ['tgl_beli', '<=', $data['tgl_kondisi']],
                        ['tgl_beli', 'NOT LIKE', ''],
                        ['harsat', '>', '0'],
                    ])->orderBy('tgl_beli', 'ASC');
                },
            ])
            ->get();

        $view = view('pelaporan.view.aset_tetap', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function atb(array $data)
    {
        $data['laporan'] = 'Aset Tak Berwujud';
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['inventaris'] = Rekening::where('kode_akun', 'LIKE', '1.2.03%')->where(function ($query) use ($data) {
            $query->whereNull('tgl_nonaktif')->orwhere('tgl_nonaktif', '>', $data['tgl_kondisi']);
        })
            ->with([
                'inventaris' => function ($query) use ($data) {
                    $query->where([
                        ['jenis', '3'],
                        ['status', '!=', '0'],
                        ['tgl_beli', '<=', $data['tgl_kondisi']],
                        ['tgl_beli', 'NOT LIKE', ''],
                    ])->orderBy('tgl_beli', 'ASC');
                },
            ])
            ->get();

        $view = view('pelaporan.view.aset_tak_berwujud', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function tingkat_kesehatan(array $data)
    {
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['dir'] = User::where([
            ['level', $data['kec']->ttd_mengetahui_lap],
            ['jabatan', '1'],
            ['lokasi', str_replace('_', '', config('tenant.suffix'))],
        ])->first();

        $data['pengawas'] = User::where([
            ['level', '3'],
            ['jabatan', '1'],
            ['lokasi', str_replace('_', '', config('tenant.suffix'))],
        ])->first();

        $data['bendahara'] = User::where([
            ['level', '1'],
            ['jabatan', '3'],
            ['lokasi', str_replace('_', '', config('tenant.suffix'))],
        ])->first();

        $view = view('pelaporan.view.penilaian_kesehatan', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function EB(array $data)
    {
        $keuangan = new Keuangan;
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $title = [
            '1,2,3' => 'Januari - Maret',
            '4,5,6' => 'April - Juni',
            '7,8,9' => 'Juli - September',
            '10,11,12' => 'Oktober - Desember',
            '12' => 'Rekap Januari - Desember',
        ];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $data['tgl'] = $title[$data['sub']].' '.$thn;

        $bulan = explode(',', $data['sub']);
        $awal = $bulan[0];
        $akhir = end($bulan);

        $data['bulan_hitung'] = $data['sub'];
        if ($data['sub'] == '12') {
            $data['bulan_hitung'] = '1,2,3,4,5,6,7,8,9,10,11,12';
        }
        $data['bulan_hitung'] = explode(',', $data['bulan_hitung']);

        $data['akhir'] = $akhir;
        $data['bulan_lalu'] = $awal - 1;
        $data['bulan_tampil'] = $bulan;
        if ($data['bulan_tampil'][0] == '12') {
            $data['bulan_tampil'] = [];
        }

        $data['triwulan'] = array_search($data['sub'], array_keys($title)) + 1;
        $data['akun1'] = AkunLevel1::where('lev1', '>=', '4')->with([
            'akun2',
            'akun2.akun3',
            'akun2.akun3.rek' => function ($query) use ($data) {
                $query->whereNull('tgl_nonaktif')->orwhere('tgl_nonaktif', '>', $data['tgl_kondisi']);
            },
            'akun2.akun3.rek.kom_saldo' => function ($query) use ($data) {
                $tahun = date('Y', strtotime($data['tgl_kondisi']));
                $query->where('tahun', $tahun)->orderBy('bulan', 'ASC')->orderBy('kode_akun', 'ASC');
            },
            'akun2.akun3.rek.kom_saldo.eb',
        ])->get();

        $data['keuangan'] = $keuangan;
        $view = view('pelaporan.view.e_budgeting', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function pelunasan(array $data)
    {
        $thn = $data['tahun'];
        $bln = $data['bulan'];
        $hari = $data['hari'];

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        if ($data['bulanan']) {
            $data['sub_judul'] = 'Bulan '.Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
            $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        }

        $tb_pinkel = 'pinjaman_kelompok_'.str_replace('_', '', config('tenant.suffix'));
        $tb_kel = 'kelompok_'.str_replace('_', '', config('tenant.suffix'));
        $data['pinjaman_kelompok'] = PinjamanKelompok::select([
            $tb_pinkel.'.*',
            $tb_kel.'.nama_kelompok',
            $tb_kel.'.ketua',
            $tb_kel.'.alamat_kelompok',
            $tb_kel.'.telpon',
            'desa.nama_desa',
            'desa.kd_desa',
            'desa.kode_desa',
            'sebutan_desa.sebutan_desa',
            DB::raw('CEILING(((TIMESTAMPDIFF(DAY, DATE_ADD('.$tb_pinkel.'.tgl_cair, INTERVAL '.$tb_pinkel.'.jangka MONTH), CURRENT_DATE) / 30) * -1)) as sisa'),
        ])->join($tb_kel, $tb_kel.'.id', '=', $tb_pinkel.'.id_kel')
            ->join('desa', $tb_kel.'.desa', '=', 'desa.kd_desa')
            ->join('sebutan_desa', 'sebutan_desa.id', '=', 'desa.sebutan')
            ->withSum(['real' => function ($query) use ($data) {
                $query->where('tgl_transaksi', 'LIKE', '%'.$data['tahun'].'-'.$data['bulan'].'-%');
            }], 'realisasi_pokok')
            ->withSum(['real' => function ($query) use ($data) {
                $query->where('tgl_transaksi', 'LIKE', '%'.$data['tahun'].'-'.$data['bulan'].'-%');
            }], 'realisasi_jasa')
            ->where([
                [$tb_pinkel.'.sistem_angsuran', '!=', '12'],
                [$tb_pinkel.'.status', 'A'],
            ])
            ->whereRaw('CEILING(((TIMESTAMPDIFF(DAY, DATE_ADD('.$tb_pinkel.'.tgl_cair, INTERVAL '.$tb_pinkel.'.jangka MONTH), CURRENT_DATE) / 30) * -1)) BETWEEN 0 AND 3')
            ->with([
                'target',
                'saldo',
            ])
            ->orderBy($tb_kel.'.desa', 'ASC')
            ->orderBy($tb_pinkel.'.id', 'ASC')->get();

        $view = view('pelaporan.view.perkembangan_piutang.pelunasan', $data)->render();
        $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

        $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'landscape');

        return $pdf->stream();
    }

    private function alokasi_laba(array $data)
    {
        $keuangan = new Keuangan;
        $thn = $data['tahun'];
        $bln = 1;
        $hari = 1;

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $trx_pembagian_laba = Transaksi::where([
            ['rekening_debit', '3.2.01.01'],
            ['keterangan_transaksi', 'LIKE', '%tahun '.($thn - 1).'%'],
        ])->first();

        $tgl_kondisi = $tgl;
        if ($trx_pembagian_laba) {
            $tgl_kondisi = $trx_pembagian_laba->tgl_transaksi;
        }

        $data['tanggal_kondisi'] = Tanggal::tglLatin(date('Y-m-d', strtotime($tgl_kondisi)));
        $data['sub_judul'] = 'Tahun '.($thn - 1);
        $data['tgl'] = Tanggal::tahun($tgl) - 1;

        $data['tahun_tb'] = $thn;
        $data['surplus'] = $keuangan->laba_rugi(($data['tahun'] - 1).'-13-00');
        $data['rekening'] = Rekening::where('kode_akun', 'like', '2.1.04%')->where(function ($query) use ($data) {
            $query->whereNull('tgl_nonaktif')->orwhere('tgl_nonaktif', '>', $data['tgl_kondisi']);
        })->with([
            'saldo' => function ($query) use ($data) {
                $query->where('tahun', $data['tahun_tb']);
            },
        ])->get();
        $data['desa'] = Desa::where('kd_kec', $data['kec']->kd_kec)->with([
            'saldo' => function ($query) use ($data) {
                $query->where([
                    ['tahun', $data['tahun_tb']],
                    ['bulan', '0'],
                ]);
            },
            'sebutan_desa',
        ])->get();
        $data['saldo_calk'] = Saldo::where([
            ['kode_akun', $data['kec']->kd_kec],
            ['tahun', $data['tahun_tb']],
        ])->get();

        $data['tgl_transaksi'] = $thn.'-12-31';
        $data['laporan'] = 'Alokasi Laba';
        $view = view('pelaporan.view.tutup_buku.alokasi_laba', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'portrait');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function jurnal_tutup_buku(array $data)
    {
        $thn = $data['tahun'] - 1;
        $bln = 1;
        $hari = 1;

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['tanggal_kondisi'] = Tanggal::tglLatin(date('Y-m-d', strtotime($tgl)));
        $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);
        $data['saldo'] = Saldo::where([
            ['tahun', $thn],
            ['bulan', '13'],
        ])->with('rek')->orderBy('kode_akun', 'ASC')->get();
        $data['rek'] = Rekening::where('kode_akun', '3.2.01.01')->first();

        $data['tgl_transaksi'] = $thn.'-12-31';
        $data['laporan'] = 'Jurnal Penutup';
        $view = view('pelaporan.view.tutup_buku.jurnal', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'portrait');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function neraca_tutup_buku(array $data)
    {
        $keuangan = new Keuangan;

        $thn = $data['tahun'];
        $bln = 1;
        $hari = 1;

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['tanggal_kondisi'] = Tanggal::tglLatin(date('Y-m-d', strtotime($tgl)));
        $data['sub_judul'] = 'Tahun'.' '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::tahun($tgl);

        $data['debit'] = 0;
        $data['kredit'] = 0;

        $data['akun1'] = AkunLevel1::where('lev1', '<=', '3')->with([
            'akun2',
            'akun2.akun3',
            'akun2.akun3.rek' => function ($query) use ($data) {
                $query->whereNull('tgl_nonaktif')->orwhere('tgl_nonaktif', '>', $data['tgl_kondisi']);
            },
            'akun2.akun3.rek.saldo' => function ($query) use ($data) {
                $query->where([
                    ['tahun', $data['tahun']],
                    ['bulan', '0'],
                ]);
            },
        ])->orderBy('kode_akun', 'ASC')->get();

        $data['laporan'] = 'Neraca Awal Tahun';
        $view = view('pelaporan.view.tutup_buku.neraca', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'portrait');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function CALK_tutup_buku(array $data)
    {
        $keuangan = new Keuangan;

        $thn = $data['tahun'];
        $bln = 1;
        $hari = 1;

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['tanggal_kondisi'] = Tanggal::tglLatin(date('Y-m-d', strtotime($tgl)));
        $data['tgl'] = Tanggal::tahun($tgl);
        $data['nama_tgl'] = 'Awal Tahun '.$thn;
        $data['sub_judul'] = 'Awal Tahun '.$thn;

        $data['debit'] = 0;
        $data['kredit'] = 0;

        if (! isset($data['tgl_mad'])) {
            $trx = Transaksi::where([
                ['keterangan_transaksi', 'LIKE', '%tahun '.$data['tahun'] - 1],
                ['rekening_debit', '3.2.01.01'],
            ])->first();

            $data['tgl_mad'] = $data['tgl_kondisi'];
            if ($trx) {
                $data['tgl_mad'] = $trx->tgl_transaksi;
            }
        }

        $data['akun1'] = AkunLevel1::where('lev1', '<=', '3')->with([
            'akun2',
            'akun2.akun3',
            'akun2.akun3.rek' => function ($query) use ($data) {
                $query->whereNull('tgl_nonaktif')->orwhere('tgl_nonaktif', '>', $data['tgl_kondisi']);
            },
            'akun2.akun3.rek.kom_saldo' => function ($query) use ($data) {
                $query->where([
                    ['tahun', $data['tahun']],
                    ['bulan', '0'],
                ]);
            },
        ])->orderBy('kode_akun', 'ASC')->get();

        $data['sekr'] = User::where([
            ['level', '1'],
            ['jabatan', '2'],
            ['lokasi', str_replace('_', '', config('tenant.suffix'))],
        ])->first();

        $data['bend'] = User::where([
            ['level', '1'],
            ['jabatan', '3'],
            ['lokasi', str_replace('_', '', config('tenant.suffix'))],
        ])->first();

        $data['pengawas'] = User::where([
            ['level', '3'],
            ['jabatan', '1'],
            ['lokasi', str_replace('_', '', config('tenant.suffix'))],
        ])->first();

        $data['dir_utama'] = User::where([
            ['level', '2'],
            ['jabatan', '65'],
            ['lokasi', str_replace('_', '', config('tenant.suffix'))],
        ])->first();

        $data['saldo_calk'] = Saldo::where([
            ['kode_akun', $data['kec']->kd_kec],
            ['tahun', $thn],
        ])->get();

        $calk = json_decode($data['kec']->calk, true);
        $data['pointA'] = $calk['calk']['A'] ?? '';

        $data['laporan'] = 'CALK Awal Tahun';
        $view = view('pelaporan.view.tutup_buku.calk', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'portrait');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function laba_rugi_tutup_buku(array $data)
    {
        $keuangan = new Keuangan;

        $thn = $data['tahun'];
        $bln = 1;
        $hari = 1;
        $awal_tahun = $thn.'-01-01';

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['tanggal_kondisi'] = Tanggal::tglLatin(date('Y-m-d', strtotime($tgl)));
        $data['sub_judul'] = 'Awal Tahun '.$thn;
        $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);
        $data['bulan_lalu'] = date('Y-m-t', strtotime('-1 month', strtotime($thn.'-'.$bln.'-10')));
        $data['header_lalu'] = 'Bulan Lalu';
        $data['header_sekarang'] = 'Bulan Ini';

        $jenis = 'Tahunan';
        if ($data['bulanan']) {
            $jenis = 'Bulanan';
        }

        $pph = $keuangan->pph($tgl, $jenis);
        $laba_rugi = $keuangan->laporan_laba_rugi($tgl, $jenis);

        $data['pph'] = [
            'bulan_lalu' => $pph['bulan_lalu'],
            'sekarang' => $pph['bulan_ini'],
        ];

        $data['pendapatan'] = $laba_rugi['pendapatan'];
        $data['beban'] = $laba_rugi['beban'];
        $data['pendapatanNOP'] = $laba_rugi['pendapatan_non_ops'];
        $data['bebanNOP'] = $laba_rugi['beban_non_ops'];

        $view = view('pelaporan.view.laba_rugi', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'portrait');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function LPM_tutup_buku(array $data)
    {
        $data['laporan'] = 'Laporan Perubahan Ekuitas';
        $keuangan = new Keuangan;

        $thn = $data['tahun'];
        $bln = 1;
        $hari = 1;

        $tgl = $thn.'-'.$bln.'-'.$hari;
        $data['tanggal_kondisi'] = Tanggal::tglLatin(date('Y-m-d', strtotime($tgl)));
        $data['sub_judul'] = 'Awal Tahun '.Tanggal::tahun($tgl);
        $data['tgl'] = Tanggal::namaBulan($tgl).' '.Tanggal::tahun($tgl);

        $data['keuangan'] = $keuangan;
        $data['rekening'] = Rekening::where('lev1', '3')->where(function ($query) use ($data) {
            $query->whereNull('tgl_nonaktif')->orwhere('tgl_nonaktif', '>', $data['tgl_kondisi']);
        })->with([
            'kom_saldo' => function ($query) use ($data) {
                $query->where('tahun', $data['tahun'])->where(function ($query) use ($data) {
                    $query->where('bulan', '0')->orwhere('bulan', $data['bulan']);
                });
            },
        ])->get();

        $view = view('pelaporan.view.tutup_buku.perubahan_modal', $data)->render();

        if ($data['type'] == 'pdf') {
            $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'portrait');

            return $pdf->stream();
        } else {
            return $view;
        }
    }

    private function PTK_POJK(array $data)
    {
        $keuangan = new Keuangan();
        $tgl_kondisi = $data['tgl_kondisi'];
        $kec = $data['kec'];

        if (!empty($data['bulanan'])) {
            $data['sub_judul'] = 'Per '.Tanggal::tglLatin($tgl_kondisi);
            $data['tgl'] = Tanggal::namaBulan($tgl_kondisi).' '.Tanggal::tahun($tgl_kondisi);
        } else {
            $data['sub_judul'] = 'Tahun '.Tanggal::tahun($tgl_kondisi);
            $data['tgl'] = Tanggal::tahun($tgl_kondisi);
        }

        $param = $keuangan->ptkPojkData($tgl_kondisi);
        $kolek = $keuangan->ptkPojkKolek($tgl_kondisi);

        $pendapatan = $keuangan->pendapatan($tgl_kondisi);
        $biaya = $keuangan->biaya($tgl_kondisi);
        $laba_bersih = $pendapatan - $biaya;

        $total_aset = (float) $param['total_aset'];
        $total_liabilitas = (float) $param['total_liabilitas'];
        $total_ekuitas = (float) $param['total_ekuitas'];
        $kas_setara_kas = (float) $param['kas_setara_kas'];
        $liabilitas_lancar = (float) $param['liabilitas_lancar'];
        $modal_disetor = (float) $param['modal_disetor'];
        $cadangan_ppap_terbentuk = (float) $param['cadangan_piutang'];

        $outstanding_pinjaman = (float) $kolek['outstanding_pinjaman'];
        $npl_neto = (float) $kolek['npl_neto'];
        $ppap_wajib_minimum = (float) $kolek['ppap_wajib_minimum'];
        $kolek_items = $kolek['kolek_items'];
        $sum_kolek_total = $kolek['sum_kolek_total'];

        $rasio_solvabilitas = $total_liabilitas > 0 ? ($total_aset / $total_liabilitas) * 100 : 0;
        $rasio_ekuitas = $modal_disetor > 0 ? ($total_ekuitas / $modal_disetor) * 100 : 0;
        $rasio_npl_neto = $outstanding_pinjaman > 0 ? ($npl_neto / $outstanding_pinjaman) * 100 : 0;
        $rasio_likuiditas = $liabilitas_lancar > 0 ? ($kas_setara_kas / $liabilitas_lancar) * 100 : 0;
        $roa = $total_aset > 0 ? ($laba_bersih / $total_aset) * 100 : 0;

        if ($ppap_wajib_minimum <= 0) {
            $ppap_coverage = 100;
        } else {
            $ppap_coverage = $cadangan_ppap_terbentuk > 0 ? ($cadangan_ppap_terbentuk / $ppap_wajib_minimum) * 100 : 0;
        }

        $skor_solv = $this->ptkPojkScoreBand($rasio_solvabilitas, [120 => 100, 115 => 75, 110 => 50, 105 => 25, 0 => 0]);
        $skor_ek = $this->ptkPojkScoreBand($rasio_ekuitas, [100 => 100, 90 => 75, 75 => 50, 60 => 25, 0 => 0]);
        $skor_permodalan = ($skor_solv + $skor_ek) / 2;

        $skor_npl = $this->ptkPojkScoreBandReverse($rasio_npl_neto, [2 => 100, 3.5 => 75, 5 => 50, 10 => 25, 100 => 0]);
        $skor_ppap = $this->ptkPojkScoreBand($ppap_coverage, [100 => 100, 80 => 75, 60 => 50, 40 => 25, 0 => 0]);
        $skor_kualitas_aset = ($skor_npl + $skor_ppap) / 2;

        $skor_manajemen = 75;

        $skor_rentabilitas = $this->ptkPojkScoreBand($roa, [2.5 => 100, 1.5 => 75, 0.5 => 50, 0 => 25, -9999 => 0]);

        $skor_likuiditas = $this->ptkPojkScoreBand($rasio_likuiditas, [10 => 100, 7 => 75, 4 => 50, 2 => 25, 0 => 0]);

        $skor_komposit = ($skor_permodalan * 0.25)
            + ($skor_kualitas_aset * 0.35)
            + ($skor_manajemen * 0.20)
            + ($skor_rentabilitas * 0.10)
            + ($skor_likuiditas * 0.10);

        $trigger_pk5 = ($rasio_npl_neto >= 25) || ($rasio_ekuitas < 50) || ($ppap_coverage < 50);

        if ($trigger_pk5) {
            $pk = 5;
        } elseif ($skor_komposit >= 81) {
            $pk = 1;
        } elseif ($skor_komposit >= 66) {
            $pk = 2;
        } elseif ($skor_komposit >= 51) {
            $pk = 3;
        } else {
            $pk = 4;
        }

        $pk_labels = [
            1 => 'Sangat Sehat',
            2 => 'Sehat',
            3 => 'Cukup Sehat',
            4 => 'Kurang Sehat',
            5 => 'Tidak Sehat',
        ];
        $pk_label = $pk_labels[$pk];

        $status_pengawasan_alasan = [];
        if ($pk == 5) {
            $status = 'Pengawasan Khusus';
            $warna = '#dc3545';
            $status_pengawasan_alasan[] = 'Peringkat Komposit (PK 5)';
        } elseif ($rasio_ekuitas < 50 || $rasio_npl_neto >= 25) {
            $status = 'Pengawasan Khusus';
            $warna = '#dc3545';
            if ($rasio_ekuitas < 50) {
                $status_pengawasan_alasan[] = 'Rasio Ekuitas <50% ('.number_format($rasio_ekuitas, 2).'%)';
            }
            if ($rasio_npl_neto >= 25) {
                $status_pengawasan_alasan[] = 'NPL Neto >=25% ('.number_format($rasio_npl_neto, 2).'%)';
            }
        } elseif ($pk == 4 || ($rasio_ekuitas >= 50 && $rasio_ekuitas < 75) || ($rasio_npl_neto > 5 && $rasio_npl_neto < 25)) {
            $status = 'Pengawasan Intensif';
            $warna = '#fd7e14';
            if ($pk == 4) {
                $status_pengawasan_alasan[] = 'Peringkat Komposit (PK 4)';
            }
            if ($rasio_ekuitas >= 50 && $rasio_ekuitas < 75) {
                $status_pengawasan_alasan[] = 'Rasio Ekuitas 50% s.d <75% ('.number_format($rasio_ekuitas, 2).'%)';
            }
            if ($rasio_npl_neto > 5 && $rasio_npl_neto < 25) {
                $status_pengawasan_alasan[] = 'NPL Neto >5% s.d <25% ('.number_format($rasio_npl_neto, 2).'%)';
            }
        } else {
            $status = 'Pengawasan Normal';
            $warna = '#28a745';
            $status_pengawasan_alasan[] = 'Seluruh rasio utama terpenuhi';
        }

        $status_pengawasan_alasan_text = implode('; ', $status_pengawasan_alasan);

        $rekomendasi = $this->ptkPojkRekomendasi([
            'total_aset' => $total_aset,
            'total_liabilitas' => $total_liabilitas,
            'total_ekuitas' => $total_ekuitas,
            'modal_disetor' => $modal_disetor,
            'kas_setara_kas' => $kas_setara_kas,
            'liabilitas_lancar' => $liabilitas_lancar,
            'cadangan_ppap_terbentuk' => $cadangan_ppap_terbentuk,
            'ppap_wajib_minimum' => $ppap_wajib_minimum,
            'outstanding_pinjaman' => $outstanding_pinjaman,
            'npl_neto' => $npl_neto,
            'pendapatan' => $pendapatan,
            'biaya' => $biaya,
            'laba_bersih' => $laba_bersih,
            'rasio_solvabilitas' => $rasio_solvabilitas,
            'rasio_ekuitas' => $rasio_ekuitas,
            'rasio_npl_neto' => $rasio_npl_neto,
            'rasio_likuiditas' => $rasio_likuiditas,
            'ppap_coverage' => $ppap_coverage,
            'roa' => $roa,
        ], $kec);

        $kolek_detail = $keuangan->ptkPojkKolekDetail($tgl_kondisi);

        $data['analisis'] = [
            'total_aset' => $total_aset,
            'total_liabilitas' => $total_liabilitas,
            'kas_setara_kas' => $kas_setara_kas,
            'liabilitas_lancar' => $liabilitas_lancar,
            'modal_disetor' => $modal_disetor,
            'total_ekuitas' => $total_ekuitas,
            'outstanding_pinjaman' => $outstanding_pinjaman,
            'npl_neto' => $npl_neto,
            'cadangan_ppap_terbentuk' => $cadangan_ppap_terbentuk,
            'ppap_wajib_minimum' => $ppap_wajib_minimum,
            'ppap_coverage' => $ppap_coverage,
            'pendapatan' => $pendapatan,
            'biaya' => $biaya,
            'laba_bersih' => $laba_bersih,
            'roa' => $roa,
            'kolek_items' => $kolek_items,
            'sum_kolek_total' => $sum_kolek_total,
            'rasio_solvabilitas' => $rasio_solvabilitas,
            'rasio_ekuitas' => $rasio_ekuitas,
            'rasio_npl_neto' => $rasio_npl_neto,
            'rasio_likuiditas' => $rasio_likuiditas,
            'skor_permodalan' => $skor_permodalan,
            'skor_kualitas_aset' => $skor_kualitas_aset,
            'skor_manajemen' => $skor_manajemen,
            'skor_rentabilitas' => $skor_rentabilitas,
            'skor_likuiditas' => $skor_likuiditas,
            'skor_komposit' => $skor_komposit,
            'pk' => $pk,
            'pk_label' => $pk_label,
            'status_pengawasan' => $status,
            'status_pengawasan_label' => $status,
            'status_pengawasan_warna' => $warna,
            'status_pengawasan_alasan' => $status_pengawasan_alasan_text,
            'rekomendasi' => $rekomendasi,
            'kolek_detail' => $kolek_detail,
        ];

        $data['laporan'] = 'Penilaian Tingkat Kesehatan POJK';

        if ($data['type'] == 'pdf') {
            $paperSize = Session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

            $data['laporan_portrait'] = $data['laporan'];

            $view_portrait = view('pelaporan.view.pojk.penilaian_tingkat_kesehatan', $data)->render();

            $view_kolek = view('pelaporan.view.pojk.kolektabilitas_detail', $data)->render();

            $html = $this->combineLandscapePage($view_portrait, $view_kolek, $paperSize);

            return PDF::loadHTML($html)->setPaper($paperSize, 'portrait')->stream();
        }

        $view = view('pelaporan.view.pojk.penilaian_tingkat_kesehatan', $data)->render();

        return $view;
    }

    private function combineLandscapePage($html_portrait, $html_landscape, $paperSize)
    {
        $landscape_inner = $html_landscape;
        if (preg_match('/<body[^>]*>(.*)<\/body>/s', $html_landscape, $m)) {
            $landscape_inner = $m[1];
        } elseif (preg_match('/<main[^>]*>(.*)<\/main>/s', $html_landscape, $m)) {
            $landscape_inner = $m[1];
        }

        $clean_portrait = $html_portrait;

        $is_a4 = is_string($paperSize) && strtoupper($paperSize) === 'A4';
        $landscape_size_css = $is_a4 ? '297mm 210mm' : '935.43pt 595.28pt';

        $style_block = '<style>@page landscape_page { size: '.$landscape_size_css.'; } .landscape-page { page: landscape_page; page-break-before: always; }</style>';

        if (preg_match('/<\/head>/i', $clean_portrait)) {
            $clean_portrait = preg_replace('/<\/head>/i', $style_block.'</head>', $clean_portrait, 1);
        } else {
            $clean_portrait = $style_block.$clean_portrait;
        }

        $landscape_html = '<div class="landscape-page">'.$landscape_inner.'</div>';

        if (preg_match_all('/<\/body>/i', $clean_portrait, $matches, PREG_OFFSET_CAPTURE)) {
            $last = end($matches[0]);
            $clean_portrait = substr($clean_portrait, 0, $last[1]).$landscape_html.substr($clean_portrait, $last[1] + strlen('</body>'));
        } else {
            $clean_portrait .= $landscape_html;
        }

        return $clean_portrait;
    }

    private function ptkPojkScoreBand($value, array $bands)
    {
        foreach ($bands as $min => $score) {
            if ($value >= $min) {
                return $score;
            }
        }

        return 0;
    }

    private function ptkPojkScoreBandReverse($value, array $bands)
    {
        foreach ($bands as $max => $score) {
            if ($value <= $max) {
                return $score;
            }
        }

        return 0;
    }

    private function ptkPojkRekomendasi(array $a, $kec)
    {
        $rp = function ($n) {
            return 'Rp '.number_format((float) $n, 0, ',', '.');
        };

        $rek_kas = Rekening::where('kode_akun', 'like', '1.1.01%')
            ->where(function ($q) {
                $q->whereNull('tgl_nonaktif')->orWhere('tgl_nonaktif', '>', now());
            })->orderBy('kode_akun', 'ASC')->get(['kode_akun', 'nama_akun']);

        $rek_bank = Rekening::where('kode_akun', 'like', '1.1.02%')
            ->where(function ($q) {
                $q->whereNull('tgl_nonaktif')->orWhere('tgl_nonaktif', '>', now());
            })->orderBy('kode_akun', 'ASC')->get(['kode_akun', 'nama_akun']);

        $rek_cadangan = Rekening::where('kode_akun', 'like', '1.1.14%')
            ->where(function ($q) {
                $q->whereNull('tgl_nonaktif')->orWhere('tgl_nonaktif', '>', now());
            })->orderBy('kode_akun', 'ASC')->get(['kode_akun', 'nama_akun']);

        $rek_pokok = Rekening::where('kode_akun', 'like', '3.1.01%')
            ->where(function ($q) {
                $q->whereNull('tgl_nonaktif')->orWhere('tgl_nonaktif', '>', now());
            })->orderBy('kode_akun', 'ASC')->get(['kode_akun', 'nama_akun']);

        $rek_wajib = Rekening::where('kode_akun', 'like', '3.1.02%')
            ->where(function ($q) {
                $q->whereNull('tgl_nonaktif')->orWhere('tgl_nonaktif', '>', now());
            })->orderBy('kode_akun', 'ASC')->get(['kode_akun', 'nama_akun']);

        $rek_hibah = Rekening::where('kode_akun', 'like', '3.1.03%')
            ->where(function ($q) {
                $q->whereNull('tgl_nonaktif')->orWhere('tgl_nonaktif', '>', now());
            })->orderBy('kode_akun', 'ASC')->get(['kode_akun', 'nama_akun']);

        $rek_cadangan_ekuitas = Rekening::where('kode_akun', 'like', '3.2.01%')
            ->where(function ($q) {
                $q->whereNull('tgl_nonaktif')->orWhere('tgl_nonaktif', '>', now());
            })->orderBy('kode_akun', 'ASC')->get(['kode_akun', 'nama_akun']);

        $rek_liabilitas_lancar = Rekening::where('kode_akun', 'like', '2.1.0%')
            ->where('lev1', '2')->where('lev2', '1')
            ->where(function ($q) {
                $q->whereNull('tgl_nonaktif')->orWhere('tgl_nonaktif', '>', now());
            })->orderBy('kode_akun', 'ASC')->get(['kode_akun', 'nama_akun']);

        $rek_giro = Rekening::where('kode_akun', 'like', '1.1.02.01%')
            ->where(function ($q) {
                $q->whereNull('tgl_nonaktif')->orWhere('tgl_nonaktif', '>', now());
            })->orderBy('kode_akun', 'ASC')->get(['kode_akun', 'nama_akun']);

        $rek_deposito = Rekening::where('kode_akun', 'like', '1.1.02.02%')
            ->where(function ($q) {
                $q->whereNull('tgl_nonaktif')->orWhere('tgl_nonaktif', '>', now());
            })->orderBy('kode_akun', 'ASC')->get(['kode_akun', 'nama_akun']);

        $rek_piutang = Rekening::where('kode_akun', 'like', '1.1.03%')
            ->where(function ($q) {
                $q->whereNull('tgl_nonaktif')->orWhere('tgl_nonaktif', '>', now());
            })->orderBy('kode_akun', 'ASC')->get(['kode_akun', 'nama_akun']);

        $rek_pendapatan_jasa = Rekening::where('lev1', '4')->where('lev2', '1')
            ->where(function ($q) {
                $q->whereNull('tgl_nonaktif')->orWhere('tgl_nonaktif', '>', now());
            })->orderBy('kode_akun', 'ASC')->get(['kode_akun', 'nama_akun']);

        $rek_beban_gaji = Rekening::where('kode_akun', 'like', '5.1.01%')
            ->where(function ($q) {
                $q->whereNull('tgl_nonaktif')->orWhere('tgl_nonaktif', '>', now());
            })->orderBy('kode_akun', 'ASC')->get(['kode_akun', 'nama_akun']);

        $rek_beban_atk = Rekening::where('kode_akun', 'like', '5.1.02%')
            ->where(function ($q) {
                $q->whereNull('tgl_nonaktif')->orWhere('tgl_nonaktif', '>', now());
            })->orderBy('kode_akun', 'ASC')->get(['kode_akun', 'nama_akun']);

        $rek_beban_adm = Rekening::where('kode_akun', 'like', '5.1.03%')
            ->where(function ($q) {
                $q->whereNull('tgl_nonaktif')->orWhere('tgl_nonaktif', '>', now());
            })->orderBy('kode_akun', 'ASC')->get(['kode_akun', 'nama_akun']);

        $rekomendasi = [];

        $listRekText = function ($rek_list, $prefix_nama) {
            if ($rek_list->isEmpty()) {
                return $prefix_nama.' (rekening belum tersedia)';
            }
            $items = [];
            foreach ($rek_list as $r) {
                $items[] = $r->kode_akun.' ('.$r->nama_akun.')';
            }

            return $prefix_nama.' '.implode(', ', $items);
        };

        $listRekSingkat = function ($rek_list, $prefix_nama) {
            if ($rek_list->isEmpty()) {
                return $prefix_nama.' (rekening belum tersedia)';
            }
            $items = [];
            foreach ($rek_list as $r) {
                $items[] = $r->kode_akun;
            }

            return $prefix_nama.' '.implode(', ', $items);
        };

        if ($a['rasio_solvabilitas'] < 110) {
            $need_solv = max(0, ($a['total_liabilitas'] * 1.10) - $a['total_aset']);
            $target_total_aset = max(0, ($a['total_liabilitas'] * 1.10));
            $rekomendasi[] =
                "PERMODALAN: Rasio Solvabilitas ".number_format($a['rasio_solvabilitas'], 2)."% di bawah batas minimum 110%. "
                ."Cara perbaikan: (a) Tambahkan minimal ".$rp($need_solv)." ke rekening ".str_replace('rekening ', '', $listRekSingkat($rek_kas, '')).' / '.str_replace('rekening ', '', $listRekSingkat($rek_bank, ''))
                ." dari setoran modal/hibah/penambahan simpanan anggota; "
                ."(b) Naikkan ekuitas di rekening ".$listRekSingkat($rek_pokok, '').', '.$listRekSingkat($rek_wajib, '').', atau '.$listRekSingkat($rek_hibah, '').' minimal '.$rp(max(0, ($a['total_liabilitas'] * 1.10) - ($a['total_aset'] - $a['total_liabilitas'])))
                ."; (c) Atau kurangi liabilitas dengan membayar jatuh tempo simpanan anggota / utang pihak ke-3 tepat waktu. "
                ."Setelah penyesuaian, target Total Aset minimal ".$rp($target_total_aset).' (110% dari Liabilitas '.$rp($a['total_liabilitas']).').';
        }

        if ($a['rasio_ekuitas'] < 75) {
            $need_ekuitas = max(0, ($a['modal_disetor'] * 0.75) - $a['total_ekuitas']);
            $rekomendasi[] =
                "PERMODALAN: Rasio Ekuitas ".number_format($a['rasio_ekuitas'], 2)."% di bawah batas minimum 75%. "
                ."Cara perbaikan: (a) Setoran Simpanan Pokok anggota baru di rekening ".$listRekSingkat($rek_pokok, '')."; "
                ."(b) Naikkan Simpanan Wajib ".$listRekSingkat($rek_wajib, '')." melalui iuran bulanan; "
                ."(c) Setor modal hibah ke ".$listRekSingkat($rek_hibah, '')."; "
                ."(d) Alokasikan SHU tahun berjalan ke rekening cadangan ekuitas ".$listRekSingkat($rek_cadangan_ekuitas, '').". "
                ."Target Total Ekuitas minimal ".$rp($a['modal_disetor'] * 0.75)." (75% dari Modal Disetor ".$rp($a['modal_disetor']).').';
        }

        if ($a['rasio_npl_neto'] > 5) {
            $target_npl = $a['outstanding_pinjaman'] * 0.05;
            $selisih_npl = max(0, $a['npl_neto'] - $target_npl);
            $rekomendasi[] =
                "KUALITAS ASET: Rasio NPL Neto ".number_format($a['rasio_npl_neto'], 2)."% di atas batas maksimum 5%. "
                ."Cara perbaikan: (a) Kurangi NPL Neto menjadi <= ".$rp($target_npl)." (5% dari Outstanding ".$rp($a['outstanding_pinjaman']).'), selisih diturunkan '.$rp($selisih_npl).'; '
                ."(b) Restrukturisasi pinjaman Kurang Lancar/Diragukan (perpanjang tenor, reschedule angsuran); "
                ."(c) Hapus buku pinjaman Macet melalui mekanisme Penghapusan Piutang ke rekening ".$listRekSingkat($rek_cadangan, '').'; '
                ."(d) Early warning system: angsuran ke-3 kirim surat peringatan, ke-4 kunjungan, ke-6 proses collection; "
                ."(e) Coverage agunan minimal 125% untuk pinjaman baru; "
                ."(f) Review kelayakan penerima pinjaman baru via Komite Kredit.";
        }

        if ($a['ppap_coverage'] < 100) {
            $need_ppap = max(0, $a['ppap_wajib_minimum'] - $a['cadangan_ppap_terbentuk']);
            $rekomendasi[] =
                "KUALITAS ASET: Coverage PPAP ".number_format($a['ppap_coverage'], 2)."% di bawah batas minimum 100%. "
                ."Cara perbaikan: (a) Tambahkan penyisihan PPAP ke rekening ".$listRekSingkat($rek_cadangan, '').' sebesar '.$rp($need_ppap)
                ." agar coverage menjadi 100%; "
                ."(b) Sumber dana: alokasi SHU tahun berjalan atau beban laba rugi periode berjalan; "
                ."(c) Formulasi PPAP sesuai kolektibilitas POJK: Lancar 0%, DPK 5%, Kurang Lancar 15%, Diragukan 50%, Macet 100%.";
        }

        if ($a['rasio_likuiditas'] < 4) {
            $need_kas = max(0, ($a['liabilitas_lancar'] * 0.04) - $a['kas_setara_kas']);
            $target_kas = max(0, ($a['liabilitas_lancar'] * 0.04));
            $liab_text = $listRekSingkat($rek_liabilitas_lancar, '');
            $rekomendasi[] =
                "LIKUIDITAS: Rasio Kas & Setara Kas ".number_format($a['rasio_likuiditas'], 2)."% di bawah batas minimum 4%. "
                ."Cara perbaikan: (a) Tambah saldo ".$listRekSingkat($rek_kas, '').' / '.$listRekSingkat($rek_bank, '').' minimal '.$rp($need_kas).' agar rasio menjadi 4%; '
                ."(b) Percepat penagihan piutang, tunda pencairan pinjaman baru, tarik simpanan berjangka jatuh tempo; "
                ."(c) Diversifikasi penempatan kas: giro ".$listRekSingkat($rek_giro, '')." (60%), deposito on-call ".$listRekSingkat($rek_deposito, '')." (30%), kas tunai ".$listRekSingkat($rek_kas, '')." (10%); "
                ."(d) Monitor liabilitas lancar ".$liab_text.' (simpanan sukarela, simpanan berjangka <=1 tahun) untuk menjaga rasio tetap >=4%; '
                ."Target Kas & Setara Kas minimal ".$rp($target_kas).'.';
        }

        if ($a['roa'] < 0.5) {
            $target_roa = $a['total_aset'] * 0.005;
            $gap_laba = max(0, $target_roa - $a['laba_bersih']);
            $rekomendasi[] =
                "RENTABILITAS: ROA ".number_format($a['roa'], 2)."% di bawah batas minimum 0.5%. "
                ."Cara perbaikan: (a) Naikkan laba bersih menjadi minimal ".$rp($target_roa)." (gap ".$rp($gap_laba).") melalui: "
                ."- review margin jasa di rekening ".$listRekSingkat($rek_pendapatan_jasa, '').', '
                ."- efisiensi ".$listRekSingkat($rek_beban_gaji, '').', '.$listRekSingkat($rek_beban_atk, '').', '.$listRekSingkat($rek_beban_adm, '').'; '
                ."(b) Alirkan Kas/Bank ke pinjaman produktif (rekening ".$listRekSingkat($rek_piutang, '').") untuk meningkatkan pendapatan bunga; "
                ."(c) Review portofolio pinjaman: hentikan produk dengan margin rendah, fokus pada layanan jasa baru (administrasi, transfer, asuransi).";
        }

        $rekomendasi[] =
            "MANAJEMEN: Lengkapi dokumentasi tata kelola: (a) Notulen rapat pengurus & rapat anggota; "
            ."(b) Kebijakan risiko & SOP operasional; "
            ."(c) Program pelatihan SDM untuk pengurus/staf; "
            ."(d) Audit internal berkala (minimal 1x/tahun oleh pihak independen).";

        if (count($rekomendasi) === 1) {
            $rekomendasi = [
                'PERTAHANKAN: Kinerja keuangan LKM saat ini telah memenuhi seluruh rasio POJK (Solvabilitas, Ekuitas, NPL, PPAP, Likuiditas, ROA). Pertahankan praktik baik: (a) Monitoring kolektibilitas piutang secara berkala; (b) Review kecukupan PPAP tiap akhir bulan; (c) Jaga likuiditas kas minimal 4% dari liabilitas lancar; (d) Lanjutkan efisiensi biaya operasional; (e) Dokumentasi tata kelola & rapat pengurus.',
            ];
        }

        return $rekomendasi;
    }

    public function beritaAcara()
    {
        $data['kec'] = Kecamatan::where('id', str_replace('_', '', config('tenant.suffix')))->with([
            'kabupaten',
        ])->first();

        $tgl_pakai = $data['kec']->tgl_pakai;
        $minimal_pakai = '2023-01-01';
        if (strtotime($tgl_pakai) < strtotime($minimal_pakai)) {
            $tgl_pakai = $minimal_pakai;
        }

        $tahun_pakai = Tanggal::tahun($tgl_pakai);
        $data['rekening'] = Rekening::with([
            'kom_saldo' => function ($query) use ($tahun_pakai) {
                $query->where([
                    ['tahun', $tahun_pakai],
                    ['bulan', '0'],
                ]);
            },
            'saldo' => function ($query) use ($tahun_pakai) {
                $query->where([
                    ['tahun', $tahun_pakai - 1],
                    ['bulan', '12'],
                ]);
            },
        ])->get();

        $data['kom_aset'] = AkunLevel1::where('lev1', '1')->with([
            'saldo_awal',
            'saldo' => function ($query) use ($tahun_pakai) {
                $query->where([
                    ['tahun', $tahun_pakai],
                    ['bulan', '0'],
                ]);
            },
        ])->first();

        $data['direktur'] = User::where([
            ['jabatan', '1'],
            ['level', '1'],
            ['lokasi', str_replace('_', '', config('tenant.suffix'))],
        ])->first();

        $data['bendahara'] = User::where([
            ['jabatan', '3'],
            ['level', '1'],
            ['lokasi', str_replace('_', '', config('tenant.suffix'))],
        ])->first();

        $view = view('pelaporan.view.ba_pergantian_laporan', $data)->render();

        $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

        $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'portrait');

        return $pdf->stream();
    }

    public function mou()
    {
        $keuangan = new Keuangan;
        $kec = Kecamatan::where('id', str_replace('_', '', config('tenant.suffix')))->with('kabupaten', 'desa', 'ttd')->first();
        $kab = $kec->kabupaten;

        $data['logo'] = $this->supabaseToBase64($kec->logo);
        $data['nama_lembaga'] = $kec->nama_lembaga_sort;
        $data['nama_kecamatan'] = $kec->sebutan_kec.' '.$kec->nama_kec;

        if (Keuangan::startWith($kab->nama_kab, 'KOTA') || Keuangan::startWith($kab->nama_kab, 'KOTA')) {
            $kabupaten_text = ' '.ucwords(strtolower($kab->nama_kab));
            $data['nama_kabupaten'] = ucwords(strtolower($kab->nama_kab));
        } else {
            $kabupaten_text = ' Kabupaten '.ucwords(strtolower($kab->nama_kab));
            $data['nama_kabupaten'] = 'Kabupaten '.ucwords(strtolower($kab->nama_kab));
        }

        // Cek panjang sebelum menambahkan kabupaten
        if (strlen($data['nama_kecamatan'].$kabupaten_text) > 50) {
            $data['nama_kecamatan'] .= '<br>'.$kabupaten_text;
        } else {
            $data['nama_kecamatan'] .= $kabupaten_text;
        }

        $jabatan = '1';
        $level = '1';
        if (str_replace('_', '', config('tenant.suffix')) == '207') {
            $jabatan = '1';
            $level = '2';
        }

        $data['dir'] = User::where([
            ['lokasi', str_replace('_', '', config('tenant.suffix'))],
            ['jabatan', $jabatan],
            ['level', $level],
        ])->first();

        $data['kec'] = $kec;
        $data['keu'] = $keuangan;

        $view = view('pelaporan.view.mou', $data)->render();

        $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

        $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'portrait');

        return $pdf->stream();
    }

    public function ts()
    {
        $data['kec'] = Kecamatan::where('id', str_replace('_', '', config('tenant.suffix')))->first();

        $view = view('pelaporan.view.ts', $data)->render();
        $pdf = PDF::loadHTML($view)->setPaper([0, 0, 595.28, 352], 'portrait');

        return $pdf->stream();
    }

    public function invoice(AdminInvoice $invoice)
    {
        $root_domain = explode('.', request()->getHost())[0];
        $allowed = ['master', 'laravel'];

        $kec = Kecamatan::where('web_kec', request()->getHost())->orwhere('web_alternatif', request()->getHost())->first();
        $data['inv'] = AdminInvoice::where('idv', $invoice->idv)->with('jp', 'trx', 'kec', 'kec.kabupaten')->first();

        if (! in_array($root_domain, $allowed)) {
            if ($kec->id != $data['inv']->lokasi) {
                abort(404);
            }
        }

        $view = view('pelaporan.view.invoice', $data)->render();
        $paperSize = session::get('lokasi') == 109 ? [0, 0, 595.28, 935.43] : 'A4';

        $pdf = PDF::loadHTML($view)->setPaper($paperSize, 'portrait');

        return $pdf->stream();
    }

    private function supabaseToBase64($url)
    {
        $response = Http::withOptions([
            'verify' => false,
        ])->get($url);

        if (! $response->successful()) {
            return null;
        }

        $binary = $response->body();

        $extension = pathinfo($url, PATHINFO_EXTENSION);
        $mime = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
        ][$extension] ?? 'application/octet-stream';

        return "data:$mime;base64,".base64_encode($binary);
    }
}
