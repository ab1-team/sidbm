@php
    $RiwayatPiutang = 'show active';
    $ProfilKelompok = '';
    if (!in_array('data_lembaga_lain.riwayat_piutang', Session::get('tombol'))) {
        $RiwayatPiutang = '';
        $ProfilKelompok = 'show active';
    }
@endphp

@extends('layouts.base')

@section('content')
    <div class="nav-wrapper position-relative end-0">
        <ul class="nav nav-pills nav-fill p-1" role="tablist">
            @if (in_array('data_lembaga_lain.riwayat_piutang', Session::get('tombol')))
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
            @if (in_array('data_lembaga_lain.profil_lembaga', Session::get('tombol')))
                <li class="nav-item">
                    <a class="nav-link mb-0 px-0 py-1 {{ $ProfilKelompok }}" data-bs-toggle="tab" href="#ProfilLembaga"
                        role="tab" aria-controls="ProfilLembaga" aria-selected="false">
                        <span class="material-icons align-middle mb-1">
                            group
                        </span>
                        Profil Lembaga
                    </a>
                </li>
            @endif
        </ul>

        <div class="tab-content mt-2">
            @if (in_array('data_lembaga_lain.riwayat_piutang', Session::get('tombol')))
                <div class="tab-pane fade {{ $RiwayatPiutang }}" id="RiwayatPiutang" role="tabpanel"
                    aria-labelledby="RiwayatPiutang">
                    <div class="card bg-gradient-default">
                        <div class="card-body">
                            <h5 class="font-weight-normal text-info text-gradient">
                                Riwayat Piutang Lembaga {{ $lembaga_lain->nama_kelompok }}
                            </h5>

                            <ul class="list-group list-group-flush mt-2">
                                @php
                                    $status = '';
                                @endphp
                                @foreach ($lembaga_lain->pinkel as $pinkel)
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
                                                    {{ $pinkel->jangka }} Bulan --
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
                                            <blockquote data-link="/register_proposal?id_kel={{ $lembaga_lain->id }}"
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
                                    @if (in_array('data_lembaga_lain.hapus_lembaga', Session::get('tombol')))
                                        <li class="list-group-item">
                                            <blockquote data-link="/database/lembaga_lain/{{ $lembaga_lain->kd_kelompok }}"
                                                class="blockquote text-white mb-1 pointer" id="deleteLembaga">
                                                <p class="text-dark ms-3">
                                                    <span class="badge badge-danger">
                                                        Hapus Lembaga
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
            @if (in_array('data_lembaga_lain.profil_lembaga', Session::get('tombol')))
                <div class="tab-pane fade {{ $ProfilKelompok }}" id="ProfilLembaga" role="tabpanel"
                    aria-labelledby="ProfilLembaga">
                    <div class="card">
                        <div class="card-body">
                            <form action="/database/lembaga_lain/{{ $lembaga_lain->kd_kelompok }}" method="post"
                                id="FormEditLembaga">
                                @csrf
                                @method('PUT')

                                <input type="hidden" name="kd_lembaga" id="kd_lembaga"
                                    value="{{ $lembaga_lain->kd_kelompok }}">
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="my-2">
                                            <label class="form-label" for="desa">Desa/Kelurahan</label>
                                            <select class="form-control" name="desa" id="desa">
                                                @foreach ($desa as $ds)
                                                    <option {{ $desa_dipilih == $ds->kd_desa ? 'selected' : '' }}
                                                        value="{{ $ds->kd_desa }}">
                                                        {{ $ds->sebutan_desa->sebutan_desa }} {{ $ds->nama_desa }},
                                                        {{ $ds->kec ? $ds->kec->nama_kec : $ds->nama_kec }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="text-danger" id="msg_desa"></small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="input-group input-group-static my-3">
                                            <label for="kode_lembaga">Kode Lembaga</label>
                                            <input autocomplete="off" type="text" name="kode_lembaga" id="kode_lembaga"
                                                class="form-control" readonly value="{{ $lembaga_lain->kd_kelompok }}">
                                            <small class="text-danger" id="msg_kode_lembaga"></small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="input-group input-group-static my-3">
                                            <label for="nama_lembaga">Nama Lembaga</label>
                                            <input autocomplete="off" type="text" name="nama_lembaga" id="nama_lembaga"
                                                class="form-control" value="{{ $lembaga_lain->nama_kelompok }}">
                                            <small class="text-danger" id="msg_nama_lembaga"></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="input-group input-group-static my-3">
                                            <label for="alamat_lembaga">Alamat Lembaga</label>
                                            <input autocomplete="off" type="text" name="alamat_lembaga"
                                                id="alamat_lembaga" class="form-control"
                                                value="{{ $lembaga_lain->alamat_kelompok }}">
                                            <small class="text-danger" id="msg_alamat_lembaga"></small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="input-group input-group-static my-3">
                                            <label for="telpon">No. HP (Aktif WA)</label>
                                            <input autocomplete="off" type="text" name="telpon" id="telpon"
                                                class="form-control"
                                                value="{{ strlen($lembaga_lain->telpon) < 11 ? '628' : $lembaga_lain->telpon }}">
                                            <small class="text-danger" id="msg_telpon"></small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="my-2">
                                            <label class="form-label" for="kategori_pinjaman">Kategori Pinjaman</label>
                                            <select class="form-control" name="kategori_pinjaman" id="kategori_pinjaman">
                                                @foreach ($fungsi_kelompok as $fk)
                                                    <option
                                                        {{ $lembaga_lain->fungsi_kelompok == $fk->id ? 'selected' : '' }}
                                                        value="{{ $fk->id }}">
                                                        {{ $fk->nama_fgs }} ({{ $fk->deskripsi_fgs }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="text-danger" id="msg_kategori_pinjaman"></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="input-group input-group-static my-3">
                                            <label for="pimpinan">Nama Pimpinan</label>
                                            <input autocomplete="off" type="text" name="pimpinan" id="pimpinan"
                                                class="form-control" value="{{ $lembaga_lain->ketua }}">
                                            <small class="text-danger" id="msg_pimpinan"></small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="input-group input-group-static my-3">
                                            <label for="penanggung_jawab">Nama Penanggung Jawab</label>
                                            <input autocomplete="off" type="text" name="penanggung_jawab"
                                                id="penanggung_jawab" class="form-control"
                                                value="{{ $lembaga_lain->sekretaris }}">
                                            <small class="text-danger" id="msg_penanggung_jawab"></small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="input-group input-group-static my-3">
                                            <label for="nomor_identitas_lembaga">Nomor Identitas Lembaga</label>
                                            <input autocomplete="off" type="text" name="nomor_identitas_lembaga"
                                                id="nomor_identitas_lembaga" class="form-control"
                                                value="{{ $lembaga_lain->pass }}">
                                            <small class="text-danger" id="msg_nomor_identitas_lembaga"></small>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            @if (in_array('data_lembaga_lain.edit_lembaga', Session::get('tombol')))
                                <button type="submit" id="SimpanLembaga" class="btn btn-github btn-sm float-end">
                                    Simpan Lembaga
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <form action="" method="post" id="formDelete">
                @method('DELETE')
                @csrf
            </form>

        </div>

        <div class="card mt-3">
            <div class="card-body p-2">
                <a href="/database/lembaga_lain" class="btn btn-sm btn-info float-end mb-0">Kembali</a>
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
        new Choices($('#kategori_pinjaman')[0], {
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
            var kd_lembaga = $('#kd_lembaga').val()
            $.get('/database/lembaga_lain/generatekode?kode=' + kd_desa + '&kd_lembaga=' + kd_lembaga, function(
                result) {
                $('#kode_lembaga').val(result.kode)
            })
        })

        $(document).on('click', '#SimpanLembaga', function(e) {
            e.preventDefault()
            $('small').html('')

            var form = $('#FormEditLembaga')
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

        $(document).on('click', '#deleteLembaga', function(e) {
            e.preventDefault()

            var action = $(this).attr('data-link')

            Swal.fire({
                title: 'Hapus Lembaga Ini?',
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
                                    window.location.href = '/database/lembaga_lain'
                                })
                            } else {
                                Swal.fire('Peringatan', 'Lembaga gagal dihapus', 'warning')
                            }
                        }
                    })
                }
            })
        })

        $(document).on('click', '.blockquote', function(e) {
            e.preventDefault()

            var link = $(this).attr('data-link')
            if ($(this).attr('id') != 'deleteLembaga') {
                window.location.href = link
            }
        })
    </script>
@endsection
