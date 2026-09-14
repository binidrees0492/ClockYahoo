<div class="border border-gray-200 rounded bg-white shadow-sm">

    {{-- TABS --}}
    <div class="flex border-b border-gray-200">
        <button wire:click="setTab('timesheet')"
                class="px-6 py-3 text-sm font-semibold {{ $tab === 'timesheet' ? 'border-b-2 border-cs-blue text-cs-blue bg-white' : 'text-gray-500 hover:text-gray-700' }}">
            My Timesheet
        </button>
        <button wire:click="setTab('schedule')"
                class="px-6 py-3 text-sm font-semibold {{ $tab === 'schedule' ? 'border-b-2 border-cs-blue text-cs-blue bg-white' : 'text-gray-500 hover:text-gray-700' }}">
            My Schedule
        </button>
    </div>

    {{-- PANEL --}}
    <div class="p-4">
        @if($tab === 'timesheet')
            <livewire:timesheets :key="'ts-' . now()->timestamp" />
        @else
            <livewire:schedule-calendar :key="'sc-' . now()->timestamp" />
        @endif
    </div>
</div>
