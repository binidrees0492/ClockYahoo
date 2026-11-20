<?php

namespace App\Services;

use App\Models\User;
use App\Models\Assignment;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\TimeLogNote;
use App\Models\TimeLogAttachment;
use App\Models\Schedule;
use App\Models\Department;
use App\Models\Location;
use App\Models\Customer;
use App\Models\TimeOffPolicy;
use App\Models\TimeOffRequest;
use App\Models\TimeOffPolicyEmployee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;

class InactiveRecordsHubService
{
    /**
     * Normalized list of tables managed by the Super Admin Database Hub.
     */
    public static function getTablesSummary(): array
    {
        $employeesTotal = User::where('role', '!=', 'Super Admin')->count();
        $employeesInactive = User::where('role', '!=', 'Super Admin')
            ->whereIn('status', ['Inactive', 'inactive', 'deleted'])
            ->count();

        $jobsTotal = Assignment::count();
        $jobsInactive = Assignment::whereIn('status', ['inactive', 'Inactive', 'archived', 'Archived'])->count();

        $tasksTotal = Task::count();
        $tasksInactive = Task::whereIn('status', ['inactive', 'Inactive', 'archived', 'Archived'])->count();

        $logsTotal = TimeLog::count();
        $logsInactive = TimeLog::where('is_archived', true)->count();

        $schedulesTotal = Schedule::count();
        $schedulesInactive = Schedule::whereIn('status', ['cancelled', 'inactive', 'rejected'])->count();

        $deptsTotal = Department::count();
        $deptsInactive = Department::whereIn('status', ['Inactive', 'inactive'])->count();

        $locsTotal = Location::count();
        $locsInactive = Location::whereIn('status', ['Inactive', 'inactive'])->count();

        $custsTotal = Customer::count();
        $custsInactive = Customer::where('active', false)->count();

        $policiesTotal = TimeOffPolicy::count();
        $policiesInactive = TimeOffPolicy::where('active', false)->count();

        return [
            'employees' => [
                'key'            => 'employees',
                'label'          => 'Employees & Staff',
                'model'          => User::class,
                'table_name'     => 'users',
                'icon'           => 'fa-solid fa-users',
                'color'          => 'blue',
                'description'    => 'Staff accounts, managers, and employee profiles.',
                'total_count'    => $employeesTotal,
                'active_count'   => max(0, $employeesTotal - $employeesInactive),
                'inactive_count' => $employeesInactive,
                'count'          => $employeesInactive,
                'children_desc'  => 'Time Logs, Schedules, Time Off Requests, Direct Reports',
            ],
            'jobs' => [
                'key'            => 'jobs',
                'label'          => 'Jobs & Projects',
                'model'          => Assignment::class,
                'table_name'     => 'tbl_assignments',
                'icon'           => 'fa-solid fa-briefcase',
                'color'          => 'indigo',
                'description'    => 'Job assignments and client projects.',
                'total_count'    => $jobsTotal,
                'active_count'   => max(0, $jobsTotal - $jobsInactive),
                'inactive_count' => $jobsInactive,
                'count'          => $jobsInactive,
                'children_desc'  => 'Child Tasks, Linked Time Logs, Assigned Shifts',
            ],
            'tasks' => [
                'key'            => 'tasks',
                'label'          => 'Tasks & Sub-tasks',
                'model'          => Task::class,
                'table_name'     => 'tbl_tasks',
                'icon'           => 'fa-solid fa-list-check',
                'color'          => 'teal',
                'description'    => 'Work tasks belonging to parent jobs.',
                'total_count'    => $tasksTotal,
                'active_count'   => max(0, $tasksTotal - $tasksInactive),
                'inactive_count' => $tasksInactive,
                'count'          => $tasksInactive,
                'children_desc'  => 'Task Time Logs, Assigned Schedules',
            ],
            'time_logs' => [
                'key'            => 'time_logs',
                'label'          => 'Time Sheets & Logs',
                'model'          => TimeLog::class,
                'table_name'     => 'tbl_time_logging',
                'icon'           => 'fa-solid fa-clock-rotate-left',
                'color'          => 'amber',
                'description'    => 'Time punch records and tracked working hours.',
                'total_count'    => $logsTotal,
                'active_count'   => max(0, $logsTotal - $logsInactive),
                'inactive_count' => $logsInactive,
                'count'          => $logsInactive,
                'children_desc'  => 'Switch Segments, Time Log Notes, Attachments',
            ],
            'schedules' => [
                'key'            => 'schedules',
                'label'          => 'Schedules & Shifts',
                'model'          => Schedule::class,
                'table_name'     => 'schedules',
                'icon'           => 'fa-solid fa-calendar-days',
                'color'          => 'violet',
                'description'    => 'Assigned shifts and scheduled work dates.',
                'total_count'    => $schedulesTotal,
                'active_count'   => max(0, $schedulesTotal - $schedulesInactive),
                'inactive_count' => $schedulesInactive,
                'count'          => $schedulesInactive,
                'children_desc'  => 'Assigned Employee Shift Hours',
            ],
            'departments' => [
                'key'            => 'departments',
                'label'          => 'Departments',
                'model'          => Department::class,
                'table_name'     => 'tbl_departments',
                'icon'           => 'fa-solid fa-building',
                'color'          => 'purple',
                'description'    => 'Organizational department groupings.',
                'total_count'    => $deptsTotal,
                'active_count'   => max(0, $deptsTotal - $deptsInactive),
                'inactive_count' => $deptsInactive,
                'count'          => $deptsInactive,
                'children_desc'  => 'Assigned Staff Members',
            ],
            'locations' => [
                'key'            => 'locations',
                'label'          => 'Locations & Sites',
                'model'          => Location::class,
                'table_name'     => 'tbl_locations',
                'icon'           => 'fa-solid fa-location-dot',
                'color'          => 'rose',
                'description'    => 'Physical work sites and geofences.',
                'total_count'    => $locsTotal,
                'active_count'   => max(0, $locsTotal - $locsInactive),
                'inactive_count' => $locsInactive,
                'count'          => $locsInactive,
                'children_desc'  => 'Assigned Staff, Tasks, Schedules',
            ],
            'customers' => [
                'key'            => 'customers',
                'label'          => 'Customers & Clients',
                'model'          => Customer::class,
                'table_name'     => 'customers',
                'icon'           => 'fa-solid fa-handshake',
                'color'          => 'emerald',
                'description'    => 'Client records and billing accounts.',
                'total_count'    => $custsTotal,
                'active_count'   => max(0, $custsTotal - $custsInactive),
                'inactive_count' => $custsInactive,
                'count'          => $custsInactive,
                'children_desc'  => 'Jobs, Quotes, Invoices',
            ],
            'time_off_policies' => [
                'key'            => 'time_off_policies',
                'label'          => 'Time Off Policies',
                'model'          => TimeOffPolicy::class,
                'table_name'     => 'time_off_policies',
                'icon'           => 'fa-solid fa-calendar-xmark',
                'color'          => 'sky',
                'description'    => 'Leave rules and time-off policies.',
                'total_count'    => $policiesTotal,
                'active_count'   => max(0, $policiesTotal - $policiesInactive),
                'inactive_count' => $policiesInactive,
                'count'          => $policiesInactive,
                'children_desc'  => 'Employee Policy Enrollments, Time Off Requests',
            ],
        ];
    }

