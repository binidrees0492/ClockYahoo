<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-light text-gray-800">Employees</h1>
        <button wire:click="openAddModal" class="bg-[#005b8f] hover:bg-[#004a75] text-white px-4 py-2 rounded text-sm font-semibold shadow-sm transition">
            Add Employee
        </button>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-3 bg-green-100 border border-green-200 text-green-800 text-sm rounded shadow-sm">
            {{ session('message') }}
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
            <button wire:click="$set('search', ''); $set('department_filter', ''); $set('status_filter', '');" class="text-xs text-[#005b8f] hover:underline mb-2 font-medium">
                Clear all filters
            </button>
        </div>
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
                            <div class="w-8 h-8 rounded-full text-white text-xs font-bold flex items-center justify-center shadow-inner" style="background-color: {{ $emp->color ?? '#ffa526' }}">
                                {{ strtoupper(substr($emp->first_name ?? $emp->name, 0, 1) . substr($emp->last_name ?? '', 0, 1)) }}
                            </div>
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
                            <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $emp->status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $emp->status ?? 'Active' }}
                            </span>
                        </td>
                        <td class="p-3 text-right">
                            <button wire:click="openEditModal({{ $emp->id }})" class="text-[#005b8f] hover:underline text-xs font-semibold mr-3">Edit</button>
                            <button wire:click="delete({{ $emp->id }})" wire:confirm="Are you sure you want to remove this employee?" class="text-red-500 hover:underline text-xs">Delete</button>
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
            <div class="bg-white rounded shadow-2xl w-full max-w-4xl h-[85vh] flex flex-col overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-white">
                    <h2 class="text-xl font-normal text-gray-800">{{ $modalMode === 'add' ? 'Add Employee' : 'Edit Employee' }}</h2>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
                </div>

                <div class="flex-1 flex overflow-hidden">
                    <div class="w-52 border-r border-gray-200 bg-white py-4 flex flex-col">
                        <button type="button" wire:click="setTab('details')" class="text-left px-5 py-3 text-sm border-l-4 transition {{ $activeTab === 'details' ? 'border-[#005b8f] bg-[#eef5fc] text-[#005b8f] font-medium' : 'border-transparent text-gray-700 hover:bg-gray-50' }}">
                            Employee Details
                        </button>
                        <button type="button" wire:click="setTab('timeoff')" class="text-left px-5 py-3 text-sm border-l-4 transition {{ $activeTab === 'timeoff' ? 'border-[#005b8f] bg-[#eef5fc] text-[#005b8f] font-medium' : 'border-transparent text-gray-700 hover:bg-gray-50' }}">
                            Time Off
                        </button>
                        <button type="button" wire:click="setTab('gps')" class="text-left px-5 py-3 text-sm border-l-4 transition {{ $activeTab === 'gps' ? 'border-[#005b8f] bg-[#eef5fc] text-[#005b8f] font-medium' : 'border-transparent text-gray-700 hover:bg-gray-50' }}">
                            GPS & Time Clock
                        </button>
                        <button type="button" wire:click="setTab('permissions')" class="text-left px-5 py-3 text-sm border-l-4 transition {{ $activeTab === 'permissions' ? 'border-[#005b8f] bg-[#eef5fc] text-[#005b8f] font-medium' : 'border-transparent text-gray-700 hover:bg-gray-50' }}">
                            Permissions
                        </button>
                        <button type="button" wire:click="setTab('manager')" class="text-left px-5 py-3 text-sm border-l-4 transition {{ $activeTab === 'manager' ? 'border-[#005b8f] bg-[#eef5fc] text-[#005b8f] font-medium' : 'border-transparent text-gray-700 hover:bg-gray-50' }}">
                            Manager Settings
                        </button>
                    </div>

                    <div class="flex-1 p-8 overflow-y-auto bg-white">
                        @if($activeTab === 'details')
                            <div>
                                <h3 class="text-lg font-medium text-gray-800 mb-6">Employee Details</h3>

                                <div class="grid grid-cols-3 gap-4 mb-4">
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

                                <div class="grid grid-cols-2 gap-4 mb-4">
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

                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">Password {{ $modalMode === 'edit' ? '(Leave blank to retain)' : '*' }}</label>
                                        <input type="password" wire:model="password" class="w-full border border-gray-300 rounded p-2 text-sm outline-none focus:border-[#005b8f]">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">Badge Color</label>
                                        <input type="color" wire:model="color" class="h-9 w-full border border-gray-300 rounded p-1 cursor-pointer">
                                    </div>
                                </div>

                                <div class="grid grid-cols-3 gap-4 border-t pt-4 mb-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Role *</label>
                                        <select wire:model.live="role" class="w-full border border-gray-300 rounded p-2 text-sm font-medium text-[#005b8f] outline-none">
                                            <option value="Employee">Employee</option>
                                            <option value="Manager">Manager</option>
                                            <option value="HR Manager">HR Manager</option>
                                            <option value="Administrator">Administrator</option>
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
                                <h3 class="text-xl font-normal text-gray-900 mb-1">Time Off</h3>
                                <p class="text-xs text-gray-500 mb-4">
                                    Changing this setting may affect the accrual of time off based on the details of the policy selected[cite: 2].
                                </p>

                                <div class="border border-gray-200 rounded overflow-hidden mb-6">
                                    <table class="w-full text-left text-sm">
                                        <thead class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-600 font-semibold">
                                            <tr>
                                                <th class="p-3">Policy</th>
                                                <th class="p-3 w-40 text-right">Hire Date</th>
                                                <th class="p-3 w-32 text-right">Balance (hrs)</th>
                                                <th class="p-3 w-16 text-center"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            @forelse($employeePolicies as $index => $item)
                                                <tr class="hover:bg-gray-50">
                                                    <td class="p-3 font-medium text-gray-800">{{ $item['name'] }}</td>
                                                    <td class="p-3 text-right">
                                                        <input type="date" wire:model="employeePolicies.{{ $index }}.hire_date" class="border border-gray-300 rounded p-1.5 text-xs text-right outline-none focus:border-[#005b8f]">
                                                    </td>
                                                    <td class="p-3 text-right">
                                                        <input type="number" step="0.5" wire:model="employeePolicies.{{ $index }}.hours_remaining" class="border border-gray-300 rounded p-1.5 text-xs text-right w-24 outline-none focus:border-[#005b8f]">
                                                    </td>
                                                    <td class="p-3 text-center">
                                                        <button type="button" wire:click="removePolicy({{ $index }})" class="text-red-500 hover:text-red-700 font-bold text-lg leading-none" title="Remove Policy">&times;</button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="p-6 text-center text-xs text-gray-400">
                                                        No policies assigned to this employee. Use the dropdown below to assign a policy.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot class="bg-gray-50 border-t border-gray-200">
                                            <tr>
                                                <td colspan="4" class="p-3">
                                                    <div class="max-w-xs">
                                                        <select wire:model.live="selectedPolicyToAdd" class="w-full border border-gray-300 rounded p-2 text-xs outline-none focus:border-[#005b8f] bg-white">
                                                            <option value="">+ Click to choose a policy to add...</option>
                                                            @foreach($availablePolicies as $pol)
                                                                <option value="{{ $pol->id }}">{{ $pol->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tfoot>
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
                                </div>
                            </div>
                        @endif

                        @if($activeTab === 'manager')
                            <div>
                                <h2 class="text-xl font-normal text-gray-900 mb-1">Manager Settings</h2>
                                <p class="text-xs text-gray-500 mb-6">If this person is a manager or HR manager, configure team scope and manager permissions[cite: 2].</p>

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
                                    <p class="text-xs text-gray-500 mb-4">This person can do the following for their team[cite: 2].</p>

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
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 bg-white flex justify-between items-center">
                    <button type="button" wire:click="closeModal" class="text-sm text-[#005b8f] hover:underline font-medium">Cancel</button>
                    <button type="button" wire:click="save" class="bg-[#005b8f] hover:bg-[#004a75] text-white px-6 py-2 rounded text-sm font-semibold shadow-sm transition">
                        {{ $modalMode === 'add' ? 'Create Employee' : 'Update Employee' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>