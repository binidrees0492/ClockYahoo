<header class="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-sm print:hidden" role="navigation">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <a href="{{ route('dashboard') }}" class="flex-shrink-0 mr-8">
                    <span class="text-xl font-black italic text-[#005a9c] tracking-tight">AXHAN Digital</span>
                </a>

                <nav class="hidden md:flex space-x-1 h-full" role="navigation">
                    <div class="relative group h-full" x-data="{ open: false }" @click.away="open = false">
                        <button @click="open = !open" class="px-3 py-5 text-sm font-bold uppercase text-gray-900 border-b-2 border-[#e75c0d] inline-flex items-center h-full">
                            Time <svg class="ml-1 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div x-show="open" x-cloak class="absolute left-0 mt-0 w-48 bg-white border border-gray-200 rounded-b shadow-lg z-50">
                            <div class="px-4 py-2 text-xs font-bold text-gray-400 uppercase tracking-wider bg-gray-50 border-b border-gray-100">Timesheets</div>
                            <a href="{{ route('timeclock.index') }}" class="block px-6 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">View</a>
                            <a href="#" class="block px-6 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">Approve</a>
                            <div class="border-t border-gray-100"></div>
                            <a href="{{ route('timeclock.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 font-medium">My Time Clock</a>
                        </div>
                    </div>

                    <a href="#" class="px-3 py-5 text-sm font-bold uppercase text-gray-500 hover:text-gray-900 border-b-2 border-transparent inline-flex items-center h-full">Schedules</a>

                    <div class="relative group h-full" x-data="{ open: false }" @click.away="open = false">
                        <button @click="open = !open" class="px-3 py-5 text-sm font-bold uppercase text-gray-500 hover:text-gray-900 border-b-2 border-transparent inline-flex items-center h-full">
                            Work <svg class="ml-1 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div x-show="open" x-cloak class="absolute left-0 mt-0 w-48 bg-white border border-gray-200 rounded-b shadow-lg z-50">
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">Customers</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">Jobs</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">Tasks</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">Quotes</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">Invoices</a>
                        </div>
                    </div>

                    <a href="#" class="px-3 py-5 text-sm font-bold uppercase text-gray-500 hover:text-gray-900 border-b-2 border-transparent inline-flex items-center h-full">Reports</a>

                    <div class="relative group h-full" x-data="{ open: false }" @click.away="open = false">
                        <button @click="open = !open" class="px-3 py-5 text-sm font-bold uppercase text-gray-500 hover:text-gray-900 border-b-2 border-transparent inline-flex items-center h-full">
                            Admin <svg class="ml-1 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div x-show="open" x-cloak class="absolute left-0 mt-0 w-56 bg-white border border-gray-200 rounded-b shadow-lg z-50">
                            <div class="px-4 py-2 text-xs font-bold text-gray-400 uppercase bg-gray-50 border-b border-gray-100">People</div>
                            <a href="#" class="block px-6 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">Employees</a>
                            <a href="#" class="block px-6 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">Departments</a>
                            <div class="px-4 py-2 text-xs font-bold text-gray-400 uppercase bg-gray-50 border-y border-gray-100 mt-2">Company</div>
                            <a href="#" class="block px-6 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">Settings</a>
                        </div>
                    </div>
                    <a href="#" class="px-3 py-5 text-sm font-bold uppercase text-gray-500 hover:text-gray-900 border-b-2 border-transparent inline-flex items-center h-full">Map</a>
                </nav>
            </div>

            <div class="hidden sm:flex sm:items-center space-x-4">
                <a href="#" class="text-sm font-medium text-[#005a9c] hover:text-blue-800">Get the Mobile App</a>

                <div class="relative group" x-data="{ open: false }" @click.away="open = false">
                    <button @click="open = !open" type="button" class="inline-flex items-center px-4 py-1.5 border border-transparent text-sm font-medium rounded-full shadow-sm text-white bg-[#1f76d1] hover:bg-blue-700">
                        + New
                    </button>
                    <div x-show="open" x-cloak class="absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded shadow-lg z-50">
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">Customer</a>
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">Job</a>
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">Task</a>
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">Employee</a>
                    </div>
                </div>

                <div class="relative group" x-data="{ open: false }" @click.away="open = false">
                    <button @click="open = !open" type="button" class="text-gray-500 hover:text-[#005a9c]">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </button>
                    <div x-show="open" x-cloak class="absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded shadow-lg z-50">
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">Help Center</a>
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">Contact Support</a>
                    </div>
                </div>

                <button type="button" class="text-gray-500 hover:text-[#005a9c] relative">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                </button>

                <div class="relative group" x-data="{ open: false }" @click.away="open = false">
                    <button @click="open = !open" type="button" class="bg-[#1f76d1] flex text-sm rounded-full h-8 w-8 items-center justify-center text-white font-bold hover:bg-blue-700 transition">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </button>
                    <div x-show="open" x-cloak class="absolute right-0 mt-2 w-64 bg-white border border-gray-200 rounded shadow-lg z-50">
                        <div class="px-4 py-3 border-b border-gray-100 flex items-center">
                            <div class="bg-[#1f76d1] flex text-sm rounded-full h-10 w-10 items-center justify-center text-white font-bold mr-3">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                            </div>
                            <div>
                                <div class="font-bold text-gray-900 text-sm uppercase">{{ auth()->user()->name ?? 'User' }}</div>
                                <div class="text-xs text-gray-500">{{ auth()->user()->email ?? 'email@example.com' }}</div>
                            </div>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">My Account</a>
                        <form method="POST" action="{{ route('logout') }}" class="w-full m-0 p-0">
                            @csrf
                            <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">Log Out</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
