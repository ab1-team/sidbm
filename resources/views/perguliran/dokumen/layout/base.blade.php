@php
    if ($type == 'excel') {
        header('Content-type: application/vnd-ms-excel');
        header('Content-Disposition: attachment; filename=' . ucwords(str_replace('_', ' ', $judul)) . '.xls');
    }
@endphp

<!DOCTYPE html>
<html lang="en" translate="no">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ ucwords(str_replace('_', ' ', $judul)) }}</title>
    <style>
        * {
            font-family: Arial, Helvetica, sans-serif;
        }

        html {
            margin: 75.59px;
            margin-left: 94.48px;
        }

        ul,
        ol {
            margin-left: -10px;
            page-break-inside: auto !important;
        }

        footer {
            position: fixed;
            bottom: -50px;
            left: 0px;
            right: 0px;
        }

        table tr th,
        table tr td {
            padding: 2px 4px;
        }

        table.p tr th,
        table.p tr td {
            padding: 4px 4px;
        }

        table.p0 tr th,
        table.p0 tr td {
            padding: 0px !important;
        }

        table tr td table:not(.padding) tr td {
            padding: 0 !important;
        }

        table tr.m td:first-child {
            margin-left: 24px;
        }

        table tr.m td:last-child {
            margin-right: 24px;
        }

        table tr.vt td,
        table tr.vb td.vt,
        table tr td.vt {
            vertical-align: top;
        }

        table tr.vb td,
        table tr.vt td.vb,
        table tr td.vb {
            vertical-align: bottom;
        }

        .break {
            page-break-after: always;
        }

        li {
            text-align: justify;
        }

        .l {
            border-left: 1px solid #000;
        }

        .t {
            border-top: 1px solid #000;
        }

        .r {
            border-right: 1px solid #000;
        }

        .b {
            border-bottom: 1px solid #000;
        }

        table.gp {
            border-collapse: collapse;
        }

        table.gp th,
        table.gp td {
            border: 1.5px solid #000;
        }

        .gp-t {
            border-top: 1.5px solid #000;
        }

        .gp-b {
            border-bottom: 1.5px solid #000;
        }

        .gp-l {
            border-left: 1.5px solid #000;
        }

        .gp-r {
            border-right: 1.5px solid #000;
        }
    </style>
</head>

<body>
    @if ($report != 'suratKuasa')
        <style>
            header {
                position: fixed;
                top: -10px;
                left: 0px;
                right: 0px;
            }

            main {
                position: relative;
                top: 60px;
                font-size: 12px;
                padding-bottom: 37.79px;
            }
        </style>
        <header>
            <table width="100%" style="border-bottom: 1px solid grey;">
                <tr>
                    <td width="30">
                        <img src="{{ $logo }}" width="40" height="40" alt="{{ $logo }}">
                    </td>
                    <td>
                        <div style="font-size: 12px;">
                            <b>{{ strtoupper($nama_lembaga) }}</b>
                        </div>
                        <div style="font-size: 12px;">
                            <b>{{ strtoupper($nama_kecamatan) }}</b>
                        </div>
                    </td>
                </tr>
            </table>
            <table width="100%" style="position: relative; top: -10px;">
                <tr>
                    <td>
                        <span style="font-size: 8px; color: grey;">
                            <i>{{ $nomor_usaha }}</i>
                        </span>
                    </td>
                    <td align="right">
                        <span style="font-size: 8px; color: grey;">
                            <i>{{ $info }}</i>
                        </span>
                    </td>
                </tr>
            </table>
        </header>
    @else
        <style>
            main {
                position: relative;
                font-size: 12px;
                top: -20px;
            }
        </style>
    @endif

    <main>
        @yield('content')
    </main>

</body>

</html>