    /**
     * Strict Orphan Prevention Rule:
     * Check whether a record can be safely restored without creating orphan records.
     * If any required parent record is missing or inactive/deleted, restoration is strictly blocked.
     */
    public static function canRestoreRecord(string $tableKey, int $id): array
    {
        switch ($tableKey) {
            case 'tasks':
                $task = Task::find($id);
                if (!$task) {
                    return ['allowed' => false, 'reason' => 'Task not found.', 'blocking_parent' => null];
                }

                if ($task->assignment_id) {
                    $parentJob = Assignment::find($task->assignment_id);
                    if (!$parentJob) {
                        return [
                            'allowed' => false,
                            'reason'  => "Cannot restore Task '{$task->name}': The parent Job (ID #{$task->assignment_id}) was permanently deleted. Restoring this task would create an orphan record.",
                            'blocking_parent' => [
                                'type'   => 'Job',
                                'id'     => $task->assignment_id,
                                'status' => 'Permanently Deleted',
                                'name'   => "Missing Job #{$task->assignment_id}",
                            ],
                        ];
                    }

                    if (in_array(strtolower((string) $parentJob->status), ['inactive', 'archived', 'deleted'], true)) {
                        return [
                            'allowed' => false,
                            'reason'  => "Cannot restore Task '{$task->name}': Its parent Job (#{$parentJob->id} '{$parentJob->name}') is currently inactive/archived. You must restore the parent Job first to prevent orphan records.",
                            'blocking_parent' => [
                                'type'   => 'Job',
                                'id'     => $parentJob->id,
                                'status' => ucfirst($parentJob->status ?? 'Inactive'),
                                'name'   => $parentJob->name,
                            ],
                        ];
                    }
                }
                return ['allowed' => true, 'reason' => null, 'blocking_parent' => null];

            case 'time_logs':
                $log = TimeLog::find($id);
                if (!$log) {
                    return ['allowed' => false, 'reason' => 'Time log not found.', 'blocking_parent' => null];
                }

                // Check Employee parent
                if ($log->employee_id) {
                    $employee = User::find($log->employee_id);
                    if (!$employee) {
                        return [
                            'allowed' => false,
                            'reason'  => "Cannot restore Time Log #{$log->id}: Its assigned Employee (ID #{$log->employee_id}) was permanently deleted.",
                            'blocking_parent' => [
                                'type'   => 'Employee',
                                'id'     => $log->employee_id,
                                'status' => 'Permanently Deleted',
                                'name'   => "Missing Employee #{$log->employee_id}",
                            ],
                        ];
                    }

                    if (in_array(strtolower((string) $employee->status), ['inactive', 'deleted'], true)) {
                        return [
                            'allowed' => false,
                            'reason'  => "Cannot restore Time Log #{$log->id}: Assigned Employee '{$employee->name}' is currently inactive or deleted. Restore the employee first.",
                            'blocking_parent' => [
                                'type'   => 'Employee',
                                'id'     => $employee->id,
                                'status' => ucfirst($employee->status ?? 'Inactive'),
                                'name'   => $employee->name,
                            ],
                        ];
                    }
                }

                // Check Job parent
                if ($log->assignment_id) {
                    $job = Assignment::find($log->assignment_id);
                    if (!$job) {
                        return [
                            'allowed' => false,
                            'reason'  => "Cannot restore Time Log #{$log->id}: Associated Job (ID #{$log->assignment_id}) was permanently deleted.",
                            'blocking_parent' => [
                                'type'   => 'Job',
                                'id'     => $log->assignment_id,
                                'status' => 'Permanently Deleted',
                                'name'   => "Missing Job #{$log->assignment_id}",
                            ],
                        ];
                    }

                    if (in_array(strtolower((string) $job->status), ['inactive', 'archived', 'deleted'], true)) {
                        return [
                            'allowed' => false,
                            'reason'  => "Cannot restore Time Log #{$log->id}: Associated Job (#{$job->id} '{$job->name}') is currently inactive or archived. Restore the job first.",
                            'blocking_parent' => [
                                'type'   => 'Job',
                                'id'     => $job->id,
                                'status' => ucfirst($job->status ?? 'Inactive'),
                                'name'   => $job->name,
                            ],
                        ];
                    }
                }

                // Check Task parent
                if ($log->task_id) {
                    $task = Task::find($log->task_id);
                    if (!$task) {
                        return [
                            'allowed' => false,
                            'reason'  => "Cannot restore Time Log #{$log->id}: Associated Task (ID #{$log->task_id}) was permanently deleted.",
                            'blocking_parent' => [
                                'type'   => 'Task',
                                'id'     => $log->task_id,
                                'status' => 'Permanently Deleted',
                                'name'   => "Missing Task #{$log->task_id}",
                            ],
                        ];
                    }

                    if (in_array(strtolower((string) $task->status), ['inactive', 'archived', 'deleted'], true)) {
                        return [
                            'allowed' => false,
                            'reason'  => "Cannot restore Time Log #{$log->id}: Associated Task (#{$task->id} '{$task->name}') is currently inactive. Restore the task first.",
                            'blocking_parent' => [
                                'type'   => 'Task',
                                'id'     => $task->id,
                                'status' => ucfirst($task->status ?? 'Inactive'),
                                'name'   => $task->name,
                            ],
                        ];
                    }
                }

                // Check Parent Log if switch segment
                if ($log->parent_log_id) {
                    $parentLog = TimeLog::find($log->parent_log_id);
                    if (!$parentLog || $parentLog->is_archived) {
                        return [
                            'allowed' => false,
                            'reason'  => "Cannot restore Segment Log #{$log->id}: The primary shift Time Log #{$log->parent_log_id} is archived or deleted. Restore the parent time log first.",
                            'blocking_parent' => [
                                'type'   => 'TimeLog',
                                'id'     => $log->parent_log_id,
                                'status' => 'Archived',
                                'name'   => "Primary Shift #{$log->parent_log_id}",
                            ],
                        ];
                    }
                }

                return ['allowed' => true, 'reason' => null, 'blocking_parent' => null];

            case 'schedules':
                $schedule = Schedule::find($id);
                if (!$schedule) {
                    return ['allowed' => false, 'reason' => 'Schedule not found.', 'blocking_parent' => null];
                }

                if ($schedule->employee_id) {
                    $employee = User::find($schedule->employee_id);
                    if (!$employee || in_array(strtolower((string) $employee->status), ['inactive', 'deleted'], true)) {
                        return [
                            'allowed' => false,
                            'reason'  => "Cannot restore Shift #{$schedule->id}: Assigned Employee is inactive or deleted.",
                            'blocking_parent' => [
                                'type'   => 'Employee',
                                'id'     => $schedule->employee_id,
                                'status' => $employee ? ucfirst($employee->status) : 'Deleted',
                                'name'   => $employee?->name ?? "Employee #{$schedule->employee_id}",
                            ],
                        ];
                    }
                }

                if ($schedule->assignment_id) {
                    $job = Assignment::find($schedule->assignment_id);
                    if (!$job || in_array(strtolower((string) $job->status), ['inactive', 'archived', 'deleted'], true)) {
                        return [
                            'allowed' => false,
                            'reason'  => "Cannot restore Shift #{$schedule->id}: Associated Job is inactive or deleted.",
                            'blocking_parent' => [
                                'type'   => 'Job',
                                'id'     => $schedule->assignment_id,
                                'status' => $job ? ucfirst($job->status) : 'Deleted',
                                'name'   => $job?->name ?? "Job #{$schedule->assignment_id}",
                            ],
                        ];
                    }
                }

                return ['allowed' => true, 'reason' => null, 'blocking_parent' => null];

            default:
                return ['allowed' => true, 'reason' => null, 'blocking_parent' => null];
        }
    }

