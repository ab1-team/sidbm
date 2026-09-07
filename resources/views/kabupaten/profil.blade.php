@extends('kabupaten.layout.base')

@section('content')
    <div class="card mb-4 mt-4">
        <div class="card-header pb-0">
            <h5>Identitas Kabupaten</h5>
        </div>
        <div class="card-body pt-0">
            <dl class="row mb-0">
                <dt class="col-sm-3">Nama Kabupaten</dt>
                <dd class="col-sm-9">{{ $kab->nama_kab }}</dd>

                <dt class="col-sm-3">Kode Provinsi</dt>
                <dd class="col-sm-9">{{ $kab->kd_prov }}</dd>

                <dt class="col-sm-3">Kode Kabupaten</dt>
                <dd class="col-sm-9">{{ $kab->kd_kab }}</dd>

                <dt class="col-sm-3">Username</dt>
                <dd class="col-sm-9">{{ $kab->uname }}</dd>

                <dt class="col-sm-3">Domain</dt>
                <dd class="col-sm-9">{{ $kab->web_kab ?: '-' }}</dd>

                <dt class="col-sm-3">Domain Alternatif</dt>
                <dd class="col-sm-9">{{ $kab->web_kab_alternatif ?: '-' }}</dd>
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card-header pb-0">
            <h5>Profil Kabupaten</h5>
        </div>
        <div class="card-body pt-0">
            <form action="/kab/profil/simpan" method="post" id="formProfil">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group input-group-static my-3">
                            <label>Nama Lembaga</label>
                            <input type="text" class="form-control" name="nama_lembaga" id="nama_lembaga"
                                value="{{ $kab->nama_lembaga }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group input-group-static my-3">
                            <label>Telepon</label>
                            <input type="text" class="form-control" name="telpon_kab" id="telpon_kab"
                                value="{{ $kab->telpon_kab }}">
                        </div>
                    </div>
                </div>

                <div class="input-group input-group-static my-3">
                    <label>Alamat</label>
                    <input type="text" class="form-control" name="alamat_kab" id="alamat_kab"
                        value="{{ $kab->alamat_kab }}">
                </div>

                <div class="input-group input-group-static my-3">
                    <label>Email</label>
                    <input type="email" class="form-control" name="email_kab" id="email_kab"
                        value="{{ $kab->email_kab }}">
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group input-group-static my-3">
                            <label>Password Baru</label>
                            <input type="password" class="form-control" name="password" id="password"
                                placeholder="Kosongkan jika tidak diubah">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group input-group-static my-3">
                            <label>Konfirmasi Password</label>
                            <input type="password" class="form-control" name="password_konfirmasi"
                                id="password_konfirmasi" placeholder="Kosongkan jika tidak diubah">
                        </div>
                    </div>
                </div>
            </form>

            <div class="d-flex justify-content-end mt-3">
                <button type="button" id="simpanProfil" class="btn btn-github btn-sm ms-2">
                    Simpan Perubahan
                </button>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(document).on('click', '#simpanProfil', function(e) {
            e.preventDefault()

            var form = $('#formProfil')
            $.ajax({
                type: form.attr('method'),
                url: form.attr('action'),
                data: form.serialize(),
                success: function(result) {
                    if (result.success) {
                        Toastr('success', result.msg)
                    }
                },
                error: function(result) {
                    Toastr('error', result.responseJSON.msg)
                }
            })
        })
    </script>
@endsection
