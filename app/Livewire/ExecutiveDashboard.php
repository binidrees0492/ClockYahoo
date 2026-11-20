<?php

namespace App\Livewire;

use App\Models\Assignment;
use App\Models\Department;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ExecutiveDashboard extends Component
{
    use WithPagination;

    public $dateRange = 'this_month'; // this_week, this_month, last_month, this_quarter, all
    public $departmentFilter = 'all';
    public $employeeFilter = 'all';
    public $statusFilter = 'all';

    public $toastMessage = null;
    public $toastType = 'success';

    public function mount()
    {
        $user = Auth::user();
        if (!$user->canAccessExecutiveDashboard()) {
            abort(403, 'Unauthorized access to Executive Dashboard.');
        }
    }

    public function setDateRange($range)
    {
        $this->dateRange = $range;
        $this->resetPage();
    }

    public function getDateBounds(): array
    {
        $now = Carbon::now();
        return match ($this->dateRange) {
            'this_week'    => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'last_month'   => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()],
            'this_quarter' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            'all'          => [Carbon::parse('2020-01-01'), $now->copy()->endOfDay()],
            default        => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()], // this_month
        };
    }

    public function render()
    {
        [$start, $end] = $this->getDateBounds();

        $baseQuery = TimeLog::with(['employee.department', 'assignment', 'task'])
            ->whereBetween('clock_in', [$start, $end]);

        if ($this->departmentFilter !== 'all') {
            $baseQuery->whereHas('employee', fn($q) => $q->where('department_id', $this->departmentFilter));
        }

        if ($this->employeeFilter !== 'all') {
            $baseQuery->where('employee_id', $this->employeeFilter);
        }

        if ($this->statusFilter !== 'all') {
            if ($this->statusFilter === 'approved') {
                $baseQuery->where('approved', true);
            } elseif ($this->statusFilter === 'pending') {
                $baseQuery->where('approved', false)->where('status', '!=', 'rejected')->where('status', '!=', 'withdrawn');
            } elseif ($this->statusFilter === 'rejected') {
                $baseQuery->where('status', 'rejected');
            } elseif ($this->statusFilter === 'withdrawn') {
                $baseQuery->where('status', 'withdrawn');
            }
        }

        $allLogs = (clone $baseQuery)->get();

        // 1. Overall Hours & Labor Cost
        $totalMinutes = 0;
        $totalCost = 0.0;
        $approvedCount = 0;
        $pendingCount = 0;
        $rejectedCount = 0;
        $withdrawnCount = 0;
        $gpsVerifiedCount = 0;
        $flaggedCount = 0;
        $manualCount = 0;

        foreach ($allLogs as $log) {
            $mins = $log->clock_out ? $log->duration_minutes : (int) ceil($log->netSeconds() / 60);
            $totalMinutes += $mins;

            $rate = $log->getEffectiveRate();
            $totalCost += ($mins / 60) * $rate;

            if ($log->approved) {
                $approvedCount++;
            } elseif ($log->status === 'rejected') {
                $rejectedCount++;
            } elseif ($log->status === 'withdrawn') {
                $withdrawnCount++;
            } else {
                $pendingCount++;
            }

            if ($log->clock_in_latitude && $log->clock_in_longitude && !$log->location_flagged) {
                $gpsVerifiedCount++;
            }
            if ($log->is_unusual || $log->location_flagged) {
                $flaggedCount++;
            }
            if ($log->is_manual || $log->isManual()) {
                $manualCount++;
            }
        }

        $totalHours = round($totalMinutes / 60, 2);
        $totalLogsCount = $allLogs->count();
        $gpsComplianceRate = $totalLogsCount > 0 ? round(($gpsVerifiedCount / $totalLogsCount) * 100, 1) : 100;

        // 2. Active Headcount right now
        $activeEmployees = TimeLog::whereNull('clock_out')
            ->whereDate('clock_in', Carbon::today())
            ->distinct('employee_id')
            ->count('employee_id');

        // 3. Department distribution
        $deptDistribution = [];
        $departments = Department::orderBy('name')->get();
        foreach ($departments as $dept) {
            $deptLogs = $allLogs->filter(fn($l) => $l->employee?->department_id == $dept->id);
            $dMins = $deptLogs->sum(fn($l) => $l->clock_out ? $l->duration_minutes : (int) ceil($l->netSeconds() / 60));
            $dCost = $deptLogs->sum(fn($l) => (($l->clock_out ? $l->duration_minutes : (int) ceil($l->netSeconds() / 60)) / 60) * $l->getEffectiveRate());
            if ($dMins > 0) {
                $deptDistribution[] = [
                    'name'  => $dept->name,
                    'hours' => round($dMins / 60, 1),
                    'cost'  => round($dCost, 2),
                    'count' => $deptLogs->count(),
                ];
            }
        }

        // 4. Top 5 Jobs
        $topJobs = [];
        $jobsGroup = $allLogs->groupBy('assignment_id');
        foreach ($jobsGroup as $jobId => $jLogs) {
            $job = $jLogs->first()->assignment;
            if ($job) {
                $jMins = $jLogs->sum(fn($l) => $l->clock_out ? $l->duration_minutes : (int) ceil($l->netSeconds() / 60));
                $topJobs[] = [
                    'name'  => $job->name,
                    'hours' => round($jMins / 60, 1),
                    'count' => $jLogs->count(),
                ];
            }
        }
        usort($topJobs, fn($a, $b) => $b['hours'] <=> $a['hours']);
        $topJobs = array_slice($topJobs, 0, 5);

        // 5. Paginated audit log table
        $paginatedLogs = (clone $baseQuery)->orderBy('clock_in', 'desc')->paginate(15);

        return view('livewire.executive-dashboard', [
            'totalHours'        => $totalHours,
            'totalCost'         => round($totalCost, 2),
            'approvedCount'     => $approvedCount,
            'pendingCount'      => $pendingCount,
            'rejectedCount'     => $rejectedCount,
            'withdrawnCount'    => $withdrawnCount,
            'activeHeadcount'   => $activeEmployees,
            'gpsComplianceRate' => $gpsComplianceRate,
            'flaggedCount'      => $flaggedCount,
            'manualCount'       => $manualCount,
            'deptDistribution'  => $deptDistribution,
            'topJobs'           => $topJobs,
            'paginatedLogs'     => $paginatedLogs,
            'departments'       => $departments,
            'employees'         => User::notSuperAdmin()->orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
