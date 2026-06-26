@php
    use App\Utils\Keuangan;
    $keuangan = new Keuangan();

    $saldo_aset = 0;

    $i = 0;
    foreach ($saldo_calk as $_saldo) {
        $calk["$i"]['th_lalu'] = floatval($_saldo->debit);
        if ($tgl_kondisi >= $tgl_mad) {
            $calk["$i"]['th_ini'] = floatval($_saldo->kredit);
        }

        $i++;
    }
@endphp

@extends('pelaporan.layout.base')

@section('content')
    <style>
        ol,
        ul {
            margin-left: unset;
        }

        .pointA *:first-child {
            margin-top: 0;
        }
    </style>
    <table border="0" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td colspan="3" align="center">
                <div style="font-size: 18px;">
                    <b>CATATAN ATAS LAPORAN KEUANGAN</b>
                </div>
                <div style="font-size: 18px; text-transform: uppercase;">
                    <b>{{ $kec->nama_lembaga_sort }}</b>
                </div>
                <div style="font-size: 16px;">
                    <b>{{ strtoupper($sub_judul) }}</b>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="3" height="5"></td>
        </tr>
    </table>

    <ol style="list-style: upper-alpha;">
        <li style="margin-top: 12px;">
            <div style="text-transform: uppercase;">
                Informasi Tambahan Laporan Keuangan
            </div>
            <div>
                <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px;">
                    <tr>
                        <td colspan="3" height="5"></td>
                    </tr>
                    <tr style="background: #000; color: #fff;">
                        <td width="30">Kode</td>
                        <td width="300">Nama Akun</td>
                        <td align="right">Saldo</td>
                    </tr>
                    <tr>
                        <td colspan="3" height="2"></td>
                    </tr>

                    @foreach ($akun1 as $lev1)
                        @php
                            $sum_akun1 = 0;
                        @endphp
                        <tr style="background: rgb(74, 74, 74); color: #fff;">
                            <td height="20" colspan="3" align="center">
                                <b>{{ $lev1->kode_akun }}. {{ $lev1->nama_akun }}</b>
                            </td>
                        </tr>
                        @foreach ($lev1->akun2 as $lev2)
                            <tr style="background: rgb(167, 167, 167); font-weight: bold;">
                                <td>{{ $lev2->kode_akun }}.</td>
                                <td colspan="2">{{ $lev2->nama_akun }}</td>
                            </tr>

                            @foreach ($lev2->akun3 as $lev3)
                                @php
                                    $sum_saldo = 0;
                                    $akun_lev4 = [];
                                @endphp

                                @foreach ($lev3->rek as $rek)
                                    @php
                                        $saldo = $keuangan->komSaldo($rek);
                                        if ($rek->kode_akun == '3.2.02.01') {
                                            $saldo = $keuangan->laba_rugi($tgl_kondisi);
                                        }

                                        $sum_saldo += $saldo;

                                        $akun_lev4[] = [
                                            'kode_akun' => $rek->kode_akun,
                                            'nama_akun' => $rek->nama_akun,
                                            'saldo' => $saldo,
                                        ];
                                    @endphp
                                @endforeach

                                @php
                                    if ($lev1->lev1 == '1') {
                                        $debit += $sum_saldo;
                                    } else {
                                        $kredit += $sum_saldo;
                                    }

                                    $sum_akun1 += $sum_saldo;
                                @endphp

                                <tr style="background: rgb(200,200,200);">
                                    <td>{{ $lev3->kode_akun }}.</td>
                                    <td>{{ $lev3->nama_akun }}</td>
                                    @if ($sum_saldo < 0)
                                        <td align="right">({{ number_format($sum_saldo * -1, 2) }})</td>
                                    @else
                                        <td align="right">{{ number_format($sum_saldo, 2) }}</td>
                                    @endif
                                </tr>

                                @foreach ($akun_lev4 as $lev4)
                                    @php
                                        $bg = 'rgb(230, 230, 230)';
                                        if ($loop->iteration % 2 == 0) {
                                            $bg = 'rgba(255, 255, 255)';
                                        }
                                    @endphp
                                    <tr style="background: rgb(255,255,255);">
                                        <td>{{ $lev4['kode_akun'] }}.</td>
                                        <td>{{ $lev4['nama_akun'] }}</td>
                                        @if ($lev4['saldo'] < 0)
                                            <td align="right">({{ number_format($lev4['saldo'] * -1, 2) }})</td>
                                        @else
                                            <td align="right">{{ number_format($lev4['saldo'], 2) }}</td>
                                        @endif
                                    </tr>
                                @endforeach
                            @endforeach
                        @endforeach

                        <tr style="background: rgb(167, 167, 167); font-weight: bold;">
                            <td height="20" colspan="2" align="left">
                                <b>Jumlah {{ $lev1->nama_akun }}</b>
                            </td>
                            <td align="right">{{ number_format($sum_akun1, 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="3" height="2"></td>
                        </tr>

                        @php
                            if ($lev1->lev1 == '1') {
                                $saldo_aset = $sum_akun1;
                            }
                        @endphp
                    @endforeach
                    <tr style="background: rgb(167, 167, 167); font-weight: bold;">
                        <td height="20" colspan="2" align="left">
                            <b>Jumlah Liabilitas + Ekuitas </b>
                        </td>
                        <td align="right">{{ number_format($kredit, 2) }}</td>
                    </tr>
                </table>
            </div>

            @php
                $saldo_aset = $saldo_aset;
                $kredit = $kredit;

                $saldo_calk = round($saldo_aset - $kredit, 2);
                if ($saldo_calk < 0) {
                    $saldo_calk *= -1;
                }
            @endphp

            @if ($saldo_calk != '0')
                <div style="color: #f44335">
                    Ada selisih antara Jumlah Aset dan Jumlah Liabilitas + Ekuitas sebesar
                    <b>Rp. {{ number_format($saldo_aset - $kredit, 2) }}</b>
                </div>
            @endif
        </li>
    </ol>
@endsection