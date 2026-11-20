<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            if (!Schema::hasColumn('schedules', 'status')) {
                $table->string('status', 20)->default('published')->after('shift_date');
            }
            if (!Schema::hasColumn('schedules', 'is_published')) {
                $table->boolean('is_published')->default(true)->after('status');
            }
        });

        // Ensure all existing rows are set to published
        DB::table('schedules')->whereNull('status')->orWhere('status', '')->update([
            'status' => 'published',
            'is_published' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            if (Schema::hasColumn('schedules', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('schedules', 'is_published')) {
                $table->dropColumn('is_published');
            }
        });
    }
};
