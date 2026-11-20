<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_time_logging', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_time_logging', 'breaks')) {
                $table->json('breaks')->nullable()->after('break_minutes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tbl_time_logging', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_time_logging', 'breaks')) {
                $table->dropColumn('breaks');
            }
        });
    }
};
