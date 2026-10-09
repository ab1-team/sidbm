<!DOCTYPE html>
<html lang="en">

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
    <meta name="description" content="">

    <link rel="stylesheet" type="text/css"
        href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900|Roboto+Slab:400,700" />

    <link href="/assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="/assets/css/nucleo-svg.css" rel="stylesheet" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">

    <link id="pagestyle" href="/assets/css/material-dashboard.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="/assets/css/style.css">
</head>

@php
    $saldo_pokok = $real->saldo_pokok;
    $saldo_jasa = $real->saldo_jasa;

    $keterangan1 = 'Belum Lunas';
    $keterangan2 = 'Belum Lunas';

    if ($saldo_pokok <= 0) {
        $saldo_pokok = 0;
        $keterangan1 = 'Lunas';
    }
    if ($saldo_jasa <= 0) {
        $saldo_jasa = 0;
        $keterangan2 = 'Lunas';
} @endphp

<body class="g-sidenav-show  bg-gray-200" onload="window.print()">
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
        <div class="card mt-3 text-sm shadow-none border-1">
            <div class="card-body">
                Dengan mempertimbangkan Standar Operasional Prosedur (SOP) yang berlaku, dengan ini Saya selaku
                {{ $kec->sebutan_level_1 }},
                menyatakan dengan sebenar-benarnya bahwa :
                <table class="table p-0 mb-3">
                    <tr class="p-1">
                        <td>Nama Kelompok</td>
                        <td>: {{ $perguliran->kelompok->nama_kelompok }}</td>
                        <td>Alokasi</td>
                        <td>{{ number_format($perguliran->alokasi) }}</td>
                    </tr>
                    <tr>
                        <td>Desa</td>
                        <td>: {{ $perguliran->kelompok->d->nama_desa }}</td>
                        <td>Jasa</td>
                        <td>{{ $perguliran->pros_jasa }}%</td>
                    </tr>
                    <tr>
                        <td>Jenis Pinjaman</td>
                        <td>: {{ $perguliran->jpp->nama_jpp }}</td>
                        <td>Sistem</td>
                        <td>{{ $perguliran->jangka }} {{ \App\Utils\Pinjaman::satuanJangka($perguliran, false) }} / {{ $perguliran->sis_pokok->nama_sistem }}</td>
                    </tr>
                </table>

                REKAPITULASI
                <table class="table f-12">
                    <thead class="bg-light">
                        <tr>
                            <th>&nbsp;</th>
                            <th>Target</th>
                            <th>Realisasi</th>
                            <th>Saldo</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Pokok</td>
                            <td>{{ number_format($real->sum_pokok + $real->saldo_pokok) }}</td>
                            <td>{{ number_format($real->sum_pokok) }}</td>
                            <td>{{ number_format($saldo_pokok) }}</td>
                            <td>{{ $keterangan1 }}</td>
                        </tr>
                        <tr>
                            <td>Jasa</td>
                            <td>{{ number_format($real->sum_jasa + $real->saldo_jasa) }}</td>
                            <td>{{ number_format($real->sum_jasa) }}</td>
                            <td>{{ number_format($saldo_jasa) }}</td>
                            <td>{{ $keterangan2 }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="row f-12">
                    <div class="col-3 relative d-flex align-items-center">
                        <img width="100%" src="/assets/img/lunas.png">
                    </div>
                    <div class="col-6 d-flex align-items-center">
                        <div class="f-12 font-primary">Pinjaman tersebut diatas telah kami nyatakan LUNAS dan Surat
                            Perjanjian Kredit (SPK) nomor {{ $perguliran->spk_no }} tanggal
                            {{ Tanggal::tglLatin($perguliran->tgl_cair) }} dinyatakan
                            selesai beserta seluruh hak
                            dan kewajibannya.</div>
                    </div>
                    <div class="col-3 d-flex align-items-center">
                        <table width="100%">
                            <tr>
                                <td class="f-12 text-center">
                                    {{ $kec->sebutan_level_1 }}
                                </td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                            </tr>
                            <tr>
                                <td class="f-12 text-center">
                                    {{ ucwords(strtolower($dir->namadepan . ' ' . $dir->namabelakang)) }}
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-3-typeahead/4.0.2/bootstrap3-typeahead.js"></script>
    <script src="/assets/js/core/popper.min.js"></script>
    <script src="/assets/js/core/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"
        integrity="sha512-fD9DI5bZwQxOi7MhYWnnNPlvXdp/2Pj3XSTRrFs5FQa4mizyGLnJcN6tuvUS6LbmgN1ut+XGSABKvjN0H6Aoow=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script src="/assets/js/plugins/world.js"></script>
    <script async defer src="https://buttons.github.io/buttons.js"></script>
    <script async src="/assets/js/material-dashboard.min.js"></script>
</body>

</html>
