@php
    use App\Utils\Tanggal;

    $waktu = '';
    $tempat = '';
    $wt_cair = explode('_', $pinkel->wt_cair);
    if (count($wt_cair) == 1) {
        $waktu = 'Pukul ' . $wt_cair[0];
    }

    if (count($wt_cair) == 2) {
        $waktu = 'Pukul ' . $wt_cair[0];
        $tempat = $wt_cair[1];
    }
@endphp

@extends('perguliran.dokumen.layout.base')

@section('content')
    <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 12px;">
        <tr>
            <td width="50">Nomor</td>
            <td width="10" align="center">:</td>
            <td colspan="2">
                ______________/{{ Tanggal::tglRomawi($pinkel->tgl_cair) }}
            </td>
        </tr>
        <tr>
            <td>Sifat</td>
            <td align="center">:</td>
            <td colspan="2">
                Penting dan Rahasia
            </td>
        </tr>
        <tr>
            <td>Perihal</td>
            <td align="center">:</td>
            <td colspan="2">
                <b>Pemberitahuan Pencairan</b>
            </td>
        </tr>
        <tr>
            <td colspan="3">&nbsp;</td>
            <td align="left" width="140">
                <div>Kepada Yth.</div>
                <div>
                    1. {{ $pinkel->kelompok->d->sebutan_desa->sebutan_kades }} {{ $pinkel->kelompok->d->nama_desa }},
                </div>
                <div>
                    2. Ketua Kelompok {{ $pinkel->kelompok->nama_kelompok }}
                </div>
                <div>Di</div>
                <div style="font-weight: bold; text-align: center;">
                    Tempat
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="4">&nbsp;</td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td colspan="3" align="justify">
                <div>Dengan hormat,</div>
                <div>
                    Menindaklanjuti hasil keputusan rapat pendanaan {{ $kec->nama_lembaga_sort }}
                    Tanggal {{ Tanggal::tglLatin($pinkel->tgl_dana) }} dengan ini memberitahukan bahwa akan dilakukan
                    pencairan kredit kepada ;
                </div>

                <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 12px;">
                    <tr>
                        <td width="10">1.</td>
                        <td width="120">Nama Kelompok</td>
                        <td width="5">:</td>
                        <td>{{ $pinkel->kelompok->nama_kelompok }}</td>
                    </tr>
                    <tr>
                        <td>2.</td>
                        <td>Alamat</td>
                        <td>:</td>
                        <td>
                            {{ ucwords(strtolower($pinkel->kelompok->alamat_kelompok)) }}
                            {{ ucwords(strtolower($pinkel->kelompok->d->sebutan_desa->sebutan_desa)) }}
                            {{ ucwords(strtolower($pinkel->kelompok->d->nama_desa)) }}
                        </td>
                    </tr>
                    <tr>
                        <td>3.</td>
                        <td>Tanggal Cair</td>
                        <td>:</td>
                        <td>{{ Tanggal::tglLatin($pinkel->tgl_cair) }} {{ $waktu }}</td>
                    </tr>
                    <tr>
                        <td>4.</td>
                        <td>Tempat</td>
                        <td>:</td>
                        <td>{{ $tempat }}</td>
                    </tr>
                    <tr>
                        <td>5.</td>
                        <td colspan="4">
                            Data pemanfaat dan alokasi pencairannya adalah sebagai berikut :
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 12px;">
        <tr>
            <th width="50">&nbsp;</th>
            <th style="background: rgb(233,233,233)" class="t l b" width="10" height="20">No</th>
            <th style="background: rgb(233,233,233)" class="t l b" width="100">Nama Pemanfaat</th>
            <th style="background: rgb(233,233,233)" class="t l b">Alamat</th>
            <th style="background: rgb(233,233,233)" class="t l b r" width="80">Alokasi (Rp)</th>
        </tr>

        @foreach ($pinkel->pinjaman_anggota as $pa)
            <tr>
                <td width="50">&nbsp;</td>
                <td class="t l b" align="center">{{ $loop->iteration }}</td>
                <td class="t l b">{{ $pa->anggota->namadepan }}</td>
                <td class="t l b">{{ $pa->anggota->alamat }}</td>
                <td class="t l b r" align="right">{{ number_format($pa->alokasi) }}</td>
            </tr>
        @endforeach

        <tr style="font-weight: bold;">
            <td width="50">&nbsp;</td>
            <td class="t l b" colspan="3">Jumlah</td>
            <td class="t l b r" align="right">{{ number_format($pinkel->pinjaman_anggota->sum('alokasi')) }}</td>
        </tr>
    </table>

    <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 12px;">
        <tr>
            <td width="50">&nbsp;</td>
            <td colspan="4">
                <p>
                    Demikian surat pemberitahuan ini kami sampaikan, atas perhatian dan kerjasamanya kami
                    ucapkan
                    terimakasih.
                </p>

                <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 12px;"
                    class="padding">
                    <tr>
                        <td colspan="2">
                            <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 12px;">
                                <tr>
                                    <td width="50%" height="10">&nbsp;</td>
                                    <td width="50%">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td align="center">{{ $kec->nama_kec }}, {{ Tanggal::tglLatin($pinkel->tgl_cair) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td align="center">
                                        {{ $kec->sebutan_level_1 }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3" height="40">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td align="center">
                                        <u>
                                            <b>{{ $dir->namadepan }} {{ $dir->namabelakang }}</b>
                                        </u>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
@endsection
