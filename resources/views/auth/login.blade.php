@php
    $username = $kec->id == '1' ? 'value="demo"' : '';
    if (old('username') != '') {
        $username = 'value="' . old('username') . '"';
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
    <link rel="apple-touch-icon" sizes="76x76" href="{{ $logo }}">
    <link rel="icon" type="image/png" href="{{ $logo }}">
    <title>
        SIDBM &mdash; {{ $kec->nama_lembaga_sort }}
    </title>

    <link rel="stylesheet" type="text/css"
        href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900|Roboto+Slab:400,700" />

    <link href="/assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="/assets/css/nucleo-svg.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=apk_install" />

    <link id="pagestyle" href="/assets/css/material-dashboard.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="/assets/css/style.css">

    <style>
        .swal2-container {
            height: unset !important;
        }

        .download-app {
            position: absolute;
            z-index: 999;
            right: 24px;
            bottom: 24px;
        }

        .download-app .btn {
            display: flex;
            align-items: center;
            justify-content: center;
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

<body>
    <main class="main-content  mt-0">
        <section>
            <div class="page-header min-vh-100">
                @if ($app)
                    <div class="download-app">
                        <button type="button" id="download-app"
                            class="btn btn-lg btn-facebook btn-icon-only rounded-circle">
                            <span class="btn-inner--icon">
                                <i class="fas fa-cloud-download-alt"></i>
                            </span>
                        </button>
                    </div>
                @endif

                <div class="container">
                    <div class="row">
                        <div
                            class="col-6 d-lg-flex d-none h-100 my-auto pe-0 position-absolute top-0 start-0 text-center justify-content-center flex-column">
                            <div class="position-relative h-100 m-3 px-7 border-radius-lg d-flex flex-column justify-content-center"
                                style="background-image: url('/assets/img/login.png'); background-size: cover;">
                            </div>
                        </div>
                        <div
                            class="col-xl-4 col-lg-5 col-md-7 d-flex flex-column ms-auto me-auto ms-lg-auto me-lg-5 position-relative">
                            <div class="card card-plain">
                                <div style="text-wrap: nowrap;"
                                    class="card-header mb-0 d-flex flex-column align-items-center">
                                    <img src="{{ $logo }}" style="width: 150px;" alt="Avatar" />
                                    <h5 class="font-weight-bolder mb-0">
                                        {{ $kec->nama_lembaga_sort }}
                                    </h5>
                                    <h5 class="font-weight-bolder">{{ $kec->sebutan_kec }} {{ $kec->nama_kec }}</h5>
                                    <p class="mb-0">
                                        Masukkan <b>Username</b> dan <b>Password</b>
                                    </p>
                                </div>
                                <div class="card-body pt-1">
                                    <form role="form" method="POST" action="/login">
                                        @csrf
                                        <div class="input-group input-group-outline mb-3">
                                            <label class="form-label">Username</label>
                                            <input type="text" name="username" id="username" class="form-control"
                                                {!! $username !!}>
                                        </div>
                                        <div class="input-group input-group-outline mb-3">
                                            <label class="form-label">Password</label>
                                            <input type="password" name="password" id="password" class="form-control"
                                                {!! $kec->id == '1' ? 'value="12345"' : '' !!}>
                                        </div>
                                        <div class="text-center">
                                            <button type="submit"
                                                class="btn btn-lg bg-gradient-info btn-lg w-100 mt-3 mb-0">
                                                Sign In
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                <div class="card-footer text-center pt-0 px-lg-2 px-1">
                                    <p class="mb-4 text-sm mx-auto">
                                        &copy; {{ date('Y') }} PT. Asta Brata
                                        Teknologi &mdash; {{ str_pad($kec->id, 4, '0', STR_PAD_LEFT) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="/assets/js/core/popper.min.js"></script>
    <script src="/assets/js/core/bootstrap.min.js"></script>
    <script src="/assets/js/plugins/perfect-scrollbar.min.js"></script>
    <script src="/assets/js/plugins/smooth-scrollbar.min.js"></script>
    <script src="/assets/js/material-dashboard.min.js"></script>
    <script src="/assets/js/plugins/sweetalert.min.js"></script>

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

        @if ($app)
            $(document).on('click', '#download-app', function(e) {
                e.preventDefault();

                window.open('{{ $app->apk_url }}', '_blank');
            })
        @endif
    </script>

    @if (session('pesan'))
        <script>
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

            Toastr('success', "{{ session('pesan') }}")
        </script>
    @endif

    @if (session('error'))
        <script>
            Swal.fire({
                title: '{{ session('error') }}',
                text: 'Silahkan hubungi technical support untuk informasi lebih lengkapnya.',
                icon: 'error'
            })
        </script>
    @endif

    @if (session('warning'))
        <script>
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

            Toastr('warning', "{{ session('warning') }}")
        </script>
    @endif
</body>

</html>
