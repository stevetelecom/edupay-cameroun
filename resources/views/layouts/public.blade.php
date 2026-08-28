<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'EduPay Cameroun')</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,400,0,0" />
<style>
:root{--ep-navy:#0B2545;--ep-teal:#0D9E75;--ep-teal2:#0A8562;--ep-teal-lt:#E0F5EE;--ep-teal-mid:#9FE1CB;--ep-gold:#E8A020;--ep-gold-lt:#FEF3DC;--ep-red:#D94040;--ep-red-lt:#FBEAEA;--ep-blue-lt:#E6F0FB;--ep-purple-lt:#EDE9FE;--border:rgba(0,0,0,0.09);--radius-md:8px;--radius-lg:12px;}
*{box-sizing:border-box;margin:0;padding:0;}
html,body{height:100%;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:14px;background:#f1f3f5;color:#1a1a2e;}
.epcard{background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);padding:18px;}
.pill{display:inline-block;font-size:11px;padding:3px 9px;border-radius:20px;font-weight:500;}
.pg{background:#E0F5EE;color:#085041;}.pa{background:#FEF3DC;color:#8B5E10;}
.pr{background:#FBEAEA;color:#9B2C2C;}.pb{background:#E6F0FB;color:#1A4F8A;}
.g2{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.g3{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;}
.g4{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;}
.kpi{background:#f8f9fa;border-radius:var(--radius-md);padding:16px;text-align:center;}
.kval{font-size:22px;font-weight:700;color:#1a1a2e;}
.klbl{font-size:11px;color:#888;margin-top:4px;}
.seclbl{font-size:11px;font-weight:600;color:#999;text-transform:uppercase;letter-spacing:.06em;margin:18px 0 10px;}
.btn-p{background:var(--ep-teal);color:#fff;border:none;padding:11px 20px;border-radius:var(--radius-md);font-size:13px;font-weight:500;cursor:pointer;width:100%;transition:background .15s;text-decoration:none;display:block;text-align:center;}
.btn-p:hover{background:var(--ep-teal2);}
.btn-o{background:transparent;color:var(--ep-teal);border:2px solid var(--ep-teal);padding:9px 18px;border-radius:var(--radius-md);font-size:13px;font-weight:500;cursor:pointer;width:100%;transition:all .15s;text-decoration:none;display:block;text-align:center;}
.btn-o:hover{background:var(--ep-teal-lt);}
.inp{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:var(--radius-md);font-size:13px;margin-bottom:12px;outline:none;transition:border .15s;}
.inp:focus{border-color:var(--ep-teal);}
.lbl{font-size:11px;color:#666;margin-bottom:5px;font-weight:500;}
.divider{height:1px;background:#f0f0f0;margin:14px 0;}
.ep-body2{padding:24px 28px;background:#f1f3f5;}
/* HERO */
.hero-band{background:var(--ep-navy);color:#fff;}
.hero-top{display:flex;align-items:center;justify-content:space-between;padding:14px 28px;border-bottom:1px solid rgba(255,255,255,.08);}
.logo-t{font-size:20px;font-weight:700;letter-spacing:-.3px;}
.logo-t span{color:#5DCAA5;}
.hero-main{padding:48px 28px 36px;text-align:center;display:flex;flex-direction:column;align-items:center;}
.hero-tag{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);border-radius:20px;padding:5px 14px;font-size:11px;color:rgba(255,255,255,.75);margin-bottom:18px;}
.hero-h1{font-size:36px;font-weight:700;line-height:1.2;max-width:620px;margin:0 auto 14px;}
.hero-h1 em{font-style:normal;color:#5DCAA5;}
.hero-sub{font-size:14px;color:rgba(255,255,255,.6);max-width:440px;margin-bottom:28px;line-height:1.65;}
.hero-btns{display:flex;gap:12px;flex-wrap:wrap;}
.hbtn-main{background:var(--ep-teal);color:#fff;border:none;padding:13px 26px;border-radius:var(--radius-md);font-size:14px;font-weight:500;cursor:pointer;transition:background .15s;text-decoration:none;}
.hbtn-main:hover{background:var(--ep-teal2);}
.hbtn-ghost{background:rgba(255,255,255,.08);color:#fff;border:1px solid rgba(255,255,255,.2);padding:13px 26px;border-radius:var(--radius-md);font-size:14px;cursor:pointer;text-decoration:none;}
.hero-stats{display:flex;border-top:1px solid rgba(255,255,255,.08);}
.hstat{flex:1;padding:18px 28px;border-right:1px solid rgba(255,255,255,.08);text-align:center;}
.material-symbols-outlined{font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;font-size:18px;display:inline-flex;align-items:center;justify-content:center;}
.hstat:last-child{border-right:none;}
.hstat-v{font-size:22px;font-weight:700;color:#5DCAA5;}
.hstat-l{font-size:11px;color:rgba(255,255,255,.5);margin-top:3px;}
/* FEATURES */
.feat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;}
.feat-card{background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;position:relative;overflow:hidden;}
.feat-icon{width:42px;height:42px;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;margin-bottom:14px;}
.feat-icon svg{width:22px;height:22px;}
.icon-round{width:40px;height:40px;border-radius:14px;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0;}
.icon-round svg{width:18px;height:18px;}
.icon-sm{width:30px;height:30px;border-radius:10px;}
.feat-title{font-size:14px;font-weight:600;margin-bottom:7px;}
.feat-desc{font-size:12px;color:#777;line-height:1.65;}
.feat-line{position:absolute;top:0;left:0;width:3px;height:100%;}
/* ABOUT */
.mission-card{background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);padding:22px;border-left:3px solid var(--ep-teal);}
.team-card{background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);padding:18px;text-align:center;}
.team-av{width:54px;height:54px;border-radius:50%;margin:0 auto 10px;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;}
.value-card{padding:16px;border-radius:var(--radius-md);background:#f8f9fa;border-left:3px solid transparent;}
/* TESTIMONIALS */
.testi-card{background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);padding:22px;}
.testi-quote{font-size:36px;color:var(--ep-teal-mid);line-height:1;margin-bottom:6px;font-family:Georgia,serif;}
.testi-text{font-size:13px;color:#333;line-height:1.75;margin-bottom:16px;font-style:italic;}
.testi-author{display:flex;align-items:center;gap:12px;}
.stars{color:var(--ep-gold);font-size:14px;margin-bottom:7px;}
.av{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;}
/* FOOTER */
.ep-footer{background:var(--ep-navy);color:rgba(255,255,255,.75);padding:36px 28px 22px;}
.footer-grid{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:28px;margin-bottom:28px;}
.footer-logo{font-size:20px;font-weight:700;color:#fff;margin-bottom:8px;}
.footer-logo span{color:#5DCAA5;}
.footer-desc{font-size:12px;color:rgba(255,255,255,.5);line-height:1.7;margin-bottom:14px;}
.footer-badge{display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:6px;padding:5px 10px;font-size:11px;color:rgba(255,255,255,.6);margin-right:6px;margin-bottom:6px;}
.footer-col-title{font-size:11px;font-weight:600;color:rgba(255,255,255,.9);text-transform:uppercase;letter-spacing:.07em;margin-bottom:12px;}
.footer-link{display:block;font-size:12px;color:rgba(255,255,255,.5);margin-bottom:8px;cursor:pointer;text-decoration:none;}
.footer-link:hover{color:rgba(255,255,255,.85);}
.footer-bottom{border-top:1px solid rgba(255,255,255,.08);padding-top:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;}
.footer-legal{font-size:11px;color:rgba(255,255,255,.35);}
.footer-socials{display:flex;gap:8px;}
.social-btn{width:32px;height:32px;border-radius:6px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;font-size:12px;cursor:pointer;color:rgba(255,255,255,.6);font-weight:600;text-decoration:none;transition:background .2s ease,color .2s ease,transform .2s ease;}.social-btn:hover{background:var(--ep-teal);color:#fff;transform:translateY(-2px);}
.cert-badge{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:6px;padding:4px 10px;font-size:10px;color:rgba(255,255,255,.45);}
.certif{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;}

/* ───────────────────────── MEDIA QUERIES RESPONSIVITÉ ───────────────────────── */

/* Tablette (768px et moins) */
@media (max-width: 768px) {
  .g2, .g3, .g4 { grid-template-columns: 1fr !important; }
  .feat-grid { grid-template-columns: 1fr !important; }
  .footer-grid { grid-template-columns: 1fr !important; gap: 20px; }
  .inp-row { grid-template-columns: 1fr !important; }
  .hero-stats { flex-direction: column; }
  .hstat { border-right: none !important; border-bottom: 1px solid rgba(255,255,255,.08); padding: 14px 28px; }
  .hstat:last-child { border-bottom: none; }
  .hero-h1 { font-size: 28px; }
  .form-body { padding: 24px 16px; }
  .form-card, .form-card-wide { padding: 20px; max-width: 100%; }
  .form-header { flex-direction: column; align-items: flex-start; gap: 12px; }
  .form-header .logo-t { font-size: 16px; }
}

/* Mobile (480px et moins) */
@media (max-width: 480px) {
  body { font-size: 13px; }
  .hero-main { padding: 32px 16px 24px; }
  .hero-h1 { font-size: 22px; margin-bottom: 10px; }
  .hero-sub { font-size: 13px; margin-bottom: 20px; }
  .hero-btns { flex-direction: column; gap: 8px; }
  .hbtn-main, .hbtn-ghost { width: 100%; }
  .ep-body2 { padding: 16px 16px; }
  .form-body { padding: 16px 12px; }
  .form-card, .form-card-wide { padding: 16px; }
  .form-title { font-size: 16px; margin-bottom: 6px; }
  .form-sub { font-size: 12px; margin-bottom: 16px; }
  .btn-p, .btn-o { padding: 10px 16px; font-size: 12px; }
  .inp { padding: 9px 10px; font-size: 12px; margin-bottom: 10px; }
  .lbl { font-size: 10px; margin-bottom: 4px; }
  .select { padding: 9px 10px; font-size: 12px; margin-bottom: 10px; }
  .seclbl { font-size: 10px; margin: 14px 0 8px; }
  .check-row { font-size: 11px; gap: 8px; }
  .feat-title { font-size: 13px; }
  .feat-desc { font-size: 11px; }
  .footer-col-title { font-size: 10px; }
  .footer-link { font-size: 11px; }
  .footer-legal { font-size: 10px; }
  .logo-t { font-size: 16px; }
  .app-header { padding: 10px 16px; }
  .sidebar { width: 0; display: none; }
  .app-body { flex-direction: column; }
  .main-content { padding: 16px 12px; }
  .pay-page { padding: 16px 12px; }
}

/* Très petit mobile (320px) */
@media (max-width: 320px) {
  .hero-h1 { font-size: 18px; }
  .form-card, .form-card-wide { padding: 12px; }
  .form-title { font-size: 14px; }
  .btn-p, .btn-o { padding: 8px 12px; font-size: 11px; }
  .inp { padding: 8px 8px; font-size: 11px; }
}

/* ───────────────────────── FIN MEDIA QUERIES ───────────────────────── */

.form-header{background:var(--ep-navy);color:#fff;padding:16px 28px;display:flex;align-items:center;justify-content:space-between;}
.form-body{flex:1;display:flex;align-items:flex-start;justify-content:center;padding:32px 20px;}
.form-card{background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);padding:28px;width:100%;max-width:520px;}
.form-card-wide{background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);padding:28px;width:100%;max-width:720px;}
.form-title{font-size:18px;font-weight:700;margin-bottom:4px;}
.form-sub{font-size:13px;color:#888;margin-bottom:22px;}
.form-section{font-size:11px;font-weight:700;color:#aaa;text-transform:uppercase;letter-spacing:.07em;margin:18px 0 10px;padding-bottom:6px;border-bottom:1px solid #f0f0f0;}
.select{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:var(--radius-md);font-size:13px;margin-bottom:12px;background:#fff;outline:none;}
.select:focus{border-color:var(--ep-teal);}
.check-row{display:flex;align-items:flex-start;gap:10px;margin-bottom:14px;font-size:12px;color:#555;line-height:1.5;}
.check-row input{margin-top:2px;flex-shrink:0;}
.inp-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.textarea{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:var(--radius-md);font-size:13px;margin-bottom:12px;resize:vertical;min-height:80px;outline:none;font-family:inherit;}
.textarea:focus{border-color:var(--ep-teal);}
/* APP LAYOUT */
.app-header{background:var(--ep-navy);color:#fff;padding:13px 24px;display:flex;align-items:center;justify-content:space-between;}
.app-body{display:flex;min-height:calc(100vh - 58px);}
.sidebar{width:200px;flex-shrink:0;padding:14px;background:#fff;border-right:1px solid var(--border);}
.sbar-item{display:flex;align-items:center;gap:9px;padding:9px 12px;border-radius:var(--radius-md);font-size:13px;color:#555;cursor:pointer;margin-bottom:2px;text-decoration:none;}
.sbar-item svg{width:15px;height:15px;flex-shrink:0;}
.sbar-item.on{background:var(--ep-teal-lt);color:#085041;font-weight:600;}
.sbar-item:hover:not(.on){background:#f0f0f0;}
.main-content{flex:1;padding:22px 24px;background:#f5f6f7;overflow-y:auto;}
.prog{height:5px;background:#eee;border-radius:3px;overflow:hidden;margin-top:6px;}
.pfill{height:100%;background:var(--ep-teal);border-radius:3px;}
.dot{width:8px;height:8px;border-radius:50%;display:inline-block;}
.row{display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f0f0f0;font-size:13px;}
.row:last-child{border-bottom:none;}
.badge-cnt{background:#FBEAEA;color:#9B2C2C;border-radius:10px;padding:1px 7px;font-size:10px;margin-left:4px;}
.pay-page{background:#f1f3f5;min-height:calc(100vh - 58px);padding:24px 28px;}
.toast-wrap{position:fixed;top:18px;right:18px;z-index:9999;display:flex;flex-direction:column;gap:10px;}
.toast{display:flex;align-items:flex-start;gap:10px;min-width:280px;max-width:360px;padding:13px 16px;border-radius:var(--radius-md);box-shadow:0 6px 20px rgba(0,0,0,.15);font-size:13px;font-weight:500;animation:toast-in .25s ease-out;}
.toast.t-success{background:#085041;color:#fff;}
.toast.t-error{background:#9B2C2C;color:#fff;}
.toast.t-info{background:#1A4F8A;color:#fff;}
.toast svg{flex-shrink:0;margin-top:1px;}
.toast.closing{animation:toast-out .2s ease-in forwards;}
@keyframes toast-in{from{opacity:0;transform:translateX(30px);}to{opacity:1;transform:translateX(0);}}
@keyframes toast-out{from{opacity:1;transform:translateX(0);}to{opacity:0;transform:translateX(30px);}}

    


    /* ══ NAVBAR PUBLIQUE ══ */
    .pub-nav{background:#0B2545;border-bottom:1px solid rgba(255,255,255,.08);position:sticky;top:0;z-index:100;}
    .pub-nav-inner{display:flex;align-items:center;justify-content:space-between;padding:13px 20px;}
    .nav-desk{display:flex;align-items:center;gap:4px;}
    .nav-link{color:rgba(255,255,255,.65);text-decoration:none;font-size:13px;padding:7px 12px;border-radius:20px;transition:all .15s;white-space:nowrap;}
    .nav-link:hover{color:#fff;background:rgba(255,255,255,.08);}
    .nav-link-active{color:#fff!important;background:rgba(255,255,255,.12)!important;font-weight:600;}
    .nav-sep{width:1px;height:18px;background:rgba(255,255,255,.15);margin:0 4px;}
    .nav-btn-ghost{color:rgba(255,255,255,.8);text-decoration:none;font-size:13px;padding:7px 14px;border-radius:20px;border:1px solid rgba(255,255,255,.25);white-space:nowrap;}
    .nav-btn-ghost:hover{background:rgba(255,255,255,.08);}
    .nav-btn-main{color:#fff;text-decoration:none;font-size:13px;font-weight:600;padding:8px 16px;border-radius:20px;background:#0D9E75;white-space:nowrap;}
    .nav-btn-main:hover{background:#0A8562;}
    .nav-burger{display:none;flex-direction:column;gap:5px;background:none;border:none;cursor:pointer;padding:6px;border-radius:8px;}
    .nav-burger span{display:block;width:22px;height:2px;background:rgba(255,255,255,.85);border-radius:2px;transition:all .25s;}
    .nav-burger.open span:nth-child(1){transform:translateY(7px) rotate(45deg);}
    .nav-burger.open span:nth-child(2){opacity:0;}
    .nav-burger.open span:nth-child(3){transform:translateY(-7px) rotate(-45deg);}
    @media(max-width:768px){
      .pub-nav-inner{padding:11px 16px;}
      .nav-desk{display:none;}
      .nav-burger{display:flex;}
    }

    /* ══ TOGGLE LANGUE ══ */
    .lang-toggle{position:relative;display:inline-flex;align-items:center;}
    .lang-btn{display:flex;align-items:center;gap:6px;background:rgba(255,255,255,.1);
              border:1px solid rgba(255,255,255,.2);border-radius:20px;
              padding:6px 12px;cursor:pointer;font-size:12px;font-weight:500;
              color:#fff;transition:all .15s;}
    .lang-btn:hover{background:rgba(255,255,255,.18);}
    .lang-btn svg{width:14px;height:14px;flex-shrink:0;}
    .lang-dropdown{position:absolute;top:calc(100% + 6px);right:0;
                   background:#fff;border-radius:10px;
                   box-shadow:0 8px 24px rgba(0,0,0,.15);
                   border:1px solid #eee;min-width:120px;
                   display:none;z-index:999;overflow:hidden;}
    .lang-dropdown.open{display:block;}
    .lang-option{display:flex;align-items:center;gap:8px;
                 padding:10px 14px;font-size:13px;color:#333;
                 cursor:pointer;transition:background .12s;}
    .lang-option:hover{background:#f5f5f5;}
    .lang-option.active{background:var(--ep-teal-lt);color:#085041;font-weight:600;}
    .lang-flag{font-size:16px;}
    /* ══ FIN TOGGLE LANGUE ══ */
    /* ══ FIN NAVBAR ══ */
</style>
  <link rel="stylesheet" href="{{ asset('css/scroll-reveal.css') }}">
  <link rel="stylesheet" href="{{ asset('css/video-bg.css') }}">
  <link rel="stylesheet" href="{{ asset('css/buttons-enhanced.css') }}">
  <link rel="stylesheet" href="{{ asset('css/forms-enhanced.css') }}">
  <link rel="icon" type="image/jpeg" href="{{ asset('images/logo.jpeg') }}">
  <link rel="apple-touch-icon" href="{{ asset('images/logo.jpeg') }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>


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

@yield('content')

<script>





// ── Toggle langue ──
function toggleLangDropdown() {
    var d = document.getElementById('lang-dropdown');
    if (d) d.classList.toggle('open');
}
document.addEventListener('click', function(e) {
    var widget = document.getElementById('lang-widget');
    if (widget && !widget.contains(e.target)) {
        var d = document.getElementById('lang-dropdown');
        if (d) d.classList.remove('open');
    }
});

// ── Navbar mobile ──
function toggleNav(){
    var drawer  = document.getElementById('nav-drawer');
    var overlay = document.getElementById('nav-overlay');
    var burger  = document.getElementById('nav-burger');
    if(!drawer) return;
    var isOpen = drawer.style.left === '0px';
    drawer.style.left     = isOpen ? '-100%' : '0px';
    overlay.style.display = isOpen ? 'none'  : 'block';
    burger.classList.toggle('open', !isOpen);
    document.body.style.overflow = isOpen ? '' : 'hidden';
}

// ── Toasts auto-dismiss (4 secondes) ──
document.querySelectorAll('[data-toast]').forEach(function(t){
    setTimeout(function(){ t.classList.add('closing'); setTimeout(function(){ t.remove(); },200); },4000);
});
</script>
<script src="{{ asset('js/counter-animation.js') }}"></script>
<script src="{{ asset('js/scroll-reveal.js') }}"></script>
@stack('scripts')
<script src="{{ asset('js/forms-enhanced.js') }}"></script>
</body>
</html>
