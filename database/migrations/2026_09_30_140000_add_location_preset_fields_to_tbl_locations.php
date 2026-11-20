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
        Schema::table('tbl_locations', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_locations', 'site_alias')) {
                $table->string('site_alias', 50)->nullable()->after('name');
            }
            if (!Schema::hasColumn('tbl_locations', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('site_alias');
            }
            if (!Schema::hasColumn('tbl_locations', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
            if (!Schema::hasColumn('tbl_locations', 'geofence_radius')) {
                $table->integer('geofence_radius')->default(200)->after('longitude');
            }
            if (!Schema::hasColumn('tbl_locations', 'tracking_policy')) {
                $table->string('tracking_policy', 20)->default('required')->after('geofence_radius'); // required, disabled, optional
            }
            if (!Schema::hasColumn('tbl_locations', 'address')) {
                $table->text('address')->nullable()->after('tracking_policy');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_locations', function (Blueprint $table) {
            $table->dropColumn([
                'site_alias',
                'latitude',
                'longitude',
                'geofence_radius',
                'tracking_policy',
                'address',
            ]);
        });
    }
};
