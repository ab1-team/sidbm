<?php

namespace App\Services;

use App\Models\Kecamatan;
use App\Models\PinjamanKelompok;
use App\Models\RealAngsuran;
use App\Models\RencanaAngsuran;
use App\Utils\Keuangan;
use DB;
use Session;

class GenerateService
{
    protected $kode_pokok = ['1.1.03.01', '1.1.03.02', '1.1.03.03'];

    protected $kode_jasa = ['1.1.03.04', '1.1.03.05', '1.1.03.06', '4.1.01.01', '4.1.01.02', '4.1.01.03'];

    protected $kode_denda = ['4.1.01.04', '4.1.01.05', '4.1.01.06'];

    public function generate(array $data, int $offset = 0)
    {
        $limit = 30;
        $kec = Kecamatan::where('id', Session::get('lokasi'))->first();
        $kondisi = collect($data)->except(['_token', 'pinjaman', 'generate_version'])->toArray();
        $where = $this->where($kondisi);

        $pinjaman = PinjamanKelompok::where(function ($query) use ($where) {
            $query->where($where['where']);
            foreach ($where['whereIn'] as $key => $value) {
                $query->whereIn($key, $value);
            }
            foreach ($where['whereNotIn'] as $key => $value) {
                $query->whereNotIn($key, $value);
            }
        })->with([
            'pinjaman_anggota',
            'sis_pokok',
            'sis_jasa',
            'trx' => function ($query) {
                $query->where('idtp', '!=', '0')->whereNotIn('rekening_kredit', $this->kode_denda);
            },
            'trx.tr_idtp' => function ($query) {
                $query->whereNotIn('rekening_kredit', $this->kode_denda);
            },
            'trx_penghapusan',
            'trx_penghapusan.tr_idtp',
            'kelompok',
            'kelompok.d',
            'pinjaman' => function ($query) {
                $query->where('status', 'H');
            },
        ])->limit($limit)->offset($offset)->orderBy('id', 'ASC')->get();

        $data_pinjaman = [];
        $data_pinjaman_H = [];

        foreach ($pinjaman as $pinkel) {
            if ($pinkel->status == 'H' || $pinkel->pinjaman) {
                $data_pinjaman_H[] = $pinkel->id;

                continue;
            }

            $this->generateByLoan($pinkel, $kec);
            $data_pinjaman[] = $pinkel->id;
        }

        return [
            'data_pinjaman' => array_merge($data_pinjaman, [$data_pinjaman_H]),
            'offset' => $offset + $limit,
            'limit' => $limit,
        ];
    }

    public function generateByLoan($pinkel, $kec = null)
    {
        $res = $this->preview($pinkel, $kec);

        DB::transaction(function () use ($pinkel, $res) {
            RencanaAngsuran::where('loan_id', $pinkel->id)->delete();
            RealAngsuran::where('loan_id', $pinkel->id)->delete();
            if (count($res['id_real'] ?? []) > 0) {
                RealAngsuran::whereIn('id', $res['id_real'])->delete();
            }

            if (count($res['rencana'] ?? []) > 0) {
                RencanaAngsuran::insert($res['rencana']);
            }

            $real = collect($res['real'] ?? [])->filter(function ($item) {
                return isset($item['id']) && intval($item['id']) > 0;
            })->values()->all();
            if (count($real) > 0) {
                RealAngsuran::insert($real);
            }
        });

        return true;
    }

