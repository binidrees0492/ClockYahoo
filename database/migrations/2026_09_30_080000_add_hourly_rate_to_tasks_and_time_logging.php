<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_tasks', 'hourly_rate')) {
                $table->decimal('hourly_rate', 10, 2)->nullable()->after('name');
            }
        });

        Schema::table('tbl_time_logging', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_time_logging', 'hourly_rate')) {
                $table->decimal('hourly_rate', 10, 2)->nullable()->after('duration_minutes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tbl_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_tasks', 'hourly_rate')) {
                $table->dropColumn('hourly_rate');
            }
        });

        Schema::table('tbl_time_logging', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_time_logging', 'hourly_rate')) {
                $table->dropColumn('hourly_rate');
            }
        });
    }
};
