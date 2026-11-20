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
        Schema::table('tbl_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_assignments', 'location_id')) {
                $table->foreignId('location_id')
                    ->nullable()
                    ->constrained('tbl_locations')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('tbl_assignments', 'gps_policy')) {
                $table->string('gps_policy', 50)->default('none');
            }
            if (!Schema::hasColumn('tbl_assignments', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable();
            }
            if (!Schema::hasColumn('tbl_assignments', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable();
            }
            if (!Schema::hasColumn('tbl_assignments', 'geofence_radius')) {
                $table->integer('geofence_radius')->default(200);
            }
        });

        Schema::table('tbl_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_tasks', 'location_id')) {
                $table->foreignId('location_id')
                    ->nullable()
                    ->constrained('tbl_locations')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_tasks', 'location_id')) {
                $table->dropForeign(['location_id']);
                $table->dropColumn('location_id');
            }
        });

        Schema::table('tbl_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_assignments', 'location_id')) {
                $table->dropForeign(['location_id']);
                $table->dropColumn('location_id');
            }
            if (Schema::hasColumn('tbl_assignments', 'gps_policy')) {
                $table->dropColumn('gps_policy');
            }
            if (Schema::hasColumn('tbl_assignments', 'latitude')) {
                $table->dropColumn('latitude');
            }
            if (Schema::hasColumn('tbl_assignments', 'longitude')) {
                $table->dropColumn('longitude');
            }
            if (Schema::hasColumn('tbl_assignments', 'geofence_radius')) {
                $table->dropColumn('geofence_radius');
            }
        });
    }
};
