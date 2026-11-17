@php
    use App\Utils\Tanggal;
    $section = 0;
@endphp

@extends('pelaporan.layout.base')

@section('content')
    @foreach ($jenis_pp as $jpp)
        @php
            if ($jpp->pinjaman_kelompok->isEmpty()) {
                break;
            }
        @endphp

        @php
            $kd_desa = [];
            $nomor = 1;
            $t_alokasi = 0;
            $t_saldo = 0;
            $t_tunggakan_pokok = 0;
            $t_tunggakan_jasa = 0;
            $t_kolek1 = 0;
            $t_kolek2 = 0;
            $t_kolek3 = 0;
        @endphp
        @if ($jpp->nama_jpp != 'SPP')
            <div class="break"></div>
        @endif
        <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px;">
            <tr>
                <td colspan="3" align="center">
                    <div style="font-size: 18px;">
                        <b>Cadangan Penyisihan Penghapusan {{ $jpp->nama_jpp }}</b>
                    </div>
                    <div style="font-size: 16px;">
                        <b>{{ strtoupper($sub_judul) }}</b>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="3" height="5"></td>
            </tr>

        </table>

        @foreach ($jpp->pinjaman_kelompok as $pinkel)
            @php
                $kd_desa[] = $pinkel->kd_desa;
                $desa = $pinkel->kd_desa;

            @endphp
            @if (array_count_values($kd_desa)[$pinkel->kd_desa] <= '1')
                @if ($section != $desa && count($kd_desa) > 1)
                    @php
                        $j_pross = $j_saldo / $j_alokasi;
                        $t_alokasi += $j_alokasi;
                        $t_saldo += $j_saldo;
                        $t_tunggakan_pokok += $j_tunggakan_pokok;
                        $t_tunggakan_jasa += $j_tunggakan_jasa;
                        $t_kolek1 += $j_kolek1;
                        $t_kolek2 += $j_kolek2;
                        $t_kolek3 += $j_kolek3;
                    @endphp
                @endif

                @php
                    $j_alokasi = 0;
                    $j_saldo = 0;
                    $j_tunggakan_pokok = 0;
                    $j_tunggakan_jasa = 0;
                    $j_kolek1 = 0;
                    $j_kolek2 = 0;
                    $j_kolek3 = 0;
                    $section = $pinkel->kd_desa;
                    $nama_desa = $pinkel->sebutan_desa . ' ' . $pinkel->nama_desa;
                @endphp
            @endif

            @php
                $sum_pokok = 0;
                $sum_jasa = 0;
                $saldo_pokok = $pinkel->alokasi;
                $saldo_jasa = \App\Utils\Keuangan::totalJasaPinjaman($pinkel); // total jasa ikut METODE (anuitas menurun, bukan flat)
                if ($pinkel->saldo) {
                    $sum_pokok = $pinkel->saldo->sum_pokok;
                    $sum_jasa = $pinkel->saldo->sum_jasa;
                    $saldo_pokok = $pinkel->saldo->saldo_pokok;
                    $saldo_jasa = $pinkel->saldo->saldo_jasa;
                }

                $target_pokok = 0;
                $target_jasa = 0;
                $wajib_pokok = 0;
                $wajib_jasa = 0;
                $angsuran_ke = 0;
                if ($pinkel->target) {
                    $target_pokok = $pinkel->target->target_pokok;
                    $target_jasa = $pinkel->target->target_jasa;
                    $wajib_pokok = $pinkel->target->wajib_pokok;
                    $wajib_jasa = $pinkel->target->wajib_jasa;
                    $angsuran_ke = $pinkel->target->angsuran_ke;
                }

                $tunggakan_pokok = $target_pokok - $sum_pokok;
                if ($tunggakan_pokok < 0) {
                    $tunggakan_pokok = 0;
                }
                $tunggakan_jasa = $target_jasa - $sum_jasa;
                if ($tunggakan_jasa < 0) {
                    $tunggakan_jasa = 0;
                }

                $pross = $saldo_pokok == 0 ? 0 : $saldo_pokok / $pinkel->alokasi;

                if ($pinkel->tgl_lunas <= $tgl_kondisi && $pinkel->status == 'L') {
                    $tunggakan_pokok = 0;
                    $tunggakan_jasa = 0;
                    $saldo_pokok = 0;
                    $saldo_jasa = 0;
                } elseif ($pinkel->tgl_lunas <= $tgl_kondisi && $pinkel->status == 'R') {
                    $tunggakan_pokok = 0;
                    $tunggakan_jasa = 0;
                    $saldo_pokok = 0;
                    $saldo_jasa = 0;
                } elseif ($pinkel->tgl_lunas <= $tgl_kondisi && $pinkel->status == 'H') {
                    $tunggakan_pokok = 0;
                    $tunggakan_jasa = 0;
                    $saldo_pokok = 0;
                    $saldo_jasa = 0;
                }

                $tgl_akhir = new DateTime($tgl_kondisi);
                $tgl_awal = new DateTime($pinkel->tgl_cair);
                $selisih = $tgl_akhir->diff($tgl_awal);

                if ($lpp == 'Minggu') {
                    $selisih_hari = $selisih->days;
                    $selisih = floor($selisih_hari / 7);
                } else {
                    $selisih = $selisih->y * 12 + $selisih->m;
                }

                $_kolek = 0;
                if ($wajib_pokok != '0') {
                    $_kolek = $tunggakan_pokok / $wajib_pokok;
                }
                $kolek = round($_kolek + ($selisih - $angsuran_ke));
                if ($saldo_pokok == '0') {
                    $kolek = 0;
                }
                if ($kolek <= 3) {
                    $kolek1 = $saldo_pokok;
                    $kolek2 = 0;
                    $kolek3 = 0;
                } elseif ($kolek <= 5) {
                    $kolek1 = 0;
                    $kolek2 = $saldo_pokok;
                    $kolek3 = 0;
                } else {
                    $kolek1 = 0;
                    $kolek2 = 0;
                    $kolek3 = $saldo_pokok;
                }

                $j_alokasi += $pinkel->alokasi;
                $j_saldo += $saldo_pokok;
                $j_tunggakan_pokok += $tunggakan_pokok;
                $j_tunggakan_jasa += $tunggakan_jasa;
                $j_kolek1 += $kolek1;
                $j_kolek2 += $kolek2;
                $j_kolek3 += $kolek3;
            @endphp
        @endforeach

        @if (count($kd_desa) > 0)
            @php
                $j_pross = $j_saldo / $j_alokasi;
                $t_alokasi += $j_alokasi;
                $t_saldo += $j_saldo;
                $t_tunggakan_pokok += $j_tunggakan_pokok;
                $t_tunggakan_jasa += $j_tunggakan_jasa;
                $t_kolek1 += $j_kolek1;
                $t_kolek2 += $j_kolek2;
                $t_kolek3 += $j_kolek3;

                $t_pros = 0;
                if ($t_saldo) {
                    $t_pross = $t_saldo / $t_alokasi;
                }
            @endphp

            <table border="1" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px;">
                <tr>
                    <th height="20" width="10">No</th>
                    <th width="200">Tingkat Kolektibilitas</th>
                    <th width="30">%</th>
                    <th width="150">Saldo Pinjaman</th>
                    <th>Beban Penyisihan Penghapusan Pinjaman</th>
                    <th width="150">NPL</th>
                </tr>
                <tr>
                    <td align="center">a</td>
                    <td align="center">b</td>
                    <td align="center">c</td>
                    <td align="center">d</td>
                    <td align="center">e = c * d</td>
                    <td align="center">f = (2 + 3) / saldo</td>
                </tr>
                <tr>
                    <td align="center">1</td>
                    <td>Lancar</td>
                    <td align="center">0%</td>
                    <td align="right">{{ number_format($t_kolek1) }}</td>
                    <td align="right">{{ number_format(($t_kolek1 * 0) / 100) }}</td>
                    <td align="center" rowspan="4">
                        {{ round($t_kolek1 + $t_kolek2 + $t_kolek3 > 0 ? (($t_kolek2 + $t_kolek3) / ($t_kolek1 + $t_kolek2 + $t_kolek3)) * 100 : 0, 2) }}%
                    </td>
                </tr>
                <tr>
                    <td align="center">2</td>
                    <td>Diragukan</td>
                    <td align="center">50%</td>
                    <td align="right">{{ number_format($t_kolek2) }}</td>
                    <td align="right">{{ number_format(($t_kolek2 * 50) / 100) }}</td>
                </tr>
                <tr>
                    <td align="center">3</td>
                    <td>Macet</td>
                    <td align="center">100%</td>
                    <td align="right">{{ number_format($t_kolek3) }}</td>
                    <td align="right">{{ number_format(($t_kolek3 * 100) / 100) }}</td>
                </tr>
                <tr>
                    <th colspan="3" height="15">Total</th>
                    <th>{{ number_format($t_kolek1 + $t_kolek2 + $t_kolek3) }}</th>
                    <th>{{ number_format(($t_kolek1 * 0) / 100 + ($t_kolek2 * 50) / 100 + ($t_kolek3 * 100) / 100) }}</th>
                </tr>
            </table>

            <div style="margin-top: 16px;"></div>
            {!! $tanda_tangan !!}
        @endif
    @endforeach
@endsection
