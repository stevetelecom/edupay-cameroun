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
        .pill{display:inline-block;font-size:11px;padding:3px 9px;border-radius:20px;font-weight:500;}
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
        .main-content{flex:1;padding:22px 24px;background:#f5f6f7;}
        .prog{height:5px;background:#eee;border-radius:3px;overflow:hidden;margin-top:6px;}
        .pfill{height:100%;background:var(--ep-teal);border-radius:3px;}
        .row{display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f0f0f0;font-size:13px;}
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
    </style>

    @stack('styles')
</head>
<body class="h-full">

    {{-- ── Header parent ── --}}
    <div class="app-header">
        <div class="logo-t">Edu<span>Pay</span></div>
        <div style="display:flex;align-items:center;gap:12px;">
            <span style="font-size:12px;color:rgba(255,255,255,.65);">{{ Auth::user()->name }}</span>
            <div class="av" style="background:var(--ep-teal);color:#fff;">
                {{ Str::of(Auth::user()->name)->explode(' ')->map(fn($w) => Str::substr($w,0,1))->join('') }}
            </div>
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" style="background:transparent;color:rgba(255,255,255,.5);border:1px solid rgba(255,255,255,.2);padding:6px 12px;border-radius:20px;font-size:11px;cursor:pointer;">
                    Déconnexion
                </button>
            </form>
        </div>
    </div>

    <div class="main-content">

        @if (session('success'))
            <div style="margin-bottom:16px;background:#E0F5EE;border:1px solid #9FE1CB;color:#085041;padding:12px 16px;border-radius:var(--radius-md);font-size:13px;">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div style="margin-bottom:16px;background:#FBEAEA;border:1px solid #f3c4c4;color:#9B2C2C;padding:12px 16px;border-radius:var(--radius-md);font-size:13px;">
                {{ session('error') }}
            </div>
        @endif
        @if (session('info'))
            <div style="margin-bottom:16px;background:#E6F0FB;border:1px solid #c4dbf3;color:#1A4F8A;padding:12px 16px;border-radius:var(--radius-md);font-size:13px;">
                {{ session('info') }}
            </div>
        @endif

        @yield('content')
    </div>

    @stack('scripts')
</body>
</html>