    /**
     * Generate the complete Relational Hierarchy Tree for any record.
     */
    public static function getRecordRelationalTree(string $tableKey, int $id): ?array
    {
        $orphanCheck = self::canRestoreRecord($tableKey, $id);

        switch ($tableKey) {
            case 'jobs':
                $job = Assignment::find($id);
                if (!$job) return null;

                $tasks = Task::where('assignment_id', $id)->get();
                $timeLogsCount = TimeLog::where('assignment_id', $id)->count();
                $archivedLogsCount = TimeLog::where('assignment_id', $id)->where('is_archived', true)->count();
                $schedulesCount = Schedule::where('assignment_id', $id)->count();

                $notesCount = 0;
                $attachmentsCount = 0;
                if ($timeLogsCount > 0) {
                    $logIds = TimeLog::where('assignment_id', $id)->pluck('id')->toArray();
                    $notesCount = TimeLogNote::whereIn('time_log_id', $logIds)->count();
                    $attachmentsCount = TimeLogAttachment::whereIn('time_log_id', $logIds)->count();
                }

                $customer = null;
                if (Schema::hasColumn('tbl_assignments', 'customer_id') && $job->customer_id) {
                    $cust = Customer::find($job->customer_id);
                    if ($cust) {
                        $customer = [
                            'type'      => 'Customer',
                            'id'        => $cust->id,
                            'name'      => $cust->name,
                            'status'    => $cust->active ? 'Active' : 'Inactive',
                            'is_active' => (bool) $cust->active,
                        ];
                    }
                }

                $childTasksList = $tasks->map(function ($t) {
                    $logs = TimeLog::where('task_id', $t->id)->count();
                    $scheds = Schedule::where('task_id', $t->id)->count();
                    $isActive = !in_array(strtolower((string) $t->status), ['inactive', 'archived', 'deleted'], true);
                    return [
                        'id'           => $t->id,
                        'name'         => $t->name,
                        'status'       => ucfirst($t->status ?? 'Active'),
                        'is_active'    => $isActive,
                        'sub_children' => "{$logs} log(s), {$scheds} shift(s)",
                    ];
                })->toArray();

                $totalChildren = $tasks->count() + $timeLogsCount + $schedulesCount + $notesCount + $attachmentsCount;

                return [
                    'entity' => [
                        'id'         => $job->id,
                        'table_key'  => 'jobs',
                        'name'       => $job->name,
                        'status'     => ucfirst($job->status ?? 'Active'),
                        'is_active'  => !in_array(strtolower((string) $job->status), ['inactive', 'archived', 'deleted'], true),
                        'subtitle'   => "Job ID #{$job->id}" . ($job->location ? " • Location: {$job->location}" : ""),
                        'created_at' => $job->created_at?->format('M d, Y h:i A') ?? 'N/A',
                        'updated_at' => $job->updated_at?->format('M d, Y h:i A') ?? 'N/A',
                    ],
                    'parents' => array_filter([$customer]),
                    'children_groups' => [
                        [
                            'label'          => 'Child Tasks',
                            'table_key'      => 'tasks',
                            'icon'           => 'fa-solid fa-list-check',
                            'total_count'    => $tasks->count(),
                            'active_count'   => $tasks->whereNotIn('status', ['inactive', 'Inactive', 'archived', 'Archived'])->count(),
                            'inactive_count' => $tasks->whereIn('status', ['inactive', 'Inactive', 'archived', 'Archived'])->count(),
                            'items'          => $childTasksList,
                        ],
                        [
                            'label'          => 'Linked Time Logs',
                            'table_key'      => 'time_logs',
                            'icon'           => 'fa-solid fa-clock-rotate-left',
                            'total_count'    => $timeLogsCount,
                            'active_count'   => max(0, $timeLogsCount - $archivedLogsCount),
                            'inactive_count' => $archivedLogsCount,
                            'grand_children' => [
                                ['label' => 'Log Notes', 'count' => $notesCount],
                                ['label' => 'Attachments', 'count' => $attachmentsCount],
                            ],
                        ],
                        [
                            'label'          => 'Assigned Schedules',
                            'table_key'      => 'schedules',
                            'icon'           => 'fa-solid fa-calendar-days',
                            'total_count'    => $schedulesCount,
                            'active_count'   => $schedulesCount,
                            'inactive_count' => 0,
                        ],
                    ],
                    'orphan_guard'     => $orphanCheck,
                    'total_children'   => $totalChildren,
                    'cascade_breakdown'=> [
                        ['label' => 'Child Tasks', 'count' => $tasks->count()],
                        ['label' => 'Time Logs', 'count' => $timeLogsCount],
                        ['label' => 'Schedules / Shifts', 'count' => $schedulesCount],
                        ['label' => 'Notes & Attachments', 'count' => $notesCount + $attachmentsCount],
                    ],
                ];

            case 'tasks':
                $task = Task::with('assignment')->find($id);
                if (!$task) return null;

                $job = $task->assignment;
                $jobParent = null;
                if ($job) {
                    $isJobActive = !in_array(strtolower((string) $job->status), ['inactive', 'archived', 'deleted'], true);
                    $jobParent = [
                        'type'      => 'Job',
                        'id'        => $job->id,
                        'name'      => $job->name,
                        'status'    => ucfirst($job->status ?? 'Active'),
                        'is_active' => $isJobActive,
                    ];
                }

                $logsCount = TimeLog::where('task_id', $id)->count();
                $archivedLogsCount = TimeLog::where('task_id', $id)->where('is_archived', true)->count();
                $schedulesCount = Schedule::where('task_id', $id)->count();

                $notesCount = 0;
                $attachmentsCount = 0;
                if ($logsCount > 0) {
                    $logIds = TimeLog::where('task_id', $id)->pluck('id')->toArray();
                    $notesCount = TimeLogNote::whereIn('time_log_id', $logIds)->count();
                    $attachmentsCount = TimeLogAttachment::whereIn('time_log_id', $logIds)->count();
                }

                $totalChildren = $logsCount + $schedulesCount + $notesCount + $attachmentsCount;

                return [
                    'entity' => [
                        'id'         => $task->id,
                        'table_key'  => 'tasks',
                        'name'       => $task->name,
                        'status'     => ucfirst($task->status ?? 'Active'),
                        'is_active'  => !in_array(strtolower((string) $task->status), ['inactive', 'archived', 'deleted'], true),
                        'subtitle'   => "Task ID #{$task->id}" . ($job ? " • Parent Job: {$job->name}" : " • No Parent Job"),
                        'created_at' => $task->created_at?->format('M d, Y h:i A') ?? 'N/A',
                        'updated_at' => $task->updated_at?->format('M d, Y h:i A') ?? 'N/A',
                    ],
                    'parents' => array_filter([$jobParent]),
                    'children_groups' => [
                        [
                            'label'          => 'Linked Time Logs',
                            'table_key'      => 'time_logs',
                            'icon'           => 'fa-solid fa-clock-rotate-left',
                            'total_count'    => $logsCount,
                            'active_count'   => max(0, $logsCount - $archivedLogsCount),
                            'inactive_count' => $archivedLogsCount,
                            'grand_children' => [
                                ['label' => 'Log Notes', 'count' => $notesCount],
                                ['label' => 'Attachments', 'count' => $attachmentsCount],
                            ],
                        ],
                        [
                            'label'          => 'Assigned Schedules',
                            'table_key'      => 'schedules',
                            'icon'           => 'fa-solid fa-calendar-days',
                            'total_count'    => $schedulesCount,
                            'active_count'   => $schedulesCount,
                            'inactive_count' => 0,
                        ],
                    ],
                    'orphan_guard'     => $orphanCheck,
                    'total_children'   => $totalChildren,
                    'cascade_breakdown'=> [
                        ['label' => 'Linked Time Logs', 'count' => $logsCount],
                        ['label' => 'Schedules / Shifts', 'count' => $schedulesCount],
                        ['label' => 'Notes & Attachments', 'count' => $notesCount + $attachmentsCount],
                    ],
                ];

            case 'employees':
                $user = User::find($id);
                if (!$user) return null;

                $parents = [];
                if ($user->department_id) {
                    $dept = Department::find($user->department_id);
                    if ($dept) {
                        $parents[] = [
                            'type'      => 'Department',
                            'id'        => $dept->id,
                            'name'      => $dept->name,
                            'status'    => ucfirst($dept->status ?? 'Active'),
                            'is_active' => !in_array(strtolower((string) $dept->status), ['inactive', 'deleted'], true),
                        ];
                    }
                }
                if ($user->location_id) {
                    $loc = Location::find($user->location_id);
                    if ($loc) {
                        $parents[] = [
                            'type'      => 'Location',
                            'id'        => $loc->id,
                            'name'      => $loc->name,
                            'status'    => ucfirst($loc->status ?? 'Active'),
                            'is_active' => !in_array(strtolower((string) $loc->status), ['inactive', 'deleted'], true),
                        ];
                    }
                }
                if ($user->manager_id) {
                    $mgr = User::find($user->manager_id);
                    if ($mgr) {
                        $parents[] = [
                            'type'      => 'Manager',
                            'id'        => $mgr->id,
                            'name'      => $mgr->name,
                            'status'    => ucfirst($mgr->status ?? 'Active'),
                            'is_active' => !in_array(strtolower((string) $mgr->status), ['inactive', 'deleted'], true),
                        ];
                    }
                }

                $timeLogsCount    = TimeLog::where('employee_id', $user->id)->count();
                $archivedLogsCount = TimeLog::where('employee_id', $user->id)->where('is_archived', true)->count();
                $notesCount       = TimeLogNote::where('user_id', $user->id)->count();
                $attachmentsCount = TimeLogAttachment::where('user_id', $user->id)->count();
                $schedulesCount   = Schedule::where('employee_id', $user->id)->count();
                $timeOffCount     = TimeOffRequest::where('employee_id', $user->id)->count();
                $policiesCount    = TimeOffPolicyEmployee::where('employee_id', $user->id)->count();
                $directReports    = User::where('manager_id', $user->id)->count();

                $totalChildren = $timeLogsCount + $notesCount + $attachmentsCount + $schedulesCount + $timeOffCount + $policiesCount + $directReports;

                return [
                    'entity' => [
                        'id'         => $user->id,
                        'table_key'  => 'employees',
                        'name'       => $user->name,
                        'status'     => ucfirst($user->status ?? 'Active'),
                        'is_active'  => !in_array(strtolower((string) $user->status), ['inactive', 'deleted'], true),
                        'subtitle'   => "{$user->role} • {$user->email}",
                        'created_at' => $user->created_at?->format('M d, Y h:i A') ?? 'N/A',
                        'updated_at' => $user->updated_at?->format('M d, Y h:i A') ?? 'N/A',
                    ],
                    'parents' => $parents,
                    'children_groups' => [
                        [
                            'label'          => 'Tracked Time Logs',
                            'table_key'      => 'time_logs',
                            'icon'           => 'fa-solid fa-clock-rotate-left',
                            'total_count'    => $timeLogsCount,
                            'active_count'   => max(0, $timeLogsCount - $archivedLogsCount),
                            'inactive_count' => $archivedLogsCount,
                        ],
                        [
                            'label'          => 'Assigned Schedules',
                            'table_key'      => 'schedules',
                            'icon'           => 'fa-solid fa-calendar-days',
                            'total_count'    => $schedulesCount,
                            'active_count'   => $schedulesCount,
                            'inactive_count' => 0,
                        ],
                        [
                            'label'          => 'Time Off Requests',
                            'table_key'      => 'time_off_requests',
                            'icon'           => 'fa-solid fa-plane-departure',
                            'total_count'    => $timeOffCount,
                            'active_count'   => $timeOffCount,
                            'inactive_count' => 0,
                        ],
                        [
                            'label'          => 'Direct Reports (Subordinates)',
                            'table_key'      => 'employees',
                            'icon'           => 'fa-solid fa-sitemap',
                            'total_count'    => $directReports,
                            'active_count'   => $directReports,
                            'inactive_count' => 0,
                        ],
                    ],
                    'orphan_guard'     => $orphanCheck,
                    'total_children'   => $totalChildren,
                    'cascade_breakdown'=> [
                        ['label' => 'Time Logs', 'count' => $timeLogsCount],
                        ['label' => 'Schedules / Shifts', 'count' => $schedulesCount],
                        ['label' => 'Time Off Requests', 'count' => $timeOffCount],
                        ['label' => 'Policy Enrollments', 'count' => $policiesCount],
                        ['label' => 'Notes & Attachments', 'count' => $notesCount + $attachmentsCount],
                        ['label' => 'Direct Reports (Unlinked)', 'count' => $directReports],
                    ],
                ];

            case 'time_logs':
                $log = TimeLog::with(['employee', 'assignment', 'task'])->find($id);
                if (!$log) return null;

                $parents = [];
                if ($log->employee) {
                    $isEmpActive = !in_array(strtolower((string) $log->employee->status), ['inactive', 'deleted'], true);
                    $parents[] = [
                        'type'      => 'Employee',
                        'id'        => $log->employee->id,
                        'name'      => $log->employee->name,
                        'status'    => ucfirst($log->employee->status ?? 'Active'),
                        'is_active' => $isEmpActive,
                    ];
                }
                if ($log->assignment) {
                    $isJobActive = !in_array(strtolower((string) $log->assignment->status), ['inactive', 'archived', 'deleted'], true);
                    $parents[] = [
                        'type'      => 'Job',
                        'id'        => $log->assignment->id,
                        'name'      => $log->assignment->name,
                        'status'    => ucfirst($log->assignment->status ?? 'Active'),
                        'is_active' => $isJobActive,
                    ];
                }
                if ($log->task) {
                    $isTaskActive = !in_array(strtolower((string) $log->task->status), ['inactive', 'archived', 'deleted'], true);
                    $parents[] = [
                        'type'      => 'Task',
                        'id'        => $log->task->id,
                        'name'      => $log->task->name,
                        'status'    => ucfirst($log->task->status ?? 'Active'),
                        'is_active' => $isTaskActive,
                    ];
                }

                $childSwitches = TimeLog::where('parent_log_id', $log->id)->count();
                $notesCount = TimeLogNote::where('time_log_id', $log->id)->count();
                $attachmentsCount = TimeLogAttachment::where('time_log_id', $log->id)->count();
                $totalChildren = $childSwitches + $notesCount + $attachmentsCount;

                $clockInStr = $log->clock_in ? $log->clock_in->format('M d, Y h:i A') : 'Unknown Start';
                $clockOutStr = $log->clock_out ? $log->clock_out->format('h:i A') : 'Active Session';
                $duration = $log->duration_minutes ? " ({$log->duration_minutes} mins)" : "";

                return [
                    'entity' => [
                        'id'         => $log->id,
                        'table_key'  => 'time_logs',
                        'name'       => "Time Log #{$log->id} – " . ($log->employee?->name ?? 'Unknown Worker'),
                        'status'     => $log->is_archived ? 'Archived' : 'Active',
                        'is_active'  => !$log->is_archived,
                        'subtitle'   => "{$clockInStr} – {$clockOutStr}{$duration}",
                        'created_at' => $log->created_at?->format('M d, Y h:i A') ?? 'N/A',
                        'updated_at' => $log->updated_at?->format('M d, Y h:i A') ?? 'N/A',
                    ],
                    'parents' => $parents,
                    'children_groups' => [
                        [
                            'label'          => 'Child Job-Switch Segments',
                            'table_key'      => 'time_logs',
                            'icon'           => 'fa-solid fa-shuffle',
                            'total_count'    => $childSwitches,
                            'active_count'   => $childSwitches,
                            'inactive_count' => 0,
                        ],
                        [
                            'label'          => 'Log Notes & Attachments',
                            'table_key'      => 'time_log_notes',
                            'icon'           => 'fa-solid fa-paperclip',
                            'total_count'    => $notesCount + $attachmentsCount,
                            'active_count'   => $notesCount + $attachmentsCount,
                            'inactive_count' => 0,
                            'grand_children' => [
                                ['label' => 'Notes', 'count' => $notesCount],
                                ['label' => 'Attachments', 'count' => $attachmentsCount],
                            ],
                        ],
                    ],
                    'orphan_guard'     => $orphanCheck,
                    'total_children'   => $totalChildren,
                    'cascade_breakdown'=> [
                        ['label' => 'Child Switch Segments', 'count' => $childSwitches],
                        ['label' => 'Log Notes', 'count' => $notesCount],
                        ['label' => 'Attachments', 'count' => $attachmentsCount],
                    ],
                ];

            case 'departments':
                $dept = Department::find($id);
                if (!$dept) return null;

                $usersCount = User::where('department_id', $dept->id)->count();

                return [
                    'entity' => [
                        'id'         => $dept->id,
                        'table_key'  => 'departments',
                        'name'       => $dept->name,
                        'status'     => ucfirst($dept->status ?? 'Active'),
                        'is_active'  => !in_array(strtolower((string) $dept->status), ['inactive', 'deleted'], true),
                        'subtitle'   => "Department ID #{$dept->id}",
                        'created_at' => $dept->created_at?->format('M d, Y h:i A') ?? 'N/A',
                        'updated_at' => $dept->updated_at?->format('M d, Y h:i A') ?? 'N/A',
                    ],
                    'parents' => [],
                    'children_groups' => [
                        [
                            'label'          => 'Assigned Employees (Will be Safely Unlinked)',
                            'table_key'      => 'employees',
                            'icon'           => 'fa-solid fa-users',
                            'total_count'    => $usersCount,
                            'active_count'   => $usersCount,
                            'inactive_count' => 0,
                        ],
                    ],
                    'orphan_guard'     => $orphanCheck,
                    'total_children'   => $usersCount,
                    'cascade_breakdown'=> [
                        ['label' => 'Assigned Employees (Safely Unlinked)', 'count' => $usersCount],
                    ],
                ];

            case 'locations':
                $loc = Location::find($id);
                if (!$loc) return null;

                $usersCount = User::where('location_id', $loc->id)->count();
                $tasksCount = Schema::hasColumn('tbl_tasks', 'location_id') ? Task::where('location_id', $loc->id)->count() : 0;
                $schedulesCount = Schedule::where('location_name', $loc->name)->count();

                return [
                    'entity' => [
                        'id'         => $loc->id,
                        'table_key'  => 'locations',
                        'name'       => $loc->name,
                        'status'     => ucfirst($loc->status ?? 'Active'),
                        'is_active'  => !in_array(strtolower((string) $loc->status), ['inactive', 'deleted'], true),
                        'subtitle'   => "Location ID #{$loc->id}" . ($loc->address ? " • Address: {$loc->address}" : ""),
                        'created_at' => $loc->created_at?->format('M d, Y h:i A') ?? 'N/A',
                        'updated_at' => $loc->updated_at?->format('M d, Y h:i A') ?? 'N/A',
                    ],
                    'parents' => [],
                    'children_groups' => [
                        [
                            'label'          => 'Assigned Employees & Tasks (Will be Unlinked)',
                            'table_key'      => 'employees',
                            'icon'           => 'fa-solid fa-link-slash',
                            'total_count'    => $usersCount + $tasksCount,
                            'active_count'   => $usersCount + $tasksCount,
                            'inactive_count' => 0,
                        ],
                        [
                            'label'          => 'Referencing Schedules',
                            'table_key'      => 'schedules',
                            'icon'           => 'fa-solid fa-calendar-days',
                            'total_count'    => $schedulesCount,
                            'active_count'   => $schedulesCount,
                            'inactive_count' => 0,
                        ],
                    ],
                    'orphan_guard'     => $orphanCheck,
                    'total_children'   => $usersCount + $tasksCount + $schedulesCount,
                    'cascade_breakdown'=> [
                        ['label' => 'Assigned Employees (Unlinked)', 'count' => $usersCount],
                        ['label' => 'Assigned Tasks (Unlinked)', 'count' => $tasksCount],
                        ['label' => 'Schedules referencing site', 'count' => $schedulesCount],
                    ],
                ];

            case 'customers':
                $cust = Customer::find($id);
                if (!$cust) return null;

                $jobsCount = Schema::hasColumn('tbl_assignments', 'customer_id') ? Assignment::where('customer_id', $cust->id)->count() : 0;
                $quotesCount = class_exists(\App\Models\Quote::class) ? \App\Models\Quote::where('customer_id', $cust->id)->count() : 0;
                $invoicesCount = class_exists(\App\Models\Invoice::class) ? \App\Models\Invoice::where('customer_id', $cust->id)->count() : 0;

                return [
                    'entity' => [
                        'id'         => $cust->id,
                        'table_key'  => 'customers',
                        'name'       => $cust->name,
                        'status'     => $cust->active ? 'Active' : 'Inactive',
                        'is_active'  => (bool) $cust->active,
                        'subtitle'   => "Customer ID #{$cust->id}" . ($cust->contact_name ? " • Contact: {$cust->contact_name}" : ""),
                        'created_at' => $cust->created_at?->format('M d, Y h:i A') ?? 'N/A',
                        'updated_at' => $cust->updated_at?->format('M d, Y h:i A') ?? 'N/A',
                    ],
                    'parents' => [],
                    'children_groups' => [
                        [
                            'label'          => 'Linked Jobs (Will be Safely Unlinked)',
                            'table_key'      => 'jobs',
                            'icon'           => 'fa-solid fa-briefcase',
                            'total_count'    => $jobsCount,
                            'active_count'   => $jobsCount,
                            'inactive_count' => 0,
                        ],
                        [
                            'label'          => 'Child Quotes & Invoices (Will be Deleted)',
                            'table_key'      => 'customers',
                            'icon'           => 'fa-solid fa-receipt',
                            'total_count'    => $quotesCount + $invoicesCount,
                            'active_count'   => $quotesCount + $invoicesCount,
                            'inactive_count' => 0,
                        ],
                    ],
                    'orphan_guard'     => $orphanCheck,
                    'total_children'   => $jobsCount + $quotesCount + $invoicesCount,
                    'cascade_breakdown'=> [
                        ['label' => 'Jobs (Safely Unlinked)', 'count' => $jobsCount],
                        ['label' => 'Child Quotes (Deleted)', 'count' => $quotesCount],
                        ['label' => 'Child Invoices (Deleted)', 'count' => $invoicesCount],
                    ],
                ];

            case 'time_off_policies':
                $policy = TimeOffPolicy::find($id);
                if (!$policy) return null;

                $enrollmentsCount = TimeOffPolicyEmployee::where('policy_id', $policy->id)->count();
                $requestsCount = TimeOffRequest::where('policy_id', $policy->id)->count();

                return [
                    'entity' => [
                        'id'         => $policy->id,
                        'table_key'  => 'time_off_policies',
                        'name'       => $policy->name,
                        'status'     => $policy->active ? 'Active' : 'Inactive',
                        'is_active'  => (bool) $policy->active,
                        'subtitle'   => "Policy ID #{$policy->id}",
                        'created_at' => $policy->created_at?->format('M d, Y h:i A') ?? 'N/A',
                        'updated_at' => $policy->updated_at?->format('M d, Y h:i A') ?? 'N/A',
                    ],
                    'parents' => [],
                    'children_groups' => [
                        [
                            'label'          => 'Enrolled Employees',
                            'table_key'      => 'employees',
                            'icon'           => 'fa-solid fa-users',
                            'total_count'    => $enrollmentsCount,
                            'active_count'   => $enrollmentsCount,
                            'inactive_count' => 0,
                        ],
                        [
                            'label'          => 'Time Off Requests',
                            'table_key'      => 'time_off_requests',
                            'icon'           => 'fa-solid fa-plane-departure',
                            'total_count'    => $requestsCount,
                            'active_count'   => $requestsCount,
                            'inactive_count' => 0,
                        ],
                    ],
                    'orphan_guard'     => $orphanCheck,
                    'total_children'   => $enrollmentsCount + $requestsCount,
                    'cascade_breakdown'=> [
                        ['label' => 'Employee Policy Enrollments', 'count' => $enrollmentsCount],
                        ['label' => 'Time Off Requests', 'count' => $requestsCount],
                    ],
                ];

            default:
                return null;
        }
    }

