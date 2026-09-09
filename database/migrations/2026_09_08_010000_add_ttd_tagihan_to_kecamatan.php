<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['mysql', 'mysql_b'] as $connection) {
            try {
                if (Schema::connection($connection)->hasTable('kecamatan')
                    && ! Schema::connection($connection)->hasColumn('kecamatan', 'ttd_tagihan')) {
                    Schema::connection($connection)->table('kecamatan', function (Blueprint $table) {
                        $table->string('ttd_tagihan', 255)->nullable()->default(null);
                    });
                }
            } catch (Exception $exception) {
                continue;
            }
        }
    }

    public function down(): void
    {
        foreach (['mysql', 'mysql_b'] as $connection) {
            try {
                if (Schema::connection($connection)->hasTable('kecamatan')
                    && Schema::connection($connection)->hasColumn('kecamatan', 'ttd_tagihan')) {
                    Schema::connection($connection)->table('kecamatan', function (Blueprint $table) {
                        $table->dropColumn('ttd_tagihan');
                    });
                }
            } catch (Exception $exception) {
                continue;
            }
        }
    }
};
