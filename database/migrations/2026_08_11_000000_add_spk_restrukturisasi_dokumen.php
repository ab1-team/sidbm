<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('dokumen_pinjaman')
            ->where('file', 'spkRestrukturisasi')
            ->exists();

        if (! $exists) {
            $maxUrutan = DB::table('dokumen_pinjaman')
                ->where('jenis_dokumen', 'dokumen_pencairan')
                ->max('urutan');

            DB::table('dokumen_pinjaman')->insert([
                'title' => 'SPK Restrukturisasi',
                'file' => 'spkRestrukturisasi',
                'jenis_dokumen' => 'dokumen_pencairan',
                'excel' => 0,
                'urutan' => ($maxUrutan ?? 0) + 1,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('dokumen_pinjaman')
            ->where('file', 'spkRestrukturisasi')
            ->delete();
    }
};
