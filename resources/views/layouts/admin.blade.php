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
        /* ══ TOGGLE SWITCH REACTIF — CSS pur, reagit instantanement au clic ══ */
        .ep-toggle { position:relative; display:inline-block; width:44px; height:24px; cursor:pointer; flex-shrink:0; }
        .ep-toggle input { opacity:0; width:0; height:0; position:absolute; }
        .ep-toggle-track { position:absolute; inset:0; background:#ddd; border-radius:24px; transition:background .2s; }
        .ep-toggle-thumb { position:absolute; height:18px; width:18px; left:3px; bottom:3px; background:#fff; border-radius:50%; transition:transform .2s; box-shadow:0 1px 2px rgba(0,0,0,.2); }
        .ep-toggle input:checked ~ .ep-toggle-track { background:#0D9E75; }
        .ep-toggle input:checked ~ .ep-toggle-track .ep-toggle-thumb { transform: translateX(20px); }
        /* ══ TOASTS ══ */
        .toast-wrap{position:fixed;top:18px;right:18px;z-index:9999;display:flex;flex-direction:column;gap:10px;}
        .toast{display:flex;align-items:flex-start;gap:10px;min-width:280px;max-width:360px;padding:13px 16px;border-radius:8px;box-shadow:0 6px 20px rgba(0,0,0,.15);font-size:13px;font-weight:500;animation:toast-in .25s ease-out;}
        .toast.t-success{background:#085041;color:#fff;}
        .toast.t-error{background:#9B2C2C;color:#fff;}
        .toast.t-info{background:#1A4F8A;color:#fff;}
        .toast svg{flex-shrink:0;margin-top:1px;}
        .toast.closing{animation:toast-out .2s ease-in forwards;}
        @keyframes toast-in{from{opacity:0;transform:translateX(30px);}to{opacity:1;transform:translateX(0);}}
        @keyframes toast-out{from{opacity:1;transform:translateX(0);}to{opacity:0;transform:translateX(30px);}}
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
    <div class="flex min-h-[calc(100vh-64px)] admin-body">

        {{-- Sidebar navigation --}}
        <aside class="w-52 shrink-0 bg-white border-r border-gray-200 pt-4 px-3 sidebar">

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


               {{-- Réclamations --}}
                @if (Route::has('admin.reclamations.index'))
                <a href="{{ route('admin.reclamations.index') }}"
                   class="sidebar-link {{ request()->routeIs('admin.reclamations.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                    </svg>
                    Réclamations
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

                {{-- Exports réglementaires --}}
                @if (Route::has('admin.exports.index'))
                <a href="{{ route('admin.exports.index') }}"
                   class="sidebar-link {{ request()->routeIs('admin.exports.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                    </svg>
                    Exports réglementaires
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

            {{-- Contenu de la page --}}
            @yield('content')
        </main>
    </div>

    {{-- Modals injectés par les pages --}}
    @stack('modals')

    {{-- Modals injectés par les pages --}}
    @stack('modals')

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

        /* Responsive overrides for admin content pages */
        .admin-body main .grid.grid-cols-4 {
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)) !important;
        }
        .admin-body main .grid.grid-cols-3 {
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)) !important;
        }
        .admin-body main .grid.grid-cols-2 {
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)) !important;
        }
        .admin-body main .overflow-hidden {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .admin-body main .overflow-hidden table {
            width: 100%;
            min-width: 100%;
            table-layout: auto;
        }
        .admin-body main table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            max-width: 100%;
        }
        .admin-body main th,
        .admin-body main td {
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
        }
        .admin-body main th {
            text-transform: none;
        }
        .admin-body main td {
            overflow: visible;
        }

        @media (max-width: 768px) {
            .admin-body main .overflow-hidden {
                overflow-x: auto;
            }
            .admin-body main .overflow-hidden table {
                min-width: 900px;
                table-layout: auto;
            }
            .admin-body main th,
            .admin-body main td {
                white-space: nowrap;
                word-break: normal;
            }
        }
        .admin-body main img {
            max-width: 100%;
            height: auto;
        }
        .admin-body main .flex.items-center.justify-between,
        .admin-body main .flex.items-center.gap-3,
        .admin-body main .flex.items-center.gap-4,
        .admin-body main .flex.items-center.gap-5,
        .admin-body main .flex.items-center.gap-6 {
            flex-wrap: wrap;
            gap: 0.85rem;
        }
        .admin-body main .flex.items-center.justify-between > * {
            min-width: 0;
        }

        @media (max-width: 1024px) {
            .admin-body { flex-direction: column; }
            .sidebar { width: 100% !important; border-right: none; border-bottom: 1px solid #E5E7EB; padding: 14px 12px; display: flex; flex-wrap: wrap; gap: 10px; }
            .sidebar-link { flex: 1 1 calc(50% - 10px); min-width: 160px; }
        }
        @media (max-width: 768px) {
            .admin-body main .grid.grid-cols-4,
            .admin-body main .grid.grid-cols-3,
            .admin-body main .grid.grid-cols-2 {
                grid-template-columns: 1fr !important;
            }
            .sidebar { padding: 12px 10px; }
            .sidebar-link { flex: 1 1 100%; }
            main { padding: 16px !important; }
            header { flex-wrap: wrap; gap: 12px; align-items: flex-start; }
        }
        @media (max-width: 480px) {
            .sidebar { gap: 8px; }
            .sidebar-link { min-width: 0; }
        }
    </style>


    {{-- Scripts injectés par les pages --}}
    @stack('scripts')

    {{-- Système epModal admin --}}
    <style>
        .ep-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 50;
            background: rgba(0,0,0,0.45);
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .ep-modal-overlay.open { display: flex; }
        .ep-modal {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            animation: epSlideUp .2s ease;
        }
        .ep-modal-sm  { max-width: 420px; }
        .ep-modal-md  { max-width: 560px; }
        .ep-modal-lg  { max-width: 720px; }
        .ep-modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 15px;
            font-weight: 700;
            color: #111;
        }
        .ep-modal-close {
            background: none;
            border: none;
            font-size: 20px;
            color: #999;
            cursor: pointer;
            line-height: 1;
            padding: 0 4px;
        }
        .ep-modal-close:hover { color: #333; }
        .ep-modal-body  { padding: 20px; }
        .ep-modal-foot  {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 14px 20px;
            border-top: 1px solid #f0f0f0;
        }
        @keyframes epSlideUp {
            from { transform: translateY(16px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }
    </style>

    <script>
    const epModal = {
        open(id) {
            const el = document.getElementById(id);
            if (el) { el.classList.add('open'); document.body.style.overflow = 'hidden'; }
        },
        close(id) {
            const el = document.getElementById(id);
            if (el) { el.classList.remove('open'); document.body.style.overflow = ''; }
        }
    };
    // Fermer en cliquant sur l'overlay
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('ep-modal-overlay')) {
            e.target.classList.remove('open');
            document.body.style.overflow = '';
        }
    });
    // Fermer avec Echap
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.ep-modal-overlay.open').forEach(m => {
                m.classList.remove('open');
                document.body.style.overflow = '';
            });
        }
    });
    </script>

    {{-- Scripts injectés par les pages --}}
    @stack('scripts')

    {{-- Système epModal admin --}}
    <style>
        .ep-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 50;
            background: rgba(0,0,0,0.45);
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .ep-modal-overlay.open { display: flex; }
        .ep-modal {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            animation: epSlideUp .2s ease;
        }
        .ep-modal-sm  { max-width: 420px; }
        .ep-modal-md  { max-width: 560px; }
        .ep-modal-lg  { max-width: 720px; }
        .ep-modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 15px;
            font-weight: 700;
            color: #111;
        }
        .ep-modal-close {
            background: none;
            border: none;
            font-size: 20px;
            color: #999;
            cursor: pointer;
            line-height: 1;
            padding: 0 4px;
        }
        .ep-modal-close:hover { color: #333; }
        .ep-modal-body  { padding: 20px; }
        .ep-modal-foot  {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 14px 20px;
            border-top: 1px solid #f0f0f0;
        }
        @keyframes epSlideUp {
            from { transform: translateY(16px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }
    </style>

    <script>
    const epModal = {
        open(id) {
            const el = document.getElementById(id);
            if (el) { el.classList.add('open'); document.body.style.overflow = 'hidden'; }
        },
        close(id) {
            const el = document.getElementById(id);
            if (el) { el.classList.remove('open'); document.body.style.overflow = ''; }
        }
    };
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('ep-modal-overlay')) {
            e.target.classList.remove('open');
            document.body.style.overflow = '';
        }
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.ep-modal-overlay.open').forEach(m => {
                m.classList.remove('open');
                document.body.style.overflow = '';
            });
        }
    });
    </script>
    {{-- ══ TOASTS ══ --}}
    <div class="toast-wrap" id="toast-wrap">
        @if (session('success'))
        <div class="toast t-success" data-toast>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <span>{{ session('success') }}</span>
        </div>
        @endif
        @if (session('error'))
        <div class="toast t-error" data-toast>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span>{{ session('error') }}</span>
        </div>
        @endif
        @if (session('info'))
        <div class="toast t-info" data-toast>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            <span>{{ session('info') }}</span>
        </div>
        @endif
    </div>

    <script>
    document.querySelectorAll('[data-toast]').forEach(function (t) {
        setTimeout(function () {
            t.classList.add('closing');
            setTimeout(function () { t.remove(); }, 200);
        }, 4000);
    });
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('main .overflow-hidden table').forEach(function (table) {
            var headers = Array.from(table.querySelectorAll('thead th')).map(function (th) {
                return th.textContent.trim();
            });
            if (!headers.length) { return; }
            table.querySelectorAll('tbody tr').forEach(function (row) {
                Array.from(row.querySelectorAll('td')).forEach(function (td, index) {
                    if (!td.dataset.label) {
                        td.dataset.label = headers[index] || '';
                    }
                });
            });
        });
    });
    </script>

</body>
</html>