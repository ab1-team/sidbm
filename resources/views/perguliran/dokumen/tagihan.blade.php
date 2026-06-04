@php
    use App\Utils\Tanggal;

    $real_pokok = 0;
    $real_jasa = 0;
    $sum_pokok = 0;
    $sum_jasa = 0;
    $saldo_pokok = $pinkel->alokasi;
    $saldo_jasa = $pinkel->pros_jasa == 0 ? 0 : $pinkel->alokasi / $pinkel->pros_jasa;
    if ($real) {
        $real_pokok = $real->realisasi_pokok;
        $real_jasa = $real->realisasi_jasa;
        $sum_pokok = $real->sum_pokok;
        $sum_jasa = $real->sum_jasa;
        $saldo_pokok = $real->saldo_pokok;
        $saldo_jasa = $real->saldo_jasa;
    }

    $target_pokok = 0;
    $target_jasa = 0;
    if ($ra) {
        $target_pokok = $ra->target_pokok;
        $target_jasa = $ra->target_jasa;
    }

    $tunggakan_pokok = $target_pokok - $sum_pokok;
    if ($tunggakan_pokok < 0) {
        $tunggakan_pokok = 0;
    }
    $tunggakan_jasa = $target_jasa - $sum_jasa;
    if ($tunggakan_jasa < 0) {
        $tunggakan_jasa = 0;
    }
@endphp

@extends('perguliran.dokumen.layout.base')

@section('content')
    <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 12px;">
        <tr>
            <td width="50">Nomor</td>
            <td width="10" align="center">:</td>
            <td colspan="2">
                ______/DBM/{{ Tanggal::tglRomawi(date('Y-m-d')) }}
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
                <b>Surat Tagihan</b>
            </td>
        </tr>
        <tr>
            <td colspan="3">&nbsp;</td>
            <td align="left" width="140">
                <div>Kepada Yth.</div>
                <div>
                    Ketua dan Anggota Kelompok {{ $pinkel->kelompok->nama_kelompok }}
                </div>
                <div>Di</div>
                <div style=" text-align: center;">
                    {{ strtoupper($pinkel->kelompok->d->nama_desa) }}
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="4">&nbsp;</td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td colspan="3">
                <div>Dengan hormat,</div>
                <div style="text-align: justify;">
                    Mendasar kepada Surat Perjanjian Kredit ({{ $pinkel->jpp->nama_jpp }}) antara
                    {{ $pinkel->kelompok->nama_kelompok }} {{ $pinkel->kelompok->d->nama_desa }} dengan
                    {{ $kec->nama_lembaga_sort }} Tanggal {{ Tanggal::tglLatin($pinkel->tgl_cair) }} dengan rincian piutang
                    sebagai berikut ;
                </div>
                <table>
                    <tr>
                        <td width="10">1.</td>
                        <td width="140">Alokasi Piutang</td>
                        <td width="5">:</td>
                        <td>
                            <b>Rp. {{ number_format($pinkel->alokasi) }}</b>
                        </td>
                    </tr>
                    <tr>
                        <td>2.</td>
                        <td>Tanggal Pencairan</td>
                        <td>:</td>
                        <td>
                            <b>{{ Tanggal::tglLatin($pinkel->tgl_cair) }}</b>
                        </td>
                    </tr>
                    <tr>
                        <td>3.</td>
                        <td>Prosentase Jasa</td>
                        <td>:</td>
                        <td>
                            <b>
                                {{ number_format($pinkel->pros_jasa > 0 ? $pinkel->pros_jasa / $pinkel->jangka : 0, 2) }}%
                                per Bulan
                            </b>
                        </td>
                    </tr>
                    <tr>
                        <td>4.</td>
                        <td>Masa Angsuran</td>
                        <td>:</td>
                        <td>
                            <b>{{ $pinkel->jangka }} Bulan</b>
                        </td>
                    </tr>
                    <tr>
                        <td>5.</td>
                        <td>Sistem Angsuran</td>
                        <td>:</td>
                        <td>
                            <b>{{ $pinkel->sis_pokok->nama_sistem }}</b>
                        </td>
                    </tr>
                </table>

                <div style="text-align: justify;">
                    dan mendasar pada catatan pembukuan kami {{ $pinkel->kelompok->nama_kelompok }} sampai dengan
                    diterbitkannya Surat Tagihan ini masih tercatat memiliki tunggakan sebagai berikut ;
                </div>

                <table>
                    <tr>
                        <td width="10">1.</td>
                        <td width="140">Tunggakan Pokok</td>
                        <td width="5">:</td>
                        <td>
                            <b>Rp. {{ number_format($tunggakan_pokok) }}</b>
                        </td>
                    </tr>
                    <tr>
                        <td>2.</td>
                        <td>Tunggakan Jasa</td>
                        <td>:</td>
                        <td>
                            <b>Rp. {{ number_format($tunggakan_jasa) }}</b>
                        </td>
                    </tr>
                    <tr>
                        <td>3.</td>
                        <td><b>Total Tunggakan (Pokok+jasa)</b></td>
                        <td>:</td>
                        <td>
                            <b>Rp. {{ number_format($tunggakan_pokok + $tunggakan_jasa) }}</b>
                        </td>
                    </tr>
                    @if(session('lokasi') == 220)
                        @php
                            $denda = ceil(0.02 * ($tunggakan_pokok + $tunggakan_jasa));
                            $total_dengan_denda = $tunggakan_pokok + $tunggakan_jasa + $denda;
                        @endphp
                        <tr>
                            <td>4.</td>
                            <td>Denda (2% x Total Tunggakan)</td>
                            <td>:</td>
                            <td>
                                <b>Rp. {{ number_format($denda) }}</b>
                            </td>
                        </tr>
                        <tr>
                            <td>5.</td>
                            <td><b>Total (Tunggakan + Denda)</b></td>
                            <td>:</td>
                            <td>
                                <b>Rp. {{ number_format($total_dengan_denda) }}</b>
                            </td>
                        </tr>
                    @endif
                </table>

                <p style="text-align: justify;">
                    Demikian surat ini kami sampaikan, apabila terjadi perbedaan hasil perhitungan angsuran/ tunggakan
                    mohon untuk melakukan klarifikasi dengan {{ $kec->nama_lembaga_sort }} dan terima kasih untuk
                    segera melakukan pelunasan tunggakan piutangnya

                </p>
            </td>
        </tr>
    </table>
    <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 12px;">
        <tr>
            <td colspan="2" height="24">&nbsp;</td>
        </tr>
        <tr>
            <td width="50%">&nbsp;</td>
            <td width="50%" align="center">
                {{ $kec->nama_kec }}, {{ Tanggal::tglLatin(date('Y-m-d')) }}
            </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td align="center">{{ $kec->sebutan_level_1 }}</td>
        </tr>
        <tr>
            <td colspan="2" height="40">&nbsp;</td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td align="center">{{ $dir->namadepan }} {{ $dir->namabelakang }}</td>
        </tr>
    </table>
@endsection
