<div class="max-w-7xl mx-auto space-y-6 pb-12">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <i class="fa-solid fa-user-shield text-[#005a9c]"></i> User Access Management (UAM)
            </h1>
            <p class="text-xs text-gray-500 mt-0.5">
                Role-based permission matrix and individual user overrides for all system modules and features.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                🔒 Access Control Center
            </span>
        </div>
    </div>

    {{-- Toast Notification --}}
    @if($toastMessage)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition
             class="p-3.5 rounded-xl text-xs font-semibold flex items-center justify-between shadow-xs transition {{ $toastType === 'error' ? 'bg-red-50 text-red-800 border border-red-200' : 'bg-green-50 text-green-800 border border-green-200' }}">
            <div class="flex items-center gap-2">
                <span>{{ $toastType === 'error' ? '⚠️' : '✓' }}</span>
                <span>{{ $toastMessage }}</span>
            </div>
            <button wire:click="dismissToast" @click="show = false" class="text-gray-400 hover:text-gray-700 font-bold">&times;</button>
        </div>
    @endif

    {{-- Top View Switcher Tabs --}}
    <div class="flex border-b border-gray-200">
        <button type="button"
                wire:click="setTab('roles')"
                class="px-5 py-2.5 text-xs font-bold border-b-2 transition flex items-center gap-2 {{ $tab === 'roles' ? 'border-[#005a9c] text-[#005a9c]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            <i class="fa-solid fa-users-gear"></i>
            <span>Role-Based Permissions (Default)</span>
            <span class="ml-1 px-1.5 py-0.5 text-[10px] rounded-full bg-blue-100 text-[#005a9c]">{{ $roles->count() }} Roles</span>
        </button>
        <button type="button"
                wire:click="setTab('users')"
                class="px-5 py-2.5 text-xs font-bold border-b-2 transition flex items-center gap-2 {{ $tab === 'users' ? 'border-[#005a9c] text-[#005a9c]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            <i class="fa-solid fa-user-lock"></i>
            <span>Employee Overrides</span>
            <span class="ml-1 px-1.5 py-0.5 text-[10px] rounded-full bg-gray-100 text-gray-600">{{ $users->count() }} Accounts</span>
        </button>
    </div>

    {{-- TAB 1: ROLE-BASED ACCESS CONTROL --}}
    @if($tab === 'roles')
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            {{-- Left Panel: Roles List & Role CRUD --}}
            <div class="lg:col-span-4 bg-white rounded-xl shadow-xs border border-gray-200 p-4 space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-gray-700">Application Roles</h3>
                        <p class="text-[11px] text-gray-400">Select a role to configure module matrix.</p>
                    </div>
                    <button type="button"
                            wire:click="openCreateRoleModal"
                            class="px-2.5 py-1.5 bg-[#005a9c] hover:bg-blue-700 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center gap-1">
                        <i class="fa-solid fa-plus text-[10px]"></i>
                        <span>Add Role</span>
                    </button>
                </div>

                {{-- Roles List --}}
                <div class="space-y-2 max-h-[560px] overflow-y-auto pr-1">
                    @foreach($roles as $r)
                        @php
                            $userCount = \App\Models\User::where('role', $r->name)->count();
                        @endphp
                        <div class="p-3 rounded-xl border transition {{ $selectedRoleId == $r->id ? 'border-[#005a9c] bg-blue-50/70 shadow-xs' : 'border-gray-200 hover:bg-gray-50' }}">
                            <div class="flex items-start justify-between gap-2">
                                <button type="button"
                                        wire:click="selectRole({{ $r->id }})"
                                        class="flex-1 text-left">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-gray-900">{{ $r->name }}</span>
                                        @if($r->name === 'Super Admin')
                                            <span class="text-[9px] px-1.5 py-0.5 rounded font-bold uppercase bg-amber-100 text-amber-800">
                                                👑 Super
                                            </span>
                                        @elseif($r->is_system)
                                            <span class="text-[9px] px-1.5 py-0.5 rounded font-bold uppercase bg-gray-100 text-gray-700">
                                                System
                                            </span>
                                        @else
                                            <span class="text-[9px] px-1.5 py-0.5 rounded font-bold uppercase bg-purple-100 text-purple-800">
                                                Custom
                                            </span>
                                        @endif
                                        @if($r->status === 'inactive')
                                            <span class="text-[9px] px-1.5 py-0.5 rounded font-bold uppercase bg-red-100 text-red-800">
                                                Inactive
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-[11px] text-gray-500 line-clamp-1 mt-0.5">{{ $r->description ?: 'No description provided.' }}</p>
                                    <span class="text-[10px] text-gray-400 mt-1 inline-block">
                                        {{ $userCount }} {{ \Illuminate\Support\Str::plural('employee', $userCount) }} assigned
                                    </span>
                                </button>

                                <div class="flex items-center gap-1 shrink-0">
                                    @if(!$r->isSystem())
                                        <button type="button"
                                                wire:click="openEditRoleModal({{ $r->id }})"
                                                class="p-1.5 text-gray-400 hover:text-[#005a9c] hover:bg-white rounded transition"
                                                title="Edit Role">
                                            <i class="fa-solid fa-pencil text-xs"></i>
                                        </button>
                                        <button type="button"
                                                wire:click="deleteRole({{ $r->id }})"
                                                wire:confirm="Are you sure you want to remove or deactivate role '{{ $r->name }}'?"
                                                class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-white rounded transition"
                                                title="Delete / Deactivate Role">
                                            <i class="fa-solid fa-trash-can text-xs"></i>
                                        </button>
                                    @endif
                                    @if($selectedRoleId == $r->id)
                                        <span class="text-[#005a9c] font-bold text-sm ml-1">✓</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Right Panel: Role Permissions Matrix --}}
            <div class="lg:col-span-8 bg-white rounded-xl shadow-xs border border-gray-200 p-6 space-y-6">
                @if($selectedRole)
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-gray-100">
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-base font-bold text-gray-900">Permissions for {{ $selectedRole->name }}</h2>
                                @if($selectedRole->name === 'Super Admin')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-100 text-amber-800">
                                        👑 Super Admin Master Role
                                    </span>
                                @elseif($selectedRole->is_system)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-blue-100 text-[#005a9c]">
                                        System Role
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-purple-100 text-purple-800">
                                        Custom Role
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ $selectedRole->description ?: 'Configure default module access granted to all users assigned to this role.' }}
                            </p>
                        </div>

                        {{-- Quick Presets --}}
                        @if($selectedRole->name !== 'Super Admin')
                            <div class="flex items-center gap-1.5">
                                <span class="text-[10px] font-bold text-gray-400 uppercase">Presets:</span>
                                <button type="button" wire:click="applyRolePreset('full')"
                                        class="px-2 py-1 rounded text-xs font-semibold bg-emerald-50 border border-emerald-200 text-emerald-700 hover:bg-emerald-100">
                                    Full Access
                                </button>
                                <button type="button" wire:click="applyRolePreset('readonly')"
                                        class="px-2 py-1 rounded text-xs font-semibold bg-blue-50 border border-blue-200 text-blue-700 hover:bg-blue-100">
                                    Read Only
                                </button>
                                <button type="button" wire:click="applyRolePreset('none')"
                                        class="px-2 py-1 rounded text-xs font-semibold bg-rose-50 border border-rose-200 text-rose-700 hover:bg-rose-100">
                                    No Access
                                </button>
                            </div>
                        @endif
                    </div>

                    @if($selectedRole->name === 'Super Admin')
                        <div class="p-6 rounded-xl border-2 border-amber-200 bg-amber-50/60 text-amber-900 space-y-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">👑</span>
                                <h3 class="text-sm font-bold">Unrestricted Master Bypass Role</h3>
                            </div>
                            <p class="text-xs leading-relaxed text-amber-800">
                                The <strong>Super Admin</strong> role automatically possesses unrestricted Read &amp; Write permissions across all present and future modules.
                                Super Administrators have master bypass capabilities, can configure critical organization settings, add custom fonts, and are completely hidden from frontend staff directories.
                            </p>
                            <span class="inline-block text-[11px] font-semibold text-amber-700 bg-amber-100/80 px-2.5 py-1 rounded-md">
                                Immutable System Master &bull; Full Permissions Enforced
                            </span>
                        </div>
                    @else
                        {{-- Matrix Table --}}
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                <tr class="border-b border-gray-200 bg-gray-50 text-gray-600 font-bold uppercase tracking-wider text-[10px]">
                                    <th class="p-3">Module / Feature</th>
                                    <th class="p-3 text-center w-28">No Access (0)</th>
                                    <th class="p-3 text-center w-28">Read Only (1)</th>
                                    <th class="p-3 text-center w-32">Read &amp; Write (2)</th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                @foreach($modules as $modKey => $modInfo)
                                    @php
                                        $currentLvl = (int)($rolePermissions[$modKey] ?? 1);
                                    @endphp
                                    <tr class="hover:bg-gray-50/70 transition">
                                        <td class="p-3">
                                            <div class="flex items-center gap-2">
                                                <div class="w-7 h-7 rounded-lg bg-gray-100 text-gray-700 flex items-center justify-center shrink-0">
                                                    <i class="{{ $modInfo['icon'] }} text-xs"></i>
                                                </div>
                                                <div>
                                                    <div class="font-bold text-gray-900">{{ $modInfo['name'] }}</div>
                                                    <div class="text-[11px] text-gray-500">{{ $modInfo['desc'] }}</div>
                                                </div>
                                            </div>
                                        </td>

                                        {{-- 0: No Access --}}
                                        <td class="p-3 text-center">
                                            <label class="inline-flex items-center justify-center cursor-pointer p-1 rounded hover:bg-rose-50">
                                                <input type="radio" wire:click="setRolePermission('{{ $modKey }}', 0)"
                                                       name="role_perm_{{ $modKey }}"
                                                       value="0"
                                                       @checked($currentLvl === 0)
                                                       class="w-4 h-4 text-rose-600 focus:ring-rose-500 border-gray-300">
                                            </label>
                                        </td>

                                        {{-- 1: Read Only --}}
                                        <td class="p-3 text-center">
                                            <label class="inline-flex items-center justify-center cursor-pointer p-1 rounded hover:bg-blue-50">
                                                <input type="radio" wire:click="setRolePermission('{{ $modKey }}', 1)"
                                                       name="role_perm_{{ $modKey }}"
                                                       value="1"
                                                       @checked($currentLvl === 1)
                                                       class="w-4 h-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                            </label>
                                        </td>

                                        {{-- 2: Read & Write --}}
                                        <td class="p-3 text-center">
                                            <label class="inline-flex items-center justify-center cursor-pointer p-1 rounded hover:bg-emerald-50">
                                                <input type="radio" wire:click="setRolePermission('{{ $modKey }}', 2)"
                                                       name="role_perm_{{ $modKey }}"
                                                       value="2"
                                                       @checked($currentLvl === 2)
                                                       class="w-4 h-4 text-emerald-600 focus:ring-emerald-500 border-gray-300">
                                            </label>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Action Bar --}}
                        <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                            <div class="text-xs text-gray-500">
                                Users with this role automatically receive these permissions unless overridden.
                            </div>
                            <button type="button"
                                    wire:click="saveRolePermissions"
                                    class="px-5 py-2.5 bg-[#005a9c] hover:bg-blue-800 text-white font-bold rounded-lg text-xs shadow-xs transition flex items-center gap-1.5">
                                <i class="fa-solid fa-floppy-disk"></i>
                                <span>Save Permissions for {{ $selectedRole->name }}</span>
                            </button>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    @endif

    {{-- TAB 2: USER-SPECIFIC OVERRIDES --}}
    @if($tab === 'users')
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            {{-- Left Panel: User List --}}
            <div class="lg:col-span-4 bg-white rounded-xl shadow-xs border border-gray-200 p-4 space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-700">Select Employee</h3>
                    <span class="text-[11px] text-gray-400 font-medium">{{ $users->count() }} accounts</span>
                </div>

                {{-- Filters --}}
                <div class="space-y-2">
                    <input type="text" wire:model.live.debounce.250ms="search" placeholder="Search by name, email..."
                           class="w-full text-xs rounded-lg border-gray-300 p-2 shadow-2xs focus:border-[#005a9c] focus:ring-[#005a9c]">

                    <div class="flex gap-1 overflow-x-auto text-[11px] pb-1">
                        <button type="button" wire:click="$set('roleFilter', 'all')"
                                class="px-2 py-1 rounded font-medium transition {{ $roleFilter === 'all' ? 'bg-[#005a9c] text-white font-bold' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                            All
                        </button>
                        @foreach($roles->where('name', '!=', 'Super Admin')->take(4) as $rPill)
                            <button type="button" wire:click="$set('roleFilter', '{{ $rPill->name }}')"
                                    class="px-2 py-1 rounded font-medium transition {{ $roleFilter === $rPill->name ? 'bg-[#005a9c] text-white font-bold' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                {{ $rPill->name }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Users Scroll List --}}
                <div class="space-y-1 max-h-[500px] overflow-y-auto divide-y divide-gray-50 pr-1">
                    @foreach($users as $u)
                        <button type="button" wire:click="selectUser({{ $u->id }})"
                                class="w-full text-left p-2.5 rounded-lg transition flex items-center gap-3 {{ $selectedUserId == $u->id ? 'bg-blue-50 border border-[#005a9c]/30 shadow-2xs' : 'hover:bg-gray-50' }}">
                            <div class="relative w-8 h-8 rounded-full overflow-hidden bg-slate-700 text-white flex items-center justify-center font-bold text-xs shrink-0">
                                @if($u->avatar_url)
                                    <img src="{{ $u->avatar_url }}" alt="{{ $u->name }}" class="w-full h-full object-cover">
                                @else
                                    <span>{{ $u->initials }}</span>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-bold text-gray-900 truncate">{{ $u->name }}</div>
                                <div class="text-[10px] text-gray-500 truncate">{{ $u->email }}</div>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span class="text-[9px] px-1 rounded font-semibold uppercase {{ $u->isAdmin() ? 'bg-purple-100 text-purple-800' : ($u->isManager() ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700') }}">
                                        {{ $u->role ?: 'Employee' }}
                                    </span>
                                    @if($u->department)
                                        <span class="text-[9px] text-gray-400">&bull; {{ $u->department->name }}</span>
                                    @endif
                                </div>
                            </div>
                            @if($selectedUserId == $u->id)
                                <span class="text-[#005a9c] text-sm font-bold">✓</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Right Panel: User Overrides Matrix --}}
            <div class="lg:col-span-8 bg-white rounded-xl shadow-xs border border-gray-200 p-6 space-y-6">
                @if($selectedUser)
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-gray-100">
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-base font-bold text-gray-900">Custom Overrides for {{ $selectedUser->name }}</h2>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $selectedUser->isAdmin() ? 'bg-purple-100 text-purple-800' : ($selectedUser->isManager() ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700') }}">
                                    Role: {{ $selectedUser->role ?: 'Employee' }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Set individual permissions that override the base role settings for this specific user.
                            </p>
                        </div>

                        {{-- Quick Presets --}}
                        <div class="flex items-center gap-1.5">
                            <span class="text-[10px] font-bold text-gray-400 uppercase">Presets:</span>
                            <button type="button" wire:click="applyUserPreset('full')"
                                    class="px-2 py-1 rounded text-xs font-semibold bg-emerald-50 border border-emerald-200 text-emerald-700 hover:bg-emerald-100">
                                Full Access
                            </button>
                            <button type="button" wire:click="applyUserPreset('readonly')"
                                    class="px-2 py-1 rounded text-xs font-semibold bg-blue-50 border border-blue-200 text-blue-700 hover:bg-blue-100">
                                Read Only
                            </button>
                            <button type="button" wire:click="applyUserPreset('none')"
                                    class="px-2 py-1 rounded text-xs font-semibold bg-rose-50 border border-rose-200 text-rose-700 hover:bg-rose-100">
                                No Access
                            </button>
                        </div>
                    </div>

                    {{-- Table --}}
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 text-gray-600 font-bold uppercase tracking-wider text-[10px]">
                                <th class="p-3">Module / Feature</th>
                                <th class="p-3 text-center w-28">No Access (0)</th>
                                <th class="p-3 text-center w-28">Read Only (1)</th>
                                <th class="p-3 text-center w-32">Read &amp; Write (2)</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                            @foreach($modules as $modKey => $modInfo)
                                @php
                                    $currentLvl = (int)($modulePermissions[$modKey] ?? 0);
                                @endphp
                                <tr class="hover:bg-gray-50/70 transition">
                                    <td class="p-3">
                                        <div class="flex items-center gap-2">
                                            <div class="w-7 h-7 rounded-lg bg-gray-100 text-gray-700 flex items-center justify-center shrink-0">
                                                <i class="{{ $modInfo['icon'] }} text-xs"></i>
                                            </div>
                                            <div>
                                                <div class="font-bold text-gray-900">{{ $modInfo['name'] }}</div>
                                                <div class="text-[11px] text-gray-500">{{ $modInfo['desc'] }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="p-3 text-center">
                                        <label class="inline-flex items-center justify-center cursor-pointer p-1 rounded hover:bg-rose-50">
                                            <input type="radio" wire:click="setUserPermission('{{ $modKey }}', 0)"
                                                   name="user_perm_{{ $modKey }}"
                                                   value="0"
                                                   @checked($currentLvl === 0)
                                                   class="w-4 h-4 text-rose-600 focus:ring-rose-500 border-gray-300">
                                        </label>
                                    </td>

                                    <td class="p-3 text-center">
                                        <label class="inline-flex items-center justify-center cursor-pointer p-1 rounded hover:bg-blue-50">
                                            <input type="radio" wire:click="setUserPermission('{{ $modKey }}', 1)"
                                                   name="user_perm_{{ $modKey }}"
                                                   value="1"
                                                   @checked($currentLvl === 1)
                                                   class="w-4 h-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                        </label>
                                    </td>

                                    <td class="p-3 text-center">
                                        <label class="inline-flex items-center justify-center cursor-pointer p-1 rounded hover:bg-emerald-50">
                                            <input type="radio" wire:click="setUserPermission('{{ $modKey }}', 2)"
                                                   name="user_perm_{{ $modKey }}"
                                                   value="2"
                                                   @checked($currentLvl === 2)
                                                   class="w-4 h-4 text-emerald-600 focus:ring-emerald-500 border-gray-300">
                                        </label>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                        <div class="text-xs text-gray-500">
                            Custom user overrides take precedence over the user's role permissions.
                        </div>
                        <button type="button" wire:click="saveUserPermissions"
                                class="px-5 py-2.5 bg-[#005a9c] hover:bg-blue-800 text-white font-bold rounded-lg text-xs shadow-xs transition flex items-center gap-1.5">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Save Overrides for {{ $selectedUser->name }}</span>
                        </button>
                    </div>
                @else
                    <div class="p-12 text-center text-gray-400">
                        <i class="fa-solid fa-user-lock text-4xl block mb-2"></i>
                        <span>Please select an employee on the left to configure individual overrides.</span>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ADD / EDIT ROLE MODAL --}}
    @if($showRoleModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-200 space-y-5">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                        <i class="fa-solid fa-shield-halved text-[#005a9c]"></i>
                        <span>{{ $isEditingRole ? 'Edit Role' : 'Create New Role' }}</span>
                    </h3>
                    <button type="button" wire:click="closeRoleModal" class="text-gray-400 hover:text-gray-600 text-lg font-bold">
                        &times;
                    </button>
                </div>

                <form wire:submit.prevent="saveRole" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Role Name *</label>
                        <input type="text"
                               wire:model="roleName"
                               placeholder="e.g. Field Supervisor, Quality Inspector"
                               class="w-full text-xs rounded-lg border-gray-300 p-2.5 focus:border-[#005a9c] focus:ring-[#005a9c]">
                        @error('roleName') <span class="text-[11px] text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Description</label>
                        <textarea wire:model="roleDescription"
                                  rows="3"
                                  placeholder="Describe the responsibilities and scope of this role..."
                                  class="w-full text-xs rounded-lg border-gray-300 p-2.5 focus:border-[#005a9c] focus:ring-[#005a9c]"></textarea>
                        @error('roleDescription') <span class="text-[11px] text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                        <button type="button"
                                wire:click="closeRoleModal"
                                class="px-4 py-2 rounded-lg text-xs font-semibold text-gray-600 hover:bg-gray-100 transition">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 rounded-lg text-xs font-bold bg-[#005a9c] hover:bg-blue-700 text-white shadow-xs transition">
                            {{ $isEditingRole ? 'Update Role' : 'Create Role' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
