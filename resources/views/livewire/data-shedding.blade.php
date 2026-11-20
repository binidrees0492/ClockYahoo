<div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">

    {{-- Header Banner --}}
    <div class="bg-gradient-to-r from-rose-900 via-rose-800 to-red-900 rounded-xl shadow-lg p-6 text-white border border-rose-700">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black uppercase tracking-wider bg-red-500/30 text-red-200 border border-red-400/40">
                        🛡️ Super Admin Only
                    </span>
                    <span class="text-xs text-rose-200 font-semibold">Production Readiness Tool</span>
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2.5">
                    <i class="fa-solid fa-broom-ball text-rose-300"></i>
                    Data Shedding &amp; Production Prep Utility
                </h1>
                <p class="text-xs text-rose-100/90 mt-1.5 max-w-2xl leading-relaxed">
                    Select exactly which mock, demo, or testing data categories and their associated child records you want to delete before moving into production.
                    <strong class="text-white underline">Your Super Admin account and essential system configs are strictly preserved and safeguarded.</strong>
                </p>
            </div>

            <div class="flex items-center gap-2 self-start md:self-auto">
                <a href="{{ route('admin.company-settings') }}" class="px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white rounded text-xs font-semibold transition border border-white/20">
                    &larr; Back to Settings
                </a>
            </div>
        </div>
    </div>

    {{-- Flash Message / Last Results --}}
    @if(session()->has('message'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition
             class="p-4 rounded-lg bg-emerald-50 border border-emerald-300 text-emerald-900 text-sm font-semibold flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-emerald-600 text-lg"></i>
                <span>{{ session('message') }}</span>
            </div>
            <button type="button" @click="show = false" class="text-emerald-700 hover:text-emerald-900 font-bold ml-3 text-base leading-none">&times;</button>
        </div>
    @endif

    @if($lastResults)
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm space-y-3">
            <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                <i class="fa-solid fa-receipt text-emerald-600"></i> Last Purge Summary
            </h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                @foreach($lastResults as $cat => $cnt)
                    <div class="p-3 rounded-lg bg-gray-50 border border-gray-200 text-center">
                        <div class="text-lg font-black text-rose-600">{{ number_format($cnt) }}</div>
                        <div class="text-[11px] font-bold text-gray-600 uppercase tracking-wider mt-0.5">{{ str_replace('_', ' ', $cat) }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Selection Controls --}}
    <div class="flex flex-wrap items-center justify-between gap-3 bg-white border border-gray-200 rounded-lg p-3 shadow-2xs">
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-gray-700">
                Selected: <span class="text-rose-600 font-extrabold">{{ count($selectedEntities) }}</span> of {{ count($entities) }} categories
            </span>
            <button type="button" wire:click="selectAll" class="text-xs text-blue-600 hover:text-blue-800 font-semibold underline px-1">
                Select All
            </button>
            <span class="text-gray-300">|</span>
            <button type="button" wire:click="deselectAll" class="text-xs text-gray-600 hover:text-gray-800 font-semibold underline px-1">
                Deselect All
            </button>
        </div>

        <button type="button"
                wire:click="promptConfirmation"
                @disabled(empty($selectedEntities))
                class="px-4 py-2 rounded-lg text-xs font-bold shadow transition flex items-center gap-2
                       {{ empty($selectedEntities) ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-rose-600 hover:bg-rose-700 text-white cursor-pointer hover:shadow-md' }}">
            <i class="fa-solid fa-trash-can"></i>
            Purge Selected Data for Production
        </button>
    </div>

    {{-- Category Cards Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($entities as $key => $item)
            @php
                $isSelected = in_array($key, $selectedEntities, true);
            @endphp
            <div wire:click="toggleEntity('{{ $key }}')"
                 class="cursor-pointer border rounded-xl p-4 transition relative flex flex-col justify-between
                        {{ $isSelected ? 'bg-rose-50/70 border-rose-400 shadow-sm ring-1 ring-rose-400' : 'bg-white border-gray-200 hover:border-gray-300 hover:shadow-2xs' }}">

                <div>
                    {{-- Top Row: Checkbox, Icon, Title, Count --}}
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <div class="flex items-center gap-2.5">
                            <input type="checkbox"
                                   value="{{ $key }}"
                                   wire:model.live="selectedEntities"
                                   @click.stop
                                   class="rounded border-gray-300 text-rose-600 focus:ring-rose-500 w-4 h-4 cursor-pointer">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm {{ $isSelected ? 'bg-rose-600 text-white' : 'bg-gray-100 text-gray-700' }}">
                                <i class="{{ $item['icon'] }}"></i>
                            </div>
                            <h3 class="text-sm font-bold text-gray-900 leading-tight">
                                {{ $item['label'] }}
                            </h3>
                        </div>

                        <span class="px-2 py-0.5 rounded text-[11px] font-black {{ $item['count'] > 0 ? 'bg-gray-100 text-gray-800 border border-gray-300' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                            {{ number_format($item['count']) }} {{ $item['count'] === 1 ? 'record' : 'records' }}
                        </span>
                    </div>

                    {{-- Description --}}
                    <p class="text-xs text-gray-600 mt-1 leading-normal">
                        {{ $item['description'] }}
                    </p>
                </div>

                {{-- Children info --}}
                <div class="mt-3 pt-2.5 border-t border-gray-200/80 text-[11px] text-gray-500">
                    <strong class="text-gray-700 font-semibold">Cascades to:</strong> {{ $item['children'] }}
                </div>
            </div>
        @endforeach
    </div>

    {{-- Confirmation Modal --}}
    @if($showConfirmModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/60 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-300 space-y-4" @click.away="$wire.cancelConfirmation()">
                <div class="flex items-center gap-3 text-rose-600 pb-2 border-b border-gray-200">
                    <div class="w-10 h-10 rounded-full bg-rose-100 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Confirm Production Data Shedding</h2>
                        <p class="text-xs text-gray-500 font-semibold">Irreversible action &bull; Strict Super Admin bypass</p>
                    </div>
                </div>

                <div class="space-y-2 text-xs text-gray-700">
                    <p class="font-medium leading-relaxed">
                        You have selected <strong class="text-rose-600 font-bold">{{ count($selectedEntities) }}</strong> data categories to purge from the system:
                    </p>
                    <ul class="list-disc pl-5 space-y-0.5 text-gray-600 max-h-36 overflow-y-auto bg-gray-50 p-2.5 rounded border border-gray-200">
                        @foreach($selectedEntities as $selKey)
                            <li>
                                <strong class="text-gray-800">{{ $entities[$selKey]['label'] ?? $selKey }}</strong>
                                <span class="text-gray-400">({{ $entities[$selKey]['count'] ?? 0 }} records + child records)</span>
                            </li>
                        @endforeach
                    </ul>
                    <p class="text-[11px] text-gray-500 bg-amber-50 border border-amber-200 p-2 rounded text-amber-900">
                        <i class="fa-solid fa-circle-info text-amber-600"></i>
                        <strong>Protection:</strong> Your current Super Admin user account and system configuration settings will NOT be deleted.
                    </p>
                </div>

                <div class="space-y-1.5 pt-1">
                    <label class="block text-xs font-bold text-gray-800">
                        Type <span class="text-rose-600 font-black">CONFIRM</span> to proceed:
                    </label>
                    <input type="text"
                           wire:model.live="confirmText"
                           placeholder="Type CONFIRM here"
                           class="w-full text-xs font-bold px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-rose-500 border-gray-300">
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-200">
                    <button type="button"
                            wire:click="cancelConfirmation"
                            class="px-4 py-2 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-100 border border-gray-300">
                        Cancel
                    </button>
                    <button type="button"
                            wire:click="executePurge"
                            @disabled(strtoupper(trim($confirmText)) !== 'CONFIRM')
                            class="px-4 py-2 rounded-lg text-xs font-bold transition flex items-center gap-1.5
                                   {{ strtoupper(trim($confirmText)) === 'CONFIRM' ? 'bg-rose-600 hover:bg-rose-700 text-white shadow-sm cursor-pointer' : 'bg-gray-200 text-gray-400 cursor-not-allowed' }}">
                        <i class="fa-solid fa-trash-can"></i>
                        Confirm &amp; Purge Selected Data
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
