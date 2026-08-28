@php
    use App\Utils\Tanggal;
    $waktu = date('H:i');
    $tempat = '';

    $sum_pokok = 0;
    if ($real) {
        $sum_pokok = $real->sum_pokok;
    }

    $saldo_pokok = $perguliran->alokasi - $sum_pokok;
    if ($saldo_pokok < 0) {
        $saldo_pokok = 0;
    }

    $jenis_pinjaman = '';
    if ($perguliran->jenis_pp != '3') {
        $jenis_pinjaman = 'Kelompok';
    }

    $dokumen_proposal = [];
    $dokumen_verifikasi = [];
    $dokumen_pencairan = [];
    foreach ($dokumenPinjaman as $dokumen) {
        $daftarDokumen = [
            'title' => $dokumen->title,
            'file' => $dokumen->file,
            'withExcel' => $dokumen->excel,
        ];

        if ($dokumen->jenis_dokumen == 'dokumen_proposal') {
            $dokumen_proposal[] = $daftarDokumen;
        }

        if ($dokumen->jenis_dokumen == 'dokumen_verifikasi') {
            $dokumen_verifikasi[] = $daftarDokumen;
        }

        if ($dokumen->jenis_dokumen == 'dokumen_pencairan') {
            $dokumen_pencairan[] = $daftarDokumen;
        }
    }

    $TombolEdit = false;
    if (in_array('tahapan_perguliran.proposal.edit_proposal', Session::get('tombol'))) {
        $TombolEdit = true;
    }

    $TombolHapus = false;
    if (in_array('tahapan_perguliran.proposal.hapus_proposal', Session::get('tombol'))) {
        $TombolHapus = true;
    }
@endphp

@extends('layouts.base')

