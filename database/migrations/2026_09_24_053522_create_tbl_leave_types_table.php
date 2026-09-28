<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_leave_types')) {
            Schema::create('tbl_leave_types', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('type')->default('paid'); // paid, unpaid
                $table->decimal('accrual_rate', 8, 2)->default(0.00);
                $table->decimal('max_hours', 8, 2)->default(0.00);
                $table->string('status')->default('Active');
                $table->timestamps();
            });

            // Insert initial default ClockShark policies
            DB::table('tbl_leave_types')->insert([
                ['name' => 'Standard Accrual Plan', 'type' => 'paid', 'accrual_rate' => 1.50, 'max_hours' => 120.00, 'status' => 'Active', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Paid Time Off (PTO)',   'type' => 'paid', 'accrual_rate' => 2.00, 'max_hours' => 160.00, 'status' => 'Active', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Sick Leave',             'type' => 'paid', 'accrual_rate' => 1.00, 'max_hours' => 80.00,  'status' => 'Active', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Vacation Plan',          'type' => 'paid', 'accrual_rate' => 1.50, 'max_hours' => 120.00, 'status' => 'Active', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Bereavement Leave',      'type' => 'paid', 'accrual_rate' => 0.00, 'max_hours' => 24.00,  'status' => 'Active', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_leave_types');
    }
};