    /**
     * Get records for a specific table with filtering by status ('all', 'active', 'inactive') and search query.
     */
    public static function getTableRecords(string $tableKey, string $statusFilter = 'all', ?string $search = null): Collection
    {
        $searchTerm = trim((string) $search);

        switch ($tableKey) {
            case 'employees':
                $q = User::where('role', '!=', 'Super Admin');
                if ($statusFilter === 'active') {
                    $q->whereNotIn('status', ['Inactive', 'inactive', 'deleted']);
                } elseif ($statusFilter === 'inactive') {
                    $q->whereIn('status', ['Inactive', 'inactive', 'deleted']);
                }
                if ($searchTerm !== '') {
                    $q->where(fn($sub) => $sub->where('name', 'like', "%{$searchTerm}%")->orWhere('email', 'like', "%{$searchTerm}%"));
                }
                return $q->orderBy('name')->get()->map(function ($u) {
                    $isInactive = in_array(strtolower((string) $u->status), ['inactive', 'deleted'], true);
                    $logsCount = TimeLog::where('employee_id', $u->id)->count();
                    $archivedLogsCount = TimeLog::where('employee_id', $u->id)->where('is_archived', true)->count();
                    $notesCount = TimeLogNote::where('user_id', $u->id)->count();
                    $attachmentsCount = TimeLogAttachment::where('user_id', $u->id)->count();
                    $schedulesCount = Schedule::where('employee_id', $u->id)->count();
                    $timeOffCount = TimeOffRequest::where('employee_id', $u->id)->count();
                    $policiesCount = TimeOffPolicyEmployee::where('employee_id', $u->id)->count();
                    $directReports = User::where('manager_id', $u->id)->count();
                    $totalChildren = $logsCount + $notesCount + $attachmentsCount + $schedulesCount + $timeOffCount + $policiesCount + $directReports;

                    $restoreItems = [
                        ['label' => 'Active Employee Status', 'count' => 1],
                    ];
                    if ($archivedLogsCount > 0) {
                        $restoreItems[] = ['label' => 'Archived Time Logs', 'count' => $archivedLogsCount];
                    }

                    $deleteItems = [
                        ['label' => 'Employee Record', 'count' => 1],
                    ];
                    if ($logsCount > 0)        $deleteItems[] = ['label' => 'Time Logs', 'count' => $logsCount];
                    if ($notesCount > 0)       $deleteItems[] = ['label' => 'Time Log Notes', 'count' => $notesCount];
                    if ($attachmentsCount > 0) $deleteItems[] = ['label' => 'Attachments', 'count' => $attachmentsCount];
                    if ($schedulesCount > 0)   $deleteItems[] = ['label' => 'Schedules / Shifts', 'count' => $schedulesCount];
                    if ($timeOffCount > 0)     $deleteItems[] = ['label' => 'Time Off Requests', 'count' => $timeOffCount];
                    if ($policiesCount > 0)    $deleteItems[] = ['label' => 'Policy Enrollments', 'count' => $policiesCount];
                    if ($directReports > 0)    $deleteItems[] = ['label' => 'Direct Reports (Unlinked)', 'count' => $directReports];

                    return [
                        'composite_id'    => "employees:{$u->id}",
                        'table_key'       => 'employees',
                        'table_label'     => 'Employee',
                        'record_id'       => $u->id,
                        'name'            => $u->name,
                        'subtitle'        => "{$u->role} • {$u->email}",
                        'status'          => ucfirst($u->status ?? 'Active'),
                        'inactive_status' => ucfirst($u->status ?? 'Inactive'),
                        'is_inactive'     => $isInactive,
                        'parent_info'     => $u->manager ? "Reports to: {$u->manager->name}" : "Direct Staff",
                        'total_children'  => $totalChildren,
                        'can_restore'     => true,
                        'orphan_reason'   => null,
                        'restore_summary' => "Restores this employee to Active status" . ($archivedLogsCount > 0 ? " and un-archives {$archivedLogsCount} linked time log(s)." : "."),
                        'delete_summary'  => "Permanently deletes this employee" . ($totalChildren > 0 ? " and cascades deletion across {$totalChildren} linked child record(s) (timesheets, notes, attachments, shifts, time-off requests)." : "."),
                        'restore_items'   => $restoreItems,
                        'delete_items'    => $deleteItems,
                        'created_at'      => $u->created_at?->format('M d, Y') ?? 'N/A',
                        'updated_at'      => $u->updated_at?->format('M d, Y h:i A') ?? 'N/A',
                    ];
                });

            case 'jobs':
                $q = Assignment::query();
                if ($statusFilter === 'active') {
                    $q->whereNotIn('status', ['inactive', 'Inactive', 'archived', 'Archived']);
                } elseif ($statusFilter === 'inactive') {
                    $q->whereIn('status', ['inactive', 'Inactive', 'archived', 'Archived']);
                }
                if ($searchTerm !== '') {
                    $q->where('name', 'like', "%{$searchTerm}%");
                }
                return $q->orderBy('name')->get()->map(function ($j) {
                    $isInactive = in_array(strtolower((string) $j->status), ['inactive', 'archived', 'deleted'], true);
                    $tasksCount = Task::where('assignment_id', $j->id)->count();
                    $inactiveTasksCount = Task::where('assignment_id', $j->id)
                        ->whereIn('status', ['inactive', 'Inactive', 'archived', 'Archived'])
                        ->count();
                    $logsCount = TimeLog::where('assignment_id', $j->id)->count();
                    $archivedLogsCount = TimeLog::where('assignment_id', $j->id)->where('is_archived', true)->count();
                    $schedulesCount = Schedule::where('assignment_id', $j->id)->count();
                    $totalChildren = $tasksCount + $logsCount + $schedulesCount;

                    $restoreItems = [
                        ['label' => 'Active Job Status', 'count' => 1],
                    ];
                    if ($inactiveTasksCount > 0) $restoreItems[] = ['label' => 'Inactive Child Tasks', 'count' => $inactiveTasksCount];
                    if ($archivedLogsCount > 0)  $restoreItems[] = ['label' => 'Archived Time Logs', 'count' => $archivedLogsCount];

                    $deleteItems = [
                        ['label' => 'Job Record', 'count' => 1],
                    ];
                    if ($tasksCount > 0)     $deleteItems[] = ['label' => 'Child Tasks', 'count' => $tasksCount];
                    if ($logsCount > 0)      $deleteItems[] = ['label' => 'Linked Time Logs', 'count' => $logsCount];
                    if ($schedulesCount > 0) $deleteItems[] = ['label' => 'Assigned Schedules', 'count' => $schedulesCount];

                    return [
                        'composite_id'    => "jobs:{$j->id}",
                        'table_key'       => 'jobs',
                        'table_label'     => 'Job',
                        'record_id'       => $j->id,
                        'name'            => $j->name,
                        'subtitle'        => "Job #{$j->id}" . ($j->location ? " • Site: {$j->location}" : ""),
                        'status'          => ucfirst($j->status ?? 'Active'),
                        'inactive_status' => ucfirst($j->status ?? 'Inactive'),
                        'is_inactive'     => $isInactive,
                        'parent_info'     => "Primary Work Project",
                        'total_children'  => $totalChildren,
                        'can_restore'     => true,
                        'orphan_reason'   => null,
                        'restore_summary' => "Restores this job to active status" . ($tasksCount > 0 ? " along with {$tasksCount} child task(s) and any archived logs." : "."),
                        'delete_summary'  => "Permanently deletes this job" . ($totalChildren > 0 ? " and cascades deletion across {$totalChildren} child record(s) ({$tasksCount} tasks, {$logsCount} timelogs, {$schedulesCount} schedules)." : "."),
                        'restore_items'   => $restoreItems,
                        'delete_items'    => $deleteItems,
                        'created_at'      => $j->created_at?->format('M d, Y') ?? 'N/A',
                        'updated_at'      => $j->updated_at?->format('M d, Y h:i A') ?? 'N/A',
                    ];
                });

            case 'tasks':
                $q = Task::with('assignment');
                if ($statusFilter === 'active') {
                    $q->whereNotIn('status', ['inactive', 'Inactive', 'archived', 'Archived']);
                } elseif ($statusFilter === 'inactive') {
                    $q->whereIn('status', ['inactive', 'Inactive', 'archived', 'Archived']);
                }
                if ($searchTerm !== '') {
                    $q->where('name', 'like', "%{$searchTerm}%");
                }
                return $q->orderBy('name')->get()->map(function ($t) {
                    $isInactive = in_array(strtolower((string) $t->status), ['inactive', 'archived', 'deleted'], true);
                    $logsCount = TimeLog::where('task_id', $t->id)->count();
                    $archivedLogsCount = TimeLog::where('task_id', $t->id)->where('is_archived', true)->count();
                    $schedulesCount = Schedule::where('task_id', $t->id)->count();
                    $totalChildren = $logsCount + $schedulesCount;
                    $orphanCheck = self::canRestoreRecord('tasks', $t->id);

                    $restoreItems = [
                        ['label' => 'Active Task Status', 'count' => 1],
                    ];
                    if ($archivedLogsCount > 0) $restoreItems[] = ['label' => 'Archived Time Logs', 'count' => $archivedLogsCount];

                    $deleteItems = [
                        ['label' => 'Task Record', 'count' => 1],
                    ];
                    if ($logsCount > 0)      $deleteItems[] = ['label' => 'Linked Time Logs', 'count' => $logsCount];
                    if ($schedulesCount > 0) $deleteItems[] = ['label' => 'Assigned Schedules', 'count' => $schedulesCount];

                    return [
                        'composite_id'    => "tasks:{$t->id}",
                        'table_key'       => 'tasks',
                        'table_label'     => 'Task',
                        'record_id'       => $t->id,
                        'name'            => $t->name,
                        'subtitle'        => "Belongs to Job: " . ($t->assignment?->name ?? 'No Parent Job'),
                        'status'          => ucfirst($t->status ?? 'Active'),
                        'inactive_status' => ucfirst($t->status ?? 'Inactive'),
                        'is_inactive'     => $isInactive,
                        'parent_info'     => $t->assignment ? "Job: {$t->assignment->name}" : "Orphan / No Job",
                        'total_children'  => $totalChildren,
                        'can_restore'     => $orphanCheck['allowed'],
                        'orphan_reason'   => $orphanCheck['reason'],
                        'restore_summary' => "Restores this task to active status" . ($archivedLogsCount > 0 ? " and restores {$archivedLogsCount} archived time log(s)." : "."),
                        'delete_summary'  => "Permanently deletes this task" . ($totalChildren > 0 ? " and cascades deletion across {$totalChildren} child record(s) ({$logsCount} timelogs, {$schedulesCount} schedules)." : "."),
                        'restore_items'   => $restoreItems,
                        'delete_items'    => $deleteItems,
                        'created_at'      => $t->created_at?->format('M d, Y') ?? 'N/A',
                        'updated_at'      => $t->updated_at?->format('M d, Y h:i A') ?? 'N/A',
                    ];
                });

            case 'time_logs':
                $q = TimeLog::with(['employee', 'assignment', 'task']);
                if ($statusFilter === 'active') {
                    $q->where('is_archived', false);
                } elseif ($statusFilter === 'inactive') {
                    $q->where('is_archived', true);
                }
                if ($searchTerm !== '') {
                    $q->where(function ($sub) use ($searchTerm) {
                        $sub->whereHas('employee', fn($e) => $e->where('name', 'like', "%{$searchTerm}%"))
                            ->orWhereHas('assignment', fn($a) => $a->where('name', 'like', "%{$searchTerm}%"))
                            ->orWhereHas('task', fn($t) => $t->where('name', 'like', "%{$searchTerm}%"))
                            ->orWhere('notes', 'like', "%{$searchTerm}%");
                    });
                }
                return $q->orderByDesc('id')->get()->map(function ($l) {
                    $orphanCheck = self::canRestoreRecord('time_logs', $l->id);
                    $childSwitches = TimeLog::where('parent_log_id', $l->id)->count();
                    $notesCount = TimeLogNote::where('time_log_id', $l->id)->count();
                    $attachmentsCount = TimeLogAttachment::where('time_log_id', $l->id)->count();
                    $totalChildren = $childSwitches + $notesCount + $attachmentsCount;

                    $restoreItems = [
                        ['label' => 'Active Time Log', 'count' => 1],
                    ];
                    if ($childSwitches > 0) $restoreItems[] = ['label' => 'Child Switch Segments', 'count' => $childSwitches];

                    $deleteItems = [
                        ['label' => 'Parent Time Log', 'count' => 1],
                    ];
                    if ($childSwitches > 0)    $deleteItems[] = ['label' => 'Child Switch Segments', 'count' => $childSwitches];
                    if ($notesCount > 0)       $deleteItems[] = ['label' => 'Log Notes', 'count' => $notesCount];
                    if ($attachmentsCount > 0) $deleteItems[] = ['label' => 'Attachments', 'count' => $attachmentsCount];

                    $empName = $l->employee?->name ?? 'Unknown Worker';
                    $jobTask = ($l->assignment?->name ?? 'No Job') . ' / ' . ($l->task?->name ?? 'No Task');
                    $dateStr = $l->clock_in ? $l->clock_in->format('M d, Y h:i A') : 'Unknown';

                    return [
                        'composite_id'    => "time_logs:{$l->id}",
                        'table_key'       => 'time_logs',
                        'table_label'     => 'Time Log',
                        'record_id'       => $l->id,
                        'name'            => "Time Log #{$l->id} – {$empName}",
                        'subtitle'        => "{$dateStr} • {$jobTask}",
                        'status'          => $l->is_archived ? 'Archived' : 'Active',
                        'inactive_status' => 'Archived',
                        'is_inactive'     => (bool) $l->is_archived,
                        'parent_info'     => "{$empName} ({$jobTask})",
                        'total_children'  => $totalChildren,
                        'can_restore'     => $orphanCheck['allowed'],
                        'orphan_reason'   => $orphanCheck['reason'],
                        'restore_summary' => "Restores this time log to active status" . ($childSwitches > 0 ? " along with {$childSwitches} child job-switch segment(s)." : "."),
                        'delete_summary'  => "Permanently deletes this time entry" . ($totalChildren > 0 ? " along with {$childSwitches} switch segments, {$notesCount} notes, and {$attachmentsCount} attachments." : "."),
                        'restore_items'   => $restoreItems,
                        'delete_items'    => $deleteItems,
                        'created_at'      => $l->created_at?->format('M d, Y') ?? 'N/A',
                        'updated_at'      => $l->updated_at?->format('M d, Y h:i A') ?? 'N/A',
                    ];
                });

            case 'schedules':
                $q = Schedule::with(['employee', 'assignment', 'task']);
                if ($statusFilter === 'active') {
                    $q->whereNotIn('status', ['cancelled', 'inactive', 'rejected']);
                } elseif ($statusFilter === 'inactive') {
                    $q->whereIn('status', ['cancelled', 'inactive', 'rejected']);
                }
                if ($searchTerm !== '') {
                    $q->where(function ($sub) use ($searchTerm) {
                        $sub->whereHas('employee', fn($e) => $e->where('name', 'like', "%{$searchTerm}%"))
                            ->orWhereHas('assignment', fn($a) => $a->where('name', 'like', "%{$searchTerm}%"))
                            ->orWhere('location_name', 'like', "%{$searchTerm}%")
                            ->orWhere('notes', 'like', "%{$searchTerm}%");
                    });
                }
                return $q->orderByDesc('shift_date')->get()->map(function ($s) {
                    $isInactive = in_array(strtolower((string) $s->status), ['cancelled', 'inactive', 'rejected'], true);
                    $orphanCheck = self::canRestoreRecord('schedules', $s->id);
                    $empName = $s->employee?->name ?? 'Unassigned';
                    $shiftDate = $s->shift_date ? $s->shift_date->format('M d, Y') : 'Date N/A';

                    return [
                        'composite_id'    => "schedules:{$s->id}",
                        'table_key'       => 'schedules',
                        'table_label'     => 'Schedule',
                        'record_id'       => $s->id,
                        'name'            => "Shift #{$s->id} – {$empName}",
                        'subtitle'        => "{$shiftDate} • {$s->start_time} - {$s->end_time}" . ($s->assignment?->name ? " • {$s->assignment->name}" : ""),
                        'status'          => ucfirst($s->status ?? 'Scheduled'),
                        'inactive_status' => ucfirst($s->status ?? 'Inactive'),
                        'is_inactive'     => $isInactive,
                        'parent_info'     => "Staff: {$empName}",
                        'total_children'  => 0,
                        'can_restore'     => $orphanCheck['allowed'],
                        'orphan_reason'   => $orphanCheck['reason'],
                        'restore_summary' => "Restores this shift schedule to active status.",
                        'delete_summary'  => "Permanently deletes this scheduled shift.",
                        'restore_items'   => [['label' => 'Active Shift Status', 'count' => 1]],
                        'delete_items'    => [['label' => 'Schedule Record', 'count' => 1]],
                        'created_at'      => $s->created_at?->format('M d, Y') ?? 'N/A',
                        'updated_at'      => $s->updated_at?->format('M d, Y h:i A') ?? 'N/A',
                    ];
                });

            case 'departments':
                $q = Department::query();
                if ($statusFilter === 'active') {
                    $q->whereNotIn('status', ['Inactive', 'inactive']);
                } elseif ($statusFilter === 'inactive') {
                    $q->whereIn('status', ['Inactive', 'inactive']);
                }
                if ($searchTerm !== '') {
                    $q->where('name', 'like', "%{$searchTerm}%");
                }
                return $q->orderBy('name')->get()->map(function ($d) {
                    $isInactive = in_array(strtolower((string) $d->status), ['inactive', 'deleted'], true);
                    $usersCount = User::where('department_id', $d->id)->count();

                    $deleteItems = [['label' => 'Department Record', 'count' => 1]];
                    if ($usersCount > 0) $deleteItems[] = ['label' => 'Assigned Employees (Safely Unlinked)', 'count' => $usersCount];

                    return [
                        'composite_id'    => "departments:{$d->id}",
                        'table_key'       => 'departments',
                        'table_label'     => 'Department',
                        'record_id'       => $d->id,
                        'name'            => $d->name,
                        'subtitle'        => "Department ID #{$d->id} • {$usersCount} assigned employee(s)",
                        'status'          => ucfirst($d->status ?? 'Active'),
                        'inactive_status' => ucfirst($d->status ?? 'Inactive'),
                        'is_inactive'     => $isInactive,
                        'parent_info'     => "Root Department",
                        'total_children'  => $usersCount,
                        'can_restore'     => true,
                        'orphan_reason'   => null,
                        'restore_summary' => "Restores this department to Active status.",
                        'delete_summary'  => "Permanently deletes this department" . ($usersCount > 0 ? " and safely unlinks {$usersCount} assigned employee(s)." : "."),
                        'restore_items'   => [['label' => 'Active Department Status', 'count' => 1]],
                        'delete_items'    => $deleteItems,
                        'created_at'      => $d->created_at?->format('M d, Y') ?? 'N/A',
                        'updated_at'      => $d->updated_at?->format('M d, Y h:i A') ?? 'N/A',
                    ];
                });

            case 'locations':
                $q = Location::query();
                if ($statusFilter === 'active') {
                    $q->whereNotIn('status', ['Inactive', 'inactive']);
                } elseif ($statusFilter === 'inactive') {
                    $q->whereIn('status', ['Inactive', 'inactive']);
                }
                if ($searchTerm !== '') {
                    $q->where('name', 'like', "%{$searchTerm}%");
                }
                return $q->orderBy('name')->get()->map(function ($l) {
                    $isInactive = in_array(strtolower((string) $l->status), ['inactive', 'deleted'], true);
                    $usersCount = User::where('location_id', $l->id)->count();
                    $tasksCount = Schema::hasColumn('tbl_tasks', 'location_id') ? Task::where('location_id', $l->id)->count() : 0;
                    $schedulesCount = Schedule::where('location_name', $l->name)->count();
                    $totalChildren = $usersCount + $tasksCount + $schedulesCount;

                    $deleteItems = [['label' => 'Location Record', 'count' => 1]];
                    if ($usersCount > 0)     $deleteItems[] = ['label' => 'Assigned Employees (Safely Unlinked)', 'count' => $usersCount];
                    if ($tasksCount > 0)     $deleteItems[] = ['label' => 'Assigned Tasks (Safely Unlinked)', 'count' => $tasksCount];
                    if ($schedulesCount > 0) $deleteItems[] = ['label' => 'Schedules referencing location name', 'count' => $schedulesCount];

                    return [
                        'composite_id'    => "locations:{$l->id}",
                        'table_key'       => 'locations',
                        'table_label'     => 'Location',
                        'record_id'       => $l->id,
                        'name'            => $l->name,
                        'subtitle'        => "Site ID #{$l->id}" . ($l->address ? " • {$l->address}" : ""),
                        'status'          => ucfirst($l->status ?? 'Active'),
                        'inactive_status' => ucfirst($l->status ?? 'Inactive'),
                        'is_inactive'     => $isInactive,
                        'parent_info'     => "Primary Site Boundary",
                        'total_children'  => $totalChildren,
                        'can_restore'     => true,
                        'orphan_reason'   => null,
                        'restore_summary' => "Restores this location to Active status.",
                        'delete_summary'  => "Permanently deletes this location" . ($totalChildren > 0 ? " and unlinks {$usersCount} employee(s) and {$tasksCount} task(s)." : "."),
                        'restore_items'   => [['label' => 'Active Location Status', 'count' => 1]],
                        'delete_items'    => $deleteItems,
                        'created_at'      => $l->created_at?->format('M d, Y') ?? 'N/A',
                        'updated_at'      => $l->updated_at?->format('M d, Y h:i A') ?? 'N/A',
                    ];
                });

            case 'customers':
                $q = Customer::query();
                if ($statusFilter === 'active') {
                    $q->where('active', true);
                } elseif ($statusFilter === 'inactive') {
                    $q->where('active', false);
                }
                if ($searchTerm !== '') {
                    $q->where('name', 'like', "%{$searchTerm}%");
                }
                return $q->orderBy('name')->get()->map(function ($c) {
                    $jobsCount = Schema::hasColumn('tbl_assignments', 'customer_id') ? Assignment::where('customer_id', $c->id)->count() : 0;
                    $quotesCount = class_exists(\App\Models\Quote::class) && Schema::hasTable('quotes') ? \App\Models\Quote::where('customer_id', $c->id)->count() : 0;
                    $invoicesCount = class_exists(\App\Models\Invoice::class) && Schema::hasTable('invoices') ? \App\Models\Invoice::where('customer_id', $c->id)->count() : 0;
                    $totalChildren = $jobsCount + $quotesCount + $invoicesCount;

                    $deleteItems = [['label' => 'Customer Record', 'count' => 1]];
                    if ($jobsCount > 0)     $deleteItems[] = ['label' => 'Jobs (Safely Unlinked)', 'count' => $jobsCount];
                    if ($quotesCount > 0)   $deleteItems[] = ['label' => 'Child Quotes', 'count' => $quotesCount];
                    if ($invoicesCount > 0) $deleteItems[] = ['label' => 'Child Invoices', 'count' => $invoicesCount];

                    return [
                        'composite_id'    => "customers:{$c->id}",
                        'table_key'       => 'customers',
                        'table_label'     => 'Customer',
                        'record_id'       => $c->id,
                        'name'            => $c->name,
                        'subtitle'        => "Customer ID #{$c->id}" . ($c->contact_name ? " • Contact: {$c->contact_name}" : ""),
                        'status'          => $c->active ? 'Active' : 'Inactive',
                        'inactive_status' => 'Inactive',
                        'is_inactive'     => !(bool) $c->active,
                        'parent_info'     => "Root Client Record",
                        'total_children'  => $totalChildren,
                        'can_restore'     => true,
                        'orphan_reason'   => null,
                        'restore_summary' => "Restores this customer to Active status.",
                        'delete_summary'  => "Permanently deletes this customer" . ($totalChildren > 0 ? " along with {$quotesCount} quote(s), {$invoicesCount} invoice(s), and unlinks {$jobsCount} job(s)." : "."),
                        'restore_items'   => [['label' => 'Active Customer Status', 'count' => 1]],
                        'delete_items'    => $deleteItems,
                        'created_at'      => $c->created_at?->format('M d, Y') ?? 'N/A',
                        'updated_at'      => $c->updated_at?->format('M d, Y h:i A') ?? 'N/A',
                    ];
                });

            case 'time_off_policies':
                $q = TimeOffPolicy::query();
                if ($statusFilter === 'active') {
                    $q->where('active', true);
                } elseif ($statusFilter === 'inactive') {
                    $q->where('active', false);
                }
                if ($searchTerm !== '') {
                    $q->where('name', 'like', "%{$searchTerm}%");
                }
                return $q->orderBy('name')->get()->map(function ($p) {
                    $enrollmentsCount = TimeOffPolicyEmployee::where('policy_id', $p->id)->count();
                    $requestsCount = TimeOffRequest::where('policy_id', $p->id)->count();
                    $totalChildren = $enrollmentsCount + $requestsCount;

                    $deleteItems = [['label' => 'Policy Record', 'count' => 1]];
                    if ($enrollmentsCount > 0) $deleteItems[] = ['label' => 'Employee Enrollments', 'count' => $enrollmentsCount];
                    if ($requestsCount > 0)    $deleteItems[] = ['label' => 'Time Off Requests', 'count' => $requestsCount];

                    return [
                        'composite_id'    => "time_off_policies:{$p->id}",
                        'table_key'       => 'time_off_policies',
                        'table_label'     => 'Policy',
                        'record_id'       => $p->id,
                        'name'            => $p->name,
                        'subtitle'        => "Policy ID #{$p->id} • {$enrollmentsCount} enrolled staff",
                        'status'          => $p->active ? 'Active' : 'Inactive',
                        'inactive_status' => 'Inactive',
                        'is_inactive'     => !(bool) $p->active,
                        'parent_info'     => "Root Leave Policy",
                        'total_children'  => $totalChildren,
                        'can_restore'     => true,
                        'orphan_reason'   => null,
                        'restore_summary' => "Restores this policy to Active status.",
                        'delete_summary'  => "Permanently deletes this policy" . ($totalChildren > 0 ? " and removes {$enrollmentsCount} employee enrollments and {$requestsCount} requests." : "."),
                        'restore_items'   => [['label' => 'Active Policy Status', 'count' => 1]],
                        'delete_items'    => $deleteItems,
                        'created_at'      => $p->created_at?->format('M d, Y') ?? 'N/A',
                        'updated_at'      => $p->updated_at?->format('M d, Y h:i A') ?? 'N/A',
                    ];
                });

            default:
                return collect();
        }
    }