    /**
     * Preview the schedule without saving to DB
     */
    public function preview($pinkel, $kec = null)
    {
        if (! $kec) {
            $kec = Kecamatan::where('id', Session::get('lokasi'))->first();
        }

        // Ensure all required relationships are loaded
        if (! $pinkel->relationLoaded('trx')) {
            $pinkel->load([
                'pinjaman_anggota', 'sis_pokok', 'sis_jasa',
                'trx' => function ($query) {
                    $query->where('idtp', '!=', '0')->whereNotIn('rekening_kredit', $this->kode_denda);
                },
                'trx.tr_idtp' => function ($query) {
                    $query->whereNotIn('rekening_kredit', $this->kode_denda);
                },
                'trx_penghapusan', 'trx_penghapusan.tr_idtp',
                'kelompok', 'kelompok.d',
                'pinjaman' => function ($query) {
                    $query->where('status', 'H');
                },
            ]);
        }

        $sum_aloc_anggota = $pinkel->pinjaman_anggota->sum(fn ($pa) => $this->getAlokasi($pa));
        $aloc_kelompok = $this->getAlokasi($pinkel);

        if ($sum_aloc_anggota != $aloc_kelompok && count($pinkel->pinjaman_anggota) > 0) {
            $res = $this->processV1($pinkel, $kec);
        } else {
            $res = $this->processV2($pinkel, $kec);
        }

        return $res;
    }

    protected function processV1($pinkel, $kec)
    {
        $res = $this->buildRencanaV1($pinkel, $kec);
        $res['real'] = $this->buildRealV1($pinkel, $res['data_rencana']);

        return $res;
    }

    protected function processV2($pinkel, $kec)
    {
        if (count($pinkel->pinjaman_anggota) > 0) {
            $res = $this->buildRencanaV2WithAnggota($pinkel, $kec);
        } else {
            $res = $this->buildRencanaV2($pinkel, $kec);
        }
        $res['real'] = $this->buildRealV2($pinkel, $res['data_rencana']);

        return $res;
    }

    protected function buildRencanaV1($pinkel, $kec)
    {
        $rencana = [];
        $data_rencana = [];
        $detail = $this->detail_pinjaman($pinkel, $pinkel->kelompok->d, $kec->batas_angsuran);
        $tgl_cair = $detail['tgl_cair'];
        $tgl_cair_asli = $detail['tgl_cair_asli'];
        $alokasi = $detail['alokasi'];
        $jangka = $pinkel->jangka;

        $sis_p = $pinkel->sis_pokok->sistem ?? '1';
        $sis_j = $pinkel->sis_jasa->sistem ?? '1';
        $ang_p = $this->sistem($pinkel->sistem_angsuran, $jangka, $sis_p);
        $ang_j = $this->sistem($pinkel->sa_jasa, $jangka, $sis_j);

        // ALWAYS calculate based on total alokasi to ensure correct group-level rounding
        $ra_data = $this->rencana_angsuran($pinkel, $ang_p, $ang_j, $alokasi, $kec->pembulatan);

        // Row 0 = tanggal cair ASLI (marker, bukan angsuran pertama)
        $data_rencana[strtotime($tgl_cair_asli)] = $this->fmtRencana($pinkel->id, 0, $tgl_cair_asli, 0, 0, 0, 0);
        $rencana[] = $data_rencana[strtotime($tgl_cair_asli)];

        $target_p = 0;
        $target_j = 0;
        for ($i = 1; $i <= $jangka; $i++) {
            $jatuh_tempo = $this->jatuh_tempo($i, $pinkel->sistem_angsuran, $tgl_cair);
            $pokok = $ra_data['pokok'][$i] ?? 0;
            $jasa = $ra_data['jasa'][$i] ?? 0;
            $target_p += $pokok;
            $target_j += $jasa;

            $data_rencana[strtotime($jatuh_tempo)] = $this->fmtRencana(
                $pinkel->id, $i, $jatuh_tempo, $pokok, $jasa, $target_p, $target_j
            );
            $rencana[] = $data_rencana[strtotime($jatuh_tempo)];
        }

        return ['rencana' => $rencana, 'data_rencana' => $data_rencana, 'id_real' => []];
    }

