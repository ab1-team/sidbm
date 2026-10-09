@php
    use App\Utils\Tanggal;
    use App\Utils\Keuangan;

    $keuangan = new Keuangan();

    $ketua = $pinkel->kelompok->ketua;
    $sekretaris = $pinkel->kelompok->sekretaris;
    if ($pinkel->struktur_kelompok) {
        $struktur_kelompok = json_decode($pinkel->struktur_kelompok, true);
        $ketua = isset($struktur_kelompok['ketua']) ? $struktur_kelompok['ketua'] : '';
        $sekretaris = isset($struktur_kelompok['sekretaris']) ? $struktur_kelompok['sekretaris'] : '';
        $bendahara = isset($struktur_kelompok['bendahara']) ? $struktur_kelompok['bendahara'] : '';
    }
@endphp

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

    table.p0 tr th,
    table.p0 tr td {
        padding: 0px !important;
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

<title>{{ $judul }}</title>
<table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 14px;">
    <tr class="b">
        <td colspan="3" align="center">
            <div style="font-size: 16px;">
                KELOMPOK PIUTANG {{ $pinkel->jpp->nama_jpp }}
            </div>
            <div style="font-size: 20px;">
                <b>{{ $pinkel->kelompok->nama_kelompok }}</b>
            </div>
            <div style="font-size: 14px;">
                Alamat : {{ $pinkel->kelompok->alamat_kelompok }}
                {{ $pinkel->kelompok->d->sebutan_desa->sebutan_desa }} {{ $pinkel->kelompok->d->nama_desa }}
                {{ $kec->nama_kec }} {{ $nama_kabupaten }} Telp: {{ $pinkel->kelompok->telpon }}
            </div>
        </td>
    </tr>
    <tr>
        <td colspan="3" height="5"></td>
    </tr>
</table>
<table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 14px;">
    <tr>
        <td width="30">&nbsp;</td>
        <td width="30">Nomor</td>
        <td width="5" align="right">:</td>
        <td width="500">
            <b>
                ______/{{ $pinkel->jpp->nama_jpp }}/{{ Tanggal::tglRomawi($pinkel->tgl_proposal) }}
            </b>
        </td>
    </tr>
    <tr>
        <td width="30">&nbsp;</td>
        <td width="30">Perihal</td>
        <td width="5" align="right">:</td>
        <td width="500">
            <b>Permohonan Kredit {{ $pinkel->jpp->nama_jpp }}</b>
        </td>
    </tr>
</table>
<table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 14px;">
    <tr>
        <td width="175">&nbsp;</td>
        <td width="100">
            <b>
                <div>Kepada Yth.</div>
                <div>{{ $kec->sebutan_level_1 }}</div>
                <div>{{ $kec->nama_lembaga_sort }}</div>
                <div>{{ $kec->sebutan_kec }} {{ $kec->nama_kec }}</div>
                <div>Di Tempat</div>
            </b>
        </td>
    </tr>

</table>
<table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 14px;">
    <tr>
        <td colspan="3" height="5"></td>
    </tr>
</table>
<table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 14px;">
    <tr>
        <td width="30">&nbsp;</td>
        <td colspan="3">Yang bertanda tangan di bawah ini :</td>
    </tr>
    <tr>
        <td width="30">&nbsp;</td>
        <td width="80">Nama Lengkap</td>
        <td width="5" align="right">:</td>
        <td style="font-weight: bold;">{{ $ketua }}</td>
    </tr>
    <tr>
        <td width="30">&nbsp;</td>
        <td>Alamat</td>
        <td width="5" align="right">:</td>
        <td style="font-weight: bold;">{{ $pinkel->kelompok->d->nama_desa }}</td>
    </tr>
    <tr>
        <td width="30">&nbsp;</td>
        <td>Jabatan</td>
        <td width="5" align="right">:</td>
        <td style="font-weight: bold;">{{ $pinkel->jenis_pp != '3' ? 'Ketua Kelompok' : 'Pimpinan' }}</td>
    </tr>
    <tr>
        <td width="30">&nbsp;</td>
        <td>Nama Lengkap</td>
        <td width="5" align="right">:</td>
        <td style="font-weight: bold;">{{ $sekretaris }}</td>
    </tr>
    <tr>
        <td width="30">&nbsp;</td>
        <td>Alamat</td>
        <td width="5" align="right">:</td>
        <td style="font-weight: bold;">{{ $pinkel->kelompok->d->nama_desa }}</td>
    </tr>
    <tr>
        <td width="30">&nbsp;</td>
        <td>Jabatan</td>
        <td width="5" align="right">:</td>
        <td style="font-weight: bold;">
            {{ $pinkel->jenis_pp != '3' ? 'Sekretaris Kelompok' : 'Penanggung Jawab' }}
        </td>
    </tr>
    <tr>
        <td colspan="4">
            <table border="0" width="100%" cellspacing="0" cellpadding="0">
                <td width="30">&nbsp;</td>
                <td colspan="3">
                    <div>
                        Dalam hal ini bertindak untuk dan atas nama seluruh anggota kelompok
                        {{ $pinkel->jpp->deskripsi_jpp }} ({{ $pinkel->jpp->nama_jpp }})
                        {{ $pinkel->kelompok->nama_kelompok }} (daftar anggota terlampir), dengan ini bermaksud
                        mengajukan
                        permohonan kredit sebesar Rp. {{ number_format($pinkel->proposal) }}
                        ({{ $keuangan->terbilang($pinkel->proposal) }}) untuk memenuhi kebutuhan tambahan modal usaha
                        bagi
                        {{ $pinkel->pinjaman_anggota_count }} anggota. Kredit atau piutang tersebut di atas, akan kami
                        kembalikan dalam jangka waktu {{ $pinkel->jangka }} {{ \App\Utils\Pinjaman::satuanJangka($pinkel, false) }}, dengan sistem angsuran
                        {{ $pinkel->sis_pokok->nama_sistem }} ({{ $pinkel->sis_pokok->deskripsi_sistem }}).
                    </div>
                    <div>
                        Sebagai bahan pertimbangan, bersama ini kami lampirkan:
                        <ol>
                            <li>Fotokopi KTP dari {{ $pinkel->pinjaman_anggota_count }} orang anggota kelompok kami
                                yang
                                mengajukan kredit;</li>
                            <li>Surat Rekomendasi dari Kepala Desa/Lurah;</li>
                            <li>Pernyataan kesediaan tanggung renteng dari seluruh anggota;</li>
                            <li>Surat pengakuan utang dan pertanggungan ahli waris</li>
                            <li>Rencana pengembalian kredit.</li>
                        </ol>
                    </div>
                    <div>Demikian permohonan kami, atas perhatiannya kami ucapkan terima kasih.</div>
                </td>
            </table>
            @if ($tanda_tangan)
                <div style="margin-top: 24px;">
                    {!! $tanda_tangan !!}
                </div>
            @else
                <table border="0" width="100%" cellspacing="0" cellpadding="0"
                    style="font-size: 14px; margin-top: 24px;">
                    <tr>
                        <td>&nbsp;</td>
                        <td align="center">{{ $kec->nama_kec }}, {{ Tanggal::tglLatin($pinkel->tgl_proposal) }}</td>
                    </tr>
                    <tr>
                        <td align="center" width="50%">Ketua Kelompok,</td>
                        <td align="center" width="50%">Sekretaris Kelompok,</td>
                    </tr>
                    <tr>
                        <td colspan="2" height="30"></td>
                    </tr>
                    <tr>
                        <td align="center" width="50%" style="font-weight: bold; text-decoration: underline;">
                            {{ $ketua }}
                        </td>
                        <td align="center" width="50%" style="font-weight: bold; text-decoration: underline;">
                            {{ $sekretaris }}
                        </td>
                    </tr>
                </table>
            @endif
        </td>
    </tr>
</table>
