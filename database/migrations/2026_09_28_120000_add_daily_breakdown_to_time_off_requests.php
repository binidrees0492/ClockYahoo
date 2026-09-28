<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_off_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('time_off_requests', 'daily_breakdown')) {
                $table->json('daily_breakdown')->nullable()->after('daily_hours');
            }
        });
    }

    public function down(): void
    {
        Schema::table('time_off_requests', function (Blueprint $table) {
            $table->dropColumn('daily_breakdown');
        });
    }
};
