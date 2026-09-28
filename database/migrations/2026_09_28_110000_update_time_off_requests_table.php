<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_off_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('time_off_requests', 'approver_id')) {
                $table->unsignedBigInteger('approver_id')->nullable()->after('policy_id');
            }
            if (!Schema::hasColumn('time_off_requests', 'is_full_day')) {
                $table->boolean('is_full_day')->default(true)->after('approver_id');
            }
            if (!Schema::hasColumn('time_off_requests', 'daily_hours')) {
                $table->decimal('daily_hours', 5, 2)->default(8.00)->after('is_full_day');
            }
        });
    }

    public function down(): void
    {
        Schema::table('time_off_requests', function (Blueprint $table) {
            $table->dropColumn(['approver_id', 'is_full_day', 'daily_hours']);
        });
    }
};
