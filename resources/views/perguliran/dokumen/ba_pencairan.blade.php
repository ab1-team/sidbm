@php
    use App\Utils\Tanggal;
    $waktu = '';
    $tempat = '';
    $wt_cair = explode('_', $pinkel->wt_cair);
    if (count($wt_cair) == 1) {
        $waktu = $wt_cair[0];
    }

    if (count($wt_cair) == 2) {
        $waktu = $wt_cair[0];
        $tempat = $wt_cair[1];
    }

    $minus = 0;

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
                    <b>BERITA ACARA PENCAIRAN</b>
                </div>
                <div style="font-size: 16px;">
                    <b>PIUTANG KELOMPOK {{ $pinkel->jpp->nama_jpp }}</b>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="3" height="5"></td>
        </tr>
    </table>

    <div style="text-align: justify;">
        Pada hari ini {{ Tanggal::namaHari($pinkel->tgl_cair) }} tanggal
        {{ $keuangan->terbilang(Tanggal::hari($pinkel->tgl_cair)) }} bulan {{ Tanggal::namaBulan($pinkel->tgl_cair) }} tahun
        {{ $keuangan->terbilang(Tanggal::tahun($pinkel->tgl_cair)) }}, telah diadakan pencairan dana perguliran
        {{ $kec->nama_lembaga_sort }} {{ $kec->sebutan_kec }} {{ $kec->nama_kec }} kepada Kelompok
        {{ $pinkel->kelompok->nama_kelompok }} {{ $pinkel->kelompok->d->sebutan_desa->sebutan_desa }}
        {{ $pinkel->kelompok->d->nama_desa }} {{ $kec->sebutan_kec }} {{ $kec->nama_kec }} bertempat di
        {{ $tempat }},
        sebesar Rp.
        {{ number_format($pinkel->alokasi) }} ({{ $keuangan->terbilang($pinkel->alokasi) }} Rupiah), sesuai dengan
        Register Piutang pada Data Base Piutang Nomor nomor : {{ $pinkel->kelompok->kd_kelompok }} dan Surat Perjanjian
        Kredit (SPK) nomor: {{ $pinkel->spk_no }}.
    </div>

    <div style="text-align: justify;">
        Adapun rincian piutang dan data kelompok (Profil Kelompok) adalah sebagai berikut :
        <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px;">
            <tr>
                <td width="10" align="center">1.</td>
                <td width="100">{{ $pinkel->kelompok->d->sebutan_desa->sebutan_desa }}</td>
                <td width="10" align="center">:</td>
                <td>
                    <b>{{ $pinkel->kelompok->d->nama_desa }}</b>
                </td>

                <td width="10" align="center">9.</td>
                <td width="100">Tingkat Kelompok</td>
                <td width="10" align="center">:</td>
                <td>
                    <b>{{ $pinkel->kelompok->tk->nama_tk }}</b>
                </td>
            </tr>
            <tr>
                <td align="center">2.</td>
                <td>Nama Kelompok</td>
                <td align="center">:</td>
                <td>
                    <b>{{ $pinkel->kelompok->nama_kelompok }}</b>
                </td>

                <td align="center">10.</td>
                <td>Fungsi Kelompok</td>
                <td align="center">:</td>
                <td>
                    <b>{{ $pinkel->kelompok->fk->nama_fk }}</b>
                </td>
            </tr>
            <tr>
                <td align="center">3.</td>
                <td>Alamat Kelompok</td>
                <td align="center">:</td>
                <td>
                    <b>{{ $pinkel->kelompok->alamat_kelompok }}</b>
                </td>

                <td align="center">11.</td>
                <td>Nama Ketua</td>
                <td align="center">:</td>
                <td>
                    <b>{{ $ketua }}</b>
                </td>
            </tr>
            <tr>
                <td align="center">4.</td>
                <td>Tanggal Berdiri</td>
                <td align="center">:</td>
                <td>
                    <b>{{ Tanggal::tglLatin($pinkel->kelompok->tgl_berdiri) }}</b>
                </td>

                <td align="center">12.</td>
                <td>Nomor Kontak</td>
                <td align="center">:</td>
                <td>
                    <b>{{ $pinkel->kelompok->telpon }}</b>
                </td>
            </tr>
            <tr>
                <td align="center">5.</td>
                <td>Jumlah Pemanfaat</td>
                <td align="center">:</td>
                <td>
                    <b>{{ $pinkel->pinjaman_anggota_count }} Orang</b>
                </td>

                <td align="center">13.</td>
                <td>Tanggal Pencairan</td>
                <td align="center">:</td>
                <td>
                    <b>{{ Tanggal::tglLatin($pinkel->tgl_cair) }}</b>
                </td>
            </tr>
            <tr>
                <td align="center">6.</td>
                <td>Jenis Piutang</td>
                <td align="center">:</td>
                <td>
                    <b>{{ $pinkel->jpp->nama_jpp }} Orang</b>
                </td>

                <td align="center">14.</td>
                <td>Alokasi Piutang</td>
                <td align="center">:</td>
                <td>
                    <b>{{ number_format($pinkel->alokasi) }}</b>
                </td>
            </tr>
            <tr>
                <td align="center">7.</td>
                <td>Jenis Usaha</td>
                <td align="center">:</td>
                <td>
                    <b>{{ $pinkel->kelompok->usaha->nama_ju }}</b>
                </td>

                <td align="center">15.</td>
                <td>Jangka Sistem</td>
                <td align="center">:</td>
                <td>
                    <b>{{ $pinkel->jangka }} / {{ $pinkel->sis_pokok->nama_sistem }}</b>
                </td>
            </tr>
            <tr>
                <td align="center">8.</td>
                <td>Jenis Kegiatan</td>
                <td align="center">:</td>
                <td>
                    <b>{{ $pinkel->kelompok->kegiatan->nama_jk }}</b>
                </td>

                <td align="center">16.</td>
                <td>Prosentase Jasa</td>
                <td align="center">:</td>
                <td>
                    <b>{{ number_format($pinkel->pros_jasa / $pinkel->jangka, 2) }}%</b>
                </td>
            </tr>
        </table>
        Untuk bertindak mewakili Kelompok dalam perjanjian kredit dengan {{ $kec->nama_lembaga_sort }}
        {{ $kec->nama_kec }} sesuai
        dengan registrasi piutang nomor {{ $pinkel->kelompok->kd_kelompok }} dan data piutang sebagai berikut :

        <table class="gp" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px; table-layout: fixed;">
            <tr style="background: rgb(232, 232, 232)">
                <th width="3%" height="20">No</th>
                <th width="17%">Nik</th>
                <th width="20%">Nama Anggota</th>
                <th width="15%">Nomor HP</th>
                <th width="30%">Alamat</th>
                <th width="15%">Alokasi</th>
            </tr>

            @foreach ($pinkel->pinjaman_anggota as $pa)
                @php
                    if ($pa->alokasi == 0) {
                        $minus += 1;
                        continue;
                    }

                    $no = $loop->iteration - $minus;
                @endphp
                <tr>
                    <td align="center">{{ $no }}</td>
                    <td align="center">{{ $pa->anggota->nik }}</td>
                    <td>{{ $pa->anggota->namadepan }}</td>
                    <td align="center">{{ $pa->anggota->hp }}</td>
                    <td>{{ $pa->anggota->alamat }}</td>
                    <td align="right">{{ number_format($pa->alokasi) }}</td>
                </tr>
            @endforeach
        </table>

        <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px;">
            <tr>
                <td style="padding: 0px !important;">
                    <div>
                        Catatan Pencairan :
                    </div>

                    <p style="margin-top: 24px;">
                        Demikian, berita acara ini dibuat sekaligus sebagai bukti pencairan dana piutang di atas.
                    </p>

                    @if ($tanda_tangan)
                        {!! $tanda_tangan !!}
                    @else
                        <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px;">
                            <tr>
                                <td width="50%">&nbsp;</td>
                                <td width="25%">&nbsp;</td>
                                <td width="25%">&nbsp;</td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td align="center" colspan="2">
                                    {{ $kec->nama_kec }}, {{ Tanggal::tglLatin($pinkel->tgl_cair) }}
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" height="40">&nbsp;</td>
                            </tr>
                            <tr>
                                <td align="center" style="font-weight: bold;">
                                    {{ $dir->namadepan }} {{ $dir->namabelakang }}
                                </td>
                                <td colspan="2" align="center" style="font-weight: bold;">{{ $ketua }}
                                </td>
                            </tr>
                            <tr>
                                <td align="center">
                                    {{ $kec->sebutan_level_1 }}
                                </td>
                                <td colspan="2" align="center">Ketua Kelompok</td>
                            </tr>
                        </table>
                    @endif
                </td>
            </tr>
        </table>
    </div>
@endsection
