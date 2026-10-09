@php
    use App\Utils\Tanggal;

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
    <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 12px;">
        <tr>
            <td colspan="3" align="center">
                <div style="font-size: 18px;">
                    <b>DAFTAR CALON PEMANFAAT</b>
                </div>
                <div style="font-size: 16px; text-decoration: underline;">
                    <b>PIUTANG {{ $pinkel->jpp->nama_jpp }}</b>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="3" height="5"></td>
        </tr>
    </table>
    <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 12px;">
        <tr>
            <td width="70">Kelompok</td>
            <td width="5" align="right">:</td>
            <td>{{ $pinkel->kelompok->nama_kelompok }} - {{ $pinkel->id }}</td>
            <td width="70">Pengajuan</td>
            <td width="5" align="right">:</td>
            <td>Rp. {{ number_format($pinkel->proposal) }}</td>
        </tr>
        <tr>
            <td>Alamat</td>
            <td align="right">:</td>
            <td>{{ $pinkel->kelompok->alamat_kelompok }}</td>
            <td>Sistem Angs.</td>
            <td align="right">:</td>
            <td>{{ $pinkel->sis_pokok->nama_sistem }}</td>
        </tr>
        <tr>
            <td>Tgl. Proposal</td>
            <td align="right">:</td>
            <td>{{ Tanggal::tglLatin($pinkel->tgl_proposal) }}</td>
            <td>Pros. Jasa</td>
            <td align="right">:</td>
            <td>{{ $pinkel->pros_jasa }}% / {{ $pinkel->jangka }} {{ \App\Utils\Pinjaman::satuanJangka($pinkel, false) }}</td>
        </tr>
        <tr>
            <td>Piutang Ke-</td>
            <td align="right">:</td>
            <td>{{ str_pad($pinjaman_ke + 1, 2, '0', STR_PAD_LEFT) }}</td>
            <td colspan="3"></td>
        </tr>
    </table>

    <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 12px; table-layout: fixed;">
        <tr style="background: rgb(232, 232, 232)">
            <th class="t l b" height="20" width="5%">No</th>
            <th class="t l b" width="20%">Nik</th>
            <th class="t l b" width="15%">Nama Anggota</th>
            @if ($version == 'v1')
                <th class="t l b" width="5%">JK</th>
            @endif
            <th class="t l b" width="5%">Usia</th>
            @if ($version == 'v1')
                <th class="t l b" width="15%">Penjamin</th>
            @else
                <th class="t l b" width="20%">Jenis Usaha</th>
            @endif
            <th class="t l b" width="15%">Pengajuan</th>
            <th class="t l b r" width="10%">Ttd</th>
        </tr>

        @php
            $proposal = 0;
        @endphp
        @foreach ($pinkel->pinjaman_anggota as $pa)
            @php
                $tgl_lahir = new DateTime($pa->anggota->tgl_lahir);
                $today = new DateTime();

                $jarak = $today->diff($tgl_lahir);
                $usia = $jarak->y;
            @endphp
            <tr>
                <td class="t l b" height="15" align="center">{{ $loop->iteration }}</td>
                <td class="t l b">{{ $pa->anggota->nik }}</td>
                <td class="t l b">{{ $pa->anggota->namadepan }}</td>
                @if ($version == 'v1')
                    <td class="t l b" align="center">{{ $pa->anggota->jk }}</td>
                @endif
                <td class="t l b">{{ $usia }}</td>
                @if ($version == 'v1')
                    <td class="t l b">{{ $pa->anggota->penjamin }}</td>
                @else
                    <td class="t l b">{{ $pa->anggota->usaha }}</td>
                @endif
                <td class="t l b" align="right">{{ number_format($pa->proposal) }}</td>
                <td class="t l b r">&nbsp;</td>
            </tr>
            @php
                $proposal += $pa->proposal;
            @endphp
        @endforeach

        <tr style="font-weight: bold;">
            @if ($version == 'v1')
                <td class="t l b" height="15" align="center" colspan="6">JUMLAH</td>
            @else
                <td class="t l b" height="15" align="center" colspan="5">JUMLAH</td>
            @endif
            <td class="t l b" align="right">{{ number_format($proposal) }}</td>
            <td class="t l b r">&nbsp;</td>
        </tr>

        <tr>
            <td colspan="{{ $version == 'v1' ? 8 : 7 }}" style="padding: 0px !important;">
                @if ($tanda_tangan)
                    <div>
                        {!! $tanda_tangan !!}
                    </div>
                @else
                    <table class="p0" border="0" width="100%" cellspacing="0" cellpadding="0"
                        style="font-size: 12px;">
                        <tr>
                            <td colspan="3" height="10">&nbsp;</td>
                        </tr>
                        <tr>
                            <td align="center" width="{{ $pinkel->jenis_pp != '3' ? '33%' : '50%' }}">{{ $pinkel->jenis_pp != '3' ? 'Ketua' : 'Pimpinan' }}</td>
                            <td align="center" width="{{ $pinkel->jenis_pp != '3' ? '33%' : '50%' }}">{{ $pinkel->jenis_pp != '3' ? 'Sekretaris' : 'Penanggung Jawab' }}</td>
                            @if ($pinkel->jenis_pp != '3')
                                <td align="center" width="33%">Bendahara</td>
                            @endif
                        </tr>
                        <tr>
                            <td align="center" colspan="{{ $pinkel->jenis_pp != '3' ? '3' : '2' }}" height="30">&nbsp;</td>
                        </tr>
                        <tr style="font-weight: bold;">
                            <td align="center">{{ $ketua }}</td>
                            <td align="center">{{ $sekretaris }}</td>
                            @if ($pinkel->jenis_pp != '3')
                                <td align="center">{{ $bendahara }}</td>
                            @endif
                        </tr>
                    </table>
                @endif
            </td>
        </tr>
    </table>
@endsection
