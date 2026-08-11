@php
    use App\Utils\Tanggal;
    use App\Utils\Keuangan;

    $waktu = date('H:i');
    $tempat = 'Kantor DBM';

    $wt_cair = explode('_', $pinkel->wt_cair);
    if (count($wt_cair) == 1) {
        $waktu = $wt_cair[0];
    }

    if (count($wt_cair) == 2) {
        $waktu = $wt_cair[0];
        $tempat = $wt_cair[1];
    }

    $ketua = $pinkel->kelompok->ketua;
    $sekretaris = $pinkel->kelompok->sekretaris;
    $bendahara = $pinkel->kelompok->bendahara;
    if ($pinkel->struktur_kelompok) {
        $struktur_kelompok = json_decode($pinkel->struktur_kelompok, true);
        $ketua = isset($struktur_kelompok['ketua']) ? $struktur_kelompok['ketua'] : '';
        $sekretaris = isset($struktur_kelompok['sekretaris']) ? $struktur_kelompok['sekretaris'] : '';
        $bendahara = isset($struktur_kelompok['bendahara']) ? $struktur_kelompok['bendahara'] : '';
    }

    $jangka = $pinkel->jangka;
    $alokasi = intval($pinkel->alokasi);
    $pros_jasa = $pinkel->pros_jasa;
    $jasa_per_bulan = $pros_jasa > 0 ? number_format($pros_jasa / $jangka, 2) : '0';
    $angsuran_pokok = $jangka > 0 ? floor($alokasi / $jangka) : 0;
    $angsuran_jasa = $pros_jasa > 0 ? floor($alokasi * ($pros_jasa / 100) / $jangka) : 0;
@endphp

@extends('perguliran.dokumen.layout.base')

