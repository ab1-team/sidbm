@php
    use App\Utils\Tanggal;

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
                <div style="font-size: 18px; text-decoration: underline;">
                    <b>DAFTAR PENERIMAAN PREMI {{ strtoupper($kec->nama_asuransi_p) }}</b>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="3" height="5"></td>
        </tr>
    </table>

    <table border="0" width="100%" align="center"cellspacing="0" cellpadding="0" style="font-size: 11px;">
        <tr>
            <td width="20%">Kelompok</td>
            <td align="center" width="2%">:</td>
            <td width="28%">
                <b>{{ $pinkel->kelompok->nama_kelompok }} / {{ $pinkel->id }}</b>
            </td>
            <td width="20%">Tanggal Cair</td>
            <td align="center" width="2%">:</td>
            <td width="28%">
                <b>{{ Tanggal::tglLatin($pinkel->tgl_cair) }}</b>
            </td>
        </tr>
        <tr>
            <td>Alamat</td>
            <td align="center">:</td>
            <td>
                <b>{{ $pinkel->kelompok->alamat_kelompok }}</b>
            </td>
            <td>Alokasi Piutang</td>
            <td align="center">:</td>
            <td>
                <b>Rp. {{ number_format($pinkel->alokasi) }}</b>
            </td>
        </tr>
        <tr>
            <td>
                {{ $pinkel->kelompok->d->sebutan_desa->sebutan_desa }}
            </td>
            <td align="center">:</td>
            <td>
                <b>{{ $pinkel->kelompok->d->nama_desa }}</b>
            </td>
            <td>Alokasi Piutang</td>
            <td align="center">:</td>
            <td>
                <b>{{ $pinkel->sis_pokok->nama_sistem }} ({{ $pinkel->jangka }} {{ \App\Utils\Pinjaman::satuanJangka($pinkel) }})</b>
            </td>
        </tr>
        <tr>
            <td>Ketua</td>
            <td align="center">:</td>
            <td>
                <b>{{ $ketua }}</b>
            </td>
            <td>Sistem Bagi Hasil</td>
            <td align="center">:</td>
            <td>
                <b>{{ number_format($pinkel->pros_jasa / $pinkel->jangka, 2) }}%/{{ \App\Utils\Pinjaman::satuanJangka($pinkel) }}, {{ $pinkel->jasa->nama_jj }}</b>
            </td>
        </tr>
    </table>

    <table border="1" width="100%" align="center"cellspacing="0" cellpadding="0" style="font-size: 11px;">
        <tr>
            <th rowspan="2" width="4%">No</th>
            <th rowspan="2" width="16%">Nama Anggota</th>
            <th rowspan="2" width="20%">TTL</th>
            <th colspan="2" width="20%">Piutang</th>
            <th rowspan="2" width="10%">Jumlah</th>
            <th rowspan="2" width="10%">Premi ({{ $kec->besar_premi }}%)</th>
            <th rowspan="2" width="10%">Ket.</th>
            <th rowspan="2" width="10%">TTD</th>
        </tr>
        <tr>
            <td width="10%">Pokok</td>
            <td width="10%">Jasa</td>
        </tr>

        @php
            $t_jasa = 0;
            $t_pokok = 0;
            $t_asuransi = 0;
            $no = 1;
        @endphp
        @foreach ($pinkel->pinjaman_anggota as $pa)
            @php
                $tgl_lahir = new DateTime($pa->anggota->tgl_lahir);
                $tgl_cair = new DateTime($pa->tgl_cair);

                $jarak = $tgl_cair->diff($tgl_lahir);
                if ($jarak->y > $kec->usia_mak) {
                    continue;
                }

                $pokok = $pa->alokasi;
                $jasa = $pa->alokasi * ($pa->pros_jasa / 100);

                $asuransi = $pokok * ($kec->besar_premi / 100);
                if ($kec->pengaturan_asuransi == 2) {
                    $asuransi = ($pokok + $jasa) * ($kec->besar_premi / 100);
                }

                $t_jasa += $jasa;
                $t_pokok += $pokok;
                $t_asuransi += $asuransi;
            @endphp
            <tr>
                <td align="center">{{ $no++ }}</td>
                <td>{{ $pa->anggota->namadepan }}</td>
                <td>
                    {{ $pa->anggota->tempat_lahir }}, {{ Tanggal::tglLatin($pa->anggota->tgl_lahir) }}
                </td>
                <td align="right">{{ number_format($pokok) }}</td>
                <td align="right">{{ number_format($jasa) }}</td>
                <td align="right">{{ number_format($pokok + $jasa) }}</td>
                <td align="right">{{ number_format($asuransi) }}</td>
                <td></td>
                <td></td>
            </tr>
        @endforeach
        <tr>
            <th colspan="3" align="center">Total</th>
            <th align="right">{{ number_format($t_pokok) }}</th>
            <th align="right">{{ number_format($t_jasa) }}</th>
            <th align="right">{{ number_format($t_pokok + $t_jasa) }}</th>
            <th align="right">{{ number_format($t_asuransi) }}</th>
            <th></th>
            <th></th>
        </tr>
    </table>

    <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px;">
        <tr>
            <td colspan="2">&nbsp;</td>
        </tr>
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
                Ketua Kelompok {{ $pinkel->kelompok->nama_kelompok }}
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
@endsection
