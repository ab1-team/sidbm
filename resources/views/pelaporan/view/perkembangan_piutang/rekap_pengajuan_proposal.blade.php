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
            $t_pengajuan = 0;
            $t_pengajuan_lalu = 0;
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
                            PENGAJUAN PROPOSAL {{ strtoupper($jpp->nama_jpp) }}
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
                    <th class="t l b" width="15%">Nama Anggota</th>
                    <th class="t l b" width="25%">Alamat</th>
                    <th class="t l b" width="10%">Tgl Proposal</th>
                    <th class="t l b" width="15%">Pengajuan</th>
                    <th class="t l b" width="15%">Pinjaman Lalu</th>
                    <th class="t l b r" width="15%">Verifikasi</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($jpp->pinjaman_kelompok as $pinkel)
                    @php
                        $kd_desa[] = $pinkel->kd_desa;
                        $desa = $pinkel->kd_desa;

                        $jenis_pinjaman = $pinkel->jenis_pp < 3 ? 'Kelompok' : '';
                    @endphp
                    @if (array_count_values($kd_desa)[$pinkel->kd_desa] <= '1')
                        @if ($section != $desa && count($kd_desa) > 1)
                            @php
                                $t_pengajuan += $j_pengajuan;
                                $t_pengajuan_lalu += $j_pengajuan_lalu;
                            @endphp
                            <tr style="font-weight: bold;">
                                <td class="t l b" align="left" colspan="4" height="20">
                                    Jumlah {{ $nama_desa }}
                                </td>
                                <td class="t l b" align="right">
                                    {{ number_format($j_pengajuan, 2) }}
                                </td>
                                <td class="t l b" align="right">
                                    {{ number_format($j_pengajuan_lalu, 2) }}
                                </td>
                                <td class="t l b r" align="right"></td>
                            </tr>
                        @endif

                        <tr style="font-weight: bold;">
                            <td class="t l b r" colspan="7" align="left" height="15">
                                {{ $pinkel->kode_desa }}. {{ $pinkel->nama_desa }}
                            </td>
                        </tr>

                        @php
                            $nomor = 1;
                            $j_pengajuan = 0;
                            $j_pengajuan_lalu = 0;
                            $section = $pinkel->kd_desa;
                            $nama_desa = $pinkel->sebutan_desa . ' ' . $pinkel->nama_desa;
                        @endphp
                    @endif

                    <tr>
                        <td class="t l b" align="center">{{ $nomor++ }}</td>
                        <td class="t l b r" align="left" colspan="6">
                            {{ $jenis_pinjaman }} {{ $pinkel->nama_kelompok }} - {{ $pinkel->id }}
                        </td>
                    </tr>

                    @php
                        $pengajuan = 0;
                        $pengajuan_lalu = 0;
                    @endphp
                    @foreach ($pinkel->pinjaman_anggota as $pinjaman_anggota)
                        @php
                            $pinjaman_lalu = 0;
                            foreach ($pinjaman_anggota->pinjaman_lain as $pinjaman_lain) {
                                if ($pinjaman_lalu > 0) {
                                    break;
                                }

                                if ($pinjaman_lain->id_pinkel != $pinkel->id) {
                                    $pinjaman_lalu += $pinjaman_lain->proposal;
                                }
                            }

                            $pengajuan += $pinjaman_anggota->proposal;
                            $pengajuan_lalu += $pinjaman_lalu;

                            // Susun alamat dengan null-safe operator (`?->`)
                            // agar baris tetap tampil meskipun relasi Desa
                            // (`d`) atau SebutanDesa kosong — kalau pakai
                            // `->` di PHP, akses terhadap null akan melempar
                            // exception dan SELURUH <tr> anggota terlewat.
                            $sebutan_desa = $pinjaman_anggota->anggota->d?->sebutan_desa?->sebutan_desa ?? '';
                            $nama_desa_agt = $pinjaman_anggota->anggota->d?->nama_desa ?? '';
                            $alamat_agt = $pinjaman_anggota->anggota->alamat ?? '';
                            $alamat_gabung = trim($sebutan_desa.' '.$nama_desa_agt.' '.$alamat_agt);
                        @endphp
                        <tr>
                            <td class="t l b" align="center">&nbsp;</td>
                            <td class="t l b" align="left">{{ $pinjaman_anggota->anggota->namadepan }}</td>
                            <td class="t l b" align="left">{{ $alamat_gabung }}</td>
                            <td class="t l b" align="center">
                                {{ $pinkel->tgl_proposal ? Tanggal::tglIndo($pinkel->tgl_proposal) : '-' }}
                            </td>
                            <td class="t l b" align="right">{{ number_format($pinjaman_anggota->proposal, 2) }}</td>
                            <td class="t l b" align="right">{{ number_format($pinjaman_lalu, 2) }}</td>
                            <td class="t l b r" align="right"></td>
                        </tr>
                    @endforeach

                    @php
                        $pengajuan_kelompok = $pengajuan ?: $pinkel->proposal;
                        $pengajuan_lalu_kelompok = $pengajuan_lalu;

                        $j_pengajuan += $pengajuan_kelompok;
                        $j_pengajuan_lalu += $pengajuan_lalu_kelompok;
                    @endphp

                    <tr>
                        <td class="t l b">&nbsp;</td>
                        <td class="t l b" align="left" colspan="3" height="15">
                            Jumlah {{ $jenis_pinjaman }} {{ $pinkel->nama_kelompok }} - {{ $pinkel->id }}
                        </td>
                        <td class="t l b" align="right">
                            {{ number_format($pengajuan_kelompok, 2) }}
                        </td>
                        <td class="t l b" align="right">
                            {{ number_format($pengajuan_lalu_kelompok, 2) }}
                        </td>
                        <td class="t l b r" align="right"></td>
                    </tr>
                @endforeach

                @if (count($kd_desa) > 0)
                    @php
                        $t_pengajuan += $j_pengajuan;
                        $t_pengajuan_lalu += $j_pengajuan_lalu;
                    @endphp
                    <tr style="font-weight: bold;">
                        <td class="t l b" align="left" colspan="4" height="20">
                            Jumlah {{ $nama_desa }}
                        </td>
                        <td class="t l b" align="right">
                            {{ number_format($j_pengajuan, 2) }}
                        </td>
                        <td class="t l b" align="right">
                            {{ number_format($j_pengajuan_lalu, 2) }}
                        </td>
                        <td class="t l b r" align="right"></td>
                    </tr>
                    <tr>
                        <td colspan="7" style="padding: 0px !important;">
                            <table class="p" border="0" width="100%" cellspacing="0" cellpadding="0"
                                style=" table-layout: fixed;">
                                <tr style="background: rgb(230, 230, 230); font-weight: bold;">
                                    <td class="t l b" align="center" width="55%" height="15">
                                        J U M L A H
                                    </td>
                                    <td class="t l b" width="15%" align="right">
                                        {{ number_format($t_pengajuan) }}
                                    </td>
                                    <td class="t l b" width="15%" align="right">
                                        {{ number_format($t_pengajuan_lalu) }}
                                    </td>
                                    <td class="t l b r" width="15%" align="right">
                                        &nbsp;
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
