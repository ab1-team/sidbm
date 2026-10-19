@php
    $config = json_decode(Session::get('config'), true);
    $darkMode = $config['darkMode'] ?? '';
    $sidebarColor = $config['sidebarColor'] ?? 'primary';
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="apple-touch-icon" sizes="76x76" href="/assets/img/icon/favicon-192x192.png">
    <link rel="icon" type="image/png" href="/assets/img/icon/favicon.png">
    <title>
        Halaman Tidak Ditemukan &mdash; SIDBM
    </title>
    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900|Roboto+Slab:400,700" />
    <link href="/assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="/assets/css/nucleo-svg.css" rel="stylesheet" />
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <link id="pagestyle" href="/assets/css/material-dashboard.min.css" rel="stylesheet" />
    <style>
        .error-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0f2f5;
        }

        .dark-version .error-page {
            background: #1a2035;
        }

        .error-card {
            max-width: 500px;
            width: 100%;
            text-align: center;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            background: white;
            transition: transform 0.3s ease;
        }

        .dark-version .error-card {
            background: #202940;
            color: white;
        }

        .error-card:hover {
            transform: translateY(-5px);
        }

        .error-illustration {
            max-width: 300px;
            margin-bottom: 30px;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }

        .error-code {
            font-size: 80px;
            font-weight: 900;
            margin-bottom: 10px;
            background: linear-gradient(45deg, var(--bs-{{ $sidebarColor }}), #f44335);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .error-message {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 15px;
            color: #344767;
        }

        .dark-version .error-message {
            color: #fff;
        }

        .error-description {
            color: #67748e;
            margin-bottom: 30px;
        }

        .dark-version .error-description {
            color: #a0aec0;
        }
    </style>
</head>

<body class="g-sidenav-show {{ $darkMode }}">
    <div class="error-page">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8 col-12">
                    <div class="error-card mx-auto">
                        <img src="/assets/img/404.png" alt="404 Illustration" class="error-illustration img-fluid">
                        <div class="error-code">404</div>
                        <h1 class="error-message">{{ $exception->getMessage() ?: 'Oops! Halaman tidak ditemukan' }}</h1>
                        <p class="error-description">
                            Maaf, halaman yang Anda cari tidak tersedia atau mungkin data telah dipindahkan ke lokasi lain.
                        </p>
                        <a href="/dashboard" class="btn bg-gradient-{{ $sidebarColor }} btn-lg w-100">
                            Kembali ke Dashboard
                        </a>
                        <div class="mt-3">
                            <a href="javascript:history.back()" class="text-secondary text-sm">
                                <i class="fas fa-arrow-left me-1"></i> Kembali ke halaman sebelumnya
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Core JS Files -->
    <script src="/assets/js/core/popper.min.js"></script>
    <script src="/assets/js/core/bootstrap.min.js"></script>
    <script src="/assets/js/plugins/perfect-scrollbar.min.js"></script>
    <script src="/assets/js/plugins/smooth-scrollbar.min.js"></script>
    <script>
        var win = navigator.platform.indexOf('Win') > -1;
        if (win && document.querySelector('#sidenav-scrollbar')) {
            var options = {
                damping: '0.5'
            }
            Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
        }
    </script>
    <!-- Github buttons -->
    <script async defer src="https://buttons.github.io/buttons.js"></script>
    <!-- Control Center for Material Dashboard: parallax effects, scripts for the example pages etc -->
    <script src="/assets/js/material-dashboard.min.js?v=3.0.0"></script>
</body>

</html>
