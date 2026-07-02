@php
    use App\Utils\Tanggal;
    $jumlah_angsuran = 0;

    if (Request::get('status') == 'P') {
        $alokasi = $pinkel->proposal;
        $tanggal = 'Tanggal Proposal';
        $tgl = $pinkel->tgl_proposal;
    }

    if (Request::get('status') == 'V') {
        $alokasi = $pinkel->verifikasi;
        $tanggal = 'Tanggal Verifikasi';
        $tgl = $pinkel->tgl_verifikasi;
    }

    if (Request::get('status') == 'W') {
        $alokasi = $pinkel->alokasi;
        $tanggal = 'Tanggal Cair';
        $tgl = $pinkel->tgl_cair;
    }

    if (Request::get('status') == 'A') {
        $alokasi = $pinkel->alokasi;
        $tanggal = 'Tanggal Cair';
        $tgl = $pinkel->tgl_cair;
    }

    $pros_jasa = $pinkel->pros_jasa;
    // if (count($pinkel->pinjaman_anggota) >= 3 && Session::get('lokasi') == '522') {
    //     $pros_jasa_kelompok = $pinkel->pros_jasa / $pinkel->jangka + 0.2;
    //     $pros_jasa = $pros_jasa_kelompok * $pinkel->jangka;
    // }

    $saldo_pokok = $alokasi;
    $alokasi_pinjaman = $alokasi;
    // saldo_jasa awal = Σ wajib_jasa per-anggota (kalau ada anggota dg
    // pros_jasa sendiri), bukan alokasi × pinkel->pros_jasa polos — supaya
    // Σ saldo_jasa_awal == Σ wajib_jasa & saldo akhir == 0.
    $saldo_jasa = $saldo_pokok * ($pros_jasa / 100);
    if (isset($generate) && !empty($generate->rencana_angsuran_anggota) && count($pinkel->pinjaman_anggota) > 0) {
        $sum_jasa_total = 0;
        foreach ($generate->rencana_angsuran_anggota as $rap) {
            // $rap->jasa bisa array (dari preview) atau stdClass (dari JSON).
            // Cast ke array supaya array_sum() kompatibel keduanya.
            $sum_jasa_total += array_sum((array) ($rap->jasa ?? []));
        }
        if ($sum_jasa_total > 0) {
            $saldo_jasa = $sum_jasa_total;
        }
    }

    $sum_pokok = 0;
    $sum_jasa = 0;

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
                    <b>RENCANA ANGSURAN PIUTANG {{ strtoupper($pinkel->jpp->nama_jpp) }}</b>
                </div>
                <div style="font-size: 16px; text-decoration: underline;">
                    <b>
                        {{ $pinkel->jenis_pp != '3' ? 'KELOMPOK' : '' }}
                        {{ strtoupper($pinkel->kelompok->nama_kelompok) }}
                        {{ strtoupper($pinkel->kelompok->d->sebutan_desa->sebutan_desa) }}
                        {{ strtoupper($pinkel->kelompok->d->nama_desa) }}
                    </b>
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
                <b>{{ $pinkel->kelompok->nama_kelompok }} &mdash; {{ $pinkel->id }}</b>
            </td>
            <td width="90">Jangka waktu</td>
            <td width="5" align="center">:</td>
            <td>
                <b>{{ $pinkel->jangka }} Bulan</b>
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
                <b>{{ $pinkel->sis_pokok->nama_sistem }} {{ round($pinkel->jangka / $pinkel->sis_pokok->sistem) }} Kali</b>
            </td>
        </tr>
        <tr>
            <td>{{ $tanggal }}</td>
            <td align="center">:</td>
            <td>
                <b>{{ Tanggal::tglLatin($tgl) }}</b>
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
                <b>Rp. {{ number_format($alokasi_pinjaman) }}</b>
            </td>
            <td>Prosentase Jasa</td>
            <td align="center">:</td>
            <td>
                <b>{{ round($pinkel->pros_jasa / $pinkel->jangka, 2) }}% per bulan</b>
            </td>
        </tr>
        <tr>
            <td colspan="6">&nbsp;</td>
        </tr>
    </table>

    <table border="0" width="100%" align="center"cellspacing="0" cellpadding="0"
        style="font-size: 11px; table-layout: fixed;">
        <tr style="background: rgb(232, 232, 232)">
            <th class="l t b" height="20" width="5%" align="center">Ke</th>
            <th class="l t b" width="13%" align="center">Tanggal</th>
            <th class="l t b" width="13%" align="center">Pokok</th>
            <th class="l t b" width="13%" align="center">Jasa</th>
            <th class="l t b" width="15%" align="center">Jumlah</th>
            <th class="l t b" width="15%" align="center">Total Target</th>
            <th class="l t b" width="13%" align="center">Saldo Pokok</th>
            <th class="l t b r" width="13%" align="center">Saldo Jasa</th>
        </tr>
        @php
            // Override Σ per-anggota: kalau pinkel punya anggota dengan
            // pros_jasa sendiri (mis. lokasi 522), pakai Σ wajib_pokok/jasa
            // per-bulan dari rencana_angsuran_anggota, bukan agregat polos
            // dari $ra (DB / generate jadwal polos).
            $override_pa = (!empty($generate->rencana_angsuran_anggota) && count($pinkel->pinjaman_anggota) > 0);
            $sum_pa_p = [];
            $sum_pa_j = [];
            if ($override_pa) {
                foreach ($generate->rencana_angsuran_anggota as $rap) {
                    foreach ($rap->pokok as $k => $v) {
                        $sum_pa_p[$k] = ($sum_pa_p[$k] ?? 0) + $v;
                    }
                    foreach ($rap->jasa as $k => $v) {
                        $sum_pa_j[$k] = ($sum_pa_j[$k] ?? 0) + $v;
                    }
                }
            }
        @endphp
        @foreach ($rencana as $ra)
            @php
                if ($ra->angsuran_ke == 0) {
                    continue;
                }
                $ra_wajib_pokok = $override_pa ? ($sum_pa_p[$ra->angsuran_ke] ?? 0) : $ra->wajib_pokok;
                $ra_wajib_jasa = $override_pa ? ($sum_pa_j[$ra->angsuran_ke] ?? 0) : $ra->wajib_jasa;
                $wajib_angsur = $ra_wajib_pokok + $ra_wajib_jasa;
                $jumlah_angsuran += $wajib_angsur;
                $saldo_pokok -= $ra_wajib_pokok;
                $saldo_jasa -= $ra_wajib_jasa;

                if ($pinkel->jenis_jasa == '2') {
                    // jasa efektif: Σ per-bulan / Σ alokasi (proxy pros_jasa agregat
                    // terboboti) — supaya saldo_jasa konsisten dengan Σ wajib_jasa
                    // per-anggota yg ditampilkan di kolom Jasa.
                    $efektif_jasa_pct = $pros_jasa;
                    if ($override_pa && isset($sum_pa_j[$ra->angsuran_ke])) {
                        $total_pa_p = array_sum($sum_pa_p);
                        $total_pa_j = array_sum($sum_pa_j);
                        if ($total_pa_p > 0) {
                            $efektif_jasa_pct = ($total_pa_j / $total_pa_p) * 100;
                        }
                    }
                    $saldo_jasa = ($saldo_pokok * $efektif_jasa_pct) / 100;
                }

                $sum_pokok += $ra_wajib_pokok;
                $sum_jasa += $ra_wajib_jasa;

                $sa_pokok = $pinkel->sistem_angsuran;
                $sa_jasa = $pinkel->sa_jasa;

                $jangka = $pinkel->jangka;

                $b = '';
                if ($ra->angsuran_ke == $jangka) {
                    $b = 'b';
                }
            @endphp
            <tr>
                <td class="l {{ $b }}" align="center">{{ $ra->angsuran_ke }}</td>
                <td class="l {{ $b }}" align="center">{{ Tanggal::tglIndo($ra->jatuh_tempo) }}</td>
                <td class="l {{ $b }}" align="right">{{ number_format($ra_wajib_pokok) }}</td>
                <td class="l {{ $b }}" align="right">{{ number_format($ra_wajib_jasa) }}</td>
                <td class="l {{ $b }}" align="right">{{ number_format($wajib_angsur) }}</td>
                <td class="l {{ $b }}" align="right">{{ number_format($jumlah_angsuran) }}</td>
                <td class="l {{ $b }}" align="right">{{ number_format($saldo_pokok) }}</td>
                <td class="l {{ $b }} r" align="right">{{ number_format($saldo_jasa) }}</td>
            </tr>
        @endforeach

        <tr>
            <td colspan="8" style="padding: 0px !important;">
                <table class="p" border="0" width="100%" cellspacing="0" cellpadding="0"
                    style="font-size: 11px; table-layout: fixed;">
                    <tr style="font-weight: bold;">
                        <td class="l t b" width="18%" height="15" align="center" colspan="2">Jumlah</td>
                        <td class="l t b" width="13%" align="right">{{ number_format($sum_pokok) }}</td>
                        <td class="l t b" width="13%" align="right">
                            {{ number_format($sum_jasa) }}
                        </td>
                        <td class="l t b" width="15%" align="right">{{ number_format($jumlah_angsuran) }}</td>
                        <td class="l t b" width="15%" align="right">{{ number_format($jumlah_angsuran) }}</td>
                        <td class="l t b" width="13%" align="right">{{ number_format($saldo_pokok) }}</td>
                        <td class="l t b r" width="13%" align="right">{{ number_format($saldo_jasa) }}</td>
                    </tr>
                </table>

                @if ($tanda_tangan)
                    {!! $tanda_tangan !!}
                @else
                    <table class="p" border="0" width="100%" cellspacing="0" cellpadding="0"
                        style="font-size: 11px;">
                        <tr>
                            <td align="center" colspan="5">&nbsp;</td>
                            <td align="center" colspan="3">
                                {{ $kec->nama_kec }}, {{ Tanggal::tglLatin($tgl) }}
                            </td>
                        </tr>
                        <tr>
                            <td align="center" colspan="5">
                                {{ $kec->sebutan_level_1 }}
                            </td>
                            <td align="center" colspan="3">
                                {{ $pinkel->jenis_pp != '3' ? 'Ketua Kelompok' : 'Pimpinan' }}
                                {{ $pinkel->kelompok->nama_kelompok }}
                            </td>
                        </tr>
                        <tr>
                            <td align="center" colspan="8" height="40">&nbsp;</td>
                        </tr>
                        <tr>
                            <td align="center" colspan="5">
                                <b>{{ $dir->namadepan }} {{ $dir->namabelakang }}</b>
                            </td>
                            <td align="center" colspan="3">
                                <b>{{ $ketua }}</b>
                            </td>
                        </tr>
                    </table>
                @endif
            </td>
        </tr>
    </table>
@endsection
