@extends('admin.layout.base')

@section('content')
    <div class="card mb-3">
        <div class="card-body p-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0">Manajemen License</h5>
                    <small class="text-muted">
                        api_secret berasal dari aplikasi level pusat. 1 kecamatan = 1 license.
                    </small>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-github btn-sm" id="tambahLicense">
                        <i class="material-icons" style="font-size:16px; vertical-align:middle">add</i>
                        Tambah License
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-flush table-hover" id="tableLicense" width="100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Kecamatan</th>
                            <th>API Secret</th>
                            <th>Status</th>
                            <th>Expired</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalLicense" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalLicenseTitle">Tambah License</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalLicenseBody">
                    <div class="text-center py-5">
                        <i class="material-icons" style="font-size:32px">hourglass_empty</i>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        var tableLicense;

        $(function() {
            tableLicense = $('#tableLicense').DataTable({
                language: {
                    paginate: {
                        previous: "&laquo;",
                        next: "&raquo;"
                    }
                },
                processing: true,
                serverSide: true,
                ajax: "/master/license",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'kecamatan', name: 'kecamatan' },
                    { data: 'api_secret', name: 'api_secret' },
                    { data: 'is_active', name: 'is_active' },
                    { data: 'expired_at', name: 'expired_at' },
                    { data: 'aksi', name: 'aksi', orderable: false, searchable: false }
                ]
            });

            new Choices($('#modalLicenseBody').find('select').get(0), {
                shouldSort: false
            });
        });

        $(".date").flatpickr({
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            time_24hr: true
        });

        // Tambah
        $(document).on('click', '#tambahLicense', function(e) {
            e.preventDefault();
            $('#modalLicenseTitle').text('Tambah License');
            $('#modalLicenseBody').html('<div class="text-center py-5"><i class="material-icons" style="font-size:32px">hourglass_empty</i></div>');
            $('#modalLicense').modal('show');

            $.get('/master/license/create', function(result) {
                $('#modalLicenseBody').html(result.view);
                bindForm();
                initChoicesAndDate();
            });
        });

        // Edit
        $(document).on('click', '.edit-license', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            $('#modalLicenseTitle').text('Edit License');
            $('#modalLicenseBody').html('<div class="text-center py-5"><i class="material-icons" style="font-size:32px">hourglass_empty</i></div>');
            $('#modalLicense').modal('show');

            $.get('/master/license/' + id + '/edit', function(result) {
                $('#modalLicenseBody').html(result.view);
                bindForm();
                initChoicesAndDate();
            });
        });

        // Hapus
        $(document).on('click', '.hapus-license', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            Swal.fire({
                title: 'Hapus License?',
                text: 'Tindakan ini tidak dapat dibatalkan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Hapus',
                cancelButtonText: 'Batal'
            }).then(function(result) {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '/master/license/' + id,
                        type: 'POST',
                        data: {
                            _method: 'DELETE',
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(res) {
                            Swal.fire('Berhasil', res.msg, 'success');
                            tableLicense.ajax.reload();
                        },
                        error: function() {
                            Swal.fire('Error', 'Gagal menghapus license', 'error');
                        }
                    });
                }
            });
        });

        function initChoicesAndDate() {
            var sel = $('#modalLicenseBody').find('select').get(0);
            if (sel) {
                new Choices(sel, { shouldSort: false });
            }
            $('#modalLicenseBody').find('.date').flatpickr({
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                time_24hr: true
            });
        }

        function bindForm() {
            $('#formLicense').on('submit', function(e) {
                e.preventDefault();
                var $form = $(this);
                var mode = $form.data('mode');
                var id = $form.find('input[name="id"]').val();
                var url = mode === 'edit' ? '/master/license/' + id : '/master/license';
                var data = $form.serializeArray();

                // is_active checkbox unchecked → kirim 0
                if (!$form.find('input[name="is_active"]').is(':checked')) {
                    data.push({ name: 'is_active', value: 0 });
                } else {
                    data.push({ name: 'is_active', value: 1 });
                }

                $('#modalLicenseBody small.text-danger').html('');

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: data,
                    success: function(res) {
                        $('#modalLicense').modal('hide');
                        Swal.fire('Berhasil', res.msg, 'success');
                        tableLicense.ajax.reload();
                    },
                    error: function(xhr) {
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            $.each(xhr.responseJSON.errors, function(key, val) {
                                $('#msg_' + key).html(val[0]);
                            });
                        } else {
                            Swal.fire('Error', 'Cek kembali input yang anda masukkan', 'error');
                        }
                    }
                });
            });
        }
    </script>
@endsection
