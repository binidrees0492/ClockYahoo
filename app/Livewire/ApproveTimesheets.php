<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\TimeLog;
use App\Models\Assignment;
use App\Models\User; // Added User model
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ApproveTimesheets extends Component
{
    public $filter = 'pending';       // all | pending | approved
    public $fromDate = null;
    public $toDate = null;
    public $assignmentFilter = null;

    // Added missing properties from blade
    public $employeeFilter = null;
    public $search = '';

    public $selected = [];            // selected log IDs for bulk
    public $selectAll = false;

    public $toastMessage = null;
    public $toastType = 'success';

    public function mount()
    {
        $this->fromDate = Carbon::now()->startOfWeek()->toDateString();
        $this->toDate   = Carbon::now()->endOfWeek()->toDateString();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = $this->baseQuery()->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->selected = [];
        }
    }

    // Added missing method called by the "Reset filters" button
    public function clearFilters()
    {
        $this->filter = 'all';
        $this->fromDate = null;
        $this->toDate = null;
        $this->assignmentFilter = null;
        $this->employeeFilter = null;
        $this->search = '';
    }

    public function approveSelected()
    {
        if (empty($this->selected)) {
            $this->toast('No rows selected.', 'error');
            return;
        }

        TimeLog::whereIn('id', $this->selected)->update([
            'approved'    => true,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        $count = count($this->selected);
        $this->selected = [];
        $this->selectAll = false;
        $this->toast("Approved {$count} entries.");
    }

    public function unapproveSelected()
    {
        if (empty($this->selected)) {
            $this->toast('No rows selected.', 'error');
            return;
        }

        TimeLog::whereIn('id', $this->selected)->update([
            'approved'    => false,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        $count = count($this->selected);
        $this->selected = [];
        $this->selectAll = false;
        $this->toast("Unapproved {$count} entries.");
    }

    public function approveOne($id)
    {
        TimeLog::where('id', $id)->update([
            'approved'    => true,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        $this->toast('Approved.');
    }

    public function unapproveOne($id)
    {
        TimeLog::where('id', $id)->update([
            'approved'    => false,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        $this->toast('Unapproved.');
    }

    public function openDetail($id)
    {
        $this->dispatch('open-log-detail', logId: $id);
    }

    private function baseQuery()
    {
        $q = TimeLog::query()->whereNotNull('clock_out');

        if ($this->filter === 'pending') {
            $q->where('approved', false);
        } elseif ($this->filter === 'approved') {
            $q->where('approved', true);
        }

        if ($this->fromDate) {
            $q->where('clock_in', '>=', Carbon::parse($this->fromDate)->startOfDay());
        }
        if ($this->toDate) {
            $q->where('clock_in', '<=', Carbon::parse($this->toDate)->endOfDay());
        }
        if ($this->assignmentFilter) {
            $q->where('assignment_id', $this->assignmentFilter);
        }

        // Added employee filter logic
        if ($this->employeeFilter) {
            $q->where('employee_id', $this->employeeFilter);
        }

        // Added search logic for Job, Task, or Employee
        if (trim($this->search) !== '') {
            $term = '%' . trim($this->search) . '%';
            $q->where(function ($w) use ($term) {
                $w->whereHas('employee', fn ($e) => $e->where('name', 'like', $term))
                    ->orWhereHas('assignment', fn ($a) => $a->where('name', 'like', $term))
                    ->orWhereHas('task', fn ($t) => $t->where('name', 'like', $term));
            });
        }

        return $q->orderByDesc('clock_in');
    }

    private function toast(string $msg, string $type = 'success')
    {
        $this->toastMessage = $msg;
        $this->toastType    = $type;
    }

    public function dismissToast() { $this->toastMessage = null; }

    public function render()
    {
        $logs = $this->baseQuery()->with(['employee', 'assignment', 'task', 'approver'])->get();

        return view('livewire.approve-timesheets', [
            'logs'        => $logs,
            'assignments' => Assignment::orderBy('name')->get(),
            'employees'   => User::orderBy('name')->get(), // Added employees collection
        ]);
    }
}
