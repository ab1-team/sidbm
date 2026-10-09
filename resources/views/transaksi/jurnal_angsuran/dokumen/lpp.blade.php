@php
    use App\Utils\Tanggal;
    $pokok = 0;
    $jasa = 0;
    $target_pokok = 0;
    $target_jasa = 0;

    $sum_pokok = 0;
    $sum_jasa = 0;

    $t_real_pokok = 0;
    $t_real_jasa = 0;
    $t_saldo = 0;
    $t_tunggakan_pokok = 0;
    $t_tunggakan_jasa = 0;

    $bulan_angsuran = [];
@endphp

@extends('transaksi.layout.base')

@section('content')
    <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px;">
        <tr>
            <td colspan="3" align="center">
                <div style="font-size: 18px;">
                    LAPORAN PERKEMBANGAN PINJAMAN PER KELOMPOK
                </div>
                <div style="font-size: 18px; font-weight: bolder; text-transform: uppercase;">
                    KELOMPOK {{ $pinkel->jpp->nama_jpp }} {{ $pinkel->kelompok->nama_kelompok }}
                    {{ $pinkel->kelompok->d->sebutan_desa->sebutan_desa }} {{ $pinkel->kelompok->d->nama_desa }}
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="3" height="5"></td>
        </tr>
    </table>
    <table border="0" width="90%" align="center" cellspacing="0" cellpadding="0" style="font-size: 11px;">
        <tr>
            <td width="30">&nbsp;</td>
            <td width="50">Kd. Kelompok</td>
            <td width="100">: &nbsp;
                <b>{{ $pinkel->kelompok->kd_kelompok }}</b>
            </td>
            <td width="50">Loan ID</td>
            <td width="100">: &nbsp;
                <b>{{ $pinkel->jpp->nama_jpp }}-{{ $pinkel->id }}</b>
            </td>
        </tr>
        <tr>
            <td width="30">&nbsp;</td>
            <td width="50">Nama Kelompok</td>
            <td width="100">: &nbsp;
                <b>{{ $pinkel->kelompok->nama_kelompok }}</b>
            </td>
            <td width="50">Nomor SPK</td>
            <td width="100">: &nbsp;
                <b>{{ $pinkel->spk_no }}</b>
            </td>
        </tr>
        <tr>
            <td width="30">&nbsp;</td>
            <td width="50">Alamat</td>
            <td width="100">: &nbsp;
                <b>{{ $pinkel->kelompok->alamat_kelompok }}</b>
            </td>
            <td width="50">Tanggal Cair</td>
            <td width="100">: &nbsp;
                <b>{{ Tanggal::tglLatin($pinkel->tgl_cair) }}</b>
            </td>
        </tr>
        <tr>
            <td width="30">&nbsp;</td>
            <td width="50">{{ $pinkel->kelompok->d->sebutan_desa->sebutan_desa }}</td>
            <td width="100">: &nbsp;
                <b>{{ $pinkel->kelompok->d->nama_desa }}</b>
            </td>
            <td width="50">Alokasi Pinjaman </td>
            <td width="100">: &nbsp;
                <b>{{ number_format($pinkel->alokasi) }}</b>
            </td>
        </tr>
        <tr>
            <td width="30">&nbsp;</td>
            <td width="50">Telpon/SMS </td>
            <td width="100">: &nbsp;
                <b>{{ $pinkel->kelompok->telpon }}</b>
            </td>
            <td width="50">Prosentase, Jenis Jasa</td>
            <td width="100">: &nbsp;
                <b>{{ $pinkel->pros_jasa > 0 ? $pinkel->pros_jasa / $pinkel->jangka : '0' }}% / {{ \App\Utils\Pinjaman::satuanJangka($pinkel) }},
                    {{ $pinkel->jasa->nama_jj }}</b>
            </td>
        </tr>
        <tr>
            <td width="30">&nbsp;</td>
            <td width="50">Nama Ketua</td>
            <td width="100">: &nbsp;
                <b>{{ $pinkel->kelompok->ketua }}</b>
            </td>
            <td width="50">Sistem Angsuran</td>
            <td width="100">: &nbsp;
                <b>{{ $pinkel->sis_pokok->nama_sistem }} ({{ $pinkel->jangka }} {{ \App\Utils\Pinjaman::satuanJangka($pinkel) }})</b>
            </td>
        </tr>
        <tr>
            <td width="30">&nbsp;</td>
            <td width="50">Nama Sekretaris </td>
            <td width="100">: &nbsp;
                <b>{{ $pinkel->kelompok->sekretaris }}</b>
            </td>
            <td width="50">Jumlah Angsuran </td>
            <td width="100">: &nbsp;
                <b>
                    Rp. {{ number_format($pinkel->target->wajib_pokok) }} x
                    {{ $pinkel->jangka / $pinkel->sis_pokok->sistem }}
                </b>
            </td>
        </tr>
    </table>
    <table border="0" width="98%" align="center" cellspacing="0" cellpadding="0" style="font-size: 11px;">
        <tr style="font-weight: bold;">
            <td class=" l t b" rowspan="2" width="10" align="center">KE</td>
            <td class=" l t b" rowspan="2" width="150" align="center">BULAN</td>
            <td class=" l t b" colspan="2" width="100" align="center">TARGET</td>
            <td class=" l t b" colspan="2" width="100" align="center">REALISASI</td>
            <td class=" l t b" rowspan="2" width="100" align="center">SALDO PINJAMAN</td>
            <td class=" l t r b" colspan="2" width="100" align="center">TUNGGAKAN</td>
        </tr>
        <tr style="font-weight: bold;">
            <td class=" l b" align="center">POKOK</td>
            <td class=" l b" align="center">JASA</td>
            <td class=" l b" align="center">POKOK</td>
            <td class=" l b" align="center">JASA</td>
            <td class=" l b" align="center">POKOK</td>
            <td class=" l r b" align="center">JASA</td>
        </tr>
        @foreach ($rencana as $ra)
            @php
                $bulan_jatuh_tempo = date('Y-m', strtotime($ra->jatuh_tempo));
                if (in_array($bulan_jatuh_tempo, $bulan_angsuran)) {
                    continue;
                } else {
                    $bulan_angsuran[] = $bulan_jatuh_tempo;
                }

                $sum_target_pokok = 0;
                $sum_target_jasa = 0;
                foreach ($ra->ra as $sub_ra) {
                    if ($bulan_jatuh_tempo == date('Y-m', strtotime($sub_ra->jatuh_tempo))) {
                        $sum_target_pokok = $sub_ra->target_pokok;
                        $sum_target_jasa = $sub_ra->target_jasa;
                    }
                }

                $nunggak_pokok = 0;
                $nunggak_jasa = 0;

                $warna = '';
                $real_pokok = 0;
                $real_jasa = 0;
                $bulan_ini = date('Y-m-t', strtotime($ra->jatuh_tempo));
                if ($bulan_ini <= $bulan) {
                    $target_pokok = $ra->target_pokok;
                    $target_jasa = $ra->target_jasa;

                    foreach ($ra->real as $real) {
                        if ($pinkel->jangka == $ra->angsuran_ke && $real->tgl_transaksi >= $ra->jatuh_tempo) {
                            $real_pokok += $real->realisasi_pokok;
                            $real_jasa += $real->realisasi_jasa;
                            $sum_pokok += $real->realisasi_pokok;
                            $sum_jasa += $real->realisasi_jasa;
                        } else {
                            if (
                                Tanggal::bulan($real->tgl_transaksi) == Tanggal::bulan($ra->jatuh_tempo) &&
                                Tanggal::tahun($real->tgl_transaksi) == Tanggal::tahun($ra->jatuh_tempo)
                            ) {
                                $real_pokok += $real->realisasi_pokok;
                                $real_jasa += $real->realisasi_jasa;
                                $sum_pokok += $real->realisasi_pokok;
                                $sum_jasa += $real->realisasi_jasa;
                            }
                        }
                    }

                    $saldo = $pinkel->alokasi - $sum_pokok;
                    if ($saldo < 0) {
                        $saldo = 0;
                    }

                    $nunggak_pokok = $ra->target_pokok - $sum_pokok;
                    if ($nunggak_pokok < 0) {
                        $nunggak_pokok = 0;
                    }

                    $nunggak_jasa = $ra->target_jasa - $sum_jasa;
                    if ($nunggak_jasa < 0) {
                        $nunggak_jasa = 0;
                    }

                    $t_real_pokok = $sum_pokok;
                    $t_real_jasa = $sum_jasa;
                    $t_saldo = $saldo;
                    $t_tunggakan_pokok = $nunggak_pokok;
                    $t_tunggakan_jasa = $nunggak_jasa;
                }

                if ($nunggak_pokok > 0 && $nunggak_jasa > 0 && $bulan_ini < $bulan) {
                    $warna = 'red;';
                }
            @endphp
            <tr style="color: {{ $bulan_ini == $bulan ? 'green; font-weight: bold;' : '' }}">
                <td class="l" align="center">{{ $ra->angsuran_ke }}.</td>
                <td class="l" align="left">{{ Tanggal::tglLatin($ra->jatuh_tempo) }}</td>
                <td class="l" align="right">{{ number_format($sum_target_pokok) }}</td>
                <td class="l" align="right">{{ number_format($sum_target_jasa) }}</td>
                <td class="l" align="right">
                    {{ number_format($real_pokok) }}
                </td>
                <td class="l" align="right">
                    {{ number_format($real_jasa) }}
                </td>
                @if ($bulan_ini > $bulan)
                    <td class="l" align="right">
                        0
                    </td>
                    <td class="l" align="right">
                        0
                    </td>
                    <td class="l r" align="right">
                        0
                    </td>
                @else
                    <td class="l" align="right">
                        {{ number_format($saldo) }}
                    </td>
                    <td class="l" align="right" style="color: {{ $warna }};">
                        {{ number_format($nunggak_pokok) }}
                    </td>
                    <td class="l r" align="right" style="color: {{ $warna }};">
                        {{ number_format($nunggak_jasa) }}
                    </td>
                @endif
            </tr>
        @endforeach
        <tr style="font-weight: bold;">
            <td class="l t b" align="center" colspan="2" height="20">
                REKAPITULASI
            </td>
            <td class="t l b" align="right">{{ number_format($target_pokok) }}</td>
            <td class="t l b" align="right">{{ number_format($target_jasa) }}</td>
            <td class="t l b" align="right">{{ number_format($t_real_pokok) }}</td>
            <td class="t l b" align="right">{{ number_format($t_real_jasa) }}</td>
            <td class="t l b" align="right">{{ number_format($t_saldo) }}</td>
            <td class="t l b" align="right">{{ number_format($t_tunggakan_pokok) }}</td>
            <td class="t l b r" align="right">{{ number_format($t_tunggakan_jasa) }}</td>
        </tr>
    </table>
@endsection
