<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('notification_rules')) {
            Schema::create('notification_rules', function (Blueprint $table) {
                $table->id();
                $table->string('event_key')->unique();
                $table->string('name');
                $table->string('category'); // Scheduling, Time Tracking, Timesheet Approvals, Time Off, Compliance & Alerts, System
                $table->text('description')->nullable();
                $table->boolean('person_involved_enabled')->default(true);
                $table->json('person_involved_channels')->nullable(); // ['in_app', 'toast', 'email']
                $table->json('roles_config')->nullable(); // {'Super Admin': {...}, 'Administrator': {...}, ...}
                $table->string('manager_scope')->default('department'); // department, direct_reports, all, none
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'manager_notification_settings')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('manager_notification_settings')->nullable()->after('detailed_notification_settings');
            });
        }

        // Seed initial default notification rules
        $defaultRules = [
            [
                'event_key'               => 'shift_assigned',
                'name'                    => 'Shift Assigned / Scheduled',
                'category'                => 'Scheduling',
                'description'             => 'Triggered when a shift is assigned to an employee or created on the schedule board.',
                'person_involved_enabled' => true,
                'person_involved_channels' => json_encode(['in_app', 'toast', 'email']),
                'manager_scope'           => 'department',
                'roles_config'            => json_encode([
                    'Super Admin'   => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => false, 'scope' => 'all'],
                    'Administrator' => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => false, 'scope' => 'all'],
                    'HR Manager'    => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => false, 'scope' => 'all'],
                    'Manager'       => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => false, 'scope' => 'department'],
                    'Employee'      => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => true, 'scope' => 'own'],
                ]),
                'is_active'               => true,
            ],
            [
                'event_key'               => 'shift_updated',
                'name'                    => 'Shift Rescheduled / Updated',
                'category'                => 'Scheduling',
                'description'             => 'Triggered when an existing shift date, hours, job, task, or location is updated.',
                'person_involved_enabled' => true,
                'person_involved_channels' => json_encode(['in_app', 'toast', 'email']),
                'manager_scope'           => 'department',
                'roles_config'            => json_encode([
                    'Super Admin'   => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => false, 'scope' => 'all'],
                    'Administrator' => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => false, 'scope' => 'all'],
                    'HR Manager'    => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => false, 'scope' => 'all'],
                    'Manager'       => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => false, 'scope' => 'department'],
                    'Employee'      => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => true, 'scope' => 'own'],
                ]),
                'is_active'               => true,
            ],
            [
                'event_key'               => 'shift_published',
                'name'                    => 'Weekly Schedule Published',
                'category'                => 'Scheduling',
                'description'             => 'Triggered when drafted shifts or a full week schedule are published for staff view.',
                'person_involved_enabled' => true,
                'person_involved_channels' => json_encode(['in_app', 'toast', 'email']),
                'manager_scope'           => 'department',
                'roles_config'            => json_encode([
                    'Super Admin'   => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => false, 'scope' => 'all'],
                    'Administrator' => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => false, 'scope' => 'all'],
                    'HR Manager'    => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => false, 'scope' => 'all'],
                    'Manager'       => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => false, 'scope' => 'department'],
                    'Employee'      => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => true, 'scope' => 'own'],
                ]),
                'is_active'               => true,
            ],
            [
                'event_key'               => 'clock_in',
                'name'                    => 'Employee Clock In Activity',
                'category'                => 'Time Tracking',
                'description'             => 'Triggered when an employee clocks into a job and task for work.',
                'person_involved_enabled' => true,
                'person_involved_channels' => json_encode(['in_app', 'toast']),
                'manager_scope'           => 'department',
                'roles_config'            => json_encode([
                    'Super Admin'   => ['enabled' => false, 'in_app' => false, 'toast' => false, 'email' => false, 'scope' => 'none'],
                    'Administrator' => ['enabled' => false, 'in_app' => false, 'toast' => false, 'email' => false, 'scope' => 'none'],
                    'HR Manager'    => ['enabled' => false, 'in_app' => false, 'toast' => false, 'email' => false, 'scope' => 'none'],
                    'Manager'       => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => false, 'scope' => 'department'],
                    'Employee'      => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => false, 'scope' => 'own'],
                ]),
                'is_active'               => true,
            ],
            [
                'event_key'               => 'clock_out',
                'name'                    => 'Employee Clock Out Activity',
                'category'                => 'Time Tracking',
                'description'             => 'Triggered when an employee clocks out and finishes their work duration.',
                'person_involved_enabled' => true,
                'person_involved_channels' => json_encode(['in_app', 'toast']),
                'manager_scope'           => 'department',
                'roles_config'            => json_encode([
                    'Super Admin'   => ['enabled' => false, 'in_app' => false, 'toast' => false, 'email' => false, 'scope' => 'none'],
                    'Administrator' => ['enabled' => false, 'in_app' => false, 'toast' => false, 'email' => false, 'scope' => 'none'],
                    'HR Manager'    => ['enabled' => false, 'in_app' => false, 'toast' => false, 'email' => false, 'scope' => 'none'],
                    'Manager'       => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => false, 'scope' => 'department'],
                    'Employee'      => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => false, 'scope' => 'own'],
                ]),
                'is_active'               => true,
            ],
            [
                'event_key'               => 'time_submitted',
                'name'                    => 'Timesheet / Time Log Submitted',
                'category'                => 'Timesheet Approvals',
                'description'             => 'Triggered when an employee logs hours or submits manual time for supervisor approval.',
                'person_involved_enabled' => true,
                'person_involved_channels' => json_encode(['in_app', 'toast']),
                'manager_scope'           => 'department',
                'roles_config'            => json_encode([
                    'Super Admin'   => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => false, 'scope' => 'all'],
                    'Administrator' => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => false, 'scope' => 'all'],
                    'HR Manager'    => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => true, 'scope' => 'all'],
                    'Manager'       => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => true, 'scope' => 'department'],
                    'Employee'      => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => false, 'scope' => 'own'],
                ]),
                'is_active'               => true,
            ],
            [
                'event_key'               => 'timesheet_approved',
                'name'                    => 'Timesheet Approved',
                'category'                => 'Timesheet Approvals',
                'description'             => 'Triggered when a supervisor or manager approves an employee\'s recorded hours.',
                'person_involved_enabled' => true,
                'person_involved_channels' => json_encode(['in_app', 'toast', 'email']),
                'manager_scope'           => 'department',
                'roles_config'            => json_encode([
                    'Super Admin'   => ['enabled' => false, 'in_app' => false, 'toast' => false, 'email' => false, 'scope' => 'none'],
                    'Administrator' => ['enabled' => false, 'in_app' => false, 'toast' => false, 'email' => false, 'scope' => 'none'],
                    'HR Manager'    => ['enabled' => false, 'in_app' => false, 'toast' => false, 'email' => false, 'scope' => 'none'],
                    'Manager'       => ['enabled' => true, 'in_app' => true, 'toast' => false, 'email' => false, 'scope' => 'department'],
                    'Employee'      => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => true, 'scope' => 'own'],
                ]),
                'is_active'               => true,
            ],
            [
                'event_key'               => 'timesheet_disapproved',
                'name'                    => 'Timesheet Rejected / Disapproved',
                'category'                => 'Timesheet Approvals',
                'description'             => 'Triggered when hours are rejected with explanation or request for clarification.',
                'person_involved_enabled' => true,
                'person_involved_channels' => json_encode(['in_app', 'toast', 'email']),
                'manager_scope'           => 'department',
                'roles_config'            => json_encode([
                    'Super Admin'   => ['enabled' => false, 'in_app' => false, 'toast' => false, 'email' => false, 'scope' => 'none'],
                    'Administrator' => ['enabled' => false, 'in_app' => false, 'toast' => false, 'email' => false, 'scope' => 'none'],
                    'HR Manager'    => ['enabled' => true, 'in_app' => true, 'toast' => false, 'email' => false, 'scope' => 'all'],
                    'Manager'       => ['enabled' => true, 'in_app' => true, 'toast' => false, 'email' => false, 'scope' => 'department'],
                    'Employee'      => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => true, 'scope' => 'own'],
                ]),
                'is_active'               => true,
            ],
            [
                'event_key'               => 'time_off_submitted',
                'name'                    => 'Time Off Request Submitted',
                'category'                => 'Time Off',
                'description'             => 'Triggered when an employee submits vacation, PTO, or medical leave request.',
                'person_involved_enabled' => true,
                'person_involved_channels' => json_encode(['in_app', 'toast']),
                'manager_scope'           => 'department',
                'roles_config'            => json_encode([
                    'Super Admin'   => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => false, 'scope' => 'all'],
                    'Administrator' => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => false, 'scope' => 'all'],
                    'HR Manager'    => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => true, 'scope' => 'all'],
                    'Manager'       => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => true, 'scope' => 'department'],
                    'Employee'      => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => false, 'scope' => 'own'],
                ]),
                'is_active'               => true,
            ],
            [
                'event_key'               => 'time_off_decided',
                'name'                    => 'Time Off Request Decided',
                'category'                => 'Time Off',
                'description'             => 'Triggered when a supervisor approves or denies a submitted time-off request.',
                'person_involved_enabled' => true,
                'person_involved_channels' => json_encode(['in_app', 'toast', 'email']),
                'manager_scope'           => 'department',
                'roles_config'            => json_encode([
                    'Super Admin'   => ['enabled' => false, 'in_app' => false, 'toast' => false, 'email' => false, 'scope' => 'none'],
                    'Administrator' => ['enabled' => false, 'in_app' => false, 'toast' => false, 'email' => false, 'scope' => 'none'],
                    'HR Manager'    => ['enabled' => true, 'in_app' => true, 'toast' => false, 'email' => false, 'scope' => 'all'],
                    'Manager'       => ['enabled' => true, 'in_app' => true, 'toast' => false, 'email' => false, 'scope' => 'department'],
                    'Employee'      => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => true, 'scope' => 'own'],
                ]),
                'is_active'               => true,
            ],
            [
                'event_key'               => 'unusual_activity',
                'name'                    => 'Overtime & Unusual Activity Alert',
                'category'                => 'Compliance & Alerts',
                'description'             => 'Triggered when work duration exceeds scheduled shift hours or over-hours exceptions.',
                'person_involved_enabled' => true,
                'person_involved_channels' => json_encode(['in_app', 'toast', 'email']),
                'manager_scope'           => 'department',
                'roles_config'            => json_encode([
                    'Super Admin'   => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => false, 'scope' => 'all'],
                    'Administrator' => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => true, 'scope' => 'all'],
                    'HR Manager'    => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => true, 'scope' => 'all'],
                    'Manager'       => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => true, 'scope' => 'department'],
                    'Employee'      => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => true, 'scope' => 'own'],
                ]),
                'is_active'               => true,
            ],
            [
                'event_key'               => 'geofence_breach',
                'name'                    => 'Geofence / Location Policy Discrepancy',
                'category'                => 'Compliance & Alerts',
                'description'             => 'Triggered when clock-in coordinates fall outside authorized location radius or GPS is missing.',
                'person_involved_enabled' => true,
                'person_involved_channels' => json_encode(['in_app', 'toast']),
                'manager_scope'           => 'department',
                'roles_config'            => json_encode([
                    'Super Admin'   => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => false, 'scope' => 'all'],
                    'Administrator' => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => false, 'scope' => 'all'],
                    'HR Manager'    => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => true, 'scope' => 'all'],
                    'Manager'       => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => true, 'scope' => 'department'],
                    'Employee'      => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => false, 'scope' => 'own'],
                ]),
                'is_active'               => true,
            ],
            [
                'event_key'               => 'system_alert',
                'name'                    => 'System Announcement & Policy Notice',
                'category'                => 'System',
                'description'             => 'Triggered for system-wide notices, policy updates, and administrative communications.',
                'person_involved_enabled' => true,
                'person_involved_channels' => json_encode(['in_app', 'toast', 'email']),
                'manager_scope'           => 'all',
                'roles_config'            => json_encode([
                    'Super Admin'   => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => false, 'scope' => 'all'],
                    'Administrator' => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => false, 'scope' => 'all'],
                    'HR Manager'    => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => false, 'scope' => 'all'],
                    'Manager'       => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => false, 'scope' => 'all'],
                    'Employee'      => ['enabled' => true, 'in_app' => true, 'toast' => true, 'email' => false, 'scope' => 'own'],
                ]),
                'is_active'               => true,
            ],
        ];

        foreach ($defaultRules as $rule) {
            $rule['created_at'] = now();
            $rule['updated_at'] = now();
            DB::table('notification_rules')->updateOrInsert(
                ['event_key' => $rule['event_key']],
                $rule
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_rules');

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'manager_notification_settings')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('manager_notification_settings');
            });
        }
    }
};