    /**
     * Get inactive records across tables or for a specific table (backwards compatible).
     */
    public static function getInactiveRecords(string $tableKey = 'all', ?string $search = null): Collection
    {
        $tables = self::getTablesSummary();
        $results = collect();

        $keysToProcess = ($tableKey === 'all' || !isset($tables[$tableKey]))
            ? array_keys($tables)
            : [$tableKey];

        foreach ($keysToProcess as $k) {
            $records = self::getTableRecords($k, 'inactive', $search);
            $results = $results->concat($records);
        }

        return $results->sortByDesc('updated_at')->values();
    }

    /**
     * Soft delete / inactivate a record.
     */
    public static function inactivateRecord(string $tableKey, int $id, ?int $protectedSuperAdminId = null): array
    {
        switch ($tableKey) {
            case 'employees':
                $user = User::find($id);
                if ($user) {
                    if ($user->isSuperAdmin() || ($protectedSuperAdminId && $user->id === $protectedSuperAdminId)) {
                        return ['inactivated' => 0, 'error' => 'Super Admin accounts cannot be inactivated.'];
                    }
                    $user->update(['status' => 'Inactive']);
                    return ['inactivated' => 1];
                }
                break;

            case 'jobs':
                $job = Assignment::find($id);
                if ($job) {
                    $job->update(['status' => 'inactive']);
                    return ['inactivated' => 1];
                }
                break;

            case 'tasks':
                $task = Task::find($id);
                if ($task) {
                    $task->update(['status' => 'inactive']);
                    return ['inactivated' => 1];
                }
                break;

            case 'time_logs':
                $log = TimeLog::find($id);
                if ($log) {
                    $log->update(['is_archived' => true, 'archived_at' => now()]);
                    return ['inactivated' => 1];
                }
                break;

            case 'schedules':
                $sched = Schedule::find($id);
                if ($sched) {
                    $sched->update(['status' => 'inactive']);
                    return ['inactivated' => 1];
                }
                break;

            case 'departments':
                $dept = Department::find($id);
                if ($dept) {
                    $dept->update(['status' => 'Inactive']);
                    return ['inactivated' => 1];
                }
                break;

            case 'locations':
                $loc = Location::find($id);
                if ($loc) {
                    $loc->update(['status' => 'Inactive']);
                    return ['inactivated' => 1];
                }
                break;

            case 'customers':
                $cust = Customer::find($id);
                if ($cust) {
                    $cust->update(['active' => false]);
                    return ['inactivated' => 1];
                }
                break;

            case 'time_off_policies':
                $policy = TimeOffPolicy::find($id);
                if ($policy) {
                    $policy->update(['active' => false]);
                    return ['inactivated' => 1];
                }
                break;
        }

        return ['inactivated' => 0];
    }

