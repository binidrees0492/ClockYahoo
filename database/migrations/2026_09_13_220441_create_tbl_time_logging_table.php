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
        Schema::create('tbl_time_logging', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id'); // Maps to your users table
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('task_id');
            $table->timestamp('clock_in')->nullable();
            $table->timestamp('clock_out')->nullable();
            $table->integer('duration_minutes')->default(0);
            $table->string('status')->default('working');
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_time_logging');
    }
};
