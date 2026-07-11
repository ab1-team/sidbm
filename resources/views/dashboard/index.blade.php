@extends('layouts.base')

@section('content')
    <form action="" method="post" id="defaultForm">
        @csrf

        <input type="hidden" name="tgl" id="tgl" value="{{ date('d/m/Y') }}">
    </form>

    <div class="row">
        @if ($jumlah_saldo <= $jumlah)
            <div class="col-12">
                <div class="alert alert-warning text-white" role="alert">
                    Sepertinya saldo transaksi anda belum tersimpan di aplikasi. Silahkan Klik <a href="#"
                        data-href="/simpan_saldo?bulan=00&tahun={{ date('Y') }}" class="alert-link"
                        id="simpanSaldo">Disini</a>
                    untuk menyimpan
                    saldo transaksi anda
                </div>
            </div>
        @endif
        @if (Session::get('lokasi') != '1')
            <div class="col-12">
                <div class="alert alert-primary text-white" role="alert">
                    <strong>Pahami Panduan Transaksi SI DBM</strong> untuk meminimalisir kesalahan input jurnal. Klik
                    <a href="/pengaturan/panduan_transaksi" class="alert-link text-white text-decoration-underline">disini</a>
                    untuk melihat panduan lengkap 17 jenis transaksi.
                </div>
            </div>
        @endif
        <div class="col-sm-4">
            <div class="card">
                <div class="card-body p-3 position-relative pointer" id="btnAktif">
                    <div class="row">
                        <div class="col-7 text-start">
                            <p class="text-sm mb-1 text-capitalize font-weight-bold">Pemanfaat</p>
                            <h5 class="font-weight-bolder mb-0">
                                {{ $pinjaman_kelompok }} Kelompok
                            </h5>
                            <span class="text-sm text-end text-success font-weight-bolder mt-auto mb-0">
                                {{ $pinjaman_anggota }}
                                <span class="font-weight-normal text-secondary">pemanfaat Aktif</span>
                            </span>
                        </div>
                        <div class="col-5">
                            <div class="dropdown text-end">
                                <span class="text-xs text-secondary">Periode {{ date('d/m/y') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-4 mt-sm-0 mt-4">
            <div class="card">
                <div class="card-body p-3 position-relative pointer" id="btnpinjaman">
                    <div class="row">
                        <div class="col-7 text-start">
                            <p class="text-sm mb-1 text-capitalize font-weight-bold">Proposal Pinjaman</p>
                            <h5 class="font-weight-bolder mb-0">
                                {{ $proposal }} Proposal
                            </h5>
                            <span class="text-sm text-end text-success font-weight-bolder mt-auto mb-0">
                                {{ $verifikasi }}
                                <span class="font-weight-normal text-secondary">verifikasi</span>
                            </span>
                        </div>
                        <div class="col-5">
                            <div class="dropdown text-end">
                                <span class="text-xs text-secondary">{{ $waiting }} waiting</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-4 mt-sm-0 mt-4">
            <div class="card">
                <div class="card-body p-3 position-relative pointer" id="btnjatuhTempo">
                    <div class="row">
                        <div class="col-7 text-start">
                            <p class="text-sm mb-1 text-capitalize font-weight-bold">Jatuh Tempo</p>
                            <h5 class="font-weight-bolder mb-0">
                                <span id="jatuh_tempo">
                                    <div class="spinner-border sm text-info" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </span> Hari Ini
                            </h5>
                            <span class="font-weight-normal text-secondary text-sm">
                                <span class="font-weight-bolder text-success" id="nunggak">
                                    <div class="spinner-border xs text-info" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </span> menunggak
                            </span>
                        </div>
                        <div class="col-5">
                            <div class="dropdown text-end">
                                <span class="text-xs text-warning">&#33; tagihan</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-lg-4 col-sm-6">
            <div class="card h-100">
                <div class="card-header pb-0 p-3">
                    <div class="d-flex justify-content-between">
                        <h6 class="mb-0">Angsuran Hari Ini</h6>
                        <button type="button" id="btnDetailAngsuranHarian"
                            class="btn btn-icon-only btn-rounded btn-outline-secondary mb-0 ms-2 btn-sm d-flex align-items-center justify-content-center"
                            data-bs-toggle="tooltip" data-bs-placement="left" data-bs-original-title="Detail Angsuran">
                            <i class="material-icons text-sm">priority_high</i>
                        </button>
                    </div>
                </div>
                <div class="card-body pb-0 p-3 pt-0 mt-4">
                    <div class="row">
                        <div class="col-7 text-start">
                            <div class="chart">
                                <canvas id="chart-pie" class="chart-canvas" height="400"
                                    style="display: block; box-sizing: border-box; height: 200px; width: 169.7px;"
                                    width="339"></canvas>
                            </div>
                        </div>
                        <div class="col-5 my-auto">
                            <span class="badge badge-md badge-dot me-4 d-block text-start">
                                <i class="bg-info"></i>
                                <span class="text-dark text-xs">SPP Pokok</span>
                            </span>
                            <span class="badge badge-md badge-dot me-4 d-block text-start">
                                <i class="bg-success"></i>
                                <span class="text-dark text-xs">SPP Jasa</span>
                            </span>
                            <span class="badge badge-md badge-dot me-4 d-block text-start">
                                <i class="bg-dark"></i>
                                <span class="text-dark text-xs">UEP Pokok</span>
                            </span>
                            <span class="badge badge-md badge-dot me-4 d-block text-start">
                                <i class="bg-secondary"></i>
                                <span class="text-dark text-xs">UEP Jasa</span>
                            </span>
                            <span class="badge badge-md badge-dot me-4 d-block text-start">
                                <i class="bg-danger"></i>
                                <span class="text-dark text-xs">PL Pokok</span>
                            </span>
                            <span class="badge badge-md badge-dot me-4 d-block text-start">
                                <i class="bg-warning"></i>
                                <span class="text-dark text-xs">PL Jasa</span>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-footer pt-0 pb-2 p-3 d-flex align-items-center">
                    <div class="w-60">
                        <div class="text-sm">
                            Total Angsuran
                            <div>
                                <b>Rp. <span id="total_angsur"></span></b>
                            </div>
                        </div>
                    </div>
                    <div class="w-40 text-end">
                        {{-- <button type="button" id="btnDetailAngsuran" class="btn bg-light mb-0 text-end">Detail</button> --}}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8 col-sm-6 mt-sm-0 mt-4">
            <div class="card">
                <div class="card-header pb-0 p-3">
                    <div class="d-flex justify-content-between">
                        <h6 class="mb-0">Pendapatan dan Beban</h6>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge badge-md badge-dot me-4">
                            <i class="bg-success"></i>
                            <span class="text-dark text-xs">Pendapatan</span>
                        </span>
                        <span class="badge badge-md badge-dot me-4">
                            <i class="bg-warning"></i>
                            <span class="text-dark text-xs">Beban</span>
                        </span>
                        <span class="badge badge-md badge-dot me-4">
                            <i class="bg-info"></i>
                            <span class="text-dark text-xs">Laba</span>
                        </span>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="chart">
                        <canvas id="chart-line" class="chart-canvas" height="400"
                            style="display: block; box-sizing: border-box; height: 210px; width: 844.4px;"
                            width="1688"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Detail Angsuran Harian --}}
    <div class="modal fade" id="DetailAngsuranHarian" aria-labelledby="DetailAngsuranHarianLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="DetailAngsuranHarianLabel">
                        Detail Angsuran Hari Ini
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-striped">
                        <thead>
                            <tr class="bg-dark text-white">
                                <th>Jenis</th>
                                <th>SPP</th>
                                <th>UEP</th>
                                <th>PL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Angsuran</td>
                                <td align="right">
                                    Rp. {{ number_format($pokok_spp + $jasa_spp) }}
                                </td>
                                <td align="right">
                                    Rp. {{ number_format($pokok_uep + $jasa_uep) }}
                                </td>
                                <td align="right">
                                    Rp. {{ number_format($pokok_pl + $jasa_pl) }}
                                </td>
                            </tr>
                            <tr>
                                <td>Pencairan</td>
                                <td align="right">
                                    Rp. {{ number_format($pencairan_spp) }}
                                </td>
                                <td align="right">
                                    Rp. {{ number_format($pencairan_uep) }}
                                </td>
                                <td align="right">
                                    Rp. {{ number_format($pencairan_pl) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>Total</td>
                                <td align="right">
                                    Rp.
                                    {{ number_format($pokok_spp + $jasa_spp - $pencairan_spp) }}
                                </td>
                                <td align="right">
                                    Rp.
                                    {{ number_format($pokok_uep + $jasa_uep - $pencairan_uep) }}
                                </td>
                                <td align="right">
                                    Rp.
                                    {{ number_format($pokok_pl + $jasa_pl - $pencairan_pl) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Jatuh Dempo --}}
    <div class="modal fade" id="jatuhTempo" aria-labelledby="jatuhTempoLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="jatuhTempoLabel">Jatuh Tempo</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="nav-wrapper position-relative end-0">
                        <ul class="nav nav-pills nav-fill p-1" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link mb-0 px-0 py-1 active text-danger" data-bs-toggle="tab"
                                    href="#tagihan_hari_ini" role="tab" aria-controls="tagihan_hari_ini"
                                    aria-selected="true">
                                    Hari Ini
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link mb-0 px-0 py-1 text-warning" data-bs-toggle="tab" href="#menunggak"
                                    role="tab" aria-controls="menunggak" aria-selected="false">
                                    Menunggak
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link mb-0 px-0 py-1 text-info" data-bs-toggle="tab" href="#tagihan"
                                    role="tab" aria-controls="tagihan" aria-selected="false">
                                    Tagihan
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content mt-2">
                            <div class="tab-pane fade show active" id="tagihan_hari_ini" role="tabpanel"
                                aria-labelledby="tagihan_hari_ini">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped midle" width="100%">
                                                <thead>
                                                    <tr>
                                                        <td align="center">No</td>
                                                        <td align="center">Nama Kelompok</td>
                                                        <td align="center">Tanggal Cair</td>
                                                        <td align="center">Alokasi</td>
                                                        <td align="center">Tunggakan Pokok</td>
                                                        <td align="center">Tunggakan Jasa</td>
                                                    </tr>
                                                </thead>
                                                <tbody id="TbHariIni"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="menunggak" role="tabpanel" aria-labelledby="menunggak">
                                <div class="card">
                                    <div class="card-body">

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="col-12 col-lg-4">
                                                    <div class="my-0">
                                                        <select class="form-control" name="tahun_cair" id="tahun_cair">
                                                            <option value="-" selected>Semua</option>
                                                            @for ($i = date('Y'); $i >= 2000; $i--)
                                                                <option value="{{ $i }}">
                                                                    {{ $i }}
                                                                </option>
                                                            @endfor
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="d-flex justify-content-end">
                                                    <div class="col-12 col-lg-4">
                                                        <div class="input-group input-group-outline">
                                                            <label class="form-label" for="cari_menunggak">
                                                                <span>
                                                                    <i class="fas fa-search"></i>
                                                                    <span class="ms-1">Cari Nama Kelompok</span>
                                                                </span>
                                                            </label>
                                                            <input type="text" name="cari_menunggak"
                                                                id="cari_menunggak" class="form-control">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <form action="/perguliran/dokumen?status=A&jenis=dokumen_pencairan"
                                            target="_blank" method="post" id="formCetakTagihan">
                                            @csrf

                                            <input type="hidden" name="id" id="id_tagihan">
                                            <input type="hidden" name="report" value="suratTagihan#pdf">
                                        </form>

                                        <div class="table-responsive">
                                            <table class="table table-striped midle" width="100%">
                                                <thead>
                                                    <tr>
                                                        <td align="center">No</td>
                                                        <td align="center">Nama Kelompok</td>
                                                        <td align="center">Tanggal Cair</td>
                                                        <td align="center">Alokasi</td>
                                                        <td align="center">Tunggakan Pokok</td>
                                                        <td align="center">Tunggakan Jasa</td>
                                                        <td align="center">Tagihan</td>
                                                    </tr>
                                                </thead>
                                                <tbody id="TbMenunggak"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="tagihan" role="tabpanel" aria-labelledby="tagihan">
                                <div class="card">
                                    <div class="card-body p-3">
                                        <form action="/dashboard/tagihan" method="post" id="formTagihan">
                                            @csrf

                                            <div class="alert alert-info text-white">
                                                Kirim Whatsapp Tagihan berdasarkan tanggal jatuh tempo. Pastikan nomor HP
                                                kelompok dapat menerima pesan Whatsapp.
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="input-group input-group-static mb-3">
                                                        <label for="tgl_tagihan">Tgl Jatuh Tempo</label>
                                                        <input autocomplete="off" type="text" name="tgl_tagihan"
                                                            id="tgl_tagihan" class="form-control date pesan"
                                                            value="{{ date('d/m/Y') }}">
                                                        <small class="text-danger" id="msg_tgl_tagihan"></small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="input-group input-group-static mb-3">
                                                        <label for="tgl_pembayaran">Tgl Pembayaran</label>
                                                        <input autocomplete="off" type="text" name="tgl_pembayaran"
                                                            id="tgl_pembayaran" class="form-control date pesan"
                                                            value="{{ date('d/m/Y') }}">
                                                        <small class="text-danger" id="msg_tgl_pembayaran"></small>
                                                    </div>
                                                </div>
                                            </div>

                                            <textarea class="form-control d-none" name="pesan_whatsapp" id="pesan_whatsapp"></textarea>
                                        </form>

                                        <div class="d-flex justify-content-end">
                                            <button type="button" id="CekTagihan" class="btn btn-sm btn-github"
                                                {{ strlen($user->hp) >= 10 ? '' : 'disabled' }}>Preview</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    @if (in_array('dashboard.print_dokumen', Session::get('tombol')))
                        <button type="button" class="btn btn-info btn-sm me-2 btn-pelaporan">
                            Print
                        </button>
                    @endif
                    <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Pinjaman --}}
    <div class="modal fade" id="pinjaman" aria-labelledby="pinjamanLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="pinjamanLabel">Pinjaman</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="nav-wrapper position-relative end-0">
                        <ul class="nav nav-pills nav-fill p-1" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link mb-0 px-0 py-1 active text-info" data-bs-toggle="tab" href="#proposal"
                                    role="tab" aria-controls="proposal" aria-selected="true">
                                    Proposal
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link mb-0 px-0 py-1 text-danger" data-bs-toggle="tab" href="#verifikasi"
                                    role="tab" aria-controls="verifikasi" aria-selected="false">
                                    Verifikasi
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link mb-0 px-0 py-1 text-warning" data-bs-toggle="tab" href="#waiting"
                                    role="tab" aria-controls="waiting" aria-selected="false">
                                    Waiting
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content mt-2">
                            <div class="tab-pane fade show active" id="proposal" role="tabpanel"
                                aria-labelledby="proposal">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped midle" width="100%">
                                                <thead>
                                                    <tr>
                                                        <td align="center">No</td>
                                                        <td align="center">Tanggal Proposal</td>
                                                        <td align="center">Nama Kelompok</td>
                                                        <td align="center">Alokasi</td>
                                                        <td align="center">Anggota</td>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbProposal"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="verifikasi" role="tabpanel" aria-labelledby="verifikasi">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped midle" width="100%">
                                                <thead>
                                                    <tr>
                                                        <td align="center">No</td>
                                                        <td align="center">Tanggal Verifikasi</td>
                                                        <td align="center">Nama Kelompok</td>
                                                        <td align="center">Alokasi</td>
                                                        <td align="center">Anggota</td>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbVerifikasi"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="waiting" role="tabpanel" aria-labelledby="waiting">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped midle" width="100%">
                                                <thead>
                                                    <tr>
                                                        <td align="center">No</td>
                                                        <td align="center">Tanggal Tunggu</td>
                                                        <td align="center">Nama Kelompok</td>
                                                        <td align="center">Alokasi</td>
                                                        <td align="center">Anggota</td>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbWaiting"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    @if (in_array('dashboard.print_dokumen', Session::get('tombol')))
                        <button type="button" class="btn btn-info btn-sm me-2 btn-pelaporan">
                            Print
                        </button>
                    @endif
                    <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Kelompok Aktif --}}
    <div class="modal fade" id="aktif" aria-labelledby="aktifLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="aktifLabel">Pinjaman Aktif</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="nav-wrapper position-relative end-0">
                        <ul class="nav nav-pills nav-fill p-1" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link mb-0 px-0 py-1 active text-info" data-bs-toggle="tab"
                                    href="#kelompok_aktif" role="tab" aria-controls="kelompok_aktif"
                                    aria-selected="true">
                                    Kelompok
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link mb-0 px-0 py-1 text-danger" data-bs-toggle="tab"
                                    href="#pemanfaat_aktif" role="tab" aria-controls="pemanfaat_aktif"
                                    aria-selected="false">
                                    Pemanfaat
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content mt-2">
                            <div class="tab-pane fade show active" id="kelompok_aktif" role="tabpanel"
                                aria-labelledby="kelompok_aktif">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped midle" width="100%">
                                                <thead>
                                                    <tr>
                                                        <td align="center">No</td>
                                                        <td align="center">Tanggal Cair</td>
                                                        <td align="center">Nama Kelompok</td>
                                                        <td align="center">Alokasi</td>
                                                        <td align="center">Saldo</td>
                                                        <td align="center">Anggota</td>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbKelompok"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="pemanfaat_aktif" role="tabpanel"
                                aria-labelledby="pemanfaat_aktif">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped midle" width="100%">
                                                <thead>
                                                    <tr>
                                                        <td align="center">No</td>
                                                        <td align="center">Nama Kelompok</td>
                                                        <td align="center">Nik</td>
                                                        <td align="center">Nama Anggota</td>
                                                        <td align="center">Alamat</td>
                                                        <td align="center">Tanggal Cair</td>
                                                        <td align="center">Alokasi</td>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbPemanfaat"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    @if (in_array('dashboard.print_dokumen', Session::get('tombol')))
                        <button type="button" class="btn btn-info btn-sm me-2 btn-pelaporan">
                            Print
                        </button>
                    @endif
                    <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Detail Angsuran --}}
    <div class="modal fade" id="detailAngsuran" aria-labelledby="detailAngsuranLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="detailAngsuranLabel">Pinjaman detailAngsuran</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tagihan --}}
    <div class="modal fade" id="tagihanPinjaman" aria-labelledby="tagihanPinjamanLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="tagihanPinjamanLabel">Tagihan</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="TbTagihan"></div>
                </div>
                <div class="modal-footer">
                    @if (in_array('dashboard.kirim_pesan_whatsapp', Session::get('tombol')))
                        <button type="button" class="btn btn-github btn-sm" id="KirimPesan">Kirim Pesan</button>
                    @endif
                    <button type="button" class="btn btn-danger btn-sm" id="closeTagihan">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <form action="/pelaporan/preview" method="post" id="FormLaporanDashboard" target="_blank">
        @csrf

        <input type="hidden" name="type" id="type" value="pdf">
        <input type="hidden" name="tahun_pinjaman_cair" id="tahun_pinjaman_cair" value="-">
        <input type="hidden" name="tahun" id="tahun" value="{{ date('Y') }}">
        <input type="hidden" name="bulan" id="bulan" value="{{ date('m') }}">
        <input type="hidden" name="hari" id="hari" value="{{ date('d') }}">
        <input type="hidden" name="laporan" id="laporan" value="">
        <input type="hidden" name="sub_laporan" id="sub_laporan" value="">
    </form>

    <textarea name="msgInvoice" id="msgInvoice" class="d-none">{{ Session::get('msg') }}</textarea>
