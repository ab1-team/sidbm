@extends('layouts.base')

@section('content')
    <div class="card">
        <div class="card-body" id="RegisterLembaga">
            <form action="/database/lembaga_lain" method="post" id="FormRegistrasilembaga">
                @csrf

                <div class="row">
                    <div class="col-md-5">
                        <div class="my-2">
                            <label class="form-label" for="desa">Desa/Kelurahan</label>
                            <select class="form-control" name="desa" id="desa">
                                @foreach ($desa as $ds)
                                    <option {{ $desa_dipilih == $ds->kd_desa ? 'selected' : '' }}
                                        value="{{ $ds->kd_desa }}">
                                        {{ $ds->sebutan_desa->sebutan_desa }}
                                        {{ $ds->nama_desa }}{{ $ds->kec ? ', ' . $ds->kec->nama_kec : ', ' . $ds->nama_kec }}
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
                                class="form-control" readonly>
                            <small class="text-danger" id="msg_kode_lembaga"></small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group input-group-static my-3">
                            <label for="nama_lembaga">Nama Lembaga</label>
                            <input autocomplete="off" type="text" name="nama_lembaga" id="nama_lembaga"
                                class="form-control">
                            <small class="text-danger" id="msg_nama_lembaga"></small>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-5">
                        <div class="input-group input-group-static my-3">
                            <label for="alamat_lembaga">Alamat Lembaga</label>
                            <input autocomplete="off" type="text" name="alamat_lembaga" id="alamat_lembaga"
                                class="form-control">
                            <small class="text-danger" id="msg_alamat_lembaga"></small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group input-group-static my-3">
                            <label for="telpon">No. HP (Aktif WA)</label>
                            <input autocomplete="off" type="text" name="telpon" id="telpon" class="form-control"
                                value="628">
                            <small class="text-danger" id="msg_telpon"></small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="my-2">
                            <label class="form-label" for="kategori_pinjaman">Kategori Pinjaman</label>
                            <select class="form-control" name="kategori_pinjaman" id="kategori_pinjaman">
                                @foreach ($fungsi_kelompok as $fk)
                                    <option value="{{ $fk->id }}">
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
                            <input autocomplete="off" type="text" name="pimpinan" id="pimpinan" class="form-control">
                            <small class="text-danger" id="msg_pimpinan"></small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group input-group-static my-3">
                            <label for="penanggung_jawab">Nama Penanggung Jawab</label>
                            <input autocomplete="off" type="text" name="penanggung_jawab" id="penanggung_jawab"
                                class="form-control">
                            <small class="text-danger" id="msg_penanggung_jawab"></small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group input-group-static my-3">
                            <label for="nomor_identitas_lembaga">Nomor Identitas Lembaga</label>
                            <input autocomplete="off" type="text" name="nomor_identitas_lembaga"
                                id="nomor_identitas_lembaga" class="form-control">
                            <small class="text-danger" id="msg_nomor_identitas_lembaga"></small>
                        </div>
                    </div>
                </div>
            </form>

            <button type="submit" id="Simpanlembaga" class="btn btn-github btn-sm float-end">Simpan lembaga</button>
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

            generateKode()
        })

        $(document).on('click', '#Simpanlembaga', function(e) {
            e.preventDefault()
            $('small').html('')

            var form = $('#FormRegistrasilembaga')
            $.ajax({
                type: 'POST',
                url: form.attr('action'),
                data: form.serialize(),
                success: function(result) {
                    var desa = result.desa
                    Swal.fire('Berhasil', result.msg, 'success').then(() => {
                        Swal.fire({
                            title: 'Tambah Lembaga Baru?',
                            text: "",
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Ya',
                            cancelButtonText: 'Tidak'
                        }).then((res) => {
                            if (res.isConfirmed) {
                                window.location.reload()
                            } else {
                                window.location.href = '/database/lembaga_lain'
                            }
                        })
                    })
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

        function generateKode() {
            var kd_desa = $('#desa').val()
            $.get('/database/lembaga_lain/generatekode?kode=' + kd_desa, function(result) {
                $('#kode_lembaga').val(result.kode)
            })
        }

        generateKode()
    </script>
@endsection
