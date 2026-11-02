<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['mysql', 'mysql_b'] as $connection) {
            try {
                if (! Schema::connection($connection)->hasTable('jenis_laporan_pinjaman')) {
                    continue;
                }

                $cek_urut = DB::connection($connection)->table('jenis_laporan_pinjaman')
                    ->where('file', 'pengajuan_proposal')
                    ->value('urut');
                $urut = $cek_urut !== null ? $cek_urut : 2;

                DB::connection($connection)->table('jenis_laporan_pinjaman')->firstOrCreate(
                    ['file' => 'rekap_proposal_desa'],
                    [
                        'nama_laporan' => 'Rekap Proposal Per Desa',
                        'urut' => $urut,
                        'status' => 1,
                    ]
                );
            } catch (Exception $exception) {
                continue;
            }
        }
    }

    public function down(): void
    {
        foreach (['mysql', 'mysql_b'] as $connection) {
            try {
                if (! Schema::connection($connection)->hasTable('jenis_laporan_pinjaman')) {
                    continue;
                }

                DB::connection($connection)->table('jenis_laporan_pinjaman')
                    ->where('file', 'rekap_proposal_desa')
                    ->delete();
            } catch (Exception $exception) {
                continue;
            }
        }
    }
};
