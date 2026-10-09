@php
    use App\Utils\Tanggal;
    $jumlah_angsuran = 0;

    $alokasi = $pinkel->alokasi;
    $alokasi_jasa = ($alokasi * $pinkel->pros_jasa) / 100;
    $t_pokok = 0;
    $t_jasa = 0;
    $t_denda = 0;
@endphp

@extends('perguliran.dokumen.layout.base')

@section('content')
    <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px;">
        <tr>
            <td colspan="3" align="center">
                <div style="font-size: 18px;">
                    <b>REKENING KORAN</b>
                </div>
                <div style="font-size: 16px; text-decoration: underline;">
                    <b>KELOMPOK {{ strtoupper($pinkel->kelompok->nama_kelompok) }}</b>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="3" height="5"></td>
        </tr>
    </table>
    <table border="0" width="100%" align="center"cellspacing="0" cellpadding="0" style="font-size: 11px;">
        <tr>
            <td width="90">Loan ID.</td>
            <td width="5" align="center">:</td>
            <td>
                <b>{{ $pinkel->kelompok->nama_kelompok }} &ndash; {{ $pinkel->id }}</b>
            </td>
            <td width="90">Jangka waktu</td>
            <td width="5" align="center">:</td>
            <td>
                <b>{{ $pinkel->jangka }} {{ \App\Utils\Pinjaman::satuanJangka($pinkel) }}</b>
            </td>
        </tr>
        <tr>
            <td>No. SPK</td>
            <td align="center">:</td>
            <td>
                <b>{{ $pinkel->spk_no }}</b>
            </td>
            <td>Sistem Angsuran</td>
            <td align="center">:</td>
            <td>
                <b>{{ $pinkel->sis_pokok->nama_sistem }} {{ $pinkel->jangka / $pinkel->sis_pokok->sistem }} Kali</b>
            </td>
        </tr>
        <tr>
            <td>Tanggal Pencairan</td>
            <td align="center">:</td>
            <td>
                <b>{{ Tanggal::tglLatin($pinkel->tgl_cair) }}</b>
            </td>
            <td>Jenis Jasa</td>
            <td align="center">:</td>
            <td>
                <b>{{ $pinkel->jasa->nama_jj }}</b>
            </td>
        </tr>
        <tr>
            <td>Alokasi Piutang</td>
            <td align="center">:</td>
            <td>
                <b>Rp. {{ number_format($alokasi) }}</b>
            </td>
            <td>Prosentase Jasa</td>
            <td align="center">:</td>
            <td>
                <b>{{ round($pinkel->pros_jasa / $pinkel->jangka, 2) }}% per {{ \App\Utils\Pinjaman::satuanJangka($pinkel, false) }}</b>
            </td>
        </tr>
    </table>

    <table border="0" width="100%" align="center" cellspacing="0" cellpadding="0" style="font-size: 11px;">
        <tr style="background: rgb(233,233,233)">
            <th class="t l b" width="10" align="center" height="20">No</th>
            <th class="t l b" width="40" align="center">Tanggal</th>
            <th class="t l b" width="40" align="center">ID.Trx</th>
            <th class="t l b" align="center">Keterangan</th>
            <th class="t l b" width="50" align="center">Pencairan</th>
            <th class="t l b" width="50" align="center">Pokok</th>
            <th class="t l b" width="50" align="center">Jasa</th>
            <th class="t l b" width="50" align="center">Denda</th>
            <th class="t l b r" width="10" align="center">P</th>
        </tr>
        <tr>
            <th class="l b" colspan="4" align="center">Target pembayaran</th>
            <th class="l b" align="right">0</th>
            <th class="l b" align="right">0</th>
            <th class="l b" align="right">0</th>
            <th class="l b" align="right">0</th>
            <th class="l b r"></th>
        </tr>

        @foreach ($transaksi as $trx)
            @php
                $debit = substr($trx->rekening_debit, 0, 6);
                $kredit = substr($trx->rekening_kredit, 0, 6);
                $pokok = 0;
                $jasa = 0;
                $denda = 0;
                $pencairan = 0;

                if (
                    $trx->rekening_kredit == '1.1.03.01' or
                    $trx->rekening_kredit == '1.1.03.02' or
                    $trx->rekening_kredit == '1.1.03.03'
                ) {
                    $pokok = intval($trx->jumlah);
                } elseif (
                    $trx->rekening_kredit == '4.1.01.01' or
                    $trx->rekening_kredit == '4.1.01.02' or
                    $trx->rekening_kredit == '4.1.01.03' or
                    $trx->rekening_kredit == '1.1.03.04' or
                    $trx->rekening_kredit == '1.1.03.05' or
                    $trx->rekening_kredit == '1.1.03.06'
                ) {
                    $jasa = intval($trx->jumlah);
                } elseif (
                    $trx->rekening_kredit == '4.1.01.04' or
                    $trx->rekening_kredit == '4.1.01.05' or
                    $trx->rekening_kredit == '4.1.01.06'
                ) {
                    $denda = intval($trx->jumlah);
                } elseif ($kredit == '1.1.01') {
                    $pencairan = intval($trx->jumlah);
                }

                $inisial = '';
                if (isset($trx->user->ins)) {
                    $inisial = $trx->user->ins;
                }

                if ($debit != '1.1.03') {
                    $t_jasa += $jasa;
                }

                $t_pokok += $pokok;
                $t_denda += $denda;
            @endphp
            <tr>
                <td class="l r" align="center">{{ $loop->iteration }}.</td>
                <td class="l r" align="center">{{ Tanggal::tglIndo($trx->tgl_transaksi) }}</td>
                <td class="l r" align="center">{{ $trx->idt }}</td>
                <td class="l r">{{ $trx->keterangan_transaksi }}</td>
                <td class="l r" align="right">{{ number_format($pencairan) }}</td>
                <td class="l r" align="right">{{ number_format($pokok) }}</td>
                <td class="l r" align="right">{{ number_format($jasa) }}</td>
                <td class="l r" align="right">{{ number_format($denda) }}</td>
                <td class="l r" align="center">{{ $inisial }}</td>
            </tr>
        @endforeach

        <tr>
            <th class="l t" colspan="4" align="center">Jumlah Pembayaran</th>
            <th class="l t">{{ number_format($alokasi) }}</th>
            <th class="l t">{{ number_format($t_pokok) }}</th>
            <th class="l t">{{ number_format($t_jasa) }}</th>
            <th class="l t">{{ number_format($t_denda) }}</th>
            <th class="l t r"></th>
        </tr>
        <tr>
            <th class="t l b" colspan="4" align="center">Saldo</th>
            <th class="t l b">{{ number_format(0) }}</th>
            <th class="t l b">{{ number_format($alokasi - $t_pokok) }}</th>
            <th class="t l b">{{ number_format($alokasi_jasa - $t_jasa) }}</th>
            <th class="t l b">{{ number_format($t_denda) }}</th>
            <th class="t l b r"></th>
        </tr>
    </table>
@endsection
