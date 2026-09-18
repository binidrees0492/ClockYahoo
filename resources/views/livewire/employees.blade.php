<div class="container mx-auto px-4 py-6" style="max-width: 1200px;">

    @if (session()->has('message'))
        <div class="mb-4 px-4 py-3 bg-green-100 text-green-800 rounded shadow-sm border border-green-200">
            {{ session('message') }}
        </div>
    @endif

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-light text-gray-800">Employees</h1>
        <button wire:click="openAddModal" class="bg-sky-700 hover:bg-sky-800 text-white px-4 py-2 rounded text-sm font-medium transition shadow-sm">
            Add Employee
        </button>
    </div>

    <div class="flex flex-col md:flex-row gap-4 mb-5 items-center justify-between bg-white p-4 rounded shadow-sm border border-gray-200">
        <div class="flex items-center gap-2 w-full md:w-auto">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by Name, ID, or Email Address" class="border border-gray-300 rounded px-3 py-2 text-sm w-full md:w-80 focus:outline-none focus:border-sky-700" />
            <button wire:click="clearFilters" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded text-sm hover:bg-gray-50">Clear</button>
        </div>

        <div class="flex items-center gap-2 w-full md:w-auto">
            <select wire:model.live="department_id" class="border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:outline-none focus:border-sky-700">
                <option value="">All Departments</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="status" class="border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:outline-none focus:border-sky-700">
                <option value="Active">Active Employees</option>
                <option value="Inactive">Inactive Employees</option>
            </select>
        </div>
    </div>

    <div class="text-gray-500 text-xs mb-2">{{ $employees->total() }} Employees</div>

    <div class="bg-white rounded shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full border-collapse text-left">
            <thead>
            <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 font-medium">
                <th class="p-4 w-10"><input type="checkbox" class="rounded"></th>
                <th class="p-4">Last Name</th>
                <th class="p-4">First Name</th>
                <th class="p-4">Email</th>
                <th class="p-4">ID</th>
                <th class="p-4">Role</th>
                <th class="p-4">Status</th>
                <th class="p-4 text-right">Actions</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-sm">
            @forelse($employees as $employee)
                <tr class="hover:bg-blue-50/50 transition">
                    <td class="p-4"><input type="checkbox" class="rounded"></td>
                    <td class="p-4 font-medium flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-sky-700 text-white flex items-center justify-center font-bold text-xs">
                            {{ strtoupper(substr($employee->last_name ?? $employee->name, 0, 2)) }}
                        </div>
                        {{ $employee->last_name ?? $employee->name }}
                    </td>
                    <td class="p-4 text-gray-600">{{ $employee->first_name }}</td>
                    <td class="p-4 text-gray-600">{{ $employee->email }}</td>
                    <td class="p-4 text-gray-600">{{ $employee->display_id ?? '-' }}</td>
                    <td class="p-4 text-gray-600">{{ $employee->designation->name ?? 'Employee' }}</td>
                    <td class="p-4">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ strtolower($employee->status) === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ ucfirst($employee->status ?? 'Active') }}
                            </span>
                    </td>
                    <td class="p-4 text-right">
                        <button wire:click="openEditModal({{ $employee->id }})" class="text-sky-700 hover:text-sky-900 font-medium text-xs bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded transition">
                            Edit
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="p-6 text-center text-gray-500">No employees found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $employees->links() }}
    </div>

    @if($isModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" x-data="{ activeTab: 'details' }">
            <div class="bg-white w-full max-w-4xl h-[85vh] rounded-lg shadow-2xl flex flex-col overflow-hidden">

                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-white">
                    <h2 class="text-xl font-light text-gray-800">
                        {{ $modalMode === 'add' ? 'Add Employee' : 'Edit Employee' }}
                    </h2>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 text-2xl font-bold leading-none">&times;</button>
                </div>

                <div class="flex flex-1 overflow-hidden">

                    <div class="w-60 border-r border-gray-200 bg-white py-4 overflow-y-auto">
                        <ul class="space-y-1">
                            <li>
                                <button @click="activeTab = 'details'" :class="activeTab === 'details' ? 'bg-blue-50 text-sky-700 border-l-4 border-sky-700 font-medium' : 'text-gray-700 hover:bg-gray-50'" class="w-full text-left px-5 py-3 text-sm transition">
                                    Employee Details
                                </button>
                            </li>
                            <li>
                                <button @click="activeTab = 'timeoff'" :class="activeTab === 'timeoff' ? 'bg-blue-50 text-sky-700 border-l-4 border-sky-700 font-medium' : 'text-gray-700 hover:bg-gray-50'" class="w-full text-left px-5 py-3 text-sm transition">
                                    Time Off
                                </button>
                            </li>
                            <li>
                                <button @click="activeTab = 'gps'" :class="activeTab === 'gps' ? 'bg-blue-50 text-sky-700 border-l-4 border-sky-700 font-medium' : 'text-gray-700 hover:bg-gray-50'" class="w-full text-left px-5 py-3 text-sm transition">
                                    GPS & Time Clock
                                </button>
                            </li>
                            <li>
                                <button @click="activeTab = 'permissions'" :class="activeTab === 'permissions' ? 'bg-blue-50 text-sky-700 border-l-4 border-sky-700 font-medium' : 'text-gray-700 hover:bg-gray-50'" class="w-full text-left px-5 py-3 text-sm transition">
                                    Permissions
                                </button>
                            </li>
                            <li>
                                <button @click="activeTab = 'manager'" :class="activeTab === 'manager' ? 'bg-blue-50 text-sky-700 border-l-4 border-sky-700 font-medium' : 'text-gray-700 hover:bg-gray-50'" class="w-full text-left px-5 py-3 text-sm transition">
                                    Manager Settings
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="flex-1 p-8 overflow-y-auto bg-white">
                        <form wire:submit="save" id="employeeForm">

                            <div x-show="activeTab === 'details'" class="space-y-6">
                                <h3 class="text-lg font-medium text-gray-800 mb-4">Employee Details</h3>

                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">First Name *</label>
                                        <input type="text" wire:model="first_name" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-sky-700">
                                        @error('first_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Last Name *</label>
                                        <input type="text" wire:model="last_name" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-sky-700">
                                        @error('last_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Employee ID</label>
                                        <input type="text" wire:model="display_id" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-sky-700">
                                    </div>
                                </div>

                                <div class="text-xs font-medium text-gray-700 mt-4">An email or mobile phone is required *</div>

                                <div class="grid grid-cols-2 gap-6 items-center">
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Email</label>
                                            <input type="email" wire:model="email" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-sky-700">
                                            @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Mobile Phone</label>
                                            <input type="text" wire:model="phone" placeholder="+1..." class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-sky-700">
                                        </div>
                                    </div>
                                    <div class="bg-gray-100 p-4 rounded text-xs text-gray-600">
                                        An email or mobile number is required to send an invitation
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Temporary Password *</label>
                                        <input type="password" wire:model="password" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-sky-700">
                                        <div class="text-[10px] text-gray-400 mt-1">8-128 characters; one number, one letter, one symbol</div>
                                    </div>
                                    <div class="w-36">
                                        <label class="block text-xs text-gray-500 mb-1">Color</label>
                                        <div class="flex items-center justify-between border border-gray-300 rounded px-3 py-2 bg-white">
                                            <div class="w-4 h-4 rounded-full bg-amber-400"></div>
                                            <span class="text-xs">&#9662;</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-3 gap-4 mt-4">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Status</label>
                                        <select wire:model="status" class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:outline-none focus:border-sky-700">
                                            <option value="Active">Active</option>
                                            <option value="Inactive">Inactive</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Role</label>
                                        <select wire:model="designation_id" class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:outline-none focus:border-sky-700">
                                            <option value="">Select Role...</option>
                                            @foreach($designations as $desig)
                                                <option value="{{ $desig->id }}">{{ $desig->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Base Pay Rate</label>
                                        <input type="text" wire:model="base_pay" placeholder="$0.00" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-sky-700">
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Departments</label>
                                        <select wire:model="department_id" class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:outline-none focus:border-sky-700">
                                            <option value="">Choose Department</option>
                                            @foreach($departments as $dept)
                                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Locations</label>
                                        <select wire:model="location_selection" class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:outline-none focus:border-sky-700">
                                            <option value="">Choose Location</option>
                                            @foreach($locations as $loc)
                                                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div x-show="activeTab === 'timeoff'" x-cloak class="space-y-4">
                                <h3 class="text-lg font-medium text-gray-800 mb-2">Time Off</h3>
                                <p class="text-xs text-gray-500 mb-4">Configure time off balances, accruals, and approvals for this employee.</p>
                                <div class="max-w-xs mb-4">
                                    <label class="block text-xs text-gray-500 mb-1">Default Time Off Policy</label>
                                    <select wire:model="time_off_policy" class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white">
                                        <option value="Standard Accrual Plan">Standard Accrual Plan</option>
                                        <option value="No Time Off Plan">No Time Off Plan</option>
                                    </select>
                                </div>
                                @if($modalMode === 'edit')
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="bg-gray-50 p-4 rounded border text-center">
                                            <span class="block text-2xl font-bold text-gray-700">{{ $leave_balance['annual'] ?? 0 }}</span>
                                            <span class="text-xs text-gray-400 uppercase">Annual Balance</span>
                                        </div>
                                        <div class="bg-gray-50 p-4 rounded border text-center">
                                            <span class="block text-2xl font-bold text-gray-700">{{ $leave_balance['sick'] ?? 0 }}</span>
                                            <span class="text-xs text-gray-400 uppercase">Sick Balance</span>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div x-show="activeTab === 'gps'" x-cloak class="space-y-4">
                                <h3 class="text-lg font-medium text-gray-800 mb-2">GPS & Time Clock</h3>
                                <p class="text-xs text-gray-500 mb-4">Manage location tracking and geofencing rules.</p>
                                <div class="space-y-3 text-sm text-gray-600">
                                    <label class="flex items-center gap-2"><input type="checkbox" wire:model="require_location" class="rounded"> Require Location to Clock In</label>
                                    <label class="flex items-center gap-2"><input type="checkbox" wire:model="restrict_geofence" class="rounded"> Restrict Punches outside Geofence</label>
                                    <label class="flex items-center gap-2"><input type="checkbox" wire:model="facial_recognition" class="rounded"> Enable Facial Recognition</label>
                                </div>
                            </div>

                            <div x-show="activeTab === 'permissions'" x-cloak class="space-y-4">
                                <h3 class="text-lg font-medium text-gray-800 mb-2">Permissions</h3>
                                <div class="space-y-4 max-w-md">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Mobile Timesheets</label>
                                        <select wire:model="mobile_timesheets" class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white">
                                            <option value="Add and edit time, notes, and attachments">Add and edit time, notes, and attachments</option>
                                            <option value="View only">View only</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Jobs</label>
                                        <select wire:model="jobs_perm" class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white">
                                            <option value="Add and edit">Add and edit</option>
                                            <option value="View only">View only</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Tasks</label>
                                        <select wire:model="tasks_perm" class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white">
                                            <option value="Add and edit">Add and edit</option>
                                            <option value="View only">View only</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div x-show="activeTab === 'manager'" x-cloak class="space-y-4">
                                <h3 class="text-lg font-medium text-gray-800 mb-2">Manager Settings</h3>
                                <p class="text-xs text-gray-500 mb-4">Manage supervisor capabilities and team access.</p>
                                <div class="space-y-4 max-w-md">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Team</label>
                                        <select wire:model="manager_team" class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white">
                                            <option value="For Everyone">For Everyone</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Timesheets</label>
                                        <select wire:model="manager_timesheets" class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white">
                                            <option value="Approve">Approve</option>
                                            <option value="View Only">View Only</option>
                                            <option value="No Access">No Access</option>
                                        </select>
                                    </div>
                                    <div class="space-y-2 pt-2 text-sm text-gray-600">
                                        <label class="flex items-center gap-2"><input type="checkbox" wire:model="mgr_view_map" class="rounded"> View location on Map</label>
                                        <label class="flex items-center gap-2"><input type="checkbox" wire:model="mgr_crew_clock" class="rounded"> Clock their team in and out with CrewClock</label>
                                        <label class="flex items-center gap-2"><input type="checkbox" wire:model="mgr_view_reports" class="rounded"> View Reports</label>
                                    </div>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>

                <div class="px-6 py-3 border-t border-gray-200 flex justify-between items-center bg-white">
                    <button wire:click="closeModal" class="text-sky-700 hover:underline text-sm font-medium bg-transparent border-0 cursor-pointer">Cancel</button>
                    <button type="submit" form="employeeForm" class="bg-sky-700 hover:bg-sky-800 text-white px-6 py-2 rounded text-sm font-medium transition">
                        <span wire:loading.remove wire:target="save">{{ $modalMode === 'add' ? 'Create Employee' : 'Save Changes' }}</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
