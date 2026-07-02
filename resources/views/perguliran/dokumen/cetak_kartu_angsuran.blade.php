@php
    use App\Utils\Tanggal;
    use App\Models\RencanaAngsuran;
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ ucwords(str_replace('_', ' ', $laporan)) }}</title>
    <style>
        * {
            font-family: Arial, Helvetica, sans-serif;
        }

        html {
            /* margin-left: 90px; */
            /* margin-right: 0px; */
            margin-bottom: 100px;
        }

        ul,
        ol {
            margin-left: -10px;
            page-break-inside: auto !important;
        }

        header {
            position: fixed;
            top: -10px;
            left: 0px;
            right: 0px;
        }

        table tr th,
        table tr td {
            padding: 2px 4px;
        }

        table tr th {
            font-size: 12px;
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
    </style>
</head>

<body>
    <main style="position: relative; font-size: 12px;">
        <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px;">
            <tr style="opacity: 0;">
                <td rowspan="7" align="center" width="400">
                    <div style="font-size: 14px; font-weight: bold;">
                        {{ $kec->nama_lembaga_sort }} {{ $kec->nama_kec }}
                    </div>
                    <div>
                        {{ $kec->alamat_kec }}
                    </div>
                    <div>
                        Telp. {{ $kec->telpon_kec }}
                    </div>
                    <div style="margin-top: 8px;">
                        <img width="150" src="data:image/png;base64,{{ $barcode }}"
                            alt="{{ $pinkel->kelompok->kd_kelompok }}">
                    </div>
                    <div style="font-size: 14px;">{{ $pinkel->kelompok->kd_kelompok }}</div>
                </td>
                <td width="150">Jenis Piutang</td>
                <td width="5" align="center">:</td>
                <td width="200">{{ $pinkel->jpp->nama_jpp }}</td>
                <td width="150">Loan Id.</td>
                <td width="5" align="center">:</td>
                <td width="200">{{ $pinkel->id }}</td>
            </tr>
            <tr style="opacity: 0;">
                <td>Nama Kelompok</td>
                <td align="center">:</td>
                <td style="font-weight: bold;">{{ $pinkel->kelompok->nama_kelompok }}</td>
                <td>&nbsp;</td>
                <td align="center">&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
            <tr style="opacity: 0;">
                <td>Alamat</td>
                <td align="center">:</td>
                <td colspan="4">{{ $pinkel->kelompok->alamat_kelompok }}</td>
            </tr>
            <tr style="opacity: 0;">
                <td>Telpon/SMS</td>
                <td align="center">:</td>
                <td>{{ $pinkel->kelompok->telpon }}</td>
                <td>Anggota</td>
                <td align="center">:</td>
                <td>{{ $pinkel->pinjaman_anggota_count }}</td>
            </tr>
            <tr style="opacity: 0;">
                <td>Tgl Cair</td>
                <td align="center">:</td>
                <td>{{ Tanggal::tglLatin($pinkel->tgl_cair) }}</td>
                <td>Jangka</td>
                <td align="center">:</td>
                <td>{{ $pinkel->jangka }} {{ $pinkel->sis_pokok->id == '12' ? 'Minggu' : 'Bulan' }}</td>
            </tr>
            <tr style="opacity: 0;">
                <td>Alokasi</td>
                <td align="center">:</td>
                <td>{{ number_format($pinkel->alokasi) }}</td>
                <td>Jasa</td>
                <td align="center">:</td>
                <td>{{ $pinkel->pros_jasa / $pinkel->jangka . '%' }}</td>
            </tr>
            <tr style="opacity: 0;">
                <td>Angsuran</td>
                <td align="center">:</td>
                <td>{{ number_format($pinkel->target->wajib_pokok + $pinkel->target->wajib_jasa) }} /
                    {{ $pinkel->sis_pokok->nama_sistem }}</td>
                <td colspan="3">
                    Angsuran pada tanggal {{ explode('-', $pinkel->target->jatuh_tempo)[2] }}
                </td>
            </tr>
            <tr style="opacity: 0;">
                <td colspan="7" class="b t" style="font-weight: bold; font-size: 24px;" align="center">
                    KARTU ANGSURAN
                </td>
            </tr>
        </table>

        @php
            $baris_angsuran = $pinkel->jangka / 2;
        @endphp

        <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px;">
            <tr style="opacity: 0;">
                <td width="40">&nbsp;</td>
                <td colspan="9" style="font-weight: bold;" height="30">TABEL KEWAJIBAN PEMBAYARAN ANGSURAN</td>
                <td width="40">&nbsp;</td>
            </tr>

            <tr style="font-weight: bold; opacity: 0;">
                <th rowspan="{{ $baris_angsuran + 1 }}">&nbsp;</th>
                <th height="30" class="l t b" align="center">Ke</th>
                <th class="l t b" align="center">Tanggal</th>
                <th class="l t b" align="center">Pokok</th>
                <th class="l t b r" align="center">Jasa</th>

                <th>&nbsp;</th>

                <th class="l t b" align="center">Ke</th>
                <th class="l t b" align="center">Tanggal</th>
                <th class="l t b" align="center">Pokok</th>
                <th class="l t b r" align="center">Jasa</th>
                <th rowspan="{{ $baris_angsuran + 1 }}">&nbsp;</th>
            </tr>
            @for ($j = 1; $j <= $baris_angsuran; $j++)
                @php
                    $i = $j - 1;
                @endphp
                <tr style="opacity: 0;">
                    <td class="l {{ $j == $baris_angsuran ? 'b' : '' }}" align="center">
                        {{ $pinkel->rencana[$i]->angsuran_ke }}
                    </td>
                    <td class="l {{ $j == $baris_angsuran ? 'b' : '' }}" align="center">
                        {{ Tanggal::tglIndo($pinkel->rencana[$i]->jatuh_tempo) }}
                    </td>
                    <td class="l {{ $j == $baris_angsuran ? 'b' : '' }}" align="right">
                        {{ number_format($pinkel->rencana[$i]->wajib_pokok) }}
                    </td>
                    <td class="l {{ $j == $baris_angsuran ? 'b' : '' }} r" align="right">
                        {{ number_format($pinkel->rencana[$i]->wajib_jasa) }}
                    </td>

                    <td>&nbsp;</td>

                    <td class="l {{ $j == $baris_angsuran ? 'b' : '' }}" align="center">
                        {{ $pinkel->rencana[$i + $baris_angsuran]->angsuran_ke }}
                    </td>
                    <td class="l {{ $j == $baris_angsuran ? 'b' : '' }}" align="center">
                        {{ Tanggal::tglIndo($pinkel->rencana[$i + $baris_angsuran]->jatuh_tempo) }}
                    </td>
                    <td class="l {{ $j == $baris_angsuran ? 'b' : '' }}" align="right">
                        {{ number_format($pinkel->rencana[$i + $baris_angsuran]->wajib_pokok) }}
                    </td>
                    <td class="l {{ $j == $baris_angsuran ? 'b' : '' }} r" align="right">
                        {{ number_format($pinkel->rencana[$i + $baris_angsuran]->wajib_jasa) }}
                    </td>
                </tr>
            @endfor

        </table>

        <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px;">
            <tr style="opacity: 0;">
                <td width="40" rowspan="{{ $pinkel->rencana_count + 16 }}">&nbsp;</td>
                <td colspan="9" style="font-weight: bold;" height="30">REALISASI PEMBAYARAN ANGSURAN</td>
                <td width="40" rowspan="{{ $pinkel->rencana_count + 16 }}">&nbsp;</td>
            </tr>
            <tr style="opacity: 0;">
                <th width="3%" class="l t b" rowspan="2">No</th>
                <th width="10%" class="l t b" rowspan="2">Tanggal</th>
                <th width="22%" class="l t" colspan="2">Pokok</th>
                <th width="22%" class="l t" colspan="2">Jasa</th>
                <th width="22%"class="l t" colspan="2">Saldo Piutang</th>
                <th width="11%" class="l t r b" rowspan="2">Sign</th>
            </tr>
            <tr style="opacity: 0;">
                <th width="12%" class="l b t">Dibayar</th>
                <th width="10%" class="l b t">Tunggakan</th>
                <th width="12%" class="l b t">Dibayar</th>
                <th width="10%" class="l b t">Tunggakan</th>
                <th width="11%" class="l b t">Pokok</th>
                <th width="11%" class="l b t">Jasa</th>
            </tr>

            @php
                $jumlah = 0;
            @endphp
            @foreach ($pinkel->real as $real)
                @php
                    $jumlah++;

                    $sign = 'TF';
                    if ($real->transaksi->rekening_debit == '1.1.01.01') {
                        $sign = 'TN';
                    }
                @endphp
                <tr {!! $real->id != $idtp ? 'style="opacity: 0;"' : '' !!}>
                    <td align="center">{{ $loop->iteration }}</td>
                    <td align="center">{{ Tanggal::tglIndo($real->tgl_transaksi) }}</td>
                    <td align="right">{{ number_format($real->realisasi_pokok) }}</td>
                    <td align="right">
                        {{ number_format($real->tunggakan_pokok < 0 ? 0 : $real->tunggakan_pokok) }}
                    </td>
                    <td align="right">{{ number_format($real->realisasi_jasa) }}</td>
                    <td align="right">
                        {{ number_format($real->tunggakan_jasa < 0 ? 0 : $real->tunggakan_jasa) }}
                    </td>
                    <td align="right">{{ number_format($real->saldo_pokok) }}</td>
                    <td align="right">{{ number_format($real->saldo_jasa) }}</td>
                    <td align="center">{{ $real->id }}</td>
                </tr>
            @endforeach

            @for ($i = 0; $i <= 16 - $jumlah; $i++)
                <tr style="opacity: 0;">
                    <td class="l {{ $i == 16 - $jumlah ? 'b' : '' }}" align="center">&nbsp;</td>
                    <td class="l {{ $i == 16 - $jumlah ? 'b' : '' }}" align="center">&nbsp;</td>
                    <td class="l {{ $i == 16 - $jumlah ? 'b' : '' }}" align="right">&nbsp;</td>
                    <td class="l {{ $i == 16 - $jumlah ? 'b' : '' }}" align="right">&nbsp;</td>
                    <td class="l {{ $i == 16 - $jumlah ? 'b' : '' }}" align="right">&nbsp;</td>
                    <td class="l {{ $i == 16 - $jumlah ? 'b' : '' }}" align="right">&nbsp;</td>
                    <td class="l {{ $i == 16 - $jumlah ? 'b' : '' }}" align="right">&nbsp;</td>
                    <td class="l {{ $i == 16 - $jumlah ? 'b' : '' }}" align="right">&nbsp;</td>
                    <td class="l {{ $i == 16 - $jumlah ? 'b' : '' }} r" align="center">&nbsp;</td>
                </tr>
            @endfor
        </table>

        <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px;">
            <tr style="opacity: 0;">
                <td width="40" rowspan="5">&nbsp;</td>
                <td colspan="3" style="font-weight: bold;" height="30">&nbsp;</td>
                <td width="40" rowspan="5">&nbsp;</td>
            </tr>
            <tr style="opacity: 0;">
                <td width="350" rowspan="3">
                    <div>Lembar 1 : Untuk Kelompok</div>
                    <div>Lembar 2 : Arsip Lembaga</div>
                </td>
                <td style="font-weight: bold; font-size: 12px;" width="350" align="center">
                    <div>{{ $kec->sebutan_level_1 }} {{ $kec->nama_lembaga_sort }}</div>
                </td>
                <td style="font-weight: bold; font-size: 12px;" width="350" align="center">Ketua Kelompok</td>
            </tr>
            <tr style="opacity: 0;">
                <td colspan="2" height="50"></td>
            </tr>
            <tr style="font-weight: bold; font-size: 12px; text-transform: uppercase; opacity: 0;">
                <td width="350" align="center">
                    <div>{{ $dir->namadepan }} {{ $dir->namabelakang }}</div>
                </td>
                <td width="350" align="center">
                    {{ $pinkel->kelompok->ketua }}
                </td>
            </tr>
            <tr style="opacity: 0;">
                <td colspan="3" style="font-weight: bold;" height="10">&nbsp;</td>
            </tr>
            <tr style="opacity: 0;">
                <td colspan="5">
                    <ol>
                        <b>Perhatian:</b>
                        <li>Bayarlah angsuran tepat waktu sesuai dengan jadwal diatas</li>
                        <li>Untuk memudahkan pelayanan, bawalah kartu ini dan slip pembayaran terakhir setiap melakukan
                            angsuran</li>
                        <li>Jagalah keutuhan kartu dan tidak melipatnya, jika hilang segera lapor UPK</li>
                        <li>Jika lembar ini tidak mencukupi, cetak pada lembar baliknya dengan dibubuhi stempel UPK</li>
                    </ol>
                </td>
            </tr>
        </table>
    </main>
</body>

</html>
