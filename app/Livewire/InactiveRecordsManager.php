<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\InactiveRecordsHubService;
use Illuminate\Support\Facades\Auth;

class InactiveRecordsManager extends Component
{
    // View mode: 'tables_hub' (Tabulated overview of all tables) or 'records_explorer' (records of a specific table)
    public string $viewMode = 'tables_hub';

    public string $selectedTable = 'all'; // 'all' or specific table key
    public string $statusFilter = 'all'; // 'all', 'active', 'inactive'
    public string $search = '';
    public array $selectedRecords = []; // array of composite IDs (e.g. ['jobs:1', 'employees:3'])

    // Relational Tree Modal
    public bool $showTreeModal = false;
    public ?string $treeRecordComposite = null;
    public ?array $activeTreeData = null;

    // Modal state for permanent delete confirmation and table shedding
    public bool $showConfirmModal = false;
    public string $modalMode = ''; // 'delete_single', 'delete_selected', 'delete_table', 'delete_all', 'shed_table'
    public ?string $targetComposite = null;
    public ?string $targetTableKey = null;
    public string $confirmText = '';

    public ?string $expandedRecordId = null;

    public function mount()
    {
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Unauthorized. Inactive Records Purging Hub is strictly restricted to Super Admins.');
        }
    }

    public function switchToTablesHub()
    {
        $this->viewMode = 'tables_hub';
        $this->selectedRecords = [];
        $this->expandedRecordId = null;
    }

    public function openTableExplorer(string $tableKey, string $status = 'all')
    {
        $this->viewMode = 'records_explorer';
        $this->selectedTable = $tableKey;
        $this->statusFilter = $status;
        $this->selectedRecords = [];
        $this->expandedRecordId = null;
    }

    public function setStatusFilter(string $status)
    {
        $this->statusFilter = $status;
        $this->selectedRecords = [];
        $this->expandedRecordId = null;
    }

    public function selectTable(string $key)
    {
        $this->selectedTable = $key;
        $this->selectedRecords = [];
        $this->expandedRecordId = null;
    }

    public function toggleRecord(string $compositeId)
    {
        if (in_array($compositeId, $this->selectedRecords, true)) {
            $this->selectedRecords = array_values(array_diff($this->selectedRecords, [$compositeId]));
        } else {
            $this->selectedRecords[] = $compositeId;
        }
    }

    public function selectAllInView()
    {
        $records = ($this->selectedTable === 'all')
            ? InactiveRecordsHubService::getInactiveRecords('all', $this->search)
            : InactiveRecordsHubService::getTableRecords($this->selectedTable, $this->statusFilter, $this->search);

        $this->selectedRecords = $records->pluck('composite_id')->toArray();
    }

    public function deselectAll()
    {
        $this->selectedRecords = [];
    }

    public function toggleExpand(string $compositeId)
    {
        $this->expandedRecordId = ($this->expandedRecordId === $compositeId) ? null : $compositeId;
    }

    /**
     * Inspect the full relational hierarchy tree for any record.
     */
    public function inspectRelationalTree(string $compositeId)
    {
        if (!str_contains($compositeId, ':')) return;
        [$tableKey, $id] = explode(':', $compositeId, 2);

        $tree = InactiveRecordsHubService::getRecordRelationalTree($tableKey, (int) $id);
        if ($tree) {
            $this->treeRecordComposite = $compositeId;
            $this->activeTreeData = $tree;
            $this->showTreeModal = true;
        } else {
            $this->dispatch('toast', message: 'Could not load relational hierarchy tree.', type: 'warning');
        }
    }

    public function closeTreeModal()
    {
        $this->showTreeModal = false;
        $this->treeRecordComposite = null;
        $this->activeTreeData = null;
    }

    /**
     * Restore a single inactive record with orphan guard check.
     */
    public function restoreSingle(string $compositeId)
    {
        if (!Auth::user()->isSuperAdmin() && !Auth::user()->canAccessAdmin()) {
            abort(403, 'Unauthorized.');
        }

        if (!str_contains($compositeId, ':')) return;
        [$tableKey, $id] = explode(':', $compositeId, 2);

        $res = InactiveRecordsHubService::restoreRecord($tableKey, (int) $id);

        if (!empty($res['blocked'])) {
            $this->dispatch('toast', message: $res['error'], type: 'error');
            return;
        }

        $this->selectedRecords = array_values(array_diff($this->selectedRecords, [$compositeId]));

        if ($this->showTreeModal && $this->treeRecordComposite === $compositeId) {
            $this->activeTreeData = InactiveRecordsHubService::getRecordRelationalTree($tableKey, (int) $id);
        }

        $childNote = $res['child_records_restored'] > 0
            ? " and {$res['child_records_restored']} child record(s)"
            : "";
        $this->dispatch('toast', message: "Record{$childNote} restored successfully.", type: 'success');
    }

    /**
     * Restore all currently selected records.
     */
    public function restoreSelected()
    {
        if (!Auth::user()->isSuperAdmin() && !Auth::user()->canAccessAdmin()) {
            abort(403, 'Unauthorized.');
        }

        if (empty($this->selectedRecords)) {
            $this->dispatch('toast', message: 'No records selected to restore.', type: 'warning');
            return;
        }

        $res = InactiveRecordsHubService::restoreMultiple($this->selectedRecords);
        $this->selectedRecords = [];

        if ($res['blocked_count'] > 0) {
            $firstReason = $res['blocked_messages'][0] ?? 'Parent association is inactive or missing.';
            $msg = "Restored {$res['restored']} record(s). Skipped {$res['blocked_count']} record(s) due to Orphan Prevention: {$firstReason}";
            $this->dispatch('toast', message: $msg, type: 'warning');
        } else {
            $msg = "Successfully restored {$res['restored']} record(s)" . ($res['child_records_restored'] > 0 ? " along with {$res['child_records_restored']} child record(s)." : ".");
            $this->dispatch('toast', message: $msg, type: 'success');
        }
    }

    /**
     * Soft delete / inactivate a single record.
     */
    public function inactivateSingle(string $compositeId)
    {
        if (!Auth::user()->isSuperAdmin() && !Auth::user()->canAccessAdmin()) {
            abort(403, 'Unauthorized.');
        }

        if (!str_contains($compositeId, ':')) return;
        [$tableKey, $id] = explode(':', $compositeId, 2);

        $res = InactiveRecordsHubService::inactivateRecord($tableKey, (int) $id, Auth::id());
        if (!empty($res['error'])) {
            $this->dispatch('toast', message: $res['error'], type: 'error');
            return;
        }

        $this->dispatch('toast', message: 'Record moved to inactive/archived status.', type: 'info');
    }

    /**
     * Restore all inactive records in the currently active table view.
     */
    public function restoreCurrentTable()
    {
        if (!Auth::user()->isSuperAdmin() && !Auth::user()->canAccessAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $res = InactiveRecordsHubService::restoreAllInTable($this->selectedTable);
        $this->selectedRecords = [];

        $tableLabel = ($this->selectedTable === 'all') ? 'all tables' : $this->selectedTable;

        if ($res['blocked_count'] > 0) {
            $msg = "Restored {$res['restored']} record(s) from {$tableLabel}. Skipped {$res['blocked_count']} orphan record(s) whose parents are inactive.";
            $this->dispatch('toast', message: $msg, type: 'warning');
        } else {
            $msg = "Restored {$res['restored']} record(s) from {$tableLabel}" . ($res['child_records_restored'] > 0 ? " along with {$res['child_records_restored']} child record(s)." : ".");
            $this->dispatch('toast', message: $msg, type: 'success');
        }
    }

    /**
     * Restore all inactive records across every table in the system.
     */
    public function restoreAllAcrossAllTables()
    {
        if (!Auth::user()->isSuperAdmin() && !Auth::user()->canAccessAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $res = InactiveRecordsHubService::restoreAllInTable('all');
        $this->selectedRecords = [];

        $msg = "All eligible inactive records restored ({$res['restored']} parent records, {$res['child_records_restored']} child records).";
        if ($res['blocked_count'] > 0) {
            $msg .= " ({$res['blocked_count']} orphans skipped).";
        }
        $this->dispatch('toast', message: $msg, type: 'success');
    }

    /**
     * Open confirmation modal for a single record permanent delete.
     */
    public function promptDeleteSingle(string $compositeId)
    {
        $this->modalMode = 'delete_single';
        $this->targetComposite = $compositeId;
        $this->targetTableKey = null;
        $this->confirmText = '';
        $this->showConfirmModal = true;
    }

    /**
     * Open confirmation modal for selected records permanent delete.
     */
    public function promptDeleteSelected()
    {
        if (empty($this->selectedRecords)) {
            $this->dispatch('toast', message: 'No records selected for deletion.', type: 'warning');
            return;
        }

        $this->modalMode = 'delete_selected';
        $this->targetComposite = null;
        $this->targetTableKey = null;
        $this->confirmText = '';
        $this->showConfirmModal = true;
    }

    /**
     * Open confirmation modal for all records in current table view.
     */
    public function promptDeleteCurrentTable()
    {
        $this->modalMode = 'delete_table';
        $this->targetComposite = null;
        $this->targetTableKey = $this->selectedTable;
        $this->confirmText = '';
        $this->showConfirmModal = true;
    }

    /**
     * Open confirmation modal for all inactive records in the entire database.
     */
    public function promptDeleteAllAcrossAllTables()
    {
        $this->modalMode = 'delete_all';
        $this->targetComposite = null;
        $this->targetTableKey = 'all';
        $this->confirmText = '';
        $this->showConfirmModal = true;
    }

    /**
     * Open confirmation modal for shedding an entire table (production wipe).
     */
    public function promptShedTable(string $tableKey)
    {
        $this->modalMode = 'shed_table';
        $this->targetComposite = null;
        $this->targetTableKey = $tableKey;
        $this->confirmText = '';
        $this->showConfirmModal = true;
    }

    public function closeConfirmModal()
    {
        $this->showConfirmModal = false;
        $this->modalMode = '';
        $this->targetComposite = null;
        $this->targetTableKey = null;
        $this->confirmText = '';
    }

    /**
     * Execute permanent deletion or table shed based on modalMode.
     */
    public function executeDelete()
    {
        if (!Auth::user()->isSuperAdmin() && !Auth::user()->canAccessAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $this->resetErrorBag();

        // For bulk operations and table shedding, require typing CONFIRM for safety
        if (in_array($this->modalMode, ['delete_selected', 'delete_table', 'delete_all', 'shed_table'], true)) {
            if (trim(strtoupper($this->confirmText)) !== 'CONFIRM') {
                $this->addError('confirmText', 'You must type CONFIRM to permanently execute this action.');
                return;
            }
        }

        $protectedId = Auth::id();

        switch ($this->modalMode) {
            case 'delete_single':
                if ($this->targetComposite && str_contains($this->targetComposite, ':')) {
                    [$tableKey, $id] = explode(':', $this->targetComposite, 2);
                    $res = InactiveRecordsHubService::permanentlyDeleteRecord($tableKey, (int) $id, $protectedId);
                    $this->selectedRecords = array_values(array_diff($this->selectedRecords, [$this->targetComposite]));

                    if ($this->showTreeModal && $this->treeRecordComposite === $this->targetComposite) {
                        $this->closeTreeModal();
                    }

                    $childNote = $res['child_records_deleted'] > 0 ? " and {$res['child_records_deleted']} child record(s)" : "";
                    $this->dispatch('toast', message: "Record{$childNote} permanently deleted.", type: 'info');
                }
                break;

            case 'delete_selected':
                $res = InactiveRecordsHubService::permanentlyDeleteMultiple($this->selectedRecords, $protectedId);
                $this->selectedRecords = [];
                $msg = "Permanently deleted {$res['deleted']} record(s) and {$res['child_records_deleted']} child record(s).";
                $this->dispatch('toast', message: $msg, type: 'info');
                break;

            case 'delete_table':
                $res = InactiveRecordsHubService::permanentlyDeleteAllInTable($this->targetTableKey ?? $this->selectedTable, $protectedId);
                $this->selectedRecords = [];
                $msg = "Permanently deleted all inactive records from " . ($this->targetTableKey ?? $this->selectedTable) . " ({$res['deleted']} parent records, {$res['child_records_deleted']} child records).";
                $this->dispatch('toast', message: $msg, type: 'info');
                break;

            case 'delete_all':
                $res = InactiveRecordsHubService::permanentlyDeleteAllInTable('all', $protectedId);
                $this->selectedRecords = [];
                $msg = "Permanently purged all inactive records across all tables ({$res['deleted']} parent records, {$res['child_records_deleted']} child records). Super Admin accounts preserved.";
                $this->dispatch('toast', message: $msg, type: 'info');
                break;

            case 'shed_table':
                if ($this->targetTableKey) {
                    $res = InactiveRecordsHubService::shedTable($this->targetTableKey, $protectedId);
                    $totalPurged = array_sum($res);
                    $msg = "Production table reset complete! Purged {$totalPurged} record(s) from '{$this->targetTableKey}'. Super Admin account strictly preserved.";
                    $this->dispatch('toast', message: $msg, type: 'success');
                }
                break;
        }

        $this->closeConfirmModal();
    }

    public function render()
    {
        $tablesSummary = InactiveRecordsHubService::getTablesSummary();
        $totalInactiveCount = array_sum(array_column($tablesSummary, 'inactive_count'));
        $totalAllRecordsCount = array_sum(array_column($tablesSummary, 'total_count'));

        // If in records explorer view: load filtered records
        $records = collect();
        if ($this->viewMode === 'records_explorer') {
            if ($this->selectedTable === 'all') {
                $records = InactiveRecordsHubService::getInactiveRecords('all', $this->search);
            } else {
                $records = InactiveRecordsHubService::getTableRecords($this->selectedTable, $this->statusFilter, $this->search);
            }
        } else {
            // For tables_hub or modal calculations:
            $records = InactiveRecordsHubService::getInactiveRecords('all', $this->search);
        }

        // Pre-calculate target modal deletion stats if modal is open
        $modalStats = null;
        if ($this->showConfirmModal) {
            if ($this->modalMode === 'delete_single' && $this->targetComposite) {
                [$k, $recId] = explode(':', $this->targetComposite, 2);
                $target = InactiveRecordsHubService::getTableRecords($k, 'all')->firstWhere('record_id', (int) $recId);
                $modalStats = [
                    'record_count'   => 1,
                    'record_name'    => $target['name'] ?? 'Record',
                    'child_count'    => $target['total_children'] ?? 0,
                    'delete_items'   => $target['delete_items'] ?? [],
                    'delete_summary' => $target['delete_summary'] ?? 'Permanently delete this record and all child associations.',
                ];
            } elseif ($this->modalMode === 'delete_selected') {
                $selectedCollection = $records->whereIn('composite_id', $this->selectedRecords);
                $modalStats = [
                    'record_count'   => count($this->selectedRecords),
                    'record_name'    => count($this->selectedRecords) . ' Selected Records',
                    'child_count'    => $selectedCollection->sum('total_children'),
                    'delete_items'   => [],
                    'delete_summary' => "Permanently deleting these " . count($this->selectedRecords) . " records will also cascade and delete " . $selectedCollection->sum('total_children') . " child records.",
                ];
            } elseif ($this->modalMode === 'delete_table') {
                $tableRecords = InactiveRecordsHubService::getInactiveRecords($this->targetTableKey ?? $this->selectedTable);
                $modalStats = [
                    'record_count'   => $tableRecords->count(),
                    'record_name'    => 'All Inactive Records in ' . ($tablesSummary[$this->targetTableKey ?? $this->selectedTable]['label'] ?? 'Table'),
                    'child_count'    => $tableRecords->sum('total_children'),
                    'delete_items'   => [],
                    'delete_summary' => "Permanently deleting all " . $tableRecords->count() . " inactive records in this table will also cascade and delete " . $tableRecords->sum('total_children') . " child records.",
                ];
            } elseif ($this->modalMode === 'delete_all') {
                $allRecords = InactiveRecordsHubService::getInactiveRecords('all');
                $modalStats = [
                    'record_count'   => $allRecords->count(),
                    'record_name'    => 'All Inactive Records Across All Tables',
                    'child_count'    => $allRecords->sum('total_children'),
                    'delete_items'   => [],
                    'delete_summary' => "Permanently deleting all " . $allRecords->count() . " inactive records across the entire database will also cascade and delete " . $allRecords->sum('total_children') . " child records.",
                ];
            } elseif ($this->modalMode === 'shed_table') {
                $tblInfo = $tablesSummary[$this->targetTableKey] ?? null;
                $modalStats = [
                    'record_count'   => $tblInfo['total_count'] ?? 0,
                    'record_name'    => ($tblInfo['label'] ?? $this->targetTableKey) . ' (Production Wipe)',
                    'child_count'    => 0,
                    'delete_items'   => [],
                    'delete_summary' => "Wipes all active and inactive records in " . ($tblInfo['label'] ?? $this->targetTableKey) . " to prepare the database for production. Super Admin account is strictly preserved.",
                ];
            }
        }

        return view('livewire.inactive-records-manager', [
            'tablesSummary'        => $tablesSummary,
            'totalInactiveCount'   => $totalInactiveCount,
            'totalAllRecordsCount' => $totalAllRecordsCount,
            'records'              => $records,
            'modalStats'           => $modalStats,
        ])->layout('layouts.app');
    }
}
