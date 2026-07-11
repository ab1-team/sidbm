@extends('layouts.base')

@section('content')
    <div class="row mb-5">
        <div class="col-lg-3">
            <div class="card position-sticky top-10">
                <ul class="nav flex-column bg-white border-radius-lg p-3">
                    <li class="nav-item mb-2">
                        <b>Pengaturan</b>
                    </li>
                    @if (in_array('personalisasi_sop.identitas_lembaga', Session::get('tombol')))
                        <li class="nav-item">
                            <a class="nav-link text-dark d-flex" data-scroll="" href="#lembaga">
                                <i class="material-icons text-lg me-2">business</i>
                                <span class="text-sm">Identitas Lembaga</span>
                            </a>
                        </li>
                    @endif
                    @if (in_array('personalisasi_sop.sebutan_pengelola', Session::get('tombol')))
                        <li class="nav-item pt-2">
                            <a class="nav-link text-dark d-flex" data-scroll="" href="#personalia">
                                <i class="material-icons text-lg me-2">assignment_ind</i>
                                <span class="text-sm">Sebutan Personalia</span>
                            </a>
                        </li>
                    @endif
                    @if (in_array('personalisasi_sop.sebutan_pengelola', Session::get('tombol')))
                        <li class="nav-item pt-2">
                            <a class="nav-link text-dark d-flex" data-scroll="" href="#pengelola">
                                <i class="material-icons text-lg me-2">assignment_ind</i>
                                <span class="text-sm">Sebutan Pengelola</span>
                            </a>
                        </li>
                    @endif
                    @if (in_array('personalisasi_sop.sistem_pinjaman', Session::get('tombol')))
                        <li class="nav-item pt-2">
                            <a class="nav-link text-dark d-flex" data-scroll="" href="#pinjaman">
                                <i class="material-icons text-lg me-2">equalizer</i>
                                <span class="text-sm">Sistem Pinjaman</span>
                            </a>
                        </li>
                    @endif
                    @if (in_array('personalisasi_sop.pengaturan_asuransi', Session::get('tombol')))
                        <li class="nav-item pt-2">
                            <a class="nav-link text-dark d-flex" data-scroll="" href="#asuransi">
                                <i class="material-icons text-lg me-2">account_balance_wallet</i>
                                <span class="text-sm">Pengaturan Asuransi</span>
                            </a>
                        </li>
                    @endif
                    @if (in_array('personalisasi_sop.redaksi_dokumen_spk', Session::get('tombol')))
                        <li class="nav-item pt-2">
                            <a class="nav-link text-dark d-flex" data-scroll="" href="#redaksi_spk">
                                <i class="material-icons text-lg me-2">description</i>
                                <span class="text-sm">Redaksi Dok. SPK</span>
                            </a>
                        </li>
                    @endif
                    @if (in_array('personalisasi_sop.upload_logo', Session::get('tombol')))
                        <li class="nav-item pt-2">
                            <a class="nav-link text-dark d-flex" data-scroll="" href="#logo">
                                <i class="material-icons text-lg me-2">crop_original</i>
                                <span class="text-sm">Logo</span>
                            </a>
                        </li>
                    @endif
                    @if (in_array('personalisasi_sop.scan_whatsapp', Session::get('tombol')))
                        <li class="nav-item pt-2">
                            <a class="nav-link text-dark d-flex" data-scroll="" href="#whatsapp">
                                <i class="material-icons text-lg me-2">priority_high</i>
                                <span class="text-sm">Whatsapp</span>
                            </a>
                        </li>
                    @endif
                    @if (in_array('personalisasi_sop.berita_acara_pergantian_laporan', Session::get('tombol')))
                        <li class="nav-item pt-2">
                            <a class="nav-link text-dark d-flex" data-scroll="" href="#berita_acara">
                                <i class="material-icons text-lg me-2">insert_drive_file</i>
                                <span class="text-sm">Berita Acara</span>
                            </a>
                        </li>
                    @endif
                    @if (in_array('personalisasi_sop.isian_tanggung_renteng_pinjaman', Session::get('tombol')))
                        <li class="nav-item pt-2">
                            <a class="nav-link text-dark d-flex" data-scroll="" href="#tanggung_renteng">
                                <i class="material-icons text-lg me-2">insert_drive_file</i>
                                <span class="text-sm">Tanggung Renteng</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="col-lg-9 mt-lg-0 mt-4">
            <div class="card" id="app-token">
                <div class="card-header">
                    <h5 class="mb-0">Token Aplikasi</h5>
                </div>
                <div class="card-body pt-0">
                    @include('sop.partials._app_token')
                </div>
            </div>
            @if (in_array('personalisasi_sop.identitas_lembaga', Session::get('tombol')))
                <div class="card mt-4" id="lembaga">
                    <div class="card-header">
                        <h5 class="mb-0">Identitas Lembaga</h5>
                    </div>
                    <div class="card-body pt-0">
                        @include('sop.partials._lembaga')
                    </div>
                </div>
            @endif
            @if (in_array('personalisasi_sop.sebutan_pengelola', Session::get('tombol')))
                <div class="card mt-4" id="personalia">
                    <div class="card-header">
                        <h5 class="mb-0">Sebutan Personalia Bumdesma</h5>
                    </div>
                    <div class="card-body pt-0">
                        @include('sop.partials._personalia')
                    </div>
                </div>
            @endif
            @if (in_array('personalisasi_sop.sebutan_pengelola', Session::get('tombol')))
                <div class="card mt-4" id="pengelola">
                    <div class="card-header">
                        <h5 class="mb-0">Sebutan Pengelola Bumdesma</h5>
                    </div>
                    <div class="card-body pt-0">
                        @include('sop.partials._pengelola')
                    </div>
                </div>
            @endif
            @if (in_array('personalisasi_sop.sistem_pinjaman', Session::get('tombol')))
                <div class="card mt-4" id="pinjaman">
                    <div class="card-header">
                        <h5 class="mb-0">Sistem Pinjaman</h5>
                    </div>
                    <div class="card-body pt-0">
                        @include('sop.partials._pinjaman')
                    </div>
                </div>
            @endif
            @if (in_array('personalisasi_sop.pengaturan_asuransi', Session::get('tombol')))
                <div class="card mt-4" id="asuransi">
                    <div class="card-header">
                        <h5 class="mb-0">Pengaturan Asuransi</h5>
                    </div>
                    <div class="card-body pt-0">
                        @include('sop.partials._asuransi')
                    </div>
                </div>
            @endif
            @if (in_array('personalisasi_sop.redaksi_dokumen_spk', Session::get('tombol')))
                <div class="card mt-4" id="redaksi_spk">
                    <div class="card-header">
                        <h5 class="mb-0">Redaksi Dokumen SPK</h5>
                    </div>
                    <div class="card-body pt-0">
                        @include('sop.partials._spk')
                    </div>
                </div>
            @endif
            @if (in_array('personalisasi_sop.upload_logo', Session::get('tombol')))
                <div class="card mt-4" id="logo">
                    <div class="card-header">
                        <h5 class="mb-0">Upload Logo</h5>
                    </div>
                    <div class="card-body pt-0">
                        @include('sop.partials._logo')
                    </div>
                </div>
            @endif
            @if (in_array('personalisasi_sop.scan_whatsapp', Session::get('tombol')))
                <div class="card mt-4" id="whatsapp">
                    <div class="card-header">
                        <h5 class="mb-0">Pengaturan Whatsapp</h5>
                    </div>
                    <div class="card-body pt-0">
                        @include('sop.partials._whatsapp')
                    </div>
                </div>
            @endif
            @if (in_array('personalisasi_sop.berita_acara_pergantian_laporan', Session::get('tombol')))
                <div class="card mt-4" id="berita_acara">
                    <div class="card-header">
                        <h5 class="mb-0">Berita Acara Pergantian Laporan</h5>
                    </div>
                    <div class="card-body pt-0">
                        @include('sop.partials._berita_acara')
                    </div>
                </div>
            @endif
            @if (in_array('personalisasi_sop.isian_tanggung_renteng_pinjaman', Session::get('tombol')))
                <div class="card mt-4" id="tanggung_renteng">
                    <div class="card-header">
                        <h5 class="mb-0">Pengaturan Tanggung Renteng Pinjaman</h5>
                    </div>
                    <div class="card-body pt-0">
                        @include('sop.partials._tanggung_renteng')
                    </div>
                </div>
            @endif
        </div>

        {{-- Modal Scan Whatsapp --}}
        <div class="modal fade" id="ModalScanWA" tabindex="-1" aria-labelledby="ModalScanWALabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="ModalScanWALabel">
                            Aktivasi Whatsapp Gateway
                        </h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="LayoutModalScanWA">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-xl-5 col-lg-6 text-center">
                                            <img class="w-100 border-radius-lg shadow-lg mx-auto"
                                                src="/assets/img/no_image.png" id="QrCode" alt="chair">
                                        </div>
                                        <div class="col-lg-5 mx-auto">
                                            <h3 class="mt-lg-0 mt-4">Scan kode QR</h3>
                                            <ul class="list-group list-group-flush rounded" id="ListConnection">
                                                <li class="list-group-item">
                                                    Membuat Kode QR
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        {{-- <button type="button" class="btn btn-warning btn-sm" id="WaLogout">Logout</button> --}}
                        <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form action="/pengaturan/whatsapp/{{ $token }}" method="post" id="FormWhatsapp">
        @csrf
    </form>
