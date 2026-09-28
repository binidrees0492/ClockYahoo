<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_off_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('time_off_requests', 'paid_hours')) {
                $table->decimal('paid_hours', 8, 2)->default(0.00)->after('hours');
            }
            if (!Schema::hasColumn('time_off_requests', 'unpaid_hours')) {
                $table->decimal('unpaid_hours', 8, 2)->default(0.00)->after('paid_hours');
            }
        });
    }

    public function down(): void
    {
        Schema::table('time_off_requests', function (Blueprint $table) {
            $table->dropColumn(['paid_hours', 'unpaid_hours']);
        });
    }
};