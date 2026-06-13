<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'Super Admin') — EduPay Cameroun</title>

    {{-- Tailwind CSS + config EduPay --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Styles spécifiques admin --}}
    <style>
        :root {
            --ep-navy:   #0B2545;
            --ep-teal:   #0D9E75;
            --ep-teal2:  #0A8562;
            --ep-gold:   #E8A020;
            --ep-red:    #D94040;
        }
    </style>
</head>
<body class="h-full bg-gray-100 font-sans text-gray-900 antialiased">

    {{-- ── Header Super Admin ── --}}
    <header class="bg-[#0B2545] text-white px-6 py-3 flex items-center justify-between shadow-md">
        <div>
            <span class="text-lg font-bold tracking-tight">
                Edu<span class="text-[#5DCAA5]">Pay</span>
            </span>
            <span class="ml-2 text-sm text-white/50">·</span>
            <span class="ml-2 text-sm font-semibold text-[#E8A020]">Super Admin</span>
            <div class="text-xs text-white/40 mt-0.5">Vue globale plateforme · CDC-EDUPAY-CM-2026-001</div>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-xs px-2.5 py-1 rounded-full bg-[#E8A020]/15 text-[#E8A020] border border-[#E8A020]/30 font-medium">
                Admin système
            </span>
            <span class="text-sm text-white/70">
                {{ Auth::guard('admin')->user()->nom_complet }}
            </span>
            <div class="w-9 h-9 rounded-full bg-[#D94040] flex items-center justify-center text-white text-sm font-bold">
                {{ Auth::guard('admin')->user()->initiales }}
            </div>
            <form method="POST" action="{{ route('admin.logout') }}" class="inline">
                @csrf
                <button type="submit"
                    class="text-white/50 hover:text-white text-xs border border-white/20 hover:border-white/40 px-3 py-1.5 rounded-full transition-colors">
                    Déconnexion
                </button>
            </form>
        </div>
    </header>

    {{-- ── Corps principal ── --}}
    <div class="flex min-h-[calc(100vh-64px)]">

        {{-- Sidebar navigation --}}
        <aside class="w-52 shrink-0 bg-white border-r border-gray-200 pt-4 px-3">

            <nav class="space-y-0.5">
                {{-- Vue globale --}}
                <a href="{{ route('admin.dashboard') }}"
                   class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/>
                        <path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>
                    </svg>
                    Vue globale
                </a>

                {{-- Établissements --}}
                @if (Route::has('admin.etablissements.index'))
                <a href="{{ route('admin.etablissements.index') }}"
                   class="sidebar-link {{ request()->routeIs('admin.etablissements.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="7" width="20" height="15"/><polyline points="16 2 12 7 8 2"/>
                    </svg>
                    Établissements
                </a>
                @endif

                {{-- Transactions --}}
                @if (Route::has('admin.transactions.index'))
                <a href="{{ route('admin.transactions.index') }}"
                   class="sidebar-link {{ request()->routeIs('admin.transactions.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                        <line x1="1" y1="10" x2="23" y2="10"/>
                    </svg>
                    Transactions
                </a>
                @endif

                {{-- Commissions --}}
                @if (Route::has('admin.commissions.index'))
                <a href="{{ route('admin.commissions.index') }}"
                   class="sidebar-link {{ request()->routeIs('admin.commissions.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                        <polyline points="17 6 23 6 23 12"/>
                    </svg>
                    Commissions
                </a>
                @endif

                {{-- Logs sécurité --}}
                @if (Route::has('admin.logs.index'))
                <a href="{{ route('admin.logs.index') }}"
                   class="sidebar-link {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    Logs sécurité
                </a>
                @endif

                {{-- Paramètres --}}
                @if (Route::has('admin.parametres.index'))
                <a href="{{ route('admin.parametres.index') }}"
                   class="sidebar-link {{ request()->routeIs('admin.parametres.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
                    </svg>
                    Paramètres sys.
                </a>
                @endif
            </nav>

            {{-- Widget commission active --}}
            <div class="mt-5 bg-[#FEF3DC] rounded-lg p-3 border-l-2 border-[#E8A020]">
                <div class="text-xs font-semibold text-[#8B5E10]">Commission active</div>
                <div class="text-2xl font-bold text-[#663E08] mt-1">
                    {{ number_format(($tauxCommission ?? 0.025) * 100, 1, ',', '') }}%
                </div>
                <div class="text-xs text-[#8B5E10] mt-0.5">par transaction</div>
            </div>
        </aside>

        {{-- Contenu principal --}}
        <main class="flex-1 overflow-y-auto p-6">

            {{-- Notifications flash --}}
            @if (session('success'))
                <div class="mb-4 flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 text-sm px-4 py-3 rounded-lg">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 text-sm px-4 py-3 rounded-lg">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    {{ session('error') }}
                </div>
            @endif

            @if (session('info'))
                <div class="mb-4 flex items-center gap-3 bg-blue-50 border border-blue-200 text-blue-800 text-sm px-4 py-3 rounded-lg">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
                    </svg>
                    {{ session('info') }}
                </div>
            @endif

            {{-- Contenu de la page --}}
            @yield('content')
        </main>
    </div>

    {{-- CSS sidebar-link utilitaire --}}
    <style>
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 8px 10px;
            border-radius: 8px;
            font-size: 13px;
            color: #555;
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
        }
        .sidebar-link:hover:not(.active) { background: #f0f0f0; }
        .sidebar-link.active {
            background: #E0F5EE;
            color: #085041;
            font-weight: 600;
        }
        .sidebar-link.active svg { stroke: #0D9E75; }
    </style>

</body>
</html>