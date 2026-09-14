<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Assignment;
use App\Models\Task;
use App\Models\TimeLog;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class Timesheets extends Component
{
    public $weekOffset = 0;

    public $editingLogId = null;
    public $editAssignment = null;
    public $editTask = null;
    public $editClockIn = null;
    public $editClockOut = null;
    public $editNotes = null;

    public $showAddModal = false;
    public $addAssignment = null;
    public $addTask = null;
    public $addClockIn = null;
    public $addClockOut = null;
    public $addNotes = null;

    public $toastMessage = null;
    public $toastType = 'success';

    public function previousWeek() { $this->weekOffset--; }
    public function nextWeek()     { $this->weekOffset++; }
    public function thisWeek()     { $this->weekOffset = 0; }

    public function startEdit($logId)
    {
        $log = TimeLog::where('employee_id', Auth::id())->findOrFail($logId);

        $this->editingLogId   = $log->id;
        $this->editAssignment = $log->assignment_id;
        $this->editTask       = $log->task_id;
        $this->editClockIn    = $log->clock_in?->format('Y-m-d\TH:i');
        $this->editClockOut   = $log->clock_out?->format('Y-m-d\TH:i');
        $this->editNotes      = $log->notes;
    }

    public function cancelEdit()
    {
        $this->editingLogId = null;
        $this->reset(['editAssignment','editTask','editClockIn','editClockOut','editNotes']);
    }

    public function saveEdit()
    {
        $this->validate([
            'editAssignment' => 'required|exists:tbl_assignments,id',
            'editTask'       => 'required|exists:tbl_tasks,id',
            'editClockIn'    => 'required|date',
            'editClockOut'   => 'nullable|date|after:editClockIn',
        ]);

        $log = TimeLog::where('employee_id', Auth::id())->findOrFail($this->editingLogId);

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
        $log = TimeLog::where('employee_id', Auth::id())->findOrFail($logId);
        $log->delete();

        $this->toast('Entry deleted.');
    }

    public function openAdd()
    {
        $now = Carbon::now();
        $this->addClockIn   = $now->copy()->subHour()->format('Y-m-d\TH:i');
        $this->addClockOut  = $now->format('Y-m-d\TH:i');
        $this->showAddModal = true;
    }

    public function cancelAdd()
    {
        $this->showAddModal = false;
        $this->reset(['addAssignment','addTask','addClockIn','addClockOut','addNotes']);
    }

    public function saveAdd()
    {
        $this->validate([
            'addAssignment' => 'required|exists:tbl_assignments,id',
            'addTask'       => 'required|exists:tbl_tasks,id',
            'addClockIn'    => 'required|date',
            'addClockOut'   => 'required|date|after:addClockIn',
        ]);

        $in  = Carbon::parse($this->addClockIn);
        $out = Carbon::parse($this->addClockOut);

        TimeLog::create([
            'employee_id'      => Auth::id(),
            'assignment_id'    => $this->addAssignment,
            'task_id'          => $this->addTask,
            'clock_in'         => $in,
            'clock_out'        => $out,
            'duration_minutes' => (int) ceil($in->diffInMinutes($out)),
            'status'           => 'manual',
            'notes'            => $this->addNotes,
        ]);

        $this->cancelAdd();
        $this->toast('Entry added.');
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

    public function dismissToast() { $this->toastMessage = null; }

    public function render()
    {
        $start = Carbon::now()->startOfWeek()->addWeeks($this->weekOffset);
        $end   = $start->copy()->endOfWeek();

        $logs = TimeLog::with(['assignment','task','logNotes','logAttachments'])
            ->where('employee_id', Auth::id())
            ->whereBetween('clock_in', [$start, $end])
            ->orderBy('clock_in')
            ->get();

        return view('livewire.timesheets', [
            'startOfWeek'  => $start,
            'endOfWeek'    => $end,
            'logs'         => $logs,
            'assignments'  => Assignment::orderBy('name')->get(),
            'tasks'        => Task::orderBy('name')->get(),
            'totalMinutes' => $logs->sum(fn ($l) => $l->clock_out
                ? $l->duration_minutes
                : (int) ceil($l->netSeconds() / 60)),
        ]);
    }
}
