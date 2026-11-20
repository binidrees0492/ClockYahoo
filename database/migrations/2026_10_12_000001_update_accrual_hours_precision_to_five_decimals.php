<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('time_off_policies') && Schema::hasColumn('time_off_policies', 'accrual_hours')) {
            Schema::table('time_off_policies', function (Blueprint $table) {
                $table->decimal('accrual_hours', 10, 5)->nullable()->change();
            });
        }

        if (Schema::hasTable('tbl_leave_types') && Schema::hasColumn('tbl_leave_types', 'accrual_rate')) {
            Schema::table('tbl_leave_types', function (Blueprint $table) {
                $table->decimal('accrual_rate', 10, 5)->default(0.00000)->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('time_off_policies') && Schema::hasColumn('time_off_policies', 'accrual_hours')) {
            Schema::table('time_off_policies', function (Blueprint $table) {
                $table->decimal('accrual_hours', 8, 2)->nullable()->change();
            });
        }

        if (Schema::hasTable('tbl_leave_types') && Schema::hasColumn('tbl_leave_types', 'accrual_rate')) {
            Schema::table('tbl_leave_types', function (Blueprint $table) {
                $table->decimal('accrual_rate', 8, 2)->default(0.00)->change();
            });
        }
    }
};
