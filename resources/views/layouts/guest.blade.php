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

    {{-- Global Bootstrap Alert Notifications --}}
    <div x-data="{
             alerts: [],
             add(msg, type = 'success') {
                 if (!msg) return;
                 let text = typeof msg === 'object' ? (msg.message || msg.text || msg.msg || '') : String(msg);
                 if (!text || !text.trim()) return;
                 let id = Date.now() + Math.random();
                 let cleanType = ['success', 'error', 'danger', 'warning', 'info'].includes(type) ? type : 'info';
                 if (cleanType === 'error') cleanType = 'danger';
                 this.alerts.push({ id, msg: text.trim(), type: cleanType });
                 setTimeout(() => { this.alerts = this.alerts.filter(a => a.id !== id); }, 4500);
             }
         }"
         x-init="
             window.showToast = (msg, type = 'success') => add(msg, type);
             @if(session('message')) add(@js(session('message')), 'success'); @endif
             @if(session('success')) add(@js(session('success')), 'success'); @endif
             @if(session('error')) add(@js(session('error')), 'danger'); @endif
             @if(session('status')) add(@js(session('status')), 'info'); @endif
         "
         class="fixed top-4 left-1/2 -translate-x-1/2 z-[99999] flex flex-col gap-2.5 pointer-events-none w-full max-w-md px-4">
        <template x-for="a in alerts" :key="a.id">
            <div class="pointer-events-auto rounded-lg shadow-2xl px-4 py-3 text-sm font-medium border flex items-center justify-between gap-3 transition-all"
                 :class="{
                     'bg-[#d1e7dd] text-[#0f5132] border-[#badbcc]': a.type === 'success',
                     'bg-[#f8d7da] text-[#842029] border-[#f5c2c7]': a.type === 'danger' || a.type === 'error',
                     'bg-[#fff3cd] text-[#664d03] border-[#ffecb5]': a.type === 'warning',
                     'bg-[#cff4fc] text-[#055160] border-[#b6effb]': a.type === 'info'
                 }"
                 role="alert">
                <span x-text="a.msg"></span>
                <button type="button" @click="alerts = alerts.filter(x => x.id !== a.id)"
                        class="shrink-0 p-1 text-gray-500 hover:text-black transition opacity-60 hover:opacity-100 text-lg leading-none cursor-pointer"
                        title="Dismiss">&times;</button>
            </div>
        </template>
    </div>

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