    /**
     * Restore a single record with strict Orphan Prevention Rule enforcement.
     */
    public static function restoreRecord(string $tableKey, int $id): array
    {
        // 1. Verify Orphan Prevention Guard
        $orphanCheck = self::canRestoreRecord($tableKey, $id);
        if (!$orphanCheck['allowed']) {
            return [
                'restored'               => 0,
                'child_records_restored' => 0,
                'blocked'                => true,
                'error'                  => $orphanCheck['reason'],
                'blocking_parent'        => $orphanCheck['blocking_parent'],
            ];
        }

        return DB::transaction(function () use ($tableKey, $id) {
            $counts = ['restored' => 0, 'child_records_restored' => 0, 'blocked' => false];

            switch ($tableKey) {
                case 'employees':
                    $user = User::find($id);
                    if ($user) {
                        $user->update(['status' => 'Active']);
                        $counts['restored']++;
                        $logsRestored = TimeLog::where('employee_id', $id)->where('is_archived', true)->update([
                            'is_archived' => false,
                            'archived_at' => null,
                            'archived_by' => null,
                        ]);
                        $counts['child_records_restored'] += $logsRestored;
                    }
                    break;

                case 'jobs':
                    $job = Assignment::find($id);
                    if ($job) {
                        $job->update(['status' => 'active']);
                        $counts['restored']++;
                        $tasksRestored = Task::where('assignment_id', $id)
                            ->whereIn('status', ['inactive', 'Inactive', 'archived', 'Archived'])
                            ->update(['status' => 'active']);
                        $logsRestored = TimeLog::where('assignment_id', $id)->where('is_archived', true)->update([
                            'is_archived' => false,
                            'archived_at' => null,
                            'archived_by' => null,
                        ]);
                        $counts['child_records_restored'] += ($tasksRestored + $logsRestored);
                    }
                    break;

                case 'tasks':
                    $task = Task::find($id);
                    if ($task) {
                        $task->update(['status' => 'active']);
                        $counts['restored']++;
                        $logsRestored = TimeLog::where('task_id', $id)->where('is_archived', true)->update([
                            'is_archived' => false,
                            'archived_at' => null,
                            'archived_by' => null,
                        ]);
                        $counts['child_records_restored'] += $logsRestored;
                    }
                    break;

                case 'time_logs':
                    $log = TimeLog::find($id);
                    if ($log) {
                        $log->update(['is_archived' => false, 'archived_at' => null, 'archived_by' => null]);
                        $counts['restored']++;
                        $childSwitches = TimeLog::where('parent_log_id', $id)->update([
                            'is_archived' => false,
                            'archived_at' => null,
                            'archived_by' => null,
                        ]);
                        $counts['child_records_restored'] += $childSwitches;
                    }
                    break;

                case 'schedules':
                    $sched = Schedule::find($id);
                    if ($sched) {
                        $sched->update(['status' => 'scheduled']);
                        $counts['restored']++;
                    }
                    break;

                case 'departments':
                    $dept = Department::find($id);
                    if ($dept) {
                        $dept->update(['status' => 'Active']);
                        $counts['restored']++;
                    }
                    break;

                case 'locations':
                    $loc = Location::find($id);
                    if ($loc) {
                        $loc->update(['status' => 'Active']);
                        $counts['restored']++;
                    }
                    break;

                case 'customers':
                    $cust = Customer::find($id);
                    if ($cust) {
                        $cust->update(['active' => true]);
                        $counts['restored']++;
                    }
                    break;

                case 'time_off_policies':
                    $policy = TimeOffPolicy::find($id);
                    if ($policy) {
                        $policy->update(['active' => true]);
                        $counts['restored']++;
                    }
                    break;
            }

            return $counts;
        });
    }

