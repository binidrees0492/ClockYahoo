<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\TimeLog;
use App\Models\Assignment;
use App\Models\Task;
use App\Models\Department;
use App\Models\User;
use App\Models\CompanySetting;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Livewire\Attributes\On;

class TimesheetScheduleTabs extends Component
{
    public $tab = 'timesheet';
    public $selectedEmployeeId = null;
    public $selectedDepartmentId = 'all';
    public $selectedJobId = 'all';
    public $selectedTaskId = 'all';

    public function mount()
    {
        $this->selectedEmployeeId = Auth::id();
    }

    public function setTab($tab)
    {
        $this->tab = $tab;
    }

    #[On('navigate-to-timesheet')]
    public function navigateToTimesheet($date = null, $employeeId = null)
    {
        $this->tab = 'timesheet';
        if ($employeeId !== null) {
            $this->selectedEmployeeId = $employeeId;
        }
    }

    public function setEmployee($id)
    {
        $user = Auth::user();
        if ($id !== 'all' && $id != $user->id && !$user->canManageEmployee($id) && !$user->canAccessAdmin()) {
            return;
        }
        $this->selectedEmployeeId = $id;
    }

    public function setDepartment($id)
    {
        $this->selectedDepartmentId = $id;
    }

    public function setJob($id)
    {
        $this->selectedJobId = $id;
    }

    public function setTask($id)
    {
        $this->selectedTaskId = $id;
    }

    public function resetFilters()
    {
        $this->selectedEmployeeId = Auth::id();
        $this->selectedDepartmentId = 'all';
        $this->selectedJobId = 'all';
        $this->selectedTaskId = 'all';
    }

    public function submitTimesheetsForApproval()
    {
        $targetEmpId = ($this->selectedEmployeeId && $this->selectedEmployeeId !== 'all')
            ? $this->selectedEmployeeId
            : Auth::id();

        $pendingLogs = TimeLog::where('employee_id', $targetEmpId)
            ->where('approved', false)
            ->whereNotNull('clock_out')
            ->get();

        if ($pendingLogs->isEmpty()) {
            $this->dispatch('toast', message: 'No completed pending timesheet entries to submit.', type: 'info');
            return;
        }

        foreach ($pendingLogs as $log) {
            \App\Services\TimeLogComplianceService::processTimeLogSubmission($log);
        }

        $this->dispatch('toast', message: count($pendingLogs) . ' timesheet entries submitted for approval.', type: 'success');
    }

    public function render()
    {
        $user = Auth::user();
        $accessibleIds = $user->getAccessibleEmployeeIds();
        $isManagerOrHr = $user->canAccessAdmin() || $user->isManager();

        $weekStartDay = CompanySetting::getWeekStartDay();
        $startOfWeek = Carbon::now()->startOfWeek($weekStartDay);
        $endOfWeek   = $startOfWeek->copy()->addDays(6);

        $query = TimeLog::whereBetween('clock_in', [$startOfWeek, $endOfWeek->copy()->endOfDay()]);
        $pendingQuery = TimeLog::with(['assignment', 'task', 'employee'])
            ->where('approved', false)
            ->orderByDesc('clock_in')
            ->limit(10);

        // Employee filter
        if ($this->selectedEmployeeId && $this->selectedEmployeeId !== 'all') {
            if ($this->selectedEmployeeId != $user->id && !$user->canManageEmployee($this->selectedEmployeeId) && !$user->canAccessAdmin()) {
                $query->where('employee_id', $user->id);
                $pendingQuery->where('employee_id', $user->id);
            } else {
                $query->where('employee_id', $this->selectedEmployeeId);
                $pendingQuery->where('employee_id', $this->selectedEmployeeId);
            }
        } else {
            if ($accessibleIds !== null) {
                $query->whereIn('employee_id', $accessibleIds);
                $pendingQuery->whereIn('employee_id', $accessibleIds);
            }
        }

        // Department filter
        if ($this->selectedDepartmentId && $this->selectedDepartmentId !== 'all') {
            $query->whereHas('employee', function ($q) {
                $q->where('department_id', $this->selectedDepartmentId);
            });
            $pendingQuery->whereHas('employee', function ($q) {
                $q->where('department_id', $this->selectedDepartmentId);
            });
        }

        // Job filter
        if ($this->selectedJobId && $this->selectedJobId !== 'all') {
            $query->where('assignment_id', $this->selectedJobId);
            $pendingQuery->where('assignment_id', $this->selectedJobId);
        }

        // Task filter
        if ($this->selectedTaskId && $this->selectedTaskId !== 'all') {
            $query->where('task_id', $this->selectedTaskId);
            $pendingQuery->where('task_id', $this->selectedTaskId);
        }

        $weekLogs = $query->get();

        $metrics = [
            'total_entries' => $weekLogs->count(),
            'pending'       => $weekLogs->where('approved', false)->count(),
            'approved'      => $weekLogs->where('approved', true)->count(),
            'flagged'       => $weekLogs->where('is_unusual', true)->count(),
            'manual'        => $weekLogs->filter(fn($l) => $l->isManual())->count(),
            'total_hours'   => number_format($weekLogs->sum(fn($l) => $l->clock_out ? $l->duration_minutes : (int) ceil($l->netSeconds() / 60)) / 60, 2),
        ];

        $recentPending = $pendingQuery->get();

        $employeesQuery = User::notSuperAdmin()->with('department')->orderBy('name');
        if ($accessibleIds !== null) {
            $employeesQuery->whereIn('id', $accessibleIds);
        }
        $employees = $employeesQuery->get();

        $departments = Department::orderBy('name')->get();
        $jobs = Assignment::orderBy('name')->get();
        $tasks = Task::orderBy('name')->get();

        return view('livewire.timesheet-schedule-tabs', [
            'metrics'       => $metrics,
            'recentPending' => $recentPending,
            'employees'     => $employees,
            'departments'   => $departments,
            'jobs'          => $jobs,
            'tasks'         => $tasks,
            'isManagerOrHr' => $isManagerOrHr,
            'user'          => $user,
        ]);
    }
}
