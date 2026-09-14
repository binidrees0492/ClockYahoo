<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Assignment;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\TimeLogNote;
use App\Models\TimeLogAttachment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class TimeClock extends Component
{
    use WithFileUploads;

    public $assignments = [];
    public $tasks = [];

    public $selectedAssignment = null;
    public $selectedTask = null;

    public $activeLogId = null;

    public $hours = '00';
    public $minutes = '00';
    public $seconds = '00';
    public $breakHours = '00';
    public $breakMinutes = '00';
    public $breakSeconds = '00';

    public $isOnBreak = false;
    public $isActive = false;

    public $toastMessage = null;
    public $toastType = 'success';

    public $showSwitchModal = false;
    public $switchAssignment = null;
    public $switchTask = null;

    public $noteDraft = '';
    public $attachmentDrafts = [];
    public $activeNotes = [];
    public $activeAttachments = [];

    public $recentActivity = [];

    public function mount()
    {
        $this->assignments = Assignment::orderBy('name')->get();
        $this->tasks       = Task::orderBy('name')->get();

        $this->refreshState();
    }

    public function refreshState()
    {
        $log = TimeLog::where('employee_id', Auth::id())
            ->whereNull('clock_out')
            ->latest()
            ->first();

        if (!$log) {
            $this->activeLogId = null;
            $this->isActive    = false;
            $this->isOnBreak   = false;
            $this->resetTimer();
            $this->loadRecentActivity();
            return;
        }

        $this->activeLogId = $log->id;
        $this->isActive    = true;
        $this->isOnBreak   = $log->isOnBreak();

        $net = $log->netSeconds();
        $this->hours   = str_pad(intdiv($net, 3600), 2, '0', STR_PAD_LEFT);
        $this->minutes = str_pad(intdiv($net % 3600, 60), 2, '0', STR_PAD_LEFT);
        $this->seconds = str_pad($net % 60, 2, '0', STR_PAD_LEFT);

        $brk = ($log->break_minutes * 60) + $log->currentBreakSeconds();
        $this->breakHours   = str_pad(intdiv($brk, 3600), 2, '0', STR_PAD_LEFT);
        $this->breakMinutes = str_pad(intdiv($brk % 3600, 60), 2, '0', STR_PAD_LEFT);
        $this->breakSeconds = str_pad($brk % 60, 2, '0', STR_PAD_LEFT);

        $this->activeNotes = $log->logNotes()->with('user')->get()->map(fn ($n) => [
            'id'         => $n->id,
            'body'       => $n->body,
            'user'       => $n->user->name ?? '—',
            'created_at' => $n->created_at->diffForHumans(),
        ])->toArray();

        $this->activeAttachments = $log->logAttachments()->get()->map(fn ($a) => [
            'id'   => $a->id,
            'name' => $a->original_name,
            'size' => $a->humanSize(),
            'url'  => $a->url(),
            'mime' => $a->mime_type,
        ])->toArray();

        $this->loadRecentActivity();
    }

    private function loadRecentActivity()
    {
        $this->recentActivity = TimeLog::where('employee_id', Auth::id())
            ->orderByDesc('clock_in')
            ->limit(5)
            ->get()
            ->map(fn ($l) => [
                'id'        => $l->id,
                'in'        => $l->clock_in?->toIso8601String(),
                'out'       => $l->clock_out?->toIso8601String(),
                'is_manual' => $l->isManual(),
            ])
            ->toArray();
    }

    private function resetTimer()
    {
        $this->hours = $this->minutes = $this->seconds = '00';
        $this->breakHours = $this->breakMinutes = $this->breakSeconds = '00';
        $this->activeNotes = [];
        $this->activeAttachments = [];
    }

    public function clockIn()
    {
        $this->validate([
            'selectedAssignment' => 'required|exists:tbl_assignments,id',
            'selectedTask'       => 'required|exists:tbl_tasks,id',
        ]);

        $existing = TimeLog::where('employee_id', Auth::id())
            ->whereNull('clock_out')
            ->first();

        if ($existing) {
            $this->toast('You are already clocked in.', 'error');
            return;
        }

        TimeLog::create([
            'employee_id'   => Auth::id(),
            'assignment_id' => $this->selectedAssignment,
            'task_id'       => $this->selectedTask,
            'clock_in'      => Carbon::now(),
            'status'        => 'active',
        ]);

        $this->refreshState();
        $this->toast('Clocked in successfully.');
    }

    public function clockOut()
    {
        $log = TimeLog::where('employee_id', Auth::id())
            ->whereNull('clock_out')
            ->latest()
            ->first();

        if (!$log) {
            $this->toast('No active session.', 'error');
            return;
        }

        if ($log->isOnBreak()) {
            $log->break_minutes += (int) ceil($log->break_started_at->diffInMinutes(Carbon::now()));
            $log->break_started_at = null;
        }

        $log->clock_out        = Carbon::now();
        $log->duration_minutes = (int) ceil($log->netSeconds() / 60);
        $log->status           = 'completed';
        $log->save();

        $this->refreshState();
        $this->selectedAssignment = null;
        $this->selectedTask       = null;
        $this->toast('Clocked out successfully.');
    }

    public function startBreak()
    {
        $log = $this->currentLog();
        if (!$log || $log->isOnBreak()) return;

        $log->break_started_at = Carbon::now();
        $log->status           = 'on_break';
        $log->save();

        $this->refreshState();
        $this->toast('Break started.');
    }

    public function endBreak()
    {
        $log = $this->currentLog();
        if (!$log || !$log->isOnBreak()) return;

        $minutes = (int) ceil($log->break_started_at->diffInMinutes(Carbon::now()));
        $log->break_minutes   += $minutes;
        $log->break_started_at = null;
        $log->status           = 'active';
        $log->save();

        $this->refreshState();
        $this->toast("Break ended ({$minutes} min).");
    }

    public function openSwitchModal()
    {
        $log = $this->currentLog();
        if (!$log) return;

        $this->switchAssignment = $log->assignment_id;
        $this->switchTask       = $log->task_id;
        $this->showSwitchModal  = true;
    }

    public function cancelSwitch()
    {
        $this->showSwitchModal  = false;
        $this->switchAssignment = null;
        $this->switchTask       = null;
    }

    public function confirmSwitch()
    {
        $this->validate([
            'switchAssignment' => 'required|exists:tbl_assignments,id',
            'switchTask'       => 'required|exists:tbl_tasks,id',
        ]);

        $log = $this->currentLog();
        if (!$log) {
            $this->toast('No active session to switch.', 'error');
            $this->showSwitchModal = false;
            return;
        }

        if ($log->assignment_id == $this->switchAssignment
            && $log->task_id == $this->switchTask) {
            $this->toast('You selected the same Job/Task.', 'error');
            $this->showSwitchModal = false;
            return;
        }

        $log->switchTo((int) $this->switchAssignment, (int) $this->switchTask);

        $this->showSwitchModal  = false;
        $this->switchAssignment = null;
        $this->switchTask       = null;

        $this->refreshState();
        $this->toast('Switched successfully.');
    }

    public function addNote()
    {
        $this->validate([
            'noteDraft' => 'required|string|min:1|max:2000',
        ]);

        $log = $this->currentLog();
        if (!$log) {
            $this->toast('No active session.', 'error');
            return;
        }

        TimeLogNote::create([
            'time_log_id' => $log->id,
            'user_id'     => Auth::id(),
            'body'        => trim($this->noteDraft),
        ]);

        $this->noteDraft = '';
        $this->refreshState();
        $this->toast('Note added.');
    }

    public function deleteNote($id)
    {
        $note = TimeLogNote::find($id);
        if ($note && $note->user_id === Auth::id()) {
            $note->delete();
            $this->refreshState();
            $this->toast('Note deleted.');
        }
    }

    public function updatedAttachmentDrafts()
    {
        $this->validate([
            'attachmentDrafts.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt,csv,zip',
        ]);
    }

    public function uploadAttachments()
    {
        $log = $this->currentLog();
        if (!$log) {
            $this->toast('No active session.', 'error');
            return;
        }

        if (empty($this->attachmentDrafts)) {
            $this->toast('No files selected.', 'error');
            return;
        }

        $this->validate([
            'attachmentDrafts.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt,csv,zip',
        ]);

        foreach ($this->attachmentDrafts as $file) {
            $path = $file->store("time-log-attachments/{$log->id}", 'public');

            TimeLogAttachment::create([
                'time_log_id'   => $log->id,
                'user_id'       => Auth::id(),
                'original_name' => $file->getClientOriginalName(),
                'path'          => $path,
                'mime_type'     => $file->getClientMimeType(),
                'size'          => $file->getSize(),
            ]);
        }

        $this->attachmentDrafts = [];
        $this->refreshState();
        $this->toast('Files uploaded.');
    }

    public function deleteAttachment($id)
    {
        $att = TimeLogAttachment::find($id);
        if ($att && $att->user_id === Auth::id()) {
            Storage::disk('public')->delete($att->path);
            $att->delete();
            $this->refreshState();
            $this->toast('Attachment deleted.');
        }
    }

    public function openDetail($id)
    {
        $this->dispatch('open-log-detail', logId: $id);
    }

    private function currentLog(): ?TimeLog
    {
        return TimeLog::where('employee_id', Auth::id())
            ->whereNull('clock_out')
            ->latest()
            ->first();
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
        return view('livewire.time-clock');
    }
}