@endsection

@section('script')
    <script>
        $(".date").flatpickr({
            dateFormat: "d/m/Y"
        })

        new Choices($('select#tahun_cair')[0], {
            shouldSort: false,
            fuseOptions: {
                threshold: 0.1,
                distance: 1000
            }
        })

        let dataTunggakan = [];

        $.ajax({
            type: 'post',
            url: '/dashboard/jatuh_tempo',
            data: $('#defaultForm').serialize(),
            success: function(result) {
                if (result.success) {
                    $('#jatuh_tempo').html(result.jatuh_tempo)

                    if (result.jatuh_tempo != '00') {
                        $('#TbHariIni').html(result.hari_ini)
                    }
                }
            }
        })

        function getTunggakan(tahun = '-') {
            $('#cari_menunggak').val('')

            $.ajax({
                type: 'post',
                url: '/dashboard/nunggak?tahun=' + tahun,
                data: $('#defaultForm').serialize(),
                success: function(result) {
                    if (result.success) {
                        $('#nunggak').html(result.nunggak)

                        if (result.nunggak > 0) {
                            dataTunggakan = result.data

                            setTabelTunggakan(dataTunggakan)
                        }
                    }
                }
            })
        }

        getTunggakan()

        $(document).on('keyup', '#cari_menunggak', function() {
            let cari = $(this).val()

            if (cari.length > 0) {
                var data = dataTunggakan.filter((item) => {
                    return item.nama_kelompok.toLowerCase().includes(cari.toLowerCase())
                })

                setTabelTunggakan(data)
            } else {
                setTabelTunggakan(dataTunggakan)
            }
        })

        $(document).on('change', '#tahun_cair', function() {
            $('#tahun_pinjaman_cair').val($(this).val())

            getTunggakan($(this).val())
        })

        function setTabelTunggakan(data) {
            $('#TbMenunggak').html('')

            if (data.length == 0) {
                $('#TbMenunggak').append(`
                    <tr>
                        <td colspan="7" align="center">Tidak ada tunggakan</td>
                    </tr>
                `)
            } else {
                data.forEach((item, index) => {
                    $('#TbMenunggak').append(`
                        <tr>
                            <td align="center">${index + 1}</td>
                            <td>${item.nama_kelompok} [${item.desa}] (${item.id})</td>
                            <td align="center">${item.tgl_cair}</td>
                            <td align="right">${item.alokasi}</td>
                            <td align="right">${item.tunggakan_pokok}</td>
                            <td align="right">${item.tunggakan_jasa}</td>
                            <td align="center"><button type="button" class="btn btn-linkedin btn-sm mb-0 btn-cetak-tagihan" data-id="${item.id}"><i class="fas fa-file"></i></button></td>
                        </tr>
                    `)
                })
            }
        }

        $(document).on('click', '.btn-cetak-tagihan', function() {
            var id = $(this).attr('data-id')

            $('#id_tagihan').val(id)
            $('#formCetakTagihan').submit()
        })

        function tagihan() {
            var tgl_tagihan = $('#tgl_tagihan').val()

            $.ajax({
                url: '/dashboard/tagihan',
                type: 'post',
                data: $('#formTagihan').serialize(),
                success: function(result) {
                    if (result.success) {
                        $('#TbTagihan').html(result.tagihan)
                        $('#tagihanPinjaman').modal('show')
                    }
                }
            })
        }

        $(document).on('click', '#CekTagihan', function(e) {
            e.preventDefault()

            tagihan()
        })

        $(document).on('click', '#closeTagihan', function() {
            $('#tagihanPinjaman').modal('hide')
            $('#jatuhTempo').modal('show')
        })

        $(document).on('click', '#btnDetailAngsuranHarian', function() {
            $('#DetailAngsuranHarian').modal('show')
        })

        $.get('/dashboard/pinjaman?status=P', function(result) {
            if (result.success) {
                $('#tbProposal').html(result.table)
            }
        })

        $.get('/dashboard/pinjaman?status=V', function(result) {
            if (result.success) {
                $('#tbVerifikasi').html(result.table)
            }
        })

        $.get('/dashboard/pinjaman?status=W', function(result) {
            if (result.success) {
                $('#tbWaiting').html(result.table)
            }
        })

        $.get('/dashboard/pinjaman?status=A', function(result) {
            if (result.success) {
                $('#tbKelompok').html(result.table)
            }
        })

        $.get('/dashboard/pemanfaat?status=A', function(result) {
            if (result.success) {
                $('#tbPemanfaat').html(result.table)
            }
        })

        $(document).on('click', '#KirimPesan', function(e) {
            e.preventDefault()

            var messages = [];
            $('[data-input=checked]:checked').each(function(i) {
                var pesan = this.value

                var number = pesan.split('||')[0]
                var kelompok = pesan.split('||')[1]
                var msg = pesan.split('||')[2]

                if (!number.startsWith('08') && !number.startsWith('628')) {
                    number = '0' + number;
                }

                messages.push({
                    number,
                    message: msg
                })
            });

            const INSTANCE = '{{ $wa_instance_name }}'
            console.log('Sending message via Gateway:', {
                instance: INSTANCE,
                url: '{{ $api }}/message/sendText/' + INSTANCE
            });

            // Send sequentially with random delay 1500-3500ms to avoid WhatsApp spam detection
            (async function sendSequential() {
                let success = 0
                let failed = 0
                for (let i = 0; i < messages.length; i++) {
                    const m = messages[i]
                    const randomDelay = 1500 + Math.floor(Math.random() * 2000)

                    try {
                        await $.ajax({
                            type: 'POST',
                            url: '{{ $api }}/message/sendText/' + INSTANCE,
                            headers: {
                                'apikey': '{{ $api_key }}'
                            },
                            contentType: 'application/json',
                            data: JSON.stringify({
                                number: m.number,
                                text: m.message,
                                delay: randomDelay
                            })
                        })
                        success++
                    } catch (err) {
                        failed++
                        console.error('Send failed for', m.number, err)
                    }
                }

                if (failed === 0) {
                    Swal.fire('Berhasil', success + ' pesan berhasil dikirim', 'success')
                } else if (success === 0) {
                    Swal.fire('Error', 'Semua pesan gagal dikirim', 'error')
                } else {
                    Swal.fire('Sebagian Gagal', success + ' terkirim, ' + failed + ' gagal', 'warning')
                }
            })()
        })

        $(document).on('click', '#btnjatuhTempo', function(e) {
            e.preventDefault()

            $('#jatuhTempo').modal('show')


            let tab = $('#jatuhTempo').find('ul li a.active')
            if (tab.length > 0) {
                if (tab.attr('aria-controls') != 'tagihan') {
                    $('.btn-pelaporan').show()
                    setLaporan('5', tab.attr('aria-controls'))
                } else {
                    $('.btn-pelaporan').hide()
                }
            }
        })

        $(document).on('click', '#btnpinjaman', function(e) {
            e.preventDefault()

            $('#pinjaman').modal('show')
            $('.btn-pelaporan').show()

            let tab = $('#pinjaman').find('ul li a.active')
            if (tab.length > 0) {
                setLaporan('5', tab.attr('aria-controls'))
            }
        })

        $(document).on('click', '#btnAktif', function(e) {
            e.preventDefault()

            $('#aktif').modal('show')
            $('.btn-pelaporan').show()

            let tab = $('#aktif').find('ul li a.active')
            if (tab.length > 0) {
                setLaporan('5', tab.attr('aria-controls'))
            }
        })

        $(document).on('click', '.nav.nav-pills .nav-item', function() {
            var a = $(this).find('a')

            if (a.attr('aria-controls') == 'tagihan') {
                $('.btn-pelaporan').hide()
            } else {
                $('.btn-pelaporan').show()
            }
            setLaporan('5', a.attr('aria-controls'))
        })

        $(document).on('click', '.btn-pelaporan', function(e) {
            $('#FormLaporanDashboard').submit()
        })

        function setLaporan(laporan, subLaporan = null) {
            $('#FormLaporanDashboard #laporan').val(laporan);
            $('#FormLaporanDashboard #sub_laporan').val(subLaporan);
        }
    </script>

    @if (Session::get('invoice'))
        <script>
            function msgInvoice(number, msg, repeat = 0) {
                const INSTANCE = '{{ $wa_instance_name }}'
                const randomDelay = 1500 + Math.floor(Math.random() * 2000)
                $.ajax({
                    type: 'POST',
                    url: '{{ $api }}/message/sendText/' + INSTANCE,
                    timeout: 0,
                    headers: {
                        "Content-Type": "application/json",
                        "apikey": "{{ $api_key }}"
                    },
                    data: JSON.stringify({
                        number: number,
                        text: msg,
                        delay: randomDelay
                    }),
                    success: function(result) {
                        if (!result.success) {
                            setTimeout(function() {
                                msgInvoice(number, msg, repeat + 1)
                            }, 2000)
                        }
                    },
                    error: function(result) {
                        if (repeat < 1) {
                            setTimeout(function() {
                                msgInvoice(number, msg, repeat + 1)
                            }, 2000)
                        }
                    }
                })
            }

            msgInvoice("{{ Session::get('hp_dir') }}", $('#msgInvoice').val())
            setTimeout(() => {
                msgInvoice('0882006644656', $('#msgInvoice').val())
            }, 2500);
        </script>
    @endif

    <script>
        var formatter = new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })

        var DataLineChart = JSON.parse(@json($charts));
        var ctx1 = document.getElementById("chart-line").getContext("2d");
        var ctx2 = document.getElementById("chart-pie").getContext("2d");

        // Line chart
        new Chart(ctx1, {
            type: "line",
            data: {
                labels: DataLineChart.nama_bulan,
                datasets: [{
                        label: "Pendapatan",
                        tension: 0,
                        pointRadius: 5,
                        pointBackgroundColor: "#4CAF50",
                        pointBorderColor: "transparent",
                        borderColor: "#4CAF50",
                        borderWidth: 2,
                        backgroundColor: "transparent",
                        fill: true,
                        data: DataLineChart.pendapatan,
                        maxBarThickness: 6
                    },
                    {
                        label: "Beban",
                        tension: 0,
                        borderWidth: 0,
                        pointRadius: 5,
                        pointBackgroundColor: "#fb8c00",
                        pointBorderColor: "transparent",
                        borderColor: "#fb8c00",
                        borderWidth: 2,
                        backgroundColor: "transparent",
                        fill: true,
                        data: DataLineChart.beban,
                        maxBarThickness: 6
                    },
                    {
                        label: "Laba",
                        tension: 0,
                        borderWidth: 0,
                        pointRadius: 5,
                        pointBackgroundColor: "#1A73E8",
                        pointBorderColor: "transparent",
                        borderColor: "#1A73E8",
                        borderWidth: 2,
                        backgroundColor: "transparent",
                        fill: true,
                        data: DataLineChart.surplus,
                        maxBarThickness: 6
                    }
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                scales: {
                    y: {
                        grid: {
                            drawBorder: false,
                            display: true,
                            drawOnChartArea: true,
                            drawTicks: false,
                            borderDash: [5, 5],
                            color: '#c1c4ce5c'
                        },
                        ticks: {
                            display: true,
                            padding: 10,
                            color: '#9ca2b7',
                            font: {
                                size: 14,
                                weight: 300,
                                family: "Roboto",
                                style: 'normal',
                                lineHeight: 2
                            },
                        }
                    },
                    x: {
                        grid: {
                            drawBorder: false,
                            display: true,
                            drawOnChartArea: true,
                            drawTicks: true,
                            borderDash: [5, 5],
                            color: '#c1c4ce5c'
                        },
                        ticks: {
                            display: true,
                            color: '#9ca2b7',
                            padding: 10,
                            font: {
                                size: 14,
                                weight: 300,
                                family: "Roboto",
                                style: 'normal',
                                lineHeight: 2
                            },
                        }
                    },
                },
            },
        });

        // Pie chart
        new Chart(ctx2, {
            type: "pie",
            data: {
                labels: [
                    'SPP Pokok',
                    'SPP Jasa',
                    'UEP Pokok',
                    'UEP Jasa',
                    'PL Pokok',
                    'PL Jasa'
                ],
                datasets: [{
                    label: "Projects",
                    weight: 9,
                    cutout: 0,
                    tension: 0.9,
                    pointRadius: 2,
                    borderWidth: 1,
                    backgroundColor: [
                        '#1a73e8',
                        '#4caf50',
                        '#344767',
                        '#7b809a',
                        '#f44335',
                        '#fb8c00',
                    ],
                    data: [
                        "{{ $pokok_spp }}",
                        "{{ $jasa_spp }}",
                        "{{ $pokok_uep }}",
                        "{{ $jasa_uep }}",
                        "{{ $pokok_pl }}",
                        "{{ $jasa_pl }}",
                    ],
                    fill: false
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                scales: {
                    y: {
                        grid: {
                            drawBorder: false,
                            display: false,
                            drawOnChartArea: false,
                            drawTicks: false,
                            color: '#c1c4ce5c'
                        },
                        ticks: {
                            display: false
                        }
                    },
                    x: {
                        grid: {
                            drawBorder: false,
                            display: false,
                            drawOnChartArea: false,
                            drawTicks: false,
                            color: '#c1c4ce5c'
                        },
                        ticks: {
                            display: false,
                        }
                    },
                },
            },
        });

        var total_angsur =
            "{{ $pokok_spp + $jasa_spp + $pokok_uep + $jasa_uep + $pokok_pl + $jasa_pl }}"

        $('#total_angsur').html(formatter.format(total_angsur))

        let childWindow, loading;
        $(document).on('click', '#simpanSaldo', function(e) {
            var link = $(this).attr('data-href')

            loading = Swal.fire({
                title: "Mohon Menunggu..",
                html: "Menyimpan Saldo Januari sampai Desember Th. {{ date('Y') }}",
                timerProgressBar: true,
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            })

            childWindow = window.open(link, '_blank');
        })

        window.addEventListener('message', function(event) {
            if (event.data === 'closed') {
                loading.close()
                window.location.reload()
            }
        })
    </script>
@endsection