    protected function buildRealV1($pinkel, $data_rencana)
    {
        $real = [];
        $sum_p = 0;
        $sum_j = 0;
        $alokasi_p = $this->getAlokasi($pinkel);
        $alokasi_j = (collect($data_rencana)->max('target_jasa') ?? 0);
        $data_idtp = [];

        ksort($data_rencana);
        foreach ($pinkel->trx as $trx) {
            if (intval($trx->idtp) <= 0 || in_array($trx->idtp, $data_idtp)) {
                continue;
            }

            $res = $this->getTrxAmounts($trx);
            $sum_p += $res['p'];
            $sum_j += $res['j'];
            $alokasi_p -= $res['p'];
            $alokasi_j -= $res['j'];

            $target = ['p' => 0, 'j' => 0];
            foreach ($data_rencana as $key => $val) {
                if ($key <= strtotime($trx->tgl_transaksi)) {
                    $target['p'] = $val['target_pokok'];
                    $target['j'] = $val['target_jasa'];
                }
            }

            $real[] = $this->fmtReal(
                $trx, $pinkel->id, $res['p'], $res['j'], $sum_p, $sum_j, $alokasi_p, $alokasi_j,
                max(0, $target['p'] - $sum_p), max(0, $target['j'] - $sum_j)
            );
            $data_idtp[] = $trx->idtp;
        }

        return $real;
    }

    protected function buildRencanaV2($pinkel, $kec)
    {
        // Same as V1 for a single loan (no anggota) in most aspects, but V2 in controller has penghapusan logic
        return $this->buildRencanaWithPenghapusan($pinkel, null, $kec);
    }

    protected function buildRencanaV2WithAnggota($pinkel, $kec)
    {
        return $this->buildRencanaWithPenghapusan($pinkel, $pinkel->pinjaman_anggota, $kec);
    }

    protected function buildRencanaWithPenghapusan($pinkel, $anggota, $kec)
    {
        $rencana = [];
        $data_rencana = [];
        $data_id_real = [];
        $detail = $this->detail_pinjaman($pinkel, $pinkel->kelompok->d, $kec->batas_angsuran);
        $tgl_cair = $detail['tgl_cair'];
        $tgl_cair_asli = $detail['tgl_cair_asli'];
        $jangka = $pinkel->jangka;
        $sis_p = $this->sistem($pinkel->sistem_angsuran, $jangka, $pinkel->sis_pokok->sistem ?? '1');
        $sis_j = $this->sistem($pinkel->sa_jasa, $jangka, $pinkel->sis_jasa->sistem ?? '1');

        $rec_p = [];
        $rec_j = [];
        $alokasi_total = 0;
        if ($anggota) {
            foreach ($anggota as $pa) {
                $dt = $this->detail_pinjaman($pa, $pinkel->kelompok->d, $kec->batas_angsuran);
                $alokasi_total += $dt['alokasi'];
            }
        } else {
            $alokasi_total = $detail['alokasi'];
        }

        // Generate schedule ONCE for the total sum to ensure correct group rounding
        $sch = $this->rencana_angsuran($pinkel, $sis_p, $sis_j, $alokasi_total, $kec->pembulatan);
        $rec_p = $sch['pokok'];
        $rec_j = $sch['jasa'];

        $penghapusan = [];
        $idx = 1;
        foreach ($pinkel->trx_penghapusan as $trx_h) {
            if (intval($trx_h->idtp) <= 0) {
                continue;
            }
            $am = $this->getTrxAmounts($trx_h);
            $penghapusan[$idx++] = [
                'tgl' => $trx_h->tgl_transaksi, 'p' => $am['p'], 'j' => $am['j'],
                'alloc_p' => 0, 'alloc_j' => 0, 'idtp' => $trx_h->idtp,
            ];
            $data_id_real[] = $trx_h->idtp;
        }

        // Row 0 = tanggal cair ASLI (marker, bukan angsuran pertama)
        $data_rencana[strtotime($tgl_cair_asli)] = $this->fmtRencana($pinkel->id, 0, $tgl_cair_asli, 0, 0, 0, 0);
        $rencana[] = $data_rencana[strtotime($tgl_cair_asli)];

        $target_p = 0;
        $target_j = 0;
        $total_jasa = $alokasi_total * ($pinkel->pros_jasa / 100);

        $rencana_anggota = [];
        if ($anggota) {
            foreach ($anggota as $pa) {
                $sch_pa = $this->rencana_angsuran($pinkel, $sis_p, $sis_j, $this->getAlokasi($pa), $kec->pembulatan);
                $target_pa_p = array_sum($sch_pa['pokok']);
                $target_pa_j = array_sum($sch_pa['jasa']);

                $rencana_anggota['id'.$pa->id] = [
                    'pokok' => $sch_pa['pokok'],
                    'jasa' => $sch_pa['jasa'],
                    'jumlah_angsuran' => ($target_pa_p + $target_pa_j) / $pa->jangka
                ];
            }
        }

        for ($i = 1; $i <= $jangka; $i++) {
            $tempo = $this->jatuh_tempo($i, $pinkel->sistem_angsuran, $tgl_cair);
            $cur_p = $rec_p[$i] ?? 0;
            $cur_j = $rec_j[$i] ?? 0;

            foreach ($penghapusan as $k => $h) {
                if (strtotime($tempo) >= strtotime($h['tgl']) && $h['alloc_p'] == 0) {
                    $data_rencana[strtotime($h['tgl'])] = $this->fmtRencana($pinkel->id, $i, $h['tgl'], $h['p'], $h['j'], $target_p + $h['p'], $target_j + $h['j']);
                    $rencana[] = $data_rencana[strtotime($h['tgl'])];
                    $target_p += $h['p'];
                    $target_j += $h['j'];
                    $penghapusan[$k]['alloc_p'] = $alokasi_total - $target_p;
                    $penghapusan[$k]['alloc_j'] = $total_jasa - $target_j;
                }
            }

            $target_p += $cur_p;
            $target_j += $cur_j;
            $data_rencana[strtotime($tempo)] = $this->fmtRencana($pinkel->id, $i, $tempo, $cur_p, $cur_j, $target_p, $target_j);
            $rencana[] = $data_rencana[strtotime($tempo)];
        }

        return [
            'rencana' => $rencana,
            'data_rencana' => $data_rencana,
            'id_real' => $data_id_real,
            'rencana_anggota' => $rencana_anggota
        ];
    }