@section('content')
    <div class="card mb-3">
        <div class="card-body p-3">
            <h5 class="mb-1">
                {{ $jenis_pinjaman }} {{ $perguliran->kelompok->nama_kelompok }} Loan ID. {{ $perguliran->id }}
                ({{ $perguliran->jpp->nama_jpp }})
            </h5>
            <p class="mb-0">
                <span
                    class="badge badge-{{ $perguliran->sts->warna_status }}">{{ $perguliran->kelompok->kd_kelompok }}</span>
                <span
                    class="badge badge-{{ $perguliran->sts->warna_status }}">{{ $perguliran->kelompok->alamat_kelompok }}</span>
                <span class="badge badge-{{ $perguliran->sts->warna_status }}">
                    {{ $perguliran->kelompok->d->sebutan_desa->sebutan_desa }}
                    {{ $perguliran->kelompok->d->nama_desa }}
                </span>
            </p>
        </div>
    </div>

    @if ($perguliran->status == 'P' && ($TombolEdit || $TombolHapus))
        <div class="card mb-3">
            <div class="card-body p-2 pb-0">
                @if ($TombolEdit)
                    <button type="button" class="btn btn-success btn-sm mb-2" id="BtnEditProposal">Edit Proposal</button>
                @endif
                @if ($TombolHapus)
                    <button type="button" id="HapusProposal" class="btn btn-danger btn-sm mb-2">Hapus Proposal</button>
                @endif
            </div>
        </div>
    @endif

    <div id="layout">

    </div>

    <div class="card mt-3">
        <div class="card-body p-2">
            <div class="d-flex justify-content-end">
                @if (in_array($perguliran->status, ['W', 'A']))
                    <button type="button" id="sinkronisasi" class="btn btn-sm btn-facebook btn-icon mb-0">
                        <span class="btn-inner--icon"><i class="fas fa-sync"></i></span>
                        <span class="btn-inner--text">Sinkronisasi</span>
                    </button>
                @endif

                @if ($perguliran->status == 'L' || $perguliran->status == 'H')
                    @if (
                        $perguliran->status != 'H' &&
                            in_array('tahapan_perguliran.lunas.cetak_keterangan_pelunasan', Session::get('tombol')))
                        <button class="btn btn-warning btn-sm ms-2"
                            onclick="window.open('/cetak_keterangan_lunas/{{ $perguliran->id }}')" type="button">
                            <i class="fa fa-print"></i> Cetak Keterangan Pelunasan
                        </button>
                    @endif

                    <a href="/database/kelompok/{{ $perguliran->kelompok->kd_kelompok }}"
                        class="btn btn-info btn-sm mb-0 ms-2">Kembali</a>
                @else
                    <a href="/perguliran?status={{ $perguliran->status }}"
                        class="btn btn-info btn-sm mb-0 ms-2">Kembali</a>

                    @if (
                        !in_array('tahapan_perguliran.verifikasi.simpan_keputusan_pendanaan', Session::get('tombol')) &&
                            $perguliran->status == 'V')
                        @if (in_array('tahapan_perguliran.verifikasi.tidak_layak_dicairkan', Session::get('tombol')))
                            <button type="button" id="tidakLayakDicairkan" class="btn btn-danger btn-sm ms-2">
                                Tidak Layak Dicairkan
                            </button>
                        @endif

                        @if (in_array('tahapan_perguliran.verifikasi.kembalikan_ke_proposal', Session::get('tombol')))
                            <button type="button" id="kembaliProposal" class="btn btn-warning btn-sm ms-2 mb-2">
                                Kembalikan Ke Proposal
                            </button>
                        @endif
                    @endif

                    @if (!in_array('tahapan_perguliran.waiting.pencairan', Session::get('tombol')) && $perguliran->status == 'W')
                        @if (in_array('tahapan_perguliran.waiting.tidak_layak_dicairkan', Session::get('tombol')))
                            <button type="button" id="tidakLayakDicairkan" class="btn btn-danger btn-sm ms-2">
                                Tidak Layak Dicairkan
                            </button>
                        @endif

                        @if (in_array('tahapan_perguliran.waiting.kembalikan_ke_proposal', Session::get('tombol')))
                            <button type="button" id="kembaliProposal" class="btn btn-warning btn-sm ms-2 mb-2">
                                Kembalikan Ke Proposal
                            </button>
                        @endif
                    @endif
                @endif
            </div>
        </div>
    </div>

    {{-- Modal Edit Proposal --}}
    <div class="modal fade" id="EditProposal" tabindex="-1" aria-labelledby="EditProposalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="EditProposalLabel">
                        Edit Proposal {{ $jenis_pinjaman }} {{ $perguliran->kelompok->nama_kelompok }} Loan ID.
                        {{ $perguliran->id }}
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="LayoutEditProposal">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" id="SimpanEditProposal" class="btn btn-github btn-sm">Simpan Perubahan</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Cetak Dokumen Proposal --}}
    <div class="modal fade" id="CetakDokumenProposal" tabindex="-1" aria-labelledby="CetakDokumenProposalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="CetakDokumenProposalLabel">Cetak Dokumen Proposal</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="/perguliran/simpan_data/{{ $perguliran->id }}?jenis=dokumen_proposal&save=true"
                        method="post" id="simpanDataProposal">
                        @csrf
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="input-group input-group-outline my-3">
                                    <label class="form-label" for="tglProposal">Tanggal Proposal</label>
                                    <input autocomplete="off" type="text" name="tglProposal" id="tglProposal"
                                        class="form-control date simpan-proposal"
                                        value="{{ Tanggal::tglIndo($perguliran->tgl_proposal) }}">
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="input-group input-group-outline my-3">
                                    <label class="form-label" for="tglVerifikasi">Tanggal Verifikasi</label>
                                    <input autocomplete="off" type="text" name="tglVerifikasi" id="tglVerifikasi"
                                        class="form-control date simpan-proposal"
                                        value="{{ Tanggal::tglIndo($perguliran->tgl_verifikasi) }}">
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="input-group input-group-outline my-3">
                                    <label class="form-label" for="waktuVerifikasi">Waktu Verifikasi</label>
                                    <input autocomplete="off" type="text" name="waktuVerifikasi" id="waktuVerifikasi"
                                        class="form-control time simpan-proposal"
                                        value="{{ $perguliran->waktu_verifikasi ?: date('H:') . '00' }}">
                                </div>
                            </div>
                        </div>
                    </form>

                    <form action="/perguliran/dokumen?status=P&jenis=dokumen_proposal" target="_blank" method="post">
                        @csrf

                        <input type="hidden" name="id" value="{{ $perguliran->id }}">
                        <div class="row">
                            @foreach ($dokumen_proposal as $doc => $val)
                                @php
                                    $label = $loop->iteration . '. ' . $val['title'];
                                    if (Session::get('lokasi') == '372' && $val['file'] == 'ba_musyawarah') {
                                        $label = $loop->iteration . '. .....';
                                    }
                                @endphp
                                <div class="col-md-3 d-grid">
                                    @if ($val['withExcel'])
                                        <div class="btn-group">
                                            <button class="btn btn-linkedin btn-sm text-start" type="submit"
                                                name="report" value="{{ $val['file'] }}#pdf">
                                                {{ $label }}
                                            </button>
                                            <button class="btn btn-icon btn-sm btn-instagram" type="submit"
                                                name="report" value="{{ $val['file'] }}#excel">
                                                <i class="fas fa-file-excel"></i>
                                            </button>
                                        </div>
                                    @else
                                        <button class="btn btn-linkedin btn-sm text-start" type="submit" name="report"
                                            value="{{ $val['file'] }}#pdf">
                                            {{ $label }}
                                        </button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    @php
        $readonly = 'readonly';
        if ($perguliran->status == 'W') {
            $readonly = '';
        }

        $wt_cair = explode('_', $perguliran->wt_cair);
        if (count($wt_cair) == 1) {
            $waktu = $wt_cair[0];
        }

        if (count($wt_cair) == 2) {
            $waktu = $wt_cair[0];
            $tempat = $wt_cair[1];
        }
    @endphp

    {{-- Modal Cetak Dokumen Pencairan --}}
    <div class="modal fade" id="CetakDokumenPencairan" tabindex="-1" aria-labelledby="CetakDokumenPencairanLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="CetakDokumenPencairanLabel">Cetak Dokumen Pencairan</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="/perguliran/simpan_data/{{ $perguliran->id }}?jenis=dokumen_pencairan&save=true"
                        method="post" id="simpanDataPencairan">
                        @csrf

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="input-group input-group-outline my-3">
                                    <label class="form-label" for="spk_no">Nomor SPK</label>
                                    <input autocomplete="off" type="text" name="spk_no" id="spk_no"
                                        class="form-control simpan-pencairan" {{ $readonly }}
                                        value="{{ $perguliran->spk_no }}">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="input-group input-group-outline my-3">
                                    <label class="form-label" for="tempat">Tempat</label>
                                    <input autocomplete="off" type="text" name="tempat" id="tempat"
                                        class="form-control simpan-pencairan" {{ $readonly }}
                                        value="{{ $tempat }}">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="input-group input-group-outline my-3">
                                    <label class="form-label" for="tgl_cair">Tanggal Cair</label>
                                    <input autocomplete="off" type="text" name="tgl_cair" id="tgl_cair"
                                        class="form-control date simpan-pencairan" {{ $readonly }}
                                        value="{{ Tanggal::tglIndo($perguliran->tgl_cair) }}">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="input-group input-group-outline my-3">
                                    <label class="form-label" for="waktu">Waktu</label>
                                    <input autocomplete="off" type="text" name="waktu" id="waktu"
                                        class="form-control simpan-pencairan" {{ $readonly }}
                                        value="{{ $waktu }}">
                                </div>
                            </div>
                        </div>
                    </form>

                    @if ($perguliran->status == 'W')
                        @if ($pinj_a['jumlah_pinjaman'] > 0)
                            <div class="alert border border-danger text-danger" role="alert">
                                <span class="text-sm">
                                    <b>Anggota Kelompok</b>
                                    terdeteksi memiliki kewajiban angsuran pinjaman
                                </span>
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th align="center" width="10"><span class="text-danger">No</span></th>
                                            <th align="center"><span class="text-danger">Nama</span></th>
                                            <th><span class="text-danger">Loan ID.</span></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($pinj_a['pinjaman'] as $pa)
                                            <tr>
                                                <td align="center">
                                                    <span class="text-danger">
                                                        {{ $loop->iteration }}
                                                    </span>
                                                </td>
                                                <td align="left">
                                                    <span class="text-danger">
                                                        {{ ucwords(strtolower($pa->anggota->namadepan)) }}
                                                        ({{ $pa->nia }})
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="/detail/{{ $pa->id_pinkel }}" target="_blank"
                                                        class="text-danger text-gradient font-weight-bold">
                                                        {{ $pa->kelompok ? $pa->kelompok->nama_kelompok : '-' }} Loan ID.
                                                        {{ $pa->id_pinkel }}
                                                    </a>.
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @endif

                    <form action="/perguliran/dokumen?status={{ $perguliran->status }}&jenis=dokumen_pencairan"
                        target="_blank" method="post">
                        @csrf

                        <input type="hidden" name="id" value="{{ $perguliran->id }}">
                        <div class="row">
                            @foreach ($dokumen_pencairan as $doc => $val)
                                <div class="col-md-3 d-grid">
                                    @if ($val['withExcel'])
                                        <div class="btn-group">
                                            <button class="btn btn-linkedin btn-sm text-start" type="submit"
                                                name="report" value="{{ $val['file'] }}#pdf">
                                                {{ $loop->iteration }}. {{ $val['title'] }}
                                            </button>
                                            <button class="btn btn-icon btn-sm btn-instagram" type="submit"
                                                name="report" value="{{ $val['file'] }}#excel">
                                                <i class="fas fa-file-excel"></i>
                                            </button>
                                        </div>
                                    @else
                                        <button class="btn btn-linkedin btn-sm text-start" type="submit" name="report"
                                            value="{{ $val['file'] }}#pdf">
                                            {{ $loop->iteration }}. {{ $val['title'] }}
                                        </button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tambah Pemanfaat --}}
    <div class="modal fade" id="TambahPemanfaat" tabindex="-1" aria-labelledby="TambahPemanfaatLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="TambahPemanfaatLabel">
                        Tambah Calon Pemanfaat ({{ $perguliran->id }})
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="/pinjaman_anggota" method="post" id="FormTambahPemanfaat">
                        @csrf

                        <input type="hidden" name="id_pinkel" id="id_pinkel" value="{{ $perguliran->id }}">
                        <input type="hidden" name="nia_pemanfaat" id="nia_pemanfaat">
                        <input type="hidden" name="catatan_pinjaman" id="catatan_pinjaman">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <div class="input-group input-group-static">
                                    <input type="text" id="cariNik" name="cariNik" class="form-control"
                                        placeholder="Ketikkan NIK atau Nama" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="input-group input-group-static">
                                    <input type="text" id="alokasi_pengajuan" disabled name="alokasi_pengajuan"
                                        class="form-control money" placeholder="Alokasi Pengajuan">
                                </div>
                                <small class="text-danger" id="msg_alokasi_pengajuan"></small>
                            </div>
                        </div>

                        <div class="fw-bold text-center">
                            <div>
                                {{ $perguliran->kelompok->nama_kelompok }} -
                                {{ $perguliran->kelompok->d->sebutan_desa->sebutan_desa }}
                                {{ $perguliran->kelompok->d->nama_desa }}
                            </div>
                        </div>
                    </form>

                    <div class="card border">
                        <div class="card-body pt-3 pb-0 ps-3 pe-3">
                            <div id="LayoutTambahPemanfaat">

                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" id="ImportPemanfaat" class="btn btn-outline-info btn-sm">
                        Import Pemanfaat
                    </button>
                    <button type="button" id="SimpanPemanfaat" disabled class="btn btn-github btn-sm">Tambahkan</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Import Pemanfaat --}}
    <div class="modal fade" id="ImportPemanfaatModal" tabindex="-1" aria-labelledby="ImportPemanfaatModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="ImportPemanfaatModalLabel">
                        Import Pemanfaat
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="/pinjaman_anggota/import" method="post" id="FormImportPemanfaat">
                        @csrf

                        <input type="hidden" name="id_pinkel" id="id_pinkel" value="{{ $perguliran->id }}">
                        <div id="LayoutImportPemanfaatModal"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" id="SimpanImportPemanfaat" class="btn btn-github btn-sm">
                        Import Pemanfaat
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Catatan Bimbingan --}}
    <div class="modal fade" id="CatatanBimbingan" tabindex="-1" aria-labelledby="CatatanBimbinganLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="CatatanBimbinganLabel">
                        Catatan Bimbingan <span class="badge badge-info">Kelompok.
                            {{ $perguliran->kelompok->nama_kelompok }}</span>
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="LayoutCatatanBimbingan">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-success btn-sm" id="CetakCatatanBimbingan">
                        Cetak
                    </button>
                    <button type="button" data-bs-toggle="modal" data-bs-target="#TambahCatatan"
                        class="btn btn-github btn-sm">
                        Tambah Catatan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <form action="/perguliran/dokumen?status={{ $perguliran->status }}" target="_blank" method="post"
        id="FormCetakCatatanBimbingan">
        @csrf

        <input type="hidden" name="id" value="{{ $perguliran->id }}">
        <input type="hidden" name="report" value="CetakCatatanBimbingan#pdf">
    </form>

    {{-- Modal Tambah Catatan Bimbingan --}}
    <div class="modal fade" id="TambahCatatan" tabindex="-1" aria-labelledby="TambahCatatanLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="TambahCatatanLabel">
                        Tambah Catatan Bimbingan
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="/perguliran/catatan_bimbingan/{{ $perguliran->id }}" method="post"
                        id="FormCatatanBimbingan">
                        @csrf

                        <div class="row">
                            <div class="col-12">
                                <div class="input-group input-group-static my-3">
                                    <label for="tanggal_catatan">Tanggal</label>
                                    <input autocomplete="off" type="text" name="tanggal_catatan" id="tanggal_catatan"
                                        class="form-control date" value="{{ Tanggal::tglIndo(date('Y-m-d')) }}">
                                    <small class="text-danger" id="msg_tanggal_catatan"></small>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="my-3">
                                    <label for="catatan_bimbingan">Catatan</label>
                                    <div id="editor"></div>
                                </div>

                                <textarea name="catatan_bimbingan" id="catatan_bimbingan" class="d-none"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger btn-sm" id="TutupFormTambahCatatan">Tutup</button>
                    <button type="button" id="SimpanCatatanBimbingan" class="btn btn-github btn-sm">
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Rescedule Pinjaman --}}
    <div class="modal fade" id="Rescedule" tabindex="-1" aria-labelledby="ResceduleLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="ResceduleLabel">
                        Resceduling <span class="badge badge-info">Loan ID. {{ $perguliran->id }}</span>
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-justify">
                        Fitur ini dapat Anda gunakan jika Anda akan menjadwal ulang (<b>Resceduling</b>) Pinjaman.
                        Dengan klik tombol <b>Rescedule Pinjaman</b> maka pinjaman ini akan berstatus <b>R</b>, dan akan
                        membuat pinjaman baru dengan Alokasi sebesar saldo yang ada, yaitu
                        <b>Rp. {{ number_format($saldo_pokok) }}</b> ;
                    </div>

                    <form action="/perguliran/rescedule" method="post" id="formRescedule">
                        @csrf

                        <input type="hidden" name="id" id="id" value="{{ $perguliran->id }}">
                        <input type="hidden" name="_pengajuan" id="_pengajuan" value="{{ $saldo_pokok }}">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="input-group input-group-static my-3">
                                    <label for="tgl_resceduling">Tanggal Resceduling</label>
                                    <input autocomplete="off" type="text" name="tgl_resceduling" id="tgl_resceduling"
                                        class="form-control date" value="{{ date('d/m/Y') }}">
                                    <small class="text-danger" id="msg_tgl_resceduling"></small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group input-group-static my-3">
                                    <label for="pengajuan">Pengajuan Rp.</label>
                                    <input autocomplete="off" type="text" name="pengajuan" id="pengajuan"
                                        class="form-control money" disabled value="{{ number_format($saldo_pokok, 2) }}">
                                    <small class="text-danger" id="msg_pengajuan"></small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="my-2">
                                    <label class="form-label" for="sistem_angsuran_pokok">Sistem Angs. Pokok</label>
                                    <select class="form-control" name="sistem_angsuran_pokok" id="sistem_angsuran_pokok">
                                        @foreach ($sistem_angsuran as $sa)
                                            <option {{ $perguliran->sistem_angsuran == $sa->id ? 'selected' : '' }}
                                                value="{{ $sa->id }}">
                                                {{ $sa->nama_sistem }} ({{ $sa->deskripsi_sistem }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-danger" id="msg_sistem_angsuran_pokok"></small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="my-2">
                                    <label class="form-label" for="sistem_angsuran_jasa">Sistem Angs. Jasa</label>
                                    <select class="form-control" name="sistem_angsuran_jasa" id="sistem_angsuran_jasa">
                                        @foreach ($sistem_angsuran as $sa)
                                            <option {{ $perguliran->sa_jasa == $sa->id ? 'selected' : '' }}
                                                value="{{ $sa->id }}">
                                                {{ $sa->nama_sistem }} ({{ $sa->deskripsi_sistem }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-danger" id="msg_sistem_angsuran_jasa"></small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group input-group-static my-3">
                                    <label for="jangka">Jangka</label>
                                    <input autocomplete="off" type="number" name="jangka" id="jangka"
                                        class="form-control" value="{{ $perguliran->jangka }}">
                                    <small class="text-danger" id="msg_jangka"></small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group input-group-static my-3">
                                    <label for="pros_jasa">Prosentase Jasa (%)</label>
                                    <input autocomplete="off" type="number" name="pros_jasa" id="pros_jasa"
                                        class="form-control" value="{{ $perguliran->pros_jasa }}">
                                    <small class="text-danger" id="msg_pros_jasa"></small>
                                </div>
                            </div>
                        </div>

                        {{-- <div class="card">
                            <div class="card-body p-3">
                                <table class="table table-striped">
                                    @foreach ($perguliran->pinjaman_anggota as $pinjaman_anggota)
                                        <tr>
                                            <td>
                                                <div class="fw-bold">{{ $pinjaman_anggota->anggota->namadepan }}</div>
                                                <div>{{ $pinjaman_anggota->anggota->nik }} ({{ $pinjaman_anggota->id }})
                                                </div>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-dynamic mb-0">
                                                    <input autocomplete="off" type="text"
                                                        name="alokasi_pokok_anggota[{{ $pinjaman_anggota->id }}]"
                                                        id="alokasi-pokok-anggota-{{ $pinjaman_anggota->id }}"
                                                        data-id="{{ $pinjaman_anggota->id }}"
                                                        class="form-control money alokasi_pokok_anggota">
                                                </div>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-dynamic mb-0">
                                                    <input autocomplete="off" type="text" disabled
                                                        name="alokasi_jasa_anggota[{{ $pinjaman_anggota->id }}]"
                                                        id="alokasi-jasa-anggota-{{ $pinjaman_anggota->id }}"
                                                        data-id="{{ $pinjaman_anggota->id }}"
                                                        class="form-control money alokasi_jasa_anggota">
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>

                                <input type="hidden" id="total-alokasi-anggota" name="total_alokasi_anggota">
                            </div>
                        </div> --}}
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" id="SimpanRescedule" class="btn btn-github btn-sm">
                        Rescedule Pinjaman
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Penghapusan Pinjaman --}}
    <div class="modal fade" id="Penghapusan" tabindex="-1" aria-labelledby="PenghapusanLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="PenghapusanLabel">
                        Penghapusan Piutang <span class="badge badge-info">Loan ID. {{ $perguliran->id }}</span>
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="/perguliran/hapus" method="post" id="FormPenghapusanPinjaman">
                        @csrf

                        <input type="hidden" name="id" id="id" value="{{ $perguliran->id }}">
                        <input type="hidden" name="saldo" id="saldo" value="{{ $saldo_pokok }}">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="input-group input-group-static my-3">
                                    <label for="tgl_penghapusan">Tanggal Penghapusan</label>
                                    <input autocomplete="off" type="text" name="tgl_penghapusan" id="tgl_penghapusan"
                                        class="form-control date" value="{{ date('d/m/Y') }}">
                                    <small class="text-danger" id="msg_tgl_penghapusan"></small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group input-group-static my-3">
                                    <label for="alokasi">Alokasi Rp.</label>
                                    <input autocomplete="off" type="text" name="alokasi" id="alokasi"
                                        class="form-control money" disabled value="{{ number_format($saldo_pokok, 2) }}">
                                    <small class="text-danger" id="msg_alokasi"></small>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="input-group input-group-static my-3">
                                    <label for="alasan_penghapusan">Alasan Penghapusan</label>
                                    <textarea class="form-control" name="alasan_penghapusan" id="alasan_penghapusan"></textarea>
                                    <small class="text-danger" id="msg_alasan_penghapusan"></small>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" id="SimpanHapus" class="btn btn-github btn-sm">
                        Hapus Pinjaman
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Pinjaman Anggota --}}
    <div class="modal fade" id="PinjamanAnggota" tabindex="-1" aria-labelledby="PinjamanAnggotaLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="PinjamanAnggotaLabel">
                        Detail Pemanfaat
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="LayoutPinjamanAnggota"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Penghapusan Pinjaman Anggota --}}
    <div class="modal fade" id="PenghapusanPinjamanAnggota" tabindex="-1"
        aria-labelledby="PenghapusanPinjamanAnggotaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="PenghapusanPinjamanAnggotaLabel">
                        Form Penghapusan Pinjaman Anggota
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="LayoutPenghapusanPinjamanAnggota"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger btn-sm" id="tutupFormPenghapusan">Tutup</button>
                    <button type="button" id="HapusPinjamanAnggota" class="btn btn-github btn-sm">
                        Hapus Pinjaman
                    </button>
                </div>
            </div>
        </div>
    </div>

    <form action="/perguliran/{{ $perguliran->id }}" method="post" id="FormDeleteProposal">
        @csrf
        @method('DELETE')
    </form>

    <form action="/perguliran/catatan/{{ $perguliran->id }}" method="post" id="FormDeleteCatatan">
        @csrf
        @method('DELETE')

        <input type="hidden" id="index" name="index">
    </form>

    <div id="placeholder" class="d-none">
        <div class="row">
            <div class="col-lg-4 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <span class="placeholder rounded-circle border" style="width: 150px; height: 150px;"></span>

                        <h5 class="mb-2">
                            <b><span class="placeholder col-12"></span></b>
                        </h5>

                        <div class="text-muted">
                            <span class="placeholder col-12"></span>
                        </div>
                        <div class="text-muted"><span class="placeholder col-12"></span></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="alert placeholder col-12 p-4"></div>
                <div class="alert placeholder col-12 p-5"></div>
                <div class="alert placeholder col-12 p-5"></div>
                <div class="alert placeholder col-12 p-4"></div>
            </div>
        </div>
    </div>

    <form action="/generate/save" method="post" target="_blank" id="FormSinkronisasi">
        @csrf

        <input type="hidden" name="pinjaman" value="kelompok">
        <input type="hidden" name="generate_version" value="v2">
        <input type="hidden" name="id[operator]" value="=">
        <input type="hidden" name="id[value]" value="{{ $perguliran->id }}">
    </form>
