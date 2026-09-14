<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'ClockShark') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body { font-family: 'Inter', sans-serif; }
        .bg-cs-blue     { background-color: #005a9c; }
        .bg-cs-dark     { background-color: #003d6b; }
        .text-cs-blue   { color: #005a9c; }
        .border-cs-blue { border-color: #005a9c; }
        .bg-cs-today    { background-color: #fdf6e3; }

        /* Navbar dropdown */
        .nav-dropdown { position: relative; }
        .nav-dropdown > .dd-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            min-width: 220px;
            background: #ffffff;
            color: #1f2937;
            border: 1px solid #e5e7eb;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            z-index: 60;
        }
        .nav-dropdown:hover > .dd-menu,
        .nav-dropdown:focus-within > .dd-menu {
            display: block;
        }
        .dd-group-title {
            padding: 12px 16px 6px;
            font-size: 11px;
            letter-spacing: 0.08em;
            font-weight: 700;
            color: #005a9c;
            text-transform: uppercase;
        }
        .dd-link {
            display: block;
            padding: 10px 16px;
            font-size: 13px;
            color: #374151;
            text-transform: none;
            letter-spacing: normal;
            font-weight: 500;
        }
        .dd-link:hover {
            background: #eaf3fb;
            color: #005a9c;
        }
        .dd-divider {
            height: 1px;
            background: #e5e7eb;
            margin: 6px 0;
        }
    </style>

    @livewireStyles
</head>
<body class="bg-gray-50 text-gray-800">

<nav class="bg-cs-blue text-white shadow-md sticky top-0 z-40">
    <div class="w-full px-4">
        <div class="flex justify-between h-14 items-center">

            <div class="flex items-center space-x-8">
                <a href="{{ route('dashboard') }}" class="text-xl font-bold italic tracking-tighter">ClockShark</a>

                <div class="hidden md:flex items-center space-x-6 text-xs font-semibold uppercase tracking-wider">

                    {{-- TIME dropdown --}}
                    <div class="nav-dropdown">
                        <a href="{{ route('dashboard') }}"
                           class="flex items-center gap-1 cursor-pointer {{ request()->routeIs('dashboard') || request()->routeIs('timesheets.*') || request()->routeIs('timeoff.*') ? 'border-b-2 border-orange-400 pb-1' : 'hover:text-gray-200' }}">
                            Time <span class="text-[8px]">&#9660;</span>
                        </a>
                        <div class="dd-menu">
                            <div class="dd-group-title">Timesheets</div>
                            <a href="{{ route('timesheets.view') }}"    class="dd-link">View</a>
                            <a href="{{ route('timesheets.approve') }}" class="dd-link">Approve</a>

                            <div class="dd-divider"></div>

                            <div class="dd-group-title">Time Off</div>
                            <a href="{{ route('timeoff.policies') }}" class="dd-link">Policies</a>
                            <a href="{{ route('timeoff.requests') }}" class="dd-link">Requests</a>

                            <div class="dd-divider"></div>

                            <a href="{{ route('dashboard') }}" class="dd-link">My Time Clock</a>
                        </div>
                    </div>

                    {{-- SCHEDULES --}}
                    <a href="{{ route('schedules') }}"
                       class="{{ request()->routeIs('schedules') ? 'border-b-2 border-orange-400 pb-1' : 'hover:text-gray-200' }}">
                        Schedules
                    </a>

                    {{-- WORK dropdown --}}
                    <div class="nav-dropdown">
                        <a href="{{ route('work') }}"
                           class="flex items-center gap-1 cursor-pointer {{ request()->routeIs('work') ? 'border-b-2 border-orange-400 pb-1' : 'hover:text-gray-200' }}">
                            Work <span class="text-[8px]">&#9660;</span>
                        </a>
                        <div class="dd-menu">
                            <div class="dd-group-title">Jobs</div>
                            <a href="{{ route('work') }}" class="dd-link">All Jobs</a>
                            <a href="{{ route('work') }}" class="dd-link">Job Sites</a>

                            <div class="dd-divider"></div>

                            <div class="dd-group-title">Tasks</div>
                            <a href="{{ route('work') }}" class="dd-link">All Tasks</a>
                        </div>
                    </div>

                    {{-- REPORTS --}}
                    <a href="{{ route('reports') }}"
                       class="{{ request()->routeIs('reports') ? 'border-b-2 border-orange-400 pb-1' : 'hover:text-gray-200' }}">
                        Reports
                    </a>

                    {{-- ADMIN dropdown --}}
                    <div class="nav-dropdown">
                        <a href="{{ route('admin') }}"
                           class="flex items-center gap-1 cursor-pointer {{ request()->routeIs('admin') ? 'border-b-2 border-orange-400 pb-1' : 'hover:text-gray-200' }}">
                            Admin <span class="text-[8px]">&#9660;</span>
                        </a>
                        <div class="dd-menu">
                            <div class="dd-group-title">Company</div>
                            <a href="{{ route('admin') }}" class="dd-link">Employees</a>
                            <a href="{{ route('admin') }}" class="dd-link">Roles</a>

                            <div class="dd-divider"></div>

                            <div class="dd-group-title">Settings</div>
                            <a href="{{ route('admin') }}" class="dd-link">Preferences</a>
                            <a href="{{ route('admin') }}" class="dd-link">Integrations</a>
                        </div>
                    </div>

                    {{-- MAP --}}
                    <a href="{{ route('map') }}"
                       class="{{ request()->routeIs('map') ? 'border-b-2 border-orange-400 pb-1' : 'hover:text-gray-200' }}">
                        Map
                    </a>
                </div>
            </div>

            <div class="flex items-center space-x-4 text-sm">
                <a href="#" class="hidden lg:inline text-blue-200 hover:text-white text-xs">Get the Mobile App</a>

                <button class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded-full text-xs font-bold flex items-center">
                    <span class="mr-1">+</span> New
                </button>

                <button class="text-gray-300 hover:text-white text-lg" title="Help">?</button>
                <button class="text-gray-300 hover:text-white text-lg" title="Notifications">&#128276;</button>

                <div class="w-8 h-8 rounded-full bg-blue-300 text-blue-900 flex items-center justify-center font-bold text-xs">
                    {{ strtoupper(substr(Auth::user()->name ?? 'MW', 0, 2)) }}
                </div>

                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-gray-300 hover:text-white text-xs ml-2">Logout</button>
                </form>
            </div>
        </div>
    </div>
</nav>

@if(session('success'))
    <div class="w-full px-4 mt-4">
        <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-2 rounded">
            {{ session('success') }}
        </div>
    </div>
@endif
@if(session('error'))
    <div class="w-full px-4 mt-4">
        <div class="bg-red-100 border border-red-400 text-red-800 px-4 py-2 rounded">
            {{ session('error') }}
        </div>
    </div>
@endif

<main class="w-full px-4 py-6">
    @yield('content')
</main>

{{-- FLOATING BOTTOM-RIGHT ACTIONS --}}
<a href="#"
   class="fixed bottom-6 right-24 bg-orange-500 hover:bg-orange-600 text-white font-bold text-xs px-4 py-3 rounded-full shadow-lg z-50">
    Start a Demo
</a>

<button type="button"
        class="fixed bottom-6 right-6 w-14 h-14 rounded-full bg-cs-blue hover:bg-blue-800 text-white shadow-lg z-50 flex items-center justify-center text-2xl">
    &#128172;
</button>

{{-- GLOBAL LOG DETAIL MODAL — reusable across all pages --}}
<livewire:time-log-detail />

@livewireScripts
</body>
</html>
