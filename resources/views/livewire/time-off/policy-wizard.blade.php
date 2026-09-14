<div>
    @if($toastMessage)
        <div class="mb-4 px-4 py-2 rounded text-sm
            {{ $toastType === 'error' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
            {{ $toastMessage }}
        </div>
    @endif

    {{-- TITLE --}}
    <h1 class="text-4xl font-light text-gray-800 mb-6">{{ $titleLabel }}</h1>

    {{-- WIZARD STEPS --}}
    <div class="w-full mb-8">
        <div class="flex items-stretch text-sm">

            @php
                $steps = [
                    ['key' => 'PolicyDetails',      'label' => 'Policy Details'],
                    ['key' => 'AdditionalSettings', 'label' => 'Additional Settings'],
                    ['key' => 'EmployeeSelection',  'label' => 'Employee Selection'],
                    ['key' => 'EmployeeDetails',    'label' => 'Employee Details'],
                ];
                $order = array_column($steps, 'key');
                $currentIdx = array_search($step, $order, true);
            @endphp

            @foreach($steps as $i => $s)
                @php
                    $isDone    = $i < $currentIdx;
                    $isCurrent = $i === $currentIdx;
                    $isFuture  = $i > $currentIdx;
                @endphp

                {{-- Main step block --}}
                <div class="relative flex items-center justify-center px-6 py-3
                    {{ $isCurrent ? 'bg-cs-blue text-white font-semibold' : '' }}
                    {{ $isDone    ? 'bg-[#e8eef5] text-cs-blue' : '' }}
                    {{ $isFuture  ? 'bg-[#f2f4f7] text-gray-500' : '' }}"
                     style="min-width:160px;">

                    <span>{{ $s['label'] }}</span>

                    {{-- right arrow --}}
                    <div class="absolute right-[-16px] top-0 h-full w-8 z-10 pointer-events-none">
                        <svg viewBox="0 0 32 100" preserveAspectRatio="none" class="h-full w-full">
                            <polygon points="0,0 16,50 0,100"
                                     fill="{{ $isDone ? '#e8eef5' : ($isCurrent ? '#005a9c' : '#f2f4f7') }}"></polygon>
                            <polygon points="16,0 32,50 16,100"
                                     fill="{{ $isFuture ? '#f2f4f7' : ($isDone ? '#f2f4f7' : '#f2f4f7') }}"></polygon>
                        </svg>
                    </div>
                </div>
            @endforeach

            {{-- Tail filler --}}
            <div class="flex-1 bg-[#f2f4f7]"></div>
        </div>
    </div>

    {{-- =================== STEP 1: POLICY DETAILS =================== --}}
    @if($step === 'PolicyDetails')
        <div class="max-w-3xl space-y-6">

            <div>
                <label class="block text-sm text-gray-700 mb-1">
                    Policy Name
                    <span class="text-gray-400 cursor-help" title="The name of your time off policy. Visible to all enrolled employees.">&#9432;</span>
                </label>
                <input type="text" wire:model="policyName"
                       placeholder="Policy Name" maxlength="100"
                       class="w-full max-w-md border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                @error('policyName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm text-gray-700 mb-1">
                    Policy Type
                    <span class="text-gray-400 cursor-help" title="If you have a combined PTO and vacation policy, choose Paid Time Off.">&#9432;</span>
                </label>
                <select wire:model.live="policyType"
                        class="w-full max-w-md border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                    <option value="0">Paid Time Off (PTO)</option>
                    <option value="1">Sick Time</option>
                    <option value="2">Unpaid Time</option>
                </select>
            </div>

            @if((int)$policyType !== 2)
                <div>
                    <label class="block text-sm text-gray-700 mb-1">
                        Time Off Limit
                        <span class="text-gray-400 cursor-help" title="Limit the amount of time an employee can take off.">&#9432;</span>
                    </label>
                    <p class="text-sm text-gray-700 mb-2">Is there a limit to how much time employees can take off?</p>

                    <label class="flex items-center gap-2 text-sm mb-1">
                        <input type="radio" wire:model.live="policyIsLimit" value="1">
                        Yes, time is accrued and limited
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="radio" wire:model.live="policyIsLimit" value="0">
                        No, time off is not limited
                    </label>
                    @error('policyIsLimit') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                @if($policyIsLimit == 1)
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">
                            Earned
                            <span class="text-gray-400 cursor-help" title="Employees can earn time off for each pay period, or all at once every year.">&#9432;</span>
                        </label>
                        <select wire:model.live="policyAccrualMethod"
                                class="w-full max-w-md border border-gray-300 rounded px-3 py-2 text-sm">
                            <option value="0">In pay periods throughout the year</option>
                            <option value="1">In one block on the first of the year</option>
                            <option value="2">In one block on the employee anniversary</option>
                            <option value="3">Based on hours worked per pay period</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm text-gray-700 mb-1">
                            {{ (int)$policyAccrualMethod === 3 ? 'Fraction Earned Per Hour Worked' : ((int)$policyAccrualMethod === 0 ? 'Total Per Period' : 'Total Per Year') }}
                        </label>
                        <div class="flex max-w-xs">
                            <input type="number" step="0.01" wire:model="policyAccrualHours"
                                   class="flex-1 border border-gray-300 rounded-l px-3 py-2 text-sm">
                            <span class="inline-flex items-center px-3 bg-gray-50 border border-l-0 border-gray-300 rounded-r text-sm text-gray-600">Hours</span>
                        </div>
                        @error('policyAccrualHours') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                @endif
            @endif

            <div class="flex items-center gap-3 pt-2">
                <button wire:click="cancel"
                        class="bg-cs-blue hover:bg-blue-800 text-white px-5 py-2 rounded text-sm font-semibold flex items-center gap-2">
                    &#8592; Go Back
                </button>

                @if((int)$policyType === 2)
                    <button wire:click="goToAdditionalSettings"
                            class="bg-cs-blue hover:bg-blue-800 text-white px-5 py-2 rounded text-sm font-semibold flex items-center gap-2">
                        Save and go to Employee Selection &#8594;
                    </button>
                @else
                    <button wire:click="goToAdditionalSettings"
                            class="bg-cs-blue hover:bg-blue-800 text-white px-5 py-2 rounded text-sm font-semibold flex items-center gap-2">
                        Additional Settings &#8594;
                    </button>
                @endif
            </div>
        </div>
    @endif

    {{-- =================== STEP 2: ADDITIONAL SETTINGS =================== --}}
    @if($step === 'AdditionalSettings')
        <div class="max-w-3xl space-y-8">

            <div>
                <label class="block text-sm text-gray-700 mb-1">
                    Waiting Period
                </label>
                <p class="text-sm text-gray-700 mb-2">
                    Do your employees go through a waiting period before they may use time off?
                    <span class="text-gray-400 cursor-help" title="If Yes, employees may start using accrued time after the specified waiting period.">&#9432;</span>
                </p>

                <label class="flex items-center gap-2 text-sm mb-1">
                    <input type="radio" wire:model.live="policyIsWaiting" value="1">
                    Yes
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="radio" wire:model.live="policyIsWaiting" value="0">
                    No
                </label>
                @error('policyIsWaiting') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            @if($policyIsWaiting == 1)
                <div>
                    <label class="block text-sm text-gray-700 mb-1">
                        Waiting Period Length
                        <span class="text-gray-400 cursor-help" title="Calendar days before the employee can use time off.">&#9432;</span>
                    </label>
                    <div class="flex max-w-xs">
                        <input type="number" wire:model="policyWaitingDays"
                               class="flex-1 border border-gray-300 rounded-l px-3 py-2 text-sm">
                        <span class="inline-flex items-center px-3 bg-gray-50 border border-l-0 border-gray-300 rounded-r text-sm text-gray-600">Days</span>
                    </div>
                    @error('policyWaitingDays') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            @endif

            @if($policyIsLimit == 1)
                <div>
                    <label class="block text-sm text-gray-700 mb-1">
                        Limited Carryover Hours
                        <span class="text-gray-400 cursor-help" title="Maximum balance of hours carried over at year end.">&#9432;</span>
                    </label>
                    <p class="text-sm text-gray-700 mb-2">Is there a limit to the number of hours your employees can carry over?</p>

                    <label class="flex items-center gap-2 text-sm mb-1">
                        <input type="radio" wire:model.live="policyIsCarryover" value="1">
                        Yes, there's a limit
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="radio" wire:model.live="policyIsCarryover" value="0">
                        No, all hours can be carried over
                    </label>
                    @error('policyIsCarryover') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                @if($policyIsCarryover == 1)
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">
                            Carryover Limit
                            <span class="text-gray-400 cursor-help" title="Maximum balance of hours carried over.">&#9432;</span>
                        </label>
                        <div class="flex max-w-xs">
                            <input type="number" wire:model="policyMaxCarryover"
                                   class="flex-1 border border-gray-300 rounded-l px-3 py-2 text-sm">
                            <span class="inline-flex items-center px-3 bg-gray-50 border border-l-0 border-gray-300 rounded-r text-sm text-gray-600">Hours</span>
                        </div>
                        @error('policyMaxCarryover') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div>
                    <label class="block text-sm text-gray-700 mb-1">
                        Maximum Balance (optional)
                        <span class="text-gray-400 cursor-help" title="Maximum hours an employee can accrue before accrual is paused.">&#9432;</span>
                    </label>
                    <div class="flex max-w-xs">
                        <input type="number" wire:model="policyMaxBalance" placeholder="Maximum Balance"
                               class="flex-1 border border-gray-300 rounded-l px-3 py-2 text-sm">
                        <span class="inline-flex items-center px-3 bg-gray-50 border border-l-0 border-gray-300 rounded-r text-sm text-gray-600">Hours</span>
                    </div>
                </div>
            @endif

            <div class="flex items-center gap-3 pt-2">
                <button wire:click="goBackToPolicyDetails"
                        class="bg-cs-blue hover:bg-blue-800 text-white px-5 py-2 rounded text-sm font-semibold flex items-center gap-2">
                    &#8592; Policy Details
                </button>

                <button wire:click="goToEmployeeSelection"
                        class="bg-cs-blue hover:bg-blue-800 text-white px-5 py-2 rounded text-sm font-semibold flex items-center gap-2">
                    Save and go to Employee Selection &#8594;
                </button>
            </div>
        </div>
    @endif

    {{-- =================== STEP 3: EMPLOYEE SELECTION =================== --}}
    @if($step === 'EmployeeSelection')
        <div class="max-w-3xl space-y-4">

            <p class="text-sm font-semibold text-cs-blue">Select employees to add to this policy.</p>

            <div class="space-y-2">
                <label class="flex items-center gap-2 text-sm">
                    <input type="radio" wire:model.live="allEmployees" value="2">
                    Choose All Employees
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="radio" wire:model.live="allEmployees" value="1">
                    Choose All Unassigned Employees
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="radio" wire:model.live="allEmployees" value="0">
                    Choose Individual Employee(s)
                </label>
            </div>

            @if($allEmployees === '0')
                <div>
                    <label class="block text-sm text-gray-700 mb-1">Employees</label>
                    <select wire:model="selectedEmployeeIds" multiple
                            class="w-full max-w-md border border-gray-300 rounded px-3 py-2 text-sm" size="8">
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}">{{ $e->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Hold Ctrl / Cmd to select multiple.</p>
                </div>
            @endif

            <div class="flex items-center gap-3 pt-2">
                <button wire:click="goBackToPolicyDetails"
                        class="bg-cs-blue hover:bg-blue-800 text-white px-5 py-2 rounded text-sm font-semibold flex items-center gap-2">
                    &#8592; Back to View Policy
                </button>

                <button wire:click="goToEmployeeDetails"
                        class="bg-cs-blue hover:bg-blue-800 text-white px-5 py-2 rounded text-sm font-semibold flex items-center gap-2">
                    Employee Details &#8594;
                </button>
            </div>
        </div>
    @endif

    {{-- =================== STEP 4: EMPLOYEE DETAILS =================== --}}
    @if($step === 'EmployeeDetails')
        <div class="max-w-3xl space-y-4">

            @if(count($employeeDetails) === 0)
                <p class="text-sm text-cs-blue font-semibold">
                    There are currently no employees assigned to this policy. You may save the policy and add employees later, or return to Employee Selection to add employees now.
                </p>
            @else
                <p class="text-sm text-cs-blue font-semibold">
                    Enter the hire/start date for each employee on this policy.
                    @if($policyIsLimit == 1)
                        If the employee has an existing balance, enter the number of hours.
                    @endif
                </p>

                <div class="border border-gray-200 rounded overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="p-3 text-left text-gray-600 font-semibold w-1/2">Employee</th>
                            <th class="p-3 text-left text-gray-600 font-semibold">Hire Date</th>
                            @if($policyIsLimit == 1)
                                <th class="p-3 text-left text-gray-600 font-semibold">Balance</th>
                            @endif
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($employeeDetails as $empId => $details)
                            @php
                                $emp = $employees->firstWhere('id', (int) $empId);
                            @endphp
                            @if($emp)
                                <tr class="border-b border-gray-100">
                                    <td class="p-3 text-gray-800">{{ $emp->name }}</td>
                                    <td class="p-3">
                                        <input type="date"
                                               wire:model="employeeDetails.{{ $empId }}.hire_date"
                                               class="border border-gray-300 rounded px-2 py-1 text-sm w-full">
                                    </td>
                                    @if($policyIsLimit == 1)
                                        <td class="p-3">
                                            <input type="number" step="0.01"
                                                   wire:model="employeeDetails.{{ $empId }}.hours_remaining"
                                                   class="border border-gray-300 rounded px-2 py-1 text-sm w-full">
                                        </td>
                                    @endif
                                </tr>
                            @endif
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="flex items-center gap-3 pt-2">
                <button wire:click="goBackToEmployeeSelection"
                        class="bg-cs-blue hover:bg-blue-800 text-white px-5 py-2 rounded text-sm font-semibold flex items-center gap-2">
                    &#8592; Employee Selection
                </button>

                <button wire:click="saveAndFinish"
                        class="bg-cs-blue hover:bg-blue-800 text-white px-5 py-2 rounded text-sm font-semibold flex items-center gap-2">
                    Save and Return to Policy Details &#8594;
                </button>
            </div>
        </div>
    @endif
</div>
