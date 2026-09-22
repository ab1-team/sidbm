@php
    use App\Utils\Tanggal;

    $jumlah = 0;
@endphp

@extends('perguliran.dokumen.layout.base')

@section('content')
    <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px;">
        <tr>
            <td colspan="3" align="center">
                <div style="font-size: 18px;">
                    <b>BERITA ACARA</b>
                </div>
                <div style="font-size: 18px;">
                    <b>RAPAT PENETAPAN PENDANAAN</b>
                </div>
            </td>
        </tr>
    </table>

    <p style="text-align: justify;">
        Dalam rangka menindak lanjuti proses tahapan perguliran atas kelompok kelompok permohonan piutang
        {{ $kec->nama_lembaga_sort }} yang sudah diterbitkan rekomendasi pada tahapan verifikasi maka pada hari ini
        {{ Tanggal::namaHari($pinkel->tgl_tunggu) }} tanggal {{ Tanggal::hari($pinkel->tgl_tunggu) }} bulan
        {{ Tanggal::namaBulan($pinkel->tgl_tunggu) }} tahun {{ Tanggal::tahun($pinkel->tgl_tunggu) }} bertempat di kantor
        {{ $kec->nama_lembaga_sort }} telah dilakukan pembahasan dan ditetapkan alokasi pendanaan dan rencana tanggal
        pencairan kepada kelompok sebagai berikut:
    </p>

    <table border="1" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px; table-layout: fixed;">
        <thead>
            <tr style="background: rgb(232,232,232)">
                <th width="4%">No</th>
                <th width="18%">Nama Kelompok</th>
                <th width="25%">Alamat</th>
                <th width="7%">Jenis</th>
                <th width="7%">Anggota</th>
                <th width="16%">Ketua Kelompok</th>
                <th width="14%">Alokasi Pendanaan</th>
                <th width="9%">Tenor</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($pinjaman as $pinj)
                @php
                    $jumlah += $pinj->alokasi;
                @endphp
                <tr>
                    <td align="center">
                        {{ $loop->iteration }}
                    </td>
                    <td>
                        {{ $pinj->kelompok->nama_kelompok }}
                    </td>
                    <td>
                        {{ $pinj->kelompok->alamat_kelompok }} {{ $pinj->kelompok->d->sebutan_desa->sebutan_desa }}
                        {{ $pinj->kelompok->d->nama_desa }}
                    </td>
                    <td align="center">
                        {{ $pinj->jpp->nama_jpp }}
                    </td>
                    <td align="center">
                        {{ $pinj->pinjaman_anggota_count }}
                    </td>
                    <td>
                        {{ $pinj->kelompok->ketua }}
                    </td>
                    <td align="right">
                        {{ number_format($pinj->alokasi) }}
                    </td>
                    <td align="right">
                        {{ ($pinj->jangka) }} Bulan
                    </td>
                </tr>
            @endforeach

            <tr style="font-weight: bold;">
                <td colspan="6" align="center">
                    JUMLAH
                </td>
                <td align="right">
                    {{ number_format($jumlah) }}
                </td>
                <td align="right">
                    &nbsp;
                </td>
            </tr>
        </tbody>
    </table>

    <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px; table-layout: fixed;">
        <tr>
            <td style="padding: 0px !important;">
                <p style="text-align: justify;">
                    Demikian Berita Acara ini dibuat dan ditanda tangani untuk menjadi dasar pencairan piutang kepada
                    kelompok kelompok
                    tersebut diatas.
                </p>

                <table border="0" width="100%" cellspacing="0" cellpadding="0"
                    style="font-size: 11px; table-layout: fixed;" class="p0">
                    <tr>
                        <td width="17%">&nbsp;</td>
                        <td width="33%">&nbsp;</td>
                        <td width="25%">Ditanda tangani di</td>
                        <td width="25%">: {{ $kec->sebutan_kec }} {{ $kec->nama_kec }}</td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>Pada tanggal</td>
                        <td>: {{ Tanggal::tglLatin($pinkel->tgl_cair) }}</td>
                    </tr>

                    <tr>
                        <td colspan="4">&nbsp;</td>
                    </tr>
                </table>

                @if ($tanda_tangan)
                    {!! $tanda_tangan !!}
                @else
                    <table border="0" width="100%" cellspacing="0" cellpadding="0"
                        style="font-size: 11px; table-layout: fixed;" class="p0">
                        @foreach ($pendanaan as $pend)
                            <tr class="vt">
                                <td height="20">
                                    <div>{{ $pend->namadepan }} {{ $pend->namabelakang }}</div>
                                    <div>
                                        <b>{{ $pend->j->nama_jabatan }}</b>
                                    </div>
                                </td>
                                <td align="right">
                                    <div>&nbsp;</div>
                                    <div>________________________________</div>
                                </td>
                                @if ($loop->iteration == '1')
                                    <td colspan="2" rowspan="{{ count($pendanaan) }}">
                                        <table width="100%" border="0" width="100%" cellspacing="0" cellpadding="0"
                                            style="font-size: 11px; table-layout: fixed;" class="p0">
                                            <tr>
                                                <td align="center">Mengetahui</td>
                                            </tr>
                                            <tr>
                                                <td align="center">{{ $kec->sebutan_level_1 }}</td>
                                            </tr>
                                            <tr>
                                                <td height="35">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td align="center">
                                                    {{ $dir->namadepan }} {{ $dir->namabelakang }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </table>
                @endif

            </td>
        </tr>
    </table>
@endsection
