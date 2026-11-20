<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            if (!Schema::hasColumn('schedules', 'break_start')) {
                $table->string('break_start')->nullable()->after('end_time');
            }
            if (!Schema::hasColumn('schedules', 'break_end')) {
                $table->string('break_end')->nullable()->after('break_start');
            }
            if (!Schema::hasColumn('schedules', 'break_minutes')) {
                $table->integer('break_minutes')->nullable()->default(0)->after('break_end');
            }
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            if (Schema::hasColumn('schedules', 'break_start')) {
                $table->dropColumn('break_start');
            }
            if (Schema::hasColumn('schedules', 'break_end')) {
                $table->dropColumn('break_end');
            }
            if (Schema::hasColumn('schedules', 'break_minutes')) {
                $table->dropColumn('break_minutes');
            }
        });
    }
};
