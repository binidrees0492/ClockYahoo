<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_time_logging', function (Blueprint $table) {
            $table->boolean('action_requested')->default(false)->after('rejection_reason');
            $table->text('action_requested_notes')->nullable()->after('action_requested');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_time_logging', function (Blueprint $table) {
            $table->dropColumn(['action_requested', 'action_requested_notes']);
        });
    }
};