    protected function buildRealV2($pinkel, $data_rencana)
    {
        $real = [];
        $sum_p = 0;
        $sum_j = 0;
        $data_idtp = [];
        $alokasi_p = $this->getAlokasi($pinkel);
        $alokasi_j = $alokasi_p * ($pinkel->pros_jasa / 100);

        ksort($data_rencana);
        foreach ($pinkel->trx as $trx) {
            if (intval($trx->idtp) <= 0 || in_array($trx->idtp, $data_idtp)) {
                continue;
            }
            $am = $this->getTrxAmounts($trx);
            $sum_p += $am['p'];
            $sum_j += $am['j'];
            $saldo_p = $alokasi_p - $sum_p;
            $saldo_j = ($pinkel->jenis_jasa == '2') ? ($saldo_p * ($pinkel->pros_jasa / 100) - $am['j']) : ($alokasi_j - $sum_j);

            $target = ['p' => 0, 'j' => 0];
            foreach ($data_rencana as $k => $v) {
                if ($k <= strtotime($trx->tgl_transaksi)) {
                    $target['p'] = $v['target_pokok'];
                    $target['j'] = $v['target_jasa'];
                }
            }

            $real[] = $this->fmtReal(
                $trx, $pinkel->id, $am['p'], $am['j'], $sum_p, $sum_j, $saldo_p, max(0, $saldo_j),
                max(0, $target['p'] - $sum_p), max(0, $target['j'] - $sum_j)
            );
            $data_idtp[] = $trx->idtp;
        }

        return $real;
    }

    protected function fmtRencana($loan_id, $ke, $tempo, $p, $j, $tp, $tj)
    {
        return [
            'loan_id' => $loan_id, 'angsuran_ke' => $ke, 'jatuh_tempo' => $tempo,
            'wajib_pokok' => $p, 'wajib_jasa' => $j, 'target_pokok' => $tp, 'target_jasa' => $tj,
            'lu' => date('Y-m-d H:i:s'), 'id_user' => (auth()->user()->id ?? 1),
        ];
    }

