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
        Schema::table('tbl_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_tasks', 'assignment_id')) {
                $table->foreignId('assignment_id')
                    ->nullable()
                    ->constrained('tbl_assignments')
                    ->nullOnDelete();
            }
        });

        Schema::table('tbl_time_logging', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_time_logging', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->index();
            }
            if (!Schema::hasColumn('tbl_time_logging', 'archived_at')) {
                $table->timestamp('archived_at')->nullable();
            }
            if (!Schema::hasColumn('tbl_time_logging', 'archived_by')) {
                $table->foreignId('archived_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_time_logging', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_time_logging', 'archived_by')) {
                $table->dropForeign(['archived_by']);
                $table->dropColumn('archived_by');
            }
            if (Schema::hasColumn('tbl_time_logging', 'archived_at')) {
                $table->dropColumn('archived_at');
            }
            if (Schema::hasColumn('tbl_time_logging', 'is_archived')) {
                $table->dropColumn('is_archived');
            }
        });

        Schema::table('tbl_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_tasks', 'assignment_id')) {
                $table->dropForeign(['assignment_id']);
                $table->dropColumn('assignment_id');
            }
        });
    }
};
