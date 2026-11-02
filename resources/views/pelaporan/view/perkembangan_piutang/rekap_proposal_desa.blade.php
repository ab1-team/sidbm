@php
    use App\Utils\Tanggal;
    $section = 0;
    $empty = false;
@endphp

@extends('pelaporan.layout.base')

@section('content')
    @foreach ($jenis_pp as $jpp)
        @php
            if ($jpp->pinjaman_kelompok->isEmpty()) {
                $empty = true;
                continue;
            }

            $kd_desa = [];
            $t_proposal = 0;
            $t_alokasi = 0;
        @endphp

        @if ($jpp->nama_jpp != 'SPP' && !$empty)
            <div class="break"></div>
            @php
                $empty = false;
            @endphp
        @endif

        <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px;">
            <tr>
                <td colspan="3" align="center">
                    <div style="font-size: 18px;">
                        <b>
                            REKAP PROPOSAL PER DESA {{ $jpp->nama_jpp }}
                        </b>
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

        <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px; table-layout: fixed;">
            <thead>
                <tr style="background: rgb(230, 230, 230); font-weight: bold;">
                    <th class="t l b" width="5%" height="20">No</th>
                    <th class="t l b" width="25%">Nama Desa</th>
                    <th class="t l b" width="30%">Nama Kelompok</th>
                    <th class="t l b" width="15%">Jumlah</th>
                    <th class="t l b" width="10%">Tgl Proposal</th>
                    <th class="t l b r" width="15%">Alokasi</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($jpp->pinjaman_kelompok as $pinkel)
                    @php
                        $kd_desa[] = $pinkel->kd_desa;
                        $desa = $pinkel->kd_desa;
                    @endphp
                    @if (array_count_values($kd_desa)[$pinkel->kd_desa] <= '1')
                        @if ($section != $desa && count($kd_desa) > 1)
                            @php
                                $t_proposal += $j_proposal;
                                $t_alokasi += $j_alokasi;
                            @endphp
                            <tr style="font-weight: bold;">
                                <td class="t l b" align="left" colspan="3" height="20">
                                    Jumlah {{ $nama_desa }}
                                </td>
                                <td class="t l b" align="right">
                                    {{ number_format($j_proposal, 2) }}
                                </td>
                                <td class="t l b"></td>
                                <td class="t l b r" align="right">
                                    {{ number_format($j_alokasi, 2) }}
                                </td>
                            </tr>
                        @endif

                        <tr style="font-weight: bold;">
                            <td class="t l b r" colspan="6" align="left" height="15">
                                {{ $pinkel->kode_desa }}. {{ $pinkel->nama_desa }}
                            </td>
                        </tr>

                        @php
                            $nomor = 1;
                            $j_proposal = 0;
                            $j_alokasi = 0;
                            $section = $pinkel->kd_desa;
                            $nama_desa = $pinkel->sebutan_desa . ' ' . $pinkel->nama_desa;
                        @endphp
                    @endif

                    @php
                        $j_proposal += $pinkel->proposal;
                        $j_alokasi += $pinkel->alokasi;
                    @endphp

                    <tr>
                        <td class="t l b" align="center">{{ $nomor++ }}</td>
                        <td class="t l b" align="left">{{ $pinkel->sebutan_desa }} {{ $pinkel->nama_desa }}</td>
                        <td class="t l b" align="left">{{ $pinkel->nama_kelompok }}</td>
                        <td class="t l b" align="right">{{ number_format($pinkel->proposal, 2) }}</td>
                        <td class="t l b" align="center">
                            {{ $pinkel->tgl_proposal ? Tanggal::tglIndo($pinkel->tgl_proposal) : '-' }}
                        </td>
                        <td class="t l b r" align="right">{{ number_format($pinkel->alokasi, 2) }}</td>
                    </tr>
                @endforeach

                @if (count($kd_desa) > 0)
                    @php
                        $t_proposal += $j_proposal;
                        $t_alokasi += $j_alokasi;
                    @endphp
                    <tr style="font-weight: bold;">
                        <td class="t l b" align="left" colspan="3" height="20">
                            Jumlah {{ $nama_desa }}
                        </td>
                        <td class="t l b" align="right">
                            {{ number_format($j_proposal, 2) }}
                        </td>
                        <td class="t l b"></td>
                        <td class="t l b r" align="right">
                            {{ number_format($j_alokasi, 2) }}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6" style="padding: 0px !important;">
                            <table class="p" border="0" width="100%" cellspacing="0" cellpadding="0"
                                style=" table-layout: fixed;">
                                <tr style="background: rgb(230, 230, 230); font-weight: bold;">
                                    <td class="t l b" align="center" width="60%" height="15">
                                        J U M L A H
                                    </td>
                                    <td class="t l b" width="20%" align="right">
                                        {{ number_format($t_proposal, 2) }}
                                    </td>
                                    <td class="t l b" width="5%">&nbsp;</td>
                                    <td class="t l b r" width="15%" align="right">
                                        {{ number_format($t_alokasi, 2) }}
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="4">
                                        <div style="margin-top: 16px;"></div>
                                        {!! $tanda_tangan !!}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endforeach
@endsection
