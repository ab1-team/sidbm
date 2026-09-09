@php
    use App\Utils\Tanggal;
@endphp

@extends('perguliran.dokumen.layout.base')

@section('content')
    @foreach ($pinkel->pinjaman_anggota as $pa)
        @if ($loop->iteration > 1)
            <div class="break"></div>
        @endif

        @php
            $identitas_peminjam = [
                [
                    'label' => 'Nama Anggota',
                    'value' => $pa->anggota->namadepan,
                ],
                [
                    'label' => 'NIK',
                    'value' => $pa->anggota->nik,
                ],
                [
                    'label' => 'Tempat, Tanggal Lahir',
                    'value' => $pa->anggota->tempat_lahir . ', ' . Tanggal::tglLatin($pa->anggota->tgl_lahir),
                ],
                [
                    'label' => 'Alamat',
                    'value' => $pa->anggota->alamat,
                ],
                [
                    'label' => 'Pekerjaan Pokok Suami',
                    'value' => '',
                ],
                [
                    'label' => 'Pekerjaan Pokok Istri',
                    'value' => '',
                ],
                [
                    'label' => 'Jumlah Kredit yang Diminta',
                    'value' => 'Rp. ' . number_format($pa->proposal),
                ],
                [
                    'label' => 'Jenis Usaha yang Akan Didanai',
                    'value' => is_numeric($pa->anggota->usaha) ? $pa->anggota->u->nama_usaha : $pa->anggota->usaha,
                ],
            ];
            $informasi_dalam_kelompok = [
                'Apakah anggota ini aktif dalam pertemuan kelompok',
                'Apakah anggota ini aktif memberikan usul, pendapat, sadan dan sebagainya',
                'Apakah anggota ini menunjukkan sikap tenang dan terbuka',
                'Apakah anggota ini jujur, disiplin dan berusaha menepati janji',
                'Apakah anggota ini bersedia membayar iuran-iuran di kelompok',
                'Apakah anggota ini disiplin dalam membayar piutangnya',
                'Apakah anggota ini rajin menabung di kelompok',
                'Apakah bersedia menjaminkan harta/tabungan sebagai jaminan kredit yang diminta',
                'Apakah bersedia menandatangani perjanjian kredit berdua (suami/istri/orang tua)',
            ];
            $pendapatan_pengeluaran = [
                [
                    'Pendapatan rutin keluarga (suami & istri) per bulan',
                    'Pendapatan dari hasil kebun, sawah, ladang',
                    'Pendapatan lain-lain',
                ],
                [
                    'Pengeluaran keluarga',
                    'Pembelian alat/barang dagangan',
                    'Pengeluaran kebutuhan makan/minum',
                    'Pengeluaran sabun-cuci-mandi',
                    'Pengeluaran untuk sekolah',
                    'Pengeluaran untuk sosial',
                    'Pengeluaran listrik, telpon, dll',
                    'Pengeluaran lain-lain',
                ],
            ];
            $jaminan = ['Tabungan tanggung renteng', 'Nilai harta lain berupa ....................................'];
            $penilaian = [
                'Ratio pendapatan keluarga (bersih) per bulan dibagi angsuran per bulan',
                'Ratio tabungan di kelompok dibagi kredit yang diajukan',
            ];
        @endphp

        <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px;">
            <tr>
                <td class="b" colspan="3" align="center">
                    <div style="font-size: 16px;">
                        PENILAIAN PERMOHONAN PIUTANG ANGGOTA KELOMPOK
                    </div>
                </td>
            </tr>
        </table>

        <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px; table-layout: fixed;">
            <tbody>
                <tr style="font-weight: bold;">
                    <td width="3%" align="center">A.</td>
                    <td colspan="6">IDENTITAS PEMINJAM (ANGGOTA)</td>
                </tr>

                @php
                    $length = ceil(count($identitas_peminjam) / 2);

                @endphp

                <tr>
                    <td>&nbsp;</td>
                    <td colspan="6">
                        <table border="0" width="100%" cellspacing="0" cellpadding="0"
                            style="font-size: 11px; table-layout: fixed;">
                            @for ($i = 0; $i < $length; $i++)
                                @php
                                    $label1 = $identitas_peminjam[$i]['label'];
                                    $value1 = ': ' . $identitas_peminjam[$i]['value'];

                                    $label2 = '';
                                    $value2 = '';
                                    if (isset($identitas_peminjam[$i + $length])) {
                                        $label2 = $identitas_peminjam[$i + $length]['label'];
                                        $value2 = ': ' . $identitas_peminjam[$i + $length]['value'];
                                    }
                                @endphp

                                <tr>
                                    <td width="21%">{{ $label1 }}</td>
                                    <td width="25%">{{ $value1 }}</td>
                                    <td width="29%">{{ $label2 }}</td>
                                    <td width="25%">{{ $value2 }}</td>
                                </tr>
                            @endfor
                        </table>
                    </td>
                </tr>

                <tr style="font-weight: bold;">
                    <td class="gp-t gp-l gp-b" align="center">B.</td>
                    <td class="gp-t gp-b" colspan="4">INFORMASI DALAM KELOMPOK</td>
                    <td class="gp-t gp-l gp-b" width="7%" align="center">YA</td>
                    <td class="gp-t gp-l gp-b gp-r" width="7%" align="center">TIDAK</td>
                </tr>

                @foreach ($informasi_dalam_kelompok as $idk => $val)
                    <tr>
                        <td class="gp-t gp-l gp-b">&nbsp;</td>
                        <td class="gp-t gp-b" align="center" width="3%">{{ $loop->iteration }}.</td>
                        <td class="gp-t gp-b" colspan="3">{{ $val }}</td>
                        <td class="gp-t gp-l gp-b" align="center">&nbsp;</td>
                        <td class="gp-t gp-l gp-b gp-r" align="center">&nbsp;</td>
                    </tr>
                @endforeach

                <tr style="font-weight: bold;">
                    <td align="center">C.</td>
                    <td colspan="6">INFORMASI PENDAPATAN & PENGELUARAN</td>
                </tr>

                @foreach ($pendapatan_pengeluaran as $pp => $p)
                    @php
                        $no = $loop->iteration;
                        $nomor = 0;
                    @endphp
                    @foreach ($p as $pen => $val)
                        @php
                            $number = '';
                            if ($no != $nomor) {
                                $nomor = $no;
                                $number = $no . '.';
                            }

                            $title = 'Pengeluaran';
                            if ($no == 1) {
                                $title = 'Pendapatan';
                            }
                        @endphp

                        <tr>
                            <td>&nbsp;</td>
                            <td align="center" width="3%">{{ $number }}</td>
                            <td colspan="2">{{ $val }}</td>
                            <td width="7%">Rp.</td>
                            <td colspan="2">&nbsp;</td>
                        </tr>
                    @endforeach

                    <tr style="font-weight: bold;">
                        <td>&nbsp;</td>
                        <td align="center" width="3%">&nbsp;</td>
                        <td align="center" colspan="2">Jumlah {{ $title }}</td>
                        <td width="7%">Rp.</td>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                @endforeach

                <tr style="font-weight: bold;">
                    <td align="center">D.</td>
                    <td colspan="6">IDENTITAS JAMINAN</td>
                </tr>

                @foreach ($jaminan as $jam => $val)
                    <tr>
                        <td>&nbsp;</td>
                        <td align="center" width="3%">{{ $loop->iteration }}.</td>
                        <td colspan="2">{{ $val }}</td>
                        <td width="7%">Rp.</td>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                @endforeach

                <tr style="font-weight: bold;">
                    <td>&nbsp;</td>
                    <td align="center" width="3%">&nbsp;</td>
                    <td align="center" colspan="2">Total Nilai Jaminan</td>
                    <td width="7%">Rp.</td>
                    <td colspan="2">&nbsp;</td>
                </tr>

                <tr style="font-weight: bold;">
                    <td align="center">E.</td>
                    <td colspan="6">PENILAIAN</td>
                </tr>

                @foreach ($penilaian as $pn => $val)
                    <tr>
                        <td>&nbsp;</td>
                        <td align="center" width="3%">{{ $loop->iteration }}.</td>
                        <td colspan="2">{{ $val }}</td>
                        <td width="7%">.....%</td>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                @endforeach

                <tr style="font-weight: bold;">
                    <td align="center">E.</td>
                    <td colspan="6">KESIMPULAN</td>
                </tr>

                <tr class="vt">
                    <td>&nbsp;</td>
                    <td colspan="2">
                        <div>
                            Anggota/pemanfaat ini LAYAK / TIDAK LAYAK untuk diberikan piutang sebesar:
                        </div>
                        <div>
                            ........................................................................
                        </div>
                        <div>
                            Catatan:
                            <br>
                            <br>
                        </div>
                        <div>
                            Coret yang tidak perlu
                        </div>
                    </td>
                    <td colspan="4">
                        <div>Diverifikasi oleh, Tim Verifikasi {{ $kec->nama_lembaga_sort }} Kecamatan
                            {{ $kec->nama_kec }}</div>
                        <table border="0" width="100%" cellspacing="0" cellpadding="0"
                            style="font-size: 11px; table-layout: fixed;">
                            @foreach ($verifikator as $verif)
                                <tr>
                                    <td width="70" height="20">
                                        <div>{{ $verif->namadepan }} {{ $verif->namabelakang }}</div>
                                        <div>
                                            @if ($verif->jabatan == '1' && $verif->level == '4')
                                                Ketua
                                            @else
                                                <b>{{ $verif->j->nama_jabatan }}</b>
                                            @endif
                                        </div>
                                    </td>
                                    <td align="right" style="vertical-align: bottom;">
                                        <b>____________________________________</b>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>
    @endforeach
@endsection
