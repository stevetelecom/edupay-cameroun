@php
  $routeActuelle = request()->routeIs('landing') ? 'landing'
      : (request()->routeIs('about') ? 'about'
      : (request()->routeIs('temoignages') ? 'temoignages'
      : (request()->routeIs('contact') ? 'contact' : '')));
@endphp

<nav class="pub-nav">
  <div class="pub-nav-inner">

    {{-- Logo --}}
    <a href="{{ route('landing') }}" class="logo-t" style="text-decoration:none;color:#fff;">
      Edu<span>Pay</span> Cameroun
    </a>

    {{-- Burger mobile --}}
    <button class="nav-burger" id="nav-burger" onclick="toggleNav()" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>

    {{-- Liens --}}
    <div class="nav-links" id="nav-links">

      {{-- Bouton fermer (visible mobile) --}}
      <button class="nav-close-btn" onclick="toggleNav()">✕</button>

      <a href="{{ route('landing') }}"
         class="nav-link {{ $routeActuelle === 'landing' ? 'nav-link-active' : '' }}">
        Accueil
      </a>
      <a href="{{ route('about') }}"
         class="nav-link {{ $routeActuelle === 'about' ? 'nav-link-active' : '' }}">
        À propos
      </a>
      <a href="{{ route('temoignages') }}"
         class="nav-link {{ $routeActuelle === 'temoignages' ? 'nav-link-active' : '' }}">
        Témoignages
      </a>

      <div class="nav-sep"></div>

      <a href="{{ route('login') }}" class="nav-btn-ghost">Connexion</a>
      <a href="{{ route('register.parent.step1') }}" class="nav-btn-main">S'inscrire →</a>
    </div>
  </div>

  {{-- Overlay --}}
  <div class="nav-overlay" id="nav-overlay" onclick="toggleNav()"></div>
</nav>
