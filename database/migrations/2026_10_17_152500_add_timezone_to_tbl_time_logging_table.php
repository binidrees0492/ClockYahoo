<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('tbl_time_logging')) {
            Schema::table('tbl_time_logging', function (Blueprint $table) {
                if (!Schema::hasColumn('tbl_time_logging', 'timezone')) {
                    $table->string('timezone', 100)->nullable()->after('task_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tbl_time_logging')) {
            Schema::table('tbl_time_logging', function (Blueprint $table) {
                if (Schema::hasColumn('tbl_time_logging', 'timezone')) {
                    $table->dropColumn('timezone');
                }
            });
        }
    }
};
