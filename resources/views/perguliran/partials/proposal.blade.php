<form action="/perguliran/{{ $perguliran->id }}" method="post" id="FormInput">
    @csrf
    @method('PUT')

    <div class="card mb-3">
        <div class="card-body">
            <div class="row mt-0">
                <div class="col-md-6 mb-3">
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
                        </ul>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="border border-light border-2 border-radius-md p-3">
                        <h6 class="text-info text-gradient mb-0">
                            &nbsp;
                        </h6>
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center text-sm">
                                Jasa
                                <span class="badge badge-info badge-pill">
                                    {{ $perguliran->pros_jasa . '% / ' . $perguliran->jangka . ' ' . \App\Utils\Pinjaman::satuanJangka($perguliran, false) }}
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
            </div>

            @if (!($perguliran->jenis_pp == '3' && $perguliran->kelompok->fungsi_kelompok == '2'))
                @if (in_array('tahapan_perguliran.proposal.tambah_pemanfaat', Session::get('tombol')))
                    <div class="d-grid">
                        <button type="button" id="BtnTambahPemanfaat" data-bs-toggle="modal"
                            data-bs-target="#TambahPemanfaat" class="btn btn-success btn-sm mb-1">
                            Tambah Pemanfaat
                        </button>
                    </div>
                    <hr class="horizontal dark">
                @endif
            @endif

            @if (!($perguliran->jenis_pp == '3' && $perguliran->kelompok->fungsi_kelompok == '2'))
                <div class="table-responsive">
                    <table class="table table-striped align-items-center mb-0" width="100%">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th>#</th>
                                <th>Nama</th>
                                <th>Pengajuan</th>
                                <th>Verifikasi</th>
                                <th>Catatan</th>
                                <th>&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $proposal = 0;
                            @endphp
                            @foreach ($perguliran->pinjaman_anggota as $pinjaman_anggota)
                                @php
                                    $proposal += $pinjaman_anggota->proposal;

                                    $class1 = '';
                                    $class2 = '';
                                    if (in_array('tahapan_perguliran.proposal.edit_proposal', Session::get('tombol'))) {
                                        $class1 = 'idpa_proposal';
                                    }

                                    if (
                                        in_array(
                                            'tahapan_perguliran.proposal.simpan_rekom_verifikator',
                                            Session::get('tombol'),
                                        )
                                    ) {
                                        $class2 = 'idpa';
                                    }
                                @endphp
                                <tr>
                                    <td align="center">{{ $loop->iteration }}</td>
                                    <td>
                                        {{ ucwords($pinjaman_anggota->anggota->namadepan) }}
                                        ({{ $pinjaman_anggota->nia }})
                                    </td>
                                    <td>
                                        <div class="input-group input-group-static">
                                            <input type="text" id="{{ $pinjaman_anggota->id }}"
                                                name="idpa_proposal[{{ $pinjaman_anggota->id }}]"
                                                class="form-control money {{ $class1 }}"
                                                value="{{ number_format($pinjaman_anggota->proposal, 2) }}">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group input-group-static">
                                            <input type="text" name="idpa[{{ $pinjaman_anggota->id }}]"
                                                class="form-control money {{ $class2 }} idpa-{{ $pinjaman_anggota->id }}"
                                                value="{{ number_format($pinjaman_anggota->proposal, 2) }}">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group input-group-static">
                                            <input type="text" class="form-control"
                                                name="catatan[{{ $pinjaman_anggota->id }}]" value="">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" id="{{ $pinjaman_anggota->id }}"
                                                class="btn btn-icon btn-sm btn-danger HapusPinjamanAnggota">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2">Jumlah</th>
                                <th id="jumlah">
                                    {{ number_format($proposal, 2) }}
                                </th>
                                <th>
                                    <span id="_verifikasi">{{ number_format($proposal, 2) }}</span>
                                    <input type="hidden" name="__verifikasi" id="__verifikasi"
                                        value="{{ $proposal }}">
                                </th>
                                <th></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>

    @if (in_array('tahapan_perguliran.proposal.cetak_dokumen_proposal', Session::get('tombol')))
        <div class="card card-body p-2 pb-0 mb-3">
            <div class="row">
                <div class="col-12 col-sm-6 col-md-6">
                    <div class="d-grid">
                        <button type="button" data-bs-toggle="modal" data-bs-target="#CetakDokumenProposal"
                            class="btn btn-info btn-sm mb-2">Cetak Dokumen Proposal</button>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-6">
                    <div class="d-grid">
                        <button type="button" id="cetakRekomTV" class="btn btn-info btn-sm mb-2">
                            Form Verifikasi
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if (in_array('tahapan_perguliran.proposal.simpan_rekom_verifikator', Session::get('tombol')))
        <div class="card mb-3">
            <div class="card-header pb-0 p-3">
                <h6>
                    Input Rekom Verifikator
                </h6>
            </div>
            <div class="card-body p-3">
                <input type="hidden" name="_id" id="_id" value="{{ $perguliran->id }}">
                <input type="hidden" name="status" id="status" value="V">
                <div class="row">
                    <div class="col-md-3">
                        <div class="input-group input-group-static my-3">
                            <label for="tgl_verifikasi">Tgl Verifikasi</label>
                            <input autocomplete="off" type="text" name="tgl_verifikasi" id="tgl_verifikasi"
                                class="form-control date" value="{{ Tanggal::tglIndo($perguliran->tgl_proposal) }}">
                            <small class="text-danger" id="msg_tgl_verifikasi"></small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group input-group-static my-3">
                            <label for="verifikasi">Verifikasi Rp.</label>
                            <input autocomplete="off" type="text" name="verifikasi" id="verifikasi"
                                class="form-control money" value="{{ number_format($perguliran->proposal, 2) }}">
                            <small class="text-danger" id="msg_verifikasi"></small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group input-group-static my-3">
                            <label for="jangka">Jangka</label>
                            <input autocomplete="off" type="number" name="jangka" id="jangka"
                                class="form-control" value="{{ $perguliran->jangka }}">
                            <small class="text-danger" id="msg_jangka"></small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group input-group-static my-3">
                            <label for="pros_jasa">Prosentase Jasa (%)</label>
                            <input autocomplete="off" type="number" name="pros_jasa" id="pros_jasa"
                                class="form-control" value="{{ $perguliran->pros_jasa }}">
                            <small class="text-danger" id="msg_pros_jasa"></small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="my-2">
                            <label class="form-label" for="jenis_jasa">Jenis Jasa</label>
                            <select class="form-control" name="jenis_jasa" id="jenis_jasa">
                                @foreach ($jenis_jasa as $jj)
                                    <option {{ $jj->id == $perguliran->jenis_jasa ? 'selected' : '' }}
                                        value="{{ $jj->id }}">
                                        {{ $jj->nama_jj }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-danger" id="msg_jenis_jasa"></small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="my-2">
                            <label class="form-label" for="sistem_angsuran_pokok">Sistem Angs. Pokok</label>
                            <select class="form-control" name="sistem_angsuran_pokok" id="sistem_angsuran_pokok">
                                @foreach ($sistem_angsuran as $sa)
                                    <option {{ $sa->id == $perguliran->sistem_angsuran ? 'selected' : '' }}
                                        value="{{ $sa->id }}">
                                        {{ $sa->nama_sistem }} ({{ $sa->deskripsi_sistem }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-danger" id="msg_sistem_angsuran_pokok"></small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="my-2">
                            <label class="form-label" for="sistem_angsuran_jasa">Sistem Angs. Jasa</label>
                            <select class="form-control" name="sistem_angsuran_jasa" id="sistem_angsuran_jasa">
                                @foreach ($sistem_angsuran as $sa)
                                    <option {{ $sa->id == $perguliran->sa_jasa ? 'selected' : '' }}
                                        value="{{ $sa->id }}">
                                        {{ $sa->nama_sistem }} ({{ $sa->deskripsi_sistem }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-danger" id="msg_sistem_angsuran_jasa"></small>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="input-group input-group-static my-3">
                            <label for="catatan_verifikasi">Catatan Verifikasi</label>
                            <textarea class="form-control" name="catatan_verifikasi" id="catatan_verifikasi" rows="3"
                                placeholder="Catatan" spellcheck="false">{{ $perguliran->catatan_verifikasi }}</textarea>
                            <small class="text-danger" id="msg_catatan_verifikasi"></small>
                        </div>
                    </div>
                </div>

                <button type="button" id="Simpan" class="btn btn-github float-end btn-sm">
                    Simpan Rekom Verifikator
                </button>
            </div>
        </div>
    @endif
</form>

<form action="/perguliran/dokumen?status=P&jenis=dokumen_verifikasi" target="_blank" method="post"
    id="formCetakRekomTV">
    @csrf

    <input type="hidden" name="id" value="{{ $perguliran->id }}">
    <input type="hidden" name="report" value="formVerifikasi#pdf">
</form>

<script>
    var formatter = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })

    new Choices($('#jenis_jasa')[0], {
        shouldSort: false,
        fuseOptions: {
            threshold: 0.1,
            distance: 1000
        }
    })
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

    $(".money").maskMoney();

    $(".date").flatpickr({
        dateFormat: "d/m/Y"
    })

    $('.idpa_proposal').change(function(e) {

        var idpa = $(this).attr('id')
        var value = $(this).val()

        $.ajax({
            url: '/pinjaman_anggota/' + idpa,
            type: 'post',
            data: {
                '_method': 'PUT',
                'idpa': idpa,
                'proposal': value,
                'status': 'P',
                '_token': $('[name=_token]').val()
            },
            success: function(result) {
                var total = 0;
                $('.idpa_proposal').map(function() {
                    var idpa = $(this).attr('id')
                    var value = $(this).val()

                    $('.idpa-' + idpa).val(value)

                    value = value.split(',').join('')
                    value = value.split('.00').join('')
                    value = parseFloat(value)

                    total += value

                })

                $('#jumlah').html(result.jumlah)
                $('#verifikasi').val(result.jumlah)
                $('#_verifikasi').html(result.jumlah)
                $('#__verifikasi').val(result.jumlah)
            }
        })
    })

    $(document).on('change', '.idpa', function(e) {
        var total = 0;
        $('.idpa').map(function() {
            var value = $(this).val()
            if (value == '') {
                value = 0
            } else {
                value = value.split(',').join('')
                value = value.split('.00').join('')
            }

            console.log(value);
            value = parseFloat(value)

            total += value
        })

        $('#__verifikasi').val(total)
        $('#_verifikasi').html(formatter.format(total))
        $('#verifikasi').val(formatter.format(total))
    })

    $(document).on('click', '#Simpan', async function(e) {
        e.preventDefault()
        $('small').html('')

        var verifikasi = parseInt($('#verifikasi').val().split(',').join('').split('.00').join(''))
        var __verifikasi = parseInt($('#__verifikasi').val())

        var lanjut = true;
        if ('{{ $perguliran->jenis_pp }}' != '3') {
            if (verifikasi != __verifikasi) {
                lanjut = await Swal.fire({
                    title: 'Peringatan',
                    text: 'Jumlah verifikasi Anggota dan Kelompok Berbeda. Tetap lanjutkan?',
                    showCancelButton: true,
                    confirmButtonText: 'Lanjutkan',
                    cancelButtonText: 'Batal',
                    icon: 'warning'
                }).then((result) => {
                    if (result.isConfirmed) {
                        return true;
                    }

                    return false
                })
            }
        }

        if (lanjut) {
            var form = $('#FormInput')
            $.ajax({
                type: 'POST',
                url: form.attr('action'),
                data: form.serialize(),
                success: function(result) {
                    Swal.fire('Berhasil', result.msg, 'success').then(() => {
                        window.location.href = '/detail/' + result.id
                    })
                },
                error: function(result) {
                    const respons = result.responseJSON;

                    Swal.fire('Error', 'Cek kembali input yang anda masukkan', 'error')
                    $.map(respons, function(res, key) {
                        $('#' + key).parent('.input-group.input-group-static')
                            .addClass(
                                'is-invalid')
                        $('#msg_' + key).html(res)
                    })
                }
            })
        }
    })
</script>
