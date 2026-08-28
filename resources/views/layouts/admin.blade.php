<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', __('messages.super_admin')) — EduPay Cameroun</title>

    {{-- Tailwind CSS + config EduPay --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- DataTables CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css"/>
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css"/>

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
  <link rel="icon" type="image/jpeg" href="{{ asset('images/logo.jpeg') }}">
  <link rel="apple-touch-icon" href="{{ asset('images/logo.jpeg') }}">
</head>
<body class="h-full bg-gray-100 font-sans text-gray-900 antialiased">

    {{-- ── Header Super Admin ── --}}
    <header class="bg-[#0B2545] text-white px-6 py-3 flex items-center justify-between shadow-md">
        <div>
            <span class="text-lg font-bold tracking-tight">
                Edu<span class="text-[#5DCAA5]">Pay</span>
            </span>
            <span class="ml-2 text-sm text-white/50">·</span>
            <span class="ml-2 text-sm font-semibold text-[#E8A020]">{{ __('messages.super_admin') }}</span>
            <div class="text-xs text-white/40 mt-0.5">CDC-EDUPAY-CM-2026-001</div>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-xs px-2.5 py-1 rounded-full bg-[#E8A020]/15 text-[#E8A020] border border-[#E8A020]/30 font-medium">
                {{ __('messages.admin_systeme') }}
            </span>
            <span class="text-sm text-white/70">
                {{ Auth::guard('admin')->user()->nom_complet }}
            </span>
            <div class="relative">
                <button onclick="toggleProfilAdmin()"
                        class="w-9 h-9 rounded-full bg-[#D94040] flex items-center justify-center text-white text-sm font-bold hover:bg-red-700 transition-colors focus:outline-none">
                    {{ Auth::guard('admin')->user()->initiales }}
                </button>

                {{-- Dropdown profil --}}
                <div id="dropdown-profil-admin"
                     style="display:none;position:fixed;top:58px;right:8px;width:min(288px,calc(100vw - 16px));z-index:9998;"
                     class="bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden">

                    {{-- En-tête profil --}}
                    <div class="px-4 py-4 border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-[#D94040] flex items-center justify-center text-white text-base font-bold shrink-0">
                                {{ Auth::guard('admin')->user()->initiales }}
                            </div>
                            <div>
                                <div class="font-bold text-gray-900 text-sm">
                                    {{ Auth::guard('admin')->user()->nom_complet }}
                                </div>
                                <div class="text-xs text-gray-500">{{ Auth::guard('admin')->user()->email }}</div>
                                <div class="mt-1">
                                    @php
                                        $roleStylesD = [
                                            'super-admin'          => 'bg-purple-50 text-purple-700 border-purple-200',
                                            'superviseur'          => 'bg-blue-50 text-blue-700 border-blue-200',
                                            'comptable_plateforme' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        ];
                                        $roleLabelsD = [
                                            'super-admin'          => __('admin.role_super_admin'),
                                            'superviseur'          => __('admin.role_superviseur'),
                                            'comptable_plateforme' => __('admin.role_comptable'),
                                        ];
                                        $roleD = Auth::guard('admin')->user()->getRoleNames()->first() ?? 'super-admin';
                                    @endphp
                                    <span class="text-xs font-medium px-2 py-0.5 rounded-full border {{ $roleStylesD[$roleD] ?? 'bg-gray-50 text-gray-600 border-gray-200' }}">
                                        {{ $roleLabelsD[$roleD] ?? $roleD }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Infos --}}
                    <div class="px-4 py-3 border-b border-gray-100 space-y-2">
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-500">{{ __('messages.telephone') }}</span>
                            <span class="font-medium text-gray-800">{{ Auth::guard('admin')->user()->telephone ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-500">{{ __('messages.dern_connexion') }}</span>
                            <span class="font-medium text-gray-800">
                                {{ Auth::guard('admin')->user()->derniere_connexion
                                    ? Auth::guard('admin')->user()->derniere_connexion->diffForHumans()
                                    : '—' }}
                            </span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-500">{{ __('messages.ip_dern_connexion') }}</span>
                            <span class="font-medium text-gray-800">{{ Auth::guard('admin')->user()->derniere_connexion_ip ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-500">{{ __('messages.statut') }}</span>
                            <span class="text-green-600 font-medium">● {{ __('messages.connecte') }}</span>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="px-4 py-3 space-y-2">
                        <button onclick="document.getElementById('dropdown-profil-admin').style.display='none'; ouvrirModalProfil()"
                                class="w-full text-left text-sm text-[#0D9E75] hover:text-[#085041] font-medium flex items-center gap-2 py-1">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                            {{ __('messages.modifier_profil') }}
                        </button>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit"
                                    class="w-full text-left text-sm text-red-600 hover:text-red-800 font-medium flex items-center gap-2 py-1">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                                    <polyline points="16 17 21 12 16 7"/>
                                    <line x1="21" y1="12" x2="9" y2="12"/>
                                </svg>
                                {{ __('messages.deconnexion_securisee') }}
                            </button>
                        </form>
                    </div>

                </div>
            </div>
            <form method="POST" action="{{ route('locale.switch') }}" class="inline">
                @csrf
                <select name="locale" onchange="this.form.submit()" class="text-xs bg-white/10 hover:bg-white/20 border border-white/20 rounded-full px-3 py-1.5 text-white cursor-pointer">
                    <option value="fr" {{ app()->getLocale()==='fr' ? 'selected' : '' }}>🇫🇷 FR</option>
                    <option value="en" {{ app()->getLocale()==='en' ? 'selected' : '' }}>🇬🇧 EN</option>
                </select>
            </form>
            <form method="POST" action="{{ route('admin.logout') }}" class="inline">
                @csrf
                <button type="submit"
                    class="text-white/50 hover:text-white text-xs border border-white/20 hover:border-white/40 px-3 py-1.5 rounded-full transition-colors">
                    {{ __('messages.deconnexion') }}
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
                    {{ __('messages.vue_globale') }}
                </a>

                {{-- Établissements --}}
                @if (Route::has('admin.etablissements.index'))
                <a href="{{ route('admin.etablissements.index') }}"
                   class="sidebar-link {{ request()->routeIs('admin.etablissements.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="7" width="20" height="15"/><polyline points="16 2 12 7 8 2"/>
                    </svg>
                    {{ __('messages.etablissements') }}
                </a>
                @endif

                {{-- Comptes payeurs --}}
                <a href="{{ route('admin.payeurs.index') }}"
                   class="sidebar-link {{ request()->routeIs('admin.payeurs.*') ? 'active' : '' }}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    {{ __('messages.comptes_payeurs') }}
                </a>

                {{-- Transactions --}}
                @if (Route::has('admin.transactions.index'))
                <a href="{{ route('admin.transactions.index') }}"
                   class="sidebar-link {{ request()->routeIs('admin.transactions.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                        <line x1="1" y1="10" x2="23" y2="10"/>
                    </svg>
                    {{ __('messages.transactions') }}
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
                    {{ __('messages.commissions') }}
                </a>
                @endif


               {{-- Réclamations --}}
                @if (Route::has('admin.reclamations.index'))
                <a href="{{ route('admin.reclamations.index') }}"
                   class="sidebar-link {{ request()->routeIs('admin.reclamations.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                    </svg>
                    {{ __('messages.reclamations') }}
                </a>
                @endif

                {{-- Logs sécurité --}}
                @if (Route::has('admin.logs.index'))
                <a href="{{ route('admin.logs.index') }}"
                   class="sidebar-link {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    {{ __('messages.logs_securite') }}
                </a>
                @endif

                {{-- Abonnements --}}
                @if (Route::has('admin.abonnements.index'))
                <a href="{{ route('admin.abonnements.index') }}"
                   class="sidebar-link {{ request()->routeIs('admin.abonnements.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>
                    </svg>
                    {{ __('messages.abonnements') }}
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
                    {{ __('messages.exports_reglementaires') }}
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
                    {{ __('messages.params_sys') }}
                </a>
                @endif
                <a href="{{ route('admin.admins.index') }}"
                   class="sidebar-link {{ request()->routeIs('admin.admins.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                        <path d="M16 3.13a4 4 0 010 7.75"/>
                    </svg>
                    {{ __('messages.equipe_admin') }}
                </a>
            </nav>

            {{-- Widget commission active --}}
            <div class="mt-5 bg-[#FEF3DC] rounded-lg p-3 border-l-2 border-[#E8A020]">
                <div class="text-xs font-semibold text-[#8B5E10]">{{ __('messages.commission_active') }}</div>
                <div class="text-2xl font-bold text-[#663E08] mt-1">
                    {{ number_format(($tauxCommission ?? 0.025) * 100, 1, ',', '') }}%
                </div>
                <div class="text-xs text-[#8B5E10] mt-0.5">{{ __('messages.par_transaction') }}</div>
            </div>
        </aside>

        {{-- Contenu principal --}}
        <main class="flex-1 overflow-y-auto p-6">

            {{-- Contenu de la page --}}
            @yield('content')
        </main>
    </div>

    {{-- ══ TOASTS ══ --}}
    <div class="toast-wrap" id="toast-wrap">
        @if(session('success'))
        <div class="toast t-success" data-toast>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <span>{{ session('success') }}</span>
        </div>
        @endif
        @if(session('error'))
        <div class="toast t-error" data-toast>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span>{{ session('error') }}</span>
        </div>
        @endif
        @if(session('info'))
        <div class="toast t-info" data-toast>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            <span>{{ session('info') }}</span>
        </div>
        @endif
    </div>

    {{-- Modal : Modifier mon profil (Super Admin) --}}
    <div id="modal-profil-admin" class="ep-modal-overlay">
      <div class="ep-modal ep-modal-md">
        <div class="ep-modal-head">
          <h3>{{ __('messages.modifier_profil') }}</h3>
          <button class="ep-modal-close" onclick="epModal.close('modal-profil-admin')">x</button>
        </div>
        <form method="POST" action="{{ route('admin.profil.update') }}">
          @csrf
          @method('PATCH')
          <div class="ep-modal-body">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.prenom') }}</label>
                <input type="text" name="prenom" required
                       value="{{ old('prenom', Auth::guard('admin')->user()->prenom) }}"
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('messages.nom') }}</label>
                <input type="text" name="nom" required
                       value="{{ old('nom', Auth::guard('admin')->user()->nom) }}"
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
              </div>
            </div>
            <div class="mb-3">
              <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.email') }}</label>
              <input type="email" name="email" required
                     value="{{ old('email', Auth::guard('admin')->user()->email) }}"
                     class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
            </div>
            <div class="mb-4">
              <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('messages.telephone') }}</label>
              <input type="text" name="telephone"
                     value="{{ old('telephone', Auth::guard('admin')->user()->telephone) }}"
                     class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
            </div>
            <div class="border-t border-gray-100 pt-3">
              <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">{{ __('admin.changer_mdp_opt') }}</div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.nouveau_mdp') }}</label>
                  <div class="ep-pwd-wrap">
                    <input type="password" name="password" minlength="10"
                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
                    <button type="button" class="ep-pwd-toggle" onclick="togglePasswordVisibility(this)" aria-label="{{ __('admin.voir_mdp') }}">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                      </svg>
                    </button>
                  </div>
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.confirmer') }}</label>
                  <div class="ep-pwd-wrap">
                    <input type="password" name="password_confirmation" minlength="10"
                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
                    <button type="button" class="ep-pwd-toggle" onclick="togglePasswordVisibility(this)" aria-label="{{ __('admin.voir_mdp') }}">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
              <p class="text-xs text-gray-400 mt-1">{{ __('admin.laisser_vide_mdp') }}</p>
            </div>
            @if($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-lg p-3 mt-3">
              @foreach($errors->all() as $error)
                <div class="text-xs text-red-700">{{ $error }}</div>
              @endforeach
            </div>
            @endif
          </div>
          <div class="ep-modal-foot">
            <button type="button" onclick="epModal.close('modal-profil-admin')"
                    style="padding:8px 16px;font-size:13px;border:1px solid #ddd;border-radius:8px;background:#fff;cursor:pointer;">
              {{ __('messages.annuler') }}
            </button>
            <button type="submit"
                    style="padding:8px 20px;font-size:13px;font-weight:600;background:#0D9E75;color:#fff;border:none;border-radius:8px;cursor:pointer;">
              {{ __('messages.enregistrer') }}
            </button>
          </div>
        </form>
      </div>
    </div>

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
            .admin-body main .overflow-hidden table:not(.ep-dt) {
                min-width: 900px;
                table-layout: auto;
            }
            .admin-body main th,
            .admin-body main td {
                white-space: nowrap;
                word-break: normal;
            }
            /* table.ep-dt (DataTables Responsive) : aucune règle de largeur/wrap forcée ici.
               Le plugin a besoin de mesurer le contenu en white-space:nowrap natif
               pour calculer correctement quelles colonnes replier. */
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


    {{-- jQuery + DataTables JS --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

    {{-- DataTables config globale EduPay --}}
    <style>
    /* ══ DataTables EduPay Theme ══ */
    .dt-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    table.ep-dt { width: 100% !important; border-collapse: collapse; font-size: 13px; }
    table.ep-dt thead th {
        background: #f8fafc;
        color: #6b7280;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .05em;
        padding: 10px 14px;
        border-bottom: 2px solid #e5e7eb;
        white-space: nowrap;
        cursor: pointer;
        user-select: none;
    }
    table.ep-dt thead th:hover { background: #f0fdf4; color: #0D9E75; }
    table.ep-dt thead th.sorting_asc  { color: #0D9E75; background: #f0fdf4; }
    table.ep-dt thead th.sorting_desc { color: #0D9E75; background: #f0fdf4; }
    table.ep-dt thead th::after,
    table.ep-dt thead th::before { color: #0D9E75 !important; }
    table.ep-dt tbody tr { border-bottom: 1px solid #f3f4f6; transition: background .1s; }
    table.ep-dt tbody tr:hover { background: #f9fafb; }
    table.ep-dt tbody td { padding: 10px 14px; color: #374151; vertical-align: middle; }
    /* Toolbar (search + length) */
    .ep-dt-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        border-bottom: 1px solid #f0f0f0;
        flex-wrap: wrap;
        gap: 10px;
    }
    .ep-dt-toolbar .dt-length select {
        padding: 6px 10px;
        font-size: 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        outline: none;
        color: #555;
    }
    .ep-dt-toolbar .dt-search input {
        padding: 7px 12px;
        font-size: 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        outline: none;
        width: 220px;
        transition: border .15s;
    }
    .ep-dt-toolbar .dt-search input:focus { border-color: #0D9E75; }
    /* Pagination */
    .ep-dt-foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 16px;
        border-top: 1px solid #f0f0f0;
        flex-wrap: wrap;
        gap: 8px;
    }
    .ep-dt-foot .dt-info { font-size: 12px; color: #9ca3af; }
    .ep-dt-foot .dt-paging .paginate_button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 6px;
        font-size: 12px;
        cursor: pointer;
        border: none;
        background: none;
        color: #555;
        margin: 0 1px;
        transition: all .15s;
    }
    .ep-dt-foot .dt-paging .paginate_button:hover { background: #E0F5EE; color: #085041; }
    .ep-dt-foot .dt-paging .paginate_button.current { background: #0D9E75; color: #fff !important; font-weight: 600; }
    .ep-dt-foot .dt-paging .paginate_button.disabled { color: #d1d5db; cursor: not-allowed; }
    /* Responsive — colonnes cachées */
    /* Badges et boutons actions DataTables */
    .ep-badge { font-size:11px; font-weight:600; padding:3px 10px; border-radius:20px; display:inline-block; }
    .ep-badge-green  { background:#dcfce7; color:#166534; }
    .ep-badge-yellow { background:#fef9c3; color:#854d0e; }
    .ep-badge-red    { background:#fee2e2; color:#991b1b; }
    .ep-badge-gray   { background:#f3f4f6; color:#4b5563; }
    .ep-actions { display:flex; align-items:center; justify-content:center; gap:5px; flex-wrap:wrap; }
    .ep-btn-icon { width:28px; height:28px; display:inline-flex; align-items:center; justify-content:center;
                   border:none; border-radius:7px; cursor:pointer; transition:opacity .15s; }
    .ep-btn-icon:hover { opacity:.8; }
    .ep-btn-teal   { background:#E0F5EE; color:#0D9E75; }
    .ep-btn-green  { background:#dcfce7; color:#16a34a; }
    .ep-btn-yellow { background:#fef9c3; color:#ca8a04; }
    .ep-btn-red    { background:#fee2e2; color:#dc2626; }
    .ep-dt-name { font-weight:600; color:#111; font-size:13px; }
    .ep-dt-sub  { font-size:11px; color:#9ca3af; margin-top:1px; }
    .ep-dt-center { text-align:center; font-weight:600; color:#374151; }
    .ep-link    { color:#0D9E75 !important; }
    .ep-pwd-wrap { position: relative; }
    .ep-pwd-wrap input[type="password"],
    .ep-pwd-wrap input[type="text"] { padding-right: 38px !important; }
    .ep-pwd-toggle {
        position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
        background: none; border: none; cursor: pointer; padding: 4px;
        color: #9ca3af; display: flex; align-items: center; justify-content: center;
    }
    .ep-pwd-toggle:hover { color: #0D9E75; }


    table.ep-dt td.dtr-details { padding: 0; }
    table.ep-dt td.dtr-control { cursor: pointer; }
    table.ep-dt td.dtr-control::before {
        background: #0D9E75 !important;
        border-color: #0D9E75 !important;
    }
    @media (max-width: 640px) {
        .ep-dt-toolbar .dt-search input { width: 160px; }
    }
    </style>

    <script>
    window.epDT = function(selector, opts) {
        var defaults = {
            responsive: true,
            language: {
                search:           '',
                searchPlaceholder: @json(__('messages.recherche')),
                lengthMenu:        @json(__('messages.afficher_lignes')),
                info:              @json(__('admin.dt_info')),
                infoEmpty:         @json(__('admin.dt_info_empty')),
                infoFiltered:      @json(__('admin.dt_info_filtered')),
                zeroRecords:       @json(__('admin.dt_empty_table')),
                emptyTable:        @json(__('admin.dt_empty_table')),
                paginate: {
                    first:    '«',
                    previous: '‹',
                    next:     '›',
                    last:     '»'
                }
            },
            dom: '<"ep-dt-toolbar"l<"dt-search"f>>rt<"ep-dt-foot"i<"dt-paging"p>>',
            pageLength: 15,
            lengthMenu: [[10, 15, 25, 50, -1], [10, 15, 25, 50, @json(__('admin.dt_all'))]],
        };
        return $(selector).DataTable($.extend(true, defaults, opts || {}));
    };
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
    // ── Toasts auto-dismiss (flash session) ──
    document.querySelectorAll('[data-toast]').forEach(function(t){
        setTimeout(function(){ t.classList.add('closing'); setTimeout(function(){ t.remove(); },200); },4000);
    });

    // ── API Toast globale (pour AJAX) ──
    // epToast('Message', 'success' | 'error' | 'info')
    window.epToast = function(message, type) {
        type = type || 'info';
        var wrap = document.getElementById('toast-wrap');
        if (!wrap) return;

        var icons = {
            success: '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
            error:   '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',
            info:    '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>'
        };

        var el = document.createElement('div');
        el.className = 'toast t-' + type;
        el.setAttribute('data-toast', '');
        el.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2">'
            + (icons[type] || icons.info) + '</svg><span></span>';
        el.querySelector('span').textContent = message;

        wrap.appendChild(el);

        setTimeout(function(){
            el.classList.add('closing');
            setTimeout(function(){ el.remove(); }, 200);
        }, 4000);
    };

    // ── Toggle visibilité mot de passe (bouton oeil) ──
    function togglePasswordVisibility(btn) {
        const wrapper = btn.closest('.ep-pwd-wrap');
        if (!wrapper) return;
        const input = wrapper.querySelector('input[type="password"], input[type="text"]');
        if (!input) return;
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        btn.innerHTML = isHidden
            ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.8 21.8 0 0 1 5.06-6.06M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.77 21.77 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>'
            : '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    }

    // ── Dropdown profil admin (header) ──
    function toggleProfilAdmin() {
        const el = document.getElementById('dropdown-profil-admin');
        if (!el) return;
        el.style.display = (el.style.display === 'none' || !el.style.display) ? 'block' : 'none';
    }
    // Fermer le dropdown si on clique en dehors
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('dropdown-profil-admin');
        const btn = e.target.closest('button[onclick*="toggleProfilAdmin"]');
        if (dropdown && dropdown.style.display === 'block' && !dropdown.contains(e.target) && !btn) {
            dropdown.style.display = 'none';
        }
    });

    // ── Modal modification profil admin ──
    function ouvrirModalProfil() {
        epModal.open('modal-profil-admin');
    }

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
    </body></html>
