<?php

namespace App\Livewire\Work;

use Livewire\Component;
use App\Models\Task;

class Tasks extends Component
{
    public $taskId = null;
    public $name = '';
    public $status = 'active';
    public $isModalOpen = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'status' => 'required|in:active,inactive',
    ];

    public function openModal()
    {
        $this->resetValidation();
        $this->reset(['taskId', 'name', 'status']);
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
        $task = Task::findOrFail($id);
        $this->taskId = $task->id;
        $this->name = $task->name;
        $this->status = $task->status;
        $this->isModalOpen = true;
    }

    public function save()
    {
        $this->validate();

        Task::updateOrCreate(
            ['id' => $this->taskId],
            [
                'name' => $this->name,
                'status' => $this->status,
            ]
        );

        $this->closeModal();
    }

    public function delete($id)
    {
        Task::findOrFail($id)->delete();
    }

    public function render()
    {
        $tasks = Task::orderBy('name')->get();
        return view('livewire.work.tasks', compact('tasks'))->layout('layouts.app');
    }
}
