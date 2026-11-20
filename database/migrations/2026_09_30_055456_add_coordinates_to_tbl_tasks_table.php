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
        if (Schema::hasTable('tbl_tasks')) {
            Schema::table('tbl_tasks', function (Blueprint $table) {
                if (!Schema::hasColumn('tbl_tasks', 'latitude')) {
                    $table->decimal('latitude', 10, 7)->nullable()->after('geofence_radius');
                }
                if (!Schema::hasColumn('tbl_tasks', 'longitude')) {
                    $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tbl_tasks')) {
            Schema::table('tbl_tasks', function (Blueprint $table) {
                if (Schema::hasColumn('tbl_tasks', 'latitude')) {
                    $table->dropColumn('latitude');
                }
                if (Schema::hasColumn('tbl_tasks', 'longitude')) {
                    $table->dropColumn('longitude');
                }
            });
        }
    }
};
