@php
    $RiwayatPiutang = 'show active';
    $ProfilKelompok = '';
    if (!in_array('data_kelompok.riwayat_piutang', Session::get('tombol'))) {
        $RiwayatPiutang = '';
        $ProfilKelompok = 'show active';
    }
@endphp

@extends('layouts.base')

@section('content')
    <div class="nav-wrapper position-relative end-0">
        <ul class="nav nav-pills nav-fill p-1" role="tablist">
            @if (in_array('data_kelompok.riwayat_piutang', Session::get('tombol')))
                <li class="nav-item">
                    <a class="nav-link mb-0 px-0 py-1 {{ $RiwayatPiutang }}" data-bs-toggle="tab" href="#RiwayatPiutang"
                        role="tab" aria-controls="RiwayatPiutang" aria-selected="true">
                        <span class="material-icons align-middle mb-1">
                            access_time
                        </span>
                        Riwayat Piutang
                    </a>
                </li>
            @endif
            @if (in_array('data_kelompok.detail_kelompok', Session::get('tombol')))
                <li class="nav-item">
                    <a class="nav-link mb-0 px-0 py-1 {{ $ProfilKelompok }}" data-bs-toggle="tab" href="#ProfilKelompok"
                        role="tab" aria-controls="ProfilKelompok" aria-selected="false">
                        <span class="material-icons align-middle mb-1">
                            group
                        </span>
                        Profil Kelompok
                    </a>
                </li>
            @endif
        </ul>

        <div class="tab-content mt-2">
            @if (in_array('data_kelompok.riwayat_piutang', Session::get('tombol')))
                <div class="tab-pane fade {{ $RiwayatPiutang }}" id="RiwayatPiutang" role="tabpanel"
                    aria-labelledby="RiwayatPiutang">
                    <div class="card bg-gradient-default">
                        <div class="card-body">
                            <h5 class="font-weight-normal text-info text-gradient">
                                Riwayat Piutang Kelompok {{ $kelompok->nama_kelompok }}
                            </h5>

                            <ul class="list-group list-group-flush mt-2">
                                @php
                                    $status = '';
                                @endphp
                                @foreach ($kelompok->pinkel as $pinkel)
                                    <li class="list-group-item">
                                        @php
                                            $saldo = 0;
                                            if ($pinkel->saldo) {
                                                $saldo = $pinkel->saldo->sum_pokok;
                                            }
                                            $link = '/detail' . '/' . $pinkel->id;
                                            if ($pinkel->status == 'P') {
                                                $tgl = $pinkel->tgl_proposal;
                                                $jumlah = $pinkel->proposal;
                                            } elseif ($pinkel->status == 'V') {
                                                $tgl = $pinkel->tgl_verifikasi;
                                                $jumlah = $pinkel->verifikasi;
                                            } elseif ($pinkel->status == 'W') {
                                                $tgl = $pinkel->tgl_cair;
                                                $jumlah = $pinkel->alokasi;
                                            } else {
                                                $tgl = $pinkel->tgl_cair;
                                                $jumlah = $pinkel->alokasi;

                                                if ($pinkel->alokasi <= $saldo) {
                                                    $link = '/lunas' . '/' . $pinkel->id;
                                                }
                                            }

                                            if (
                                                $pinkel->status == 'L' ||
                                                $pinkel->status == 'H' ||
                                                $pinkel->status == 'T' ||
                                                $pinkel->status == 'B' ||
                                                $pinkel->status == 'R'
                                            ) {
                                                $link = '/detail' . '/' . $pinkel->id;
                                            }
                                            $status = $pinkel->status;

                                        @endphp
                                        <blockquote data-link="{{ $link }}"
                                            class="blockquote text-white mb-1 pointer">
                                            <p class="text-dark ms-3">
                                                <span class="badge badge-{{ $pinkel->sts->warna_status }}">
                                                    Loan ID. {{ $pinkel->id }}
                                                </span>
                                                |
                                                <span class="fw-bold">
                                                    {{ Tanggal::tglIndo($tgl) }}
                                                </span>
                                                |
                                                <span class="fw-bold">
                                                    {{ number_format($jumlah) }}
                                                </span>
                                                |
                                                <span class="fw-bold">
                                                    {{ $pinkel->pros_jasa == 0 ? 0 : $pinkel->pros_jasa / $pinkel->jangka }}%
                                                    @
                                                    {{ $pinkel->jangka }} {{ \App\Utils\Pinjaman::satuanJangka($pinkel) }} --
                                                    {{ $pinkel->angsuran_pokok->nama_sistem }}
                                                </span>
                                                |
                                                <span class="fw-bold">
                                                    {{ $pinkel->sts->nama_status }}
                                                </span>
                                            </p>
                                        </blockquote>
                                    </li>
                                @endforeach

                                @if (!($status == 'P' || $status == 'V' || $status == 'W'))
                                    @if (in_array('register_proposal', Session::get('akses_menu')))
                                        <li class="list-group-item">
                                            <blockquote data-link="/register_proposal?id_kel={{ $kelompok->id }}"
                                                class="blockquote text-white mb-1 pointer">
                                                <p class="text-dark ms-3">
                                                    <span class="badge badge-dark">
                                                        Buat Proposal Baru
                                                    </span>
                                                </p>
                                            </blockquote>
                                        </li>
                                    @endif
                                @endif

                                @if ($status == '')
                                    @if (in_array('data_kelompok.hapus_kelompok', Session::get('tombol')))
                                        <li class="list-group-item">
                                            <blockquote data-link="/database/kelompok/{{ $kelompok->kd_kelompok }}"
                                                class="blockquote text-white mb-1 pointer" id="deleteKelompok">
                                                <p class="text-dark ms-3">
                                                    <span class="badge badge-danger">
                                                        Hapus Kelompok
                                                    </span>
                                                </p>
                                            </blockquote>
                                        </li>
                                    @endif
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <form action="" method="post" id="formDelete">
                @method('DELETE')
                @csrf
            </form>

            @if (in_array('data_kelompok.detail_kelompok', Session::get('tombol')))
                <div class="tab-pane fade {{ $ProfilKelompok }}" id="ProfilKelompok" role="tabpanel"
                    aria-labelledby="ProfilKelompok">
                    <div class="card">
                        <div class="card-body">
                            <form action="/database/kelompok/{{ $kelompok->kd_kelompok }}" method="post"
                                id="FormEditKelompok">
                                @csrf
                                @method('PUT')

                                <input type="hidden" name="kd_kelompok" id="kd_kelompok"
                                    value="{{ $kelompok->kd_kelompok }}">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="my-2">
                                            <label class="form-label" for="desa">Desa/Kelurahan</label>
                                            <select class="form-control" name="desa" id="desa">
                                                @foreach ($desa as $ds)
                                                    <option {{ $desa_dipilih == $ds->kd_desa ? 'selected' : '' }}
                                                        value="{{ $ds->kd_desa }}">
                                                        {{ $ds->sebutan_desa->sebutan_desa }} {{ $ds->nama_desa }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="text-danger" id="msg_desa"></small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="input-group input-group-static my-3">
                                            <label for="kode_kelompok">Kode Kelompok</label>
                                            <input autocomplete="off" type="text" name="kode_kelompok" id="kode_kelompok"
                                                class="form-control" readonly value="{{ $kelompok->kd_kelompok }}">
                                            <small class="text-danger" id="msg_kode_kelompok"></small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="input-group input-group-static my-3">
                                            <label for="nama_kelompok">Nama Kelompok</label>
                                            <input autocomplete="off" type="text" name="nama_kelompok" id="nama_kelompok"
                                                class="form-control" value="{{ $kelompok->nama_kelompok }}">
                                            <small class="text-danger" id="msg_nama_kelompok"></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="my-2">
                                            <label class="form-label" for="jenis_produk_pinjaman">Jenis Produk
                                                Pinjaman</label>
                                            <select class="form-control" name="jenis_produk_pinjaman"
                                                id="jenis_produk_pinjaman">
                                                @foreach ($jenis_produk_pinjaman as $jpp)
                                                    <option {{ $kelompok->jenis_pp->id == $jpp->id ? 'selected' : '' }}
                                                        value="{{ $jpp->id }}">
                                                        {{ $jpp->nama_jpp }} ({{ $jpp->deskripsi_jpp }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="text-danger" id="msg_jenis_produk_pinjaman"></small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="input-group input-group-static my-3">
                                            <label for="alamat_kelompok">Alamat Kelompok</label>
                                            <input autocomplete="off" type="text" name="alamat_kelompok"
                                                id="alamat_kelompok" class="form-control"
                                                value="{{ $kelompok->alamat_kelompok }}">
                                            <small class="text-danger" id="msg_alamat_kelompok"></small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="input-group input-group-static my-3">
                                            <label for="telpon">No. HP (Aktif WA)</label>
                                            <input autocomplete="off" type="text" name="telpon" id="telpon"
                                                class="form-control"
                                                value="{{ strlen($kelompok->telpon) < 11 ? '628' : $kelompok->telpon }}">
                                            <small class="text-danger" id="msg_telpon"></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="input-group input-group-static my-3">
                                            <label for="tgl_berdiri">Tgl Berdiri</label>
                                            <input autocomplete="off" type="text" name="tgl_berdiri" id="tgl_berdiri"
                                                class="form-control date"
                                                value="{{ Tanggal::tglIndo($kelompok->tgl_berdiri) }}">
                                            <small class="text-danger" id="msg_tgl_berdiri"></small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="input-group input-group-static my-3">
                                            <label for="ketua">Nama Ketua</label>
                                            <input autocomplete="off" type="text" name="ketua" id="ketua"
                                                class="form-control" value="{{ $kelompok->ketua }}">
                                            <small class="text-danger" id="msg_ketua"></small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="input-group input-group-static my-3">
                                            <label for="sekretaris">Nama Sekretaris</label>
                                            <input autocomplete="off" type="text" name="sekretaris" id="sekretaris"
                                                class="form-control" value="{{ $kelompok->sekretaris }}">
                                            <small class="text-danger" id="msg_sekretaris"></small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="input-group input-group-static my-3">
                                            <label for="bendahara">Nama Bendahara</label>
                                            <input autocomplete="off" type="text" name="bendahara" id="bendahara"
                                                class="form-control" value="{{ $kelompok->bendahara }}">
                                            <small class="text-danger" id="msg_bendahara"></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="my-2">
                                            <label class="form-label" for="jenis_usaha">Jenis Usaha</label>
                                            <select class="form-control" name="jenis_usaha" id="jenis_usaha">
                                                @foreach ($jenis_usaha as $ju)
                                                    <option {{ $kelompok->jenis_usaha == $ju->id ? 'selected' : '' }}
                                                        value="{{ $ju->id }}">
                                                        {{ $ju->nama_ju }} ({{ $ju->deskripsi_ju }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="text-danger" id="msg_jenis_usaha"></small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="my-2">
                                            <label class="form-label" for="jenis_kegiatan">Jenis Kegiatan</label>
                                            <select class="form-control" name="jenis_kegiatan" id="jenis_kegiatan">
                                                @foreach ($jenis_kegiatan as $jk)
                                                    <option {{ $kelompok->jenis_kegiatan == $jk->id ? 'selected' : '' }}
                                                        value="{{ $jk->id }}">
                                                        {{ $jk->nama_jk }} ({{ $jk->deskripsi_jk }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="text-danger" id="msg_jenis_kegiatan"></small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="my-2">
                                            <label class="form-label" for="tingkat_kelompok">Tingkat Kelompok</label>
                                            <select class="form-control" name="tingkat_kelompok" id="tingkat_kelompok">
                                                @foreach ($tingkat_kelompok as $tk)
                                                    <option {{ $kelompok->tingkat_kelompok == $tk->id ? 'selected' : '' }}
                                                        value="{{ $tk->id }}">
                                                        {{ $tk->nama_tk }} ({{ $tk->deskripsi_tk }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="text-danger" id="msg_tingkat_kelompok"></small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="my-2">
                                            <label class="form-label" for="fungsi_kelompok">Fungsi Kelompok</label>
                                            <select class="form-control" name="fungsi_kelompok" id="fungsi_kelompok">
                                                @foreach ($fungsi_kelompok as $fk)
                                                    <option {{ $kelompok->fungsi_kelompok == $fk->id ? 'selected' : '' }}
                                                        value="{{ $fk->id }}">
                                                        {{ $fk->nama_fgs }} ({{ $fk->deskripsi_fgs }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="text-danger" id="msg_fungsi_kelompok"></small>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            @if (in_array('data_kelompok.edit_kelompok', Session::get('tombol')))
                                <button type="submit" id="SimpanKelompok" class="btn btn-github btn-sm float-end">
                                    Simpan Kelompok
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="card mt-3">
            <div class="card-body p-2">
                <a href="/database/kelompok" class="btn btn-sm btn-info float-end mb-0">Kembali</a>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        new Choices($('#desa')[0], {
            shouldSort: false,
            fuseOptions: {
                threshold: 0.1,
                distance: 1000
            }
        })
        new Choices($('#jenis_produk_pinjaman')[0], {
            shouldSort: false,
            fuseOptions: {
                threshold: 0.1,
                distance: 1000
            }
        })
        new Choices($('#jenis_usaha')[0], {
            shouldSort: false,
            fuseOptions: {
                threshold: 0.1,
                distance: 1000
            }
        })
        new Choices($('#jenis_kegiatan')[0], {
            shouldSort: false,
            fuseOptions: {
                threshold: 0.1,
                distance: 1000
            }
        })
        new Choices($('#tingkat_kelompok')[0], {
            shouldSort: false,
            fuseOptions: {
                threshold: 0.1,
                distance: 1000
            }
        })
        new Choices($('#fungsi_kelompok')[0], {
            shouldSort: false,
            fuseOptions: {
                threshold: 0.1,
                distance: 1000
            }
        })

        $(".date").flatpickr({
            dateFormat: "d/m/Y"
        })

        $(document).on('change', '#desa', function(e) {
            e.preventDefault()

            var kd_desa = $(this).val()
            var kd_kelompok = $('#kd_kelompok').val()
            $.get('/database/kelompok/generatekode?kode=' + kd_desa + '&kd_kelompok=' + kd_kelompok, function(
                result) {
                $('#kode_kelompok').val(result.kd_kelompok)
            })
        })

        $(document).on('click', '#SimpanKelompok', function(e) {
            e.preventDefault()
            $('small').html('')

            var form = $('#FormEditKelompok')
            $.ajax({
                type: 'POST',
                url: form.attr('action'),
                data: form.serialize(),
                success: function(result) {
                    Swal.fire('Berhasil', result.msg, 'success')
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

        $(document).on('click', '#deleteKelompok', function(e) {
            e.preventDefault()

            var action = $(this).attr('data-link')

            Swal.fire({
                title: 'Hapus Kelompok Ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = $('#formDelete')
                    $.ajax({
                        url: action,
                        method: form.attr('method'),
                        data: form.serialize(),
                        success: function(result) {
                            if (result.success) {
                                Swal.fire('Berhasil!', result.msg, 'success').then(() => {
                                    window.location.href = '/database/kelompok'
                                })
                            } else {
                                Swal.fire('Peringatan', 'Kelompok gagal dihapus', 'warning')
                            }
                        }
                    })
                }
            })
        })

        $(document).on('click', '.blockquote', function(e) {
            e.preventDefault()

            var link = $(this).attr('data-link')
            if ($(this).attr('id') != 'deleteKelompok') {
                window.location.href = link
            }
        })
    </script>
@endsection