    /**
     * Permanently delete a single record and cascade all child records.
     */
    public static function permanentlyDeleteRecord(string $tableKey, int $id, ?int $protectedSuperAdminId = null): array
    {
        return DB::transaction(function () use ($tableKey, $id, $protectedSuperAdminId) {
            $counts = ['deleted' => 0, 'child_records_deleted' => 0];

            switch ($tableKey) {
                case 'employees':
                    $user = User::find($id);
                    if ($user) {
                        if ($user->isSuperAdmin() || ($protectedSuperAdminId && $user->id === $protectedSuperAdminId)) {
                            return $counts; // Strictly protect Super Admin account
                        }

                        $counts['child_records_deleted'] += TimeLogAttachment::where('user_id', $id)->delete();
                        $counts['child_records_deleted'] += TimeLogNote::where('user_id', $id)->delete();
                        $counts['child_records_deleted'] += TimeLog::where('employee_id', $id)->delete();
                        $counts['child_records_deleted'] += Schedule::where('employee_id', $id)->delete();
                        $counts['child_records_deleted'] += TimeOffRequest::where('employee_id', $id)->delete();
                        $counts['child_records_deleted'] += TimeOffPolicyEmployee::where('employee_id', $id)->delete();
                        User::where('manager_id', $id)->update(['manager_id' => null]);
                        if (Schema::hasColumn('tbl_assignments', 'employee_id')) {
                            Assignment::where('employee_id', $id)->update(['employee_id' => null]);
                        }

                        $user->delete();
                        $counts['deleted']++;
                    }
                    break;

                case 'jobs':
                    $job = Assignment::find($id);
                    if ($job) {
                        $taskIds = Task::where('assignment_id', $id)->pluck('id')->toArray();
                        if (!empty($taskIds)) {
                            $counts['child_records_deleted'] += TimeLog::whereIn('task_id', $taskIds)->delete();
                            $counts['child_records_deleted'] += Schedule::whereIn('task_id', $taskIds)->delete();
                            $counts['child_records_deleted'] += Task::whereIn('id', $taskIds)->delete();
                        }
                        $counts['child_records_deleted'] += TimeLog::where('assignment_id', $id)->delete();
                        $counts['child_records_deleted'] += Schedule::where('assignment_id', $id)->delete();

                        $job->delete();
                        $counts['deleted']++;
                    }
                    break;

                case 'tasks':
                    $task = Task::find($id);
                    if ($task) {
                        $counts['child_records_deleted'] += TimeLog::where('task_id', $id)->delete();
                        $counts['child_records_deleted'] += Schedule::where('task_id', $id)->delete();
                        $task->delete();
                        $counts['deleted']++;
                    }
                    break;

                case 'time_logs':
                    $log = TimeLog::find($id);
                    if ($log) {
                        $counts['child_records_deleted'] += TimeLog::where('parent_log_id', $id)->delete();
                        $counts['child_records_deleted'] += TimeLogNote::where('time_log_id', $id)->delete();
                        $counts['child_records_deleted'] += TimeLogAttachment::where('time_log_id', $id)->delete();
                        $log->delete();
                        $counts['deleted']++;
                    }
                    break;

                case 'schedules':
                    $sched = Schedule::find($id);
                    if ($sched) {
                        $sched->delete();
                        $counts['deleted']++;
                    }
                    break;

                case 'departments':
                    $dept = Department::find($id);
                    if ($dept) {
                        User::where('department_id', $dept->id)->update(['department_id' => null]);
                        $dept->delete();
                        $counts['deleted']++;
                    }
                    break;

                case 'locations':
                    $loc = Location::find($id);
                    if ($loc) {
                        User::where('location_id', $loc->id)->update(['location_id' => null]);
                        if (Schema::hasColumn('tbl_tasks', 'location_id')) {
                            Task::where('location_id', $loc->id)->update(['location_id' => null]);
                        }
                        $loc->delete();
                        $counts['deleted']++;
                    }
                    break;

                case 'customers':
                    $cust = Customer::find($id);
                    if ($cust) {
                        if (Schema::hasColumn('tbl_assignments', 'customer_id')) {
                            Assignment::where('customer_id', $cust->id)->update(['customer_id' => null]);
                        }
                        if (class_exists(\App\Models\Quote::class) && Schema::hasTable('quotes')) {
                            $counts['child_records_deleted'] += \App\Models\Quote::where('customer_id', $cust->id)->delete();
                        }
                        if (class_exists(\App\Models\Invoice::class) && Schema::hasTable('invoices')) {
                            $counts['child_records_deleted'] += \App\Models\Invoice::where('customer_id', $cust->id)->delete();
                        }
                        $cust->delete();
                        $counts['deleted']++;
                    }
                    break;

                case 'time_off_policies':
                    $policy = TimeOffPolicy::find($id);
                    if ($policy) {
                        $counts['child_records_deleted'] += TimeOffPolicyEmployee::where('policy_id', $policy->id)->delete();
                        $counts['child_records_deleted'] += TimeOffRequest::where('policy_id', $policy->id)->delete();
                        $policy->delete();
                        $counts['deleted']++;
                    }
                    break;
            }

            return $counts;
        });
    }