    protected function fmtReal($trx, $loan_id, $p, $j, $sp, $sj, $slp, $slj, $tp, $tj)
    {
        return [
            'id' => $trx->idtp, 'loan_id' => $loan_id, 'tgl_transaksi' => $trx->tgl_transaksi,
            'realisasi_pokok' => $p, 'realisasi_jasa' => $j, 'sum_pokok' => $sp, 'sum_jasa' => $sj,
            'saldo_pokok' => $slp, 'saldo_jasa' => $slj, 'tunggakan_pokok' => $tp, 'tunggakan_jasa' => $tj,
            'lu' => date('Y-m-d H:i:s'), 'id_user' => (auth()->user()->id ?? 1),
        ];
    }

    protected function getTrxAmounts($trx)
    {
        $p = 0;
        $j = 0;
        foreach ($trx->tr_idtp as $idtp) {
            if (in_array($idtp->rekening_kredit, $this->kode_pokok)) {
                $p += floatval($idtp->jumlah);
            }
            if (in_array($idtp->rekening_kredit, $this->kode_jasa)) {
                $j += floatval($idtp->jumlah);
            }
        }

        return ['p' => $p, 'j' => $j];
    }

    protected function getAlokasi($pinjaman)
    {
        if ($pinjaman->status == 'P') {
            return $pinjaman->proposal;
        }
        if ($pinjaman->status == 'V') {
            return $pinjaman->verifikasi;
        }

        return $pinjaman->alokasi;
    }

    protected function where($kondisi)
    {
        $where = [];
        $whereIn = [];
        $whereNotIn = [];
        foreach ($kondisi as $key => $val) {
            $opt = '=';
            $value = $val;
            if (is_array($val)) {
                $opt = $val['operator'];
                $value = $val['value'];
                if (! $value) {
                    continue;
                }
                if ($opt == 'IN') {
                    $whereIn[$key] = explode(',', $value);

                    continue;
                }
                if ($opt == 'NOT IN') {
                    $whereNotIn[$key] = explode(',', $value);

                    continue;
                }
            }
            $where[] = [$key, $opt, $value];
        }

        return ['where' => $where, 'whereIn' => $whereIn, 'whereNotIn' => $whereNotIn];
    }

    protected function sistem($sistem_angsuran, $jangka_pinjaman, $sistem)
    {
        $map = [11 => 24, 14 => 3, 26 => 6, 15 => 2, 25 => 1, 20 => 12];
        if (isset($map[$sistem_angsuran])) {
            $tempo = $jangka_pinjaman - $map[$sistem_angsuran] / $sistem;
            $mulai_angsuran = $jangka_pinjaman - $tempo;
        } else {
            $tempo = floor($jangka_pinjaman / $sistem);
            $mulai_angsuran = 0;
        }

        return ['tempo' => $tempo, 'sistem' => $sistem, 'mulai_angsuran' => $mulai_angsuran];
    }

