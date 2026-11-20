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
        Schema::table('tbl_time_logging', function (Blueprint $table) {
            $table->string('shift_id', 64)->nullable()->after('employee_id')->index();
            $table->unsignedBigInteger('parent_log_id')->nullable()->after('shift_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_time_logging', function (Blueprint $table) {
            $table->dropColumn(['shift_id', 'parent_log_id']);
        });
    }
};
