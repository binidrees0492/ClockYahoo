<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_log_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('time_log_id')->constrained('tbl_time_logging')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('time_log_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('time_log_id')->constrained('tbl_time_logging')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_log_attachments');
        Schema::dropIfExists('time_log_notes');
    }
};