    protected function detail_pinjaman($pinjaman, $desa, $batas_angsuran)
    {
        $alokasi = $this->getAlokasi($pinjaman);
        $tgl_cair = $pinjaman->tgl_cair;
        if (in_array($pinjaman->status, ['P', 'V'])) {
            $tgl_cair = ($pinjaman->status == 'P') ? $pinjaman->tgl_proposal : $pinjaman->tgl_verifikasi;
        } elseif ($tgl_cair == '0000-00-00') {
            $tgl_cair = $pinjaman->tgl_tunggu;
        }

        $time = strtotime($tgl_cair);
        $y = date('Y', $time);
        $m = (int) date('m', $time);
        $d = (int) date('d', $time);

        $d_asli = $d;
        $d_jadwal = $d;
        if ($desa->jadwal_angsuran_desa > 0) {
            $d_jadwal = (int) $desa->jadwal_angsuran_desa;
        }

        // Tentukan bulan angsuran pertama:
        //   - Selalu: bulan.cair + 1, hari = tgl jadwal desa
        //   - Kalau ada batas dan tgl.cair > batas: bulan.cair + 2
        $m++;
        if ($batas_angsuran > 0 && $d_asli > $batas_angsuran) {
            $m++;
        }
        $d = $d_jadwal;

        $tgl_baru = mktime(0, 0, 0, $m, 1, $y);
        $max_d = (int) date('t', $tgl_baru);
        $tgl_baru_str = date('Y-m', $tgl_baru) . '-' . sprintf('%02d', min($d, $max_d));

        // tgl_cair_asli = tanggal cair mentah (sebelum penyesuaian jadwal desa)
        $tgl_cair_asli = date('Y-m-d', $time);

        return ['alokasi' => $alokasi, 'tgl_cair' => $tgl_baru_str, 'tgl_cair_asli' => $tgl_cair_asli];
    }

    protected function rencana_angsuran($pinkel, $ang_pokok, $ang_jasa, $alokasi, $pembulatan = '500')
    {
        $rencana = ['pokok' => [], 'jasa' => []];
        $alokasi_pokok = $alokasi;
        $temp_alokasi = $alokasi;

        $pokok_dibulatkan = Keuangan::pembulatan($alokasi_pokok / $ang_pokok['tempo'], $pembulatan);

        for ($j = 1; $j <= $pinkel->jangka; $j++) {
            $pokok = 0;
            if ($j % $ang_pokok['sistem'] == 0) {
                $ke = $j / $ang_pokok['sistem'];
                if ($ke == $ang_pokok['tempo']) {
                    $pokok = $alokasi_pokok - ($pokok_dibulatkan * ($ang_pokok['tempo'] - 1));
                } elseif ($ke > $ang_pokok['mulai_angsuran'] && $ke < $ang_pokok['tempo']) {
                    $pokok = $pokok_dibulatkan;
                }
            }
            $rencana['pokok'][$j] = $pokok;

            $jasa = 0;
            if ($j % $ang_jasa['sistem'] == 0) {
                $ke = $j / $ang_jasa['sistem'];
                $alokasi_jasa = $temp_alokasi * ($pinkel->pros_jasa / 100);
                $jasa_dibulatkan = Keuangan::pembulatan($alokasi_jasa / $ang_jasa['tempo'], $pembulatan);

                if ($pinkel->jenis_jasa == '2') {
                    $jasa = $jasa_dibulatkan;
                } else {
                    if ($ke == $ang_jasa['tempo']) {
                        $jasa = $alokasi_jasa - ($jasa_dibulatkan * ($ang_jasa['tempo'] - 1));
                    } elseif ($ke > $ang_jasa['mulai_angsuran'] && $ke < $ang_jasa['tempo']) {
                        $jasa = $jasa_dibulatkan;
                    }
                }
            }

            if ($pinkel->jenis_jasa == '2') {
                $temp_alokasi -= $pokok;
            }
            $rencana['jasa'][$j] = $jasa;
        }

        return $rencana;
    }

    protected function jatuh_tempo($index, $sa_pokok, $tanggal)
    {
        // $tanggal diasumsikan sudah = tanggal jatuh tempo angsuran pertama.
        // Angsuran ke-$index = tanggal + ($index - 1) bulan.
        // (Dahulu pakai +$index month, tapi $tanggal dulunya = tgl.cair asli,
        // sekarang $tanggal = angs pertama → kurangi 1.)
        $penambahan = ($sa_pokok == 12) ? '+'.(($index - 1) * 7).' days' : '+'.($index - 1).' month';
        $base = strtotime($tanggal);
        $target_ts = strtotime($penambahan, $base);
        $day = (int) date('d', $base);
        $max_d = (int) date('t', $target_ts);

        return date('Y-m', $target_ts) . '-' . sprintf('%02d', min($day, $max_d));
    }
}