    /**
     * Bulk restore multiple records with orphan check.
     */
    public static function restoreMultiple(array $compositeIds): array
    {
        $total = [
            'restored'               => 0,
            'child_records_restored' => 0,
            'blocked_count'          => 0,
            'blocked_messages'       => [],
        ];

        foreach ($compositeIds as $composite) {
            if (!str_contains($composite, ':')) continue;
            [$tableKey, $id] = explode(':', $composite, 2);

            $res = self::restoreRecord($tableKey, (int) $id);
            if (!empty($res['blocked'])) {
                $total['blocked_count']++;
                $total['blocked_messages'][] = $res['error'];
            } else {
                $total['restored'] += $res['restored'];
                $total['child_records_restored'] += $res['child_records_restored'];
            }
        }

        return $total;
    }

    /**
     * Bulk permanently delete multiple records.
     */
    public static function permanentlyDeleteMultiple(array $compositeIds, ?int $protectedSuperAdminId = null): array
    {
        $total = ['deleted' => 0, 'child_records_deleted' => 0];

        foreach ($compositeIds as $composite) {
            if (!str_contains($composite, ':')) continue;
            [$tableKey, $id] = explode(':', $composite, 2);
            $res = self::permanentlyDeleteRecord($tableKey, (int) $id, $protectedSuperAdminId);
            $total['deleted'] += $res['deleted'];
            $total['child_records_deleted'] += $res['child_records_deleted'];
        }

        return $total;
    }

    /**
     * Restore all inactive records in a specific table or across all tables.
     */
    public static function restoreAllInTable(string $tableKey): array
    {
        $records = self::getTableRecords($tableKey, 'inactive');
        $compositeIds = $records->pluck('composite_id')->toArray();
        return self::restoreMultiple($compositeIds);
    }

    /**
     * Permanently delete all inactive records in a specific table or across all tables.
     */
    public static function permanentlyDeleteAllInTable(string $tableKey, ?int $protectedSuperAdminId = null): array
    {
        $records = self::getTableRecords($tableKey, 'inactive');
        $compositeIds = $records->pluck('composite_id')->toArray();
        return self::permanentlyDeleteMultiple($compositeIds, $protectedSuperAdminId);
    }

    /**
     * Shed an entire table for production readiness (active + inactive records).
     */
    public static function shedTable(string $tableKey, ?int $currentSuperAdminId = null): array
    {
        return DataSheddingService::shedData([$tableKey], $currentSuperAdminId);
    }
}
