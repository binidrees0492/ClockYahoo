<?php

namespace App\Livewire\Work;

use Livewire\Component;
use App\Models\Assignment;

class Jobs extends Component
{
    public $jobId = null;
    public $name = '';
    public $location = '';
    public $status = 'active';
    public $isModalOpen = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'location' => 'nullable|string|max:255',
        'status' => 'required|in:active,inactive',
    ];

    public function openModal()
    {
        $this->resetValidation();
        $this->reset(['jobId', 'name', 'location', 'status']);
        $this->status = 'active';
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    public function edit($id)
    {
        $this->resetValidation();
        $job = Assignment::findOrFail($id);
        $this->jobId = $job->id;
        $this->name = $job->name;
        $this->location = $job->location;
        $this->status = $job->status;
        $this->isModalOpen = true;
    }

    public function save()
    {
        $this->validate();

        Assignment::updateOrCreate(
            ['id' => $this->jobId],
            [
                'name' => $this->name,
                'location' => $this->location,
                'status' => $this->status,
            ]
        );

        $this->closeModal();
    }

    public function delete($id)
    {
        Assignment::findOrFail($id)->delete();
    }

    public function render()
    {
        $jobs = Assignment::orderBy('name')->get();
        return view('livewire.work.jobs', compact('jobs'))->layout('layouts.app');
    }
}
