<div class="max-w-7xl mx-auto space-y-6 pb-12">
    {{-- Header & Range Filters --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-[#005a9c] text-white flex items-center justify-center font-bold shadow-xs">
                    <i class="fa-solid fa-gauge-high"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 leading-tight">Executive Dashboard & Workforce Audit</h1>
                    <p class="text-xs text-gray-500">Live operational intelligence, labor expenditures, geofence compliance, and locked record status.</p>
                </div>
            </div>
        </div>

        {{-- Date Range Pills --}}
        <div class="flex flex-wrap items-center gap-1 bg-white p-1 rounded-xl border border-gray-200 shadow-2xs text-xs font-semibold">
            <button type="button" wire:click="setDateRange('this_week')"
                    class="px-3 py-1.5 rounded-lg transition {{ $dateRange === 'this_week' ? 'bg-[#005a9c] text-white font-bold shadow-xs' : 'text-gray-600 hover:bg-gray-100' }}">
                This Week
            </button>
            <button type="button" wire:click="setDateRange('this_month')"
                    class="px-3 py-1.5 rounded-lg transition {{ $dateRange === 'this_month' ? 'bg-[#005a9c] text-white font-bold shadow-xs' : 'text-gray-600 hover:bg-gray-100' }}">
                This Month
            </button>
            <button type="button" wire:click="setDateRange('last_month')"
                    class="px-3 py-1.5 rounded-lg transition {{ $dateRange === 'last_month' ? 'bg-[#005a9c] text-white font-bold shadow-xs' : 'text-gray-600 hover:bg-gray-100' }}">
                Last Month
            </button>
            <button type="button" wire:click="setDateRange('this_quarter')"
                    class="px-3 py-1.5 rounded-lg transition {{ $dateRange === 'this_quarter' ? 'bg-[#005a9c] text-white font-bold shadow-xs' : 'text-gray-600 hover:bg-gray-100' }}">
                This Quarter
            </button>
            <button type="button" wire:click="setDateRange('all')"
                    class="px-3 py-1.5 rounded-lg transition {{ $dateRange === 'all' ? 'bg-[#005a9c] text-white font-bold shadow-xs' : 'text-gray-600 hover:bg-gray-100' }}">
                All Time
            </button>
        </div>
    </div>

    {{-- KEY METRIC CARDS --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Hours --}}
        <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-xs relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Tracked Time</span>
                <span class="p-2 rounded-lg bg-blue-50 text-[#005a9c] text-sm"><i class="fa-solid fa-clock"></i></span>
            </div>
            <div class="mt-2 text-2xl font-black text-gray-900">{{ number_format($totalHours, 2) }} <span class="text-xs font-semibold text-gray-500">hrs</span></div>
            <div class="mt-1 flex items-center gap-1.5 text-[11px] text-gray-500">
                <span class="inline-flex items-center gap-1 text-emerald-700 font-semibold"><i class="fa-solid fa-circle-check"></i> {{ $approvedCount }} Approved</span>
                <span>&bull;</span>
                <span class="text-amber-700 font-semibold">{{ $pendingCount }} Pending</span>
            </div>
        </div>

        {{-- Labor Expenditure --}}
        <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-xs relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Est. Labor Cost</span>
                <span class="p-2 rounded-lg bg-emerald-50 text-emerald-600 text-sm"><i class="fa-solid fa-dollar-sign"></i></span>
            </div>
            <div class="mt-2 text-2xl font-black text-emerald-700">${{ number_format($totalCost, 2) }}</div>
            <div class="mt-1 text-[11px] text-gray-500">
                Calculated using task billing & employee base pay
            </div>
        </div>

        {{-- Active Headcount --}}
        <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-xs relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Active On-Clock Now</span>
                <span class="p-2 rounded-lg bg-purple-50 text-purple-600 text-sm"><i class="fa-solid fa-user-clock"></i></span>
            </div>
            <div class="mt-2 text-2xl font-black text-purple-700">{{ $activeHeadcount }} <span class="text-xs font-semibold text-gray-500">staff</span></div>
            <div class="mt-1 text-[11px] text-emerald-600 font-semibold flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Live attendance in progress</span>
            </div>
        </div>

        {{-- Geofence Compliance --}}
        <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-xs relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">GPS Geofence Match</span>
                <span class="p-2 rounded-lg bg-sky-50 text-sky-600 text-sm"><i class="fa-solid fa-location-crosshairs"></i></span>
            </div>
            <div class="mt-2 text-2xl font-black text-sky-700">{{ $gpsComplianceRate }}%</div>
            <div class="mt-1 text-[11px] text-gray-500">
                <span>{{ $flaggedCount }} flagged</span> &bull; <span>{{ $manualCount }} manual entries</span>
            </div>
        </div>
    </div>

    {{-- LOCK STATUS BREAKDOWN BANNER (Requirement: Approved, withdrawn, or rejected are locked) --}}
    <div class="bg-slate-900 text-white rounded-xl p-4 shadow-sm flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center text-amber-400 text-lg">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <span>Audit & Immutability Rules: Locked Status Enforcement</span>
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">
                    Timesheets with status <strong>Approved</strong>, <strong>Withdrawn</strong>, or <strong>Rejected</strong> are permanently locked from modifications.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 text-xs font-semibold">
            <span class="px-3 py-1.5 rounded-lg bg-emerald-950/80 border border-emerald-500/40 text-emerald-300 inline-flex items-center gap-1.5">
                <i class="fa-solid fa-lock text-emerald-400"></i>
                <span>Approved: {{ $approvedCount }} (Locked)</span>
            </span>
            <span class="px-3 py-1.5 rounded-lg bg-rose-950/80 border border-rose-500/40 text-rose-300 inline-flex items-center gap-1.5">
                <i class="fa-solid fa-lock text-rose-400"></i>
                <span>Rejected: {{ $rejectedCount }} (Locked)</span>
            </span>
            <span class="px-3 py-1.5 rounded-lg bg-slate-800 border border-slate-600 text-slate-300 inline-flex items-center gap-1.5">
                <i class="fa-solid fa-lock text-slate-400"></i>
                <span>Withdrawn: {{ $withdrawnCount }} (Locked)</span>
            </span>
            <span class="px-3 py-1.5 rounded-lg bg-amber-950/80 border border-amber-500/40 text-amber-300 inline-flex items-center gap-1.5">
                <i class="fa-solid fa-pen text-amber-400"></i>
                <span>Pending Review: {{ $pendingCount }} (Editable)</span>
            </span>
        </div>
    </div>

    {{-- TWO COLUMN SECTION: DEPARTMENTS & TOP JOBS --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Department Distribution --}}
        <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                    <i class="fa-solid fa-building text-[#005a9c]"></i> Hours & Cost by Department
                </h3>
                <span class="text-xs text-gray-400">{{ count($deptDistribution) }} active</span>
            </div>

            @if(empty($deptDistribution))
                <div class="p-8 text-center text-gray-400 text-xs">No hours recorded in this date range.</div>
            @else
                <div class="space-y-3">
                    @foreach($deptDistribution as $dept)
                        <div class="p-3 rounded-lg border border-gray-100 bg-gray-50/50 hover:bg-blue-50/30 transition flex items-center justify-between">
                            <div>
                                <div class="text-xs font-bold text-gray-900">{{ $dept['name'] }}</div>
                                <div class="text-[11px] text-gray-500">{{ $dept['count'] }} entries logged</div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs font-bold text-[#005a9c]">{{ $dept['hours'] }} hrs</div>
                                <div class="text-[11px] font-semibold text-emerald-700">${{ number_format($dept['cost'], 2) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Top 5 Jobs by Hours --}}
        <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                    <i class="fa-solid fa-briefcase text-emerald-600"></i> Top Jobs by Hours Logged
                </h3>
                <span class="text-xs text-gray-400">Top 5</span>
            </div>

            @if(empty($topJobs))
                <div class="p-8 text-center text-gray-400 text-xs">No job activity recorded in this period.</div>
            @else
                <div class="space-y-3">
                    @foreach($topJobs as $j)
                        <div class="p-3 rounded-lg border border-gray-100 bg-gray-50/50 hover:bg-emerald-50/30 transition flex items-center justify-between">
                            <div>
                                <div class="text-xs font-bold text-gray-900 whitespace-normal break-words max-w-xs">{{ $j['name'] }}</div>
                                <div class="text-[11px] text-gray-500">{{ $j['count'] }} shifts</div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs font-bold text-emerald-800">{{ $j['hours'] }} hrs</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- AUDIT LOGS TABLE & FILTERS --}}
    <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-5 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 pb-3">
            <div>
                <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-[#005a9c]"></i> Timesheet Audit Trail (24H Time Format)
                </h3>
                <p class="text-xs text-gray-500">
                    Displaying times in 24H format using your preferred timezone ({{ Auth::user()->getTimezone() }}).
                </p>
            </div>

            {{-- Table Filters --}}
            <div class="flex flex-wrap items-center gap-2 text-xs">
                {{-- Department --}}
                <select wire:model.live="departmentFilter" class="rounded-lg border-gray-300 text-xs p-1.5 focus:border-[#005a9c] focus:ring-[#005a9c]">
                    <option value="all">🏢 All Departments</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}">{{ $d->name }}</option>
                    @endforeach
                </select>

                {{-- Status --}}
                <select wire:model.live="statusFilter" class="rounded-lg border-gray-300 text-xs p-1.5 focus:border-[#005a9c] focus:ring-[#005a9c]">
                    <option value="all">All Statuses</option>
                    <option value="approved">🔒 Approved (Locked)</option>
                    <option value="pending">⏳ Pending Review</option>
                    <option value="rejected">🔒 Rejected (Locked)</option>
                    <option value="withdrawn">🔒 Withdrawn (Locked)</option>
                </select>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                <tr class="border-b border-gray-200 bg-gray-50 text-gray-600 font-bold uppercase tracking-wider text-[10px]">
                    <th class="p-3">Employee</th>
                    <th class="p-3">Job & Task</th>
                    <th class="p-3">Date & 24H In/Out</th>
                    <th class="p-3 text-right">Hours</th>
                    <th class="p-3">GPS / Status Flags</th>
                    <th class="p-3 text-center">Audit Lock Status</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                @forelse($paginatedLogs as $log)
                    @php
                        $userTz = Auth::user()->getTimezone();
                        $inFormatted = $log->clock_in ? $log->clock_in->copy()->timezone($userTz)->format('Y-m-d H:i') : '—';
                        $outFormatted = $log->clock_out ? $log->clock_out->copy()->timezone($userTz)->format('H:i') : 'Running';
                        $mins = $log->clock_out ? $log->duration_minutes : (int) ceil($log->netSeconds() / 60);
                        $isLocked = $log->isLocked();
                    @endphp
                    <tr class="hover:bg-gray-50/70 transition">
                        {{-- Employee --}}
                        <td class="p-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-full bg-slate-700 text-white flex items-center justify-center font-bold text-[10px] overflow-hidden shrink-0">
                                    @if($log->employee?->avatar_url)
                                        <img src="{{ $log->employee->avatar_url }}" alt="" class="w-full h-full object-cover">
                                    @else
                                        <span>{{ $log->employee?->initials ?? 'U' }}</span>
                                    @endif
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900">{{ $log->employee->name ?? '—' }}</div>
                                    <div class="text-[10px] text-gray-400">{{ $log->employee?->department?->name ?? 'General' }}</div>
                                </div>
                            </div>
                        </td>

                        {{-- Job & Task --}}
                        <td class="p-3">
                            <div class="font-semibold text-gray-800 whitespace-normal break-words max-w-xs">
                                {{ $log->assignment->name ?? 'No Job' }}
                            </div>
                            <div class="text-[11px] text-gray-500 whitespace-normal break-words max-w-xs">
                                {{ $log->task->name ?? 'No Task' }}
                            </div>
                        </td>

                        {{-- Date & 24H In/Out --}}
                        <td class="p-3 font-mono text-[11px]">
                            <div class="font-bold text-gray-900">{{ $inFormatted }}</div>
                            <div class="text-gray-500 text-[10px]">Out: {{ $outFormatted }}</div>
                        </td>

                        {{-- Hours --}}
                        <td class="p-3 text-right font-bold text-gray-900">
                            {{ number_format($mins / 60, 2) }} h
                        </td>

                        {{-- Flags --}}
                        <td class="p-3">
                            <div class="flex flex-wrap items-center gap-1">
                                @if($log->is_manual || $log->isManual())
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-800" title="Manual Entry">
                                        <i class="fa-solid fa-pen-to-square text-[9px]"></i> Manual
                                    </span>
                                @endif
                                @if($log->clock_in_latitude && $log->clock_in_longitude && !$log->location_flagged)
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800" title="GPS Verified">
                                        <i class="fa-solid fa-location-dot text-[9px]"></i> Verified
                                    </span>
                                @endif
                                @if($log->is_unusual)
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-rose-100 text-rose-800 cursor-help" title="{{ $log->unusual_reason }}">
                                        <i class="fa-solid fa-triangle-exclamation text-[9px]"></i> Unusual
                                    </span>
                                @endif
                                @if($log->location_flagged)
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-orange-100 text-orange-800 cursor-help" title="{{ $log->location_flag_reason }}">
                                        <i class="fa-solid fa-map-pin text-[9px]"></i> Outside Geofence
                                    </span>
                                @endif
                            </div>
                        </td>

                        {{-- Status & Lock Icon --}}
                        <td class="p-3 text-center">
                            @if($isLocked)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-gray-100 text-gray-700 border border-gray-300" title="Entry is locked and cannot be edited.">
                                    <i class="fa-solid fa-lock text-gray-500"></i>
                                    <span>Locked ({{ ucfirst($log->status ?: ($log->approved ? 'Approved' : 'Locked')) }})</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                    <i class="fa-solid fa-clock text-amber-500"></i>
                                    <span>Pending Review</span>
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-gray-400">No timesheet records found for the selected criteria.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-3">
            {{ $paginatedLogs->links() }}
        </div>
    </div>
</div>
