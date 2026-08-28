<div class="card mb-3">
    <div class="card-body">
        <div class="row mt-0">
            <div class="col-md-4 mb-3">
                <div class="border border-light border-2 border-radius-md p-3">
                    <h6 class="text-info text-gradient mb-0">
                        Proposal
                    </h6>
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                            Tgl Pengajuan
                            <span class="badge badge-info badge-pill">
                                {{ Tanggal::tglIndo($perguliran->tgl_proposal) }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                            Pengajuan
                            <span class="badge badge-info badge-pill">
                                {{ number_format($perguliran->proposal) }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                            Jenis Jasa
                            <span class="badge badge-info badge-pill">
                                {{ $perguliran->jasa->nama_jj }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                            Jasa
                            <span class="badge badge-info badge-pill">
                                {{ $perguliran->pros_jasa . '% / ' . $perguliran->jangka . ' bulan' }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                            Angs. Pokok
                            <span class="badge badge-info badge-pill">
                                {{ $perguliran->sis_pokok->nama_sistem }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                            Angs. Jasa
                            <span class="badge badge-info badge-pill">
                                {{ $perguliran->sis_jasa->nama_sistem }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="border border-light border-2 border-radius-md p-3">
                    <h6 class="text-danger text-gradient mb-0">
                        Verified
                    </h6>
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                            Tgl Verifikasi
                            <span class="badge badge-danger badge-pill">
                                {{ Tanggal::tglIndo($perguliran->tgl_verifikasi) }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                            Verifikasi
                            <span class="badge badge-danger badge-pill">
                                {{ number_format($perguliran->verifikasi) }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                            Jenis Jasa
                            <span class="badge badge-danger badge-pill">
                                {{ $perguliran->jasa->nama_jj }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                            Jasa
                            <span class="badge badge-danger badge-pill">
                                {{ $perguliran->pros_jasa . '% / ' . $perguliran->jangka . ' bulan' }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                            Angs. Pokok
                            <span class="badge badge-danger badge-pill">
                                {{ $perguliran->sis_pokok->nama_sistem }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                            Angs. Jasa
                            <span class="badge badge-danger badge-pill">
                                {{ $perguliran->sis_jasa->nama_sistem }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="border border-light border-2 border-radius-md p-3">
                    <h6 class="text-warning text-gradient mb-0">
                        Waiting
                    </h6>
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                            Tgl Penetapan
                            <span class="badge badge-warning badge-pill">
                                {{ Tanggal::tglIndo($perguliran->tgl_tunggu) }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                            Pendanaan
                            <span class="badge badge-warning badge-pill">
                                {{ number_format($perguliran->alokasi) }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                            Jenis Jasa
                            <span class="badge badge-warning badge-pill">
                                {{ $perguliran->jasa->nama_jj }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                            Jasa
                            <span class="badge badge-warning badge-pill">
                                {{ $perguliran->pros_jasa . '% / ' . $perguliran->jangka . ' bulan' }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                            Angs. Pokok
                            <span class="badge badge-warning badge-pill">
                                {{ $perguliran->sis_pokok->nama_sistem }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                            Angs. Jasa
                            <span class="badge badge-warning badge-pill">
                                {{ $perguliran->sis_jasa->nama_sistem }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        @if (!($perguliran->jenis_pp == '3' && $perguliran->kelompok->fungsi_kelompok == '2'))
            @if ($perguliran->status == 'A' || $perguliran->status == 'R')
                @if (in_array('tahapan_perguliran.aktif.tambah_pemanfaat', Session::get('tombol')))
                    <div class="d-grid">
                        <button type="button" id="BtnTambahPemanfaat" data-bs-toggle="modal"
                            data-bs-target="#TambahPemanfaat" class="btn btn-success btn-sm mb-1">
                            Tambah Pemanfaat
                        </button>
                    </div>
                @endif
            @endif
        @endif

        @if (!($perguliran->jenis_pp == '3' && $perguliran->kelompok->fungsi_kelompok == '2'))
            <hr class="horizontal dark">

            <div class="table-responsive">
                <table class="table table-striped table-hover align-items-center mb-0" width="100%">
                    <thead class="bg-dark text-white">
                        <tr>
                            <th>#</th>
                            <th>Nama</th>
                            <th>Pengajuan</th>
                            <th>Verifikasi</th>
                            <th>Alokasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $proposal = 0;
                            $verifikasi = 0;
                            $alokasi = 0;
                        @endphp
                        @foreach ($perguliran->pinjaman_anggota as $pinjaman_anggota)
                            @php
                                $proposal += $pinjaman_anggota->proposal;
                                $verifikasi += $pinjaman_anggota->verifikasi;
                                $alokasi += $pinjaman_anggota->alokasi;

                                $Class = 'pointer';
                                $warna = '';

                                if (
                                    in_array(
                                        'tahapan_perguliran.aktif.penghapusan_pinjaman_anggota',
                                        Session::get('tombol'),
                                    )
                                ) {
                                    $Class .= ' btn-click';
                                }

                                if (
                                    in_array(
                                        'tahapan_perguliran.aktif.pelunasan_pinjaman_anggota',
                                        Session::get('tombol'),
                                    )
                                ) {
                                    $Class .= ' btn-click';
                                }

                                if ($pinjaman_anggota->status == 'H') {
                                    $warna = 'class="text-danger fw-bold"';
                                    $Class = '';
                                }
                            @endphp
                            <tr class="{{ $Class }}" data-id="{{ $pinjaman_anggota->id }}">
                                <td {!! $warna !!} align="center">{{ $loop->iteration }}</td>
                                <td {!! $warna !!}>
                                    {{ ucwords($pinjaman_anggota->anggota->namadepan) }}
                                    ({{ $pinjaman_anggota->nia }})
                                </td>
                                <td {!! $warna !!}>
                                    {{ number_format($pinjaman_anggota->proposal, 2) }}
                                </td>
                                <td {!! $warna !!}>
                                    {{ number_format($pinjaman_anggota->verifikasi, 2) }}
                                </td>
                                <td {!! $warna !!}>
                                    {{ number_format($pinjaman_anggota->alokasi, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2">Jumlah</th>
                            <th>
                                {{ number_format($proposal, 2) }}
                            </th>
                            <th id="jumlah">
                                {{ number_format($verifikasi, 2) }}
                            </th>
                            <th>
                                {{ number_format($alokasi, 2) }}
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
</div>

@if (in_array('tahapan_perguliran.aktif.cetak_dokumen_pencairan', Session::get('tombol')))
    <div class="card card-body p-2 pb-0 mb-3">
        <form action="/perguliran/dokumen?status=A" target="_blank" method="post">
            @csrf

            <input type="hidden" name="id" value="{{ $perguliran->id }}">
            <div class="row">
                <div class="col-12 col-sm-6 col-md-6 col-lg-4">
                    <div class="d-grid">
                        <a href="/perguliran/dokumen/kartu_angsuran/{{ $perguliran->id }}" target="_blank"
                            class="btn btn-outline-info btn-sm mb-2">Kartu Angsuran</a>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-6 col-lg-4">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-outline-info btn-sm mb-2" name="report"
                            value="rencanaAngsuran#pdf">Rencana Angsuran</button>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-6 col-lg-4">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-outline-info btn-sm mb-2" name="report"
                            value="rekeningKoran#pdf">Rekening Koran</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endif

@php
    $CetakDokumenProposal = false;
    $CetakDokumenPencairan = false;
    $CetakDokumenVerifikasi = $perguliran->status == 'L';
    $CetakDokumen = false;

    $jumlahCetak = 0;
    if (in_array('tahapan_perguliran.aktif.cetak_dokumen_proposal', Session::get('tombol'))) {
        $CetakDokumenProposal = true;
        $CetakDokumen = true;
        $jumlahCetak++;
    }

    if (in_array('tahapan_perguliran.aktif.cetak_dokumen_pencairan', Session::get('tombol'))) {
        $CetakDokumenPencairan = true;
        $CetakDokumen = true;
        $jumlahCetak++;
    }

    if ($CetakDokumenVerifikasi) {
        $CetakDokumen = true;
        $jumlahCetak++;
    }

    $Class = '';
    if ($jumlahCetak > 0) {
        $Class = 'col-12 col-sm-' . 12 / $jumlahCetak . ' col-md-' . 12 / $jumlahCetak;
    }
@endphp

@if ($CetakDokumen)
    <div class="card card-body p-2 pb-0 mb-3">
        <div class="row">
            @if ($CetakDokumenProposal)
                <div class="{{ $Class }}">
                    <div class="d-grid">
                        <button type="button" data-bs-toggle="modal" data-bs-target="#CetakDokumenProposal"
                            class="btn btn-info btn-sm mb-2">Cetak Dokumen Proposal</button>
                    </div>
                </div>
            @endif
            @if ($CetakDokumenPencairan)
                <div class="{{ $Class }}">
                    <div class="d-grid">
                        <button type="button" data-bs-toggle="modal" data-bs-target="#CetakDokumenPencairan"
                            class="btn btn-info btn-sm mb-2">Cetak Dokumen Pencairan</button>
                    </div>
                </div>
            @endif
            @if ($CetakDokumenVerifikasi)
                <div class="{{ $Class }}">
                    <div class="d-grid">
                        <button type="button" id="BtnCetakDokVerifikasi" class="btn btn-info btn-sm mb-2">
                            Dok. Verifikasi
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endif

@if ($CetakDokumenVerifikasi)
    <form action="/perguliran/dokumen?status=L&jenis=dokumen_verifikasi" target="_blank" method="post"
        id="formCetakDokVerifikasi">
        @csrf

        <input type="hidden" name="id" value="{{ $perguliran->id }}">
        <input type="hidden" name="report" value="formVerifikasi#pdf">
    </form>
@endif

<div class="card mb-3 {{ $perguliran->status == 'T' ? 'd-none' : '' }}">
    <div class="card-body pb-2">
        <h5 class="mb-1">
            Riwayat Angsuran
        </h5>

        <div class="table-responsive">
            <table class="table table-striped align-items-center mb-0" width="100%">
                <thead>
                    <tr class="bg-dark text-white">
                        <th>#</th>
                        <th>Tgl transaksi</th>
                        <th>Pokok</th>
                        <th>Jasa</th>
                        <th>Saldo Pokok</th>
                        <th>#</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($perguliran->real as $real)
                        <tr>
                            <td align="center">{{ $loop->iteration }}</td>
                            <td align="center">{{ Tanggal::tglIndo($real->tgl_transaksi) }}</td>
                            <td align="right">{{ number_format($real->realisasi_pokok) }}</td>
                            <td align="right">{{ number_format($real->realisasi_jasa) }}</td>
                            <td align="right">{{ number_format($real->saldo_pokok) }}</td>
                            <td align="center">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-instagram btn-icon-only btn-tooltip"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <span class="btn-inner--icon"><i class="fas fa-file"></i></span>
                                    </button>
                                    <ul class="dropdown-menu px-2 py-3" aria-labelledby="dropdownMenuButton">
                                        <li>
                                            <a class="dropdown-item border-radius-md" target="_blank"
                                                href="/transaksi/dokumen/struk/{{ $real->id }}">
                                                Kuitansi
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item border-radius-md" target="_blank"
                                                href="/transaksi/dokumen/struk_matrix/{{ $real->id }}">
                                                Kuitansi Dot Matrix
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item border-radius-md" target="_blank"
                                                href="/transaksi/dokumen/struk_thermal/{{ $real->id }}">
                                                Kuitansi Thermal
                                            </a>
                                        </li>
                                    </ul>
                                    <button type="button" class="btn btn-tumblr btn-icon-only btn-tooltip"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <span class="btn-inner--icon"><i class="fas fa-file-invoice"></i></span>
                                    </button>
                                    <ul class="dropdown-menu px-2 py-3" aria-labelledby="dropdownMenuButton">
                                        <li>
                                            <a class="dropdown-item border-radius-md" target="_blank"
                                                href="/perguliran/dokumen/kartu_angsuran/{{ $real->loan_id }}/{{ $real->id }}">
                                                Kelompok
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item border-radius-md" target="_blank"
                                                href="/perguliran/dokumen/cetak_kartu_angsuran_anggota/{{ $real->loan_id }}/{{ $real->id }}">
                                                Anggota
                                            </a>
                                        </li>
                                    </ul>
                                    <button type="button"
                                        data-action="/transaksi/dokumen/bkm_angsuran/{{ $real->transaksi->idt }}"
                                        class="btn btn-github btn-icon-only btn-tooltip btn-link"
                                        data-bs-toggle="tooltip" data-bs-placement="top" title="BKM"
                                        data-container="body" data-animation="true">
                                        <span class="btn-inner--icon"><i
                                                class="fas fa-file-circle-exclamation"></i></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if (
            $perguliran->status == 'A' &&
                (in_array('tahapan_perguliran.aktif.resceduling_pinjaman', Session::get('tombol')) ||
                    in_array('tahapan_perguliran.aktif.penghapusan_pinjaman_kelompok', Session::get('tombol')) ||
                    in_array('tahapan_perguliran.aktif.catatan_bimbingan', Session::get('tombol'))))
            <div class="d-flex justify-content-end mt-3">
                @if (in_array('tahapan_perguliran.aktif.catatan_bimbingan', Session::get('tombol')))
                    <button type="button" id="btnCatatanBimbingan" class="btn btn-success btn-sm">
                        Catatan Bimbingan
                    </button>
                @endif
                @if (in_array('tahapan_perguliran.aktif.resceduling_pinjaman', Session::get('tombol')))
                    <button type="button" data-bs-toggle="modal" data-bs-target="#Rescedule"
                        class="btn btn-warning btn-sm ms-1">Resceduling Pinjaman</button>
                @endif
                @if (in_array('tahapan_perguliran.aktif.penghapusan_pinjaman_kelompok', Session::get('tombol')))
                    <button type="button" data-bs-toggle="modal" data-bs-target="#Penghapusan"
                        class="btn btn-danger btn-sm ms-1">Penghapusan Pinjaman</button>
                @endif
            </div>
        @endif
    </div>
</div>
