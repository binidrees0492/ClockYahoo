<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\On;
use App\Models\TimeLog;
use App\Models\TimeLogNote;
use App\Models\TimeLogAttachment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TimeLogDetail extends Component
{
    use WithFileUploads;

    public $show = false;
    public $logId = null;

    public $log = null;

    public $noteDraft = '';
    public $attachmentDrafts = [];

    public $toastMessage = null;
    public $toastType = 'success';

    #[On('open-log-detail')]
    public function open($logId)
    {
        $this->logId = $logId;
        $this->show = true;
        $this->noteDraft = '';
        $this->attachmentDrafts = [];
        $this->loadLog();
    }

    public function close()
    {
        $this->show = false;
        $this->logId = null;
        $this->log = null;
        $this->noteDraft = '';
        $this->attachmentDrafts = [];
    }

    public function loadLog()
    {
        if (!$this->logId) return;

        $l = TimeLog::with(['assignment', 'task', 'employee', 'approver'])
            ->find($this->logId);

        if (!$l) {
            $this->show = false;
            return;
        }

        $this->log = [
            'id'            => $l->id,
            'employee'      => $l->employee->name ?? '—',
            'assignment'    => $l->assignment->name ?? '—',
            'task'          => $l->task->name ?? '—',
            'clock_in'      => $l->clock_in?->format('D M j, Y g:i A'),
            'clock_out'     => $l->clock_out?->format('D M j, Y g:i A'),
            'break_minutes' => $l->break_minutes,
            'duration'      => number_format((($l->clock_out ? $l->duration_minutes : (int) ceil($l->netSeconds() / 60))) / 60, 2),
            'status'        => $l->status,
            'approved'      => $l->approved,
            'approved_by'   => $l->approver->name ?? null,
            'approved_at'   => $l->approved_at?->format('M j, Y g:i A'),
            'is_manual'     => $l->isManual(),
        ];
    }

    public function approve()
    {
        $l = TimeLog::find($this->logId);
        if (!$l) return;

        $l->approved    = true;
        $l->approved_by = Auth::id();
        $l->approved_at = now();
        $l->save();

        $this->loadLog();
        $this->toast('Approved.');
        $this->dispatch('log-updated', logId: $l->id);
    }

    public function unapprove()
    {
        $l = TimeLog::find($this->logId);
        if (!$l) return;

        $l->approved    = false;
        $l->approved_by = null;
        $l->approved_at = null;
        $l->save();

        $this->loadLog();
        $this->toast('Unapproved.');
        $this->dispatch('log-updated', logId: $l->id);
    }

    public function addNote()
    {
        $this->validate([
            'noteDraft' => 'required|string|min:1|max:2000',
        ]);

        if (!$this->logId) return;

        TimeLogNote::create([
            'time_log_id' => $this->logId,
            'user_id'     => Auth::id(),
            'body'        => trim($this->noteDraft),
        ]);

        $this->noteDraft = '';
        $this->loadLog();
        $this->toast('Note added.');
        $this->dispatch('log-updated', logId: $this->logId);
    }

    public function deleteNote($id)
    {
        $note = TimeLogNote::find($id);
        if ($note && $note->user_id === Auth::id()) {
            $note->delete();
            $this->loadLog();
            $this->toast('Note deleted.');
            $this->dispatch('log-updated', logId: $this->logId);
        }
    }

    public function uploadAttachments()
    {
        if (!$this->logId) return;

        if (empty($this->attachmentDrafts)) {
            $this->toast('No files selected.', 'error');
            return;
        }

        $this->validate([
            'attachmentDrafts.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt,csv,zip',
        ]);

        foreach ($this->attachmentDrafts as $file) {
            $path = $file->store("time-log-attachments/{$this->logId}", 'public');

            TimeLogAttachment::create([
                'time_log_id'   => $this->logId,
                'user_id'       => Auth::id(),
                'original_name' => $file->getClientOriginalName(),
                'path'          => $path,
                'mime_type'     => $file->getClientMimeType(),
                'size'          => $file->getSize(),
            ]);
        }

        $this->attachmentDrafts = [];
        $this->loadLog();
        $this->toast('Files uploaded.');
        $this->dispatch('log-updated', logId: $this->logId);
    }

    public function deleteAttachment($id)
    {
        $att = TimeLogAttachment::find($id);
        if ($att && $att->user_id === Auth::id()) {
            Storage::disk('public')->delete($att->path);
            $att->delete();
            $this->loadLog();
            $this->toast('Attachment deleted.');
            $this->dispatch('log-updated', logId: $this->logId);
        }
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
        $notes = collect();
        $attachments = collect();

        if ($this->logId) {
            $l = TimeLog::find($this->logId);
            if ($l) {
                $notes = $l->logNotes()->with('user')->get()->map(fn ($n) => [
                    'id'         => $n->id,
                    'body'       => $n->body,
                    'user'       => $n->user->name ?? '—',
                    'user_id'    => $n->user_id,
                    'created_at' => $n->created_at->diffForHumans(),
                ]);
                $attachments = $l->logAttachments()->get()->map(fn ($a) => [
                    'id'      => $a->id,
                    'name'    => $a->original_name,
                    'size'    => $a->humanSize(),
                    'url'     => $a->url(),
                    'user_id' => $a->user_id,
                ]);
            }
        }

        return view('livewire.time-log-detail', [
            'notes'       => $notes,
            'attachments' => $attachments,
        ]);
    }
}
