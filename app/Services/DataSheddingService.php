<?php

namespace App\Services;

use App\Models\User;
use App\Models\Assignment;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\TimeLogAttachment;
use App\Models\TimeLogNote;
use App\Models\Schedule;
use App\Models\Department;
use App\Models\Location;
use App\Models\Customer;
use App\Models\AuditLog;
use App\Models\EmailLog;
use App\Models\TimeOffRequest;
use App\Models\TimeOffRequestThread;
use App\Models\TimeOffPolicyEmployee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DataSheddingService
{
    /**
     * Get metadata and live record counts for all cleanable entities.
     */
    public static function getAvailableEntities(): array
    {
        return [
            'time_logs' => [
                'key'         => 'time_logs',
                'label'       => 'Time Logs & Hours History',
                'icon'        => 'fa-solid fa-clock-rotate-left',
                'description' => 'Wipes all tracked hours, manual timesheets, active sessions, and punch records.',
                'children'    => 'Includes switch segment child logs, attachments, and timelog notes.',
                'count'       => TimeLog::count(),
                'danger'      => 'high',
            ],
            'schedules' => [
                'key'         => 'schedules',
                'label'       => 'Schedules & Shifts',
                'icon'        => 'fa-solid fa-calendar-days',
                'description' => 'Wipes all scheduled employee shifts and calendar entries.',
                'children'    => 'Includes published shifts, shift notes, and repeat rules.',
                'count'       => Schedule::count(),
                'danger'      => 'medium',
            ],
            'time_off' => [
                'key'         => 'time_off',
                'label'       => 'Time Off Requests & Balances',
                'icon'        => 'fa-solid fa-plane-departure',
                'description' => 'Wipes all employee leave requests, approvals, rejections, and policy enrollments.',
                'children'    => 'Includes time-off request discussion threads and employee policy allocations.',
                'count'       => TimeOffRequest::count(),
                'danger'      => 'medium',
            ],
            'employees' => [
                'key'         => 'employees',
                'label'       => 'Employees & Staff Accounts',
                'icon'        => 'fa-solid fa-users',
                'description' => 'Wipes staff, employee, manager, and non-super-admin user accounts.',
                'children'    => 'Cascades to associated time logs, schedules, time-off requests, and employee profiles. (Super Admin is strictly protected).',
                'count'       => User::where('role', '!=', 'Super Admin')->count(),
                'danger'      => 'critical',
            ],
            'jobs' => [
                'key'         => 'jobs',
                'label'       => 'Jobs & Projects',
                'icon'        => 'fa-solid fa-briefcase',
                'description' => 'Wipes all job assignments and client projects.',
                'children'    => 'Cascades to all child tasks, job shift configs, job time logs, and job schedules.',
                'count'       => Assignment::count(),
                'danger'      => 'high',
            ],
            'tasks' => [
                'key'         => 'tasks',
                'label'       => 'Work Tasks',
                'icon'        => 'fa-solid fa-list-check',
                'description' => 'Wipes all work tasks and rate assignments across jobs.',
                'children'    => 'Cascades to task-specific time logs, geofences, and schedule allocations.',
                'count'       => Task::count(),
                'danger'      => 'high',
            ],
            'departments' => [
                'key'         => 'departments',
                'label'       => 'Departments',
                'icon'        => 'fa-solid fa-building-user',
                'description' => 'Wipes company department organizational structures.',
                'children'    => 'Safely clears department references from any remaining users and schedules.',
                'count'       => Department::count(),
                'danger'      => 'low',
            ],
            'locations' => [
                'key'         => 'locations',
                'label'       => 'Locations & Geofences',
                'icon'        => 'fa-solid fa-location-dot',
                'description' => 'Wipes location presets, map markers, and GPS boundaries.',
                'children'    => 'Safely clears location references from users, jobs, and tasks.',
                'count'       => Location::count(),
                'danger'      => 'low',
            ],
            'customers' => [
                'key'         => 'customers',
                'label'       => 'Customers & Invoices',
                'icon'        => 'fa-solid fa-handshake',
                'description' => 'Wipes client records, billing invoices, and service quotes.',
                'children'    => 'Cascades to all child invoices, invoice line items, and quotes.',
                'count'       => Customer::count(),
                'danger'      => 'medium',
            ],
            'logs' => [
                'key'         => 'logs',
                'label'       => 'Audit Trails & System Logs',
                'icon'        => 'fa-solid fa-clipboard-list',
                'description' => 'Wipes system audit trails, email dispatch logs, and notifications.',
                'children'    => 'Cleans audit log history, sent/failed email logs, and notification records.',
                'count'       => AuditLog::count() + EmailLog::count(),
                'danger'      => 'low',
            ],
        ];
    }

    /**
     * Shed the selected data categories while maintaining strict data integrity.
     *
     * @param array $selectedKeys Array of keys selected for shedding
     * @param int|null $currentSuperAdminId ID of currently logged in Super Admin to protect
     * @return array Summary of records deleted per entity
     */
    public static function shedData(array $selectedKeys, ?int $currentSuperAdminId = null): array
    {
        $results = [];

        DB::transaction(function () use ($selectedKeys, $currentSuperAdminId, &$results) {
            // 1. Time Logs & Activities
            if (in_array('time_logs', $selectedKeys, true)) {
                TimeLogAttachment::query()->delete();
                TimeLogNote::query()->delete();
                $count = TimeLog::query()->delete();
                $results['time_logs'] = $count;
            }

            // 2. Schedules
            if (in_array('schedules', $selectedKeys, true)) {
                $count = Schedule::query()->delete();
                $results['schedules'] = $count;
            }

            // 3. Time Off Requests
            if (in_array('time_off', $selectedKeys, true)) {
                TimeOffRequestThread::query()->delete();
                TimeOffPolicyEmployee::query()->delete();
                $count = TimeOffRequest::query()->delete();
                $results['time_off'] = $count;
            }

            // 4. Employees & Non-Super-Admin Accounts
            if (in_array('employees', $selectedKeys, true)) {
                // Determine target employee IDs (strictly preserving Super Admin)
                $employeeQuery = User::where('role', '!=', 'Super Admin')
                    ->where('role', '!=', 'super_admin')
                    ->where('role', '!=', 'SuperAdmin');

                if ($currentSuperAdminId) {
                    $employeeQuery->where('id', '!=', $currentSuperAdminId);
                }

                $employeeIds = $employeeQuery->pluck('id')->toArray();

                if (!empty($employeeIds)) {
                    // Cascade child records for these employees
                    TimeLogAttachment::whereIn('user_id', $employeeIds)->delete();
                    TimeLogNote::whereIn('user_id', $employeeIds)->delete();
                    TimeLog::whereIn('employee_id', $employeeIds)->delete();
                    Schedule::whereIn('employee_id', $employeeIds)->delete();
                    TimeOffRequestThread::whereIn('user_id', $employeeIds)->delete();
                    TimeOffRequest::whereIn('employee_id', $employeeIds)->delete();
                    TimeOffPolicyEmployee::whereIn('employee_id', $employeeIds)->delete();

                    // Unlink direct reports
                    User::whereIn('manager_id', $employeeIds)->update(['manager_id' => null]);

                    // Unlink assignment leads if column exists
                    if (\Illuminate\Support\Facades\Schema::hasColumn('tbl_assignments', 'employee_id')) {
                        Assignment::whereIn('employee_id', $employeeIds)->update(['employee_id' => null]);
                    }

                    $count = User::whereIn('id', $employeeIds)->delete();
                    $results['employees'] = $count;
                } else {
                    $results['employees'] = 0;
                }
            }

            // 5. Tasks (if selected before jobs or independently)
            if (in_array('tasks', $selectedKeys, true)) {
                $taskIds = Task::pluck('id')->toArray();
                if (!empty($taskIds)) {
                    TimeLog::whereIn('task_id', $taskIds)->delete();
                    Schedule::whereIn('task_id', $taskIds)->delete();
                    $count = Task::query()->delete();
                    $results['tasks'] = $count;
                } else {
                    $results['tasks'] = 0;
                }
            }

            // 6. Jobs / Assignments
            if (in_array('jobs', $selectedKeys, true)) {
                $jobIds = Assignment::pluck('id')->toArray();
                if (!empty($jobIds)) {
                    // Child tasks belonging to these jobs
                    $childTaskIds = Task::whereIn('assignment_id', $jobIds)->pluck('id')->toArray();
                    if (!empty($childTaskIds)) {
                        TimeLog::whereIn('task_id', $childTaskIds)->delete();
                        Schedule::whereIn('task_id', $childTaskIds)->delete();
                        Task::whereIn('id', $childTaskIds)->delete();
                    }

                    TimeLog::whereIn('assignment_id', $jobIds)->delete();
                    Schedule::whereIn('assignment_id', $jobIds)->delete();

                    $count = Assignment::query()->delete();
                    $results['jobs'] = $count;
                } else {
                    $results['jobs'] = 0;
                }
            }

            // 7. Departments
            if (in_array('departments', $selectedKeys, true)) {
                if (Schema::hasColumn('users', 'department_id')) {
                    User::whereNotNull('department_id')->update(['department_id' => null]);
                }
                if (Schema::hasColumn('schedules', 'department_id')) {
                    Schedule::whereNotNull('department_id')->update(['department_id' => null]);
                }
                $count = Department::query()->delete();
                $results['departments'] = $count;
            }

            // 8. Locations
            if (in_array('locations', $selectedKeys, true)) {
                if (Schema::hasColumn('users', 'location_id')) {
                    User::whereNotNull('location_id')->update(['location_id' => null]);
                }
                if (Schema::hasColumn('tbl_tasks', 'location_id')) {
                    Task::whereNotNull('location_id')->update(['location_id' => null]);
                }
                if (Schema::hasColumn('tbl_assignments', 'location_id')) {
                    Assignment::whereNotNull('location_id')->update(['location_id' => null]);
                }
                if (Schema::hasColumn('tbl_tasks', 'latitude')) {
                    Task::whereNotNull('latitude')->update(['latitude' => null, 'longitude' => null]);
                }
                if (Schema::hasColumn('schedules', 'location_id')) {
                    Schedule::whereNotNull('location_id')->update(['location_id' => null]);
                }
                if (Schema::hasColumn('schedules', 'location_name')) {
                    Schedule::whereNotNull('location_name')->update(['location_name' => null]);
                }
                $count = Location::query()->delete();
                $results['locations'] = $count;
            }

            // 9. Customers & Billing
            if (in_array('customers', $selectedKeys, true)) {
                if (Schema::hasColumn('tbl_assignments', 'customer_id')) {
                    Assignment::whereNotNull('customer_id')->update(['customer_id' => null]);
                }
                if (class_exists(\App\Models\Invoice::class) && Schema::hasTable('invoices')) {
                    \App\Models\Invoice::query()->delete();
                }
                if (class_exists(\App\Models\Quote::class) && Schema::hasTable('quotes')) {
                    \App\Models\Quote::query()->delete();
                }
                $count = Customer::query()->delete();
                $results['customers'] = $count;
            }

            // 10. Audit & System Logs
            if (in_array('logs', $selectedKeys, true)) {
                $auditCount = AuditLog::query()->delete();
                $emailCount = EmailLog::query()->delete();
                if (Schema::hasTable('notifications')) {
                    DB::table('notifications')->delete();
                }
                $results['logs'] = $auditCount + $emailCount;
            }
        });

        // Record an audit log for the production shedding action
        if ($currentSuperAdminId) {
            $admin = User::find($currentSuperAdminId);
            AuditLog::record(
                "Super Admin {$admin?->name} executed Production Data Shedding.",
                ['shed_entities' => $selectedKeys, 'results' => $results],
                null,
                'Purge'
            );
        }

        return $results;
    }
}
