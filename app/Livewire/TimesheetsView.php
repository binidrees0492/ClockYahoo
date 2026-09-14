<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Assignment;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TimesheetsView extends Component
{
    public $weekOffset = 0;

    public $currencyOrTz = 'PKT';
    public $employeeFilter = null;

    public $showAddModal = false;
    public $addEmployeeId = null;
    public $addAssignment = null;
    public $addTask = null;
    public $addDate = null;
    public $addClockIn = null;
    public $addClockOut = null;
    public $addNotes = null;

    public $editingLogId = null;
    public $editAssignment = null;
    public $editTask = null;
    public $editClockIn = null;
    public $editClockOut = null;
    public $editNotes = null;

    public $toastMessage = null;
    public $toastType = 'success';

    public function previousWeek() { $this->weekOffset--; }
    public function nextWeek()     { $this->weekOffset++; }
    public function thisWeek()     { $this->weekOffset = 0; }

    public function openAdd($employeeId, $date)
    {
        $this->reset([
            'editingLogId',
            'addAssignment', 'addTask',
            'addClockIn', 'addClockOut', 'addNotes',
        ]);

        $this->addEmployeeId = $employeeId;
        $this->addDate       = $date;
        $this->addClockIn    = $date . 'T08:00';
        $this->addClockOut   = $date . 'T17:00';
        $this->showAddModal  = true;
    }

    public function cancelAdd()
    {
        $this->showAddModal = false;
        $this->reset([
            'addEmployeeId', 'addDate',
            'addAssignment', 'addTask',
            'addClockIn', 'addClockOut', 'addNotes',
        ]);
    }

    public function saveAdd()
    {
        $this->validate([
            'addEmployeeId' => 'required|exists:users,id',
            'addAssignment' => 'required|exists:tbl_assignments,id',
            'addTask'       => 'required|exists:tbl_tasks,id',
            'addClockIn'    => 'required|date',
            'addClockOut'   => 'required|date|after:addClockIn',
        ]);

        $in  = Carbon::parse($this->addClockIn);
        $out = Carbon::parse($this->addClockOut);

        TimeLog::create([
            'employee_id'      => $this->addEmployeeId,
            'assignment_id'    => $this->addAssignment,
            'task_id'          => $this->addTask,
            'clock_in'         => $in,
            'clock_out'        => $out,
            'duration_minutes' => (int) ceil($in->diffInMinutes($out)),
            'status'           => 'manual',
            'notes'            => $this->addNotes,
        ]);

        $this->cancelAdd();
        $this->toast('Time added.');
    }

    public function startEdit($logId)
    {
        $log = TimeLog::findOrFail($logId);

        $this->editingLogId    = $log->id;
        $this->editAssignment  = $log->assignment_id;
        $this->editTask        = $log->task_id;
        $this->editClockIn     = $log->clock_in?->format('Y-m-d\TH:i');
        $this->editClockOut    = $log->clock_out?->format('Y-m-d\TH:i');
        $this->editNotes       = $log->notes;
    }

    public function cancelEdit()
    {
        $this->editingLogId = null;
        $this->reset(['editAssignment', 'editTask', 'editClockIn', 'editClockOut', 'editNotes']);
    }

    public function saveEdit()
    {
        $this->validate([
            'editAssignment' => 'required|exists:tbl_assignments,id',
            'editTask'       => 'required|exists:tbl_tasks,id',
            'editClockIn'    => 'required|date',
            'editClockOut'   => 'nullable|date|after:editClockIn',
        ]);

        $log = TimeLog::findOrFail($this->editingLogId);

        $log->assignment_id = $this->editAssignment;
        $log->task_id       = $this->editTask;
        $log->clock_in      = Carbon::parse($this->editClockIn);
        $log->clock_out     = $this->editClockOut ? Carbon::parse($this->editClockOut) : null;
        $log->notes         = $this->editNotes;

        if ($log->clock_out) {
            $log->duration_minutes = (int) ceil(
                $log->clock_in->diffInMinutes($log->clock_out) - $log->break_minutes
            );
        }

        $log->save();

        $this->cancelEdit();
        $this->toast('Entry updated.');
    }

    public function deleteLog($logId)
    {
        TimeLog::findOrFail($logId)->delete();
        $this->toast('Entry deleted.');
    }

    public function openDetail($logId)
    {
        $this->dispatch('open-log-detail', logId: $logId);
    }

    private function toast(string $msg, string $type = 'success')
    {
        $this->toastMessage = $msg;
        $this->toastType    = $type;
    }

    public function dismissToast()
    {
        $this->toastMessage = null;
    }

    public function render()
    {
        $start = Carbon::now()->startOfWeek()->addWeeks($this->weekOffset);
        $end   = $start->copy()->endOfWeek();

        $employees = User::when($this->employeeFilter, fn ($q) => $q->where('id', $this->employeeFilter))
            ->orderBy('name')
            ->get();

        $logs = TimeLog::with(['assignment', 'task', 'employee'])
            ->whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('clock_in', [$start, $end])
            ->orderBy('clock_in')
            ->get();

        // Group logs: [employeeId][YYYY-MM-DD] = Collection
        $grouped = [];
        foreach ($logs as $log) {
            $empId = $log->employee_id;
            $day   = $log->clock_in->toDateString();
            $grouped[$empId][$day][] = $log;
        }

        // Per-day totals
        $dayTotals = [];
        foreach (range(0, 6) as $i) {
            $day = $start->copy()->addDays($i)->toDateString();
            $mins = 0;
            foreach ($grouped as $empId => $days) {
                if (isset($days[$day])) {
                    foreach ($days[$day] as $log) {
                        $mins += $log->clock_out
                            ? $log->duration_minutes
                            : (int) ceil($log->netSeconds() / 60);
                    }
                }
            }
            $dayTotals[$day] = $mins;
        }

        // Weekly total
        $weekTotal = array_sum($dayTotals);

        // Approval status per employee (true if all logs are approved and at least one log exists)
        $employeeApproved = [];
        foreach ($employees as $e) {
            $empLogs = collect($grouped[$e->id] ?? [])->flatten();
            $employeeApproved[$e->id] = $empLogs->count() > 0 && $empLogs->every(fn ($l) => (bool) $l->approved);
        }

        return view('livewire.timesheets-view', [
            'startOfWeek'      => $start,
            'endOfWeek'        => $end,
            'employees'        => $employees,
            'grouped'          => $grouped,
            'dayTotals'        => $dayTotals,
            'weekTotal'        => $weekTotal,
            'employeeApproved' => $employeeApproved,
            'assignments'      => Assignment::orderBy('name')->get(),
            'tasks'            => Task::orderBy('name')->get(),
        ]);
    }
}
