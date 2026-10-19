<!DOCTYPE html>
<html lang="en" translate="no">

<head>
    <meta charset="utf-8" />
    <meta name="description" content="Jembatan Akuntabilitas Bumdesma">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="keywords"
        content="dbm, sidbm, sidbm.net, demo.sidbm.net, app.sidbm.net, asta brata teknologi, abt, dbm, kepmendesa 136, kepmendesa nomor 136 tahun 2022">
    <meta name="author" content="Enfii">

    <link rel="apple-touch-icon" sizes="76x76" href="/assets/img/icon/favicon-192x192.png">
    <link rel="icon" type="image/png" href="/assets/img/icon/favicon.png">
    <title>
        GENERATE &mdash; Aplikasi Dana Bergulir Masyarakat
    </title>

    <link rel="stylesheet" type="text/css"
        href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900|Roboto+Slab:400,700" />

    <link href="/assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="/assets/css/nucleo-svg.css" rel="stylesheet" />

    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">

    <link id="pagestyle" href="/assets/css/material-dashboard.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="/assets/css/style.css">

    <style>
        table.table tr td,
        table.table tr th {
            vertical-align: middle;
        }
    </style>
</head>

<body>
    <section class="container">
        <div class="row">
            <div class="col-12">
                <h1 class="text-center">Generate</h1>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                @php
                    $operator = [
                        '=',
                        '!=',
                        '>',
                        '<',
                        'LIKE',
                        'NOT LIKE',
                        [
                            'title' => 'IN (...)',
                            'value' => 'IN',
                        ],
                        [
                            'title' => 'NOT IN (...)',
                            'value' => 'NOT IN',
                        ],
                    ];

                    $continue = ['sumber', 'catatan_verifikasi', 'wt_cair', 'lu'];
                @endphp

                <form action="/generate/save" method="post" target="_blank" id="GenerateForm">
                    @csrf


                    <input type="hidden" name="pinjaman" id="pinjaman" value="kelompok">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="bg-dark text-white">
                                <tr>
                                    <th>Kolom</th>
                                    <th>Operator</th>
                                    <th>Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($struktur as $val)
                                    @php
                                        if (in_array($val, $continue)) {
                                            continue;
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                            <b>{{ ucwords(str_replace('_', ' ', $val)) }}</b>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-static">
                                                <select name="{{ $val }}[operator]" class="form-control">
                                                    @foreach ($operator as $opt)
                                                        @php
                                                            $title = $opt;
                                                            $value = $opt;
                                                            if (is_array($opt)) {
                                                                $title = $opt['title'];
                                                                $value = $opt['value'];
                                                            }
                                                        @endphp

                                                        <option value="{{ $value }}">{{ $title }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-static">
                                                <input type="text" name="{{ $val }}[value]"
                                                    class="form-control" autocomplete="off">
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="sticky-bottom">
                        <div class="d-flex justify-content-end bg-white shadow-sm p-2 pt-3 pb-3 rounded">
                            <button type="submit" id="Generate" class="btn btn-info btn-sm ms-2 mb-0">
                                Generate
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="/assets/js/core/popper.min.js"></script>
    <script src="/assets/js/core/bootstrap.min.js"></script>
    <script src="/assets/js/plugins/perfect-scrollbar.min.js"></script>
    <script src="/assets/js/plugins/smooth-scrollbar.min.js"></script>
    <script src="/assets/js/material-dashboard.min.js"></script>
    <script src="/assets/js/plugins/sweetalert.min.js"></script>

    <script>
        $.get('/generate/kelompok', function(result) {
            $('#StructurKelompok').html(result.view)
        })
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
</body>

</html>
