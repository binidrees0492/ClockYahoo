<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-light text-gray-800">Employees</h1>
        @if(auth()->user()->isAdmin() || auth()->user()->isHrManager())
            <button wire:click="openAddModal" class="bg-[#005b8f] hover:bg-[#004a75] text-white px-4 py-2 rounded text-sm font-semibold shadow-sm transition">
                Add Employee
            </button>
        @endif
    </div>

    @if (session()->has('message'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3500)" x-transition class="mb-4 p-3 bg-green-100 border border-green-200 text-green-800 text-sm rounded shadow-sm flex items-center justify-between">
            <span>{{ session('message') }}</span>
            <button type="button" @click="show = false" class="text-green-700 hover:text-green-900 font-bold ml-3 leading-none text-base">&times;</button>
        </div>
    @endif
    @if (session()->has('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3500)" x-transition class="mb-4 p-3 bg-red-100 border border-red-200 text-red-800 text-sm rounded shadow-sm flex items-center justify-between">
            <span>{{ session('error') }}</span>
            <button type="button" @click="show = false" class="text-red-700 hover:text-red-900 font-bold ml-3 leading-none text-base">&times;</button>
        </div>
    @endif

    {{-- Filter Bar --}}
    <div class="bg-white border border-gray-200 rounded p-4 mb-4 shadow-sm grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">SEARCH</label>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by Name, ID, or Email..." class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005b8f]">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">DEPARTMENT</label>
            <select wire:model.live="department_filter" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005b8f]">
                <option value="">All Departments</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">STATUS</label>
            <select wire:model.live="status_filter" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005b8f]">
                <option value="">All Statuses</option>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
            </select>
        </div>
        <div class="flex items-end">
            <button wire:click="$set('search', ''); $set('department_filter', ''); $set('status_filter', ''); $set('includeDeleted', false);" class="text-xs text-[#005b8f] hover:underline mb-2 font-medium">
                Clear all filters
            </button>
        </div>
        @if(auth()->user()?->isSuperAdmin())
            <div class="md:col-span-4 flex items-center justify-between pt-2 border-t border-gray-100">
                <label class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-700 cursor-pointer bg-gray-50 hover:bg-gray-100 px-2.5 py-1.5 rounded border border-gray-300">
                    <input type="checkbox" wire:model.live="includeDeleted" class="rounded border-gray-300 text-rose-600 focus:ring-rose-500">
                    <span class="flex items-center gap-1">
                        <span>🗑️ Include Deleted Records</span>
                        <span class="text-[10px] text-rose-600 font-extrabold uppercase">(Super Admin)</span>
                    </span>
                </label>
                @if($includeDeleted)
                    <span class="text-[11px] text-rose-600 font-bold bg-rose-50 border border-rose-200 px-2 py-0.5 rounded">
                        Showing all records including deleted / inactive accounts
                    </span>
                @endif
            </div>
        @endif
    </div>

    {{-- Employee Table --}}
    <div class="bg-white border border-gray-200 rounded shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead>
            <tr class="bg-gray-50 border-b border-gray-200 text-left text-xs uppercase text-gray-500 font-semibold tracking-wider">
                <th class="p-3">Employee</th>
                <th class="p-3">Email</th>
                <th class="p-3">ID</th>
                <th class="p-3">Role</th>
                <th class="p-3">Department</th>
                <th class="p-3">Status</th>
                <th class="p-3 text-right">Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($employees as $emp)
                <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                    <td class="p-3 flex items-center gap-3">
                        @if($emp->avatar_url)
                            <img src="{{ $emp->avatar_url }}" alt="{{ $emp->name }}" class="w-8 h-8 rounded-full object-cover ring-1 ring-sky-400/50 shadow-2xs" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex';">
                            <div class="w-8 h-8 rounded-full text-white text-xs font-bold items-center justify-center shadow-inner hidden" style="background-color: {{ $emp->color ?? '#ffa526' }}">
                                {{ $emp->initials }}
                            </div>
                        @else
                            <div class="w-8 h-8 rounded-full text-white text-xs font-bold flex items-center justify-center shadow-inner" style="background-color: {{ $emp->color ?? '#ffa526' }}">
                                {{ $emp->initials }}
                            </div>
                        @endif
                        <div>
                            <span class="font-medium text-gray-900 block">{{ $emp->name }}</span>
                            @if($emp->manager)
                                <span class="text-[11px] text-gray-400">Reports to: {{ $emp->manager->name }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="p-3 text-gray-600">{{ $emp->email }}</td>
                    <td class="p-3 text-gray-600">{{ $emp->display_id ?? '—' }}</td>
                    <td class="p-3">
                            <span class="px-2 py-0.5 rounded text-xs font-semibold
                                {{ $emp->role === 'Administrator' ? 'bg-purple-100 text-purple-800' : ($emp->role === 'HR Manager' ? 'bg-indigo-100 text-indigo-800' : ($emp->role === 'Manager' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800')) }}">
                                {{ $emp->role ?? 'Employee' }}
                            </span>
                    </td>
                    <td class="p-3 text-gray-600">{{ $emp->department->name ?? '—' }}</td>
                    <td class="p-3">
                        @if($emp->status === 'Active')
                            <span class="px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800">
                                Active
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded text-xs font-bold bg-rose-100 text-rose-800 inline-flex items-center gap-1">
                                <span>🗑️</span> Deleted / Inactive
                            </span>
                        @endif
                    </td>
                    <td class="p-3 text-right">
                        @if($emp->status === 'Active')
                            <button wire:click="openEditModal({{ $emp->id }})" class="text-[#005b8f] hover:underline text-xs font-semibold mr-3">Edit</button>
                            <button wire:click="delete({{ $emp->id }})" wire:confirm="Are you sure you want to remove this employee?" class="text-red-500 hover:underline text-xs">Delete</button>
                        @elseif(auth()->user()?->isSuperAdmin())
                            <button wire:click="restoreEmployee({{ $emp->id }})" class="text-emerald-600 hover:text-emerald-800 hover:underline text-xs font-bold mr-3 inline-flex items-center gap-1">
                                <i class="fa-solid fa-rotate-left"></i> Restore
                            </button>
                            <button wire:click="permanentlyDeleteEmployee({{ $emp->id }})" wire:confirm="PERMANENT DELETE: Are you sure you want to permanently delete employee '{{ $emp->name }}' and ALL linked child records (timesheets, schedules, leaves)? This action cannot be undone." class="text-rose-600 hover:text-rose-800 hover:underline text-xs font-bold inline-flex items-center gap-1">
                                <i class="fa-solid fa-trash-can"></i> Delete Permanently
                            </button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="p-8 text-center text-gray-400">No employees found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-gray-100">
            {{ $employees->links() }}
        </div>
    </div>

    {{-- Add/Edit Tabbed Modal --}}
    @if($isModalOpen)
        <div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg shadow-2xl w-full max-w-4xl h-[85vh] flex flex-col overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-white shrink-0">
                    <h2 class="text-xl font-normal text-gray-800">{{ $modalMode === 'add' ? 'Add Employee' : 'Edit Employee' }}</h2>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
                </div>

                <div class="flex-1 flex overflow-hidden">
                    <div class="w-60 border-r border-gray-200 bg-white py-4 overflow-y-auto shrink-0">
                        <ul class="space-y-1">
                            <li>
                                <button type="button" wire:click="setTab('details')" class="w-full text-left px-5 py-3 text-sm transition {{ $activeTab === 'details' ? 'bg-[#eef5fc] text-[#005b8f] border-l-4 border-[#005b8f] font-semibold' : 'border-l-4 border-transparent text-gray-700 hover:bg-gray-50' }}">
                                    Employee Details
                                </button>
                            </li>
                            <li>
                                <button type="button" wire:click="setTab('timeoff')" class="w-full text-left px-5 py-3 text-sm transition {{ $activeTab === 'timeoff' ? 'bg-[#eef5fc] text-[#005b8f] border-l-4 border-[#005b8f] font-semibold' : 'border-l-4 border-transparent text-gray-700 hover:bg-gray-50' }}">
                                    Time Off
                                </button>
                            </li>
                            <li>
                                <button type="button" wire:click="setTab('gps')" class="w-full text-left px-5 py-3 text-sm transition {{ $activeTab === 'gps' ? 'bg-[#eef5fc] text-[#005b8f] border-l-4 border-[#005b8f] font-semibold' : 'border-l-4 border-transparent text-gray-700 hover:bg-gray-50' }}">
                                    GPS & Time Clock
                                </button>
                            </li>
                            <li>
                                <button type="button" wire:click="setTab('permissions')" class="w-full text-left px-5 py-3 text-sm transition {{ $activeTab === 'permissions' ? 'bg-[#eef5fc] text-[#005b8f] border-l-4 border-[#005b8f] font-semibold' : 'border-l-4 border-transparent text-gray-700 hover:bg-gray-50' }}">
                                    Permissions
                                </button>
                            </li>
                            <li>
                                <button type="button" wire:click="setTab('manager')" class="w-full text-left px-5 py-3 text-sm transition {{ $activeTab === 'manager' ? 'bg-[#eef5fc] text-[#005b8f] border-l-4 border-[#005b8f] font-semibold' : 'border-l-4 border-transparent text-gray-700 hover:bg-gray-50' }}">
                                    Manager Settings
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="flex-1 p-6 md:p-8 overflow-y-auto bg-white">
                        <form wire:submit="save" id="employeeForm">
                        @if($activeTab === 'details')
                            <div>
                                <h3 class="text-lg font-medium text-gray-800 mb-4">Employee Details</h3>

                                {{-- Display Picture (DP) Upload Section --}}
                                <div class="mb-5 p-3.5 bg-gray-50 border border-gray-200 rounded-lg flex flex-wrap items-center gap-4">
                                    <div class="relative w-14 h-14 rounded-full overflow-hidden bg-gray-200 border-2 border-cs-blue shadow-inner flex items-center justify-center shrink-0">
                                        @if($avatarFile)
                                            <img src="{{ $avatarFile->temporaryUrl() }}" alt="Preview" class="w-full h-full object-cover">
                                        @elseif($currentAvatarUrl)
                                            <img src="{{ $currentAvatarUrl }}" alt="DP" class="w-full h-full object-cover" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='inline';">
                                            <span class="text-gray-400 font-bold text-xl hidden">👤</span>
                                        @else
                                            <span class="text-gray-400 font-bold text-xl">👤</span>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-[200px]">
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1">Display Picture (DP)</label>
                                        <div class="flex items-center gap-2">
                                            <input type="file" wire:model="avatarFile" accept="image/*" class="text-xs text-gray-600 file:mr-2 file:py-1 file:px-2.5 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-cs-blue file:text-white hover:file:bg-blue-800 cursor-pointer">
                                            @if($avatarFile || $currentAvatarUrl)
                                                <button type="button" wire:click="removeAvatar" class="text-xs text-red-600 hover:underline font-semibold">Remove</button>
                                            @endif
                                        </div>
                                        <p class="text-[10px] text-gray-400 mt-1">PNG, JPG or WebP (max 5MB).</p>
                                        <div wire:loading wire:target="avatarFile" class="text-xs text-blue-600 font-medium mt-0.5">Uploading preview...</div>
                                        @error('avatarFile') <span class="text-red-500 text-xs block mt-0.5">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">First Name *</label>
                                        <input type="text" wire:model="first_name" placeholder="First Name" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005b8f]">
                                        @error('first_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">Last Name *</label>
                                        <input type="text" wire:model="last_name" placeholder="Last Name" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005b8f]">
                                        @error('last_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">Employee ID</label>
                                        <input type="text" wire:model="display_id" placeholder="Employee ID" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005b8f]">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">Email *</label>
                                        <input type="email" wire:model="email" placeholder="Email Address" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005b8f]">
                                        @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">Mobile Phone</label>
                                        <input type="tel" wire:model="phone" placeholder="Phone Number" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005b8f]">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">Password {{ $modalMode === 'edit' ? '(Leave blank to retain)' : '*' }}</label>
                                        <input type="password" wire:model="password"  autocomplete="false" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005b8f]">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">Badge Color</label>
                                        <input type="color" wire:model="color" class="h-9 w-full border border-gray-300 rounded p-1 cursor-pointer">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 border-t pt-4 mb-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Role *</label>
                                        <select wire:model.live="role" class="w-full border border-gray-300 rounded p-2 text-sm font-medium text-[#005b8f] outline-none">
                                            @if(isset($availableRoles) && count($availableRoles) > 0)
                                                @foreach($availableRoles as $r)
                                                    <option value="{{ $r->name }}">{{ $r->name }}</option>
                                                @endforeach
                                            @else
                                                <option value="Employee">Employee</option>
                                                <option value="Manager">Manager</option>
                                                <option value="HR Manager">HR Manager</option>
                                                <option value="Administrator">Administrator</option>
                                            @endif
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">Status *</label>
                                        <select wire:model="status" class="w-full border border-gray-300 rounded p-2 text-sm outline-none">
                                            <option value="Active">Active</option>
                                            <option value="Inactive">Inactive</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">Base Pay Rate ($/hr)</label>
                                        <input type="number" step="0.01" wire:model="base_pay" placeholder="0.00" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005b8f]">
                                    </div>
                                </div>

                                <div class="grid grid-cols-3 gap-4 border-t pt-4">
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">Department</label>
                                        <select wire:model="department_id" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005b8f]">
                                            <option value="">None</option>
                                            @foreach($departments as $dept)
                                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">Location</label>
                                        <select wire:model="location_id" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005b8f]">
                                            <option value="">None</option>
                                            @foreach($locations as $loc)
                                                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">Reports To (Manager)</label>
                                        <select wire:model="manager_id" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005b8f]">
                                            <option value="">None (Direct to Admin / HR)</option>
                                            @foreach($eligibleManagers as $mgr)
                                                <option value="{{ $mgr->id }}">{{ $mgr->name }} ({{ $mgr->role }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($activeTab === 'timeoff')
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <h3 class="text-xl font-normal text-gray-900">Time Off Policies</h3>
                                    <span class="text-xs text-gray-400 font-medium">{{ count($employeePolicies) }} policies available</span>
                                </div>
                                <p class="text-xs text-gray-500 mb-4">
                                    Manage policy enrollments, accrual start dates, and available balances for this employee.
                                </p>

                                <div class="border border-gray-200 rounded overflow-hidden mb-6 shadow-2xs">
                                    <table class="w-full text-left text-sm">
                                        <thead class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-600 font-semibold">
                                        <tr>
                                            <th class="p-3 w-10 text-center">Enroll</th>
                                            <th class="p-3">Policy Name</th>
                                            <th class="p-3 w-28 text-center">Status</th>
                                            <th class="p-3 w-40 text-right">Hire Date</th>
                                            <th class="p-3 w-32 text-right">Balance (hrs)</th>
                                            <th class="p-3 w-20 text-center">Action</th>
                                        </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                        @forelse($employeePolicies as $index => $item)
                                            <tr class="hover:bg-gray-50/80 transition {{ !empty($item['enrolled']) ? 'bg-blue-50/20' : 'opacity-70 bg-gray-50/30' }}">
                                                <td class="p-3 text-center">
                                                    <input type="checkbox" wire:model.live="employeePolicies.{{ $index }}.enrolled" class="rounded text-[#005b8f] focus:ring-[#005b8f] cursor-pointer">
                                                </td>
                                                <td class="p-3 font-medium text-gray-800">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-sm font-semibold text-gray-900">{{ $item['name'] }}</span>
                                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ ($item['type_label'] ?? '') === 'Sick' ? 'bg-amber-100 text-amber-800' : (($item['type_label'] ?? '') === 'Unpaid' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800') }}">
                                                            {{ $item['type_label'] ?? 'PTO' }}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="p-3 text-center">
                                                    @if(!empty($item['enrolled']))
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                                            ✓ Enrolled
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-500">
                                                            Not Enrolled
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="p-3 text-right">
                                                    <input type="date" wire:model="employeePolicies.{{ $index }}.hire_date" class="border border-gray-300 rounded p-1.5 text-xs text-right outline-none focus:border-[#005b8f] {{ empty($item['enrolled']) ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white' }}" {{ empty($item['enrolled']) ? 'disabled' : '' }}>
                                                </td>
                                                <td class="p-3 text-right">
                                                    <input type="number" step="0.5" wire:model="employeePolicies.{{ $index }}.hours_remaining" class="border border-gray-300 rounded p-1.5 text-xs text-right w-24 outline-none focus:border-[#005b8f] {{ empty($item['enrolled']) ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white' }}" {{ empty($item['enrolled']) ? 'disabled' : '' }}>
                                                </td>
                                                <td class="p-3 text-center">
                                                    @if(!empty($item['enrolled']))
                                                        <button type="button" wire:click="togglePolicyEnrollment({{ $index }})" class="text-xs text-red-600 hover:text-red-800 font-medium hover:underline">Remove</button>
                                                    @else
                                                        <button type="button" wire:click="togglePolicyEnrollment({{ $index }})" class="text-xs text-[#005b8f] hover:text-blue-800 font-semibold hover:underline">+ Enroll</button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="p-6 text-center text-xs text-gray-400">
                                                    No time off policies configured in the company yet. Go to <a href="{{ route('time-off.policy-manager') }}" class="text-[#005b8f] underline font-semibold">Policy Manager</a> to create one.
                                                </td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                        @if(isset($availablePolicies) && count($availablePolicies) > 0)
                                            <tfoot class="bg-gray-50 border-t border-gray-200">
                                            <tr>
                                                <td colspan="6" class="p-3">
                                                    <div class="max-w-xs flex items-center gap-2">
                                                        <select wire:model.live="selectedPolicyToAdd" class="w-full border border-gray-300 rounded p-1.5 text-xs outline-none focus:border-[#005b8f] bg-white">
                                                            <option value="">+ Add another policy to list...</option>
                                                            @foreach($availablePolicies as $pol)
                                                                <option value="{{ $pol->id }}">{{ $pol->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </td>
                                            </tr>
                                            </tfoot>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        @endif

                        @if($activeTab === 'gps')
                            <div>
                                <h3 class="text-lg font-medium text-gray-800 mb-2">GPS & Time Clock</h3>
                                <p class="text-xs text-gray-500 mb-6">Manage punch restrictions and geolocation tracking.</p>
                                <div class="space-y-3 text-sm">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" wire:model="permissions.allow_web_clock" class="rounded text-[#005b8f] focus:ring-0">
                                        <span class="text-gray-700">Allow Web Browser Time Clocking</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" wire:model="permissions.require_location" class="rounded text-[#005b8f] focus:ring-0">
                                        <span class="text-gray-700">Require GPS Location on Clock In/Out</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" wire:model="permissions.restrict_geofence" class="rounded text-[#005b8f] focus:ring-0">
                                        <span class="text-gray-700">Restrict Punches outside Geofence</span>
                                    </label>
                                </div>
                            </div>
                        @endif

                        @if($activeTab === 'permissions')
                            <div>
                                <h2 class="text-xl font-normal text-gray-900 mb-4">Permissions</h2>
                                <div class="space-y-4 max-w-md">
                                    @foreach(['mobile_timesheets' => 'Mobile Timesheets', 'jobs' => 'Jobs', 'tasks' => 'Tasks', 'customers' => 'Customers', 'quotes' => 'Quotes', 'invoices' => 'Invoices', 'schedules' => 'Schedules', 'reports' => 'Reports'] as $key => $title)
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-600 mb-1">{{ $title }}</label>
                                            <select wire:model="permissions.{{ $key }}" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005b8f]">
                                                <option value="0">No access</option>
                                                <option value="1">Read-Only / View</option>
                                                <option value="2">Write / Add & Edit</option>
                                            </select>
                                        </div>
                                    @endforeach

                                    @if(auth()->user()?->isSuperAdmin())
                                        <div class="border-t border-gray-200 pt-3 mt-3">
                                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                                <input type="checkbox" wire:model="permissions.allow_hard_delete" class="rounded text-rose-600 focus:ring-rose-500">
                                                <span class="text-xs font-bold text-gray-800">Allow Direct Hard Delete</span>
                                            </label>
                                            <p class="text-[11px] text-gray-500 mt-0.5">
                                                Grant permission to permanently delete records along with child records upwards, bypassing inactivation.
                                            </p>
                                        </div>
                                    @endif

                                    <div class="border-t border-gray-200 pt-5 mt-4">
                                        <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider mb-1">Assigned Jobs & Tasks</h3>
                                        <p class="text-xs text-gray-500 mb-3">Employees can only view and clock in to jobs & tasks assigned to them (or scheduled on their shifts). Leave unselected to restrict exclusively to scheduled calendar shifts.</p>

                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Permitted Jobs & Shift Hours</label>
                                                <p class="text-[11px] text-gray-500 mb-2">Define shift hours per employee for each job and whether they have an exception to record beyond shift hours.</p>
                                                <div class="max-h-64 overflow-y-auto border border-gray-200 rounded p-2.5 bg-gray-50 space-y-2 text-xs">
                                                    @forelse($availableJobs as $job)
                                                        @php
                                                            $isJobSelected = in_array($job->id, array_map('intval', (array)$assigned_jobs));
                                                        @endphp
                                                        <div class="border border-gray-200 rounded p-2 bg-white transition">
                                                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                                                <input type="checkbox" wire:model.live="assigned_jobs" value="{{ $job->id }}" class="rounded text-[#005b8f] focus:ring-[#005b8f]">
                                                                <span class="text-gray-900 font-semibold">{{ $job->name }}</span>
                                                            </label>
                                                            @if($isJobSelected)
                                                                <div class="mt-2 pt-2 border-t border-gray-100 grid grid-cols-1 sm:grid-cols-3 gap-2 bg-slate-50 p-2 rounded">
                                                                    <div>
                                                                        <label class="block text-[10px] font-bold text-gray-600 mb-0.5">Shift From</label>
                                                                        <input type="time" wire:model="job_shifts.{{ $job->id }}.shift_start" class="w-full text-xs p-1 border border-gray-300 rounded bg-white">
                                                                    </div>
                                                                    <div>
                                                                        <label class="block text-[10px] font-bold text-gray-600 mb-0.5">Shift To</label>
                                                                        <input type="time" wire:model="job_shifts.{{ $job->id }}.shift_end" class="w-full text-xs p-1 border border-gray-300 rounded bg-white">
                                                                    </div>
                                                                    <div>
                                                                        <label class="block text-[10px] font-bold text-gray-600 mb-0.5">Shift Hours</label>
                                                                        <input type="number" step="0.25" min="0" max="24" wire:model="job_shifts.{{ $job->id }}.shift_hours" placeholder="e.g. 8.0" class="w-full text-xs p-1 border border-gray-300 rounded bg-white">
                                                                    </div>
                                                                    <div class="sm:col-span-3 mt-1">
                                                                        <label class="flex items-center gap-2 cursor-pointer select-none text-gray-700">
                                                                            <input type="checkbox" wire:model="job_shifts.{{ $job->id }}.allow_beyond_shift" class="rounded text-[#005b8f] focus:ring-[#005b8f]">
                                                                            <span class="text-[11px] font-medium">Allow hours beyond shift (over-hours exception)</span>
                                                                        </label>
                                                                        <p class="text-[10px] text-gray-400 mt-0.5">If unchecked, any logged time exceeding the shift duration will be flagged as unusual for HR verification.</p>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @empty
                                                        <span class="text-gray-400 italic">No ongoing jobs available.</span>
                                                    @endforelse
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Permitted Tasks (Subset of Jobs)</label>
                                                <div class="max-h-48 overflow-y-auto border border-gray-200 rounded p-2.5 bg-gray-50 space-y-1.5 text-xs">
                                                    @forelse($availableTasks as $tsk)
                                                        @php
                                                            $jobAssigned = empty($assigned_jobs) || !$tsk->assignment_id || in_array($tsk->assignment_id, array_map('intval', (array)$assigned_jobs));
                                                        @endphp
                                                        @if($jobAssigned)
                                                            <label class="flex items-center justify-between cursor-pointer hover:bg-white p-1 rounded transition select-none">
                                                                <div class="flex items-center gap-2">
                                                                    <input type="checkbox" wire:model="assigned_tasks" value="{{ $tsk->id }}" class="rounded text-[#005b8f] focus:ring-[#005b8f]">
                                                                    <span class="text-gray-800">{{ $tsk->name }}</span>
                                                                </div>
                                                                @if($tsk->assignment)
                                                                    <span class="text-[10px] bg-blue-50 text-[#005b8f] border border-blue-200 font-semibold px-1.5 py-0.5 rounded">
                                                                        {{ $tsk->assignment->name }}
                                                                    </span>
                                                                @else
                                                                    <span class="text-[10px] text-gray-400 italic">General</span>
                                                                @endif
                                                            </label>
                                                        @endif
                                                    @empty
                                                        <span class="text-gray-400 italic">No ongoing tasks available.</span>
                                                    @endforelse
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($activeTab === 'manager')
                            <div>
                                <h2 class="text-xl font-normal text-gray-900 mb-1">Manager Settings</h2>
                                <p class="text-xs text-gray-500 mb-6">If this person is a manager or HR manager, configure team scope and manager permissions.</p>

                                <div class="max-w-md mb-4">
                                    <label class="block text-xs text-gray-600 mb-1">Team</label>
                                    <select wire:model.live="managed_team_type" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005b8f]">
                                        <option value="all">All Employees</option>
                                        <option value="restricted">For Only...</option>
                                    </select>
                                </div>

                                @if($managed_team_type === 'restricted')
                                    <div class="mb-6">
                                        <label class="block text-xs text-gray-600 mb-1">Employees <span class="text-red-500">*</span></label>
                                        <div class="w-full min-h-[42px] p-2 border border-gray-300 rounded flex flex-wrap gap-2 items-center bg-white">
                                            @foreach($managed_entities as $index => $item)
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-100 border border-gray-300 text-gray-800 text-xs rounded">
                                                    <button type="button" wire:click="removeManagedEntity({{ $index }})" class="text-gray-500 hover:text-black font-bold leading-none">&times;</button>
                                                    {{ $item['name'] }}
                                                </span>
                                            @endforeach
                                            <div class="flex-1 min-w-[200px]">
                                                <select wire:model.live="selectedEntityToAdd" class="w-full border-none p-1 text-xs outline-none bg-transparent text-gray-500">
                                                    <option value="">+ Click to add department, location, or employee...</option>
                                                    <optgroup label="Departments">
                                                        @foreach($departments as $dept)
                                                            <option value="dept:{{ $dept->id }}">Department: {{ $dept->name }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                    <optgroup label="Locations">
                                                        @foreach($locations as $loc)
                                                            <option value="loc:{{ $loc->id }}">Location: {{ $loc->name }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                    <optgroup label="Employees">
                                                        @foreach($allEmployees as $user)
                                                            <option value="emp:{{ $user->id }}">{{ $user->name }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div class="border-t border-gray-200 pt-5 mt-4">
                                    <h4 class="text-sm font-bold text-[#00263a] uppercase tracking-wider mb-1">PERMISSIONS</h4>
                                    <p class="text-xs text-gray-500 mb-4">This person can do the following for their team.</p>

                                    <div class="max-w-md space-y-4 mb-5">
                                        <div>
                                            <label class="block text-xs text-gray-600 mb-1">Timesheets</label>
                                            <select wire:model="manager_permissions.timesheets" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005b8f]">
                                                <option value="0">No access</option>
                                                <option value="1">Read-Only / View</option>
                                                <option value="2">Write / Add & Edit</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-600 mb-1">Schedules</label>
                                            <select wire:model="manager_permissions.schedules" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005b8f]">
                                                <option value="0">No access</option>
                                                <option value="1">Read-Only / View</option>
                                                <option value="2">Write / Add & Edit</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="space-y-3 text-sm text-gray-800">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" wire:model="manager_permissions.view_map_location" class="rounded text-[#005b8f] focus:ring-0">
                                            <span>View location on Map</span>
                                        </label>
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" wire:model="manager_permissions.crew_clock" class="rounded text-[#005b8f] focus:ring-0">
                                            <span>Clock their team in and out with CrewClock</span>
                                        </label>
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" wire:model="manager_permissions.view_reports" class="rounded text-[#005b8f] focus:ring-0">
                                            <span>View Reports</span>
                                        </label>
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" wire:model="manager_permissions.approve_timesheets" class="rounded text-[#005b8f] focus:ring-0">
                                            <span>Approve Timesheets</span>
                                        </label>
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" wire:model="manager_permissions.manage_time_off" class="rounded text-[#005b8f] focus:ring-0">
                                            <span>Manage Time Off</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        @endif
                        </form>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 bg-white flex justify-between items-center shrink-0">
                    <button type="button" wire:click="closeModal" class="text-sm text-[#005b8f] hover:underline font-medium">Cancel</button>
                    <button type="submit" form="employeeForm" class="bg-[#005b8f] hover:bg-[#004a75] text-white px-6 py-2 rounded text-sm font-semibold shadow-sm transition">
                        {{ $modalMode === 'add' ? 'Create Employee' : 'Update Employee' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
