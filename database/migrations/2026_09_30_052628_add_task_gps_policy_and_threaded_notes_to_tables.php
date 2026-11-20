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
        // Add GPS tracking policy to tasks (off by default)
        if (Schema::hasTable('tbl_tasks')) {
            Schema::table('tbl_tasks', function (Blueprint $table) {
                if (!Schema::hasColumn('tbl_tasks', 'gps_policy')) {
                    $table->string('gps_policy')->default('none')->after('status'); // 'none', 'flag', 'mandatory'
                }
                if (!Schema::hasColumn('tbl_tasks', 'geofence_radius')) {
                    $table->integer('geofence_radius')->default(200)->after('gps_policy');
                }
            });
        }

        // Add parent_id to time_log_notes for threaded comments and replies
        if (Schema::hasTable('time_log_notes')) {
            Schema::table('time_log_notes', function (Blueprint $table) {
                if (!Schema::hasColumn('time_log_notes', 'parent_id')) {
                    $table->unsignedBigInteger('parent_id')->nullable()->after('user_id');
                }
            });
        }

        // Add detailed notification settings to users
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'detailed_notification_settings')) {
                    $table->json('detailed_notification_settings')->nullable()->after('notification_channel_preference');
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
                if (Schema::hasColumn('tbl_tasks', 'gps_policy')) {
                    $table->dropColumn('gps_policy');
                }
                if (Schema::hasColumn('tbl_tasks', 'geofence_radius')) {
                    $table->dropColumn('geofence_radius');
                }
            });
        }

        if (Schema::hasTable('time_log_notes')) {
            Schema::table('time_log_notes', function (Blueprint $table) {
                if (Schema::hasColumn('time_log_notes', 'parent_id')) {
                    $table->dropColumn('parent_id');
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'detailed_notification_settings')) {
                    $table->dropColumn('detailed_notification_settings');
                }
            });
        }
    }
};
