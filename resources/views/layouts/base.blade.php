@php
    use App\Models\AdminInvoice;
    $invoice = AdminInvoice::where([['lokasi', Session::get('lokasi')], ['status', 'UNPAID']])
        ->with('jp')
        ->orderBy('tgl_invoice', 'DESC');

    $jumlah = 0;
    $inv = $invoice->take(5)->get();
    if ($invoice->count() > 0) {
        $jumlah = $invoice->count();
    }

    $config = json_decode(Session::get('config'), true);
    
    $wa_session = null;
    if (Session::has('lokasi')) {
        $wa_session = \App\Models\Whatsapp::where('lokasi', Session::get('lokasi'))->first();
    }
@endphp

<!DOCTYPE html>
<html lang="en" translate="no">

<head>
    <meta charset="utf-8" />
    <meta name="description" content="Sistem Informasi Dana Bergulir Masyarakat &mdash; Siap Audit Kapanpun Siapapun">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="keywords"
        content="dbm, sidbm, sidbm.net, demo.sidbm.net, app.sidbm.net, asta brata teknologi, abt, dbm, kepmendesa 136, kepmendesa nomor 136 tahun 2022">
    <meta name="author" content="Enfii">
    <meta name="theme-color" content="#4CAF50">

    <link rel="manifest" href="{{ url('/manifest.json') }}">
    <link rel="apple-touch-icon" sizes="76x76" href="{{ Session::get('icon') }}">
    <link rel="icon" type="image/png" href="{{ Session::get('icon') }}">
    <title>
        {{ $title }} &mdash; Aplikasi Dana Bergulir Masyarakat SI DBM
    </title>

    <link rel="canonical" href="https://www.creative-tim.com/product/material-dashboard-pro" />

    {{-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@9.0.1/public/assets/styles/choices.min.css" /> --}}

    <link rel="stylesheet" type="text/css"
        href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900|Roboto+Slab:400,700" />

    <link href="/assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="/assets/css/nucleo-svg.css" rel="stylesheet" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.2.1/themes/default/style.min.css" />
    <link rel="stylesheet" href="/assets/css/pace.css?v={{ time() }}">

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">

    @yield('css')

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link id="pagestyle" href="/assets/css/material-dashboard.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="/assets/css/style.css?v={{ time() }}">
    <style>
        .tox-promotion {
            display: none;
        }

        .swal2-container {
            height: unset !important;
        }

        .dataTables_filter .form-control {
            border: 1px solid #d2d6da !important;
        }

        table.dataTable thead th,
        table.dataTable tbody td {
            white-space: nowrap;
        }

        #progress-container {
            width: 100%;
            height: 6px;
            position: relative;
        }

        #progress-bar {
            width: 0;
            height: 100%;
            background-color: var(--bs-success);
            position: absolute;
            top: 0;
            left: 0;
            transition: width 0.3s ease;
        }

        #html5-qrcode-anchor-scan-type-change,
        #html5-qrcode-button-camera-stop,
        #html5-qrcode-button-camera-start {
            display: none !important;
        }

        .active>.page-link,
        .page-link.active {
            background-color: var(--bs-{{ $config['sidebarColor'] }}) !important;
            border-color: var(--bs-{{ $config['sidebarColor'] }}) !important;
        }

        .flatpickr-wrapper {
            width: 100%;
        }

        .list-group-numbered .list-group-item *:not(.badge) {
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            margin-top: 0 !important;
            margin-bottom: 0 !important;
        }
    </style>

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/assets/js/serviceworker.js')
                .then(function(registration) {
                    console.log('Service Worker registered with scope:', registration.scope);
                }).catch(function(error) {
                    console.log('Service Worker registration failed:', error);
                });
        } else {
            console.warn('Service Worker is not supported in this browser.');
        }
    </script>
</head>

