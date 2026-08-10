@php
    use App\Utils\Tanggal;

    $ketua = $pinkel->kelompok->ketua;
    $sekretaris = $pinkel->kelompok->sekretaris;
    $bendahara = $pinkel->kelompok->bendahara;
    if ($pinkel->struktur_kelompok) {
        $struktur_kelompok = json_decode($pinkel->struktur_kelompok, true);
        $ketua = isset($struktur_kelompok['ketua']) ? $struktur_kelompok['ketua'] : '';
        $sekretaris = isset($struktur_kelompok['sekretaris']) ? $struktur_kelompok['sekretaris'] : '';
        $bendahara = isset($struktur_kelompok['bendahara']) ? $struktur_kelompok['bendahara'] : '';
    }
@endphp

@extends('perguliran.dokumen.layout.base')

@section('content')
    <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px;">
        <tr>
            <td colspan="3" align="center">
                <div style="font-size: 18px;">
                    <b>BERITA ACARA PERTEMUAN</b>
                </div>
                <div style="font-size: 16px;">
                    <b>
                        {{ $pinkel->jenis_pp != '3' ? 'KELOMPOK' : '' }} {{ strtoupper($pinkel->jpp->deskripsi_jpp) }}
                    </b>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="3" height="5"></td>
        </tr>
    </table>

    <div style="text-align: justify;">
        Sehubungan dengan rencana kelompok mengajukan piutang kepada {{ $kec->nama_lembaga_sort }} {{ $kec->sebutan_kec }}
        {{ $kec->nama_kec }}, maka pada hari ini _________ tanggal ___ bulan ____________ tahun _____ , bertempat di
        {{ $pinkel->kelompok->alamat_kelompok }} {{ $pinkel->kelompok->d->sebutan_desa->sebutan_desa }}
        {{ $pinkel->kelompok->d->nama_desa }}, nama kelompok {{ $pinkel->kelompok->nama_kelompok }}
        {{ $pinkel->kelompok->d->sebutan_desa->sebutan_desa }} {{ $pinkel->kelompok->d->nama_desa }}
        {{ $kec->sebutan_kec }} {{ $kec->nama_kec }} telah diselenggarakan musyawarah kelompok yang dihadiri oleh
        anggota kelompok dan unsur lain yang terkait di desa sebagaimana tercantum dalam daftar hadir terlampir.
    </div>

    <div style="text-align: justify;">
        Adapun materi atau topik yang dibahas dalam musyawarah ini adalah sebagai berikut :
        <ol style="list-style: lower-alpha;">
            <li>Jumlah anggota peminjam dan besar piutang yang diajukan</li>
            <li>Penetapan bunga piutang kepada anggota</li>
            <li>Rencana penggunaan selisih angsuran bunga</li>
            <li>Sanksi kelompok bagi anggota peminjam yang menunggak</li>
        </ol>
    </div>

    <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px;">
        <tr>
            <td width="130">Acara musyawarah dipimpin oleh</td>
            <td width="5" align="center">:</td>
            <td>{{ $ketua }}</td>
        </tr>
        <tr>
            <td>Notulen</td>
            <td>:</td>
            <td>{{ $sekretaris }}</td>
        </tr>
        <tr>
            <td>Narasumber</td>
            <td>:</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td colspan="3">_______________________________________</td>
        </tr>
        <tr>
            <td colspan="3">_______________________________________</td>
        </tr>
    </table>

    <div style="text-align: justify;">
        Setelah dilakukan diskusi dan musyawarah terhadap topik di atas, peserta memutuskan dan telah menyepakati sebagai
        berikut :

        <ol style="list-style: lower-alpha;">
            <li>
                Forum menyepakati jumlah anggota kelompok yang akan mengajukan piutang yaitu
                {{ $pinkel->pinjaman_anggota_count }} orang dan besar piutang yang akan diajukan kepada
                {{ $kec->nama_lembaga_sort }} sebesar Rp {{ number_format($pinkel->proposal) }}
            </li>
            <li>
                Forum menyepakati anggota kelompok wajib mengangsur setiap {{ $pinkel->sis_pokok->nama_sistem }} sesuai
                dengan jumlah piutang yang diterima dan tanggal yang telah disepakati.
            </li>
            <li>
                Forum menyepakati bunga piutang yang akan diberlakukan kepada anggota kelompok sebesar
                @if ($kec->id == 373)
                    _____% setiap bulan dalam {{ $pinkel->jangka }} bulan.
                @else
                    {{ $pinkel->pros_jasa / $pinkel->jangka }}% setiap bulan dalam {{ $pinkel->jangka }} bulan.
                @endif
            </li>
            <li>
                Forum menyepakati penggunaan selisih angsuran bunga ke {{ $kec->nama_lembaga_sort }} akan digunakan
                untuk :

                <ul style="list-style: decimal;">
                    <li>Honor Pengurus</li>
                    <li>Transport Pengurus</li>
                    <li>ATK Kelompok</li>
                </ul>
            </li>
            <li>
                Forum menyepakati besar tabungan beku tanggung renteng yaitu _____ % dan tabungan beku tanggung renteng
                tersebut akan disimpan di Rekening Bank.
            </li>
            <li>
                Forum menyepakati aturan dan sanksi kelompok bila anggota menunggak, yaitu :

                <ul style="list-style: decimal;">
                    <li>Setiap Kelompok harus membuat proposal sesuai petunjuk</li>
                    <li>Proposal Wajib melampirkan KK dan KTP</li>
                    <li>
                        Setiap peminjam wajib melunasi piutangnya sesuai dengan jangka waktu yang disepakati atau
                        diperjanjikan angsuran pokok beserta bunganya
                    </li>
                    <li>
                        Setiap peminjam wajib menyimpan dana pada rekening tabungan beku atau tanggung renteng kelompok
                        sebesar ___% dari besar piutangnya
                    </li>
                    <li>
                        Apabila peminjam meninggal dunia dan masih memiliki sisa angsuran, maka sisa angsuran tersebut harus
                        dilunasi oleh Ahli Warisnya atau oleh pribadi yang menandatangani pada surat pernyataann persetujuan
                        piutang
                    </li>
                </ul>

                Sanksi :
                <ul style="list-style: decimal;">
                    <li>
                        Apabila peminjam tidak melunasi piutangnya, maka yang bersangkutan akan diberi peringatan dan atau
                        dilakukan proses hukum yang berlaku. Jika ahli waris atau penanggung jawab adalah warga yang tidak
                        mampu, maka bisa mengajukan permohonan Penghapusan Piutang sesuai dengan aturan yang berlaku di
                        {{ $kec->nama_lembaga_sort }} {{ $kec->sebutan_kec }} {{ $kec->nama_kec }}
                    </li>
                </ul>
            </li>
        </ol>
        Keputusan di atas disepakati dengan cara musyawarah mukafat / voting
    </div>

    <div style="text-align: justify;">
        Demikian berita acara ini kami buat dengan sebenar-benarnya dan atas dasar musyawarah kelompok agar dapat
        dipergunakan sebagaimana mestinya.
    </div>

    <div class="break"></div>
    <div style="text-align: justify">
        <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px;">
            <tr>
                <td height="15" style="padding: 0px;" colspan="3">Mengetahui dan menyetujui</td>
            </tr>
            <tr>
                <td height="15" style="padding: 0px;" colspan="3">Wakil Anggota/Peserta Musyawarah :</td>
            </tr>
            <tr>
                <td height="15" style="padding: 0px;" align="center">Nama Lengkap</td>
                <td style="padding: 0px;" align="center" colspan="2">Tanda Tangan</td>
            </tr>

            @for ($i = 1; $i <= $pinkel->pinjaman_anggota_count; $i++)
                <tr>
                    <td height="15" style="padding: 0px;" width="50%">{{ $i }}.
                        ______________________________________________</td>
                    @if ($i % 2 == 0)
                        <td style="padding: 0px;" width="25%">&nbsp;</td>
                        <td style="padding: 0px;" width="25%">{{ $i }}. _____________________</td>
                    @else
                        <td style="padding: 0px;" width="25%">{{ $i }}. _____________________</td>
                        <td style="padding: 0px;" width="25%">&nbsp;</td>
                    @endif
                </tr>
            @endfor

            <tr>
                <td colspan="3">&nbsp;</td>
            </tr>
            <tr>
                <td></td>
                <td align="center" colspan="2">{{ $kec->nama_kec }}, _______________________</td>
            </tr>
            <tr>
                <td align="center">Ketua Kelompok</td>
                <td align="center" colspan="2">Sekretaris Kelompok</td>
            </tr>
            <tr>
                <td align="center" colspan="3" height="40">&nbsp;</td>
            </tr>
            <tr>
                <td align="center">
                    <b>{{ $ketua }}</b>
                </td>
                <td align="center" colspan="2">
                    <b>{{ $sekretaris }}</b>
                </td>
            </tr>
        </table>
    </div>
@endsection
