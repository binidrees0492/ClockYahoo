<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('time_off_request_threads')) {
            Schema::create('time_off_request_threads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('time_off_request_id')->constrained('time_off_requests')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('type')->default('message'); // system_event, message, status_change
                $table->text('message');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('time_off_request_threads');
    }
};