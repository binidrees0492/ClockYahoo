<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Assignment;
use App\Models\Schedule;
use App\Models\TimeLog;
use App\Models\User;
use Carbon\Carbon;

class ScheduleBoard extends Component
{
    public $weekOffset = 0;
    public $weekView = 'week';       // week | month
    public $employeeFilter = null;

    public $showAddShift = false;
    public $editingShiftId = null;
    public $shiftEmployeeId = null;
    public $shiftAssignmentId = null;
    public $shiftTaskId = null;
    public $shiftDate = null;
    public $shiftStart = '08:00';
    public $shiftEnd = '17:00';
    public $shiftNotes = null;

    public $showAddJob = false;
    public $newJobName = '';

    public $showAddEmployee = false;
    public $newEmployeeName = '';
    public $newEmployeeEmail = '';

    public $toastMessage = null;
    public $toastType = 'success';

    public function previousWeek() { $this->weekOffset--; }
    public function nextWeek()     { $this->weekOffset++; }
    public function thisWeek()     { $this->weekOffset = 0; }

    public function openAddShift($employeeId = null, $date = null)
    {
        $this->reset([
            'editingShiftId', 'shiftEmployeeId', 'shiftAssignmentId',
            'shiftTaskId', 'shiftNotes',
        ]);
        $this->shiftDate       = $date ?: Carbon::now()->toDateString();
        $this->shiftEmployeeId = $employeeId;
        $this->shiftStart      = '08:00';
        $this->shiftEnd        = '17:00';
        $this->showAddShift    = true;
    }

    public function cancelShift()
    {
        $this->showAddShift = false;
        $this->reset([
            'editingShiftId', 'shiftEmployeeId', 'shiftAssignmentId',
            'shiftTaskId', 'shiftDate', 'shiftNotes',
        ]);
    }

    public function saveShift()
    {
        $this->validate([
            'shiftEmployeeId'   => 'required|exists:users,id',
            'shiftAssignmentId' => 'nullable|exists:tbl_assignments,id',
            'shiftDate'         => 'required|date',
            'shiftStart'        => 'required',
            'shiftEnd'          => 'required|after:shiftStart',
        ]);

        $data = [
            'employee_id'   => $this->shiftEmployeeId,
            'assignment_id' => $this->shiftAssignmentId,
            'task_id'       => $this->shiftTaskId,
            'shift_date'    => $this->shiftDate,
            'start_time'    => $this->shiftStart,
            'end_time'      => $this->shiftEnd,
            'notes'         => $this->shiftNotes,
        ];

        if ($this->editingShiftId) {
            Schedule::findOrFail($this->editingShiftId)->update($data);
            $this->toast('Shift updated.');
        } else {
            Schedule::create($data);
            $this->toast('Shift added.');
        }

        $this->cancelShift();
    }

    public function openEditShift($id)
    {
        $s = Schedule::find($id);
        if (!$s) return;

        $this->editingShiftId   = $s->id;
        $this->shiftEmployeeId  = $s->employee_id;
        $this->shiftAssignmentId= $s->assignment_id;
        $this->shiftTaskId      = $s->task_id;
        $this->shiftDate        = $s->shift_date?->toDateString();
        $this->shiftStart       = substr($s->start_time, 0, 5);
        $this->shiftEnd         = substr($s->end_time, 0, 5);
        $this->shiftNotes       = $s->notes;
        $this->showAddShift     = true;
    }

    public function deleteShift($id)
    {
        Schedule::find($id)?->delete();
        $this->toast('Shift deleted.');
    }

    public function openAddJob()
    {
        $this->newJobName = '';
        $this->showAddJob = true;
    }

    public function saveJob()
    {
        $this->validate(['newJobName' => 'required|string|max:100']);

        Assignment::create([
            'name'   => $this->newJobName,
            'status' => 'active',
        ]);

        $this->newJobName = '';
        $this->showAddJob = false;
        $this->toast('Job added.');
    }

    public function cancelJob()
    {
        $this->showAddJob = false;
        $this->newJobName = '';
    }

    public function openAddEmployee()
    {
        $this->newEmployeeName  = '';
        $this->newEmployeeEmail = '';
        $this->showAddEmployee  = true;
    }

    public function saveEmployee()
    {
        $this->validate([
            'newEmployeeName'  => 'required|string|max:100',
            'newEmployeeEmail' => 'required|email|unique:users,email',
        ]);

        User::create([
            'name'     => $this->newEmployeeName,
            'email'    => $this->newEmployeeEmail,
            'password' => bcrypt(str()->random(16)),
        ]);

        $this->showAddEmployee = false;
        $this->toast('Employee added.');
    }

    public function cancelEmployee()
    {
        $this->showAddEmployee = false;
        $this->newEmployeeName = '';
        $this->newEmployeeEmail = '';
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

        $jobs = Assignment::orderBy('name')->get();

        $schedules = Schedule::with(['employee', 'assignment', 'task'])
            ->whereBetween('shift_date', [$start->toDateString(), $end->toDateString()])
            ->when($this->employeeFilter, fn ($q) => $q->where('employee_id', $this->employeeFilter))
            ->get()
            ->groupBy(fn ($s) => $s->shift_date->toDateString())
            ->map(fn ($day) => $day->groupBy('employee_id'));

        // Per-employee week hours (from schedules)
        $employeeHours = [];
        foreach ($employees as $e) {
            $mins = 0;
            foreach ($schedules as $day) {
                foreach ($day[$e->id] ?? [] as $s) {
                    $mins += Carbon::parse($s->start_time)->diffInMinutes(Carbon::parse($s->end_time));
                }
            }
            $employeeHours[$e->id] = round($mins / 60, 1);
        }

        return view('livewire.schedule-board', [
            'startOfWeek'   => $start,
            'endOfWeek'     => $end,
            'days'          => collect(range(0, 6))->map(fn ($i) => $start->copy()->addDays($i)),
            'employees'     => $employees,
            'jobs'          => $jobs,
            'schedules'     => $schedules,
            'employeeHours' => $employeeHours,
            'todayKey'      => Carbon::now()->toDateString(),
        ]);
    }
}
