<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $companyThemeSetting = \App\Models\CompanySetting::get('company_theme', 'light');
        $activeTheme = Auth::check() && Auth::user()->theme_preference
            ? Auth::user()->theme_preference
            : $companyThemeSetting;
        $companyFontSetting = \App\Models\CompanySetting::get('company_font', 'opensans');
        $activeFont = Auth::check() && Auth::user()->font_preference
            ? Auth::user()->font_preference
            : $companyFontSetting;
        $baseTheme = \App\Models\CompanySetting::getBaseTheme(); // 'classic' or 'modern'
        $isModern = $baseTheme === 'modern';
        $modernRealtimeLoading = (bool) \App\Models\CompanySetting::get('modern_realtime_loading', true);
        $modernHighContrast = (bool) \App\Models\CompanySetting::get('modern_high_contrast', true);
        $modernMicroAnimations = (bool) \App\Models\CompanySetting::get('modern_micro_animations', true);
        $modernCustomIcons = (bool) \App\Models\CompanySetting::get('modern_custom_icons', true);
        $logoUrl = \App\Models\CompanySetting::getLogoUrl();
        $faviconUrl = \App\Models\CompanySetting::getFaviconUrl();
        $companyName = \App\Models\CompanySetting::get('company_name', config('app.name', 'ClockVerse'));
    @endphp

    <title>{{ $companyName }} - {{ $title ?? 'Workforce & Time Tracking' }} (Updated: {{ now()->timezone(Auth::check() ? Auth::user()->getTimezone() : 'UTC')->format('d M H:i') }})</title>

    <!-- Dynamic Favicon & PWA -->
    <link rel="icon" href="{{ $faviconUrl }}">
    <link rel="shortcut icon" href="{{ $faviconUrl }}">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#005a9c">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $companyName }}">
    <link rel="apple-touch-icon" href="{{ $faviconUrl }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://unpkg.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;700&family=Fira+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Inter:wght@300;400;500;600;700&family=Lato:ital,wght@0,300;0,400;0,700;1,400&family=Merriweather:ital,wght@0,300;0,400;0,700;1,400&family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Nunito:ital,wght@0,400;0,600;0,700;1,400&family=Open+Sans:ital,wght@0,300;0,400;0,600;0,700;1,400&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&family=Raleway:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Roboto:ital,wght@0,300;0,400;0,500;0,700;1,400&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;700&family=Fira+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Inter:wght@300;400;500;600;700&family=Lato:ital,wght@0,300;0,400;0,700;1,400&family=Merriweather:ital,wght@0,300;0,400;0,700;1,400&family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Nunito:ital,wght@0,400;0,600;0,700;1,400&family=Open+Sans:ital,wght@0,300;0,400;0,600;0,700;1,400&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&family=Raleway:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Roboto:ital,wght@0,300;0,400;0,500;0,700;1,400&display=swap" rel="stylesheet">
    </noscript>
    @php
        $availableFontsForHead = \App\Models\CompanySetting::getAvailableFonts();
    @endphp
    @foreach($availableFontsForHead as $headFont)
        @if(!empty($headFont['is_custom']) && !empty($headFont['google_url']))
            <link href="{{ $headFont['google_url'] }}" rel="stylesheet" media="print" onload="this.media='all'">
        @endif
    @endforeach
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" media="print" onload="this.media='all'" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Leaflet Interactive Maps (Deferred for zero render-blocking) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="" defer></script>

    <style>
        [x-cloak] { display: none !important; }
        /* Leaflet Map & Tailwind CSS Compatibility */
        .leaflet-container img {
            max-width: none !important;
            max-height: none !important;
        }
        .leaflet-container {
            font-family: inherit;
        }
        .leaflet-pane {
            z-index: 10 !important;
        }
        .leaflet-top, .leaflet-bottom {
            z-index: 20 !important;
        }
        @font-face {
            font-family: 'Aptos';
            src: local('Aptos Display'), local('Aptos'), local('Segoe UI');
            font-weight: 400;
            font-style: normal;
        }
        @font-face {
            font-family: 'Aptos';
            src: local('Aptos Display Bold'), local('Aptos Bold'), local('Segoe UI Bold');
            font-weight: 700;
            font-style: normal;
        }
        @font-face {
            font-family: 'Aptos';
            src: local('Aptos Display Italic'), local('Aptos Italic'), local('Segoe UI Italic');
            font-weight: 400;
            font-style: italic;
        }
        @php
            $fontCss = \App\Models\CompanySetting::getFontCss($activeFont);
        @endphp
        body {
            font-family: {!! $fontCss !!};
            -webkit-tap-highlight-color: transparent;
        }

        /* Vibrant Form Controls with depth and refined focus */
        input:not([type="checkbox"]):not([type="radio"]), select, textarea {
            border: 1.5px solid #cbd5e1 !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
        }
        input:not([type="checkbox"]):not([type="radio"]):focus, select:focus, textarea:focus {
            border-color: #005088 !important;
            box-shadow: 0 0 0 3px rgba(0, 80, 136, 0.2) !important;
            outline: none !important;
        }

        .bg-cs-blue      { background-color: #005088; }
        .bg-cs-dark      { background-color: #003e6b; }
        .text-cs-blue    { color: #005088; }
        .border-cs-blue  { border-color: #005088; }
        .text-cs-orange  { color: #f26522; }
        .border-cs-orange { border-color: #f26522; }
        .bg-cs-orange    { background-color: #f26522; }
        .bg-cs-pill-blue { background-color: #0077c5; }
        .bg-cs-today     { background-color: #fdf6e3; }

        .nav-dropdown { position: relative; }
        .nav-dropdown > .dd-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            min-width: 210px;
            background: #ffffff;
            color: #1f2937;
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            border-radius: 6px;
            z-index: 60;
            overflow: hidden;
        }
        .nav-dropdown:hover > .dd-menu,
        .nav-dropdown:focus-within > .dd-menu {
            display: block;
        }
        .dd-group-title {
            padding: 10px 16px 4px;
            font-size: 10px;
            letter-spacing: 0.08em;
            font-weight: 700;
            color: #005088;
            text-transform: uppercase;
        }
        .dd-link {
            display: block;
            padding: 9px 16px;
            font-size: 13px;
            color: #374151;
            text-transform: none;
            letter-spacing: normal;
            font-weight: 500;
            transition: all 0.15s ease;
        }
        .dd-link:hover {
            background: #eaf3fb;
            color: #005088;
            padding-left: 20px;
        }
        .dd-divider {
            height: 1px;
            background: #f0f2f5;
            margin: 4px 0;
        }

        /* Safe area padding for iPhone notch / home bar */
        .safe-bottom {
            padding-bottom: env(safe-area-inset-bottom, 0px);
        }

        /* Mobile-First App Optimizations */
        @media (max-width: 768px) {
            .overflow-x-auto {
                -webkit-overflow-scrolling: touch;
            }
            button, a, input, select, textarea {
                touch-action: manipulation;
            }
        }

        /* ================= THEMES ================= */
        /* ================= THEMES ================= */
        /* Dark Mode Theme */
        body.theme-dark {
            background-color: #0f172a !important;
            color: #f1f5f9 !important;
        }
        body.theme-dark nav.bg-cs-blue {
            background-color: #0b1120 !important;
            border-bottom: 1px solid #1e293b !important;
        }
        body.theme-dark .bg-white {
            background-color: #1e293b !important;
            color: #f1f5f9 !important;
        }
        body.theme-dark .bg-gray-50 {
            background-color: #0f172a !important;
        }
        body.theme-dark [class*="bg-blue-50"],
        body.theme-dark [class*="bg-sky-50"],
        body.theme-dark [class*="bg-indigo-50"],
        body.theme-dark [class*="bg-slate-50"] {
            background-color: #182234 !important;
            border-color: #334155 !important;
        }
        body.theme-dark [class*="bg-amber-50"] {
            background-color: #241a0e !important;
            border-color: #5c3a12 !important;
        }
        body.theme-dark [class*="bg-green-50"],
        body.theme-dark [class*="bg-emerald-50"] {
            background-color: #0b251d !important;
            border-color: #065f46 !important;
        }
        body.theme-dark [class*="bg-red-50"] {
            background-color: #291010 !important;
            border-color: #7f1d1d !important;
        }
        body.theme-dark .bg-cs-today {
            background-color: #1e3a5f !important;
            color: #93c5fd !important;
        }
        body.theme-dark .bg-gray-100,
        body.theme-dark .bg-gray-200 {
            background-color: #243044 !important;
            border-color: #334155 !important;
            color: #f1f5f9 !important;
        }
        body.theme-dark table th,
        body.theme-dark table td {
            border-color: #334155 !important;
        }
        body.theme-dark thead tr {
            background-color: #182234 !important;
        }
        body.theme-dark .text-cs-blue {
            color: #38bdf8 !important;
        }
        body.theme-dark .text-blue-700,
        body.theme-dark .text-blue-800,
        body.theme-dark .text-blue-900 {
            color: #7dd3fc !important;
        }
        body.theme-dark .text-indigo-700,
        body.theme-dark .text-indigo-800 {
            color: #a5b4fc !important;
        }
        body.theme-dark .text-gray-900,
        body.theme-dark .text-gray-800,
        body.theme-dark .text-gray-700 {
            color: #f8fafc !important;
        }
        body.theme-dark .text-gray-600 {
            color: #e2e8f0 !important;
        }
        body.theme-dark .text-gray-500 {
            color: #cbd5e1 !important;
        }
        body.theme-dark .text-gray-400 {
            color: #94a3b8 !important;
        }
        body.theme-dark .border-gray-200,
        body.theme-dark .border-gray-100,
        body.theme-dark .border-gray-300 {
            border-color: #334155 !important;
        }
        body.theme-dark input:not([type="checkbox"]):not([type="radio"]),
        body.theme-dark select,
        body.theme-dark textarea {
            background-color: #0f172a !important;
            color: #f8fafc !important;
            border-color: #334155 !important;
        }
        body.theme-dark .dd-menu {
            background-color: #1e293b !important;
            border-color: #334155 !important;
            color: #f1f5f9 !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5) !important;
        }
        body.theme-dark .dd-link {
            color: #cbd5e1 !important;
        }
        body.theme-dark .dd-link:hover {
            background-color: #334155 !important;
            color: #38bdf8 !important;
        }
        body.theme-dark .bg-blue-100 { background-color: #1e3a8a !important; color: #bfdbfe !important; border-color: #2563eb !important; }
        body.theme-dark .bg-green-100 { background-color: #064e3b !important; color: #a7f3d0 !important; border-color: #059669 !important; }
        body.theme-dark .bg-amber-100 { background-color: #78350f !important; color: #fde68a !important; border-color: #d97706 !important; }
        body.theme-dark .bg-red-100 { background-color: #7f1d1d !important; color: #fecaca !important; border-color: #dc2626 !important; }
        body.theme-dark .bg-purple-100 { background-color: #581c87 !important; color: #e9d5ff !important; border-color: #9333ea !important; }
        body.theme-dark .bg-indigo-100 { background-color: #312e81 !important; color: #c7d2fe !important; border-color: #4f46e5 !important; }
        body.theme-dark .bg-emerald-100 { background-color: #064e3b !important; color: #a7f3d0 !important; border-color: #059669 !important; }

        /* Gradient Modern Theme */
        body.theme-gradient {
            background: linear-gradient(135deg, #f0f4ff 0%, #e0e7ff 40%, #fae8ff 100%) !important;
            min-height: 100vh;
        }
        body.theme-gradient nav.bg-cs-blue {
            background: linear-gradient(90deg, #3730a3 0%, #4f46e5 50%, #7c3aed 100%) !important;
            box-shadow: 0 4px 20px rgba(79, 70, 229, 0.25) !important;
        }
        body.theme-gradient .bg-cs-blue {
            background-color: #4f46e5 !important;
        }
        body.theme-gradient .text-cs-blue {
            color: #4f46e5 !important;
        }
        body.theme-gradient .border-cs-blue {
            border-color: #4f46e5 !important;
        }
        body.theme-gradient .bg-white {
            background-color: rgba(255, 255, 255, 0.92) !important;
            backdrop-filter: blur(10px);
        }

        /* Sage Dark Green Theme */
        body.theme-sage-dark-green {
            background-color: #131c18 !important;
            color: #e6ede8 !important;
        }
        body.theme-sage-dark-green nav.bg-cs-blue {
            background-color: #1a2722 !important;
            border-bottom: 1px solid #2d423a !important;
        }
        body.theme-sage-dark-green .bg-cs-blue {
            background-color: #27473b !important;
        }
        body.theme-sage-dark-green .text-cs-blue {
            color: #5eead4 !important;
        }
        body.theme-sage-dark-green .border-cs-blue {
            border-color: #27473b !important;
        }
        body.theme-sage-dark-green .bg-white {
            background-color: #1a2722 !important;
            color: #e6ede8 !important;
        }
        body.theme-sage-dark-green .bg-gray-50 {
            background-color: #131c18 !important;
        }
        body.theme-sage-dark-green [class*="bg-blue-50"],
        body.theme-sage-dark-green [class*="bg-sky-50"],
        body.theme-sage-dark-green [class*="bg-indigo-50"],
        body.theme-sage-dark-green [class*="bg-slate-50"] {
            background-color: #16241f !important;
            border-color: #2d423a !important;
        }
        body.theme-sage-dark-green [class*="bg-amber-50"] {
            background-color: #281e10 !important;
            border-color: #4e3215 !important;
        }
        body.theme-sage-dark-green [class*="bg-green-50"],
        body.theme-sage-dark-green [class*="bg-emerald-50"] {
            background-color: #0d281e !important;
            border-color: #1c523d !important;
        }
        body.theme-sage-dark-green [class*="bg-red-50"] {
            background-color: #291111 !important;
            border-color: #6b2121 !important;
        }
        body.theme-sage-dark-green .bg-cs-today {
            background-color: #1f352b !important;
            color: #a7f3d0 !important;
        }
        body.theme-sage-dark-green .bg-gray-100,
        body.theme-sage-dark-green .bg-gray-200 {
            background-color: #22332c !important;
            border-color: #2d423a !important;
            color: #e6ede8 !important;
        }
        body.theme-sage-dark-green table th,
        body.theme-sage-dark-green table td {
            border-color: #2d423a !important;
        }
        body.theme-sage-dark-green thead tr {
            background-color: #16241f !important;
        }
        body.theme-sage-dark-green .text-blue-700,
        body.theme-sage-dark-green .text-blue-800,
        body.theme-sage-dark-green .text-blue-900 {
            color: #5eead4 !important;
        }
        body.theme-sage-dark-green .text-gray-900,
        body.theme-sage-dark-green .text-gray-800,
        body.theme-sage-dark-green .text-gray-700 {
            color: #f0fdf4 !important;
        }
        body.theme-sage-dark-green .text-gray-600 {
            color: #cbdad3 !important;
        }
        body.theme-sage-dark-green .text-gray-500 {
            color: #9cb3a8 !important;
        }
        body.theme-sage-dark-green .text-gray-400 {
            color: #7b9487 !important;
        }
        body.theme-sage-dark-green .border-gray-200,
        body.theme-sage-dark-green .border-gray-100,
        body.theme-sage-dark-green .border-gray-300 {
            border-color: #2d423a !important;
        }
        body.theme-sage-dark-green input:not([type="checkbox"]):not([type="radio"]),
        body.theme-sage-dark-green select,
        body.theme-sage-dark-green textarea {
            background-color: #131c18 !important;
            color: #e6ede8 !important;
            border-color: #2d423a !important;
        }
        body.theme-sage-dark-green .dd-menu {
            background-color: #1a2722 !important;
            border-color: #2d423a !important;
            color: #e6ede8 !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5) !important;
        }
        body.theme-sage-dark-green .dd-link {
            color: #cbdad3 !important;
        }
        body.theme-sage-dark-green .dd-link:hover {
            background-color: #27473b !important;
            color: #74d8af !important;
        }
        body.theme-sage-dark-green .bg-green-100,
        body.theme-sage-dark-green .bg-emerald-100 { background-color: #064e3b !important; color: #6ee7b7 !important; border-color: #059669 !important; }
        body.theme-sage-dark-green .bg-blue-100 { background-color: #133938 !important; color: #99f6e4 !important; border-color: #0d9488 !important; }
        body.theme-sage-dark-green .bg-amber-100 { background-color: #5c3813 !important; color: #fed7aa !important; border-color: #d97706 !important; }
        body.theme-sage-dark-green .bg-red-100 { background-color: #631b1b !important; color: #fecaca !important; border-color: #dc2626 !important; }
        body.theme-sage-dark-green .bg-purple-100 { background-color: #4c1d68 !important; color: #e9d5ff !important; border-color: #9333ea !important; }
        body.theme-sage-dark-green .bg-indigo-100 { background-color: #2e2669 !important; color: #c7d2fe !important; border-color: #4f46e5 !important; }

        /* ================= BASE THEME: MODERN ARCHITECTURE ================= */
        body.base-modern {
            background-color: #f8fafc !important;
            color: #0f172a !important;
            letter-spacing: -0.011em;
        }

        /* Modern Top Navbar */
        body.base-modern nav.bg-cs-blue {
            background: #0f172a !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
            box-shadow: 0 4px 16px -2px rgba(0, 0, 0, 0.3) !important;
        }

        /* Modern Navbar Brand Logo */
        body.base-modern .modern-brand-badge {
            background: linear-gradient(135deg, #4f46e5 0%, #06b6d4 100%);
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.35);
        }

        /* Modern Nav Link Pills */
        body.base-modern .nav-pill-link {
            padding: 0.35rem 0.75rem;
            border-radius: 9999px;
            transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
            color: #94a3b8;
            font-weight: 600;
        }
        body.base-modern .nav-pill-link:hover {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.08);
            transform: translateY(-1px);
        }
        body.base-modern .nav-pill-link.active-link {
            color: #38bdf8 !important;
            background: rgba(56, 189, 248, 0.12) !important;
            border: 1px solid rgba(56, 189, 248, 0.3) !important;
            box-shadow: 0 2px 8px rgba(56, 189, 248, 0.15) !important;
        }

        /* Modern Dropdown Menus */
        body.base-modern .dd-menu {
            border-radius: 1rem !important;
            background: rgba(255, 255, 255, 0.98) !important;
            backdrop-filter: blur(20px) !important;
            -webkit-backdrop-filter: blur(20px) !important;
            border: 1px solid rgba(226, 232, 240, 0.9) !important;
            box-shadow: 0 20px 45px -12px rgba(15, 23, 42, 0.18), 0 4px 12px rgba(0, 0, 0, 0.05) !important;
            animation: modernDropdownSlide 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards !important;
            transform-origin: top left;
            padding: 0.5rem !important;
        }
        @keyframes modernDropdownSlide {
            from {
                opacity: 0;
                transform: translateY(-6px) scale(0.97);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        body.base-modern .dd-group-title {
            color: #6366f1 !important;
            font-weight: 800 !important;
            font-size: 10px !important;
            letter-spacing: 0.08em !important;
            padding: 0.5rem 0.75rem 0.25rem !important;
        }
        body.base-modern .dd-link {
            border-radius: 0.5rem !important;
            padding: 0.5rem 0.75rem !important;
            color: #334155 !important;
            font-weight: 600 !important;
            font-size: 12.5px !important;
            transition: all 0.15s ease !important;
            display: flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
        }
        body.base-modern .dd-link:hover {
            background: #f1f5f9 !important;
            color: #4f46e5 !important;
            padding-left: 1rem !important;
            transform: translateX(2px);
        }

        /* Modern "+ New" Action Button */
        body.base-modern .modern-action-btn {
            background: linear-gradient(135deg, #4f46e5 0%, #06b6d4 100%) !important;
            border-radius: 9999px !important;
            box-shadow: 0 4px 14px 0 rgba(79, 70, 229, 0.35) !important;
            border: none !important;
            color: white !important;
            font-weight: 700 !important;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
        }
        body.base-modern .modern-action-btn:hover {
            transform: translateY(-1px) scale(1.02) !important;
            box-shadow: 0 6px 20px 0 rgba(79, 70, 229, 0.45) !important;
        }
        body.base-modern .modern-action-btn:active {
            transform: scale(0.97) !important;
        }

        /* Modern Card & Surface Geometry - Strictly page-level cards, NEVER modals/dialogs/dropdowns/tabs */
        body.base-modern main .bg-white:not(.fixed *):not([role="dialog"] *):not(.dd-menu):not(.dd-menu *):not(nav *):not(header *):not(.modal-exempt) {
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.03), 0 4px 14px -3px rgba(0, 0, 0, 0.04);
        }

        /* Modal & Dialog Protection - Strictly isolate modals, dialogs, dropdowns, and tab strips */
        .fixed .bg-white,
        [role="dialog"] .bg-white,
        .modal-shell,
        .modal-shell .bg-white,
        .fixed button,
        [role="dialog"] button {
            transform: none !important;
        }
        .fixed table,
        [role="dialog"] table {
            border-collapse: collapse !important;
            border-spacing: 0 !important;
        }

        /* Modern High Contrast Buttons */
        body.base-modern main button:not([class*="sr-only"]):not(.fixed *):not([role="dialog"] *):active,
        body.base-modern main a.btn:active {
            transform: scale(0.98) !important;
        }

        /* Modern High Contrast Tables - Only on page body, never in modals */
        body.base-modern main table:not(.fixed *):not([role="dialog"] *) {
            border-collapse: separate !important;
            border-spacing: 0 !important;
        }
        body.base-modern main table:not(.fixed *):not([role="dialog"] *) thead th {
            background-color: #f1f5f9 !important;
            color: #334155 !important;
            font-weight: 700 !important;
            font-size: 11px !important;
            letter-spacing: 0.06em !important;
            border-bottom: 2px solid #e2e8f0 !important;
        }
        body.base-modern main table:not(.fixed *):not([role="dialog"] *) tbody tr:hover {
            background-color: rgba(99, 102, 241, 0.04) !important;
        }

        /* Modern Form Inputs & Controls */
        body.base-modern input:not([type="checkbox"]):not([type="radio"]),
        body.base-modern select,
        body.base-modern textarea {
            border-radius: 0.625rem !important;
            border: 1.5px solid #cbd5e1 !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03) !important;
            transition: border-color 0.15s ease, box-shadow 0.15s ease !important;
        }
        body.base-modern input:not([type="checkbox"]):not([type="radio"]):focus,
        body.base-modern select:focus,
        body.base-modern textarea:focus {
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25) !important;
        }

        /* Modern Floating Island Dock for Mobile */
        body.base-modern nav.md\\:hidden {
            bottom: 0.75rem !important;
            left: 0.75rem !important;
            right: 0.75rem !important;
            border-radius: 1.25rem !important;
            background: #0f172a !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            box-shadow: 0 16px 32px -8px rgba(0, 0, 0, 0.5) !important;
            color: #94a3b8 !important;
        }
        body.base-modern nav.md\\:hidden a {
            color: #94a3b8 !important;
        }
        body.base-modern nav.md\\:hidden a.text-\\[\\#005a9c\\] {
            color: #38bdf8 !important;
            font-weight: 700 !important;
        }

        /* Modern Duotone Icons */
        body.base-modern .modern-icon-badge {
            width: 1.75rem;
            height: 1.75rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            background: rgba(99, 102, 241, 0.1);
            color: #4f46e5;
            font-size: 0.8rem;
        }

        /* Modern Dark Mode Adaptations */
        body.base-modern.theme-dark {
            background-color: #0b0f19 !important;
            color: #f8fafc !important;
        }
        body.base-modern.theme-dark nav.bg-cs-blue {
            background: rgba(11, 15, 25, 0.96) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
        }
        body.base-modern.theme-dark .bg-white {
            background-color: #131b2e !important;
            border-color: #1e293b !important;
            color: #f8fafc !important;
        }
        body.base-modern.theme-dark .dd-menu {
            background-color: #131b2e !important;
            border-color: #1e293b !important;
            color: #f8fafc !important;
        }
        body.base-modern.theme-dark .dd-link {
            color: #cbd5e1 !important;
        }
        body.base-modern.theme-dark .dd-link:hover {
            background-color: #1e293b !important;
            color: #38bdf8 !important;
        }
        body.base-modern.theme-dark table thead th {
            background-color: #0f172a !important;
            color: #94a3b8 !important;
            border-color: #1e293b !important;
        }
    </style>

    @livewireStyles
</head>
<body class="base-{{ $baseTheme }} {{ $isModern ? 'base-modern modern-layout' : 'base-classic' }} theme-{{ Str::slug($activeTheme) }} bg-[#f4f6f8] text-gray-800 antialiased min-h-screen flex flex-col"
      x-data="{ mobileDrawerOpen: false, deferredPrompt: null, showInstallBtn: false, showHelpModal: false, showSearchModal: false, searchQuery: '' }"
      @keydown.window.ctrl.k.prevent="showSearchModal = true"
      @keydown.window.cmd.k.prevent="showSearchModal = true"
      @keydown.escape.window="showSearchModal = false; showHelpModal = false"
      x-init="
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            showInstallBtn = true;
        });
        window.addEventListener('appinstalled', () => {
            showInstallBtn = false;
            deferredPrompt = null;
        });
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js');
        }
      ">

{{-- Top Header / Navigation (ClockShark Authentic White Header) --}}
<nav class="bg-white border-b border-gray-200 sticky top-0 z-40 shadow-xs">
    <div class="w-full px-4 sm:px-6">
        <div class="flex justify-between h-14 items-center">

            {{-- Brand & Desktop Links --}}
            <div class="flex items-center space-x-3 lg:space-x-6 h-14">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2 group py-1 shrink-0">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $companyName }}" class="h-8 max-w-[140px] object-contain" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex';">
                        <div class="items-center gap-0.5 hidden">
                            @if(strtolower(trim($companyName)) === 'clockshark' || strtolower(trim($companyName)) === 'clockverse' || strtolower(trim($companyName)) === 'clockyahoo')
                                <span class="text-xl font-bold tracking-tight text-[#005088]">Clock</span><span class="text-xl font-black italic tracking-tight text-[#0077c5]">Shark</span>
                            @else
                                <span class="text-xl font-bold tracking-tight text-[#005088]">{{ $companyName }}</span>
                            @endif
                        </div>
                    @else
                        <div class="flex items-center gap-0.5">
                            @if(strtolower(trim($companyName)) === 'clockshark' || strtolower(trim($companyName)) === 'clockverse' || strtolower(trim($companyName)) === 'clockyahoo')
                                <span class="text-xl font-bold tracking-tight text-[#005088]">Clock</span><span class="text-xl font-black italic tracking-tight text-[#0077c5]">Shark</span>
                            @else
                                <span class="text-xl font-bold tracking-tight text-[#005088]">{{ $companyName }}</span>
                            @endif
                        </div>
                    @endif
                </a>

                <div class="hidden md:flex items-center space-x-1 lg:space-x-2 text-xs font-semibold uppercase tracking-wider h-14">

                    <!-- 1. Time Dropdown -->
                    @php
                        $showTimesheetsView = \App\Models\CompanySetting::isPageActive('timesheets_view') && Auth::user()->canAccessTimesheets();
                        $showTimesheetsApprove = \App\Models\CompanySetting::isPageActive('timesheets_approve') && Auth::user()->canApproveTimesheets();
                        $showTimeoffRequests = \App\Models\CompanySetting::isPageActive('timeoff_requests');
                        $showTimeoffPolicies = \App\Models\CompanySetting::isPageActive('timeoff_policies') && Auth::user()->canAccessAdmin();
                        $showTimeClock = \App\Models\CompanySetting::isPageActive('dashboard');
                        $hasTimeAccess = $showTimesheetsView || $showTimesheetsApprove || $showTimeoffRequests || $showTimeoffPolicies || $showTimeClock;
                    @endphp

                    @if($hasTimeAccess)
                        <div class="nav-dropdown h-14 flex items-center">
                            <a href="{{ route('dashboard') }}" wire:navigate
                               class="h-14 flex items-center gap-1 px-2.5 transition select-none {{ request()->routeIs('dashboard') || request()->routeIs('timesheets.*') || request()->routeIs('timeoff.*') || request()->routeIs('timeclock.*') ? 'border-b-[3px] border-[#f26522] text-[#f26522] font-black' : 'border-b-[3px] border-transparent text-gray-700 hover:text-[#005088] font-bold' }}">
                                <span>Time</span> <span class="text-[8px]">&#9660;</span>
                            </a>
                            <div class="dd-menu">
                                @if($showTimesheetsView || $showTimesheetsApprove)
                                    <div class="dd-group-title">Timesheets</div>
                                    @if($showTimesheetsView)
                                        <a href="{{ route('timesheets.view') }}" wire:navigate class="dd-link">
                                            <span>View Timesheets</span>
                                        </a>
                                    @endif
                                    @if($showTimesheetsApprove)
                                        <a href="{{ route('timesheets.approve') }}" wire:navigate class="dd-link">
                                            <span>Approve Timesheets</span>
                                        </a>
                                    @endif
                                @endif

                                @if($showTimeoffRequests || $showTimeoffPolicies)
                                    <div class="dd-divider"></div>
                                    <div class="dd-group-title">Time Off</div>
                                    @if($showTimeoffPolicies)
                                        <a href="{{ route('timeoff.policies') }}" wire:navigate class="dd-link">
                                            <span>Policies</span>
                                        </a>
                                    @endif
                                    @if($showTimeoffRequests)
                                        <a href="{{ route('timeoff.requests') }}" wire:navigate class="dd-link">
                                            <span>Requests</span>
                                        </a>
                                    @endif
                                @endif

                                @if($showTimeClock)
                                    <div class="dd-divider"></div>
                                    <a href="{{ route('dashboard') }}" wire:navigate class="dd-link font-semibold">
                                        <span>My Time Clock</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- 2. Schedules -->
                    @if(\App\Models\CompanySetting::isPageActive('schedules') && Auth::user()->canAccessSchedules())
                        <a href="{{ route('schedules') }}" wire:navigate
                           class="h-14 flex items-center gap-1 px-2.5 transition select-none {{ request()->routeIs('schedules*') ? 'border-b-[3px] border-[#f26522] text-[#f26522] font-black' : 'border-b-[3px] border-transparent text-gray-700 hover:text-[#005088] font-bold' }}">
                            <span>Schedules</span>
                        </a>
                    @endif

                    <!-- 3. Work Dropdown -->
                    @php
                        $canJobs = \App\Models\CompanySetting::isPageActive('work_jobs') && Auth::user()->canView('jobs');
                        $canTasks = \App\Models\CompanySetting::isPageActive('work_tasks') && Auth::user()->canView('tasks');
                        $canCustomers = \App\Models\CompanySetting::isPageActive('work_customers') && Auth::user()->canView('customers');
                        $canQuotes = \App\Models\CompanySetting::isPageActive('work_quotes') && Auth::user()->canView('quotes');
                        $canInvoices = \App\Models\CompanySetting::isPageActive('work_invoices') && Auth::user()->canView('invoices');
                        $hasWorkAccess = $canJobs || $canTasks || $canCustomers || $canQuotes || $canInvoices;
                    @endphp

                    @if($hasWorkAccess)
                        <div class="nav-dropdown h-14 flex items-center">
                            <a href="#" class="h-14 flex items-center gap-1 px-2.5 transition select-none {{ request()->routeIs('work.*') ? 'border-b-[3px] border-[#f26522] text-[#f26522] font-black' : 'border-b-[3px] border-transparent text-gray-700 hover:text-[#005088] font-bold' }}">
                                <span>Work</span> <span class="text-[8px]">&#9660;</span>
                            </a>
                            <div class="dd-menu">
                                @if($canCustomers)
                                    <a href="{{ route('work.customers') }}" wire:navigate class="dd-link">
                                        <span>Customers</span>
                                    </a>
                                @endif
                                @if($canJobs)
                                    <a href="{{ route('work.jobs') }}" wire:navigate class="dd-link">
                                        <span>Jobs</span>
                                    </a>
                                @endif
                                @if($canTasks)
                                    <a href="{{ route('work.tasks') }}" wire:navigate class="dd-link">
                                        <span>Tasks</span>
                                    </a>
                                @endif
                                @if($canQuotes)
                                    <a href="{{ route('work.quotes') }}" wire:navigate class="dd-link">
                                        <span>Quotes</span>
                                    </a>
                                @endif
                                @if($canInvoices)
                                    <a href="{{ route('work.invoices') }}" wire:navigate class="dd-link">
                                        <span>Invoices</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- 4. Reports -->
                    @if(\App\Models\CompanySetting::isPageActive('reports') && Auth::user()->canViewReports())
                        <a href="{{ route('reports') }}" wire:navigate
                           class="h-14 flex items-center gap-1 px-2.5 transition select-none {{ request()->routeIs('reports*') ? 'border-b-[3px] border-[#f26522] text-[#f26522] font-black' : 'border-b-[3px] border-transparent text-gray-700 hover:text-[#005088] font-bold' }}">
                            <span>Reports</span>
                        </a>
                    @endif

                    <!-- 5. Admin Dropdown -->
                    @if(Auth::user()->canAccessAdmin())
                        @php
                            $canAdminEmployees = \App\Models\CompanySetting::isPageActive('admin_employees');
                            $canAdminDepartments = \App\Models\CompanySetting::isPageActive('admin_departments');
                            $canAdminLocations = \App\Models\CompanySetting::isPageActive('admin_locations');
                            $canAdminPto = \App\Models\CompanySetting::isPageActive('timeoff_policies');
                            $canAdminAudit = \App\Models\CompanySetting::isPageActive('admin_audit_log');
                            $hasPeopleGroup = $canAdminEmployees || $canAdminDepartments || $canAdminLocations;
                        @endphp
                        <div class="nav-dropdown h-14 flex items-center">
                            <a href="{{ route('admin.employees') }}" wire:navigate
                               class="h-14 flex items-center gap-1 px-2.5 transition select-none {{ request()->routeIs('admin.employees') || request()->routeIs('admin.departments') || request()->routeIs('admin.locations') || request()->routeIs('admin.audit-log') || request()->routeIs('admin.company-settings') ? 'border-b-[3px] border-[#f26522] text-[#f26522] font-black' : 'border-b-[3px] border-transparent text-gray-700 hover:text-[#005088] font-bold' }}">
                                <span>Admin</span> <span class="text-[8px]">&#9660;</span>
                            </a>
                            <div class="dd-menu">
                                @if($hasPeopleGroup)
                                    <div class="dd-group-title">People</div>
                                    @if($canAdminEmployees)
                                        <a href="{{ route('admin.employees') }}" wire:navigate class="dd-link">
                                            <span>Employees</span>
                                        </a>
                                    @endif
                                    @if($canAdminDepartments)
                                        <a href="{{ route('admin.departments') }}" wire:navigate class="dd-link">
                                            <span>Departments</span>
                                        </a>
                                    @endif
                                    @if($canAdminLocations)
                                        <a href="{{ route('admin.locations') }}" wire:navigate class="dd-link">
                                            <span>Locations</span>
                                        </a>
                                    @endif
                                    <div class="dd-divider"></div>
                                @endif

                                <div class="dd-group-title">Settings</div>
                                <a href="{{ route('admin.company-settings') }}" wire:navigate class="dd-link">
                                    <span>Company Settings</span>
                                </a>
                                @if($canAdminPto)
                                    <a href="{{ route('timeoff.policies') }}" wire:navigate class="dd-link">
                                        <span>Time Off Policies</span>
                                    </a>
                                @endif
                                <a href="{{ route('notification.settings') }}" wire:navigate class="dd-link">
                                    <span>Alerts and Reminders</span>
                                </a>

                                <div class="dd-divider"></div>
                                <a href="{{ route('admin.audit-log') }}" wire:navigate class="dd-link">
                                    <span>Audit Log</span>
                                </a>
                            </div>
                        </div>
                    @endif

                    <!-- 6. Map -->
                    @if(\App\Models\CompanySetting::isPageActive('map') && Auth::user()->canViewMap())
                        <a href="{{ route('map') }}" wire:navigate
                           class="h-14 flex items-center gap-1 px-2.5 transition select-none {{ request()->routeIs('map*') ? 'border-b-[3px] border-[#f26522] text-[#f26522] font-black' : 'border-b-[3px] border-transparent text-gray-700 hover:text-[#005088] font-bold' }}">
                            <span>Map</span>
                        </a>
                    @endif

                    <!-- 7. Super Admin Dedicated Module (Exclusively for Super Admins) -->
                    @if(Auth::user()->isSuperAdmin())
                        <div class="nav-dropdown h-14 flex items-center">
                            <a href="#" class="h-14 flex items-center gap-1 px-2.5 transition select-none {{ request()->routeIs('executive.*') || request()->routeIs('mail.*') || request()->routeIs('notifications.log') || request()->routeIs('webmail') || request()->routeIs('admin.access-management') || request()->routeIs('admin.inactive-records') || request()->routeIs('admin.data-shedding') ? 'border-b-[3px] border-[#f26522] text-[#f26522] font-black' : 'border-b-[3px] border-transparent text-amber-700 hover:text-[#005088] font-bold' }}">
                                <span class="bg-amber-100 text-amber-800 text-[10px] font-extrabold px-1.5 py-0.5 rounded tracking-wide uppercase mr-0.5">Super</span>
                                <span>Admin</span> <span class="text-[8px]">&#9660;</span>
                            </a>
                            <div class="dd-menu w-64 shadow-2xl">
                                <div class="dd-group-title">Executive & Oversight</div>
                                @if(\App\Models\CompanySetting::isPageActive('executive_dashboard'))
                                    <a href="{{ route('executive.dashboard') }}" wire:navigate class="dd-link flex items-center gap-2">
                                        <i class="fa-solid fa-gauge-high text-xs text-indigo-500"></i> Executive Dashboard
                                    </a>
                                @endif
                                @if(\App\Models\CompanySetting::isPageActive('admin_access_management'))
                                    <a href="{{ route('admin.access-management') }}" wire:navigate class="dd-link flex items-center gap-2">
                                        <i class="fa-solid fa-shield-halved text-xs text-emerald-500"></i> Access Management (UAM)
                                    </a>
                                @endif

                                <div class="dd-divider"></div>
                                <div class="dd-group-title">Communications</div>
                                @if(\App\Models\CompanySetting::isPageActive('webmail'))
                                    <a href="{{ route('webmail') }}" wire:navigate class="dd-link flex items-center gap-2">
                                        <i class="fa-solid fa-envelope text-xs text-sky-500"></i> Webmail
                                    </a>
                                @endif
                                @if(\App\Models\CompanySetting::isPageActive('mail_logs'))
                                    <a href="{{ route('mail.logs') }}" wire:navigate class="dd-link flex items-center gap-2">
                                        <i class="fa-solid fa-envelope-open-text text-xs text-purple-500"></i> Mail Delivery Logs
                                    </a>
                                @endif
                                @if(\App\Models\CompanySetting::isPageActive('notifications_log'))
                                    <a href="{{ route('notifications.log') }}" wire:navigate class="dd-link flex items-center gap-2">
                                        <i class="fa-solid fa-bell text-xs text-amber-500"></i> Notifications History Log
                                    </a>
                                @endif
                                <a href="{{ route('notification.settings') }}" wire:navigate class="dd-link flex items-center gap-2">
                                    <i class="fa-solid fa-bell-concierge text-xs text-sky-500"></i> Notification Management
                                </a>

                                <div class="dd-divider"></div>
                                <div class="dd-group-title">Archive & System</div>
                                <a href="{{ route('admin.inactive-records') }}" wire:navigate class="dd-link flex items-center gap-2 text-rose-600 font-semibold">
                                    <i class="fa-solid fa-box-archive text-xs text-rose-500"></i> Inactive Records Hub
                                </a>
                                <a href="{{ route('admin.data-shedding') }}" wire:navigate class="dd-link flex items-center gap-2">
                                    <i class="fa-solid fa-broom-ball text-xs text-rose-500"></i> Data Shedding (Production)
                                </a>
                                <a href="{{ route('admin.company-settings') }}" wire:navigate class="dd-link flex items-center gap-2">
                                    <i class="fa-solid fa-sliders text-xs text-gray-500"></i> Master Configurations
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Right Header Elements (Matches ClockShark layout) --}}
            <div class="flex items-center space-x-2 sm:space-x-3 text-sm h-14">

                <!-- Get the Mobile App link -->
                <a href="https://www.clockshark.com/mobile-app" target="_blank"
                   class="hidden xl:inline-flex items-center text-xs font-semibold text-[#0077c5] hover:text-[#005088] hover:underline mr-1 transition">
                    Get the Mobile App
                </a>

                <!-- Install PWA Button (Shown when installable) -->
                <button x-show="showInstallBtn"
                        @click="if (deferredPrompt) { deferredPrompt.prompt(); deferredPrompt.userChoice.then(() => { deferredPrompt = null; showInstallBtn = false; }); }"
                        class="hidden md:inline-flex items-center gap-1 bg-[#0077c5] hover:bg-[#005088] text-white text-xs px-2.5 py-1 rounded font-semibold shadow-xs transition"
                        title="Install app on your device">
                    <span>&#8681;</span> Install App
                </button>

                <!-- ClockShark "+ New" Quick Action Dropdown -->
                @php
                    $canAddCustomer = \App\Models\CompanySetting::isPageActive('work_customers') && Auth::user()->canEdit('customers');
                    $canAddJob      = \App\Models\CompanySetting::isPageActive('work_jobs') && Auth::user()->canEdit('jobs');
                    $canAddTask     = \App\Models\CompanySetting::isPageActive('work_tasks') && Auth::user()->canEdit('tasks');
                    $canAddQuote    = \App\Models\CompanySetting::isPageActive('work_quotes') && Auth::user()->canEdit('quotes');
                    $canAddInvoice  = \App\Models\CompanySetting::isPageActive('work_invoices') && Auth::user()->canEdit('invoices');
                    $canAddEmployee = \App\Models\CompanySetting::isPageActive('admin_employees') && (Auth::user()->isAdmin() || Auth::user()->isHrManager());
                    $hasAnyNewAction = $canAddCustomer || $canAddJob || $canAddTask || $canAddQuote || $canAddInvoice || $canAddEmployee;
                @endphp
                @if($hasAnyNewAction)
                    <div class="nav-dropdown hidden sm:block">
                        <button type="button" class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-bold text-white bg-[#0077c5] hover:bg-[#005fa3] shadow-xs transition select-none">
                            + New
                        </button>
                        <div class="dd-menu w-44 shadow-xl">
                            @if($canAddCustomer)
                                <a href="{{ route('work.customers') }}" wire:navigate class="dd-link">
                                    <span>Customer</span>
                                </a>
                            @endif
                            @if($canAddJob)
                                <a href="{{ route('work.jobs') }}" wire:navigate class="dd-link">
                                    <span>Job</span>
                                </a>
                            @endif
                            @if($canAddTask)
                                <a href="{{ route('work.tasks') }}" wire:navigate class="dd-link">
                                    <span>Task</span>
                                </a>
                            @endif
                            @if($canAddQuote)
                                <a href="{{ route('work.quotes') }}" wire:navigate class="dd-link">
                                    <span>Quote</span>
                                </a>
                            @endif
                            @if($canAddInvoice)
                                <a href="{{ route('work.invoices') }}" wire:navigate class="dd-link">
                                    <span>Invoice</span>
                                </a>
                            @endif
                            @if($canAddEmployee)
                                <a href="{{ route('admin.employees') }}" wire:navigate class="dd-link">
                                    <span>Employee</span>
                                </a>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- 1. Search Icon -->
                <button @click="showSearchModal = true" type="button"
                        class="p-2 text-gray-500 hover:text-gray-800 rounded-full hover:bg-gray-100 transition focus:outline-none flex items-center justify-center"
                        title="Quick Search (Ctrl + K)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>

                <!-- 2. Notification Bell Livewire Component -->
                <livewire:notification-bell />

                <!-- 3. Help / Support Icon Button -->
                <button @click="showHelpModal = true" type="button"
                        class="p-2 text-gray-500 hover:text-gray-800 rounded-full hover:bg-gray-100 transition focus:outline-none flex items-center justify-center"
                        title="Help & Shortcuts">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </button>

                <!-- 4. User Profile (Avatar circle + Name + Role underneath) -->
                <div class="nav-dropdown">
                    <div class="flex items-center gap-2 cursor-pointer py-1 pl-1 pr-1.5 rounded-full hover:bg-gray-100 transition">
                        <div class="w-8 h-8 rounded-full ring-2 ring-gray-200 overflow-hidden bg-[#005088] flex items-center justify-center font-bold text-xs text-white shadow-2xs">
                            @if(Auth::user()->avatar_url)
                                <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" class="w-full h-full object-cover" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='inline';">
                                <span class="hidden">{{ Auth::user()->initials }}</span>
                            @else
                                <span>{{ Auth::user()->initials }}</span>
                            @endif
                        </div>
                        <div class="text-left hidden sm:block leading-tight">
                            <div class="text-xs font-bold text-gray-800 tracking-tight">{{ Auth::user()->name }}</div>
                            <div class="text-[10px] text-gray-500 font-medium leading-none mt-0.5">
                                {{ Auth::user()->isAdmin() ? 'Super Admin' : (Auth::user()->role ?: 'Employee') }}
                            </div>
                        </div>
                    </div>

                    <div class="dd-menu right-0 left-auto w-64 shadow-2xl">
                        <div class="p-3 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-100">
                            <div class="text-xs font-bold text-gray-900">{{ Auth::user()->name }}</div>
                            <div class="text-[11px] text-gray-500 truncate">{{ Auth::user()->email }}</div>
                            <div class="flex items-center gap-2 mt-1.5">
                                <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold uppercase
                                    {{ Auth::user()->isAdmin() ? 'bg-purple-100 text-purple-800' : (Auth::user()->isHrManager() ? 'bg-indigo-100 text-indigo-800' : (Auth::user()->isManager() ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700')) }}">
                                    {{ Auth::user()->isAdmin() ? 'Super Admin' : Auth::user()->role }}
                                </span>
                                <span class="text-[10px] text-gray-500 font-medium">TZ: {{ Auth::user()->getTimezoneAbbr() }}</span>
                            </div>
                        </div>
                        <a href="{{ route('profile.edit') }}" wire:navigate class="dd-link flex items-center gap-2">
                            <span>⚙️</span> My Account & Configs
                        </a>
                        @if(Auth::user()->canAccessAdmin() || Auth::user()->isManager())
                            <a href="{{ route('notification.settings') }}" wire:navigate class="dd-link flex items-center gap-2">
                                <i class="fa-solid fa-bell-concierge text-xs text-[#005088]"></i> Notification Management
                            </a>
                        @endif
                        <div class="dd-divider"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left dd-link text-red-600 hover:text-red-800 hover:bg-red-50">
                                Sign Out
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Mobile Hamburger Button -->
                <button type="button" @click="mobileDrawerOpen = true"
                        class="md:hidden p-1.5 text-gray-600 hover:text-gray-900 rounded hover:bg-gray-100 focus:outline-none"
                        title="Open Menu">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                    </svg>
                </button>

                <!-- 5. Time Clock Pill (Absolute Right-most on Navbar! Click to open modal) -->
                <livewire:navbar-clock />
            </div>
        </div>
    </div>
</nav>

@if($isModern)
    {{-- Modern Context Ribbon --}}
    <div class="w-full bg-slate-900 border-b border-slate-800 px-4 py-1 flex items-center justify-between text-xs text-slate-300">
        <div class="flex items-center gap-2">
            <span class="font-semibold text-slate-200 capitalize text-[11px]">{{ str_replace(['.', '-', '_'], ' ', request()->route() ? request()->route()->getName() : 'Dashboard') }}</span>
        </div>
        <div class="hidden sm:flex items-center gap-2 text-[10px] text-slate-400 font-medium">
            <i class="fa-solid fa-globe text-slate-500"></i>
            <span>{{ \App\Models\CompanySetting::getServerTimezone() }}</span>
        </div>
    </div>
@endif

{{-- Mobile Slide-Over Navigation Drawer --}}
<div x-show="mobileDrawerOpen"
     x-cloak
     class="fixed inset-0 z-50 overflow-hidden md:hidden"
     aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
    {{-- Backdrop --}}
    <div x-show="mobileDrawerOpen"
         x-transition:enter="ease-in-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in-out duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="mobileDrawerOpen = false"
         class="fixed inset-0 bg-black/60 transition-opacity"></div>

    <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
        <div x-show="mobileDrawerOpen"
             x-transition:enter="transform transition ease-in-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transform transition ease-in-out duration-300"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             class="w-screen max-w-xs bg-white shadow-2xl flex flex-col">

            <!-- Drawer Header -->
            <div class="p-4 bg-[#005088] text-white flex justify-between items-center">
                <div class="flex items-center gap-2">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $companyName }}" class="h-7 max-w-[120px] object-contain rounded bg-white/20 p-0.5" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex';">
                        <div class="w-7 h-7 rounded bg-amber-500 items-center justify-center text-white font-bold text-xs hidden">
                            &#128337;
                        </div>
                    @else
                        <div class="w-7 h-7 rounded bg-amber-500 flex items-center justify-center text-white font-bold text-xs">
                            &#128337;
                        </div>
                    @endif
                    <span class="font-bold text-base">{{ $companyName }}</span>
                </div>
                <button @click="mobileDrawerOpen = false" class="text-white hover:text-gray-200 text-2xl leading-none">&times;</button>
            </div>

            <!-- User Info Card -->
            <div class="p-4 bg-gray-50 border-b border-gray-200 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-100 text-[#005088] flex items-center justify-center font-bold text-sm overflow-hidden shrink-0 ring-1 ring-sky-400/40">
                    @if(Auth::user()->avatar_url)
                        <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" class="w-full h-full object-cover" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='inline';">
                        <span class="hidden">{{ Auth::user()->initials }}</span>
                    @else
                        {{ Auth::user()->initials }}
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <div class="font-semibold text-xs text-gray-900 truncate">{{ Auth::user()->name }}</div>
                    <span class="inline-block px-1.5 py-0.2 rounded text-[9px] font-bold uppercase
                        {{ Auth::user()->isAdmin() ? 'bg-purple-100 text-purple-800' : (Auth::user()->isHrManager() ? 'bg-indigo-100 text-indigo-800' : (Auth::user()->isManager() ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700')) }}">
                        {{ Auth::user()->role }}
                    </span>
                </div>
            </div>

            <!-- Drawer Links List -->
            <div class="flex-1 overflow-y-auto p-4 space-y-4 text-xs font-semibold uppercase tracking-wider text-gray-600">
                <!-- Clock -->
                @if(\App\Models\CompanySetting::isPageActive('dashboard'))
                    <div>
                        <a href="{{ route('dashboard') }}" wire:navigate @click="mobileDrawerOpen = false"
                           class="flex items-center gap-3 py-2 px-3 rounded hover:bg-blue-50 hover:text-[#005a9c] text-gray-800">
                            <span class="text-base">&#9200;</span> My Time Clock
                        </a>
                    </div>
                @endif

                <!-- Timesheets -->
                @if($showTimesheetsView || $showTimesheetsApprove)
                    <div class="space-y-1">
                        <div class="text-[10px] text-gray-400 px-3 uppercase">Time Tracking</div>
                        @if($showTimesheetsView)
                            <a href="{{ route('timesheets.view') }}" wire:navigate @click="mobileDrawerOpen = false"
                               class="flex items-center gap-3 py-2 px-3 rounded hover:bg-blue-50 hover:text-[#005a9c] text-gray-800">
                                <span>&#128197;</span> View Timesheets
                            </a>
                        @endif
                        @if($showTimesheetsApprove)
                            <a href="{{ route('timesheets.approve') }}" wire:navigate @click="mobileDrawerOpen = false"
                               class="flex items-center gap-3 py-2 px-3 rounded hover:bg-blue-50 hover:text-[#005a9c] text-gray-800">
                                <span>&#10003;</span> Approve Timesheets
                            </a>
                        @endif
                    </div>
                @endif

                <!-- Time Off -->
                @if($showTimeoffRequests || $showTimeoffPolicies)
                    <div class="space-y-1">
                        <div class="text-[10px] text-gray-400 px-3 uppercase">Time Off</div>
                        @if($showTimeoffRequests)
                            <a href="{{ route('timeoff.requests') }}" wire:navigate @click="mobileDrawerOpen = false"
                               class="flex items-center gap-3 py-2 px-3 rounded hover:bg-blue-50 hover:text-[#005a9c] text-gray-800">
                                <span>&#9992;</span> Time Off Requests
                            </a>
                        @endif
                        @if($showTimeoffPolicies)
                            <a href="{{ route('timeoff.policies') }}" wire:navigate @click="mobileDrawerOpen = false"
                               class="flex items-center gap-3 py-2 px-3 rounded hover:bg-blue-50 hover:text-[#005a9c] text-gray-800">
                                <span>&#128220;</span> PTO Policies
                            </a>
                        @endif
                    </div>
                @endif

                <!-- Schedules -->
                @if(\App\Models\CompanySetting::isPageActive('schedules') && Auth::user()->canAccessSchedules())
                    <div>
                        <a href="{{ route('schedules') }}" wire:navigate @click="mobileDrawerOpen = false"
                           class="flex items-center gap-3 py-2 px-3 rounded hover:bg-blue-50 hover:text-[#005a9c] text-gray-800">
                            <span>&#128197;</span> Schedules
                        </a>
                    </div>
                @endif

                <!-- Work -->
                @if($hasWorkAccess)
                    <div class="space-y-1">
                        <div class="text-[10px] text-gray-400 px-3 uppercase">Work</div>
                        @if($canCustomers)
                            <a href="{{ route('work.customers') }}" wire:navigate @click="mobileDrawerOpen = false"
                               class="flex items-center gap-3 py-2 px-3 rounded hover:bg-blue-50 hover:text-[#005a9c] text-gray-800">
                                <span>&#128101;</span> Customers
                            </a>
                        @endif
                        @if($canJobs)
                            <a href="{{ route('work.jobs') }}" wire:navigate @click="mobileDrawerOpen = false"
                               class="flex items-center gap-3 py-2 px-3 rounded hover:bg-blue-50 hover:text-[#005a9c] text-gray-800">
                                <span>&#128188;</span> Jobs
                            </a>
                        @endif
                        @if($canTasks)
                            <a href="{{ route('work.tasks') }}" wire:navigate @click="mobileDrawerOpen = false"
                               class="flex items-center gap-3 py-2 px-3 rounded hover:bg-blue-50 hover:text-[#005a9c] text-gray-800">
                                <span>&#9745;</span> Tasks
                            </a>
                        @endif
                        @if($canQuotes)
                            <a href="{{ route('work.quotes') }}" wire:navigate @click="mobileDrawerOpen = false"
                               class="flex items-center gap-3 py-2 px-3 rounded hover:bg-blue-50 hover:text-[#005a9c] text-gray-800">
                                <span>&#128221;</span> Quotes
                            </a>
                        @endif
                        @if($canInvoices)
                            <a href="{{ route('work.invoices') }}" wire:navigate @click="mobileDrawerOpen = false"
                               class="flex items-center gap-3 py-2 px-3 rounded hover:bg-blue-50 hover:text-[#005a9c] text-gray-800">
                                <span>&#128176;</span> Invoices
                            </a>
                        @endif
                    </div>
                @endif

                <!-- Reports -->
                @if(\App\Models\CompanySetting::isPageActive('reports') && Auth::user()->canViewReports())
                    <div>
                        <a href="{{ route('reports') }}" wire:navigate @click="mobileDrawerOpen = false"
                           class="flex items-center gap-3 py-2 px-3 rounded hover:bg-blue-50 hover:text-[#005a9c] text-gray-800">
                            <span>&#128202;</span> Reports
                        </a>
                    </div>
                @endif

                <!-- Map -->
                @if(\App\Models\CompanySetting::isPageActive('map') && Auth::user()->canViewMap())
                    <div>
                        <a href="{{ route('map') }}" wire:navigate @click="mobileDrawerOpen = false"
                           class="flex items-center gap-3 py-2 px-3 rounded hover:bg-blue-50 hover:text-[#005a9c] text-gray-800">
                            <span>&#127758;</span> GPS Map
                        </a>
                    </div>
                @endif

                <!-- Admin Management -->
                @if(Auth::user()->canAccessAdmin())
                    @php
                        $canMobileAdminEmployees = \App\Models\CompanySetting::isPageActive('admin_employees');
                        $canMobileAdminDepartments = \App\Models\CompanySetting::isPageActive('admin_departments');
                        $canMobileAdminLocations = \App\Models\CompanySetting::isPageActive('admin_locations');
                    @endphp
                    <div class="space-y-1">
                        <div class="text-[10px] text-gray-400 px-3 uppercase">Administration</div>
                        @if($canMobileAdminEmployees)
                            <a href="{{ route('admin.employees') }}" wire:navigate @click="mobileDrawerOpen = false"
                               class="flex items-center gap-3 py-2 px-3 rounded hover:bg-blue-50 hover:text-[#005a9c] text-gray-800">
                                <span>&#128100;</span> Employees
                            </a>
                        @endif
                        @if($canMobileAdminDepartments)
                            <a href="{{ route('admin.departments') }}" wire:navigate @click="mobileDrawerOpen = false"
                               class="flex items-center gap-3 py-2 px-3 rounded hover:bg-blue-50 hover:text-[#005a9c] text-gray-800">
                                <span>&#127970;</span> Departments
                            </a>
                        @endif
                        @if($canMobileAdminLocations)
                            <a href="{{ route('admin.locations') }}" wire:navigate @click="mobileDrawerOpen = false"
                               class="flex items-center gap-3 py-2 px-3 rounded hover:bg-blue-50 hover:text-[#005a9c] text-gray-800">
                                <span>&#128205;</span> Locations
                            </a>
                        @endif
                        <a href="{{ route('admin.company-settings') }}" wire:navigate @click="mobileDrawerOpen = false"
                           class="flex items-center gap-3 py-2 px-3 rounded hover:bg-blue-50 hover:text-[#005a9c] text-gray-800">
                            <span>⚙️</span> Company Settings
                        </a>
                        @if(\App\Models\CompanySetting::isPageActive('admin_audit_log'))
                            <a href="{{ route('admin.audit-log') }}" wire:navigate @click="mobileDrawerOpen = false"
                               class="flex items-center gap-3 py-2 px-3 rounded hover:bg-blue-50 hover:text-[#005a9c] text-gray-800">
                                <span>📋</span> Audit Log
                            </a>
                        @endif
                    </div>
                @endif

                <!-- Super Admin Dedicated Module (Mobile - Strictly for Super Admins) -->
                @if(Auth::user()->isSuperAdmin())
                    <div class="space-y-1 border-t border-amber-200/50 pt-2">
                        <div class="text-[10px] text-amber-600 font-extrabold px-3 uppercase tracking-wider flex items-center gap-1">
                            <span class="bg-amber-100 text-amber-800 px-1 rounded text-[9px]">Super</span> Admin Only
                        </div>
                        @if(\App\Models\CompanySetting::isPageActive('executive_dashboard'))
                            <a href="{{ route('executive.dashboard') }}" wire:navigate @click="mobileDrawerOpen = false"
                               class="flex items-center gap-3 py-2 px-3 rounded hover:bg-amber-50 hover:text-amber-800 text-gray-800">
                                <i class="fa-solid fa-gauge-high text-xs text-indigo-500"></i> Executive Dashboard
                            </a>
                        @endif
                        @if(\App\Models\CompanySetting::isPageActive('admin_access_management'))
                            <a href="{{ route('admin.access-management') }}" wire:navigate @click="mobileDrawerOpen = false"
                               class="flex items-center gap-3 py-2 px-3 rounded hover:bg-amber-50 hover:text-amber-800 text-gray-800">
                                <i class="fa-solid fa-shield-halved text-xs text-emerald-500"></i> Access Management (UAM)
                            </a>
                        @endif
                        @if(\App\Models\CompanySetting::isPageActive('webmail'))
                            <a href="{{ route('webmail') }}" wire:navigate @click="mobileDrawerOpen = false"
                               class="flex items-center gap-3 py-2 px-3 rounded hover:bg-amber-50 hover:text-amber-800 text-gray-800">
                                <i class="fa-solid fa-envelope text-xs text-sky-500"></i> Webmail
                            </a>
                        @endif
                        @if(\App\Models\CompanySetting::isPageActive('mail_logs'))
                            <a href="{{ route('mail.logs') }}" wire:navigate @click="mobileDrawerOpen = false"
                               class="flex items-center gap-3 py-2 px-3 rounded hover:bg-amber-50 hover:text-amber-800 text-gray-800">
                                <i class="fa-solid fa-envelope-open-text text-xs text-purple-500"></i> Mail Delivery Logs
                            </a>
                        @endif
                        @if(\App\Models\CompanySetting::isPageActive('notifications_log'))
                            <a href="{{ route('notifications.log') }}" wire:navigate @click="mobileDrawerOpen = false"
                               class="flex items-center gap-3 py-2 px-3 rounded hover:bg-amber-50 hover:text-amber-800 text-gray-800">
                                <i class="fa-solid fa-bell text-xs text-amber-500"></i> Notifications History Log
                            </a>
                        @endif
                        <a href="{{ route('notification.settings') }}" wire:navigate @click="mobileDrawerOpen = false"
                           class="flex items-center gap-3 py-2 px-3 rounded hover:bg-amber-50 hover:text-amber-800 text-gray-800">
                            <i class="fa-solid fa-bell-concierge text-xs text-sky-500"></i> Notification Management
                        </a>
                        <a href="{{ route('admin.inactive-records') }}" wire:navigate @click="mobileDrawerOpen = false"
                           class="flex items-center gap-3 py-2 px-3 rounded hover:bg-amber-50 hover:text-rose-700 text-rose-600 font-semibold">
                            <i class="fa-solid fa-box-archive text-xs text-rose-500"></i> Inactive Records Hub
                        </a>
                        <a href="{{ route('admin.data-shedding') }}" wire:navigate @click="mobileDrawerOpen = false"
                           class="flex items-center gap-3 py-2 px-3 rounded hover:bg-amber-50 hover:text-rose-700 text-rose-600 font-semibold">
                            <i class="fa-solid fa-broom-ball text-xs text-rose-500"></i> Data Shedding (Production)
                        </a>
                    </div>
                @endif

                <div class="pt-2 border-t border-gray-100">
                    <a href="{{ route('profile.edit') }}" wire:navigate @click="mobileDrawerOpen = false"
                       class="flex items-center gap-3 py-2 px-3 rounded hover:bg-blue-50 hover:text-[#005a9c] text-gray-800">
                        <span>&#9881;</span> Profile Settings
                    </a>

                    <form method="POST" action="{{ route('logout') }}" class="mt-2">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-3 py-2 px-3 rounded text-red-600 hover:bg-red-50 text-left font-semibold">
                            <span>&#128682;</span> Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Main Page Content Container --}}
<main class="w-full flex-1 px-3 sm:px-4 py-4 sm:py-6 pb-20 md:pb-6">
    {{ $slot ?? '' }}
    @yield('content')
</main>

{{-- Global Interactive Time Log Detail Modal --}}
<livewire:time-log-detail />

{{-- ClockShark or Modern Floating Island Mobile Bottom Navigation Bar --}}
<nav class="md:hidden fixed bottom-0 left-0 right-0 z-30 {{ $isModern ? 'bottom-3 left-3 right-3 rounded-2xl bg-slate-900/95 backdrop-blur-xl border border-slate-700/80 shadow-2xl mx-3' : 'bg-white border-t border-gray-200 shadow-lg safe-bottom' }}">
    <div class="flex items-center justify-around h-14 text-center {{ $isModern ? 'px-2' : '' }}">
        <!-- 1. Clock -->
        @if(\App\Models\CompanySetting::isPageActive('dashboard'))
            <a href="{{ route('dashboard') }}" wire:navigate
               class="flex-1 flex flex-col items-center justify-center py-1 {{ request()->routeIs('dashboard') ? ($isModern ? 'text-sky-400 font-bold scale-105' : 'text-[#005a9c] font-bold') : ($isModern ? 'text-slate-400 hover:text-slate-200' : 'text-gray-500 hover:text-gray-800') }} transition">
                <span class="text-base leading-none">⏱️</span>
                <span class="text-[10px] mt-0.5">Clock</span>
                @if($isModern && request()->routeIs('dashboard'))
                    <span class="h-1 w-1 rounded-full bg-sky-400 mt-0.5"></span>
                @endif
            </a>
        @endif

        <!-- 2. Timesheets -->
        @if($hasTimeAccess)
            <a href="{{ route('timesheets.view') }}" wire:navigate
               class="flex-1 flex flex-col items-center justify-center py-1 {{ request()->routeIs('timesheets.*') ? ($isModern ? 'text-sky-400 font-bold scale-105' : 'text-[#005a9c] font-bold') : ($isModern ? 'text-slate-400 hover:text-slate-200' : 'text-gray-500 hover:text-gray-800') }} transition">
                <span class="text-base leading-none">📅</span>
                <span class="text-[10px] mt-0.5">Time</span>
                @if($isModern && request()->routeIs('timesheets.*'))
                    <span class="h-1 w-1 rounded-full bg-sky-400 mt-0.5"></span>
                @endif
            </a>
        @endif

        <!-- 3. Schedules -->
        @if(\App\Models\CompanySetting::isPageActive('schedules') && Auth::user()->canAccessSchedules())
            <a href="{{ route('schedules') }}" wire:navigate
               class="flex-1 flex flex-col items-center justify-center py-1 {{ request()->routeIs('schedules') ? ($isModern ? 'text-sky-400 font-bold scale-105' : 'text-[#005a9c] font-bold') : ($isModern ? 'text-slate-400 hover:text-slate-200' : 'text-gray-500 hover:text-gray-800') }} transition">
                <span class="text-base leading-none">📋</span>
                <span class="text-[10px] mt-0.5">Schedule</span>
                @if($isModern && request()->routeIs('schedules'))
                    <span class="h-1 w-1 rounded-full bg-sky-400 mt-0.5"></span>
                @endif
            </a>
        @endif

        <!-- 4. Work -->
        @if($hasWorkAccess)
            <a href="{{ route('work.jobs') }}" wire:navigate
               class="flex-1 flex flex-col items-center justify-center py-1 {{ request()->routeIs('work.*') ? ($isModern ? 'text-sky-400 font-bold scale-105' : 'text-[#005a9c] font-bold') : ($isModern ? 'text-slate-400 hover:text-slate-200' : 'text-gray-500 hover:text-gray-800') }} transition">
                <span class="text-base leading-none">💼</span>
                <span class="text-[10px] mt-0.5">Work</span>
                @if($isModern && request()->routeIs('work.*'))
                    <span class="h-1 w-1 rounded-full bg-sky-400 mt-0.5"></span>
                @endif
            </a>
        @endif

        <!-- 5. More (Opens Drawer) -->
        <button type="button" @click="mobileDrawerOpen = true"
                class="flex-1 flex flex-col items-center justify-center py-1 {{ $isModern ? 'text-slate-400 hover:text-slate-200' : 'text-gray-500 hover:text-gray-800' }} focus:outline-none transition">
            <span class="text-base leading-none">☰</span>
            <span class="text-[10px] mt-0.5">More</span>
        </button>
    </div>
</nav>

    {{-- Quick Search Modal (Ctrl + K) --}}
    @php
        $searchUser = Auth::user();
        $allowedSearchItems = [];

        if ($searchUser) {
            // Dashboard / Time Clock - available to all authenticated users if active
            if (\App\Models\CompanySetting::isPageActive('dashboard')) {
                $allowedSearchItems[] = [
                    'title' => 'My Time Clock',
                    'desc' => 'Clock in, clock out, and live shift tracker',
                    'url' => route('dashboard'),
                    'icon' => 'fa-solid fa-clock',
                    'category' => 'Core',
                    'keywords' => 'clock in out punch timer tracker work shift',
                ];
            }

            // Timesheets
            if ($searchUser->canAccessTimesheets() && \App\Models\CompanySetting::isPageActive('timesheets_view')) {
                $allowedSearchItems[] = [
                    'title' => 'Weekly Timesheets',
                    'desc' => 'View hours, logs, overtime, and time entries',
                    'url' => route('timesheets.view'),
                    'icon' => 'fa-solid fa-calendar-week',
                    'category' => 'Time',
                    'keywords' => 'timesheet hours logs weekly entries work timesheets',
                ];
            }

            // Approve Timesheets
            if ($searchUser->canApproveTimesheets() && \App\Models\CompanySetting::isPageActive('timesheets_approve')) {
                $allowedSearchItems[] = [
                    'title' => 'Approve Timesheets',
                    'desc' => 'Review, approve, or reject employee shift logs',
                    'url' => route('timesheets.approve'),
                    'icon' => 'fa-solid fa-circle-check',
                    'category' => 'Management',
                    'keywords' => 'approve review reject timesheets supervisor audit',
                ];
            }

            // Schedules
            if ($searchUser->canAccessSchedules() && \App\Models\CompanySetting::isPageActive('schedules')) {
                $allowedSearchItems[] = [
                    'title' => 'Shift Schedules & Calendar',
                    'desc' => 'Manage rosters, published shifts, and schedules',
                    'url' => route('schedules'),
                    'icon' => 'fa-solid fa-calendar-days',
                    'category' => 'Scheduling',
                    'keywords' => 'schedules calendar shifts roster planning',
                ];
                $allowedSearchItems[] = [
                    'title' => 'Schedule Board',
                    'desc' => 'Interactive board view for day/week scheduling',
                    'url' => route('schedules') . '?tab=board',
                    'icon' => 'fa-solid fa-table-columns',
                    'category' => 'Scheduling',
                    'keywords' => 'schedule board timeline shifts gantt',
                ];
            }

            // Work - Jobs, Tasks, Customers
            if (($searchUser->canView('jobs') || $searchUser->canView('work')) && \App\Models\CompanySetting::isPageActive('work_jobs')) {
                $allowedSearchItems[] = [
                    'title' => 'Work Jobs',
                    'desc' => 'Manage projects, customer job sites, and ongoing jobs',
                    'url' => route('work.jobs'),
                    'icon' => 'fa-solid fa-briefcase',
                    'category' => 'Work',
                    'keywords' => 'jobs projects work sites contracts ongoing',
                ];
            }

            if (($searchUser->canView('customers') || $searchUser->canView('work')) && \App\Models\CompanySetting::isPageActive('work_customers')) {
                $allowedSearchItems[] = [
                    'title' => 'Customers Directory',
                    'desc' => 'Client contacts, billing addresses, and linked jobs',
                    'url' => route('work.customers'),
                    'icon' => 'fa-solid fa-address-book',
                    'category' => 'Work',
                    'keywords' => 'customers clients contacts accounts companies',
                ];
            }

            if (($searchUser->canView('tasks') || $searchUser->canView('work')) && \App\Models\CompanySetting::isPageActive('work_tasks')) {
                $allowedSearchItems[] = [
                    'title' => 'Work Tasks & GPS Geofences',
                    'desc' => 'Configure tasks, hourly billing rates, and coordinates',
                    'url' => route('work.tasks'),
                    'icon' => 'fa-solid fa-list-check',
                    'category' => 'Work',
                    'keywords' => 'tasks gps geofence coordinates billing rate work',
                ];
            }

            if ($searchUser->canView('invoices') && \App\Models\CompanySetting::isPageActive('work_invoices')) {
                $allowedSearchItems[] = [
                    'title' => 'Invoices',
                    'desc' => 'Manage customer billing, invoices, and payment tracking',
                    'url' => route('work.invoices'),
                    'icon' => 'fa-solid fa-file-invoice-dollar',
                    'category' => 'Billing',
                    'keywords' => 'invoices billing payments money receivables',
                ];
            }

            if ($searchUser->canView('quotes') && \App\Models\CompanySetting::isPageActive('work_quotes')) {
                $allowedSearchItems[] = [
                    'title' => 'Quotes & Estimates',
                    'desc' => 'Create job proposals, customer quotes, and bids',
                    'url' => route('work.quotes'),
                    'icon' => 'fa-solid fa-file-lines',
                    'category' => 'Billing',
                    'keywords' => 'quotes estimates proposals bids pricing',
                ];
            }

            // Employees Directory
            if ($searchUser->canView('employees') && \App\Models\CompanySetting::isPageActive('admin_employees')) {
                $allowedSearchItems[] = [
                    'title' => 'Employee Directory',
                    'desc' => 'Manage staff roster, avatars, pay rates, and status',
                    'url' => route('admin.employees'),
                    'icon' => 'fa-solid fa-users',
                    'category' => 'Staff',
                    'keywords' => 'employees staff users workers people profiles',
                ];
            }

            // Departments
            if ($searchUser->canView('departments') && \App\Models\CompanySetting::isPageActive('admin_departments')) {
                $allowedSearchItems[] = [
                    'title' => 'Company Departments',
                    'desc' => 'Organize teams, departments, and branch units',
                    'url' => route('admin.departments'),
                    'icon' => 'fa-solid fa-building',
                    'category' => 'Staff',
                    'keywords' => 'departments teams units divisions groups',
                ];
            }

            // Franchise Locations
            if (($searchUser->canView('locations') || $searchUser->canViewMap()) && (\App\Models\CompanySetting::isPageActive('admin_locations') || \App\Models\CompanySetting::isPageActive('map'))) {
                $allowedSearchItems[] = [
                    'title' => 'Franchise Locations & Live Map',
                    'desc' => 'Franchise buildings, presets, GPS pins, and map',
                    'url' => route('map'),
                    'icon' => 'fa-solid fa-map-location-dot',
                    'category' => 'Locations',
                    'keywords' => 'locations map gps franchise branch buildings pins',
                ];
            }

            // Reports & Analytics
            if ($searchUser->canViewReports() && \App\Models\CompanySetting::isPageActive('reports')) {
                $allowedSearchItems[] = [
                    'title' => 'Reports & Analytics',
                    'desc' => 'Payroll summaries, attendance audits, and exports',
                    'url' => route('reports'),
                    'icon' => 'fa-solid fa-chart-pie',
                    'category' => 'Analytics',
                    'keywords' => 'reports analytics attendance payroll export audits data',
                ];
            }

            // Executive Dashboard
            if ($searchUser->canAccessExecutiveDashboard() && \App\Models\CompanySetting::isPageActive('executive_dashboard')) {
                $allowedSearchItems[] = [
                    'title' => 'Executive Dashboard',
                    'desc' => 'High-level labor costs, productivity metrics, and locked stats',
                    'url' => route('executive.dashboard'),
                    'icon' => 'fa-solid fa-gauge-high',
                    'category' => 'Executive',
                    'keywords' => 'executive dashboard metrics kpi labor costs stats leadership',
                ];
            }

            // In-App Webmail
            if ($searchUser->canView('webmail') && \App\Models\CompanySetting::isPageActive('webmail')) {
                $allowedSearchItems[] = [
                    'title' => 'In-App Webmail',
                    'desc' => 'Personal inbox, compose emails, and manage mail',
                    'url' => route('webmail'),
                    'icon' => 'fa-solid fa-envelope',
                    'category' => 'Communication',
                    'keywords' => 'webmail email inbox messages compose mail',
                ];
            }

            // Mail Delivery Logs
            if ($searchUser->canView('mail_logs') && \App\Models\CompanySetting::isPageActive('mail_logs')) {
                $allowedSearchItems[] = [
                    'title' => 'Mail Delivery Logs',
                    'desc' => 'Audit outgoing mail delivery records and retry fails',
                    'url' => route('mail.logs'),
                    'icon' => 'fa-solid fa-envelope-open-text',
                    'category' => 'Audit',
                    'keywords' => 'mail logs smtp delivery audit outgoing fails',
                ];
            }

            // Notifications Audit Log
            if ($searchUser->canView('notifications_log') && \App\Models\CompanySetting::isPageActive('notifications_log')) {
                $allowedSearchItems[] = [
                    'title' => 'Notifications Audit Log',
                    'desc' => 'System notifications history and archived alerts',
                    'url' => route('notifications.log'),
                    'icon' => 'fa-solid fa-bell',
                    'category' => 'Audit',
                    'keywords' => 'notifications alerts log history archive unread',
                ];
            }

            // User Access Management (UAM)
            if ($searchUser->canAccessAdmin() && \App\Models\CompanySetting::isPageActive('admin_access_management')) {
                $allowedSearchItems[] = [
                    'title' => 'User Access Management (UAM)',
                    'desc' => 'Configure role-based permissions matrix and custom roles',
                    'url' => route('admin.access-management'),
                    'icon' => 'fa-solid fa-user-shield',
                    'category' => 'Security',
                    'keywords' => 'uam access management permissions security roles rbac',
                ];
            }

            // Company Settings
            if ($searchUser->canAccessAdmin() || $searchUser->canView('company_settings')) {
                $allowedSearchItems[] = [
                    'title' => 'Company System Settings',
                    'desc' => 'Manage logo, themes, fonts, geofences, and server mailers',
                    'url' => route('admin.company-settings'),
                    'icon' => 'fa-solid fa-sliders',
                    'category' => 'Administration',
                    'keywords' => 'company settings branding logo fonts smtp geofence timezone',
                ];
            }

            // Personal Configs & Profile
            $allowedSearchItems[] = [
                'title' => 'Personal Configs & Typography',
                'desc' => 'Appearance themes, font preview dropdown, and timezone',
                'url' => route('profile.edit'),
                'icon' => 'fa-solid fa-gear',
                'category' => 'Personal',
                'keywords' => 'profile personal configs timezone theme font passkey webauthn password',
            ];
        }
    @endphp

    <div x-show="showSearchModal" x-cloak
         x-data="{
             searchQuery: '',
             allItems: {{ \Illuminate\Support\Js::from($allowedSearchItems) }},
             filteredItems() {
                 if (!this.searchQuery.trim()) return this.allItems;
                 const q = this.searchQuery.toLowerCase();
                 return this.allItems.filter(item =>
                     item.title.toLowerCase().includes(q) ||
                     item.desc.toLowerCase().includes(q) ||
                     item.category.toLowerCase().includes(q) ||
                     item.keywords.toLowerCase().includes(q)
                 );
             }
         }"
         class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="showSearchModal = false"></div>
        <div class="flex min-h-full items-start justify-center p-4 pt-16 sm:p-6 sm:pt-24 text-center">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all w-full max-w-lg border border-gray-100"
                 @click.stop>
                <div class="flex items-center border-b border-gray-200 px-4 py-3 bg-gray-50">
                    <svg class="w-5 h-5 text-gray-400 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" x-model="searchQuery" x-ref="searchInput"
                           x-init="$watch('showSearchModal', val => { if(val) { searchQuery = ''; setTimeout(() => $refs.searchInput.focus(), 100); } })"
                           placeholder="Search authorized features, pages, modules..."
                           class="w-full bg-transparent border-0 text-sm focus:outline-none focus:ring-0 text-gray-900 placeholder-gray-400">
                    <kbd class="hidden sm:inline-block px-2 py-0.5 text-[10px] font-semibold text-gray-500 bg-gray-200 rounded border border-gray-300">ESC</kbd>
                </div>

                <div class="max-h-80 overflow-y-auto p-2 divide-y divide-gray-50 text-xs">
                    <div class="p-2 flex items-center justify-between text-[10px] text-gray-400 uppercase tracking-wider font-bold">
                        <span>Authorized Options (<span x-text="filteredItems().length"></span>)</span>
                        <span class="text-emerald-700 bg-emerald-50 px-1.5 py-0.2 rounded font-semibold text-[9px] border border-emerald-200">Permission Scoped</span>
                    </div>

                    <template x-for="item in filteredItems()" :key="item.url + item.title">
                        <a :href="item.url" wire:navigate @click="showSearchModal = false"
                           class="flex items-center gap-3 p-2.5 rounded-lg hover:bg-blue-50 text-gray-800 transition group">
                            <div class="w-7 h-7 rounded-lg bg-gray-100 group-hover:bg-blue-100 group-hover:text-[#005a9c] text-gray-600 flex items-center justify-center shrink-0 transition">
                                <i :class="item.icon" class="text-xs"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-gray-900 flex items-center gap-2">
                                    <span x-text="item.title"></span>
                                    <span class="text-[9px] font-semibold px-1.5 py-0.2 rounded bg-gray-100 text-gray-600 group-hover:bg-blue-100 group-hover:text-[#005a9c]" x-text="item.category"></span>
                                </div>
                                <div class="text-[11px] text-gray-500 truncate" x-text="item.desc"></div>
                            </div>
                            <i class="fa-solid fa-arrow-right text-[10px] text-gray-300 group-hover:text-[#005a9c] transition"></i>
                        </a>
                    </template>

                    <div x-show="filteredItems().length === 0" class="p-6 text-center text-gray-400">
                        <i class="fa-solid fa-ban text-2xl block mb-1"></i>
                        <span>No authorized features matching "<span x-text="searchQuery" class="font-semibold text-gray-600"></span>"</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Help & Shortcuts Modal --}}
    <div x-show="showHelpModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="showHelpModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4 text-center">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all w-full max-w-md border border-gray-100 p-6"
                 @click.stop>
                <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                    <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                        <span class="text-sky-600">&#9432;</span> ClockVerse Help & Support
                    </h3>
                    <button @click="showHelpModal = false" class="text-gray-400 hover:text-gray-600 text-lg font-bold">&times;</button>
                </div>
                <div class="mt-4 space-y-3 text-xs text-gray-600">
                    <div>
                        <div class="font-bold text-gray-800">Timezone & Clock</div>
                        <p class="mt-0.5">The top navbar clock ticks in your configured timezone (<strong>{{ Auth::user()->getTimezone() }}</strong>). You can change your timezone in <em>Profile & Settings &rarr; Personal Configs</em>.</p>
                    </div>
                    <div>
                        <div class="font-bold text-gray-800">Unusual Shift Activity & Location</div>
                        <p class="mt-0.5">Entries logged beyond scheduled shift hours or outside authorized GPS geofences are highlighted with warning indicators (⚠️) and reported to management.</p>
                    </div>
                    <div>
                        <div class="font-bold text-gray-800">Keyboard Shortcuts</div>
                        <div class="mt-1 flex items-center justify-between bg-gray-50 p-2 rounded">
                            <span>Open Quick Search</span>
                            <kbd class="px-2 py-0.5 bg-gray-200 rounded font-semibold text-[10px]">Ctrl + K</kbd>
                        </div>
                    </div>
                </div>
                <div class="mt-6 text-right">
                    <button @click="showHelpModal = false" class="px-4 py-2 bg-[#005a9c] hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow">
                        Got it
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Global Bootstrap Alert Notifications (Reusable Component fixed at Top-Right) --}}
    <x-toast-alerts />


    {{-- Global Alert Modal Dialog (Transforms window.alert into beautiful modal with auto-dismiss fallback) --}}
    <div x-data="{
             open: false,
             title: 'Notice',
             message: '',
             buttonText: 'OK',
             onClose: null,
             _timer: null,
             show(options) {
                 if (window._isNavigating) return;
                 if (!options) return;
                 let msg = typeof options === 'string' ? options : (options.message || '');
                 if (!msg || typeof msg !== 'string' || !msg.trim()) {
                     return; // NEVER show an empty or blank notice modal
                 }
                 if (typeof options === 'string') {
                     this.message = msg.trim();
                     this.title = 'Notice';
                     this.buttonText = 'OK';
                     this.onClose = null;
                 } else {
                     this.title = options.title || 'Notice';
                     this.message = msg.trim();
                     this.buttonText = options.buttonText || 'OK';
                     this.onClose = typeof options.onClose === 'function' ? options.onClose : null;
                 }
                 this.open = true;
                 if (this._timer) clearTimeout(this._timer);
                 this._timer = setTimeout(() => {
                     if (this.open) this.close();
                 }, 6000);
             },
             close() {
                 if (this._timer) {
                     clearTimeout(this._timer);
                     this._timer = null;
                 }
                 this.open = false;
                 if (this.onClose) {
                     let fn = this.onClose;
                     this.onClose = null;
                     fn();
                 }
             }
         }"
         @alert-modal.window="show($event.detail)"
         @close-all-modals.window="open = false; onClose = null"
         @keydown.escape.window="close()"
         x-init="
             window.alertModal = (options, title) => {
                 if (window._isNavigating) return;
                 if (!options) return;
                 if (typeof options === 'string') {
                     if (!options.trim()) return;
                     show({ message: options.trim(), title: title || 'Notice' });
                 } else {
                     if (!options.message || typeof options.message !== 'string' || !options.message.trim()) return;
                     show(options);
                 }
             };
         "
         x-show="open"
         x-cloak
         class="fixed inset-0 z-[10001] flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
         style="display: none;">
        <div @click.outside="close()" class="bg-white rounded-xl shadow-2xl max-w-sm w-full p-5 space-y-4 border border-gray-100 transform transition-all">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 bg-blue-100 text-cs-blue">
                    <span class="text-xl">ℹ️</span>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-bold text-gray-900" x-text="title"></h3>
                    <p class="text-xs text-gray-600 mt-1 leading-relaxed whitespace-pre-line" x-text="message"></p>
                </div>
            </div>
            <div class="flex justify-end pt-2 border-t border-gray-100">
                <button type="button" @click="close" class="px-4 py-1.5 rounded-lg text-xs font-bold text-white bg-cs-blue hover:bg-blue-800 shadow-xs transition" x-text="buttonText"></button>
            </div>
        </div>
    </div>

    {{-- Global Confirmation Modal Dialog (No browser confirm/prompt dialogs) --}}
    <div x-data="{
             open: false,
             title: 'Please Confirm',
             message: '',
             confirmText: 'Confirm',
             cancelText: 'Cancel',
             isDestructive: false,
             isPrompt: false,
             promptInput: '',
             promptPlaceholder: '',
             onConfirm: null,
             onCancel: null,
             show(options) {
                 if (window._isNavigating) return;
                 if (!options) return;
                 let msg = typeof options === 'string' ? options : (options.message || '');
                 if (!msg || typeof msg !== 'string' || !msg.trim()) {
                     return; // NEVER show an empty confirmation modal!
                 }
                 if (typeof options === 'string') {
                     this.title = 'Please Confirm';
                     this.message = msg.trim();
                     this.confirmText = 'Confirm';
                     this.cancelText = 'Cancel';
                     this.isDestructive = false;
                     this.isPrompt = false;
                     this.promptInput = '';
                     this.promptPlaceholder = '';
                     this.onConfirm = null;
                     this.onCancel = null;
                 } else {
                     this.title = options.title || 'Please Confirm';
                     this.message = msg.trim();
                     this.confirmText = options.confirmText || 'Confirm';
                     this.cancelText = options.cancelText || 'Cancel';
                     this.isDestructive = !!options.isDestructive;
                     this.isPrompt = !!options.isPrompt;
                     this.promptInput = options.defaultValue || '';
                     this.promptPlaceholder = options.placeholder || '';
                     this.onConfirm = typeof options.onConfirm === 'function' ? options.onConfirm : null;
                     this.onCancel = typeof options.onCancel === 'function' ? options.onCancel : null;
                 }
                 this.open = true;
             },
             proceed() {
                 this.open = false;
                 if (this.onConfirm) {
                     let fn = this.onConfirm;
                     let val = this.isPrompt ? this.promptInput : true;
                     this.onConfirm = null;
                     this.onCancel = null;
                     fn(val);
                 }
             },
             cancel() {
                 this.open = false;
                 if (this.onCancel) {
                     let fn = this.onCancel;
                     this.onCancel = null;
                     fn();
                 }
                 this.onConfirm = null;
             }
         }"
         @confirm-modal.window="show($event.detail)"
         @close-all-modals.window="open = false; onConfirm = null; onCancel = null"
         @keydown.escape.window="cancel()"
         x-init="
             window.confirmModal = (options) => {
                 if (window._isNavigating) return;
                 if (!options) return;
                 let msg = typeof options === 'string' ? options : (options.message || '');
                 if (!msg || typeof msg !== 'string' || !msg.trim()) return;
                 show(options);
             };
             window.promptModal = (options) => {
                 if (window._isNavigating) return;
                 if (!options) return;
                 let msg = typeof options === 'string' ? options : (options.message || '');
                 if (!msg || typeof msg !== 'string' || !msg.trim()) return;
                 let opts = typeof options === 'string' ? { message: options } : Object.assign({}, options);
                 opts.isPrompt = true;
                 show(opts);
             };
         "
         x-show="open"
         x-cloak
         class="fixed inset-0 z-[10000] flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
         style="display: none;">
        <div @click.outside="cancel()" class="bg-white rounded-xl shadow-2xl max-w-sm w-full p-5 space-y-4 border border-gray-100 transform transition-all">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0"
                     :class="isDestructive ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-cs-blue'">
                    <span class="text-xl" x-text="isDestructive ? '⚠️' : (isPrompt ? '✏️' : '❓')"></span>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-bold text-gray-900" x-text="title"></h3>
                    <p class="text-xs text-gray-600 mt-1 leading-relaxed whitespace-pre-line" x-text="message"></p>
                    <template x-if="isPrompt">
                        <div class="mt-3">
                            <input type="text" x-model="promptInput"
                                   :placeholder="promptPlaceholder"
                                   @keydown.enter.prevent="proceed()"
                                   class="w-full border border-gray-300 rounded-lg p-2 text-xs text-gray-800 focus:ring-1 focus:ring-cs-blue focus:outline-none bg-white">
                        </div>
                    </template>
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                <button type="button" @click="cancel" class="px-3.5 py-1.5 rounded-lg border border-gray-300 text-xs font-semibold text-gray-700 hover:bg-gray-100 transition" x-text="cancelText"></button>
                <button type="button" @click="proceed" class="px-4 py-1.5 rounded-lg text-xs font-bold text-white shadow-xs transition"
                        :class="isDestructive ? 'bg-red-600 hover:bg-red-700' : 'bg-cs-blue hover:bg-blue-800'"
                        x-text="confirmText"></button>
            </div>
        </div>
    </div>

@livewireScripts
<script>
    window._isNavigating = false;

    // Reset modals on wire:navigate and standard navigation to prevent unwanted prompts
    document.addEventListener('livewire:navigating', () => {
        window._isNavigating = true;
        window.dispatchEvent(new CustomEvent('close-all-modals'));
    });

    document.addEventListener('livewire:navigated', () => {
        window.dispatchEvent(new CustomEvent('close-all-modals'));
        window._isNavigating = false;
    });

    window.addEventListener('beforeunload', () => {
        window._isNavigating = true;
        window.dispatchEvent(new CustomEvent('close-all-modals'));
    });

    // Intercept clicks on any element with [wire:confirm] in capturing phase
    // This guarantees Livewire 3 uses our global confirmation modal instead of browser native confirm()
    document.addEventListener('click', (e) => {
        let el = e.target.closest('[wire\\:confirm]');
        if (!el) return;
        let message = el.getAttribute('wire:confirm');
        if (!message) return;

        el.__livewire_confirm = (action, instead) => {
            const msgLower = (message || '').toLowerCase();
            const isDestructive = msgLower.includes('delete')
                               || msgLower.includes('remove')
                               || msgLower.includes('reject')
                               || msgLower.includes('withdraw')
                               || msgLower.includes('cancel');

            window.confirmModal({
                title: isDestructive ? 'Confirm Action' : 'Please Confirm',
                message: message,
                confirmText: isDestructive ? 'Yes, Delete' : 'Confirm',
                cancelText: 'Cancel',
                isDestructive: isDestructive,
                onConfirm: () => {
                    if (typeof action === 'function') action();
                },
                onCancel: () => {
                    if (typeof instead === 'function') instead();
                }
            });
        };
    }, true);

    // Global replacements for native browser dialogs (no native alert/confirm/prompt)
    window.alert = function(msg) {
        if (window._isNavigating || !msg) return;
        if (window.alertModal) {
            window.alertModal(String(msg));
        }
    };

    window.confirm = function(msg) {
        if (!window._isNavigating && msg && window.confirmModal) {
            window.confirmModal({
                title: 'Please Confirm',
                message: String(msg),
                confirmText: 'OK',
                cancelText: 'Cancel'
            });
        }
        return false;
    };

    window.prompt = function(msg, def) {
        if (!window._isNavigating && msg && window.promptModal) {
            window.promptModal({
                message: String(msg),
                defaultValue: def || ''
            });
        }
        return null;
    };

    // Livewire 3 wire:confirm directive fallback
    document.addEventListener('livewire:init', () => {
        if (window.Livewire && typeof window.Livewire.directive === 'function') {
            window.Livewire.directive('confirm', ({ el, directive }) => {
                let message = directive.expression || el.getAttribute('wire:confirm') || 'Are you sure you want to proceed?';

                el.__livewire_confirm = (action, instead) => {
                    const msgLower = (message || '').toLowerCase();
                    const isDestructive = msgLower.includes('delete')
                                       || msgLower.includes('remove')
                                       || msgLower.includes('reject')
                                       || msgLower.includes('withdraw')
                                       || msgLower.includes('cancel');

                    window.confirmModal({
                        title: isDestructive ? 'Confirm Action' : 'Please Confirm',
                        message: message,
                        confirmText: isDestructive ? 'Yes, Delete' : 'Confirm',
                        cancelText: 'Cancel',
                        isDestructive: isDestructive,
                        onConfirm: () => {
                            if (typeof action === 'function') action();
                        },
                        onCancel: () => {
                            if (typeof instead === 'function') instead();
                        }
                    });
                };
            });
        }

        // Handle Page Expired (419) or Unauthorized (401) => redirect to /login
        Livewire.hook('request', ({ fail }) => {
            fail(({ status, preventDefault }) => {
                if (status === 419 || status === 401) {
                    if (preventDefault) preventDefault();
                    window.location.href = '{{ route('login') }}';
                }
            });
        });
    });

    // Native fallback for page expired
    window.addEventListener('error', (event) => {
        if (event && event.message && event.message.includes('419')) {
            window.location.href = '{{ route('login') }}';
        }
    });
</script>
</body>
</html>