@endsection

@section('script')
    <script>
        new Choices($('#sistem_angsuran_pokok')[0], {
            shouldSort: false,
            fuseOptions: {
                threshold: 0.1,
                distance: 1000
            }
        })
        new Choices($('#sistem_angsuran_jasa')[0], {
            shouldSort: false,
            fuseOptions: {
                threshold: 0.1,
                distance: 1000
            }
        })

        var quill = new Quill('#editor', {
            theme: 'snow'
        });


        $(".date").flatpickr({
            dateFormat: "d/m/Y"
        })

        $(".time").flatpickr({
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true
        })

        var formatter = new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })

        $.get('/perguliran/{{ $perguliran->id }}', function(result) {
            $('#layout').html(result)
        })

        $('#BtnEditProposal').click(function(e) {
            e.preventDefault()

            $.get('/perguliran/{{ $perguliran->id }}/edit', function(result) {
                $('#LayoutEditProposal').html(result)
                $('#EditProposal').modal('show')
            })
        })

        $('#HapusProposal').click(function(e) {
            e.preventDefault()

            Swal.fire({
                title: 'Hapus Proposal Ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = $('#FormDeleteProposal')
                    $.ajax({
                        type: 'post',
                        url: form.attr('action'),
                        data: form.serialize(),
                        success: function(result) {
                            if (result.hapus) {
                                Swal.fire('Berhasil!', result.msg, 'success').then(() => {
                                    window.location.href = '/perguliran'
                                })
                            } else {
                                Swal.fire('Peringatan', result.msg, 'warning')
                            }
                        }
                    })
                }
            })
        })

        $('#cariNik').typeahead({
            source: function(query, process) {
                var states = [];
                return $.get('/pinjaman_anggota/cari_pemanfaat', {
                    loan_id: '{{ $perguliran->id }}',
                    query: query
                }, function(result) {
                    var resultList = result.map(function(item) {

                        var disable = false
                        if (item.status == '0') {
                            disable = true
                        }

                        states.push({
                            "id": item.id,
                            "name": item.namadepan + ' [' + item.nik + ']' + '[' + item
                                .alamat + ']',
                            "value": item.nik,
                            "id_pinkel": '{{ $perguliran->id }}',
                            "disable": disable
                        });
                    });

                    return process(states);
                })
            },
            // updater: function(item) {
            //     return item.disable ? '' : item;
            // },
            afterSelect: function(item) {
                if (item != '') {
                    $.ajax({
                        url: '/pinjaman_anggota/register/' + item.id_pinkel,
                        type: 'get',
                        data: item,
                        success: function(result) {
                            if (result.enable_alokasi) {
                                $('#alokasi_pengajuan').removeAttr('disabled')
                                $('#SimpanPemanfaat').removeAttr('disabled')
                            } else {
                                $('#alokasi_pengajuan').attr('disabled', true)
                                $('#SimpanPemanfaat').attr('disabled', true)
                            }

                            $('#nia_pemanfaat').val(result.nia)
                            $('#catatan_pinjaman').val(result.catatan)
                            $('#LayoutTambahPemanfaat').html(result.html)
                        }
                    });
                } else {
                    Toastr('error', 'Pemanfaat diblokir. Tidak dapat mengajukan pinjaman')
                }
            }
        });

        $(document).on('click', '#cetakRekomTV', function(e) {
            e.preventDefault()

            $('#formCetakRekomTV').submit()
        })

        $(document).on('click', '#BtnCetakDokVerifikasi', function(e) {
            e.preventDefault()

            $('#formCetakDokVerifikasi').submit()
        })

        $(document).on('click', '#btnCatatanBimbingan', function() {
            catatan()
        })

        $(document).on('click', '#TutupFormTambahCatatan', function() {
            $('#CatatanBimbingan').modal('show')
            $('#TambahCatatan').modal('hide')
        })

        $(document).on('click', '#BtnTambahPemanfaat', function(e) {
            e.preventDefault()
            $('small').html('')

            $('#cariNik').val('')
            $('#alokasi_pengajuan').val('')

            $('#LayoutTambahPemanfaat').html($('#placeholder').html())
        })

        $(document).on('click', '#CetakCatatanBimbingan', function() {
            $('#FormCetakCatatanBimbingan').submit()
        })

        $(document).on('click', '#SimpanCatatanBimbingan', function(e) {
            e.preventDefault()

            $('#catatan_bimbingan').val(quill.container.firstChild.innerHTML)
            var form = $('#FormCatatanBimbingan')
            $.ajax({
                type: form.attr('method'),
                url: form.attr('action'),
                data: form.serialize(),
                success: function(result) {
                    Swal.fire('Berhasil', result.msg, 'success').then(() => {
                        catatan()
                    })
                },
                error: function(error) {
                    Swal.fire('Error', 'Cek kembali input yang anda masukkan', 'error')
                }
            })
        })

        $(document).on('click', '.delete-catatan', function(e) {
            e.preventDefault();

            var index = $(this).attr('data-id')
            $('input#index').val(index)

            Swal.fire({
                title: 'Hapus Catatan',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = $('#FormDeleteCatatan')
                    $.ajax({
                        type: form.attr('method'),
                        url: form.attr('action'),
                        data: form.serialize(),
                        success: function(result) {
                            if (result.success) {
                                Swal.fire('Berhasil', result.msg, 'success').then(() => {
                                    $('#CatatanBimbingan').modal('hide')
                                })
                            }
                        }
                    })
                }
            })
        })

        $(document).on('click', '#SimpanEditProposal', function(e) {
            e.preventDefault()
            $('small').html('')

            var form = $('#FormEditProposal')
            $.ajax({
                type: 'POST',
                url: form.attr('action'),
                data: form.serialize(),
                success: function(result) {
                    Swal.fire('Berhasil', result.msg, 'success').then(() => {
                        $.get('/perguliran/{{ $perguliran->id }}', function(result) {
                            $('#layout').html(result)

                            $('#EditProposal').modal('hide')
                            window.location.reload()
                        })
                    })
                },
                error: function(result) {
                    const respons = result.responseJSON;

                    Swal.fire('Error', 'Cek kembali input yang anda masukkan', 'error')
                    $.map(respons, function(res, key) {
                        $('#' + key).parent('.input-group.input-group-static')
                            .addClass(
                                'is-invalid')
                        $('#FormEditProposal #msg_' + key).html(res)
                    })
                }
            })
        })

        $(document).on('click', '#ImportPemanfaat', function(e) {
            e.preventDefault()

            $.get('/pinjaman_anggota/ambil_daftar_pemanfaat', {
                'id_kelompok': '{{ $perguliran->id_kel }}',
                'id_pinkel': '{{ $perguliran->id }}'
            }, function(result) {
                if (result.success) {
                    $('#TambahPemanfaat').modal('hide')

                    $('#LayoutImportPemanfaatModal').html(result.view)
                    $('#ImportPemanfaatModal').modal('show')
                }
            })
        })

        $(document).on('click', '#SimpanImportPemanfaat', function(e) {
            e.preventDefault()

            var form = $('#FormImportPemanfaat')
            $.ajax({
                type: form.attr('method'),
                url: form.attr('action'),
                data: form.serialize(),
                success: function(result) {
                    if (result.success) {
                        Swal.fire('Berhasil', result.msg, 'success').then(() => {
                            $.get('/perguliran/{{ $perguliran->id }}', function(result) {
                                $('#layout').html(result)
                            })

                            $('#ImportPemanfaatModal').modal('hide')
                        })
                    } else {
                        Swal.fire('Error', result.msg, 'error')
                    }
                }
            })
        })

        $(document).on('click', '#SimpanPemanfaat', function(e) {
            e.preventDefault()
            $('small').html('')

            var form = $('#FormTambahPemanfaat')
            $.ajax({
                type: 'post',
                url: form.attr('action'),
                data: form.serialize(),
                success: function(result) {
                    Swal.fire('Berhasil', result.msg, 'success').then(() => {
                        $.get('/perguliran/{{ $perguliran->id }}', function(result) {
                            $('#layout').html(result)
                        })

                        $('#TambahPemanfaat').modal('toggle')
                    })
                },
                error: function(result) {
                    const respons = result.responseJSON;

                    Swal.fire('Error', 'Cek kembali input yang anda masukkan', 'error')
                    $.map(respons, function(res, key) {
                        $('#' + key).parent('.input-group.input-group-static').addClass(
                            'is-invalid')
                        $('#FormTambahPemanfaat #msg_' + key).html(res)
                    })
                }
            })
        })

        $(document).on('click', '.HapusPinjamanAnggota', function(e) {
            e.preventDefault()

            var id = $(this).attr('id')
            Swal.fire({
                title: 'Hapus Pemanfaat Ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: 'get',
                        url: '/hapus_pemanfaat/' + id,
                        data: {
                            'id': id
                        },
                        success: function(result) {
                            if (result.hapus) {
                                Swal.fire('Berhasil', result.msg, 'success').then(() => {
                                    $.get('/perguliran/{{ $perguliran->id }}',
                                        function(result) {
                                            $('#layout').html(result)
                                        })
                                })
                            } else {
                                Swal.fire('Berhasil', result.msg, 'warning')
                            }
                        }
                    })
                }
            })
        })

        $(document).on('change', '.simpan-pencairan', function() {
            var form = $('#simpanDataPencairan')
            $.ajax({
                type: form.attr('method'),
                url: form.attr('action'),
                data: form.serialize(),
                success: function(result) {
                    if (result.success) {
                        $('[name=tgl_cair]').val(result.tgl_cair)
                        Swal.fire('Berhasil', result.msg, 'success')
                    }
                }
            })
        })

        $(document).on('change', '.simpan-proposal', function() {
            setTimeout(() => {
                var form = $('#simpanDataProposal')
                $.ajax({
                    type: form.attr('method'),
                    url: form.attr('action'),
                    data: form.serialize(),
                    success: function(result) {
                        if (result.success) {
                            Swal.fire('Berhasil', result.msg, 'success')
                        }
                    }
                })
            }, 1000);
        })

        $(document).on('click', '#tidakLayakDicairkan', function() {
            Swal.fire({
                title: 'Tidak Layak',
                text: 'Setelah klik lanjutkan, pinjaman ini akan berstatus T (Tidak Layak) dan tidak dapat dicairkan. Lanjutkan?',
                showCancelButton: true,
                confirmButtonText: 'Lanjutkan',
                cancelButtonText: 'Batal',
                icon: 'error'
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = $('#formTidakLayakDicairkan')
                    $.ajax({
                        type: form.attr('method'),
                        url: form.attr('action'),
                        data: form.serialize(),
                        success: function(result) {
                            if (result.success) {
                                Swal.fire('Berhasil', result.msg, 'success').then(() => {
                                    window.location.href = '/detail/' + result.id_pinkel
                                })
                            }
                        }
                    })
                }
            })
        })

        $(document).on('click', '#kembaliProposal', function() {
            Swal.fire({
                title: 'Peringatan',
                text: 'Anda yakin ingin mengembalikan pinjaman menjadi P (Pengajuan/Proposal)?',
                showCancelButton: true,
                confirmButtonText: 'Kembalikan',
                cancelButtonText: 'Batal',
                icon: 'warning'
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = $('#formKembaliProposal')
                    $.ajax({
                        type: form.attr('method'),
                        url: form.attr('action'),
                        data: form.serialize(),
                        success: function(result) {
                            if (result.success) {
                                Swal.fire('Berhasil', result.msg, 'success').then(() => {
                                    window.location.href = '/detail/' + result.id_pinkel
                                })
                            }
                        }
                    })
                }
            })
        })

        $(document).on('change', '.alokasi_pokok_anggota, #pros_jasa', function(e) {
            e.preventDefault();

            var prosJasa = $('#pros_jasa').val();
            var saldoPokok = Number('{{ $saldo_pokok }}');
            var alokasiAnggotaResc = 0;

            var alokasiPokokAnggota = $('.alokasi_pokok_anggota');
            alokasiPokokAnggota.each((i) => {
                var inputValue = Number(alokasiPokokAnggota[i].value.replace(/,/g, ''));

                if (inputValue > 0) {
                    var pinjamanAnggotaId = alokasiPokokAnggota[i].getAttribute('data-id');

                    var jasaAnggota = 0;
                    if (prosJasa > 0) {
                        jasaAnggota = inputValue * prosJasa / 100;
                    }

                    $('#alokasi-jasa-anggota-' + pinjamanAnggotaId).val(formatter.format(jasaAnggota))
                }

                alokasiAnggotaResc += inputValue;
            })

            if (alokasiAnggotaResc > saldoPokok) {
                Swal.fire('Berhasil', 'Total Alokasi Rescheduling Anggota tidak boleh melebihi Alokasi Kelompok',
                    'warning')

                $('#SimpanRescedule').attr('disabled', 'true')
            } else {
                $('#SimpanRescedule').removeAttr('disabled')
            }

            $('#total-alokasi-anggota').val(alokasiAnggotaResc)
        })

        $(document).on('click', '#SimpanRescedule', async function(e) {
            e.preventDefault()
            $('#Rescedule').modal('hide')

            // console.log(
            //     $('#total-alokasi-anggota').val(),
            //     '{{ $saldo_pokok }}',
            // )

            // return;

            const {
                value: spk
            } = await Swal.fire({
                title: 'Masukkan Nomor SPK baru',
                input: 'text',
                inputLabel: 'Spk No.',
                confirmButtonText: 'Simpan Perubahan'
            })

            if (spk) {
                var loading = Swal.fire({
                    title: "Mohon Menunggu..",
                    timerProgressBar: true,
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                })

                var form = $('#formRescedule')
                $.ajax({
                    type: 'POST',
                    url: form.attr('action') + '?spk=' + spk,
                    data: form.serialize(),
                    success: function(result) {
                        if (result.success) {
                            loading.close()

                            var id = result.id
                            $.get('/perguliran/generate/' + result.id + '?status=' + result.status +
                                '&save=true',
                                function(res) {
                                    if (res.success) {
                                        Swal.fire('Berhasil', result.msg, 'success').then(
                                            () => {
                                                window.location.href = '/detail/' + id
                                            })
                                    }
                                })
                        }
                    }
                })
            }
        })

        $(document).on('click', '#SimpanHapus', function(e) {
            e.preventDefault()

            Swal.fire({
                title: 'Peringatan',
                text: 'Anda yakin ingin melakukan penghapusan untuk pinjaman ini?',
                showCancelButton: true,
                confirmButtonText: 'Hapus Pinjaman',
                cancelButtonText: 'Batal',
                icon: 'warning'
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = $('#FormPenghapusanPinjaman')
                    $.ajax({
                        type: form.attr('method'),
                        url: form.attr('action'),
                        data: form.serialize(),
                        success: function(result) {
                            if (result.success) {
                                Swal.fire('Berhasil', result.msg, 'success').then(() => {
                                    window.location.href = '/perguliran'
                                })
                            }
                        }
                    })
                }
            })
        })

        $(document).on('click', '.btn-click', function(e) {
            e.preventDefault()

            var id = $(this).attr('data-id')
            $.get('/pinjaman_anggota/' + id, function(result) {
                if (result.success) {
                    $('#LayoutPinjamanAnggota').html(result.view)

                    $('#PinjamanAnggota').modal('show')
                }
            })
        })

        $(document).on('click', '#Pelunasan', function(e) {
            e.preventDefault()

            Swal.fire({
                title: 'Peringatan',
                text: 'Anda yakin ingin melakukan pelunasan pinjaman untuk pemanfaat ini?',
                showCancelButton: true,
                confirmButtonText: 'Ya, Lunaskan',
                cancelButtonText: 'Batal',
                icon: 'warning'
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = $('#formPelunasanPemanfaat')
                    $.ajax({
                        type: form.attr('method'),
                        url: form.attr('action'),
                        data: form.serialize(),
                        success: function(result) {
                            if (result.success) {
                                Swal.fire('Berhasil', result.msg, 'success').then(() => {
                                    $.get('/perguliran/{{ $perguliran->id }}',
                                        function(result) {
                                            $('#layout').html(result)
                                        })

                                    $('#PinjamanAnggota').modal('hide')
                                })
                            }
                        }
                    })
                }
            })
        })

        $(document).on('click', '#Penghapusan', function(e) {
            e.preventDefault()

            var id_pinj = $(this).attr('data-id')
            if (id_pinj) {
                $.get('/pinjaman_anggota/form_hapus/' + id_pinj, function(result) {
                    if (result.success) {
                        $('#LayoutPenghapusanPinjamanAnggota').html(result.view)

                        $('#PenghapusanPinjamanAnggota').modal('show')
                        $('#PinjamanAnggota').modal('hide')
                    }
                })
            }
        })

        $(document).on('click', '#tutupFormPenghapusan', function(e) {
            e.preventDefault()

            $('#PenghapusanPinjamanAnggota').modal('hide')
            $('#PinjamanAnggota').modal('show')
        })

        $(document).on('click', '#HapusPinjamanAnggota', function(e) {
            e.preventDefault()

            var form = $('#FormPenghapusanPinjamanAnggota')
            $.ajax({
                url: form.attr('action'),
                type: form.attr('method'),
                data: form.serialize(),
                success: function(result) {
                    if (result.success) {
                        $.get('/transaksi/regenerate_real/' + result.id_pinkel, function(res) {
                            if (res.success) {
                                Swal.fire('Berhasil!', result.msg, 'success').then(() => {
                                    $.get('/perguliran/{{ $perguliran->id }}',
                                        function(result) {
                                            $('#PenghapusanPinjamanAnggota').modal(
                                                'hide')
                                            $('#layout').html(result)
                                        })
                                })
                            }
                        })
                    } else {
                        Swal.fire('Error', result.msg, 'error')
                    }
                },
                error: function(result) {
                    const respons = result.responseJSON;

                    Swal.fire('Error', 'Cek kembali input yang anda masukkan', 'error')
                    $.map(respons, function(res, key) {
                        $('#' + key).parent('.input-group.input-group-static').addClass(
                            'is-invalid')
                        $('#msg_' + key).html(res)
                    })
                }
            })
        })

        $(document).on('click', '.btn-link', function(e) {
            var action = $(this).attr('data-action')

            open_window(action)
        })

        $(document).on('click', ' #sinkronisasi', function(e) {
            e.preventDefault()

            $('#FormSinkronisasi').submit()
        })

        function catatan() {
            $.ajax({
                type: 'GET',
                url: '/perguliran/catatan/{{ $perguliran->id }}',
                success: function(result) {
                    if (result.success) {
                        $('#LayoutCatatanBimbingan').html(result.view)
                        $('#CatatanBimbingan').modal('show')
                        $('#TambahCatatan').modal('hide')
                    }
                }
            })
        }

        $(".money").maskMoney();
    </script>
@endsection