@endsection

@section('script')
    <script>
        let ListContainer = $('#ListConnection')
        const API = '{{ $api }}'
        const MASTER_KEY = '{{ $api_key }}'
        const SAVED_INSTANCE = '{{ $instance_name }}'

        const LOKASI_ID = '{{ $kec->id }}'
        const KODE_KEC = '{{ $kec->kd_kec }}'
        const NAMA_KEC = `{{ $kec->nama_lembaga_sort ?? $kec->nama_kec ?? "kec" }}`

        let pollInterval = null
        let qrPollInterval = null

        function setIdleState() {
            $('#HapusWa, #ScanWA, #PairWA, #InstanceInfo').hide()
            $('#CreateInstance').show()
            $('#InstanceName').text('')
        }

        function setActiveState(instance) {
            $('#CreateInstance, #ScanWA, #PairWA').hide()
            $('#HapusWa').show()
            $('#InstanceInfo').show()
            $('#InstanceName').text(instance)
        }

        function setPendingState(instance) {
            $('#CreateInstance, #PairWA').hide()
            $('#HapusWa, #ScanWA, #InstanceInfo').show()
            $('#InstanceName').text(instance)
        }

        function pollConnectionState(instance) {
            pollInterval = setInterval(() => {
                $.ajax({
                    type: 'GET',
                    url: '/pengaturan/whatsapp/connection_state',
                    success: function(res) {
                        console.log('Connection state:', res)
                        if (res.state === 'open') {
                            clearInterval(pollInterval)
                            $('#ListConnection').html(
                                '<li class="list-group-item list-group-item-success fw-bold">Whatsapp Aktif</li>'
                            )
                            $('#QrCode').attr('src', '/assets/img/no_image.png')
                            setActiveState(instance)
                            Toastr('success', 'WhatsApp berhasil terhubung')

                            setTimeout(() => {
                                if ($('#ModalScanWA').hasClass('show')) {
                                    $('#ModalScanWA').modal('hide')
                                }
                            }, 1000)
                        } else if (res.state === 'close' || res.state === 'refused') {
                            clearInterval(pollInterval)
                            Toastr('error', 'Koneksi ditutup oleh gateway')
                        }
                    },
                    error: function() {
                        console.warn('Polling error')
                    }
                })
            }, 3000)
        }

        function pollQr() {
            if (qrPollInterval) {
                clearInterval(qrPollInterval)
            }

            let attempts = 0
            qrPollInterval = setInterval(() => {
                attempts++
                if (attempts > 30) {
                    // stop after ~60s
                    clearInterval(qrPollInterval)
                    return
                }

                $.ajax({
                    type: 'GET',
                    url: '/pengaturan/whatsapp/qr',
                    success: function(res) {
                        console.log('QR poll:', res)
                        if (res.success && res.qr) {
                            clearInterval(qrPollInterval)
                            $('#QrCode').attr('src',
                                res.qr.startsWith('data:') ? res.qr : 'data:image/png;base64,' + res.qr
                            )
                            $('#ListConnection').html(
                                '<li class="list-group-item list-group-item-success fw-bold">Scan QR dari WhatsApp</li>'
                            )
                            Toastr('success', 'QR siap, silakan scan dari WhatsApp')
                        }
                    },
                    error: function() {
                        console.warn('QR poll error')
                    }
                })
            }, 2000)
        }

        $(document).ready(function() {
            if (SAVED_INSTANCE) {
                // Ada instance tersimpan → cek status
                $.ajax({
                    type: 'GET',
                    url: '/pengaturan/whatsapp/connection_state',
                    success: function(res) {
                        console.log('Initial state:', res)
                        if (res.state === 'open') {
                            setActiveState(SAVED_INSTANCE)
                        } else {
                            setPendingState(SAVED_INSTANCE)
                        }
                    },
                    error: function() {
                        setPendingState(SAVED_INSTANCE)
                    }
                })
            } else {
                setIdleState()
            }
        })

        $(document).on('click', '#CreateInstance', function(e) {
            e.preventDefault()

            Swal.fire({
                title: 'Aktivasi WhatsApp',
                text: 'Buat instance WhatsApp baru untuk kecamatan ini.',
                showCancelButton: true,
                confirmButtonText: 'Lanjutkan',
                cancelButtonText: 'Batal',
                icon: 'info'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: 'POST',
                        url: '/pengaturan/whatsapp/save_device',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(res) {
                            console.log('Create instance response:', res)
                            if (res.success) {
                                if (res.qr) {
                                    $('#QrCode').attr('src', res.qr.startsWith('data:') ? res.qr : 'data:image/png;base64,' + res.qr)
                                    $('#ListConnection').html(
                                        '<li class="list-group-item list-group-item-success fw-bold">Scan QR dari WhatsApp</li>'
                                    )
                                } else {
                                    $('#QrCode').attr('src', '/assets/img/no_image.png')
                                    $('#ListConnection').html(
                                        '<li class="list-group-item">Menunggu QR dari gateway...</li>'
                                    )
                                }

                                setPendingState(res.instance)
                                $('#ModalScanWA').modal('show')
                                pollConnectionState(res.instance)
                                if (! res.qr) {
                                    pollQr()
                                }
                            } else {
                                Swal.fire('Error', res.msg || 'Gagal membuat instance.', 'error')
                            }
                        },
                        error: function(xhr) {
                            Swal.fire('Error', 'Gagal terhubung ke gateway Evolution.', 'error')
                        }
                    })
                }
            })
        })

        $(document).on('click', '#ScanWA', function(e) {
            e.preventDefault()

            if (!SAVED_INSTANCE) return

            $('#ModalScanWA').modal('show')
            $('#QrCode').attr('src', '/assets/img/no_image.png')
            $('#ListConnection').html(
                '<li class="list-group-item">Menunggu QR dari gateway...</li>'
            )

            // Try /qr endpoint first (for existing instance); fall back to save_device (which may restart)
            $.ajax({
                type: 'GET',
                url: '/pengaturan/whatsapp/qr',
                success: function(res) {
                    if (res.qr) {
                        $('#QrCode').attr('src', res.qr.startsWith('data:') ? res.qr : 'data:image/png;base64,' + res.qr)
                        $('#ListConnection').html(
                            '<li class="list-group-item list-group-item-success fw-bold">Scan QR dari WhatsApp</li>'
                        )
                    } else {
                        $('#ListConnection').html(
                            '<li class="list-group-item">Menunggu QR dari gateway...</li>'
                        )
                        pollQr()
                    }
                    pollConnectionState(SAVED_INSTANCE)
                }
            })
        })

        $(document).on('click', '#HapusWa', function(e) {
            e.preventDefault()

            Swal.fire({
                title: 'Hapus WhatsApp',
                text: 'Hapus koneksi WhatsApp SIDBM.',
                showCancelButton: true,
                confirmButtonText: 'Hapus',
                cancelButtonText: 'Batal',
                icon: 'error'
            }).then((result) => {
                if (result.isConfirmed) {
                    if (pollInterval) clearInterval(pollInterval)
                    $.post('/pengaturan/whatsapp/delete_session', {
                        _token: '{{ csrf_token() }}',
                        lokasi: LOKASI_ID
                    }, function(res) {
                        window.location.reload()
                    }).fail(function() {
                        window.location.reload()
                    })
                }
            })
        })

        $(document).on('click', '#TambahPersonalia', function(e) {
            e.preventDefault()

            var newPersonalia = $('#newPersonalia').html()
            $('#FormPersonalia .row').append(newPersonalia)
        })
    </script>

    <script>
        $(".date").flatpickr({
            dateFormat: "d/m/Y"
        })

        var tahun = "{{ date('Y') }}"
        var bulan = "{{ date('m') }}"

        $(".money").maskMoney();
        new Choices($('#pembulatan')[0], {
            shouldSort: false,
            fuseOptions: {
                threshold: 0.1,
                distance: 1000
            }
        })
        new Choices($('#sistem')[0], {
            shouldSort: false,
            fuseOptions: {
                threshold: 0.1,
                distance: 1000
            }
        })
        new Choices($('#jenis_asuransi')[0], {
            shouldSort: false,
            fuseOptions: {
                threshold: 0.1,
                distance: 1000
            }
        })

        var quill = new Quill('#editor', {
            theme: 'snow'
        });

        var quill1 = new Quill('#ba-editor', {
            theme: 'snow'
        });

        var quill2 = new Quill('#tanggung-renteng-editor', {
            theme: 'snow'
        });

        $(document).on('click', '.btn-simpan', async function(e) {
            e.preventDefault()

            if ($(this).attr('id') == 'SimpanSPK') {
                await $('#spk').val(quill.container.firstChild.innerHTML)
            }

            if ($(this).attr('id') == 'SimpanBeritaAcara') {
                await $('#ba').val(quill1.container.firstChild.innerHTML)
            }

            if ($(this).attr('id') == 'SimpanTanggungRenteng') {
                await $('#tanggung-renteng').val(quill2.container.firstChild.innerHTML)
            }

            var form = $($(this).attr('data-target'))
            $.ajax({
                type: form.attr('method'),
                url: form.attr('action'),
                data: form.serialize(),
                success: function(result) {
                    if (result.success) {
                        Toastr('success', result.msg)

                        if (result.nama_lembaga) {
                            $('#nama_lembaga_sort').html(result.nama_lembaga)
                        }
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

        $(document).on('click', '#EditLogo', function(e) {
            e.preventDefault()

            $('#logo_kec').trigger('click')
        })

        $(document).on('change', '#logo_kec', function(e) {
            e.preventDefault()

            var logo = $(this).get(0).files[0]
            if (logo) {
                var form = $('#FormLogo')
                var formData = new FormData(document.querySelector('#FormLogo'));
                $.ajax({
                    type: form.attr('method'),
                    url: form.attr('action'),
                    data: formData,
                    contentType: false,
                    cache: false,
                    processData: false,
                    success: function(result) {
                        if (result.success) {
                            var reader = new FileReader();

                            reader.onload = function() {
                                $("#previewLogo").attr("src", reader.result);
                                $(".colored-shadow").css('background-image',
                                    "url(" + reader.result + ")")
                            }

                            reader.readAsDataURL(logo);
                            Toastr('success', result.msg)
                        } else {
                            Toastr('error', result.msg)
                        }
                    }
                })
            }
        })

        $(document).on('click', '#copy-token', function(e) {
            e.preventDefault();

            const token = $('#hidden-app-token').val();

            if (!token || token.trim() === '') {
                Toastr('error', 'Token tidak valid');
                return;
            }

            const textarea = document.createElement('textarea');
            textarea.value = token;

            textarea.style.position = 'fixed';
            textarea.style.top = '-9999px';
            textarea.style.left = '-9999px';
            textarea.style.opacity = '0';
            textarea.setAttribute('readonly', '');

            document.body.appendChild(textarea);

            textarea.focus();
            textarea.select();

            textarea.setSelectionRange(0, token.length);
            let copySuccess = false;

            try {
                copySuccess = document.execCommand('copy');

                if (copySuccess) {
                    Toastr('success', 'Token berhasil disalin');

                    saveTokenToServer();
                } else {
                    Toastr('error', 'Gagal menyalin token');
                }
            } catch (err) {
                console.error('Error saat copy:', err);
                Toastr('error', 'Terjadi kesalahan saat menyalin token');
            } finally {
                document.body.removeChild(textarea);
            }
        });

        function saveTokenToServer() {
            const form = $('#FormAppToken');

            $.ajax({
                type: form.attr('method'),
                url: form.attr('action'),
                data: form.serialize(),
                success: function(result) {
                    console.log('Token berhasil disimpan ke server');
                },
                error: function(xhr, status, error) {
                    console.error('Gagal menyimpan token:', error);
                }
            });
        }
    </script>
@endsection
