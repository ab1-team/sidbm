<!DOCTYPE html>
<html lang="en" translate="no">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="apple-touch-icon" sizes="76x76" href="/assets/img/icon/favicon.png">
    <link rel="icon" type="image/png" href="/assets/img/icon/favicon.png">
    <title>
        {{ $title }} &mdash; Aplikasi Dana Bergulir Masyarakat SI DBM
    </title>

    <link rel="canonical" href="https://www.creative-tim.com/product/material-dashboard-pro" />

    <meta name="keywords" content="">
    <meta name="description" content="Sistem Informasi Dana Bergulir Masyarakat &mdash; Siap Audit Kapanpun Siapapun">

    {{-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@9.0.1/public/assets/styles/choices.min.css" /> --}}


    <link rel="stylesheet" type="text/css"
        href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900|Roboto+Slab:400,700" />

    <link href="/assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="/assets/css/nucleo-svg.css" rel="stylesheet" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">

    @yield('css')

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link id="pagestyle" href="/assets/css/material-dashboard.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="/assets/css/style.css">
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
    </style>
</head>

<body class="g-sidenav-show  bg-gray-200">
    @include('admin.layout.sidebar')
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
        @include('admin.layout.navbar')

        <div class="container-fluid py-3">
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
                    <h5 class="mt-3 mb-0">Pengaturan Tampilan</h5>
                    <p></p>
                </div>
                <div class="float-end mt-4">
                    <button class="btn btn-link text-dark p-0 fixed-plugin-close-button">
                        <i class="material-icons">clear</i>
                    </button>
                </div>

            </div>
            <hr class="horizontal dark my-1">
            <div class="card-body pt-sm-3 pt-0">

                <div>
                    <h6 class="mb-0">Sidebar Colors</h6>
                </div>
                <a href="javascript:void(0)" class="switch-trigger background-color">
                    <div class="badge-colors my-2 text-start">
                        <span class="badge filter bg-gradient-primary active" data-color="primary"
                            onclick="sidebarColor(this)"></span>
                        <span class="badge filter bg-gradient-dark" data-color="dark"
                            onclick="sidebarColor(this)"></span>
                        <span class="badge filter bg-gradient-info" data-color="info"
                            onclick="sidebarColor(this)"></span>
                        <span class="badge filter bg-gradient-success" data-color="success"
                            onclick="sidebarColor(this)"></span>
                        <span class="badge filter bg-gradient-warning" data-color="warning"
                            onclick="sidebarColor(this)"></span>
                        <span class="badge filter bg-gradient-danger" data-color="danger"
                            onclick="sidebarColor(this)"></span>
                    </div>
                </a>

                <div class="mt-3">
                    <h6 class="mb-0">Sidenav Type</h6>
                </div>
                <div class="d-flex">
                    <button class="btn bg-gradient-dark px-3 mb-2 active" data-class="bg-gradient-dark"
                        onclick="sidebarType(this)">Dark</button>
                    <button class="btn bg-gradient-dark px-3 mb-2 ms-2" data-class="bg-transparent"
                        onclick="sidebarType(this)">Transparent</button>
                    <button class="btn bg-gradient-dark px-3 mb-2 ms-2" data-class="bg-white"
                        onclick="sidebarType(this)">White</button>
                </div>
                <p class="text-sm d-xl-none d-block mt-2">You can change the sidenav type just on desktop view.</p>

                <div class="mt-3 d-flex">
                    <h6 class="mb-0">Navbar Fixed</h6>
                    <div class="form-check form-switch ps-0 ms-auto my-auto">
                        <input class="form-check-input mt-1 ms-auto" type="checkbox" id="navbarFixed"
                            onclick="navbarFixed(this)">
                    </div>
                </div>
                <hr class="horizontal dark my-3">
                <div class="mt-2 d-flex">
                    <h6 class="mb-0">Sidenav Mini</h6>
                    <div class="form-check form-switch ps-0 ms-auto my-auto">
                        <input class="form-check-input mt-1 ms-auto" type="checkbox" id="navbarMinimize"
                            onclick="navbarMinimize(this)">
                    </div>
                </div>
                <hr class="horizontal dark my-3">
                <div class="mt-2 d-flex">
                    <h6 class="mb-0">Light / Dark</h6>
                    <div class="form-check form-switch ps-0 ms-auto my-auto">
                        <input class="form-check-input mt-1 ms-auto" type="checkbox" id="dark-version"
                            onclick="darkMode(this)">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form action="/master/logout" method="post" id="formLogout">
        @csrf
    </form>

    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-3-typeahead/4.0.2/bootstrap3-typeahead.js"></script>
    <script src="/assets/js/core/popper.min.js"></script>
    <script src="/assets/js/core/bootstrap.min.js"></script>
    <script src="/assets/js/plugins/perfect-scrollbar.min.js"></script>
    <script src="/assets/js/plugins/smooth-scrollbar.min.js"></script>
    <script src="/assets/js/plugins/choices.min.js"></script>
    <script src="/assets/js/plugins/sweetalert.min.js"></script>
    <script src="/assets/js/plugins/flatpickr.min.js"></script>
    <script src="/assets/js/plugins/chartjs.min.js"></script>
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

    @yield('script')

    <script>
        $(document).ready(function() {
            $('#master-server-switcher').change(function() {
                var selectedServer = $(this).val();
                $.ajax({
                    url: '/master/switch_server',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        server: selectedServer
                    },
                    success: function(response) {
                        if (response.success) {
                            window.location.reload();
                        }
                    }
                });
            });
        });

        var formatter = new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })

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

        var notif;

        $.ajaxSetup({
            xhr: () => {
                var xhr = new window.XMLHttpRequest();
                console.log(xhr);
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = (evt.loaded / evt.total) * 100;
                        console.log(percentComplete);
                    }
                }, false);

                xhr.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = (evt.loaded / evt.total) * 100;
                        console.log(percentComplete);
                    }
                }, false);
                return xhr;
            },
        });

        // $(document).ajaxStart(function() {
        //     let timerInterval
        //     notif = Swal.fire({
        //         title: 'Loading...',
        //         html: 'Permintaan anda sedang diproses oleh sistem.',
        //         timer: 10000,
        //         timerProgressBar: true,
        //         didOpen: () => {
        //             Swal.showLoading()
        //             const b = Swal.getHtmlContainer().querySelector('b')
        //             timerInterval = setInterval(() => {
        //                 b.textContent = Swal.getTimerLeft()
        //             }, 100)
        //         },
        //         willClose: () => {
        //             clearInterval(timerInterval)
        //         }
        //     })
        // });

        // $(document).ajaxStop(function() {
        //     notif.close();
        // });
    </script>

    <script>
        tinymce.init({
            selector: '.tiny-mce-editor',
            plugins: 'table visualblocks fullscreen',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | align | table fullscreen | removeformat',
            font_family_formats: 'Arial=arial,helvetica,sans-serif; Courier New=courier new,courier,monospace;',
            tinycomments_mode: 'embedded',
            tinycomments_author: 'ARAFII'
        });

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
    </script>


    <script async defer src="https://buttons.github.io/buttons.js"></script>
    <script async src="/assets/js/material-dashboard.min.js"></script>

    @if (session('pesan'))
        <script>
            Toastr('success', "{{ session('pesan') }}")
        </script>
    @endif
</body>

</html>
