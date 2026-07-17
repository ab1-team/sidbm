@php
    use App\Utils\Tanggal;
@endphp

@extends('perguliran.dokumen.layout.base')

@section('content')
    <style>
        .break {
            page-break-after: always;
        }
    </style>
    <table border="0" width="100%" cellspacing="0" cellpadding="0">
        @foreach ($pinjaman as $pinj)
            @php
                $waktu = date('H:i');
                $tempat = 'Kantor DBM';

                $wt_cair = explode('_', $pinj->pinkel->wt_cair);
                if (count($wt_cair) == 1) {
                    $waktu = $wt_cair[0];
                }

                if (count($wt_cair) == 2) {
                    $waktu = $wt_cair[0];
                    $tempat = $wt_cair[1];
                }

                $tgl_cair = $pinj->pinkel->tgl_cair;
            @endphp

            <tr>
                <td style="padding-top: 6px;">
                    <div style="padding: 12px; padding-bottom: 0px; border: 1px solid #000;">
                        <table border="0" width="100%" class="p" style="font-size: 11px;"">
                            <tr>
                                <td colspan="3" align="center" style="text-transform: uppercase; font-size: 14px;">
                                    <b>K u i t a n s i</b>
                                </td>
                            </tr>
                            <tr>
                                <td width="90">Telah Diterima Dari</td>
                                <td width="10" align="center">:</td>
                                <td class="b">
                                    <b>{{ $kec->sebutan_level_3 }}</b>
                                </td>
                            </tr>
                            <tr>
                                <td>Uang Sebanyak</td>
                                <td align="center">:</td>
                                <td class="b">
                                    <b>{{ $keuangan->terbilang($pinj->alokasi) }} Rupiah</b>
                                </td>
                            </tr>
                            <tr>
                                <td>Untuk Pembayaran</td>
                                <td align="center">:</td>
                                <td class="b">
                                    <b>Pencairan Pemanfaat Kelompok {{ $pinj->pinkel->kelompok->nama_kelompok }}</b>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">&nbsp;</td>
                                <td class="b">
                                    <b>
                                        a.n. {{ $pinj->namadepan }} NIK. {{ $pinj->nik }}
                                    </b>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3"></td>
                            </tr>
                            <tr>
                                <td colspan="2" class="t b" align="center">
                                    Rp. {{ number_format($pinj->alokasi) }}
                                </td>
                                <td>&nbsp;</td>
                            </tr>
                        </table>

                        <table border="0" width="100%" style="font-size: 11px;">
                            <tr>
                                <td width="60%">&nbsp;</td>
                                <td width="40%" align="center">
                                    {{ $kec->nama_kec }}, {{ Tanggal::tglLatin($tgl_cair) }}
                                </td>
                            </tr>
                            <tr>
                                <td width="60%">&nbsp;</td>
                                <td width="40%" align="center">
                                    Diterima Oleh
                                </td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td align="center">
                                    Anggota Pemanfaat
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" height="30">&nbsp;</td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td align="center">
                                    <b>{{ $pinj->namadepan }}</b>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        @endforeach
    </table>
@endsection
