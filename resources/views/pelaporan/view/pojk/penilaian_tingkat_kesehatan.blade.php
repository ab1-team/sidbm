@php
    use App\Utils\Tanggal;
    use App\Utils\Keuangan;
    $keuangan = new Keuangan();
    $a = $analisis;
@endphp

@extends('pelaporan.layout.base')

@section('content')
    <table border="0" width="100%" cellspacing="0" cellpadding="2" style="font-size: 10px;">
        <tr>
            <td colspan="3" align="center" style="font-size: 14px;">
                <b>LAPORAN PENILAIAN TINGKAT KESEHATAN POJK</b>
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
            <td colspan="3" align="center" style="font-size: 11px;">
                (Berdasarkan Parameter POJK tentang Penilaian Tingkat Kesehatan Lembaga)
            </td>
        </tr>
    </table>
    <br>

    <table border="0" width="100%" cellspacing="0" cellpadding="2" style="font-size: 10px;">
        <tr>
            <td width="3%" valign="top">1.</td>
            <td width="35%" valign="top">Nama Lembaga</td>
            <td width="62%" valign="top">: <b>{{ $kec->nama_lembaga_long }}</b></td>
        </tr>
        <tr>
            <td valign="top">2.</td>
            <td valign="top">Sandi Lembaga</td>
            <td valign="top">: {{ $kec->sandi_Lembaga }}</td>
        </tr>
        <tr>
            <td valign="top">3.</td>
            <td valign="top">Nomor & Tanggal Izin Usaha</td>
            <td valign="top">: {{ $kec->ijin_usaha ?? '-' }}</td>
        </tr>
        <tr>
            <td valign="top">4.</td>
            <td valign="top">Alamat</td>
            <td valign="top">: {{ $kec->alamat_kec }}, {{ $kec->nama_kec }}</td>
        </tr>
        <tr>
            <td valign="top">5.</td>
            <td valign="top">Tanggal Cetak</td>
            <td valign="top">: {{ $tanggal_kondisi }}</td>
        </tr>
    </table>
    <br>

    <div style="font-size: 11px; font-weight: bold; background: rgb(232,232,232); padding: 4px;">
        A. PARAMETER KEUANGAN (otomatis dihitung dari database per {{ $tgl_kondisi }})
    </div>
    <table border="0" width="100%" cellspacing="0" cellpadding="3" style="font-size: 10px; border: 1px solid #000;">
        <thead>
            <tr style="background: rgb(245,245,245);">
                <th class="t l b r" width="5%" align="center">No</th>
                <th class="t l b r" width="55%" align="left">Parameter</th>
                <th class="t l b r" width="40%" align="right">Nilai (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="t l b r" align="center">1</td>
                <td class="t l b r">Total Aset</td>
                <td class="t l b r" align="right">{{ number_format($a['total_aset'], 0, '.', ',') }}</td>
            </tr>
            <tr>
                <td class="t l b r" align="center">2</td>
                <td class="t l b r">Total Liabilitas</td>
                <td class="t l b r" align="right">{{ number_format($a['total_liabilitas'], 0, '.', ',') }}</td>
            </tr>
            <tr>
                <td class="t l b r" align="center">3</td>
                <td class="t l b r">Kas & Setara Kas (Akun 1.1.01 & 1.1.02)</td>
                <td class="t l b r" align="right">{{ number_format($a['kas_setara_kas'], 0, '.', ',') }}</td>
            </tr>
            <tr>
                <td class="t l b r" align="center">4</td>
                <td class="t l b r">Liabilitas Lancar (Akun 2.1.xx)</td>
                <td class="t l b r" align="right">{{ number_format($a['liabilitas_lancar'], 0, '.', ',') }}</td>
            </tr>
            <tr>
                <td class="t l b r" align="center">5</td>
                <td class="t l b r">Modal Disetor (Akun 3.1.01.xx)</td>
                <td class="t l b r" align="right">{{ number_format($a['modal_disetor'], 0, '.', ',') }}</td>
            </tr>
            <tr>
                <td class="t l b r" align="center">6</td>
                <td class="t l b r">Total Ekuitas (Akun 3.xx)</td>
                <td class="t l b r" align="right">{{ number_format($a['total_ekuitas'], 0, '.', ',') }}</td>
            </tr>
            <tr>
                <td class="t l b r" align="center">7</td>
                <td class="t l b r">Total Outstanding Pinjaman</td>
                <td class="t l b r" align="right">{{ number_format($a['outstanding_pinjaman'], 0, '.', ',') }}</td>
            </tr>
            <tr>
                <td class="t l b r" align="center">8</td>
                <td class="t l b r">Nilai Pinjaman Bermasalah (Kurang Lancar + Diragukan + Macet)</td>
                <td class="t l b r" align="right">{{ number_format($a['npl_neto'], 0, '.', ',') }}</td>
            </tr>
            <tr>
                <td class="t l b r" align="center">9</td>
                <td class="t l b r">Cadangan PPAP yang Dibentuk (Akun 1.1.14)</td>
                <td class="t l b r" align="right">{{ number_format($a['cadangan_ppap_terbentuk'], 0, '.', ',') }}</td>
            </tr>
        </tbody>
    </table>
    <br>

    <div style="font-size: 11px; font-weight: bold; background: rgb(232,232,232); padding: 4px;">
        B. ANALISIS RASIO KUANTITATIF & SKORING (Bobot SEOJK No. 21/SEOJK.06/2015)
    </div>
    <table border="0" width="100%" cellspacing="0" cellpadding="3" style="font-size: 9px; border: 1px solid #000;">
        <thead>
            <tr style="background: rgb(245,245,245);">
                <th class="t l b r" width="4%" align="center">No</th>
                <th class="t l b r" width="15%" align="left">Aspek</th>
                <th class="t l b r" width="18%" align="left">Rasio</th>
                <th class="t l b r" width="10%" align="center">Bobot</th>
                <th class="t l b r" width="13%" align="right">Hasil</th>
                <th class="t l b r" width="13%" align="center">Batas POJK</th>
                <th class="t l b r" width="10%" align="center">Skor (0-100)</th>
                <th class="t l b r" width="17%" align="center">Status</th>
            </tr>
        </thead>
        <tbody>
            @php
                $status_sol = $a['rasio_solvabilitas'] >= 110 ? 'Memenuhi' : 'Tidak Memenuhi';
                $status_ek = $a['rasio_ekuitas'] >= 75 ? 'Memenuhi' : 'Tidak Memenuhi';
                $status_npl = $a['rasio_npl_neto'] <= 5 ? 'Memenuhi' : 'Tidak Memenuhi';
                $status_lik = $a['rasio_likuiditas'] >= 4 ? 'Memenuhi' : 'Tidak Memenuhi';
                $status_ppap = $a['ppap_coverage'] >= 100 ? 'Memenuhi' : 'Tidak Memenuhi';
                $status_roa = $a['roa'] >= 0.5 ? 'Baik' : 'Perlu Perhatian';
            @endphp
            <tr>
                <td class="t l b r" align="center" rowspan="2">1</td>
                <td class="t l b r" rowspan="2">Permodalan & Solvabilitas</td>
                <td class="t l b r">Rasio Solvabilitas (Aset / Liabilitas)</td>
                <td class="t l b r" align="center">12,5%</td>
                <td class="t l b r" align="right"><b>{{ number_format($a['rasio_solvabilitas'], 2) }}%</b></td>
                <td class="t l b r" align="center">Min. 110%</td>
                <td class="t l b r" align="center" rowspan="2" style="vertical-align: middle;"><b>{{ number_format(($a['skor_permodalan']), 1) }}</b></td>
                <td class="t l b r" align="center">{{ $status_sol }}</td>
            </tr>
            <tr>
                <td class="t l b r">Rasio Ekuitas (Ekuitas / Modal Disetor)</td>
                <td class="t l b r" align="center">12,5%</td>
                <td class="t l b r" align="right"><b>{{ number_format($a['rasio_ekuitas'], 2) }}%</b></td>
                <td class="t l b r" align="center">Min. 75%</td>
                <td class="t l b r" align="center">{{ $status_ek }}</td>
            </tr>
            <tr>
                <td class="t l b r" align="center" rowspan="2">2</td>
                <td class="t l b r" rowspan="2">Kualitas Aset</td>
                <td class="t l b r">NPL/NPF Neto</td>
                <td class="t l b r" align="center">21%</td>
                <td class="t l b r" align="right"><b>{{ number_format($a['rasio_npl_neto'], 2) }}%</b></td>
                <td class="t l b r" align="center">Maks. 5%</td>
                <td class="t l b r" align="center" rowspan="2" style="vertical-align: middle;"><b>{{ number_format($a['skor_kualitas_aset'], 1) }}</b></td>
                <td class="t l b r" align="center">{{ $status_npl }}</td>
            </tr>
            <tr>
                <td class="t l b r">Coverage PPAP (Cadangan / PPAP Wajib)</td>
                <td class="t l b r" align="center">14%</td>
                <td class="t l b r" align="right"><b>{{ number_format($a['ppap_coverage'], 2) }}%</b></td>
                <td class="t l b r" align="center">Min. 100%</td>
                <td class="t l b r" align="center">{{ $status_ppap }}</td>
            </tr>
            <tr>
                <td class="t l b r" align="center">3</td>
                <td class="t l b r">Manajemen</td>
                <td class="t l b r">Penilaian Kualitatif (Risiko, Tata Kelola, Kepatuhan)</td>
                <td class="t l b r" align="center">20%</td>
                <td class="t l b r" align="right">Kualitatif</td>
                <td class="t l b r" align="center">Baik</td>
                <td class="t l b r" align="center">{{ number_format($a['skor_manajemen'], 1) }}</td>
                <td class="t l b r" align="center">Asumsi Normal</td>
            </tr>
            <tr>
                <td class="t l b r" align="center">4</td>
                <td class="t l b r">Rentabilitas</td>
                <td class="t l b r">ROA (Laba / Aset Produktif)</td>
                <td class="t l b r" align="center">10%</td>
                <td class="t l b r" align="right"><b>{{ number_format($a['roa'], 2) }}%</b></td>
                <td class="t l b r" align="center">Positif</td>
                <td class="t l b r" align="center">{{ number_format($a['skor_rentabilitas'], 1) }}</td>
                <td class="t l b r" align="center">{{ $status_roa }}</td>
            </tr>
            <tr>
                <td class="t l b r" align="center">5</td>
                <td class="t l b r">Likuiditas</td>
                <td class="t l b r">Kas & Setara Kas / Liabilitas Lancar</td>
                <td class="t l b r" align="center">10%</td>
                <td class="t l b r" align="right"><b>{{ number_format($a['rasio_likuiditas'], 2) }}%</b></td>
                <td class="t l b r" align="center">Min. 4%</td>
                <td class="t l b r" align="center">{{ number_format($a['skor_likuiditas'], 1) }}</td>
                <td class="t l b r" align="center">{{ $status_lik }}</td>
            </tr>
            <tr style="background: rgb(232,232,232); font-weight: bold;">
                <td class="t l b r" colspan="6" align="right">SKOR KOMPOSIT (Total Tertimbang)</td>
                <td class="t l b r" align="center"><b>{{ number_format($a['skor_komposit'], 2) }}</b></td>
                <td class="t l b r" align="center">-</td>
            </tr>
        </tbody>
    </table>
    <br>

    <div style="font-size: 11px; font-weight: bold; background: rgb(232,232,232); padding: 4px;">
        C. RINGKASAN PERINGKAT KOMPOSIT (PK) & STATUS PENGAWASAN
    </div>
    <table border="0" width="100%" cellspacing="0" cellpadding="4" style="font-size: 10px; border: 1px solid #000;">
        <tr>
            <td class="t l b r" width="5%" align="center"><b>PK</b></td>
            <td class="t l b r" width="20%" align="left"><b>Label</b></td>
            <td class="t l b r" width="20%" align="center"><b>Rentang Skor</b></td>
            <td class="t l b r" width="55%" align="center"><b>Keterangan</b></td>
        </tr>
        <tr>
            <td class="t l b r" align="center">PK 1</td>
            <td class="t l b r">Sangat Sehat</td>
            <td class="t l b r" align="center">81 - 100</td>
            <td class="t l b r">Lembaga dalam kondisi sangat sehat, seluruh rasio terpenuhi dengan margin kuat.</td>
        </tr>
        <tr>
            <td class="t l b r" align="center">PK 2</td>
            <td class="t l b r">Sehat</td>
            <td class="t l b r" align="center">66 - < 81</td>
            <td class="t l b r">Lembaga sehat, seluruh rasio utama terpenuhi.</td>
        </tr>
        <tr>
            <td class="t l b r" align="center">PK 3</td>
            <td class="t l b r">Cukup Sehat</td>
            <td class="t l b r" align="center">51 - < 66</td>
            <td class="t l b r">Lembaga cukup sehat, terdapat sebagian kecil rasio yang perlu perbaikan.</td>
        </tr>
        <tr>
            <td class="t l b r" align="center">PK 4</td>
            <td class="t l b r">Kurang Sehat</td>
            <td class="t l b r" align="center">< 51 (kecuali trigger PK 5)</td>
            <td class="t l b r">Lembaga kurang sehat, beberapa rasio tidak memenuhi batas.</td>
        </tr>
        <tr>
            <td class="t l b r" align="center">PK 5</td>
            <td class="t l b r">Tidak Sehat</td>
            <td class="t l b r" align="center">Trigger otomatis</td>
            <td class="t l b r">NPL Neto &ge; 25% ATAU Rasio Ekuitas <  50% ATAU Coverage PPAP <  50%.</td>
        </tr>
        <tr style="background: rgb(255,247,224); font-weight: bold;">
            <td class="t l b r" align="center" style="background: {{ $a['status_pengawasan_warna'] }}; color: #fff; font-size: 12px;">
                PK {{ $a['pk'] }}
            </td>
            <td class="t l b r" colspan="3">
                Hasil Penilaian: <b>{{ $a['pk_label'] }}</b> (Skor Komposit = {{ number_format($a['skor_komposit'], 2) }}).
            </td>
        </tr>
    </table>
    <br>

    <div style="font-size: 11px; font-weight: bold; background: rgb(232,232,232); padding: 4px;">
        D. STATUS PENGAWASAN
    </div>
    <table border="0" width="100%" cellspacing="0" cellpadding="4" style="font-size: 10px; border: 1px solid #000;">
        <tr style="background: rgb(245,245,245); font-weight: bold;">
            <td class="t l b r" width="5%" align="center">No</td>
            <td class="t l b r" width="25%" align="left">Status</td>
            <td class="t l b r" width="70%" align="left">Threshold</td>
        </tr>
        <tr>
            <td class="t l b r" align="center">1</td>
            <td class="t l b r">Pengawasan Intensif</td>
            <td class="t l b r">PK 4 ATAU Rasio Ekuitas 50% s.d < 75% ATAU NPL Neto > 5% s.d < 25%.</td>
        </tr>
        <tr>
            <td class="t l b r" align="center">2</td>
            <td class="t l b r">Pengawasan Khusus</td>
            <td class="t l b r">PK 5 ATAU Rasio Ekuitas <  50% ATAU NPL Neto > 25%.</td>
        </tr>
        <tr style="background: {{ $a['status_pengawasan_warna'] }}; color: #fff; font-weight: bold;">
            <td class="t l b r" align="center" colspan="2">
                Status Saat Ini: <b>{{ strtoupper($a['status_pengawasan_label']) }}</b>
            </td>
            <td class="t l b r">
                {{ $a['status_pengawasan_alasan'] }}
            </td>
        </tr>
    </table>
    <br>

    <div style="font-size: 11px; font-weight: bold; background: rgb(232,232,232); padding: 4px;">
        E. RINGKASAN CADANGAN PPAP PER KOLEKTIBILITAS
    </div>
    <table border="0" width="100%" cellspacing="0" cellpadding="3" style="font-size: 9px; border: 1px solid #000;">
        <thead>
            <tr style="background: rgb(245,245,245);">
                <th class="t l b r" width="20%" align="left">Kolektibilitas</th>
                <th class="t l b r" width="15%" align="center">Sisa Pokok (Rp)</th>
                <th class="t l b r" width="15%" align="center">% PPAP Wajib</th>
                <th class="t l b r" width="18%" align="center">PPAP Wajib (Rp)</th>
                <th class="t l b r" width="18%" align="center">PPAP Terbentuk (Proporsi)</th>
                <th class="t l b r" width="14%" align="center">Selisih</th>
            </tr>
        </thead>
        <tbody>
            @php
                $proporsi_terbentuk = 0;
                $proporsi_total = 0;
                if ($a['ppap_wajib_minimum'] > 0) {
                    $proporsi_terbentuk = $a['cadangan_ppap_terbentuk'];
                }
            @endphp
            @foreach ($a['kolek_items'] as $idx => $item)
                @php
                    $nama = $item['nama'] ?? '-';
                    $saldo = $a['sum_kolek_total'][$idx] ?? 0;
                    $prosentase = (float) ($item['prosentase'] ?? 0);
                    $ppap_wajib_row = ($saldo * $prosentase) / 100;

                    $total_kolek_saldo = array_sum($a['sum_kolek_total']);
                    $proporsi_ppap = 0;
                    if ($total_kolek_saldo > 0 && $a['cadangan_ppap_terbentuk'] > 0) {
                        $proporsi_ppap = ($a['cadangan_ppap_terbentuk'] >= $a['ppap_wajib_minimum'])
                            ? $ppap_wajib_row
                            : ($a['cadangan_ppap_terbentuk'] / max($a['ppap_wajib_minimum'], 1)) * $ppap_wajib_row;
                    }
                    $selisih = $proporsi_ppap - $ppap_wajib_row;
                @endphp
                <tr>
                    <td class="t l b r">{{ $nama }} ({{ $prosentase }}%)</td>
                    <td class="t l b r" align="right">{{ number_format($saldo, 0, '.', ',') }}</td>
                    <td class="t l b r" align="center">{{ $prosentase }}%</td>
                    <td class="t l b r" align="right">{{ number_format($ppap_wajib_row, 0, '.', ',') }}</td>
                    <td class="t l b r" align="right">{{ number_format($proporsi_ppap, 0, '.', ',') }}</td>
                    <td class="t l b r" align="right" style="color: {{ $selisih < 0 ? '#dc3545' : '#198754' }};">
                        {{ number_format($selisih, 0, '.', ',') }}
                    </td>
                </tr>
            @endforeach
            <tr style="background: rgb(232,232,232); font-weight: bold;">
                <td class="t l b r" align="right">TOTAL</td>
                <td class="t l b r" align="right">{{ number_format(array_sum($a['sum_kolek_total']), 0, '.', ',') }}</td>
                <td class="t l b r" align="center">-</td>
                <td class="t l b r" align="right">{{ number_format($a['ppap_wajib_minimum'], 0, '.', ',') }}</td>
                <td class="t l b r" align="right">{{ number_format($a['cadangan_ppap_terbentuk'], 0, '.', ',') }}</td>
                <td class="t l b r" align="right">{{ number_format($a['cadangan_ppap_terbentuk'] - $a['ppap_wajib_minimum'], 0, '.', ',') }}</td>
            </tr>
        </tbody>
    </table>
    <br>

    <div style="font-size: 11px; font-weight: bold; background: rgb(232,232,232); padding: 4px;">
        F. REKOMENDASI PERBAIKAN
    </div>
    <table border="0" width="100%" cellspacing="0" cellpadding="5" style="font-size: 10px; border: 1px solid #000;">
        <thead>
            <tr style="background: rgb(245,245,245);">
                <th class="t l b r" width="4%" align="center">No</th>
                <th class="t l b r" width="33%" align="left">Aspek & Temuan</th>
                <th class="t l b r" width="63%" align="left">Cara Perbaikan</th>
            </tr>
        </thead>
        <tbody>
            @php
                $rek_items = $a['rekomendasi'] ?? [];
            @endphp
            @forelse ($rek_items as $idx => $rek)
                @php
                    $parts = explode('Cara perbaikan:', $rek, 2);
                    $temuan = trim($parts[0]);
                    $cara = isset($parts[1]) ? trim($parts[1]) : '';
                    $cara_lines = $cara === '' ? [] : preg_split('/;\s*(?=[a-z]\)|\([a-z]\)|-\s|[A-Z])/i', $cara);
                    if (empty($cara_lines) || (count($cara_lines) === 1 && trim($cara_lines[0]) === '')) {
                        $cara_lines = $cara === '' ? [] : [$cara];
                    }
                @endphp
                <tr>
                    <td class="t l b r" valign="top" align="center">{{ $idx + 1 }}</td>
                    <td class="t l b r" valign="top">
                        <b>{{ $temuan }}</b>
                    </td>
                    <td class="t l b r" valign="top">
                        @if (empty($cara))
                            -
                        @else
                            <ol style="margin: 0; padding-left: 18px; line-height: 1.5;">
                                @foreach ($cara_lines as $line)
                                    @php $line = trim(rtrim($line, ';.')); @endphp
                                    @if ($line !== '')
                                        <li style="margin-bottom: 4px;">{{ $line }}</li>
                                    @endif
                                @endforeach
                            </ol>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="t l b r" align="center">1</td>
                    <td class="t l b r" colspan="2" align="center">Tidak ada rekomendasi tambahan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <br>

    <div style="font-size: 11px; font-weight: bold; background: rgb(232,232,232); padding: 4px;">
        G. RINGKASAN KOLEKTIBILITAS POJK (lihat lampiran halaman landscape)
    </div>
    <table border="0" width="100%" cellspacing="0" cellpadding="3" style="font-size: 9px; border: 1px solid #000;">
        <thead>
            <tr style="background: rgb(245,245,245);">
                <th class="t l b r" width="4%" align="center">No</th>
                <th class="t l b r" width="20%" align="left">Kolektibilitas POJK</th>
                <th class="t l b r" width="10%" align="center">% PPAP</th>
                <th class="t l b r" width="18%" align="right">Saldo Pokok (Rp)</th>
                <th class="t l b r" width="18%" align="right">PPAP Wajib (Rp)</th>
                <th class="t l b r" width="14%" align="right">% thd Total</th>
                <th class="t l b r" width="16%" align="center">Klasik Tk. Kesehatan</th>
            </tr>
        </thead>
        <tbody>
            @php
                $sum_g1 = $a['sum_kolek_total'][0] ?? 0;
                $sum_g2 = $a['sum_kolek_total'][1] ?? 0;
                $sum_g3 = $a['sum_kolek_total'][2] ?? 0;
                $sum_g4 = $a['sum_kolek_total'][3] ?? 0;
                $sum_g5 = $a['sum_kolek_total'][4] ?? 0;
                $total_g_saldo = $sum_g1 + $sum_g2 + $sum_g3 + $sum_g4 + $sum_g5;
                $kolek_labels = [
                    1 => ['Lancar', '0%', 'rgb(212,237,218)', 'Sehat'],
                    2 => ['Dalam Perhatian Khusus (DPK)', '5%', 'rgb(255,243,205)', 'Perlu Perhatian'],
                    3 => ['Kurang Lancar', '15%', 'rgb(255,224,192)', 'Kurang Sehat'],
                    4 => ['Diragukan', '50%', 'rgb(248,215,218)', 'Tidak Sehat'],
                    5 => ['Macet', '100%', 'rgb(220,53,69)', 'Tidak Sehat'],
                ];
            @endphp
            @for ($i = 1; $i <= 5; $i++)
                @php
                    $row = $kolek_labels[$i];
                    $saldo = $a['sum_kolek_total'][$i - 1] ?? 0;
                    $ppap_wajib_row = $saldo * ((float) str_replace('%', '', $row[1]) / 100);
                    $pct_total = $total_g_saldo > 0 ? ($saldo / $total_g_saldo) * 100 : 0;
                    $color_bg = $i == 5 ? 'rgb(220,53,69); color: #fff;' : 'background: '.$row[2].';';
                @endphp
                <tr>
                    <td class="t l b r" align="center">{{ $i }}</td>
                    <td class="t l b r">{{ $row[0] }}</td>
                    <td class="t l b r" align="center">{{ $row[1] }}</td>
                    <td class="t l b r" align="right">{{ number_format($saldo, 0, '.', ',') }}</td>
                    <td class="t l b r" align="right">{{ number_format($ppap_wajib_row, 0, '.', ',') }}</td>
                    <td class="t l b r" align="right">{{ number_format($pct_total, 2) }}%</td>
                    <td class="t l b r" align="center" style="{{ $color_bg }} font-weight: bold;">{{ $row[3] }}</td>
                </tr>
            @endfor
            <tr style="background: rgb(232,232,232); font-weight: bold;">
                <td class="t l b r" colspan="3" align="right">TOTAL</td>
                <td class="t l b r" align="right">{{ number_format($total_g_saldo, 0, '.', ',') }}</td>
                <td class="t l b r" align="right">{{ number_format($a['ppap_wajib_minimum'], 0, '.', ',') }}</td>
                <td class="t l b r" align="right">100,00%</td>
                <td class="t l b r" align="center">-</td>
            </tr>
        </tbody>
    </table>
    <div style="font-size: 9px; color: #555; margin-top: 4px; font-style: italic;">
        * Detail per pinjaman tersedia di lampiran halaman landscape.
    </div>

    <br><br>

    <table border="0" width="100%" cellspacing="0" cellpadding="2" style="font-size: 11px;">
        <tr>
            <td width="50%"></td>
            <td width="50%" align="center">
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
@endsection
