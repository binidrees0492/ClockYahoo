<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $companyName = \App\Models\CompanySetting::get('company_name', config('app.name', 'ClockVerse'));
        $logoUrl = \App\Models\CompanySetting::getLogoUrl();
        $faviconUrl = \App\Models\CompanySetting::getFaviconUrl();
    @endphp

    <title>{{ $companyName }} - Login</title>
    <link rel="icon" href="{{ $faviconUrl }}">
    <link rel="shortcut icon" href="{{ $faviconUrl }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS & App Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Inter', sans-serif; }
        .bg-cs-blue { background-color: #005a9c; }
        .hover\:bg-cs-dark:hover { background-color: #003d6b; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-sky-50/50 to-blue-100 text-gray-800 antialiased min-h-screen flex flex-col justify-center items-center p-4">

    {{-- Global Bootstrap Alert Notifications (Reusable Component fixed at Top-Right) --}}
    <x-toast-alerts />


    <div class="w-full sm:max-w-md flex flex-col items-center mb-6">
        <a href="/" class="flex items-center gap-3 group">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $companyName }}" class="h-12 max-w-[180px] object-contain rounded-lg bg-white/70 p-1 shadow-sm" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex';">
                <div class="w-12 h-12 rounded-xl bg-amber-500 items-center justify-center text-white font-black text-2xl shadow-md group-hover:scale-105 transition transform hidden">
                    &#128337;
                </div>
            @else
                <div class="w-12 h-12 rounded-xl bg-amber-500 flex items-center justify-center text-white font-black text-2xl shadow-md group-hover:scale-105 transition transform">
                    &#128337;
                </div>
            @endif
            <div class="text-left">
                <div class="text-2xl font-bold tracking-tight text-[#005a9c]">{{ $companyName }}</div>
                <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wider">Workforce & Time Tracking</div>
            </div>
        </a>
    </div>

    <div class="w-full sm:max-w-md bg-white rounded-2xl shadow-xl border border-gray-100 p-8">
        {{ $slot }}
    </div>

    <div class="mt-8 text-center text-xs text-gray-400">
        &copy; {{ date('Y') }} ClockVerse. All rights reserved.
    </div>

</body>
</html>
