<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Users table additions
        Schema::table('users', function (Blueprint $table) {
            $table->string('timezone')->nullable()->after('email');
            $table->string('notification_channel_preference')->default('both')->after('timezone'); // 'both', 'bell_only', 'email_only'
            $table->string('standard_shift_start')->default('08:00')->nullable()->after('notification_channel_preference');
            $table->string('standard_shift_end')->default('17:00')->nullable()->after('standard_shift_start');
            $table->decimal('standard_shift_hours', 4, 2)->default(8.00)->after('standard_shift_end');
            $table->string('avatar')->nullable()->after('color');
        });

        // 2. Schedules table additions
        Schema::table('schedules', function (Blueprint $table) {
            $table->decimal('standard_hours', 4, 2)->default(8.00)->after('end_time');
            $table->decimal('latitude', 10, 7)->nullable()->after('standard_hours');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->integer('geofence_radius')->default(200)->after('longitude'); // in meters
            $table->string('location_name')->nullable()->after('geofence_radius');
        });

        // 3. tbl_time_logging table additions
        Schema::table('tbl_time_logging', function (Blueprint $table) {
            $table->boolean('is_manual')->default(false)->after('status');
            $table->boolean('is_unusual')->default(false)->after('is_manual');
            $table->text('unusual_reason')->nullable()->after('is_unusual');
            $table->boolean('location_flagged')->default(false)->after('unusual_reason');
            $table->string('location_flag_reason')->nullable()->after('location_flagged');
            $table->decimal('clock_in_latitude', 10, 7)->nullable()->after('location_flag_reason');
            $table->decimal('clock_in_longitude', 10, 7)->nullable()->after('clock_in_latitude');
            $table->decimal('clock_out_latitude', 10, 7)->nullable()->after('clock_in_longitude');
            $table->decimal('clock_out_longitude', 10, 7)->nullable()->after('clock_out_latitude');
            $table->text('rejection_reason')->nullable()->after('approved_at');
            $table->foreignId('rejected_by')->nullable()->after('rejection_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
        });

        // 4. Notifications table archive support
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->timestamp('archived_at')->nullable()->after('read_at')->index();
            });
        }

        // 5. Company settings table
        if (!Schema::hasTable('company_settings')) {
            Schema::create('company_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_settings');

        if (Schema::hasTable('notifications') && Schema::hasColumn('notifications', 'archived_at')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropColumn('archived_at');
            });
        }

        Schema::table('tbl_time_logging', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn([
                'is_manual',
                'is_unusual',
                'unusual_reason',
                'location_flagged',
                'location_flag_reason',
                'clock_in_latitude',
                'clock_in_longitude',
                'clock_out_latitude',
                'clock_out_longitude',
                'rejection_reason',
                'rejected_at',
            ]);
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn([
                'standard_hours',
                'latitude',
                'longitude',
                'geofence_radius',
                'location_name',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'timezone',
                'notification_channel_preference',
                'standard_shift_start',
                'standard_shift_end',
                'standard_shift_hours',
                'avatar',
            ]);
        });
    }
};
