@php
  $routeActuelle = request()->routeIs('landing') ? 'landing'
      : (request()->routeIs('about') ? 'about'
      : (request()->routeIs('temoignages') ? 'temoignages'
      : (request()->routeIs('contact') ? 'contact' : '')));
@endphp

<nav class="pub-nav">
  <div class="pub-nav-inner">
    <a href="{{ route('landing') }}" class="logo-t" style="text-decoration:none;color:#fff;">
      Edu<span>Pay</span> Cameroun
    </a>
    <button class="nav-burger" id="nav-burger" onclick="toggleNav()" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>
    {{-- Desktop uniquement --}}
    <div class="nav-desk">
      <a href="{{ route('landing') }}" class="nav-link {{ $routeActuelle==='landing'?'nav-link-active':'' }}">Accueil</a>
      <a href="{{ route('about') }}"   class="nav-link {{ $routeActuelle==='about'?'nav-link-active':'' }}">À propos</a>
      <a href="{{ route('temoignages') }}" class="nav-link {{ $routeActuelle==='temoignages'?'nav-link-active':'' }}">Témoignages</a>
      <a href="{{ route('contact') }}" class="nav-link {{ $routeActuelle==='contact'?'nav-link-active':'' }}">Contact</a>
      <div class="nav-sep"></div>
      <a href="{{ route('login') }}" class="nav-btn-ghost">Connexion</a>
      <a href="{{ route('register.parent.step1') }}" class="nav-btn-main">S'inscrire →</a>
    </div>
  </div>
</nav>

{{-- Drawer mobile — directement dans le body via position:fixed --}}
<div id="nav-overlay" onclick="toggleNav()"
     style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.6);z-index:9998;"></div>

<div id="nav-drawer"
     style="position:fixed;top:0;left:-100%;width:75%;max-width:260px;height:100%;
            background:#0B2545;border-right:1px solid rgba(255,255,255,.12);
            z-index:9999;transition:left .3s ease;overflow-y:auto;
            display:flex;flex-direction:column;padding:20px 16px 30px;gap:6px;">

  {{-- Fermer --}}
  <button onclick="toggleNav()"
          style="align-self:flex-end;background:rgba(255,255,255,.1);border:none;
                 color:#fff;width:32px;height:32px;border-radius:8px;font-size:18px;
                 cursor:pointer;display:flex;align-items:center;justify-content:center;
                 margin-bottom:16px;">✕</button>

  <a href="{{ route('landing') }}"
     style="color:{{ $routeActuelle==='landing'?'#fff':'rgba(255,255,255,.7)' }};
            text-decoration:none;font-size:15px;padding:12px 14px;border-radius:10px;
            background:{{ $routeActuelle==='landing'?'rgba(255,255,255,.1)':'' }};">
    Accueil
  </a>
  <a href="{{ route('about') }}"
     style="color:{{ $routeActuelle==='about'?'#fff':'rgba(255,255,255,.7)' }};
            text-decoration:none;font-size:15px;padding:12px 14px;border-radius:10px;
            background:{{ $routeActuelle==='about'?'rgba(255,255,255,.1)':'' }};">
    À propos
  </a>
  <a href="{{ route('temoignages') }}"
     style="color:{{ $routeActuelle==='temoignages'?'#fff':'rgba(255,255,255,.7)' }};
            text-decoration:none;font-size:15px;padding:12px 14px;border-radius:10px;
            background:{{ $routeActuelle==='temoignages'?'rgba(255,255,255,.1)':'' }};">
    Témoignages
  </a>
  <a href="{{ route('contact') }}"
     style="color:{{ $routeActuelle==='contact'?'#fff':'rgba(255,255,255,.7)' }};
            text-decoration:none;font-size:15px;padding:12px 14px;border-radius:10px;
            background:{{ $routeActuelle==='contact'?'rgba(255,255,255,.1)':'' }};">
    Contact
  </a>

  <div style="height:1px;background:rgba(255,255,255,.1);margin:8px 0;"></div>

  <a href="{{ route('login') }}"
     style="color:rgba(255,255,255,.85);text-decoration:none;font-size:15px;
            padding:12px 14px;border-radius:10px;border:1px solid rgba(255,255,255,.2);
            text-align:center;">
    Connexion
  </a>
  <a href="{{ route('register.parent.step1') }}"
     style="color:#fff;text-decoration:none;font-size:15px;font-weight:600;
            padding:13px 14px;border-radius:10px;background:#0D9E75;
            text-align:center;margin-top:4px;">
    S'inscrire gratuitement →
  </a>
</div>
