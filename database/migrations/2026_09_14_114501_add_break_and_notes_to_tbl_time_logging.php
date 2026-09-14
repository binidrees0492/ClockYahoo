<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_time_logging', function (Blueprint $table) {
            $table->timestamp('break_started_at')->nullable()->after('clock_in');
            $table->integer('break_minutes')->default(0)->after('break_started_at');
            $table->text('notes')->nullable()->after('duration_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_time_logging', function (Blueprint $table) {
            $table->dropColumn(['break_started_at', 'break_minutes', 'notes']);
        });
    }
};
