@php
    use App\Utils\Tanggal;
    use App\Utils\Pinjaman;

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
                    <b>{{ $title }}</b>
                </div>
                <div style="font-size: 16px;">
                    <b>PINJAMAN KELOMPOK {{ strtoupper($pinkel->jpp->nama_jpp) }}</b>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="3" height="5"></td>
        </tr>
    </table>
    <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px;">
        <tr>
            <td colspan="6">
                <b>IDENTITAS KELOMPOK :</b>
            </td>
        </tr>
        <tr>
            <td width="90">ID Kelompok</td>
            <td width="5" align="center">:</td>
            <td width="130">
                <b>{{ $pinkel->kelompok->kd_kelompok }}</b>
            </td>
            <td width="90">Tanggal Berdiri</td>
            <td width="5" align="center">:</td>
            <td width="130">
                <b>{{ Tanggal::tglLatin($pinkel->kelompok->tgl_berdiri) }}</b>
            </td>
        </tr>
        <tr>
            <td>Nama Kelompok </td>
            <td>:</td>
            <td>
                <b>{{ $pinkel->kelompok->nama_kelompok }}</b>
            </td>
            <td>Jenis Produk Piutang</td>
            <td>:</td>
            <td>
                <b>{{ $pinkel->jpp->nama_jpp }}</b>
            </td>
        </tr>
        <tr>
            <td>Alamat Kelompok</td>
            <td>:</td>
            <td>
                <b>{{ $pinkel->kelompok->alamat_kelompok }}</b>
            </td>
            <td>Jenis Usaha </td>
            <td>:</td>
            <td>
                <b>{{ $pinkel->kelompok->usaha->nama_usaha }}</b>
            </td>
        </tr>
        <tr>
            <td>
                {{ $pinkel->kelompok->d->sebutan_desa->sebutan_desa }}
            </td>
            <td>:</td>
            <td>
                <b>{{ $pinkel->kelompok->d->nama_desa }}</b>
            </td>
            <td>Jenis Kegiatan</td>
            <td>:</td>
            <td>
                <b>{{ $pinkel->kelompok->kegiatan->nama_jk }}</b>
            </td>
        </tr>
        <tr>
            <td>Kecamatan</td>
            <td>:</td>
            <td>
                <b>{{ $kec->nama_kec }}</b>
            </td>
            <td>Tingkat Kelompok </td>
            <td>:</td>
            <td>
                <b>{{ $pinkel->kelompok->tk->nama_tk }}</b>
            </td>
        </tr>
        <tr>
            <td>Telpon</td>
            <td>:</td>
            <td>
                <b>{{ $pinkel->kelompok->telpon }}</b>
            </td>
            <td>Fungsi Kelompok </td>
            <td>:</td>
            <td>
                <b>{{ $pinkel->kelompok->fk->nama_fk }}</b>
            </td>
        </tr>
        <tr>
            <td>Nama Ketua</td>
            <td>:</td>
            <td>
                <b>{{ $ketua }}</b>
            </td>
            <td>Last Update</td>
            <td>:</td>
            <td>
                <b>{{ Tanggal::tglLatin(date('Y-m-d', strtotime($pinkel->lu))) }}</b>
            </td>
        </tr>
        <tr>
            <td>Nama Sekretaris</td>
            <td>:</td>
            <td>
                <b>{{ $sekretaris }}</b>
            </td>
            <td>Petugas/PJ</td>
            <td>:</td>
            <td>
                <b>{{ $pinkel->user->namadepan }} {{ $pinkel->user->namabelakang }}</b>
            </td>
        </tr>
        <tr>
            <td colspan="6">&nbsp;</td>
        </tr>
    </table>

    <div>
        <b>DATA PIUTANG KELOMPOK :</b>
    </div>
    <table class="gp" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px;">
        <tr style="background: rgb(232,232,232)">
            <th width="10" height="15" align="center">ID.Reg. #{{ $pinkel->id }}</th>
            <th width="30" align="center">Tanggal</th>
            <th width="30" align="center">Alokasi</th>
            <th width="30" align="center">Jasa</th>
            <th width="30" align="center">Jangka</th>
            <th width="30" align="center">Sistem</th>

        </tr>
        <tr>
            <td align="center"><b>Data Proposal</b></td>
            <td>{{ Tanggal::tglLatin($pinkel->tgl_proposal) }}</td>
            <td align="right">{{ number_format($pinkel->proposal) }}</td>
            <td align="center">
                {{ number_format($pinkel->pros_jasa / $pinkel->jangka, 2) }}%/{{ $pinkel->jasa->nama_jj }}
            </td>
            <td align="center">{{ $pinkel->jangka }} bulan</td>
            <td align="center">{{ $pinkel->sis_pokok->nama_sistem }}</td>
        </tr>
        @if (!($pinkel->status == 'P' || $pinkel->status == 'V'))
            <tr>
                <td align="center">Data Verifikasi</td>
                <td>{{ Tanggal::tglLatin($pinkel->tgl_verifikasi) }}</td>
                <td align="right">{{ number_format($pinkel->verifikasi) }}</td>
                <td align="center">
                    {{ number_format($pinkel->pros_jasa / $pinkel->jangka, 2) }}%/{{ $pinkel->jasa->nama_jj }}
                </td>
                <td align="center">{{ $pinkel->jangka }} bulan</td>
                <td align="center">{{ $pinkel->sis_pokok->nama_sistem }}</td>
            </tr>
        @else
            <tr>
                <td align="center">Data Verifikasi</td>
                <td align="center">&nbsp;</td>
                <td align="right">&nbsp;</td>
                <td align="center">&nbsp;</td>
                <td align="center">&nbsp;</td>
                <td align="center">&nbsp;</td>
            </tr>
        @endif
        <tr>
            <td colspan="6" height="20">
                Catatan Verifikasi :
                <div>{{ $pinkel->catatan_verifikasi }}</div>
            </td>
        </tr>
    </table>

    <div style="margin-top: 12px;">
        <b>DATA PIUTANG ANGGOTA :</b>
    </div>
    <table class="gp" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px;">
        <tr>
            <th width="5%" align="center">No</th>
            <th width="20%" align="center">Nama Anggota</th>
            <th width="15%" align="center">Pinj. Lalu</th>
            <th width="15%" align="center">Proposal (Rp.)</th>
            <th width="15%" align="center">Rekom TV</th>
            <th width="15%" align="center">Rekom TP</th>
            <th align="center">Catatan</th>
        </tr>

        @php
            $proposal = 0;
            $proposal_lalu = 0;
            $verifikasi = 0;
            $alokasi = 0;
            $no = 0;
        @endphp
        @foreach ($pinkel->pinjaman_anggota as $pa)
            @php
                $proposal += $pa->proposal;
                $verifikasi += $pa->verifikasi;
                $alokasi += $pa->alokasi;

                $pinjaman_lalu = 0;
                if ($pa->pinj_ang) {
                    $proposal_lalu += $pa->pinj_ang->alokasi;
                    $pinjaman_lalu = $pa->pinj_ang->alokasi;
                }

                $no = $loop->iteration;
            @endphp
            <tr>
                <td align="center">{{ $no }}</td>
                <td>{{ $pa->anggota->namadepan }}</td>
                <td align="right">{{ number_format($pinjaman_lalu) }}</td>
                <td align="right">{{ number_format($pa->proposal) }}</td>
                <td align="right">
                    {!! $statusDokumen != 'P' || $pinkel->status == 'V' ? number_format($pa->verifikasi) : '&nbsp;' !!}
                </td>
                <td align="right">
                    {!! $statusDokumen == 'W' || $statusDokumen == 'A' ? number_format($pa->alokasi) : '&nbsp;' !!}
                </td>
                <td>
                    {!! !($statusDokumen == 'P' || $statusDokumen == 'L') || $pinkel->status == 'V'
                        ? $pa->catatan_verifikasi
                        : '&nbsp;' !!}
                </td>
            </tr>
        @endforeach

        <tr>
            <td align="center" colspan="2">
                <b>JUMLAH</b>
            </td>
            <td align="right">{{ number_format($proposal_lalu) }}</td>
            <td align="right">{{ number_format($proposal) }}</td>
            <td align="right">
                {!! $statusDokumen != 'P' || $pinkel->status == 'V' ? number_format($verifikasi) : '&nbsp;' !!}
            </td>
            <td align="right">
                {!! $statusDokumen == 'W' || $statusDokumen == 'A' ? number_format($alokasi) : '&nbsp;' !!}
            </td>
            <td align="right">&nbsp;</td>
        </tr>
    </table>

    <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px;">
        <tr>
            <td style="padding: 0px !important;">
                <table class="p" border="0" width="100%" cellspacing="0" cellpadding="0"
                    style="font-size: 11px;">
                    <tr>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td width="50%" align="justify" style="vertical-align: text-top;">
                            @if ($jenis_dokumen == 'dokumen_verifikasi')
                                <div>Verified Sign:</div>
                                <div>
                                    {{ $kec->nama_tv_sort }} {{ $kec->nama_lembaga_sort }} {{ $kec->sebutan_kec }}
                                    {{ $kec->nama_kec }}
                                    menyatakan dengan sebenar-benarnya sesuai
                                    dengan hasil survey lapangan bahwa kelompok dengan identitas tersebut di atas
                                    <b>ADA/TIDAK
                                        ADA</b>
                                    keberadaannya dan dapat dipertanggungjawabkan sesuai dengan peraturan yang berlaku.
                                    Serta
                                    <b>LAYAK/TIDAK
                                        LAYAK</b> untuk diberikan piutang sesuai dengan hasil rekomendasi Verifikasi di
                                    atas.
                                    Form ini
                                    digunakan sebagai dasar Verified pada SI DBM.
                                </div>
                            @else
                                <div>Catatan Keputusan Pendanaan:</div>
                                <div>
                                    Tim Pendanaan {{ $kec->nama_lembaga_sort }} {{ $kec->sebutan_kec }}
                                    {{ $kec->nama_kec }} menyatakan bahwa berdasarkan hasil survei lapangan, kelompok
                                    dengan
                                    identitas tersebut di atas <b>ADA/TIDAK ADA</b> keberadaannya dan dapat
                                    dipertanggungjawabkan
                                    sesuai dengan peraturan yang berlaku. Berdasarkan hasil Pendanaan dan rekomendasi yang
                                    telah
                                    dilakukan, kelompok tersebut dinyatakan <b>LAYAK/TIDAK LAYAK</b> untuk diberikan
                                    pendanaan/piutang. Pernyataan ini digunakan sebagai dasar pendanaan pada SI DBM.
                                </div>
                            @endif

                        </td>
                        <td width="50%" align="justify" style="vertical-align: top;">
                            <div>
                                @if ($jenis_dokumen == 'dokumen_verifikasi')
                                    Diverifikasi oleh, {{ $kec->nama_tv_sort }} {{ $kec->nama_lembaga_sort }}
                                    {{ $kec->sebutan_kec }}
                                    {{ $kec->nama_kec }}
                                @else
                                    Disetujui oleh, Tim Pendanaan {{ $kec->nama_lembaga_sort }}
                                    {{ $kec->sebutan_kec }}
                                    {{ $kec->nama_kec }}
                                @endif
                            </div>
                            <div style="margin-top: 12px;">
                                <table border="0" width="100%" cellspacing="0" cellpadding="0"
                                    style="font-size: 11px;">
                                    @foreach ($user as $u)
                                        <tr>
                                            <td width="70" height="20">
                                                <div>{{ $u->namadepan }} {{ $u->namabelakang }}</div>
                                                <div>
                                                    @if ($u->jabatan == '1' && $u->level == '4')
                                                        Ketua
                                                    @else
                                                        <b>{{ $u->j->nama_jabatan }}</b>
                                                    @endif
                                                </div>
                                            </td>
                                            <td align="right" style="vertical-align: bottom;">
                                                _____________________________________
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>
                        </td>
                    </tr>
                </table>

                @if ($tanda_tangan)
                    @php
                        $tanda_tangan_anggota = Pinjaman::keyword(json_encode($tanda_tangan), [
                            'kec' => $kec,
                            'jenis_laporan' => $jenis_laporan,
                            'tgl_kondisi' => $tgl_kondisi,
                            'pinkel' => $pinkel,
                            'pinjaman_anggota' => $pa,
                        ]);
                    @endphp
                    <div style="font-size: 14px;">
                        {!! $tanda_tangan_anggota !!}
                    </div>
                @else
                    @if ($kec->kd_kab == '33.13')
                        <table class="p" border="0" width="100%" cellspacing="0" cellpadding="0"
                            style="font-size: 11px;">
                            <tr>
                                <td>&nbsp;</td>
                            </tr>
                            <tr>
                                <td align="center">
                                    <div>Mengetahui</div>
                                    <div>{{ $pinkel->kelompok->d->sebutan_desa->sebutan_kades }}
                                        {{ $pinkel->kelompok->d->nama_desa }}</div>
                                </td>
                            </tr>
                            <tr>
                                <td height="30">&nbsp;</td>
                            </tr>
                            <tr>
                                <td align="center">
                                    {{ $pinkel->kelompok->d->kades }}
                                </td>
                            </tr>
                        </table>
                    @endif
                @endif
            </td>
        </tr>
    </table>
@endsection
