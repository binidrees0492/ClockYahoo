<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\AuditLog as AuditLogModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Component
{
    use WithPagination;

    public $search = '';
    public $rangePreset = '7days';
    public $startDate;
    public $endDate;
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $pageSize = 25;

    public $selectedLogId = null;
    public $showDetailsModal = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'rangePreset' => ['except' => '7days'],
        'page' => ['except' => 1],
    ];

    public function mount()
    {
        if (!Auth::user()->canAccessAdmin()) {
            abort(403, 'Unauthorized access to Audit Log.');
        }

        $this->applyPreset('7days');
    }

    public function applyPreset(string $preset)
    {
        $this->rangePreset = $preset;
        $now = Carbon::now();

        switch ($preset) {
            case 'today':
                $this->startDate = $now->copy()->startOfDay()->format('Y-m-d');
                $this->endDate = $now->copy()->endOfDay()->format('Y-m-d');
                break;
            case '7days':
                $this->startDate = $now->copy()->subDays(6)->startOfDay()->format('Y-m-d');
                $this->endDate = $now->copy()->endOfDay()->format('Y-m-d');
                break;
            case '30days':
                $this->startDate = $now->copy()->subDays(29)->startOfDay()->format('Y-m-d');
                $this->endDate = $now->copy()->endOfDay()->format('Y-m-d');
                break;
            case 'thisMonth':
                $this->startDate = $now->copy()->startOfMonth()->format('Y-m-d');
                $this->endDate = $now->copy()->endOfMonth()->format('Y-m-d');
                break;
            case 'lastMonth':
                $this->startDate = $now->copy()->subMonth()->startOfMonth()->format('Y-m-d');
                $this->endDate = $now->copy()->subMonth()->endOfMonth()->format('Y-m-d');
                break;
            default:
                // Custom, don't override dates
                break;
        }

        $this->resetPage();
    }

    public function sortBy(string $field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function viewDetails(int $id)
    {
        $this->selectedLogId = $id;
        $this->showDetailsModal = true;
    }

    public function closeDetails()
    {
        $this->showDetailsModal = false;
        $this->selectedLogId = null;
    }

    public function render()
    {
        $query = AuditLogModel::query();

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        }

        if (trim($this->search)) {
            $s = trim($this->search);
            $query->where(function ($q) use ($s) {
                $q->where('description', 'like', "%{$s}%")
                  ->orWhere('user_name', 'like', "%{$s}%")
                  ->orWhere('action', 'like', "%{$s}%");
            });
        }

        $query->orderBy($this->sortField, $this->sortDirection);

        $logs = $query->paginate($this->pageSize);

        $selectedLog = $this->selectedLogId ? AuditLogModel::find($this->selectedLogId) : null;

        return view('livewire.audit-log', [
            'logs' => $logs,
            'selectedLog' => $selectedLog,
        ])->layout('layouts.app');
    }
}
