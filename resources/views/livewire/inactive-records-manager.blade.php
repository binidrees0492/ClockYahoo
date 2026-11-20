<div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">

    {{-- HEADER BANNER --}}
    <div class="bg-gradient-to-r from-slate-900 via-sky-950 to-blue-900 rounded-xl shadow-lg p-6 text-white border border-sky-800">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black uppercase tracking-wider bg-sky-500/30 text-sky-200 border border-sky-400/40">
                        🛡️ Master Database Management &amp; Archive Hub
                    </span>
                    <span class="text-xs text-sky-200 font-semibold">Super Admin Exclusive</span>
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2.5">
                    <i class="fa-solid fa-database text-sky-300"></i>
                    Inactive &amp; Deleted Records Manager
                </h1>
                <p class="text-xs text-sky-100/90 mt-1.5 max-w-2xl leading-relaxed">
                    Tabulated management for all database tables, active &amp; inactive records, cascading relational hierarchy trees, orphan restoration guards, and production data shedding.
                </p>
            </div>

            {{-- View Mode & Global Quick Actions --}}
            <div class="flex flex-wrap items-center gap-2 self-start md:self-auto">
                {{-- View Mode Switcher --}}
                <div class="inline-flex rounded-lg p-1 bg-white/10 border border-white/20">
                    <button type="button"
                            wire:click="switchToTablesHub"
                            class="px-3 py-1.5 rounded-md text-xs font-bold transition flex items-center gap-1.5
                                   {{ $viewMode === 'tables_hub' ? 'bg-white text-slate-900 shadow-sm' : 'text-sky-100 hover:text-white' }}">
                        <i class="fa-solid fa-table-cells"></i>
                        Tables Overview
                    </button>
                    <button type="button"
                            wire:click="openTableExplorer('{{ $selectedTable === 'all' ? 'jobs' : $selectedTable }}')"
                            class="px-3 py-1.5 rounded-md text-xs font-bold transition flex items-center gap-1.5
                                   {{ $viewMode === 'records_explorer' ? 'bg-white text-slate-900 shadow-sm' : 'text-sky-100 hover:text-white' }}">
                        <i class="fa-solid fa-list-check"></i>
                        Records Explorer
                    </button>
                </div>

                @if($totalInactiveCount > 0)
                    <button type="button"
                            wire:click="restoreAllAcrossAllTables"
                            wire:confirm="Restore all inactive records that have valid parent associations?"
                            class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition shadow-sm flex items-center gap-1.5">
                        <i class="fa-solid fa-rotate-left"></i>
                        Restore All ({{ $totalInactiveCount }})
                    </button>
                    <button type="button"
                            wire:click="promptDeleteAllAcrossAllTables"
                            class="px-3 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-bold transition shadow-sm flex items-center gap-1.5">
                        <i class="fa-solid fa-trash-can"></i>
                        Purge All Inactive ({{ $totalInactiveCount }})
                    </button>
                @endif
                <a href="{{ route('admin.data-shedding') }}" class="px-3 py-2 bg-amber-500/20 hover:bg-amber-500/30 text-amber-200 border border-amber-400/40 rounded-lg text-xs font-bold transition flex items-center gap-1.5">
                    <i class="fa-solid fa-broom-ball"></i>
                    Shedding
                </a>
            </div>
        </div>

        {{-- Metrics Ticker --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-5 pt-4 border-t border-sky-800/80 text-xs">
            <div class="bg-white/5 rounded-lg p-2.5 border border-white/10">
                <span class="text-sky-300 font-medium block text-[11px]">Total Database Records</span>
                <span class="text-xl font-black text-white mt-0.5 block">{{ number_format($totalAllRecordsCount) }}</span>
            </div>
            <div class="bg-white/5 rounded-lg p-2.5 border border-white/10">
                <span class="text-sky-300 font-medium block text-[11px]">Total Inactive Records</span>
                <span class="text-xl font-black text-amber-300 mt-0.5 block">{{ number_format($totalInactiveCount) }}</span>
            </div>
            <div class="bg-white/5 rounded-lg p-2.5 border border-white/10">
                <span class="text-sky-300 font-medium block text-[11px]">Database Tables</span>
                <span class="text-xl font-black text-white mt-0.5 block">{{ count($tablesSummary) }}</span>
            </div>
            <div class="bg-white/5 rounded-lg p-2.5 border border-white/10">
                <span class="text-sky-300 font-medium block text-[11px]">Selected For Action</span>
                <span class="text-xl font-black text-sky-200 mt-0.5 block">{{ count($selectedRecords) }}</span>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- VIEW MODE 1: TABULATED TABLES OVERVIEW (MASTER TABLES HUB) --}}
    {{-- ========================================================================= --}}
    @if($viewMode === 'tables_hub')
        <div class="bg-white border border-gray-200 rounded-xl shadow-xs overflow-hidden">
            <div class="p-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gray-50/70">
                <div>
                    <h2 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                        <i class="fa-solid fa-table text-sky-600"></i>
                        System Database Tables Directory
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Interactive summary of all database entities with live row counts, active/inactive distribution, and table-level actions.
                    </p>
                </div>
                <div class="text-xs text-gray-500 font-medium">
                    Showing <strong class="text-gray-900">{{ count($tablesSummary) }}</strong> core business tables
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-100/80 text-[11px] font-black text-gray-600 uppercase tracking-wider">
                            <th class="py-3 px-4">Entity &amp; Table Name</th>
                            <th class="py-3 px-3 text-center">Total Rows</th>
                            <th class="py-3 px-3 text-center">Active</th>
                            <th class="py-3 px-3 text-center">Inactive / Archived</th>
                            <th class="py-3 px-4">Child Associations</th>
                            <th class="py-3 px-4 text-right">Super Admin Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($tablesSummary as $k => $tbl)
                            <tr class="hover:bg-sky-50/30 transition">
                                {{-- Table & Entity Name --}}
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 text-sm shadow-2xs
                                            {{ match($tbl['color'] ?? 'blue') {
                                                'blue'    => 'bg-blue-100 text-blue-700',
                                                'indigo'  => 'bg-indigo-100 text-indigo-700',
                                                'teal'    => 'bg-teal-100 text-teal-700',
                                                'amber'   => 'bg-amber-100 text-amber-700',
                                                'violet'  => 'bg-purple-100 text-purple-700',
                                                'purple'  => 'bg-purple-100 text-purple-700',
                                                'rose'    => 'bg-rose-100 text-rose-700',
                                                'emerald' => 'bg-emerald-100 text-emerald-700',
                                                'sky'     => 'bg-sky-100 text-sky-700',
                                                default   => 'bg-gray-100 text-gray-700',
                                            } }}">
                                            <i class="{{ $tbl['icon'] }}"></i>
                                        </div>
                                        <div>
                                            <div class="font-bold text-gray-900 text-xs flex items-center gap-2">
                                                <span>{{ $tbl['label'] }}</span>
                                                <code class="px-1.5 py-0.2 bg-gray-100 text-gray-600 rounded text-[10px] font-mono border border-gray-200">
                                                    {{ $tbl['table_name'] }}
                                                </code>
                                            </div>
                                            <p class="text-[11px] text-gray-500 mt-0.5">
                                                {{ $tbl['description'] }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                {{-- Total Rows --}}
                                <td class="py-3.5 px-3 text-center font-bold text-gray-800">
                                    {{ number_format($tbl['total_count']) }}
                                </td>

                                {{-- Active Rows --}}
                                <td class="py-3.5 px-3 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        {{ number_format($tbl['active_count']) }} Active
                                    </span>
                                </td>

                                {{-- Inactive Rows --}}
                                <td class="py-3.5 px-3 text-center">
                                    @if($tbl['inactive_count'] > 0)
                                        <button type="button"
                                                wire:click="openTableExplorer('{{ $k }}', 'inactive')"
                                                class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 hover:bg-amber-200 text-amber-900 border border-amber-300 transition cursor-pointer">
                                            ⚠️ {{ number_format($tbl['inactive_count']) }} Inactive
                                        </button>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gray-100 text-gray-400">
                                            0 Inactive
                                        </span>
                                    @endif
                                </td>

                                {{-- Child Dependencies --}}
                                <td class="py-3.5 px-4 text-[11px] text-gray-500 max-w-xs">
                                    <span class="block truncate" title="{{ $tbl['children_desc'] }}">
                                        {{ $tbl['children_desc'] }}
                                    </span>
                                </td>

                                {{-- Actions --}}
                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        {{-- View Table Records --}}
                                        <button type="button"
                                                wire:click="openTableExplorer('{{ $k }}')"
                                                class="px-2.5 py-1.5 bg-sky-600 hover:bg-sky-700 text-white rounded-lg text-xs font-bold transition shadow-2xs flex items-center gap-1 cursor-pointer"
                                                title="View records for {{ $tbl['label'] }}">
                                            <i class="fa-solid fa-eye text-[11px]"></i>
                                            <span>View Table</span>
                                        </button>

                                        {{-- Inactive Actions if any --}}
                                        @if($tbl['inactive_count'] > 0)
                                            <button type="button"
                                                    wire:click="selectTable('{{ $k }}'); restoreCurrentTable();"
                                                    wire:confirm="Restore all {{ $tbl['inactive_count'] }} inactive records in {{ $tbl['label'] }} that have valid parent associations?"
                                                    class="px-2 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-lg text-xs font-bold transition flex items-center gap-1"
                                                    title="Restore all inactive records">
                                                <i class="fa-solid fa-rotate-left text-[11px]"></i>
                                                <span>Restore</span>
                                            </button>

                                            <button type="button"
                                                    wire:click="promptDeleteCurrentTable"
                                                    wire:click="selectTable('{{ $k }}')"
                                                    class="px-2 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-300 rounded-lg text-xs font-bold transition flex items-center gap-1"
                                                    title="Permanently purge inactive records">
                                                <i class="fa-solid fa-trash-can text-[11px]"></i>
                                                <span>Purge</span>
                                            </button>
                                        @endif

                                        {{-- Shed / Reset Table --}}
                                        <button type="button"
                                                wire:click="promptShedTable('{{ $k }}')"
                                                class="px-2 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 rounded-lg text-xs font-semibold transition flex items-center gap-1"
                                                title="Wipe table for production (strictly protects Super Admin)">
                                            <i class="fa-solid fa-broom-ball text-[11px]"></i>
                                            <span>Reset</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- VIEW MODE 2: DETAILED RECORDS EXPLORER (SEARCH, FILTER & TREE ACTIONS) --}}
    {{-- ========================================================================= --}}
    @if($viewMode === 'records_explorer')
        {{-- BREADCRUMB & TABLE SELECTOR PILLS --}}
        <div class="bg-white border border-gray-200 rounded-xl p-3 shadow-2xs space-y-3">
            <div class="flex items-center justify-between">
                <button type="button"
                        wire:click="switchToTablesHub"
                        class="text-xs font-bold text-sky-700 hover:text-sky-900 flex items-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>&larr; Back to Tables Directory</span>
                </button>
                <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                    Viewing Table: <span class="text-sky-700 font-black">{{ $selectedTable === 'all' ? 'All Tables' : ($tablesSummary[$selectedTable]['label'] ?? $selectedTable) }}</span>
                </div>
            </div>

            {{-- Table Selector Tabs --}}
            <div class="flex flex-wrap gap-1.5 pt-1 border-t border-gray-100">
                <button type="button"
                        wire:click="selectTable('all')"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 border cursor-pointer
                               {{ $selectedTable === 'all'
                                   ? 'bg-blue-600 text-white border-blue-600 shadow-sm'
                                   : 'bg-gray-50 hover:bg-gray-100 text-gray-700 border-gray-200' }}">
                    <i class="fa-solid fa-layer-group"></i>
                    <span>All Tables</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-black {{ $selectedTable === 'all' ? 'bg-white text-blue-800' : 'bg-gray-200 text-gray-800' }}">
                        {{ $totalInactiveCount }}
                    </span>
                </button>

                @foreach($tablesSummary as $k => $t)
                    <button type="button"
                            wire:click="selectTable('{{ $k }}')"
                            class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1.5 border cursor-pointer
                                   {{ $selectedTable === $k
                                       ? 'bg-sky-700 text-white border-sky-700 shadow-sm font-bold'
                                       : 'bg-white hover:bg-gray-50 text-gray-700 border-gray-200' }}">
                        <i class="{{ $t['icon'] }} {{ $selectedTable === $k ? 'text-white' : 'text-gray-500' }}"></i>
                        <span>{{ $t['label'] }}</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-black
                                     {{ $t['inactive_count'] > 0
                                         ? ($selectedTable === $k ? 'bg-amber-300 text-gray-900' : 'bg-amber-100 text-amber-900 border border-amber-300')
                                         : ($selectedTable === $k ? 'bg-sky-800 text-white' : 'bg-gray-100 text-gray-400') }}">
                            {{ $t['inactive_count'] }}
                        </span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- TOOLBAR: STATUS TABS, SEARCH, AND BULK CONTROLS --}}
        <div class="flex flex-wrap items-center justify-between gap-3 bg-white border border-gray-200 rounded-xl p-3 shadow-2xs">
            <div class="flex flex-wrap items-center gap-2">
                {{-- Status Filter Tabs --}}
                <div class="inline-flex rounded-lg p-0.5 bg-gray-100 border border-gray-200 text-xs">
                    <button type="button"
                            wire:click="setStatusFilter('all')"
                            class="px-3 py-1 rounded-md font-bold transition
                                   {{ $statusFilter === 'all' ? 'bg-white text-gray-900 shadow-2xs' : 'text-gray-600 hover:text-gray-900' }}">
                        All Records
                    </button>
                    <button type="button"
                            wire:click="setStatusFilter('active')"
                            class="px-3 py-1 rounded-md font-bold transition
                                   {{ $statusFilter === 'active' ? 'bg-white text-emerald-800 shadow-2xs' : 'text-gray-600 hover:text-gray-900' }}">
                        Active Only
                    </button>
                    <button type="button"
                            wire:click="setStatusFilter('inactive')"
                            class="px-3 py-1 rounded-md font-bold transition
                                   {{ $statusFilter === 'inactive' ? 'bg-white text-rose-800 shadow-2xs' : 'text-gray-600 hover:text-gray-900' }}">
                        Inactive / Archived Only
                    </button>
                </div>

                {{-- Search Box --}}
                <div class="relative min-w-[200px] max-w-xs">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input type="text"
                           wire:model.live.debounce.300ms="search"
                           placeholder="Search records in table..."
                           class="w-full pl-8 pr-3 py-1 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-sky-600 focus:border-sky-600">
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                {{-- Multi-Select Controls --}}
                @if($records->count() > 0)
                    <button type="button" wire:click="selectAllInView" class="text-xs text-blue-600 hover:text-blue-800 font-semibold underline px-1 cursor-pointer">
                        Select All ({{ $records->count() }})
                    </button>
                    @if(count($selectedRecords) > 0)
                        <span class="text-gray-300">|</span>
                        <button type="button" wire:click="deselectAll" class="text-xs text-gray-600 hover:text-gray-800 font-semibold underline px-1 cursor-pointer">
                            Deselect All
                        </button>
                    @endif
                @endif

                {{-- Actions for Selected Records --}}
                @if(count($selectedRecords) > 0)
                    <div class="flex items-center gap-1.5 ml-2 pl-2 border-l border-gray-200">
                        <button type="button"
                                wire:click="restoreSelected"
                                class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition shadow-xs flex items-center gap-1 cursor-pointer">
                            <i class="fa-solid fa-rotate-left"></i>
                            Restore Selected ({{ count($selectedRecords) }})
                        </button>
                        <button type="button"
                                wire:click="promptDeleteSelected"
                                class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-bold transition shadow-xs flex items-center gap-1 cursor-pointer">
                            <i class="fa-solid fa-trash-can"></i>
                            Purge Selected ({{ count($selectedRecords) }})
                        </button>
                    </div>
                @endif
            </div>
        </div>

        {{-- RECORDS TABULATION / LIST --}}
        @if($records->isEmpty())
            <div class="bg-white border border-gray-200 rounded-xl p-12 text-center shadow-sm space-y-3">
                <div class="w-14 h-14 mx-auto rounded-full bg-sky-100 text-sky-600 flex items-center justify-center text-2xl">
                    <i class="fa-solid fa-folder-open"></i>
                </div>
                <h3 class="text-base font-bold text-gray-900">
                    No Records Found
                </h3>
                <p class="text-xs text-gray-500 max-w-md mx-auto leading-relaxed">
                    @if($search)
                        No records match query <span class="font-bold text-gray-700">"{{ $search }}"</span> under the current status filter.
                    @else
                        There are currently no records matching status filter <span class="font-bold text-gray-700">"{{ ucfirst($statusFilter) }}"</span> in this table.
                    @endif
                </p>
            </div>
        @else
            <div class="bg-white border border-gray-200 rounded-xl shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-100/80 text-[11px] font-black text-gray-600 uppercase tracking-wider">
                                <th class="py-3 px-3 w-10 text-center">
                                    <span class="sr-only">Select</span>
                                </th>
                                <th class="py-3 px-3">Record Details</th>
                                <th class="py-3 px-3 text-center">Status</th>
                                <th class="py-3 px-3">Parent Association</th>
                                <th class="py-3 px-3 text-center">Children</th>
                                <th class="py-3 px-3">Timestamps</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($records as $r)
                                @php
                                    $isSelected = in_array($r['composite_id'], $selectedRecords, true);
                                    $isExpanded = ($expandedRecordId === $r['composite_id']);
                                @endphp
                                <tr class="hover:bg-sky-50/20 transition {{ $isSelected ? 'bg-sky-50/40 ring-1 ring-sky-300' : '' }}">
                                    {{-- Checkbox --}}
                                    <td class="py-3 px-3 text-center">
                                        <input type="checkbox"
                                               value="{{ $r['composite_id'] }}"
                                               wire:click="toggleRecord('{{ $r['composite_id'] }}')"
                                               {{ $isSelected ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-sky-600 focus:ring-sky-500 w-4 h-4 cursor-pointer">
                                    </td>

                                    {{-- Record Name & Details --}}
                                    <td class="py-3 px-3">
                                        <div class="space-y-0.5">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-gray-900 text-xs">
                                                    {{ $r['name'] }}
                                                </span>
                                                <span class="px-1.5 py-0.2 rounded text-[10px] font-black uppercase tracking-wider
                                                    {{ match($r['table_key']) {
                                                        'employees'   => 'bg-blue-100 text-blue-900',
                                                        'jobs'        => 'bg-indigo-100 text-indigo-900',
                                                        'tasks'       => 'bg-teal-100 text-teal-900',
                                                        'time_logs'   => 'bg-amber-100 text-amber-900',
                                                        'schedules'   => 'bg-purple-100 text-purple-900',
                                                        'departments' => 'bg-purple-100 text-purple-900',
                                                        'locations'   => 'bg-rose-100 text-rose-900',
                                                        'customers'   => 'bg-emerald-100 text-emerald-900',
                                                        default       => 'bg-gray-100 text-gray-800',
                                                    } }}">
                                                    {{ $r['table_label'] }}
                                                </span>
                                            </div>
                                            <div class="text-[11px] text-gray-500">
                                                {{ $r['subtitle'] }}
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Status Badge --}}
                                    <td class="py-3 px-3 text-center">
                                        @if($r['is_inactive'])
                                            <span class="px-2 py-0.5 rounded text-[10px] font-black bg-rose-50 text-rose-700 border border-rose-200">
                                                🗑️ {{ $r['inactive_status'] ?? $r['status'] }}
                                            </span>
                                            @if(!$r['can_restore'])
                                                <span class="block mt-1 text-[10px] font-bold text-rose-600 leading-tight" title="{{ $r['orphan_reason'] }}">
                                                    ⚠️ Blocked Parent
                                                </span>
                                            @endif
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                Active
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Parent Info --}}
                                    <td class="py-3 px-3 text-[11px] text-gray-600 max-w-xs">
                                        <span class="block truncate" title="{{ $r['parent_info'] ?? 'Root' }}">
                                            {{ $r['parent_info'] ?? 'Root' }}
                                        </span>
                                    </td>

                                    {{-- Child Dependencies & Tree Button --}}
                                    <td class="py-3 px-3 text-center">
                                        <button type="button"
                                                wire:click="inspectRelationalTree('{{ $r['composite_id'] }}')"
                                                class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-300 transition inline-flex items-center gap-1 cursor-pointer"
                                                title="Inspect full relational tree">
                                            <i class="fa-solid fa-diagram-project text-[9px] text-sky-600"></i>
                                            <span>{{ $r['total_children'] }}</span>
                                            <span class="text-gray-400">tree</span>
                                        </button>
                                    </td>

                                    {{-- Timestamps --}}
                                    <td class="py-3 px-3 text-[10px] text-gray-400">
                                        <div>Updated: {{ $r['updated_at'] }}</div>
                                        <div>Created: {{ $r['created_at'] }}</div>
                                    </td>

                                    {{-- Row Actions --}}
                                    <td class="py-3 px-4 text-right">
                                        <div class="inline-flex items-center gap-1.5">
                                            {{-- Tree Button --}}
                                            <button type="button"
                                                    wire:click="inspectRelationalTree('{{ $r['composite_id'] }}')"
                                                    class="px-2 py-1 bg-white hover:bg-gray-50 text-gray-700 border border-gray-300 rounded-md text-[11px] font-bold transition flex items-center gap-1 cursor-pointer"
                                                    title="View relational hierarchy tree">
                                                <i class="fa-solid fa-diagram-project text-[10px] text-sky-600"></i>
                                                <span>Tree</span>
                                            </button>

                                            {{-- Restore or Inactivate --}}
                                            @if($r['is_inactive'])
                                                @if($r['can_restore'])
                                                    <button type="button"
                                                            wire:click="restoreSingle('{{ $r['composite_id'] }}')"
                                                            class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md text-[11px] font-bold transition shadow-2xs flex items-center gap-1 cursor-pointer"
                                                            title="Restore this record">
                                                        <i class="fa-solid fa-rotate-left text-[10px]"></i>
                                                        <span>Restore</span>
                                                    </button>
                                                @else
                                                    <button type="button"
                                                            wire:click="restoreSingle('{{ $r['composite_id'] }}')"
                                                            class="px-2.5 py-1 bg-rose-50 text-rose-700 border border-rose-300 rounded-md text-[11px] font-bold transition flex items-center gap-1 cursor-not-allowed opacity-90"
                                                            title="{{ $r['orphan_reason'] }}">
                                                        <i class="fa-solid fa-ban text-[10px]"></i>
                                                        <span>Blocked</span>
                                                    </button>
                                                @endif
                                            @else
                                                <button type="button"
                                                        wire:click="inactivateSingle('{{ $r['composite_id'] }}')"
                                                        wire:confirm="Mark this record as inactive/archived?"
                                                        class="px-2 py-1 bg-gray-50 hover:bg-amber-50 text-gray-700 hover:text-amber-800 border border-gray-300 hover:border-amber-300 rounded-md text-[11px] font-semibold transition flex items-center gap-1 cursor-pointer"
                                                        title="Mark record inactive">
                                                    <i class="fa-solid fa-box-archive text-[10px]"></i>
                                                    <span>Inactivate</span>
                                                </button>
                                            @endif

                                            {{-- Permanent Delete --}}
                                            <button type="button"
                                                    wire:click="promptDeleteSingle('{{ $r['composite_id'] }}')"
                                                    class="px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-300 rounded-md text-[11px] font-bold transition flex items-center gap-1 cursor-pointer"
                                                    title="Permanently hard-delete record and cascade children">
                                                <i class="fa-solid fa-trash-can text-[10px]"></i>
                                                <span>Purge</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif

    {{-- ========================================================================= --}}
    {{-- RELATIONAL HIERARCHY TREE MODAL --}}
    {{-- ========================================================================= --}}
    @if($showTreeModal && $activeTreeData)
        <div class="fixed inset-0 bg-black/70 backdrop-blur-xs flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] flex flex-col border border-gray-200 animate-in fade-in duration-200">
                {{-- Modal Header --}}
                <div class="p-4 border-b border-gray-200 flex items-center justify-between bg-gradient-to-r from-slate-900 to-sky-950 text-white rounded-t-2xl">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-sky-500/20 text-sky-300 border border-sky-400/30 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-diagram-project"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold leading-tight">
                                Relational Tree &amp; Cascade Impact Visualizer
                            </h3>
                            <p class="text-[11px] text-sky-200/80">
                                Inspect parent entities, child trees, and orphan restoration integrity
                            </p>
                        </div>
                    </div>
                    <button type="button" wire:click="closeTreeModal" class="text-sky-300 hover:text-white text-lg p-1">
                        &times;
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="p-5 overflow-y-auto space-y-4 text-xs">
                    {{-- 1. Upward Parent Associations --}}
                    <div>
                        <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <i class="fa-solid fa-arrow-up-long text-sky-600"></i>
                            <span>Parent Associations (Upward Hierarchy)</span>
                        </div>
                        @if(empty($activeTreeData['parents']))
                            <div class="p-2.5 rounded-lg bg-gray-50 border border-gray-200 text-gray-500 text-[11px] italic">
                                This is a root-level master record (no required parent dependencies).
                            </div>
                        @else
                            <div class="space-y-1.5">
                                @foreach($activeTreeData['parents'] as $p)
                                    <div class="p-2.5 rounded-lg border flex items-center justify-between
                                                {{ $p['is_active'] ? 'bg-emerald-50/60 border-emerald-200' : 'bg-rose-50 border-rose-300 ring-1 ring-rose-300' }}">
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $p['is_active'] ? 'bg-emerald-200 text-emerald-900' : 'bg-rose-200 text-rose-900' }}">
                                                {{ $p['type'] }}
                                            </span>
                                            <span class="font-bold text-gray-900">{{ $p['name'] }}</span>
                                            <span class="text-gray-500 text-[10px]">(#{{ $p['id'] }})</span>
                                        </div>
                                        <div>
                                            @if($p['is_active'])
                                                <span class="text-emerald-700 font-bold text-[10px] flex items-center gap-1">
                                                    <i class="fa-solid fa-circle-check"></i> Active
                                                </span>
                                            @else
                                                <span class="text-rose-700 font-bold text-[10px] flex items-center gap-1">
                                                    <i class="fa-solid fa-triangle-exclamation"></i> Inactive (Blocks Restore)
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- 2. Target Record Node --}}
                    <div>
                        <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <i class="fa-solid fa-bullseye text-blue-600"></i>
                            <span>Target Entity Node</span>
                        </div>
                        <div class="p-3.5 rounded-xl bg-blue-50 border border-blue-200 shadow-2xs space-y-1.5">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-gray-900 text-sm">
                                    {{ $activeTreeData['entity']['name'] }}
                                </span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase
                                    {{ $activeTreeData['entity']['is_active'] ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-rose-100 text-rose-800 border border-rose-300' }}">
                                    {{ $activeTreeData['entity']['status'] }}
                                </span>
                            </div>
                            <p class="text-gray-600 text-[11px]">
                                {{ $activeTreeData['entity']['subtitle'] }}
                            </p>
                            <div class="text-[10px] text-gray-400 pt-1 border-t border-blue-100 flex items-center justify-between">
                                <span>Created: {{ $activeTreeData['entity']['created_at'] }}</span>
                                <span>Updated: {{ $activeTreeData['entity']['updated_at'] }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- 3. Downward Children Cascade Tree --}}
                    <div>
                        <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <i class="fa-solid fa-arrow-down-long text-indigo-600"></i>
                            <span>Child Dependencies &amp; Cascading Entities</span>
                        </div>
                        @if(empty($activeTreeData['children_groups']) || $activeTreeData['total_children'] === 0)
                            <div class="p-2.5 rounded-lg bg-gray-50 border border-gray-200 text-gray-500 text-[11px] italic">
                                No child records currently linked to this entity.
                            </div>
                        @else
                            <div class="space-y-2">
                                @foreach($activeTreeData['children_groups'] as $cg)
                                    <div class="p-3 rounded-lg bg-gray-50 border border-gray-200 space-y-2">
                                        <div class="flex items-center justify-between font-bold text-gray-800 text-xs">
                                            <div class="flex items-center gap-2">
                                                <i class="{{ $cg['icon'] }} text-indigo-600"></i>
                                                <span>{{ $cg['label'] }}</span>
                                            </div>
                                            <span class="px-2 py-0.2 rounded-full text-[10px] font-black bg-indigo-100 text-indigo-800">
                                                {{ $cg['total_count'] }} linked
                                            </span>
                                        </div>

                                        {{-- Sub-items list if available --}}
                                        @if(!empty($cg['items']))
                                            <div class="space-y-1 pl-4 border-l-2 border-indigo-200 mt-1">
                                                @foreach(array_slice($cg['items'], 0, 5) as $it)
                                                    <div class="flex items-center justify-between text-[11px] text-gray-600">
                                                        <span>• {{ $it['name'] }}</span>
                                                        <span class="text-[10px] text-gray-400">{{ $it['sub_children'] ?? '' }}</span>
                                                    </div>
                                                @endforeach
                                                @if(count($cg['items']) > 5)
                                                    <div class="text-[10px] text-gray-400 italic">
                                                        ... and {{ count($cg['items']) - 5 }} more child items
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- 4. Orphan Prevention Rule Status --}}
                    @if(isset($activeTreeData['orphan_guard']))
                        <div class="p-3.5 rounded-xl border {{ $activeTreeData['orphan_guard']['allowed'] ? 'bg-emerald-50 border-emerald-200' : 'bg-rose-50 border-rose-300' }}">
                            <div class="flex items-start gap-2.5">
                                <div class="text-base {{ $activeTreeData['orphan_guard']['allowed'] ? 'text-emerald-600' : 'text-rose-600' }}">
                                    <i class="fa-solid {{ $activeTreeData['orphan_guard']['allowed'] ? 'fa-circle-check' : 'fa-ban' }}"></i>
                                </div>
                                <div class="space-y-0.5">
                                    <h4 class="font-bold text-xs {{ $activeTreeData['orphan_guard']['allowed'] ? 'text-emerald-900' : 'text-rose-900' }}">
                                        {{ $activeTreeData['orphan_guard']['allowed'] ? 'Orphan Prevention Passed' : 'Restoration Blocked by Orphan Prevention Rule' }}
                                    </h4>
                                    <p class="text-[11px] {{ $activeTreeData['orphan_guard']['allowed'] ? 'text-emerald-800' : 'text-rose-800' }} leading-relaxed">
                                        @if($activeTreeData['orphan_guard']['allowed'])
                                            All required parent associations are active and intact. This record can safely be restored.
                                        @else
                                            {{ $activeTreeData['orphan_guard']['reason'] }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- 5. Cascade Impact Preview Box --}}
                    @if(!empty($activeTreeData['cascade_breakdown']))
                        <div class="p-3.5 rounded-xl bg-gray-50 border border-gray-200 text-xs space-y-2">
                            <span class="font-bold text-gray-800 block text-[11px]">
                                🗑️ Permanent Deletion Cascade Impact:
                            </span>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($activeTreeData['cascade_breakdown'] as $cb)
                                    @if($cb['count'] > 0)
                                        <span class="px-2 py-0.5 rounded bg-white text-rose-800 border border-rose-200 text-[10px] font-semibold">
                                            {{ $cb['count'] }}x {{ $cb['label'] }}
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Modal Footer --}}
                <div class="p-4 border-t border-gray-200 flex items-center justify-between bg-gray-50 rounded-b-2xl">
                    <button type="button" wire:click="closeTreeModal" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg text-xs font-semibold transition">
                        Close
                    </button>

                    <div class="flex items-center gap-2">
                        @if(!$activeTreeData['entity']['is_active'])
                            @if($activeTreeData['orphan_guard']['allowed'])
                                <button type="button"
                                        wire:click="restoreSingle('{{ $treeRecordComposite }}')"
                                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition shadow-sm flex items-center gap-1.5 cursor-pointer">
                                    <i class="fa-solid fa-rotate-left"></i>
                                    Restore This Record
                                </button>
                            @else
                                <button type="button"
                                        disabled
                                        class="px-4 py-2 bg-gray-200 text-gray-400 rounded-lg text-xs font-bold cursor-not-allowed flex items-center gap-1.5"
                                        title="Cannot restore: Parent record is inactive or missing">
                                    <i class="fa-solid fa-ban"></i>
                                    Restoration Blocked
                                </button>
                            @endif
                        @endif

                        <button type="button"
                                wire:click="promptDeleteSingle('{{ $treeRecordComposite }}')"
                                class="px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-bold transition shadow-sm flex items-center gap-1.5 cursor-pointer">
                            <i class="fa-solid fa-trash-can"></i>
                            Permanently Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- CONFIRMATION MODAL FOR PERMANENT DELETION & TABLE SHEDDING --}}
    {{-- ========================================================================= --}}
    @if($showConfirmModal)
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 space-y-4 border border-rose-200 animate-in fade-in duration-200">
                <div class="flex items-start gap-3.5">
                    <div class="w-10 h-10 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center shrink-0 text-lg">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">
                            {{ $modalMode === 'shed_table' ? 'Confirm Production Table Reset' : 'Confirm Permanent Deletion' }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            This action is <strong class="text-rose-600 font-bold">permanent and irreversible</strong>. Super Admin accounts are safeguarded.
                        </p>
                    </div>
                </div>

                @if($modalStats)
                    <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 space-y-2 text-xs">
                        <div class="font-bold text-rose-900 flex items-center justify-between">
                            <span>Target: {{ $modalStats['record_name'] }}</span>
                            <span class="px-2 py-0.5 rounded bg-rose-200 text-rose-900 text-[10px] font-black">
                                {{ $modalStats['record_count'] }} record(s)
                            </span>
                        </div>
                        <p class="text-gray-700 leading-relaxed text-[11px]">
                            {{ $modalStats['delete_summary'] }}
                        </p>
                        @if(!empty($modalStats['delete_items']))
                            <div class="pt-2 border-t border-rose-200/80 flex flex-wrap gap-1.5">
                                @foreach($modalStats['delete_items'] as $item)
                                    <span class="px-2 py-0.5 rounded bg-white text-rose-800 border border-rose-300 text-[10px] font-semibold">
                                        {{ $item['count'] }}x {{ $item['label'] }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Safety CONFIRM input for bulk/table actions --}}
                @if(in_array($modalMode, ['delete_selected', 'delete_table', 'delete_all', 'shed_table'], true))
                    <div class="space-y-1.5 pt-2">
                        <label class="block text-xs font-bold text-gray-700">
                            Type <span class="text-rose-600 font-mono font-black">CONFIRM</span> to execute:
                        </label>
                        <input type="text"
                               wire:model="confirmText"
                               placeholder="CONFIRM"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-rose-500 focus:border-rose-500 font-mono">
                        @error('confirmText')
                            <span class="text-rose-600 text-xs font-semibold block">{{ $message }}</span>
                        @enderror
                    </div>
                @endif

                <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2.5">
                    <button type="button"
                            wire:click="closeConfirmModal"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-semibold transition">
                        Cancel
                    </button>
                    <button type="button"
                            wire:click="executeDelete"
                            class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-bold transition shadow-sm flex items-center gap-1.5">
                        <i class="fa-solid fa-trash-can"></i>
                        <span>{{ $modalMode === 'shed_table' ? 'Reset Table for Production' : 'Permanently Delete Records' }}</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
