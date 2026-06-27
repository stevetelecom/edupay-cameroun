<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'Mon espace') — EduPay Cameroun</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

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
        .epcard{background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);padding:18px;}
        .pill{display:inline-block;font-size:11px;padding:3px 9px;border-radius:20px;font-weight:500;white-space:nowrap;line-height:1.4;}
        .pg{background:#E0F5EE;color:#085041;}.pa{background:#FEF3DC;color:#8B5E10;}
        .pr{background:#FBEAEA;color:#9B2C2C;}.pb{background:#E6F0FB;color:#1A4F8A;}
        .g2{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
        .g4{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;}
        .kpi{background:#f8f9fa;border-radius:var(--radius-md);padding:16px;text-align:center;}
        .kval{font-size:22px;font-weight:700;color:#1a1a2e;}
        .klbl{font-size:11px;color:#888;margin-top:4px;}
        .seclbl{font-size:11px;font-weight:600;color:#999;text-transform:uppercase;letter-spacing:.06em;margin:18px 0 10px;}
        .btn-p{background:var(--ep-teal);color:#fff;border:none;padding:11px 20px;border-radius:var(--radius-md);font-size:13px;font-weight:500;cursor:pointer;transition:background .15s;text-decoration:none;display:inline-block;text-align:center;}
        .btn-p:hover{background:var(--ep-teal2);color:#fff;}
        .btn-o{background:transparent;color:var(--ep-teal);border:2px solid var(--ep-teal);padding:9px 18px;border-radius:var(--radius-md);font-size:13px;font-weight:500;cursor:pointer;transition:all .15s;text-decoration:none;display:inline-block;text-align:center;}
        .btn-o:hover{background:var(--ep-teal-lt);}
        .btn-r{background:var(--ep-red);color:#fff;border:none;padding:9px 18px;border-radius:var(--radius-md);font-size:13px;font-weight:500;cursor:pointer;text-decoration:none;display:inline-block;text-align:center;width:100%;}
        .btn-r:hover{background:#C13333;}
        .inp{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:var(--radius-md);font-size:13px;margin-bottom:12px;outline:none;transition:border .15s;}
        .inp:focus{border-color:var(--ep-teal);}
        .lbl{font-size:11px;color:#666;margin-bottom:5px;font-weight:500;}
        .app-header{background:var(--ep-navy);color:#fff;padding:13px 24px;display:flex;align-items:center;justify-content:space-between;}
        .app-body{display:flex;min-height:calc(100vh - 58px);}
        .sidebar{width:200px;flex-shrink:0;padding:14px;background:#fff;border-right:1px solid var(--border);}
        .main-content{flex:1;padding:22px 24px;background:#f5f6f7;overflow-y:auto;}
        .sbar-item{display:flex;align-items:center;gap:9px;padding:9px 12px;border-radius:var(--radius-md);font-size:13px;color:#555;cursor:pointer;margin-bottom:2px;text-decoration:none;}
        .sbar-item svg{width:15px;height:15px;flex-shrink:0;}
        .sbar-item.on{background:var(--ep-teal-lt);color:#085041;font-weight:600;}
        .sbar-item.on svg{stroke:#0D9E75;}
        .sbar-item:hover:not(.on){background:#f0f0f0;}
        .sbar-item.disabled{color:#bbb;cursor:not-allowed;}
        .sbar-item.disabled:hover{background:transparent;}
        .badge-cnt{background:var(--ep-red-lt);color:#9B2C2C;border-radius:10px;padding:1px 7px;font-size:10px;margin-left:auto;}
        .badge-soon{background:#eee;color:#999;border-radius:10px;padding:1px 7px;font-size:9px;margin-left:auto;}
        .prog{height:5px;background:#eee;border-radius:3px;overflow:hidden;margin-top:6px;}
        .pfill{height:100%;background:var(--ep-teal);border-radius:3px;}
        .row{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;padding:10px 0;border-bottom:1px solid #f0f0f0;font-size:13px;}
        .row span{flex-shrink:0;color:#666;}
        .row strong{text-align:right;}
        .row:last-child{border-bottom:none;}
        .av{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;}
        .pay-page{background:#f1f3f5;min-height:calc(100vh - 58px);padding:24px 28px;}
        table.ep-table{width:100%;border-collapse:collapse;font-size:13px;}
        table.ep-table th{text-align:left;font-size:11px;font-weight:600;color:#999;text-transform:uppercase;letter-spacing:.05em;padding:10px 8px;border-bottom:2px solid #f0f0f0;}
        table.ep-table td{padding:11px 8px;border-bottom:1px solid #f0f0f0;}
        table.ep-table tr:last-child td{border-bottom:none;}
        table.ep-table tr:hover td{background:#fafbfc;}
        .logo-t{font-size:16px;font-weight:700;}
        .logo-t span{color:#5DCAA5;}
        .toast-wrap{position:fixed;top:18px;right:18px;z-index:9999;display:flex;flex-direction:column;gap:10px;}
        .toast{display:flex;align-items:flex-start;gap:10px;min-width:280px;max-width:360px;padding:13px 16px;border-radius:var(--radius-md);box-shadow:0 6px 20px rgba(0,0,0,.15);font-size:13px;font-weight:500;animation:toast-in .25s ease-out;}
        .toast.t-success{background:#085041;color:#fff;}
        .toast.t-error{background:#9B2C2C;color:#fff;}
        .toast.t-info{background:#1A4F8A;color:#fff;}
        .toast svg{flex-shrink:0;margin-top:1px;}
        .toast.closing{animation:toast-out .2s ease-in forwards;}
        @keyframes toast-in{from{opacity:0;transform:translateX(30px);}to{opacity:1;transform:translateX(0);}}
        @keyframes toast-out{from{opacity:1;transform:translateX(0);}to{opacity:0;transform:translateX(30px);}}
    </style>


    <style>
        /* ── Système Modal EduPay ── */
        .ep-modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9000;align-items:center;justify-content:center;padding:16px;}
        .ep-modal-overlay.open{display:flex;}
        .ep-modal{background:#fff;border-radius:var(--radius-lg);width:100%;display:flex;flex-direction:column;max-height:90vh;box-shadow:0 20px 60px rgba(0,0,0,.2);}
        .ep-modal-sm{max-width:420px;}
        .ep-modal-md{max-width:560px;}
        .ep-modal-lg{max-width:720px;}
        .ep-modal-head{display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid #f0f0f0;flex-shrink:0;}
        .ep-modal-head h3{font-size:15px;font-weight:700;margin:0;}
        .ep-modal-close{background:none;border:none;font-size:22px;cursor:pointer;color:#aaa;line-height:1;padding:2px 6px;border-radius:4px;}
        .ep-modal-close:hover{background:#f5f5f5;color:#333;}
        .ep-modal-body{padding:20px;overflow-y:auto;flex:1;}
        .ep-modal-foot{display:flex;justify-content:flex-end;gap:10px;padding:14px 20px;border-top:1px solid #f0f0f0;flex-shrink:0;}
        .ep-modal-danger .ep-modal-head{border-bottom-color:#FBEAEA;}
        .ep-modal-danger .ep-modal-head h3{color:var(--ep-red);}

        /* ───────────────────────── MEDIA QUERIES RESPONSIVITÉ ───────────────────────── */

        @media (max-width: 1024px) {
            .g2 { grid-template-columns: repeat(2,1fr) !important; }
            .g4 { grid-template-columns: repeat(2,1fr) !important; }
        }

        @media (max-width: 768px) {
            .app-body { flex-direction: column; }
            .sidebar {
                width: 100%;
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                padding: 12px 14px 10px;
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
            .g2, .g4 { grid-template-columns: 1fr !important; }
            table.ep-table { font-size: 12px; }
            table.ep-table th { font-size: 10px; padding: 8px 6px; }
            table.ep-table td { padding: 8px 6px; }
            .app-header { flex-direction: column; align-items: flex-start; gap: 12px; }
        }

        @media (max-width: 480px) {
            body { font-size: 13px; }
            .app-header { padding: 10px 12px; }
            .main-content { padding: 12px 10px; }
            .sidebar { gap: 6px; }
            .sbar-item { flex: 1 1 100%; }
            .epcard { padding: 12px; }
            .kpi { padding: 12px; }
            .kval { font-size: 18px; }
            .klbl { font-size: 10px; }
            .btn-p, .btn-o, .btn-r { padding: 9px 14px; font-size: 12px; }
            .inp { padding: 8px 10px; font-size: 12px; margin-bottom: 8px; }
            .lbl { font-size: 10px; margin-bottom: 3px; }
            .seclbl { font-size: 10px; margin: 12px 0 6px; }
            .row { font-size: 12px; padding: 8px 0; gap: 6px; }
            .badge-cnt { font-size: 9px; padding: 1px 5px; }
            table.ep-table { font-size: 11px; }
            table.ep-table th { font-size: 9px; padding: 6px 4px; }
            table.ep-table td { padding: 6px 4px; }
            .toast { min-width: 260px; max-width: 300px; padding: 10px 12px; font-size: 12px; }
            .ep-modal-sm, .ep-modal-md, .ep-modal-lg, .ep-modal-xl { max-width: 100% !important; }
            .ep-modal-body { padding: 14px; }
            .ep-modal-foot { padding: 10px 14px; gap: 6px; }
            .pill { font-size: 10px; padding: 2px 7px; }
        }

        @media (max-width: 320px) {
            .btn-p, .btn-o, .btn-r { padding: 7px 10px; font-size: 11px; }
            .inp { padding: 6px 8px; font-size: 11px; }
            .kval { font-size: 16px; }
        }

        /* ───────────────────────── FIN MEDIA QUERIES ───────────────────────── */
    </style>

    @stack('styles')
</head>
<body class="h-full">

    @php
        $estSoloLayout = in_array(Auth::user()->profil ?? 'parent', ['eleve', 'etudiant']);
        $headerLabel = $estSoloLayout
            ? Auth::user()->name . (Auth::user()->etablissement ? ' — ' . Auth::user()->etablissement->nom : '')
            : Auth::user()->name;
    @endphp

    {{-- ── Header parent ── --}}
    <div class="app-header">
        <div class="logo-t">Edu<span>Pay</span></div>
        <div style="display:flex;align-items:center;gap:12px;">
            <span style="font-size:12px;color:rgba(255,255,255,.65);">{{ $headerLabel }}</span>
            <div class="relative" style="position:relative;">
                <button onclick="toggleProfilPayeur()"
                        title="Voir mon profil"
                        style="width:36px;height:36px;border-radius:50%;background:var(--ep-teal);color:#fff;
                               font-size:13px;font-weight:700;border:none;cursor:pointer;
                               display:flex;align-items:center;justify-content:center;transition:opacity .15s;"
                        onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                    {{ Auth::user()->initiales }}
                </button>

                {{-- Dropdown profil payeur --}}
                <div id="dropdown-profil-payeur"
                     style="display:none;position:absolute;right:0;top:44px;width:280px;
                            background:#fff;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.12);
                            border:1px solid #e5e7eb;z-index:999;">

                    {{-- En-tête --}}
                    <div style="padding:16px;border-bottom:1px solid #f0f0f0;">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:46px;height:46px;border-radius:50%;background:var(--ep-teal);
                                        color:#fff;font-size:16px;font-weight:700;flex-shrink:0;
                                        display:flex;align-items:center;justify-content:center;">
                                {{ Auth::user()->initiales }}
                            </div>
                            <div>
                                <div style="font-size:14px;font-weight:700;color:#111;">
                                    {{ Auth::user()->name }}
                                </div>
                                <div style="font-size:11px;color:#888;">{{ Auth::user()->email ?? Auth::user()->telephone }}</div>
                                <span style="font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px;
                                             background:#E1F5EE;color:#085041;border:1px solid #5DCAA5;margin-top:4px;display:inline-block;">
                                    {{ ucfirst(Auth::user()->getRoleNames()->first() ?? 'Parent') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Infos --}}
                    <div style="padding:12px 16px;border-bottom:1px solid #f0f0f0;">
                        <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 0;">
                            <span style="color:#888;">Téléphone</span>
                            <span style="font-weight:600;color:#333;">{{ Auth::user()->telephone ?? '—' }}</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 0;">
                            <span style="color:#888;">Ville</span>
                            <span style="font-weight:600;color:#333;">{{ Auth::user()->ville ?? '—' }}</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 0;">
                            <span style="color:#888;">Statut</span>
                            <span style="color:#0D9E75;font-weight:600;">● Connecté</span>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div style="padding:12px 16px;">
                        <a href="{{ route('payeur.profil.index') }}"
                           style="display:flex;align-items:center;gap:8px;font-size:13px;
                                  color:#0D9E75;font-weight:500;text-decoration:none;padding:4px 0;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                            Voir mon profil
                        </a>
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
                                Déconnexion
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" style="background:transparent;color:rgba(255,255,255,.5);border:1px solid rgba(255,255,255,.2);padding:6px 12px;border-radius:20px;font-size:11px;cursor:pointer;">
                    Déconnexion
                </button>
            </form>
        </div>
    </div>

    <div class="app-body">

        {{-- ── Sidebar ── --}}
        <div class="sidebar">
            <a href="{{ route('payeur.dashboard') }}" id="tab-dashboard" class="sbar-item {{ request()->routeIs('payeur.dashboard') ? 'on' : '' }}"
               @if(request()->routeIs('payeur.dashboard')) onclick="if(window.showVuePane){showVuePane('resume');return false;}" @endif>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
                Tableau de bord
            </a>

            @unless($estSoloLayout)
            <a href="{{ route('payeur.mes-enfants') }}" id="tab-children"
               class="sbar-item {{ request()->routeIs('payeur.dashboard') && request()->server('QUERY_STRING') === '' ? '' : '' }}"
               onclick="
                 const sec = null;
                 if(sec){ sec.scrollIntoView({behavior:'smooth', block:'start'}); }
                 document.querySelectorAll('.sbar-item').forEach(el => el.classList.remove('on'));
                 this.classList.add('on');
               ">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Mes enfants
            </a>
            @endunless

            <a href="{{ route('payeur.historique') }}" class="sbar-item {{ request()->routeIs('payeur.historique') ? 'on' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                Historique
            </a>

            <a href="{{ route('payeur.recus.index') }}" class="sbar-item {{ request()->routeIs('payeur.recus.*') ? 'on' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Reçus &amp; Certificats
            </a>

            <a href="{{ route('payeur.reclamations.index') }}" class="sbar-item {{ request()->routeIs('payeur.reclamations.*') ? 'on' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Réclamations
                @php
                    $nbReclamationsOuvertes = \App\Models\Reclamation::where('user_id', Auth::id())
                        ->whereIn('statut', ['ouvert', 'en_cours'])
                        ->count();
                @endphp
                @if($nbReclamationsOuvertes > 0)
                    <span class="badge-cnt">{{ $nbReclamationsOuvertes }}</span>
                @endif
            </a>

            <a href="{{ route('payeur.profil.index') }}" class="sbar-item {{ request()->routeIs('payeur.profil.*') ? 'on' : '' }}" style="margin-top:4px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                Profil &amp; notifications
            </a>
        </div>

        {{-- ── Contenu principal ── --}}
        <div class="main-content">

            @yield('content')
        </div>
    </div>

    {{-- ── Toasts de notification ── --}}
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
        document.querySelectorAll('[data-toast]').forEach(function (toast) {
            setTimeout(function () {
                toast.classList.add('closing');
                setTimeout(function () { toast.remove(); }, 200);
            }, 4000);
        });
    </script>


    @stack('modals')

    <script>
        // Ouvrir  : epModal.open('monModalId')
        // Fermer  : epModal.close('monModalId')  ou  epModal.closeAll()
        var epModal = {
            open: function(id) {
                var el = document.getElementById(id);
                if (!el) return;
                el.classList.add('open');
                // Si modal rattacher → afficher liste établissements
                if (id === 'modal-rattacher') {
                    setTimeout(function() {
                        var liste = document.getElementById('m-etab-liste');
                        if (liste) liste.style.display = 'block';
                    }, 80);
                }
                var handler = function(e) {
                    if (e.target === el) { epModal.close(id); el.removeEventListener('click', handler); }
                };
                el.addEventListener('click', handler);
            },
            close: function(id) {
                var el = document.getElementById(id);
                if (el) el.classList.remove('open');
            },
            closeAll: function() {
                document.querySelectorAll('.ep-modal-overlay.open').forEach(function(el) {
                    el.classList.remove('open');
                });
            }
        };
        window.epModal = epModal;
        document.querySelectorAll('[data-modal-open]').forEach(function(btn){
            btn.addEventListener('click', function(){ epModal.open(btn.dataset.modalOpen); });
        });
        document.querySelectorAll('[data-modal-close]').forEach(function(btn){
            btn.addEventListener('click', function(){ epModal.close(btn.dataset.modalClose); });
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') epModal.closeAll();
        });
    </script>

    @stack('scripts')
<script>
// Auto-scroll vers #mes-enfants si present dans l'URL
document.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash === '#mes-enfants') {
        const sec = null;
        if (sec) {
            setTimeout(() => {
                sec.scrollIntoView({behavior: 'smooth', block: 'start'});
                const tab = document.getElementById('tab-children');
                if (tab) {
                    document.querySelectorAll('.sbar-item').forEach(el => el.classList.remove('on'));
                    tab.classList.add('on');
                }
            }, 200);
        }
    }
});

function toggleProfilPayeur() {
    var el = document.getElementById('dropdown-profil-payeur');
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
document.addEventListener('click', function(e) {
    var dropdown = document.getElementById('dropdown-profil-payeur');
    if (!dropdown) return;
    var btn = dropdown.previousElementSibling;
    if (!dropdown.contains(e.target) && !btn.contains(e.target)) {
        dropdown.style.display = 'none';
    }
});
</script>
</body>
</html>
