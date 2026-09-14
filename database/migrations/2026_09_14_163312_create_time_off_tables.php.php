<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_off_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('policy_type')->default(0); // 0=PTO, 1=Sick, 2=Unpaid
            $table->integer('accrual_method')->default(0); // 0=pay period, 1=first of year, 2=anniversary, 3=hrs-based
            $table->boolean('is_time_off_limit')->default(false);
            $table->decimal('accrual_hours', 8, 2)->nullable();
            $table->boolean('is_waiting_period')->default(false);
            $table->integer('waiting_period_days')->nullable();
            $table->boolean('is_carryover_limit')->default(false);
            $table->integer('max_carryover_hours')->nullable();
            $table->integer('max_balance_hours')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('time_off_policy_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained('time_off_policies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->date('hire_date')->nullable();
            $table->decimal('hours_remaining', 10, 2)->default(0);
            $table->timestamps();
            $table->unique(['policy_id', 'employee_id']);
        });

        Schema::create('time_off_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('policy_id')->nullable()->constrained('time_off_policies')->nullOnDelete();
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            $table->decimal('hours', 8, 2)->default(0);
            $table->text('reason')->nullable();
            $table->string('status')->default('pending'); // pending | approved | denied | cancelled
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_off_requests');
        Schema::dropIfExists('time_off_policy_employees');
        Schema::dropIfExists('time_off_policies');
    }
};
