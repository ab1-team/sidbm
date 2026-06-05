@extends('pelaporan.layout.base')

@section('content')
    <table border="0">
        <tr>
            <td width="5%">Nomor</td>
            <td width="50%">: ______________________</td>
            <td width="45%" align="right">{{ $kec->nama_kec }}, {{ $tgl }}</td>
        </tr>
        <tr>
            <td>Lampiran</td>
            <td>: 1 Bendel</td>
        </tr>
        <tr>
            <td>Perihal</td>
            <td>: Laporan Keuangan</td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td style="padding-left: 8px;">
                &nbsp; <u>Sampai Dengan {{ $sub_judul }}</u>
            </td>
        </tr>
        <tr>
            <td colspan="3" height="15"></td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td colspan="2" align="left" style="padding-left: 8px;">
                <div><b>Kepada Yth.</b></div>
                <div><b>{{ $kab->nama_lembaga }} {{ $nama_kabupaten }}</b></div>
                <div><b>Di {{ $kab->alamat_kab }}.</b></div>
            </td>
        </tr>
        <tr>
            <td colspan="3" height="15"></td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td colspan="2" style="padding-left: 8px; text-align: justify;">
                <div>Dengan Hormat,</div>
                <div>
                    Bersama ini kami sampaikan Laporan Keuangan {{ $nama_lembaga }} {{ $kec->nama_kec }} sampai dengan
                    {{ $sub_judul }} sebagai berikut:
                    <ol>
                        <li>Laporan Neraca</li>
                        <li>Laporan Laba Rugi</li>
                        <li>Laporan Arus kas</li>
                        <li>Laporan Perubahan Ekuitas</li>
                        <li>Catatan Atas Laporan Keuangan (CALK)</li>
                    </ol>
                </div>
                <div>
                    Demikian laporan kami sampaikan, atas perhatiannya kami ucapkan terima kasih.
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="3" height="15"></td>
        </tr>
        <tr>
            <td colspan="2"></td>
            <td align="center">
                <div>{{ $nama_lembaga }} {{ $kec->nama_kec }}</div>
                <div>
                    @if ($dir_utama)
                        {{ $dir_utama->j->nama_jabatan }},
                    @else
                        {{ $kec->sebutan_level_1 }},
                    @endif
                </div>
                <br>
                <br>
                <br>
                <br>
                @if (!empty($tanda_tangan))
                    {!! $tanda_tangan !!}
                @else
                    <div>
                        @if ($dir_utama)
                            <b>{{ $dir_utama->namadepan . ' ' . $dir_utama->namabelakang }}</b>
                        @else
                            <b>{{ $dir->namadepan . ' ' . $dir->namabelakang }}</b>
                        @endif
                    </div>
                @endif
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <div>
                    Tembusan : {{ $kec->kd_kab == '33.01' ? 'Kepada' : '' }}
                    <ol>
                        @if ($kec->kd_kab == '33.01')
                            <li>Yth. Camat {{ $kec->nama_kec }} (Sebagai Laporan)</li>
                            <li>Yth. Penasehat (Sebagai Laporan)</li>
                            <li>Yth. Pengurus Forum Bumdesma Kab. {{ $kec->kabupaten->nama_kab }}</li>
                            <li>Arsip</li>
                        @else
                            <li>Arsip</li>
                        @endif
                    </ol>
                </div>
            </td>
        </tr>
    </table>
@endsection
