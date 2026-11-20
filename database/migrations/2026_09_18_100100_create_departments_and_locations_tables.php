<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_departments')) {
            Schema::create('tbl_departments', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tbl_locations')) {
            Schema::create('tbl_locations', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_departments');
        Schema::dropIfExists('tbl_locations');
    }
};
