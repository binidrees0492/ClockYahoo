<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\DataSheddingService;
use Illuminate\Support\Facades\Auth;

class DataShedding extends Component
{
    public array $selectedEntities = [];
    public string $confirmText = '';
    public bool $showConfirmModal = false;
    public ?array $lastResults = null;

    public function mount()
    {
        if (!Auth::check() || !Auth::user()->isSuperAdmin()) {
            abort(403, 'Unauthorized. Production data shedding is restricted exclusively to Super Admins.');
        }

        // Default selection: empty or customizable
        $this->selectedEntities = [];
    }

    public function selectAll()
    {
        $entities = DataSheddingService::getAvailableEntities();
        $this->selectedEntities = array_keys($entities);
    }

    public function deselectAll()
    {
        $this->selectedEntities = [];
    }

    public function toggleEntity(string $key)
    {
        if (in_array($key, $this->selectedEntities, true)) {
            $this->selectedEntities = array_values(array_diff($this->selectedEntities, [$key]));
        } else {
            $this->selectedEntities[] = $key;
        }
    }

    public function promptConfirmation()
    {
        if (empty($this->selectedEntities)) {
            $this->dispatch('toast', message: 'Please select at least one data category to shed.', type: 'warning');
            return;
        }

        $this->confirmText = '';
        $this->showConfirmModal = true;
    }

    public function cancelConfirmation()
    {
        $this->showConfirmModal = false;
        $this->confirmText = '';
    }

    public function executePurge()
    {
        if (!Auth::check() || !Auth::user()->isSuperAdmin()) {
            abort(403, 'Unauthorized.');
        }

        if (trim(strtoupper($this->confirmText)) !== 'CONFIRM') {
            $this->dispatch('toast', message: 'You must type CONFIRM to proceed with data shedding.', type: 'error');
            return;
        }

        if (empty($this->selectedEntities)) {
            $this->dispatch('toast', message: 'No tables selected for shedding.', type: 'warning');
            $this->showConfirmModal = false;
            return;
        }

        $results = DataSheddingService::shedData($this->selectedEntities, Auth::id());
        $this->lastResults = $results;
        $this->showConfirmModal = false;
        $this->confirmText = '';
        $this->selectedEntities = [];

        $totalDeleted = array_sum($results);
        $msg = "Production data shedding complete! Successfully purged {$totalDeleted} records across " . count($results) . " category(s). Your Super Admin account is safe.";
        session()->flash('message', $msg);
        $this->dispatch('toast', message: $msg, type: 'success');
    }

    public function render()
    {
        $entities = DataSheddingService::getAvailableEntities();

        return view('livewire.data-shedding', [
            'entities' => $entities,
        ])->layout('layouts.app');
    }
}
