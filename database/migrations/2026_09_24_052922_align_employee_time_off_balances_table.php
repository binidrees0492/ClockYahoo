<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_leave_balances')) {
            Schema::create('tbl_leave_balances', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('leave_type_id');
                $table->date('hire_date')->nullable();
                $table->decimal('balance_hours', 8, 2)->default(0.00);
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        } else {
            Schema::table('tbl_leave_balances', function (Blueprint $table) {
                if (!Schema::hasColumn('tbl_leave_balances', 'hire_date')) {
                    $table->date('hire_date')->nullable()->after('leave_type_id');
                }
                if (!Schema::hasColumn('tbl_leave_balances', 'balance_hours')) {
                    $table->decimal('balance_hours', 8, 2)->default(0.00)->after('hire_date');
                }
            });
        }
    }

    public function down(): void
    {
        // Keep existing table intact if Rolling back
    }
};
