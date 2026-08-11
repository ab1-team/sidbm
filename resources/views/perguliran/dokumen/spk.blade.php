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

    $redaksi_spk = '';
    if ($kec->redaksi_spk) {
        $redaksi_spk = str_replace('<ol>', '', str_replace('</ol>', '', $kec->redaksi_spk));
        $redaksi_spk = str_replace('<ul>', '', str_replace('</ul>', '', $redaksi_spk));
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
    $sa_pokok = $pinkel->sistem_angsuran;
    $sistem_pokok = $pinkel->sis_pokok->sistem;
    if ($sa_pokok == 11) {
        $tempo_pokok = $jangka - 24 / $sistem_pokok;
        $mulai_angsuran_pokok = $jangka - $tempo_pokok;
    } elseif ($sa_pokok == 14) {
        $tempo_pokok = $jangka - 3 / $sistem_pokok;
        $mulai_angsuran_pokok = $jangka - $tempo_pokok;
    } elseif ($sa_pokok == 26) {
        $tempo_pokok = $jangka - 6 / $sistem_pokok;
        $mulai_angsuran_pokok = $jangka - $tempo_pokok;
    } elseif ($sa_pokok == 15) {
        $tempo_pokok = $jangka - 2 / $sistem_pokok;
        $mulai_angsuran_pokok = $jangka - $tempo_pokok;
    } elseif ($sa_pokok == 25) {
        $tempo_pokok = $jangka - 1 / $sistem_pokok;
        $mulai_angsuran_pokok = $jangka - $tempo_pokok;
    } elseif ($sa_pokok == 20) {
        $tempo_pokok = $jangka - 12 / $sistem_pokok;
        $mulai_angsuran_pokok = $jangka - $tempo_pokok;
    } else {
        $tempo_pokok = floor($jangka / $sistem_pokok);
        $mulai_angsuran_pokok = 0;
    }
@endphp

@extends('perguliran.dokumen.layout.base')

@section('content')
    @php
        $resc = '';
        $title_spk = 'SURAT PERJANJIAN KREDIT (SPK)';
        $text_spk = 'surat perjanjian kredit (SPK)';
        if ($pinkel->sumber == '2') {
            $resc = ' RESCHEDULING';
            $title_spk = 'SURAT PERJANJIAN KREDIT RESCHEDULING';
            $text_spk = 'surat perjanjian kredit rescheduling (SPK RESCHEDULING)';
        }
    @endphp

    <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 14px;">
        <tr>
            <td colspan="3" align="center">
                <div style="font-size: 18px;">
                    <b>{{ $title_spk }}</b>
                </div>
                <div style="font-size: 14px;">
                    Nomor: {{ $pinkel->spk_no }}
                </div>
                <div style="font-size: 14px;">
                    Tanggal: {{ Tanggal::tglLatin($pinkel->tgl_cair) }}
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="3" height="5"></td>
        </tr>
    </table>

    <div style="text-align: justify; font-size: 14px;">
        Dengan memohon rahmat Tuhan Yang Maha Kuasa serta kesadaran akan cita-cita luhur pemberdayaan masyarakat desa untuk
        mencapai kemajuan ekonomi dan kemakmuran bersama, pada hari ini {{ Tanggal::namaHari($pinkel->tgl_cair) }} tanggal
        {{ $keuangan->terbilang(Tanggal::hari($pinkel->tgl_cair)) }} bulan {{ Tanggal::namaBulan($pinkel->tgl_cair) }} tahun
        {{ $keuangan->terbilang(Tanggal::tahun($pinkel->tgl_cair)) }}, bertempat di {{ $tempat }} kami yang bertanda
        tangan dibawah ini;
    </div>

    <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 14px;">
        <tr>
            <td width="90">Nama Lengkap</td>
            <td width="10" align="center">:</td>
            <td>{{ $dir->namadepan }} {{ $dir->namabelakang }}</td>
        </tr>
        <tr>
            <td>Jabatan</td>
            <td align="center">:</td>
            <td>{{ $kec->sebutan_level_1 }} {{ $kec->nama_lembaga_sort }}</td>
        </tr>
        <tr>
            <td>NIK</td>
            <td align="center">:</td>
            <td>{{ $dir->nik }}</td>
        </tr>
        <tr>
            <td>Alamat</td>
            <td align="center">:</td>
            <td>{{ $dir->alamat }}</td>
        </tr>
    </table>

    <div style="text-align: justify; font-size: 14px;">
        Bertindak untuk dan atas nama Manajemen {{ $kec->nama_lembaga_sort }} {{ $kec->sebutan_kec }}
        {{ $kec->nama_kec }} selaku pengelola Dana Bergulir Masyarakat untuk {{ $pinkel->jpp->deskripsi_jpp }}
        ({{ $pinkel->jpp->nama_jpp }}) di {{ $kec->sebutan_kec }} {{ $kec->nama_kec }}, selanjutnya disebut PIHAK
        PERTAMA, dan
    </div>

    <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 14px;">
        <tr>
            <td width="90">Nama Lengkap</td>
            <td width="10" align="center">:</td>
            <td>{{ $ketua }}</td>
        </tr>
        <tr>
            <td>Jabatan</td>
            <td align="center">:</td>
            <td>Ketua Kelompok</td>
        </tr>
        @if (Session::get('lokasi') != '523')
            <tr>
                <td>Nama Lengkap</td>
                <td align="center">:</td>
                <td>{{ $sekretaris }}</td>
            </tr>
            <tr>
                <td>Jabatan</td>
                <td align="center">:</td>
                <td>Sekretaris Kelompok</td>
            </tr>
            <tr>
                <td>Nama Lengkap</td>
                <td align="center">:</td>
                <td>{{ $bendahara }}</td>
            </tr>
            <tr>
                <td>Jabatan</td>
                <td align="center">:</td>
                <td>Bendahara Kelompok</td>
            </tr>
        @endif
    </table>

    <div style="text-align: justify; font-size: 14px;">
        Bertindak untuk dan atas nama kelompok {{ $pinkel->jpp->nama_jpp }} {{ $pinkel->kelompok->nama_kelompok }} yang
        berkedudukan di {{ $pinkel->kelompok->alamat_kelompok }} {{ $pinkel->kelompok->d->sebutan_desa->sebutan_desa }}
        {{ $pinkel->kelompok->d->nama_desa }} {{ $kec->sebutan_kec }} {{ $kec->nama_kec }}, dan beserta anggota yang
        memberikan kuasa secara tertulis sebagaimana Surat Kuasa terlampir sebagai bagian yang tidak terpisahkan dari
        dokumen perjanjian kredit ini, selanjutnya disebut PIHAK KEDUA.
    </div>

    <p style="text-align: justify; font-size: 14px;">
        Dalam kedudukan para pihak sebagaimana tertulis diatas, dengan sadar dan sukarela serta rasa penuh tanggung jawab
        menyatakan telah membuat {{ $text_spk }} dengan ketentuan-ketentuan yang disepakati bersama sebagai
        berikut :
    </p>

    <div style="text-align: center;">
        <div>
            <b style="font-size: 14px;">PASAL 1</b>
        </div>
        @if (Session::get('lokasi') == '270' && $pinkel->sumber == '2')
            <div>
                <b style="font-size: 14px;">DASAR RESCHEDULING</b>
            </div>
        @endif

        <ol style="text-align: justify; font-size: 14px;">
            @if (Session::get('lokasi') == '270' && $pinkel->sumber == '2')
                <li>
                    PIHAK KEDUA mengajukan permohonan penjadwalan ulang pembayaran kredit karena mengalami penurunan
                    kemampuan membayar yang disebabkan oleh: penurunan usaha dan/atau penurunan pendapatan.
                </li>
                <li>
                    PIHAK PERTAMA telah melakukan evaluasi dan menyetujui permohonan tersebut.
                </li>
            @else
                <li>
                    @php
                        $text = 'setuju memberikan kredit/piutang kepada Pihak Kedua sebesar';
                        if ($pinkel->sumber == '2') {
                            $text = 'setuju melakukan penjadwalan ulang atas sisa pinjaman Pihak Kedua sebesar';
                        }
                    @endphp

                    Pihak Pertama {{ $text }} Rp.
                    {{ number_format($pinkel->alokasi) }} ({{ $keuangan->terbilang($pinkel->alokasi) }} Rupiah) yaitu
                    jumlah
                    yang telah diputuskan dalam rapat penetapan pendanaan, berdasarkan permohonan dari Pihak Kedua dan para
                    pemberi kuasa yang dilakukan secara kelompok sesuai Surat Permohonan Kredit tanggal
                    {{ Tanggal::tglLatin($pinkel->tgl_proposal) }}.
                </li>
                <li>
                    @php
                        $text =
                            'menyatakan telah menerima uang dengan jumlah sebagaimana yang tertulis pada ayat 1 diatas';
                        if ($pinkel->sumber == '2') {
                            $text = 'menyatakan telah setuju penjadwalam ulang sbgmana yg tertulis pada ayat 1 diatas';
                        }
                    @endphp

                    Pihak Kedua dan Pemberi kuasa, {{ $text }}., dan telah diterima oleh para anggota pemanfaat
                    sesuai
                    kelayakan kredit
                    masing-masing anggota pemanfaat yang dibuktikan secara sah dengan daftar penerima dana terlampir,
                    dan sekaligus berlaku sebagai Surat Pengakuan Hutang, baik bagi setiap anggota penerima manfaat
                    maupun secara kelompok dalam pernyataan ketaatan tanggung-renteng.
                </li>
            @endif
        </ol>
    </div>

    <div style="text-align: center;">
        <div>
            <b style="font-size: 14px;">PASAL 2</b>
        </div>
        @if (Session::get('lokasi') == '270' && $pinkel->sumber == '2')
            <div>
                <b style="font-size: 14px;">KETENTUAN RESCHEDULING</b>
            </div>
        @endif


        <div style="text-align: justify; font-size: 14px;">
            @if (Session::get('lokasi') == '270' && $pinkel->sumber == '2')
                Dengan adanya penjadwalan ulang ini, kedua belah pihak sepakat bahwa:
            @else
                Kedua belah Pihak secara sukarela menerima syarat-syarat perjanjian utang-piutang sebagaimana
                dinyatakan dalam ketentuan-ketentuan dibawah ini :
            @endif

            <ol style="text-align: justify;">
                @if (Session::get('lokasi') == '270' && $pinkel->sumber == '2')
                    <li>
                        Sisa pokok pinjaman per tanggal {{ Tanggal::tglLatin($pinkel->tgl_cair) }} adalah sebesar Rp.
                        {{ number_format($pinkel->alokasi) }}.
                    </li>
                    <li>
                        Jangka waktu kredit diubah menjadi {{ $pinkel->jangka }} bulan/tahun, terhitung mulai tanggal
                        {{ Tanggal::tglLatin(date('Y-m-d', strtotime('+1 month', strtotime($pinkel->tgl_cair)))) }}.
                    </li>
                    <li>
                        Besaran angsuran baru yang wajib dibayar PIHAK KEDUA adalah sebesar Rp.
                        {{ number_format(Keuangan::pembulatan($pinkel->alokasi / $tempo_pokok, (string) $kec->pembulatan)) }}
                        per bulan, dibayarkan paling lambat tanggal
                        {{ date('d', strtotime('+1 month', strtotime($pinkel->tgl_cair))) }} setiap bulan.
                    </li>
                    <li>
                        Jadwal pembayaran angsuran terlampir dan menjadi bagian tidak terpisahkan dari perjanjian ini.
                    </li>
                @else
                    <li>
                        Dana piutang dari {{ $kec->nama_lembaga_sort }} akan dipergunakan untuk kegiatan usaha
                        dan/atau pembiayaan hal-hal yang bermanfaat untuk meningkatkan pendapatan dan mutu kehidupan
                        keluarga guna memberikan manfaat sebesar-besarnya bagi pertumbuhan ekonomi dan kesejahteraan
                        keluarga pengurus dan anggota kelompok {{ $pinkel->kelompok->nama_kelompok }}.
                    </li>
                    <li>
                        Menjunjung tinggi dan ikut menyepakati hasil Musyawarah pendanaan yang telah menetapkan piutang
                        kelompok sebagaimana kelompok {{ $pinkel->kelompok->nama_kelompok }} adalah termasuk dalam kategori
                        kelompok yang sepakat memberikan dukungan operasional dan pengembangan kepada
                        {{ $kec->nama_lembaga_sort }} secara progresif proporsional berupa jasa piutang sebesar
                        {{ number_format($pinkel->pros_jasa / $pinkel->jangka, 2) }}% {{ $pinkel->jasa->nama_jj }}
                        per-bulan
                        dikalikan pokok
                        piutang.
                    </li>
                    <li>
                        Kelompok menyepakati akan melakukan angsuran kredit dalam jangka waktu {{ $pinkel->jangka }}
                        ({{ $keuangan->terbilang($pinkel->jangka) }}) bulan dengan cara membayar angsuran Pokok
                        {{ $pinkel->sis_pokok->nama_sistem }} ({{ $pinkel->sis_pokok->deskripsi_sistem }}) dan angsuran
                        jasa
                        {{ $pinkel->sis_jasa->nama_sistem }} ({{ $pinkel->sis_jasa->deskripsi_sistem }}) sebagaimana
                        jadwal
                        angsuran terlampir yang tidak terpisahkan dari Surat Perjanjian Kredit (SPK).
                    </li>
                    @if (strlen(json_decode($redaksi_spk, true)) > 15)
                        {!! json_decode($redaksi_spk, true) !!}
                    @endif
                @endif
            </ol>
        </div>
    </div>

    @if (Session::get('lokasi') == '270' && $pinkel->sumber == '2')
        <div style="text-align: center;">
            <div>
                <b style="font-size: 14px;">PASAL 3</b>
            </div>
            <div>
                <b style="font-size: 14px;">HAK & KEWAJIBAN</b>
            </div>

            <div style="text-align: justify; font-size: 14px;">
                <ol style="text-align: justify;">
                    <li>
                        <div>PIHAK KEDUA berkewajiban:</div>
                        <ol style="list-style: lower-alpha; text-align: justify;">
                            <li>Membayar angsuran tepat waktu sesuai jadwal baru.</li>
                            <li>Memberikan informasi keuangan yang benar apabila diminta PIHAK PERTAMA.
                            </li>
                        </ol>
                    </li>
                    <li>
                        <div>PIHAK PERTAMA berhak:</div>
                        <ol style="list-style: lower-alpha; text-align: justify;">
                            <li>Menagih angsuran sesuai jadwal.</li>
                            <li>
                                Melakukan tindakan penagihan apabila terjadi tunggakan dengan cara dan
                                skema yang disepakati kedua pihak.
                            </li>
                        </ol>
                    </li>
                </ol>
            </div>
        </div>

        <div style="text-align: center;">
            <div>
                <b style="font-size: 14px;">PASAL 4</b>
            </div>
            <div>
                <b style="font-size: 14px;">LAIN-LAIN</b>
            </div>

            <div style="text-align: justify; font-size: 14px;">
                <ol style="text-align: justify;">
                    <li>
                        Jika PIHAK KEDUA kembali lalai dalam memenuhi kewajiban, maka PIHAK PERTAMA berhak membatalkan
                        kesepakatan rescheduling/Penjadwalan Ulang, selanjutnya mengikuti ketentuan penanganan pinjaman
                        bermasalah.
                    </li>
                    <li>
                        Hal yang belum diatur dalam SPK ini akan diatur kemudian dan disepakati kedua pihak.
                    </li>
                </ol>
            </div>
        </div>
    @endif

    <div style="text-align: center;">
        <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 14px;"class="p0">
            <tr>
                <td style="padding: 0px !important;">
                    <table class="p0" border="0" width="100%" cellspacing="0" cellpadding="0"
                        style="font-size: 14px;">
                        <tr>
                            <td style="padding: 0px !important;">
                                @if (Session::get('lokasi') == '270' && $pinkel->sumber == '2')
                                    <div style="text-align: center;">
                                        <div>
                                            <b style="font-size: 14px;">PASAL 5</b>
                                        </div>
                                        <div>
                                            <b style="font-size: 14px;">PENUTUP</b>
                                        </div>
                                    </div>

                                    <div style="text-align: justify; font-size: 14px;">
                                        <ol style="text-align: justify;">
                                            <li>
                                                PIHAK KEDUA menyatakan secara sadar dan sukarela telah menanda tangani
                                                akad atau perjanjian Resceduling/Penjadwalan Ulang ini, setelah terlebih
                                                dahulu membacakan isi perjanjian ini kepada para pemberi kuasa dengan
                                                sejelas-jelasnya dan tidak seorangpun diantaranya menyatakan keberatan,
                                                serta untuk menjadikan periksa bagi yang berwenang
                                            </li>
                                            <li>
                                                Perjanjian ini dibuat dalam bermaterai
                                                cukup, dan mempunyai kekuatan hukum yang sama.
                                            </li>
                                        </ol>
                                    </div>
                                @else
                                    <div style="text-align: center;">
                                        <b style="font-size: 14px; text-align: center;">PASAL 3</b>
                                    </div>

                                    <ol style="text-align: justify;">
                                        <li>
                                            Pihak kedua dan pemberi kuasa sadar dan mengerti bahwa mengembalikan kredit
                                            secara
                                            lancar sesuai
                                            jadwal yang disepakati, merupakan kewajiban hukum sekaligus menunjukkan budi
                                            pekerti
                                            luhur untuk
                                            mengembangkan semangat tolong menolong dengan saudaranya sesama warga desa lain.
                                            Pengembalian kredit secara lancar akan memperluas kesempatan untuk memperoleh
                                            kredit
                                            berikutnya
                                            serta membuka peluang bagi orang lain mendapatkan giliran pelayanan.
                                        </li>
                                        <li>
                                            Apabila terjadi saling selisih berkenaan dengan hak serta kewajiban yang timbul
                                            atas
                                            perjanjian
                                            utang-piutang ini, akan diselesaikan secara musyawarah untuk mencapai kata
                                            sepakat.
                                            Apabila tidak
                                            dapat dicapai kata sepakat, kedua belah pihak setuju untuk menunjuk Pengadilan
                                            Negeri {{ $nama_kab }}
                                            sebagai upaya hukum menyelesaikan persengketaan tersebut.
                                        </li>
                                        <li>
                                            Pihak kedua menyatakan secara sadar dan sukarela telah menanda tangani akad atau
                                            perjanjian kredit
                                            ini, setelah terlebih dahulu membacakan isi perjanjian ini kepada para pemberi
                                            kuasa
                                            dengan
                                            sejelas-jelasnya dan tidak seorangpun diantaranya menyatakan keberatan, serta
                                            untuk
                                            menjadikan
                                            periksa bagi yang berwenang.
                                        </li>
                                    </ol>
                                @endif
                            </td>
                        </tr>
                    </table>

                    @if ($tanda_tangan)
                        {!! $tanda_tangan !!}
                    @else
                        <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 14px;"
                            class="p">
                            <tr>
                                <td width="50%" align="center">Pihak Pertama</td>
                                <td width="25%" colspan="2" align="center">Pihak Kedua</td>
                            </tr>
                            <tr>
                                <td colspan="3" height="50"></td>
                            </tr>
                            <tr>
                                <td align="center">
                                    <b>{{ $dir->namadepan }} {{ $dir->namabelakang }}</b>
                                </td>
                                <td align="center">
                                    <b>{{ $ketua }}</b>
                                </td>
                                <td align="center">
                                    <b>{{ $sekretaris }}</b>
                                </td>
                            </tr>
                            <tr>
                                <td align="center">{{ $kec->sebutan_level_1 }}</td>
                                <td align="center">Ketua</td>
                                <td align="center">Sekretaris</td>
                            </tr>
                        </table>
                    @endif
                </td>
            </tr>
        </table>
    </div>
@endsection
