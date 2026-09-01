@php
    use App\Utils\Tanggal;
    $a = $analisis;
    $detail = $a['kolek_detail']['detail'] ?? [];
    $tot = $a['kolek_detail']['total'] ?? [
        'alokasi' => 0, 'saldo' => 0,
        'kolek1' => 0, 'kolek2' => 0, 'kolek3' => 0, 'kolek4' => 0, 'kolek5' => 0,
    ];
@endphp

@extends('pelaporan.layout.base')

@section('content')
    <div style="page: landscape_page; page-break-before: always;">
    <table border="0" width="100%" cellspacing="0" cellpadding="2" style="font-size: 10px;">
        <tr>
            <td colspan="3" align="center" style="font-size: 13px;">
                <b>LAMPIRAN KOLEKTIBILITAS POJK</b>
            </td>
        </tr>
        <tr>
            <td colspan="3" align="center" style="font-size: 12px;">
                <b>{{ strtoupper($kec->nama_lembaga_long) }}</b>
            </td>
        </tr>
        <tr>
            <td colspan="3" align="center" style="font-size: 11px;">
                <b>{{ strtoupper($sub_judul) }}</b>
            </td>
        </tr>
        <tr>
            <td colspan="3" align="center" style="font-size: 10px;">
                (Klasifikasi 5 Kolektibilitas POJK: Lancar / DPK / Kurang Lancar / Diragukan / Macet)
            </td>
        </tr>
    </table>
    <br>

    @php
        $global_kolek1 = 0;
        $global_kolek2 = 0;
        $global_kolek3 = 0;
        $global_kolek4 = 0;
        $global_kolek5 = 0;
        $global_alokasi = 0;
        $global_saldo = 0;
    @endphp

    @forelse ($detail as $jpp)
        @php
            $desaList = $jpp['desa'] ?? [];
            $t = $jpp['tot'] ?? [
                'alokasi' => 0, 'saldo' => 0,
                'kolek1' => 0, 'kolek2' => 0, 'kolek3' => 0, 'kolek4' => 0, 'kolek5' => 0,
            ];
            $global_alokasi += $t['alokasi'];
            $global_saldo += $t['saldo'];
            $global_kolek1 += $t['kolek1'];
            $global_kolek2 += $t['kolek2'];
            $global_kolek3 += $t['kolek3'];
            $global_kolek4 += $t['kolek4'];
            $global_kolek5 += $t['kolek5'];
            $nomor = 1;
        @endphp

        <div style="font-size: 11px; font-weight: bold; background: rgb(232,232,232); padding: 4px;">
            JENIS PRODUK PINJAMAN: {{ strtoupper($jpp['nama_jpp'] ?? '-') }}
        </div>
        <table border="0" width="100%" cellspacing="0" cellpadding="3" style="font-size: 9px; border: 1px solid #000;">
            <thead>
                <tr style="background: rgb(245,245,245);">
                    <th class="t l b r" width="3%" align="center">No</th>
                    <th class="t l b r" width="6%" align="center">Loan ID</th>
                    <th class="t l b r" width="7%" align="center">Tgl Cair</th>
                    <th class="t l b r" width="16%" align="left">Kelompok / Desa</th>
                    <th class="t l b r" width="6%" align="right">Alokasi</th>
                    <th class="t l b r" width="6%" align="right">Saldo Pokok</th>
                    <th class="t l b r" width="4%" align="center">%</th>
                    <th class="t l b r" width="5%" align="right">Tunggakan Pokok</th>
                    <th class="t l b r" width="4%" align="center">Bln Tunggak</th>
                    <th class="t l b r" width="7%" align="center">Lancar</th>
                    <th class="t l b r" width="7%" align="center">DPK</th>
                    <th class="t l b r" width="7%" align="center">Kurang Lancar</th>
                    <th class="t l b r" width="7%" align="center">Diragukan</th>
                    <th class="t l b r" width="7%" align="center">Macet</th>
                    <th class="t l b r" width="6%" align="center">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($desaList as $desa)
                    {{-- header desa, gaya kolek kelompok DBM: "{kode}. {nama desa}" --}}
                    <tr style="font-weight: bold; background: rgb(245,245,245);">
                        <td class="t l b r" colspan="15" align="left">
                            {{ $desa['kode_desa'] }}. {{ $desa['sebutan_desa'] ?? 'Desa' }} {{ $desa['nama_desa'] }}
                        </td>
                    </tr>
                    @php
                        $nomor = 1;
                    @endphp
                    @foreach ($desa['rows'] as $row)
                        <tr>
                            <td class="t l b r" align="center">{{ $nomor++ }}</td>
                            <td class="t l b r" align="center">{{ $row['id'] }}</td>
                            <td class="t l b r" align="center">{{ Tanggal::tglLatin($row['tgl_cair']) }}</td>
                            <td class="t l b r">{{ $row['nama_kelompok'] }}</td>
                            <td class="t l b r" align="right">{{ number_format($row['alokasi'], 0, '.', ',') }}</td>
                            <td class="t l b r" align="right">{{ number_format($row['saldo_pokok'], 0, '.', ',') }}</td>
                            <td class="t l b r" align="center">{{ number_format(floor($row['pross'] * 100), 0) }}</td>
                            <td class="t l b r" align="right">{{ number_format($row['tunggakan_pokok'], 0, '.', ',') }}</td>
                            <td class="t l b r" align="center">{{ $row['bulan_tunggak'] }}</td>
                            <td class="t l b r" align="right" style="{{ $row['kategori'] == 1 ? 'background: rgb(212,237,218); font-weight: bold;' : '' }}">{{ $row['kategori'] == 1 ? number_format($row['saldo_pokok'], 0, '.', ',') : '-' }}</td>
                            <td class="t l b r" align="right" style="{{ $row['kategori'] == 2 ? 'background: rgb(255,243,205); font-weight: bold;' : '' }}">{{ $row['kategori'] == 2 ? number_format($row['saldo_pokok'], 0, '.', ',') : '-' }}</td>
                            <td class="t l b r" align="right" style="{{ $row['kategori'] == 3 ? 'background: rgb(255,224,192); font-weight: bold;' : '' }}">{{ $row['kategori'] == 3 ? number_format($row['saldo_pokok'], 0, '.', ',') : '-' }}</td>
                            <td class="t l b r" align="right" style="{{ $row['kategori'] == 4 ? 'background: rgb(248,215,218); font-weight: bold;' : '' }}">{{ $row['kategori'] == 4 ? number_format($row['saldo_pokok'], 0, '.', ',') : '-' }}</td>
                            <td class="t l b r" align="right" style="{{ $row['kategori'] == 5 ? 'background: rgb(220,53,69); color: #fff; font-weight: bold;' : '' }}">{{ $row['kategori'] == 5 ? number_format($row['saldo_pokok'], 0, '.', ',') : '-' }}</td>
                            <td class="t l b r" align="center">{{ $row['status_pinjaman'] }}</td>
                        </tr>
                    @endforeach
                    {{-- subtotal per desa --}}
                    <tr style="font-weight: bold; background: rgb(250,250,245);">
                        <td class="t l b r" colspan="4" align="right">Jumlah {{ $desa['sebutan_desa'] ?? 'Desa' }} {{ $desa['nama_desa'] }}</td>
                        <td class="t l b r" align="right">{{ number_format($desa['tot']['alokasi'] ?? 0, 0, '.', ',') }}</td>
                        <td class="t l b r" align="right">{{ number_format($desa['tot']['saldo'] ?? 0, 0, '.', ',') }}</td>
                        <td class="t l b r" align="center">-</td>
                        <td class="t l b r" align="right">-</td>
                        <td class="t l b r" align="center">-</td>
                        <td class="t l b r" align="right">{{ number_format($desa['tot']['kolek1'] ?? 0, 0, '.', ',') }}</td>
                        <td class="t l b r" align="right">{{ number_format($desa['tot']['kolek2'] ?? 0, 0, '.', ',') }}</td>
                        <td class="t l b r" align="right">{{ number_format($desa['tot']['kolek3'] ?? 0, 0, '.', ',') }}</td>
                        <td class="t l b r" align="right">{{ number_format($desa['tot']['kolek4'] ?? 0, 0, '.', ',') }}</td>
                        <td class="t l b r" align="right">{{ number_format($desa['tot']['kolek5'] ?? 0, 0, '.', ',') }}</td>
                        <td class="t l b r" align="center">-</td>
                    </tr>
                @endforeach
                {{-- total per JPP --}}
                <tr style="background: rgb(232,232,232); font-weight: bold;">
                    <td class="t l b r" colspan="4" align="right">JUMLAH {{ strtoupper($jpp['nama_jpp'] ?? '-') }}</td>
                    <td class="t l b r" align="right">{{ number_format($t['alokasi'], 0, '.', ',') }}</td>
                    <td class="t l b r" align="right">{{ number_format($t['saldo'], 0, '.', ',') }}</td>
                    <td class="t l b r" align="center">-</td>
                    <td class="t l b r" align="right">-</td>
                    <td class="t l b r" align="center">-</td>
                    <td class="t l b r" align="right">{{ number_format($t['kolek1'], 0, '.', ',') }}</td>
                    <td class="t l b r" align="right">{{ number_format($t['kolek2'], 0, '.', ',') }}</td>
                    <td class="t l b r" align="right">{{ number_format($t['kolek3'], 0, '.', ',') }}</td>
                    <td class="t l b r" align="right">{{ number_format($t['kolek4'], 0, '.', ',') }}</td>
                    <td class="t l b r" align="right">{{ number_format($t['kolek5'], 0, '.', ',') }}</td>
                    <td class="t l b r" align="center">-</td>
                </tr>
            </tbody>
        </table>
        <br>
    @empty
        <table border="0" width="100%" cellspacing="0" cellpadding="3" style="font-size: 10px; border: 1px solid #000;">
            <tr>
                <td class="t l b r" align="center" style="padding: 10px;">
                    Tidak ada data pinjaman aktif untuk diklasifikasikan.
                </td>
            </tr>
        </table>
    @endforelse

    @php
        $npl_total_pinjaman = $global_saldo > 0 ? (($global_kolek3 + $global_kolek4 + $global_kolek5) / $global_saldo) * 100 : 0;
        $ppap_wajib_minimum_total = ($global_kolek2 * 0.05) + ($global_kolek3 * 0.15) + ($global_kolek4 * 0.50) + ($global_kolek5 * 1.00);
    @endphp

    <div style="font-size: 11px; font-weight: bold; background: rgb(232,232,232); padding: 4px;">
        REKAPITULASI KOLEKTIBILITAS POJK (GABUNGAN SEMUA JENIS PINJAMAN)
    </div>
    <table border="0" width="100%" cellspacing="0" cellpadding="4" style="font-size: 10px; border: 1px solid #000;">
        <thead>
            <tr style="background: rgb(245,245,245); font-weight: bold;">
                <th class="t l b r" width="4%" align="center">No</th>
                <th class="t l b r" width="20%" align="left">Kolektibilitas POJK</th>
                <th class="t l b r" width="10%" align="center">Kriteria Tunggakan</th>
                <th class="t l b r" width="10%" align="center">% PPAP</th>
                <th class="t l b r" width="14%" align="right">Saldo Pokok (Rp)</th>
                <th class="t l b r" width="14%" align="right">PPAP Wajib (Rp)</th>
                <th class="t l b r" width="14%" align="right">% thd Total</th>
                <th class="t l b r" width="14%" align="center">Klasik Tingkat Kesehatan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="t l b r" align="center">1</td>
                <td class="t l b r">Lancar</td>
                <td class="t l b r" align="center">0 - 3 bulan</td>
                <td class="t l b r" align="center">0%</td>
                <td class="t l b r" align="right">{{ number_format($global_kolek1, 0, '.', ',') }}</td>
                <td class="t l b r" align="right">{{ number_format($global_kolek1 * 0, 0, '.', ',') }}</td>
                <td class="t l b r" align="right">{{ $global_saldo > 0 ? number_format(($global_kolek1 / $global_saldo) * 100, 2) : '0,00' }}%</td>
                <td class="t l b r" align="center" style="background: rgb(212,237,218); font-weight: bold;">Sehat</td>
            </tr>
            <tr>
                <td class="t l b r" align="center">2</td>
                <td class="t l b r">Dalam Perhatian Khusus (DPK)</td>
                <td class="t l b r" align="center">>3 - 6 bulan</td>
                <td class="t l b r" align="center">5%</td>
                <td class="t l b r" align="right">{{ number_format($global_kolek2, 0, '.', ',') }}</td>
                <td class="t l b r" align="right">{{ number_format($global_kolek2 * 0.05, 0, '.', ',') }}</td>
                <td class="t l b r" align="right">{{ $global_saldo > 0 ? number_format(($global_kolek2 / $global_saldo) * 100, 2) : '0,00' }}%</td>
                <td class="t l b r" align="center" style="background: rgb(255,243,205); font-weight: bold;">Perlu Perhatian</td>
            </tr>
            <tr>
                <td class="t l b r" align="center">3</td>
                <td class="t l b r">Kurang Lancar</td>
                <td class="t l b r" align="center">>6 - 9 bulan</td>
                <td class="t l b r" align="center">15%</td>
                <td class="t l b r" align="right">{{ number_format($global_kolek3, 0, '.', ',') }}</td>
                <td class="t l b r" align="right">{{ number_format($global_kolek3 * 0.15, 0, '.', ',') }}</td>
                <td class="t l b r" align="right">{{ $global_saldo > 0 ? number_format(($global_kolek3 / $global_saldo) * 100, 2) : '0,00' }}%</td>
                <td class="t l b r" align="center" style="background: rgb(255,224,192); font-weight: bold;">Kurang Sehat</td>
            </tr>
            <tr>
                <td class="t l b r" align="center">4</td>
                <td class="t l b r">Diragukan</td>
                <td class="t l b r" align="center">>9 - 12 bulan</td>
                <td class="t l b r" align="center">50%</td>
                <td class="t l b r" align="right">{{ number_format($global_kolek4, 0, '.', ',') }}</td>
                <td class="t l b r" align="right">{{ number_format($global_kolek4 * 0.50, 0, '.', ',') }}</td>
                <td class="t l b r" align="right">{{ $global_saldo > 0 ? number_format(($global_kolek4 / $global_saldo) * 100, 2) : '0,00' }}%</td>
                <td class="t l b r" align="center" style="background: rgb(248,215,218); font-weight: bold;">Tidak Sehat</td>
            </tr>
            <tr>
                <td class="t l b r" align="center">5</td>
                <td class="t l b r">Macet</td>
                <td class="t l b r" align="center">&gt; 12 bulan</td>
                <td class="t l b r" align="center">100%</td>
                <td class="t l b r" align="right" style="background: rgb(220,53,69); color: #fff; font-weight: bold;">{{ number_format($global_kolek5, 0, '.', ',') }}</td>
                <td class="t l b r" align="right" style="background: rgb(220,53,69); color: #fff; font-weight: bold;">{{ number_format($global_kolek5 * 1.00, 0, '.', ',') }}</td>
                <td class="t l b r" align="right" style="background: rgb(220,53,69); color: #fff; font-weight: bold;">{{ $global_saldo > 0 ? number_format(($global_kolek5 / $global_saldo) * 100, 2) : '0,00' }}%</td>
                <td class="t l b r" align="center" style="background: rgb(220,53,69); color: #fff; font-weight: bold;">Tidak Sehat</td>
            </tr>
            <tr style="background: rgb(232,232,232); font-weight: bold;">
                <td class="t l b r" colspan="4" align="right">TOTAL</td>
                <td class="t l b r" align="right">{{ number_format($global_saldo, 0, '.', ',') }}</td>
                <td class="t l b r" align="right">{{ number_format($ppap_wajib_minimum_total, 0, '.', ',') }}</td>
                <td class="t l b r" align="right">100,00%</td>
                <td class="t l b r" align="center">-</td>
            </tr>
        </tbody>
    </table>
    <br>

    <div style="font-size: 10px; padding: 6px; background: rgb(245,245,245); border: 1px solid #999;">
        <b>Keterangan Klasifikasi Tingkat Kesehatan per Kolektibilitas POJK:</b>
        <ul style="margin: 4px 0 0 16px; padding: 0;">
            <li><b style="color: #155724;">Sehat</b>: Saldo Lancar mendominasi (&gt; 95%), NPL Neto &lt;= 5%.</li>
            <li><b style="color: #856404;">Perlu Perhatian</b>: Saldo DPK muncul (&gt; 0%), NPL Neto masih &lt;= 5%.</li>
            <li><b style="color: #d39e00;">Kurang Sehat</b>: Saldo Kurang Lancar muncul (&gt; 0%), NPL Neto &gt; 5% s.d &lt; 25%.</li>
            <li><b style="color: #721c24;">Tidak Sehat</b>: Saldo Diragukan atau Macet signifikan, NPL Neto &gt;= 25%, atau trigger PK 5.</li>
        </ul>
        <div style="margin-top: 6px;">
            <b>NPL Neto (Kurang Lancar + Diragukan + Macet):</b>
            Rp {{ number_format($global_kolek3 + $global_kolek4 + $global_kolek5, 0, '.', ',') }}
            ({{ number_format($npl_total_pinjaman, 2) }}% dari Total Outstanding {{ number_format($global_saldo, 0, '.', ',') }})
            @if ($npl_total_pinjaman >= 25)
                <span style="color: #dc3545; font-weight: bold;">[TRIGGER PK 5]</span>
            @endif
        </div>
    </div>

    <br><br>
    <table border="0" width="100%" cellspacing="0" cellpadding="2" style="font-size: 10px;">
        <tr>
            <td width="60%"></td>
            <td width="40%" align="center">
                {{ $kec->nama_kec }}, {{ Tanggal::tglLatin($tgl_kondisi) }}<br>
                {{ $nama_lembaga }}<br><br><br><br><br>
                <strong><u>{{ $dir->namadepan ?? '' }} {{ $dir->namabelakang ?? '' }}</u></strong><br>
                <strong>
                    @if (!empty($dir) && isset($dir->jabatan))
                        {{ $dir->j->nama_jabatan ?? 'Direktur' }}
                    @else
                        Direktur
                    @endif
                </strong>
            </td>
        </tr>
    </table>
    </div>
@endsection
