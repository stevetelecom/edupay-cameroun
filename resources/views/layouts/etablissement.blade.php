<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', __('messages.tableau_de_bord')) — EduPay Cameroun</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />

    {{-- DataTables CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css"/>
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css"/>

    <style>
        :root{
            --ep-navy:#0B2545; --ep-teal:#0D9E75; --ep-teal2:#0A8562;
            --ep-teal-lt:#E0F5EE; --ep-teal-mid:#9FE1CB;
            --ep-gold:#E8A020; --ep-gold-lt:#FEF3DC;
            --ep-red:#D94040; --ep-red-lt:#FBEAEA;
            --ep-blue-lt:#E6F0FB; --ep-purple-lt:#EDE9FE;
            --border:rgba(0,0,0,0.09); --radius-md:8px; --radius-lg:12px;
        }
        *{box-sizing:border-box;}
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:14px;background:#f1f3f5;color:#1a1a2e;}
        .epcard{background:rgba(255,255,255,.93);border:1px solid var(--border);border-radius:var(--radius-lg);padding:18px;position:relative;z-index:1;}
        .pill{display:inline-block;font-size:11px;padding:3px 9px;border-radius:20px;font-weight:500;}
        .pg{background:#E0F5EE;color:#085041;}.pa{background:#FEF3DC;color:#8B5E10;}
        .pr{background:#FBEAEA;color:#9B2C2C;}.pb{background:#E6F0FB;color:#1A4F8A;}
        .g2{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
        .g3{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;}
        .g4{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;}
        .kpi{background:rgba(248,249,250,.88);border-radius:var(--radius-md);padding:16px;text-align:center;position:relative;z-index:1;}
        .kval{font-size:22px;font-weight:700;color:#1a1a2e;}
        .klbl{font-size:11px;color:#888;margin-top:4px;}
        .seclbl{font-size:11px;font-weight:600;color:#999;text-transform:uppercase;letter-spacing:.06em;margin:18px 0 10px;}
        .icon-round{width:40px;height:40px;border-radius:14px;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0;}
        .icon-round svg{width:18px;height:18px;}
        .material-symbols-outlined{font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;font-size:18px;display:inline-flex;align-items:center;justify-content:center;color:#fff;}
        .btn-p{background:var(--ep-teal);color:#fff;border:none;padding:11px 20px;border-radius:var(--radius-md);font-size:13px;font-weight:500;cursor:pointer;transition:background .15s;text-decoration:none;display:inline-block;text-align:center;}
        .btn-p:hover{background:var(--ep-teal2);color:#fff;}
        .btn-o{background:transparent;color:var(--ep-teal);border:2px solid var(--ep-teal);padding:9px 18px;border-radius:var(--radius-md);font-size:13px;font-weight:500;cursor:pointer;transition:all .15s;text-decoration:none;display:inline-block;text-align:center;}
        .btn-o:hover{background:var(--ep-teal-lt);}
        .btn-r{background:var(--ep-red);color:#fff;border:none;padding:9px 18px;border-radius:var(--radius-md);font-size:13px;font-weight:500;cursor:pointer;text-decoration:none;display:inline-block;text-align:center;}
        .btn-r:hover{background:#C13333;}
        .inp{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:var(--radius-md);font-size:13px;margin-bottom:12px;outline:none;transition:border .15s;}
        .inp:focus{border-color:var(--ep-teal);}
        .select{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:var(--radius-md);font-size:13px;margin-bottom:12px;background:#fff;outline:none;}
        .select:focus{border-color:var(--ep-teal);}
        .lbl{font-size:11px;color:#666;margin-bottom:5px;font-weight:500;}
        .divider{height:1px;background:#f0f0f0;margin:14px 0;}
        .app-header{background:var(--ep-navy);color:#fff;padding:13px 24px;display:flex;align-items:center;justify-content:space-between;}
        .app-body{display:flex;min-height:calc(100vh - 58px);}
        .sidebar{width:200px;flex-shrink:0;padding:14px;background:#fff;border-right:1px solid var(--border);}
        .sbar-item{display:flex;align-items:center;gap:9px;padding:9px 12px;border-radius:var(--radius-md);font-size:13px;color:#555;cursor:pointer;margin-bottom:2px;text-decoration:none;}
        .sbar-item svg{width:15px;height:15px;flex-shrink:0;}
        .sbar-item.on{background:var(--ep-teal-lt);color:#085041;font-weight:600;}
        .sbar-item:hover:not(.on){background:#f0f0f0;}
        .main-content{flex:1;padding:22px 24px;background:#f5f6f7;overflow-y:auto;position:relative;}
        .main-content::before{
            content:'';
            position:fixed;
            top:58px; left:200px; right:0; bottom:0;
            background-image:url('{{ asset('images/logo-watermark.png') }}');
            background-repeat:no-repeat;
            background-position:center;
            background-size:min(75vw, 780px);
            opacity:.22;
            pointer-events:none;
            z-index:0;
        }
        @media (max-width: 900px){
            .main-content::before{ left:0; background-size:min(88vw, 420px); }
        }
        .prog{height:5px;background:#eee;border-radius:3px;overflow:hidden;margin-top:6px;}
        .pfill{height:100%;background:var(--ep-teal);border-radius:3px;}
        .dot{width:8px;height:8px;border-radius:50%;display:inline-block;}
        .row{display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f0f0f0;font-size:13px;}
        .row:last-child{border-bottom:none;}
        .badge-cnt{background:#FBEAEA;color:#9B2C2C;border-radius:10px;padding:1px 7px;font-size:10px;margin-left:4px;}
        .av{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;}
        table.ep-table{width:100%;border-collapse:collapse;font-size:13px;}
        table.ep-table th{text-align:left;font-size:11px;font-weight:600;color:#999;text-transform:uppercase;letter-spacing:.05em;padding:10px 8px;border-bottom:2px solid #f0f0f0;}
        table.ep-table td{padding:11px 8px;border-bottom:1px solid #f0f0f0;}
        table.ep-table tr:last-child td{border-bottom:none;}
        table.ep-table tr:hover td{background:#fafbfc;}
        .toast-wrap{position:fixed;top:18px;right:18px;z-index:9999;display:flex;flex-direction:column;gap:10px;}
        .toast{display:flex;align-items:flex-start;gap:10px;min-width:280px;max-width:360px;padding:13px 16px;border-radius:var(--radius-md);box-shadow:0 6px 20px rgba(0,0,0,.15);font-size:13px;font-weight:500;animation:toast-in .25s ease-out;}
        .toast.t-success{background:#085041;color:#fff;}
        .toast.t-error{background:#9B2C2C;color:#fff;}
        .toast.t-info{background:#1A4F8A;color:#fff;}
        .toast svg{flex-shrink:0;margin-top:1px;}
        .toast.closing{animation:toast-out .2s ease-in forwards;}
        @keyframes toast-in{from{opacity:0;transform:translateX(30px);}to{opacity:1;transform:translateX(0);}}
        @keyframes toast-out{from{opacity:1;transform:translateX(0);}to{opacity:0;transform:translateX(30px);}}

        /* ══ SYSTÈME MODAL GLOBAL ══ */
        .ep-modal-overlay{
            display:none;position:fixed;inset:0;
            background:rgba(11,37,69,.55);
            z-index:9000;
            align-items:center;justify-content:center;
            animation:modal-bg-in .18s ease-out;
        }
        .ep-modal-overlay.open{display:flex;}
        @keyframes modal-bg-in{from{opacity:0;}to{opacity:1;}}
        .ep-modal{
            background:#fff;border-radius:var(--radius-lg);
            width:100%;margin:16px;
            box-shadow:0 24px 64px rgba(0,0,0,.22);
            max-height:90vh;display:flex;flex-direction:column;
            animation:modal-in .2s cubic-bezier(.34,1.3,.7,1);
        }
        @keyframes modal-in{from{opacity:0;transform:scale(.94) translateY(12px);}to{opacity:1;transform:scale(1) translateY(0);}}
        .ep-modal-sm{max-width:420px;}
        .ep-modal-md{max-width:560px;}
        .ep-modal-lg{max-width:720px;}
        .ep-modal-xl{max-width:900px;}
        .ep-modal-head{
            display:flex;align-items:center;justify-content:space-between;
            padding:16px 20px;border-bottom:1px solid #f0f0f0;flex-shrink:0;
        }
        .ep-modal-head h3{font-size:15px;font-weight:700;margin:0;}
        .ep-modal-close{
            background:none;border:none;cursor:pointer;
            width:30px;height:30px;border-radius:50%;
            display:flex;align-items:center;justify-content:center;
            color:#888;font-size:18px;transition:background .15s;
        }
        .ep-modal-close:hover{background:#f5f5f5;color:#333;}
        .ep-modal-body{padding:20px;overflow-y:auto;flex:1;}
        .ep-modal-foot{
            display:flex;justify-content:flex-end;gap:10px;
            padding:14px 20px;border-top:1px solid #f0f0f0;flex-shrink:0;
        }
        /* Danger zone dans modal */
        .ep-modal-danger .ep-modal-head{border-bottom-color:#FBEAEA;}
        .ep-modal-danger .ep-modal-head h3{color:var(--ep-red);}

        /* ───────────────────────── MEDIA QUERIES RESPONSIVITÉ ───────────────────────── */

        @media (max-width: 1024px) {
            .g4 { grid-template-columns: repeat(2,1fr) !important; }
        }

        @media (max-width: 768px) {
            .app-body { flex-direction: column; }
            .sidebar {
                width: 100%;
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                padding: 12px 14px;
                border-right: none;
                border-bottom: 1px solid var(--border);
                background: #fff;
            }
            .sbar-item {
                flex: 1 1 calc(50% - 8px);
                min-width: 140px;
                padding: 10px 12px;
                border-radius: var(--radius-md);
            }
            .sbar-item svg { margin-right: 6px; }
            .main-content { padding: 16px 12px; }
            .g2, .g3, .g4 { grid-template-columns: 1fr !important; }
            table.ep-table { font-size: 12px; }
            table.ep-table th { font-size: 10px; padding: 8px 6px; }
            table.ep-table td { padding: 8px 6px; }
            .app-header { flex-direction: column; align-items: flex-start; gap: 12px; }
        }

        @media (max-width: 480px) {
            body { font-size: 13px; }
            .app-header { padding: 10px 12px; }
            .main-content { padding: 12px 10px; }
            .epcard { padding: 12px; }
            .kpi { padding: 12px; }
            .kval { font-size: 18px; }
            .klbl { font-size: 10px; }
            .btn-p, .btn-o, .btn-r { padding: 9px 14px; font-size: 12px; }
            .inp { padding: 8px 10px; font-size: 12px; margin-bottom: 8px; }
            .lbl { font-size: 10px; margin-bottom: 3px; }
            .select { padding: 8px 10px; font-size: 12px; margin-bottom: 8px; }
            .seclbl { font-size: 10px; margin: 12px 0 6px; }
            .row { font-size: 12px; padding: 8px 0; }
            .badge-cnt { font-size: 9px; padding: 1px 5px; }
            table.ep-table { font-size: 11px; }
            table.ep-table th { font-size: 9px; padding: 6px 4px; }
            table.ep-table td { padding: 6px 4px; }
            .toast { min-width: 260px; max-width: 300px; padding: 10px 12px; font-size: 12px; }
            .ep-modal-sm, .ep-modal-md, .ep-modal-lg, .ep-modal-xl { max-width: 100% !important; }
            .ep-modal-body { padding: 14px; }
            .ep-modal-foot { padding: 10px 14px; gap: 6px; }
        }

        @media (max-width: 320px) {
            .btn-p, .btn-o, .btn-r { padding: 7px 10px; font-size: 11px; }
            .inp { padding: 6px 8px; font-size: 11px; }
            .kval { font-size: 16px; }
        }

        /* ───────────────────────── FIN MEDIA QUERIES ───────────────────────── */
    </style>
    @stack('styles')
  <link rel="stylesheet" href="{{ asset('css/buttons-enhanced.css') }}">
  <link rel="stylesheet" href="{{ asset('css/forms-enhanced.css') }}">
  <link rel="icon" type="image/jpeg" href="{{ asset('images/logo.jpeg') }}">
  <link rel="apple-touch-icon" href="{{ asset('images/logo.jpeg') }}">
</head>
<body class="h-full">

    <div class="app-header">
        <div style="display:flex;align-items:center;gap:10px;">
            @if(Auth::user()->etablissement->logo ?? false)
                <img src="{{ asset('storage/' . Auth::user()->etablissement->logo) }}"
                     alt="Logo"
                     style="width:36px;height:36px;border-radius:8px;object-fit:cover;background:#fff;flex-shrink:0;" />
            @else
                <div style="width:36px;height:36px;border-radius:8px;background:rgba(255,255,255,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:13px;font-weight:700;color:#fff;">
                    {{ Str::substr(Auth::user()->etablissement->nom ?? 'E', 0, 1) }}
                </div>
            @endif
            <div>
                <div style="font-size:14px;font-weight:700;color:#fff;">
                    {{ Auth::user()->etablissement->nom ?? 'Mon établissement' }}
                </div>
                <div style="font-size:11px;color:rgba(255,255,255,.5);">
                    @if(Auth::user()->etablissement->ville ?? false){{ Auth::user()->etablissement->ville }}@endif
                </div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            <span style="font-size:12px;color:rgba(255,255,255,.65);">
                {{ Auth::user()->prenom ?? '' }} {{ Auth::user()->nom ?? Auth::user()->name }}
                @if(Auth::user()->roles->first()) ({{ ucfirst(Auth::user()->roles->first()->name) }})@endif
            </span>
            <div style="position:relative;">
                <button onclick="toggleProfilEtab()"
                        title="{{ __('messages.voir_profil') }}"
                        style="width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.12);
                               color:#fff;font-size:13px;font-weight:700;border:none;cursor:pointer;
                               display:flex;align-items:center;justify-content:center;transition:opacity .15s;"
                        onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                    {{ Str::substr(Auth::user()->prenom ?? Auth::user()->name, 0, 1) }}{{ Str::substr(Auth::user()->nom ?? '', 0, 1) }}
                </button>

                {{-- Dropdown profil établissement --}}
                <div id="dropdown-profil-etab"
                     style="display:none;position:fixed;top:58px;right:8px;width:min(288px,calc(100vw - 16px));
                            background:#fff;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.14);
                            border:1px solid #e5e7eb;z-index:9998;overflow:hidden;">

                    {{-- En-tête --}}
                    <div style="padding:16px;border-bottom:1px solid #f0f0f0;">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:46px;height:46px;border-radius:50%;
                                        background:var(--ep-teal);color:#fff;
                                        font-size:16px;font-weight:700;flex-shrink:0;
                                        display:flex;align-items:center;justify-content:center;">
                                {{ Str::substr(Auth::user()->prenom ?? Auth::user()->name, 0, 1) }}{{ Str::substr(Auth::user()->nom ?? '', 0, 1) }}
                            </div>
                            <div>
                                <div style="font-size:14px;font-weight:700;color:#111;">
                                    {{ Auth::user()->prenom ?? '' }} {{ Auth::user()->nom ?? Auth::user()->name }}
                                </div>
                                <div style="font-size:11px;color:#888;">{{ Auth::user()->email ?? Auth::user()->telephone }}</div>
                                <span style="font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px;
                                             background:#E1F5EE;color:#085041;border:1px solid #5DCAA5;
                                             margin-top:4px;display:inline-block;">
                                    {{ ucfirst(Auth::user()->roles->first()->name ?? 'Utilisateur') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Établissement --}}
                    <div style="padding:12px 16px;border-bottom:1px solid #f0f0f0;">
                        <div style="font-size:10px;font-weight:600;color:#aaa;text-transform:uppercase;
                                    letter-spacing:.05em;margin-bottom:8px;">{{ __('messages.etablissement') }}</div>
                        <div style="display:flex;justify-content:space-between;font-size:12px;padding:3px 0;">
                            <span style="color:#888;">{{ __('messages.nom') }}</span>
                            <span style="font-weight:600;color:#333;">{{ Auth::user()->etablissement->nom ?? '—' }}</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:12px;padding:3px 0;">
                            <span style="color:#888;">{{ __('messages.ville') }}</span>
                            <span style="font-weight:600;color:#333;">{{ Auth::user()->etablissement->ville ?? '—' }}</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:12px;padding:3px 0;">
                            <span style="color:#888;">{{ __('messages.code') }}</span>
                            <span style="font-weight:600;color:var(--ep-teal);">
                                {{ Auth::user()->etablissement->code_etablissement ?? '—' }}
                            </span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:12px;padding:3px 0;">
                            <span style="color:#888;">{{ __('messages.statut') }}</span>
                            <span style="color:#0D9E75;font-weight:600;">● {{ __('messages.connecte') }}</span>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div style="padding:12px 16px;">
                        <button type="button" onclick="toggleProfilEtab(); epModal.open('modal-profil-etab');"
                           style="display:flex;align-items:center;gap:8px;font-size:13px;width:100%;
                                  color:#0D9E75;font-weight:500;text-decoration:none;padding:4px 0;
                                  background:none;border:none;cursor:pointer;text-align:left;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                            Voir mon profil
                        </button>
                        <form method="POST" action="{{ route('logout') }}" style="margin-top:8px;">
                            @csrf
                            <button type="submit"
                                    style="background:none;border:none;cursor:pointer;padding:4px 0;
                                           font-size:13px;color:#e53e3e;font-weight:500;
                                           display:flex;align-items:center;gap:8px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                                    <polyline points="16 17 21 12 16 7"/>
                                    <line x1="21" y1="12" x2="9" y2="12"/>
                                </svg>
                                {{ __('messages.deconnexion') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <form method="POST" action="{{ route('locale.switch') }}" style="display:inline-flex;align-items:center;">
                @csrf
                <select name="locale" onchange="this.form.submit()" style="background:rgba(255,255,255,.08);color:#fff;border:1px solid rgba(255,255,255,.25);border-radius:20px;padding:6px 10px;font-size:11px;font-weight:500;cursor:pointer;outline:none;">
                    <option value="fr" {{ app()->getLocale()==='fr' ? 'selected' : '' }}>🇫🇷 FR</option>
                    <option value="en" {{ app()->getLocale()==='en' ? 'selected' : '' }}>🇬🇧 EN</option>
                </select>
            </form>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" style="background:transparent;color:rgba(255,255,255,.5);border:1px solid rgba(255,255,255,.2);padding:6px 12px;border-radius:20px;font-size:11px;cursor:pointer;">
                    {{ __('messages.deconnexion') }}
                </button>
            </form>
        </div>
    </div>

    <div class="app-body">
        <div class="sidebar">
            <a href="{{ route('etablissement.dashboard') }}" class="sbar-item {{ request()->routeIs('etablissement.dashboard') ? 'on' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                {{ __('messages.tableau_de_bord') }}
            </a>
            <a href="{{ route('etablissement.apprenants.index') }}" class="sbar-item {{ request()->routeIs('etablissement.apprenants.*') ? 'on' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                {{ __('messages.apprenants') }}
            </a>
            <a href="{{ route('etablissement.frais.index') }}" class="sbar-item {{ request()->routeIs('etablissement.frais.*') ? 'on' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                {{ __('messages.frais_echeanciers') }}
            </a>
            <a href="{{ route('etablissement.paiements.index') }}" class="sbar-item {{ request()->routeIs('etablissement.paiements.*') ? 'on' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                {{ __('messages.paiements') }}
            </a>
            <a href="{{ route('etablissement.impayes.index') }}" class="sbar-item {{ request()->routeIs('etablissement.impayes.*') ? 'on' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                {{ __('messages.impayes') }}
                @if(($countImpayes ?? 0) > 0)<span class="badge-cnt">{{ $countImpayes }}</span>@endif
            </a>
            <a href="{{ route('etablissement.rapports.index') }}" class="sbar-item {{ request()->routeIs('etablissement.rapports.*') ? 'on' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                {{ __('messages.rapports') }}
            </a>
            <a href="{{ route('etablissement.remboursements.index') }}" class="sbar-item {{ request()->routeIs('etablissement.remboursements.*') ? 'on' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/></svg>
                {{ __('messages.remboursements') }}
            </a>
            <a href="{{ route('etablissement.sites.index') }}" class="sbar-item {{ request()->routeIs('etablissement.sites.*') ? 'on' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                {{ __('messages.multi_sites') }}
            </a>
            <a href="{{ route('etablissement.parametres.index') }}" class="sbar-item {{ request()->routeIs('etablissement.parametres.*') ? 'on' : '' }}" style="margin-top:4px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                {{ __('messages.parametres') }}
            </a>
            <a href="{{ route('etablissement.utilisateurs.index') }}" class="sbar-item {{ request()->routeIs('etablissement.utilisateurs.*') ? 'on' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                {{ __('messages.utilisateurs_internes') }}
            </a>
            <a href="{{ route('etablissement.aide.index') }}" class="sbar-item {{ request()->routeIs('etablissement.aide.*') ? 'on' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                {{ __('messages.guide_support') }}
            </a>
            <div style="margin-top:16px;background:var(--ep-teal-lt);border-radius:var(--radius-md);padding:12px;">
                <div style="font-size:11px;font-weight:600;color:#0F6E56;margin-bottom:4px;">{{ __('messages.recouvrement') }}</div>
                <div style="font-size:24px;font-weight:700;color:#085041;">{{ number_format($tauxRecouvrementDecimal ?? 0, 2, ',', '') }}%</div>
                <div class="prog"><div class="pfill" style="width:{{ min($tauxRecouvrementDecimal ?? 0, 100) }}%"></div></div>
                <div style="font-size:10px;color:#1B9E75;margin-top:3px;">{{ __('messages.objectif_pourcent') }}</div>
            </div>
        </div>

        <div class="main-content">
            @yield('content')
        </div>
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

    {{-- ══ MODAL SYSTÈME — toutes les pages peuvent en déclarer via @push('modals') ══ --}}
    @include('etablissement.partials.modal-profil')
    @stack('modals')

    <script>
    // ── Toasts auto-dismiss ──
    document.querySelectorAll('[data-toast]').forEach(function(t){
        setTimeout(function(){ t.classList.add('closing'); setTimeout(function(){ t.remove(); },200); },4000);
    });

    // ══ API MODAL GLOBALE ══
    // Ouvrir  : epModal.open('monModalId')
    // Fermer  : epModal.close('monModalId')  ou  epModal.closeAll()
    var epModal = {
        open: function(id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.classList.add('open');
            document.body.style.overflow = 'hidden';
            // Ferme sur clic overlay
            el.addEventListener('click', function handler(e){
                if (e.target === el) { epModal.close(id); el.removeEventListener('click', handler); }
            });
            // Ferme sur Échap
            document._epEsc = function(e){ if(e.key==='Escape') epModal.close(id); };
            document.addEventListener('keydown', document._epEsc);
        },
        close: function(id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.classList.remove('open');
            document.body.style.overflow = '';
            document.removeEventListener('keydown', document._epEsc);
        },
        closeAll: function() {
            document.querySelectorAll('.ep-modal-overlay.open').forEach(function(el){
                el.classList.remove('open');
            });
            document.body.style.overflow = '';
        }
    };

    // Boutons [data-modal-open] et [data-modal-close] déclaratifs
    document.addEventListener('DOMContentLoaded', function(){
        document.querySelectorAll('[data-modal-open]').forEach(function(btn){
            btn.addEventListener('click', function(){ epModal.open(btn.dataset.modalOpen); });
        });
        document.querySelectorAll('[data-modal-close]').forEach(function(btn){
            btn.addEventListener('click', function(){ epModal.close(btn.dataset.modalClose); });
        });
    });
    
function toggleProfilEtab() {
    var el = document.getElementById('dropdown-profil-etab');
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
document.addEventListener('click', function(e) {
    var dropdown = document.getElementById('dropdown-profil-etab');
    if (!dropdown) return;
    var btn = dropdown.previousElementSibling;
    if (!dropdown.contains(e.target) && !btn.contains(e.target)) {
        dropdown.style.display = 'none';
    }
});
</script>

    {{-- jQuery + DataTables JS --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

    {{-- ══ DataTables EduPay Theme (identique admin) ══ --}}
    <style>
    .dt-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    table.ep-dt { width: 100% !important; border-collapse: collapse; font-size: 13px; }
    table.ep-dt thead th {
        background: #f8fafc; color: #6b7280; font-size: 11px; font-weight: 600;
        text-transform: uppercase; letter-spacing: .05em; padding: 10px 14px;
        border-bottom: 2px solid #e5e7eb; white-space: nowrap; cursor: pointer; user-select: none;
    }
    table.ep-dt thead th:hover { background: #f0fdf4; color: #0D9E75; }
    table.ep-dt thead th.sorting_asc  { color: #0D9E75; background: #f0fdf4; }
    table.ep-dt thead th.sorting_desc { color: #0D9E75; background: #f0fdf4; }
    table.ep-dt tbody tr { border-bottom: 1px solid #f3f4f6; transition: background .1s; }
    table.ep-dt tbody tr:hover { background: #f9fafb; }
    table.ep-dt tbody td { padding: 10px 14px; color: #374151; vertical-align: middle; }
    .ep-dt-toolbar { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-bottom: 1px solid #f0f0f0; flex-wrap: wrap; gap: 10px; }
    .ep-dt-toolbar .dt-length select { padding: 6px 10px; font-size: 12px; border: 1px solid #ddd; border-radius: 8px; outline: none; color: #555; }
    .ep-dt-toolbar .dt-search input { padding: 7px 12px; font-size: 12px; border: 1px solid #ddd; border-radius: 8px; outline: none; width: 220px; }
    .ep-dt-toolbar .dt-search input:focus { border-color: #0D9E75; }
    .ep-dt-foot { display: flex; align-items: center; justify-content: space-between; padding: 10px 16px; border-top: 1px solid #f0f0f0; flex-wrap: wrap; gap: 8px; }
    .ep-dt-foot .dt-info { font-size: 12px; color: #9ca3af; }
    .ep-dt-foot .dt-paging .paginate_button { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 6px; font-size: 12px; cursor: pointer; border: none; background: none; color: #555; margin: 0 1px; transition: all .15s; }
    .ep-dt-foot .dt-paging .paginate_button:hover { background: #E0F5EE; color: #085041; }
    .ep-dt-foot .dt-paging .paginate_button.current { background: #0D9E75; color: #fff !important; font-weight: 600; }
    .ep-dt-foot .dt-paging .paginate_button.disabled { color: #d1d5db; cursor: not-allowed; }
    .ep-badge { font-size:11px; font-weight:600; padding:3px 10px; border-radius:20px; display:inline-block; }
    .ep-badge-green  { background:#dcfce7; color:#166534; }
    .ep-badge-yellow { background:#fef9c3; color:#854d0e; }
    .ep-badge-red    { background:#fee2e2; color:#991b1b; }
    .ep-badge-blue   { background:#dbeafe; color:#1e40af; }
    .ep-badge-gray   { background:#f3f4f6; color:#4b5563; }
    .ep-actions { display:flex; align-items:center; justify-content:center; gap:5px; flex-wrap:wrap; }
    .ep-btn-icon { width:28px; height:28px; display:inline-flex; align-items:center; justify-content:center; border:none; border-radius:7px; cursor:pointer; transition:opacity .15s; }
    .ep-btn-icon:hover { opacity:.8; }
    .ep-btn-teal   { background:#E0F5EE; color:#0D9E75; }
    .ep-btn-green  { background:#dcfce7; color:#16a34a; }
    .ep-btn-yellow { background:#fef9c3; color:#ca8a04; }
    .ep-btn-red    { background:#fee2e2; color:#dc2626; }
    .ep-btn-blue   { background:#dbeafe; color:#1e40af; }
    .ep-dt-name { font-weight:600; color:#111; font-size:13px; }
    .ep-dt-sub  { font-size:11px; color:#9ca3af; margin-top:1px; }
    .ep-dt-center { text-align:center; font-weight:600; color:#374151; }
    table.ep-dt td.dtr-control::before { background: #0D9E75 !important; border-color: #0D9E75 !important; }
    @media (max-width: 640px) { .ep-dt-toolbar .dt-search input { width: 160px; } }
    </style>

    <script>
    window.epDT = function(selector, opts) {
        var defaults = {
            responsive: true,
            language: {
                search: '', searchPlaceholder: 'Rechercher...', lengthMenu: 'Afficher _MENU_ lignes',
                info: '_START_\u2013_END_ sur _TOTAL_', infoEmpty: '0 r\u00e9sultat', infoFiltered: '(filtr\u00e9 sur _MAX_)',
                zeroRecords: 'Aucun r\u00e9sultat', emptyTable: 'Tableau vide',
                paginate: { first: '\u00ab', previous: '\u2039', next: '\u203a', last: '\u00bb' }
            },
            dom: '<"ep-dt-toolbar"l<"dt-search"f>>rt<"ep-dt-foot"i<"dt-paging"p>>',
            pageLength: 15,
            lengthMenu: [[10, 15, 25, 50, -1], [10, 15, 25, 50, 'Tous']],
        };
        return $(selector).DataTable($.extend(true, defaults, opts || {}));
    };

    // ── API Toast globale (pour AJAX) ──
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
        el.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2">' + (icons[type] || icons.info) + '</svg><span></span>';
        el.querySelector('span').textContent = message;
        wrap.appendChild(el);
        setTimeout(function(){ el.classList.add('closing'); setTimeout(function(){ el.remove(); }, 200); }, 4000);
    };
    </script>

    @stack('scripts')
<script src="{{ asset('js/forms-enhanced.js') }}"></script>
</body>
</html>
