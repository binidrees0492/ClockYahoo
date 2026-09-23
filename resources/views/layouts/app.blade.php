<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'ClockVertex') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!-- Alpine.js is required for x-data modals -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body { font-family: 'Inter', sans-serif; }
        .bg-cs-blue     { background-color: #005a9c; }
        .bg-cs-dark     { background-color: #003d6b; }
        .text-cs-blue   { color: #005a9c; }
        .border-cs-blue { border-color: #005a9c; }
        .bg-cs-today    { background-color: #fdf6e3; }

        .nav-dropdown { position: relative; }
        .nav-dropdown > .dd-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            min-width: 200px;
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
                <a href="{{ route('dashboard') }}" class="text-xl font-bold italic tracking-tighter">ClockVertex</a>

                <div class="hidden md:flex items-center space-x-6 text-xs font-semibold uppercase tracking-wider">

                    <!-- Added My Time Clock -->
                    <a href="{{ route('dashboard') }}" class="hover:text-gray-300">My Time Clock</a>

                    <div class="nav-dropdown">
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-1 cursor-pointer">Time <span class="text-[8px]">&#9660;</span></a>
                        <div class="dd-menu">
                            <div class="dd-group-title">Timesheets</div>
                            <a href="{{ route('timesheets.view') }}" class="dd-link">View</a>
                            <a href="{{ route('timesheets.approve') }}" class="dd-link">Approve</a>

                            <div class="dd-divider"></div>

                            <div class="dd-group-title">Time Off</div>
                            <a href="{{ route('timeoff.requests') }}" class="dd-link">Time Off Requests</a>
                        </div>
                    </div>

                    <a href="{{ route('schedules') }}">Schedules</a>

                    <!-- Added Work Dropdown -->
                    <div class="nav-dropdown">
                        <a href="#" class="flex items-center gap-1 cursor-pointer {{ request()->routeIs('work.*') ? 'border-b-2 border-orange-400 pb-1' : '' }}">
                            Work <span class="text-[8px]">&#9660;</span>
                        </a>
                        <div class="dd-menu">
                            <a href="{{ route('work.jobs') }}" class="dd-link">Jobs</a>
                            <a href="{{ route('work.tasks') }}" class="dd-link">Tasks</a>
                        </div>
                    </div>

                    <a href="{{ route('reports') }}">Reports</a>

                    <div class="nav-dropdown">
                        <a href="{{ route('admin.employees') }}" class="flex items-center gap-1 cursor-pointer {{ request()->routeIs('admin.*') || request()->routeIs('timeoff.policies*') ? 'border-b-2 border-orange-400 pb-1' : '' }}">
                            Admin <span class="text-[8px]">&#9660;</span>
                        </a>
                        <div class="dd-menu">
                            <div class="dd-group-title">People</div>
                            <a href="{{ route('admin.employees') }}" class="dd-link">Employees</a>
                            <a href="{{ route('admin.departments') }}" class="dd-link">Departments</a>
                            <a href="{{ route('admin.locations') }}" class="dd-link">Locations</a>

                            <div class="dd-divider"></div>

                            <div class="dd-group-title">Company</div>
                            <a href="#" class="dd-link">Company Settings</a>
                            <a href="{{ route('timeoff.policies') }}" class="dd-link">Time Off Policies</a>
                        </div>
                    </div>

                    <a href="{{ route('map') }}">Map</a>
                </div>
            </div>

            <div class="flex items-center space-x-4 text-sm">
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

<main class="w-full px-4 py-6">
    {{ $slot ?? '' }}
    @yield('content')
</main>

@livewireScripts
</body>
</html>
