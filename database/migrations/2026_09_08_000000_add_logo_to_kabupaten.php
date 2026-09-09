<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('kabupaten', 'logo')) {
            Schema::table('kabupaten', function (Blueprint $table) {
                $table->string('logo', 255)->nullable()->default(null)->after('email_kab');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('kabupaten', 'logo')) {
            Schema::table('kabupaten', function (Blueprint $table) {
                $table->dropColumn('logo');
            });
        }
    }
};