@section('content')
    <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 14px;">
        <tr>
            <td colspan="3" align="center">
                <div style="font-size: 18px;">
                    <b><u>SURAT PERJANJIAN PINJAMAN RESTRUKTURISASI</u></b>
                </div>
                <div style="font-size: 14px;">
                    No : {{ $pinkel->spk_no }}
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="3" height="10"></td>
        </tr>
    </table>

    <div style="text-align: justify; font-size: 14px;">
        Sesuai Surat Perjanjian Pinjaman Dana Bergulir Masyarakat {{ $kec->nama_lembaga_sort }},
        {{ $kec->sebutan_kec }} {{ $kec->nama_kec }} {{ $nama_kab }},
        No: {{ $pinkel->spk_no }}
    </div>

    <div style="text-align: justify; font-size: 14px; margin-top: 5px;">
        Tanggal; {{ Tanggal::tglLatin($pinkel->tgl_cair) }}
    </div>

    <div style="text-align: justify; font-size: 14px; margin-top: 5px;">
        Yang selanjutnya dipertegas melalui dokumen Penyelesaian Pinjaman Bermasalah,
        yang disetujui dan disepakati oleh seluruh pemanfaat serta diketahui
        dan telah dibubuhi tandatangan serta cap/meterai yang cukup.
    </div>

    <div style="text-align: justify; font-size: 14px; margin-top: 5px;">
        Maka, Berdasarkan Surat Keputusan Rapat Komite Pinjaman Dana {{ $kec->nama_lembaga_sort }},
        {{ $kec->sebutan_kec }} {{ $kec->nama_kec }} {{ $nama_kab }},
        yang dilaksanakan pada hari {{ Tanggal::namaHari($pinkel->tgl_cair) }},
        tanggal {{ Tanggal::tglLatin($pinkel->tgl_cair) }},
    </div>

    <div style="text-align: justify; font-size: 14px; margin-top: 10px;">
        Yang bertanda tangan dibawah ini, kami :
    </div>

    {{-- PIHAK PERTAMA --}}
    <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 14px; margin-top: 5px;">
        <tr>
            <td width="20" style="vertical-align: top;">1.</td>
            <td>
                <table border="0" width="100%" cellspacing="0" cellpadding="0">
                    <tr>
                        <td width="90">Nama</td>
                        <td width="10" align="center">:</td>
                        <td>{{ $dir->namadepan }} {{ $dir->namabelakang }}</td>
                    </tr>
                    <tr>
                        <td>Jabatan</td>
                        <td align="center">:</td>
                        <td>{{ $kec->sebutan_level_1 }} {{ $kec->nama_lembaga_sort }},
                            {{ $kec->sebutan_kec }} {{ $kec->nama_kec }} {{ $nama_kab }}.</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div style="text-align: justify; font-size: 14px; margin-top: 5px; margin-left: 20px;">
        Bertindak untuk dan atas nama {{ $kec->nama_lembaga_sort }},
        Selanjutnya disebut: <b><i><u>PIHAK PERTAMA</u></i></b> atau <b><i><u>{{ strtoupper($kec->nama_lembaga_sort) }}</u></i></b>
    </div>

    {{-- PIHAK KEDUA --}}
    <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 14px; margin-top: 10px;">
        <tr>
            <td width="20" style="vertical-align: top;">2.</td>
            <td>
                <table border="0" width="100%" cellspacing="0" cellpadding="0">
                    <tr>
                        <td width="90">Nama</td>
                        <td width="10" align="center">:</td>
                        <td>{{ $ketua }}</td>
                    </tr>
                    <tr>
                        <td>Alamat</td>
                        <td align="center">:</td>
                        <td>{{ $pinkel->kelompok->alamat_kelompok }},
                            {{ $pinkel->kelompok->d->sebutan_desa->sebutan_desa ?? 'Desa' }}
                            {{ $pinkel->kelompok->d->nama_desa }},
                            {{ $kec->sebutan_kec }} {{ $kec->nama_kec }}, {{ $nama_kab }}.</td>
                    </tr>
                    <tr>
                        <td>Jabatan</td>
                        <td align="center">:</td>
                        <td>Ketua Kelompok</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 14px; margin-top: 3px;">
        <tr>
            <td width="20" style="vertical-align: top;">3.</td>
            <td>
                <table border="0" width="100%" cellspacing="0" cellpadding="0">
                    <tr>
                        <td width="90">Nama</td>
                        <td width="10" align="center">:</td>
                        <td>{{ $bendahara }}</td>
                    </tr>
                    <tr>
                        <td>Alamat</td>
                        <td align="center">:</td>
                        <td>{{ $pinkel->kelompok->alamat_kelompok }},
                            {{ $pinkel->kelompok->d->sebutan_desa->sebutan_desa ?? 'Desa' }}
                            {{ $pinkel->kelompok->d->nama_desa }},
                            {{ $kec->sebutan_kec }} {{ $kec->nama_kec }}, {{ $nama_kab }}.</td>
                    </tr>
                    <tr>
                        <td>Jabatan</td>
                        <td align="center">:</td>
                        <td>Bendahara Kelompok</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div style="text-align: justify; font-size: 14px; margin-top: 5px; margin-left: 20px;">
        Bertindak untuk dan atas nama Kelompok {{ $pinkel->kelompok->nama_kelompok }},
        {{ $pinkel->kelompok->d->sebutan_desa->sebutan_desa ?? 'Desa' }}
        {{ $pinkel->kelompok->d->nama_desa }}
        {{ $kec->sebutan_kec }} {{ $kec->nama_kec }} {{ $nama_kab }}.
    </div>

    <div style="text-align: justify; font-size: 14px; margin-top: 10px; margin-left: 20px;">
        Selanjutnya disebut: <b><i><u>PIHAK KEDUA</u></i></b> atau <b><i><u>PEMINJAM.</u></i></b>
    </div>

    <div style="text-align: justify; font-size: 14px; margin-top: 10px;">
        Dalam perjanjian ini diuraikan hal-hal sebagai berikut :
    </div>

    {{-- PASAL 1 --}}
    <div style="text-align: center; font-size: 14px; margin-top: 10px;">
        <b>Pasal 1</b>
    </div>

    <table border="0" width="100%" cellspacing="0" cellpadding="2" style="font-size: 14px; margin-top: 5px;">
        <tr>
            <td width="20" style="vertical-align: top;">1.</td>
            <td style="text-align: justify;">
                PIHAK PERTAMA memberikan pinjaman kepada PIHAK KEDUA dan PIHAK KEDUA menerima pinjaman tersebut berupa
                uang sebesar <b>Rp. {{ number_format($alokasi) }}</b>
                (<i>{{ $keuangan->terbilang($alokasi) }} rupiah</i>)
                selanjutnya disebut <b><i><u>PINJAMAN</u></i></b>;
            </td>
        </tr>
        <tr>
            <td style="vertical-align: top;">2.</td>
            <td style="text-align: justify;">
                PINJAMAN tersebut diangsur selama <b>{{ $jangka }} ({{ $keuangan->terbilang($jangka) }}) bulan</b>,
                dengan angsuran pokok dan angsuran bunga, selanjutnya disebut <b><i><u>ANGSURAN</u></i></b>;
            </td>
        </tr>
    </table>

    {{-- PASAL 2 --}}
    <div style="text-align: center; font-size: 14px; margin-top: 10px;">
        <b>Pasal 2</b>
    </div>

    <table border="0" width="100%" cellspacing="0" cellpadding="2" style="font-size: 14px; margin-top: 5px;">
        <tr>
            <td width="20" style="vertical-align: top;">1.</td>
            <td style="text-align: justify;">
                PEMINJAM dikenakan biaya administrasi atau tata usaha penjadwalan ulang pinjaman
                sebesar 1,5% dari pokok PINJAMAN;
            </td>
        </tr>
        <tr>
            <td style="vertical-align: top;">2.</td>
            <td style="text-align: justify;">
                Atas PINJAMAN tersebut PEMINJAM dikenakan Bunga sebesar
                <b>{{ $jasa_per_bulan }}%</b> dari pokok PINJAMAN per bulan secara menetap (flat);
            </td>
        </tr>
        <tr>
            <td style="vertical-align: top;">3.</td>
            <td style="text-align: justify;">
                Setiap keterlambatan pembayaran ANGSURAN dikenakan sanksi sesuai ketentuan
                Pinjaman Kelompok DBM yang telah ditetapkan oleh {{ strtoupper($kec->nama_lembaga_sort) }};
            </td>
        </tr>
    </table>

    {{-- PASAL 3 --}}
    <div style="text-align: center; font-size: 14px; margin-top: 10px;">
        <b>Pasal 3</b>
    </div>

    <div style="text-align: justify; font-size: 14px; margin-top: 5px; margin-left: 20px;">
        Hal &ndash; hal yang tidak diatur dalam perjanjian ini, berlaku ketentuan yang sudah disepakati
        dan sekaligus menjadi bagian yang tidak terpisahkan/addendum dalam ikatan surat perjanjian
        dan/ atau pernyataan terdahulu;
    </div>

    {{-- PASAL 4 --}}
    <div style="text-align: center; font-size: 14px; margin-top: 10px;">
        <b>Pasal 4</b>
    </div>

    <div style="text-align: justify; font-size: 14px; margin-top: 5px;">
        Bila dikemudian hari terjadi perselisihan yang timbul akibat perjanjian ini, kedua belah pihak
        sepakat menyelesaikan secara musyawarah. Dan, jika diselesaikan secara hukum memilih domisili
        hukum yang tetap dan tidak berubah pada Kantor Pengadilan Negeri di {{ $nama_kab }},
        pemilihan mana berlaku pula bagi para ahli waris PEMINJAM;
    </div>

    <div style="text-align: justify; font-size: 14px; margin-top: 15px;">
        Demikian perjanjian pinjaman penyelesaian ulang ini dibuat dan ditandatangani di :
        {{ $kec->nama_kec }}, Pada tanggal {{ Tanggal::tglLatin($pinkel->tgl_cair) }}.
    </div>

    {{-- TANDA TANGAN --}}
    @if ($tanda_tangan)
        {!! $tanda_tangan !!}
    @else
        <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 14px; margin-top: 20px;"
            class="p">
            <tr>
                <td width="50%" align="center">Pihak Pertama;</td>
                <td width="50%" align="center">Pihak Kedua;</td>
            </tr>
            <tr>
                <td align="center">
                    <b>{{ strtoupper($kec->nama_lembaga_sort) }}</b>
                </td>
                <td align="center"><b>PEMINJAM,</b></td>
            </tr>
            <tr>
                <td align="center">{{ $kec->sebutan_level_1 }};</td>
                <td align="center">
                    <div style="font-size: 8px; font-style: italic;">Materai Rp. 10.000,-</div>
                </td>
            </tr>
            <tr>
                <td colspan="2" height="50"></td>
            </tr>
            <tr>
                <td align="center">
                    <b>( <u>{{ $dir->namadepan }} {{ $dir->namabelakang }}</u> )</b>
                </td>
                <td align="center">
                    <b>( <u>{{ $ketua }}</u> ) X ( <u>{{ $bendahara }}</u> )</b>
                </td>
            </tr>
        </table>
    @endif
@endsection
