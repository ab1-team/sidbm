<?php

namespace App\Utils;

class Pinjaman
{
    public static function satuanJangka($pinjaman, $capital = true)
    {
        $minggu = optional(optional($pinjaman)->sis_pokok)->id == '12';

        return $capital
            ? ($minggu ? 'Minggu' : 'Bulan')
            : ($minggu ? 'minggu' : 'bulan');
    }

    public static function keyword($text = false, $data = [])
    {
        if ($text === false) {
            return [
                [
                    'key' => '{nama_kec}',
                    'des' => 'Nama Kecamatan',
                    'pinjaman' => '0'
                ],
                [
                    'key' => '{kepala_lembaga}',
                    'des' => 'Sebutan Kepala Lembaga',
                    'pinjaman' => '0'
                ],
                [
                    'key' => '{kabag_administrasi}',
                    'des' => 'Sebutan Kabag Administrasi',
                    'pinjaman' => '0'
                ],
                [
                    'key' => '{kabag_keuangan}',
                    'des' => 'Sebutan Kabag Keuangan',
                    'pinjaman' => '0'
                ],
                [
                    'key' => '{verifikator}',
                    'des' => 'Nama Sebutan Verifikator',
                    'pinjaman' => '0'
                ],
                [
                    'key' => '{pengawas}',
                    'des' => 'Nama Sebutan Pengawas',
                    'pinjaman' => '0'
                ],
                [
                    'key' => '{nama_kelompok}',
                    'des' => 'Nama Kelompok',
                    'pinjaman' => '1'
                ],
                [
                    'key' => '{ketua}',
                    'des' => 'Nama Ketua Kelompok',
                    'pinjaman' => '1'
                ],
                [
                    'key' => '{sekretaris}',
                    'des' => 'Nama Sekretaris Kelompok',
                    'pinjaman' => '1'
                ],
                [
                    'key' => '{bendahara}',
                    'des' => 'Nama Bendahara Kelompok',
                    'pinjaman' => '1'
                ],
                [
                    'key' => '{kades}',
                    'des' => 'Nama Kepala Desa/Lurah',
                    'pinjaman' => '1'
                ],
                [
                    'key' => '{pangkat}',
                    'des' => 'Pangkat Kepala Desa/Lurah',
                    'pinjaman' => '1'
                ],
                [
                    'key' => '{nip}',
                    'des' => 'Nip Kepala Desa/Lurah',
                    'pinjaman' => '1'
                ],
                [
                    'key' => '{sekdes}',
                    'des' => 'Nama Sekdes',
                    'pinjaman' => '1'
                ],
                [
                    'key' => '{ked}',
                    'des' => 'Nama Kader Ekonomi Desa',
                    'pinjaman' => '1'
                ],
                [
                    'key' => '{desa}',
                    'des' => 'Nama Desa',
                    'pinjaman' => '1'
                ],
                [
                    'key' => '{sebutan_kades}',
                    'des' => 'Sebutan Kepala Desa/Lurah',
                    'pinjaman' => '1'
                ],
                [
                    'key' => '{tanggal_proposal}',
                    'des' => 'Tanggal Proposal',
                    'pinjaman' => '1'
                ],
                [
                    'key' => '{tanggal_waiting}',
                    'des' => 'Tanggal Waiting',
                    'pinjaman' => '1'
                ],
                [
                    'key' => '{tanggal_cair}',
                    'des' => 'Tanggal Cair',
                    'pinjaman' => '1'
                ],
                [
                    'key' => '{nama_anggota}',
                    'des' => 'Nama Anggota Pemanfaat',
                    'pinjaman' => '1'
                ],
                [
                    'key' => '{nik}',
                    'des' => 'NIK Anggota Pemanfaat',
                    'pinjaman' => '1'
                ],
                [
                    'key' => '{penjamin}',
                    'des' => 'Penjamin',
                    'pinjaman' => '1'
                ],
                [
                    'key' => '{tanggal_kondisi}',
                    'des' => 'Tanggal Laporan Dibuka',
                    'pinjaman' => '0'
                ],
            ];
        } else {
            $kec = $data['kec'];
            $replacer = [
                '{nama_kec}' => $kec->nama_kec,
                '{kepala_lembaga}' => $kec->sebutan_level_1,
                '{kabag_administrasi}' => $kec->sebutan_level_2,
                '{kabag_keuangan}' => $kec->sebutan_level_3,
                '{verifikator}' => $kec->nama_tv_long,
                '{pengawas}' => $kec->nama_bp_long,
                '{tanggal_kondisi}' => Tanggal::tglLatin($data['tgl_kondisi']),
            ];

            if ($data['jenis_laporan'] == 'dokumen_pinjaman') {
                $pinkel = $data['pinkel'];
                $kel = $pinkel->kelompok;
                $desa = $pinkel->kelompok->d;

                $ketua = $kel->ketua;
                $sekretaris = $kel->sekretaris;
                $bendahara = $kel->bendahara;
                if ($pinkel->struktur_kelompok) {
                    $struktur_kelompok = json_decode($pinkel->struktur_kelompok, true);
                    $ketua = isset($struktur_kelompok['ketua']) ? $struktur_kelompok['ketua'] : '';
                    $sekretaris = isset($struktur_kelompok['sekretaris']) ? $struktur_kelompok['sekretaris'] : '';
                    $bendahara = isset($struktur_kelompok['bendahara']) ? $struktur_kelompok['bendahara'] : '';
                }

                $replacer['{nama_kelompok}'] = $kel->nama_kelompok;
                $replacer['{ketua}'] = $ketua;
                $replacer['{sekretaris}'] = $sekretaris;
                $replacer['{bendahara}'] = $bendahara;
                $replacer['{kades}'] = $desa->kades;
                $replacer['{pangkat}'] = $desa->pangkat;
                $replacer['{nip}'] = $desa->nip;
                $replacer['{sekdes}'] = $desa->sekdes;
                $replacer['{ked}'] = $desa->ked;
                $replacer['{desa}'] = $desa->nama_desa;
                $replacer['{sebutan_kades}'] = $desa->sebutan_desa->sebutan_kades;
                $replacer['{tanggal_proposal}'] = Tanggal::tglLatin($pinkel->tgl_proposal);
                $replacer['{tanggal_waiting}'] = Tanggal::tglLatin($pinkel->tgl_tunggu);
                $replacer['{tanggal_cair}'] = Tanggal::tglLatin($pinkel->tgl_cair);

                if (isset($data['pinjaman_anggota'])) {
                    $pinjaman_anggota = $data['pinjaman_anggota'];

                    $replacer['{nama_anggota}'] = $pinjaman_anggota->anggota->namadepan;
                    $replacer['{nik}'] = $pinjaman_anggota->anggota->nik;
                    $replacer['{penjamin}'] = $pinjaman_anggota->anggota->penjamin;
                }
            }

            $replacer['1'] = '1';
            $replacer['0'] = '0';


            $ttd = strtr(json_decode($text, true), $replacer);
            return $ttd;
        }
    }
}