<body class="g-sidenav-show  bg-gray-200 {{ $config['darkMode'] }} {{ $config['sidebarMini'] }}">

    @include('layouts.sidebar')
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
        @include('layouts.navbar')

        <div class="container-fluid py-3">
            <input type="hidden" name="localConfig" id="localConfig" value="{{ Session::get('config') }}">
            @yield('content')

            <footer class="footer py-4  ">
                <div class="container-fluid">
                    <div class="row align-items-center justify-content-lg-between">
                        <div class="col-lg-6 mb-lg-0 mb-4">
                            <div class="copyright text-center text-sm text-muted text-lg-start">
                                ©
                                <script>
                                    document.write(new Date().getFullYear())
                                </script>,
                                made with <i class="fa fa-heart"></i> by
                                <a href="https://abt.co.id" class="font-weight-bold" target="_blank">
                                    PT. Asta Brata Teknologi
                                </a>
                                for a business.
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </main>
    <div class="fixed-plugin">
        <a class="fixed-plugin-button text-dark position-fixed px-3 py-2">
            <i class="material-icons py-2">settings</i>
        </a>
        <div class="card shadow-lg">
            <div class="card-header pb-0 pt-3">
                <div class="float-start">
                    <h5 class="mt-3 mb-0">
                        Tampilan SIDBM
                    </h5>
                    <p>Sesuaikan tampilan aplikasi anda.</p>
                </div>
                <div class="float-end mt-4">
                    <button class="btn btn-link p-0 fixed-plugin-close-button text-dark">
                        <i class="material-icons">clear</i>
                    </button>
                </div>

            </div>
            <hr class="horizontal my-1 dark">
            <div class="card-body pt-sm-3 pt-0">

                <div>
                    <h6 class="mb-0">Warna Sidebar</h6>
                </div>
                <a href="javascript:void(0)" class="switch-trigger background-color">
                    <div class="badge-colors my-2 text-start">
                        <span
                            class="badge filter bg-gradient-primary {{ $config['sidebarColor'] == 'primary' ? 'active' : '' }}"
                            data-color="primary" onclick="sidebarColor(this)"></span>
                        <span
                            class="badge filter bg-gradient-dark {{ $config['sidebarColor'] == 'dark' ? 'active' : '' }}"
                            data-color="dark" onclick="sidebarColor(this)"></span>
                        <span
                            class="badge filter bg-gradient-info {{ $config['sidebarColor'] == 'info' ? 'active' : '' }}"
                            data-color="info" onclick="sidebarColor(this)"></span>
                        <span
                            class="badge filter bg-gradient-success {{ $config['sidebarColor'] == 'success' ? 'active' : '' }}"
                            data-color="success" onclick="sidebarColor(this)"></span>
                        <span
                            class="badge filter bg-gradient-warning {{ $config['sidebarColor'] == 'warning' ? 'active' : '' }}"
                            data-color="warning" onclick="sidebarColor(this)"></span>
                        <span
                            class="badge filter bg-gradient-danger {{ $config['sidebarColor'] == 'danger' ? 'active' : '' }}"
                            data-color="danger" onclick="sidebarColor(this)"></span>
                    </div>
                </a>

                <div class="mt-3">
                    <h6 class="mb-0">Tipe Sidebar</h6>
                </div>
                <div class="d-flex">
                    <button
                        class="btn bg-gradient-dark px-3 mb-2 {{ $config['sidebarType'] == 'bg-gradient-dark' ? 'active' : '' }}"
                        data-class="bg-gradient-dark" onclick="sidebarType(this)">Dark</button>
                    <button
                        class="btn bg-gradient-dark px-3 mb-2 ms-2 {{ $config['sidebarType'] == 'bg-transparent' ? 'active' : '' }}"
                        data-class="bg-transparent" onclick="sidebarType(this)">Transparent</button>
                    <button
                        class="btn bg-gradient-dark px-3 mb-2 ms-2 {{ $config['sidebarType'] == 'bg-white' ? 'active' : '' }}"
                        data-class="bg-white" onclick="sidebarType(this)">White</button>
                </div>
                <p class="text-sm d-xl-none d-block mt-2">You can change the sidenav type just on desktop view.</p>

                <div class="mt-3 d-flex">
                    <h6 class="mb-0">Navbar Fixed</h6>
                    <div class="form-check form-switch ps-0 ms-auto my-auto">
                        <input class="form-check-input mt-1 ms-auto" type="checkbox" id="navbarFixed"
                            onclick="navbarFixed(this)" {!! $config['navbarFixed'] != '' ? '' : 'checked="true"' !!}>
                    </div>
                </div>
                <hr class="horizontal my-3 dark">
                <div class="mt-2 d-flex">
                    <h6 class="mb-0">Sidebar Mini</h6>
                    <div class="form-check form-switch ps-0 ms-auto my-auto is-filled">
                        <input class="form-check-input mt-1 ms-auto" type="checkbox" id="navbarMinimize"
                            onclick="navbarMinimize(this)" {!! $config['sidebarMini'] == 'g-sidenav-hidden' ? 'checked="true"' : '' !!}>
                    </div>
                </div>
                <hr class="horizontal my-3 dark">
                <div class="mt-2 d-flex">
                    <h6 class="mb-0">Mode Malam</h6>
                    <div class="form-check form-switch ps-0 ms-auto my-auto is-filled">
                        <input class="form-check-input mt-1 ms-auto" type="checkbox" id="dark-version"
                            onclick="darkMode(this)" {!! $config['darkMode'] == 'dark-version' ? 'checked="true"' : '' !!}>
                    </div>
                </div>
                <hr class="horizontal my-sm-4 dark">
                <div class="w-100 text-center">
                    <span></span>
                    <h6 class="mt-3">Have a nice day</h6>
                    <a href="https://app.sidbm.net" class="btn btn-dark mb-0 me-2" target="_blank">
                        <i class="fas fa-cube me-1" aria-hidden="true"></i> Demo App
                    </a>
                    <a href="https://www.facebook.com/astabratagroup" class="btn btn-dark mb-0 me-2" target="_blank">
                        <i class="fab fa-facebook-square me-1" aria-hidden="true"></i> Facebook
                    </a>
                </div>
            </div>
        </div>
    </div>

    <form action="/logout" method="post" id="formLogout">
        @csrf
    </form>

    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.30.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-3-typeahead/4.0.2/bootstrap3-typeahead.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/pace-js@latest/pace.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <script src="/assets/js/core/popper.min.js"></script>
    <script src="/assets/js/core/bootstrap.min.js"></script>
    <script src="/assets/js/plugins/perfect-scrollbar.min.js"></script>
    <script src="/assets/js/plugins/smooth-scrollbar.min.js"></script>
    <script src="/assets/js/plugins/choices.min.js"></script>
    <script src="/assets/js/plugins/sweetalert.min.js"></script>
    <script src="/assets/js/plugins/flatpickr.min.js"></script>
    <script src="/assets/js/plugins/chartjs.min.js"></script>
    <script src="/assets/js/html5-qrcode.js?v={{ time() }}"></script>
    <script src="{{ asset('vendor/tinymce/tinymce.min.js') }}"></script>

    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-maskmoney/3.0.2/jquery.maskMoney.min.js"
        integrity="sha512-Rdk63VC+1UYzGSgd3u2iadi0joUrcwX0IWp2rTh6KXFoAmgOjRS99Vynz1lJPT8dLjvo6JZOqpAHJyfCEZ5KoA=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"
        integrity="sha512-fD9DI5bZwQxOi7MhYWnnNPlvXdp/2Pj3XSTRrFs5FQa4mizyGLnJcN6tuvUS6LbmgN1ut+XGSABKvjN0H6Aoow=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="//cdn.quilljs.com/1.3.7/quill.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.2.1/jstree.min.js"></script>

    @yield('script')

    <script>
        var formatter = new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })

        window.paceOptions = {
            ajax: true,
            document: false,
            eventLag: false,
            elements: {
                selectors: ['.g-sidenav-show']
            }
        }

        $('#cariKelompok').typeahead({
            source: function(query, process) {
                var states = [];
                return $.get('/perguliran/cari_kelompok', {
                    query: query
                }, function(result) {
                    var resultList = result.map(function(item) {
                        states.push({
                            "id": item.id,
                            "name": item.nama_kelompok +
                                ' [' + item.nama_desa + ']' +
                                ' [' + item.ketua + ']' +
                                ' [' + item.kd_kelompok + ']',
                            "value": item.id
                        });
                    });

                    return process(states);
                })
            },
            afterSelect: function(item) {
                var path = '{{ Request::path() }}'
                if (path == 'transaksi/jurnal_angsuran') {
                    $.get('/transaksi/form_angsuran/' + item.id, function(result) {
                        var ch_pokok = document.getElementById('chartP').getContext("2d");
                        var ch_jasa = document.getElementById('chartJ').getContext("2d");

                        angsuran(true, result)

                        makeChart('pokok', ch_pokok, result.sisa_pokok, result.sum_pokok)
                        makeChart('jasa', ch_jasa, result.sisa_jasa, result.sum_jasa)

                        $('#loan-id').html(item.id)

                        var id = $('#id').val()
                        $.get('/transaksi/angsuran/form_anggota/' + id, function(result) {
                            if (result.success) {
                                $('#LayoutAngsuranAnggota').html(result.view)
                                $('#AngsuranAnggotaLabel').text(result.title)
                            }
                        })
                    })
                } else {
                    window.location.href = '/transaksi/jurnal_angsuran?pinkel=' + item.id
                }
            }
        });

        function makeChart(id, target, sisa_saldo, sum_saldo) {
            window[id] = new Chart(target, {
                type: 'doughnut',
                data: {
                    labels: [
                        'Sisa Saldo',
                        'Total Pengembalian'
                    ],
                    datasets: [{
                        label: 'My First Dataset',
                        data: [sisa_saldo, sum_saldo],
                        backgroundColor: [
                            '#e3316e',
                            '#3A416F'
                        ],
                        hoverOffset: 4
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            })
        }

        function angsuran(destroy = false, result) {
            $('#pokok').val(formatter.format(result.saldo_pokok))
            $('#jasa').val(formatter.format(result.saldo_jasa))
            $('#id').val(result.pinkel.id)

            $('#_pokok').val(result.sisa_pokok)
            $('#_jasa').val(result.sisa_jasa)

            var ch_pokok = document.getElementById('chartP').getContext("2d");
            var ch_jasa = document.getElementById('chartJ').getContext("2d");

            if (destroy) {
                if (pokok) {
                    pokok.destroy()
                }

                if (jasa) {
                    jasa.destroy()
                }
            }

            $('#alokasi_pokok').html("Rp. " + formatter.format(result.alokasi_pokok))
            $('#alokasi_jasa').html("Rp. " + formatter.format(result.alokasi_jasa))

            $('#pokok,#jasa,#denda').trigger('change')
            $('#penyetor').val(result.penyetor)
        }

        function open_window(link) {
            return window.open(link)
        }

        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        })

        $(document).on('click', '#logout', function(e) {
            e.preventDefault()

            Swal.fire({
                title: 'Logout',
                text: 'Dengan klik tombol logout maka anda tidak bisa membuka halaman ini lagi sebelum melakukan login ulang, Logout?',
                showCancelButton: true,
                confirmButtonText: 'Logout',
                cancelButtonText: 'Batal',
                icon: 'info'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#formLogout').submit()
                }
            })
        })

        $(document).on('click', '#btnLaporanPelunasan', function(e) {
            e.preventDefault()

            $('input#laporan').val('pelunasan')
            $('input#type').val('pdf')
            $('#FormLaporanSisipan').submit()
        })
    </script>

    <script>
        function initTinyMCE(skin = 'oxide', content_css = 'default') {
            tinymce.init({
                selector: '.tiny-mce-editor',
                plugins: 'table visualblocks fullscreen',
                toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | align | table fullscreen | removeformat',
                font_family_formats: 'Arial=arial,helvetica,sans-serif; Courier New=courier new,courier,monospace;',
                tinycomments_mode: 'embedded',
                tinycomments_author: 'ARAFII',
                skin: skin,
                content_css: content_css
            });
        }

        var skin = "{{ $config['darkMode'] }}"
        if (skin == 'false') {
            initTinyMCE()
        } else {
            initTinyMCE('oxide-dark', 'dark')
        }

        var win = navigator.platform.indexOf('Win') > -1;
        if (win && document.querySelector('#sidenav-scrollbar')) {
            var options = {
                damping: '0.5'
            }
            Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
        }

        function Toastr(icon, text) {
            font = "1.2rem Nimrod MT";

            canvas = document.createElement("canvas");
            context = canvas.getContext("2d");
            context.font = font;
            width = context.measureText(text).width;
            formattedWidth = Math.ceil(width) + 100;

            Toast.fire({
                icon: icon,
                title: text,
                width: formattedWidth
            })
        }

        function MultiToast(icon, text) {
            font = "1.2rem Nimrod MT";

            canvas = document.createElement("canvas");
            context = canvas.getContext("2d");
            context.font = font;
            width = context.measureText(text).width;
            formattedWidth = Math.ceil(width) + 100;

            let MultiToast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            })

            MultiToast.fire({
                icon: icon,
                title: text,
                width: formattedWidth
            })
        }
    </script>

    <script>
        if (localStorage.getItem('devops') !== 'true') {
            $(document).bind("contextmenu", function(e) {
                return false;
            });

            $(document).keydown(function(event) {
                if (event.keyCode == 123) { // Prevent F12
                    return false;
                }
                if (event.ctrlKey && event.shiftKey && event.keyCode == 73) { // Prevent Ctrl+Shift+I        
                    return false;
                }
                if (event.ctrlKey && event.shiftKey && event.keyCode == 67) { // Prevent Ctrl+Shift+C  
                    return false;
                }
                if (event.ctrlKey && event.shiftKey && event.keyCode == 74) { // Prevent Ctrl+Shift+J
                    return false;
                }
            });
        }
    </script>

    <script async defer src="https://buttons.github.io/buttons.js"></script>
    <script async src="/assets/js/material-dashboard.min.js?v={{ time() }}"></script>

    @if (session('pesan'))
        <script>
            Toastr('success', "{{ session('pesan') }}")
        </script>
    @endif

    {{-- WhatsApp notification listener (Evolution API, no websocket) --}}
</body>

</html>
