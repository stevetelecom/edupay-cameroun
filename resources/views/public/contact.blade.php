@extends('layouts.public')

@section('title', 'Contact — EduPay Cameroun')

@section('content')

<div class="hero-band">
  <div class="hero-top">
    <div class="logo-t">Edu<span>Pay</span> Cameroun</div>
    <div style="display:flex;gap:8px;align-items:center;">
      <a href="{{ route('landing') }}" style="background:transparent;color:rgba(255,255,255,.7);border:1px solid rgba(255,255,255,.2);padding:7px 14px;border-radius:20px;font-size:12px;text-decoration:none;">Accueil</a>
      <a href="{{ route('temoignages') }}" style="background:transparent;color:rgba(255,255,255,.7);border:1px solid rgba(255,255,255,.2);padding:7px 14px;border-radius:20px;font-size:12px;text-decoration:none;">Témoignages</a>
      <a href="{{ route('about') }}" style="background:transparent;color:rgba(255,255,255,.7);border:1px solid rgba(255,255,255,.2);padding:7px 14px;border-radius:20px;font-size:12px;text-decoration:none;">À propos</a>
    </div>
  </div>
  <div class="hero-main">
    <div class="hero-tag" style="justify-content:center;display:inline-flex;"> <span style="width:7px;height:7px;border-radius:50%;background:#5DCAA5;display:inline-block;"></span> Contactez notre équipe</div>
    <div class="hero-h1">Une question ?<br><span style="color:#5DCAA5;">Nous sommes là pour vous aider.</span></div>
    <div class="hero-sub" style="margin:0 auto;max-width:560px;">Support établissements, familles et partenariats. Remplissez le formulaire ci-dessous et nous revenons vers vous rapidement.</div>
  </div>
</div>

<div class="ep-body2">
  <form method="POST" action="{{ route('contact.submit') }}">
    @csrf
    <div class="g2" style="gap:24px;align-items:flex-start;">
      <div style="display:grid;gap:18px;">
        <div class="epcard" style="padding:24px;">
          <div style="font-size:14px;font-weight:700;color:#0B2545;margin-bottom:10px;">Informations de contact</div>
          <div style="display:grid;gap:14px;">
            <div style="background:#E0F5EE;border-radius:16px;padding:18px;display:flex;gap:14px;align-items:flex-start;">
              <span class="icon-round" style="background:var(--ep-teal);">
                <span class="material-symbols-outlined">place</span>
              </span>
              <div>
                <div style="font-size:13px;font-weight:700;color:#0B2545;">Adresse</div>
                <div style="font-size:13px;color:#555;line-height:1.6;">Yaoundé, Cameroun<br>Quartier Minboman</div>
              </div>
            </div>
            <div style="background:#E8F1FC;border-radius:16px;padding:18px;display:flex;gap:14px;align-items:flex-start;">
              <span class="icon-round" style="background:#185FA5;">
                <span class="material-symbols-outlined">call</span>
              </span>
              <div>
                <div style="font-size:13px;font-weight:700;color:#0B2545;">Téléphone</div>
                <div style="font-size:13px;color:#555;line-height:1.6;">+237 654 862 989<br>+237 688 462 229</div>
              </div>
            </div>
            <div style="background:#EFF8F0;border-radius:16px;padding:18px;display:flex;gap:14px;align-items:flex-start;">
              <span class="icon-round" style="background:var(--ep-teal);">
                <span class="material-symbols-outlined">email</span>
              </span>
              <div>
                <div style="font-size:13px;font-weight:700;color:#0B2545;">Email</div>
                <div style="font-size:13px;color:#555;line-height:1.6;">contact@edupay.cm<br>support@edupay.cm</div>
              </div>
            </div>
            <div style="background:#FCEFEF;border-radius:16px;padding:18px;display:flex;gap:14px;align-items:flex-start;">
              <span class="icon-round" style="background:var(--ep-red);">
                <span class="material-symbols-outlined">schedule</span>
              </span>
              <div>
                <div style="font-size:13px;font-weight:700;color:#0B2545;">Horaires</div>
                <div style="font-size:13px;color:#555;line-height:1.6;">Lundi - Vendredi : 8h - 18h<br>Support en ligne 24/7</div>
              </div>
            </div>
          </div>
        </div>
        <div class="epcard" style="padding:24px;">
          <div style="font-size:14px;font-weight:700;color:#0B2545;margin-bottom:10px;">Pourquoi nous contacter ?</div>
          <div style="font-size:13px;color:#555;line-height:1.8;">
            Que vous soyez un établissement, un parent ou un partenaire, notre équipe vous accompagne sur l'intégration, les paiements, les relances et les solutions sur mesure.
          </div>
        </div>
      </div>

      <div class="epcard" style="padding:28px;">
        <div style="font-size:18px;font-weight:700;color:#0B2545;margin-bottom:12px;">Envoyez-nous un message</div>
        <div style="font-size:13px;color:#555;line-height:1.75;margin-bottom:24px;">Remplissez le formulaire ci-dessous et nous reviendrons vers vous dans les plus brefs délais.</div>
        <div style="display:grid;gap:18px;">
          <div style="display:grid;gap:8px;">
            <div style="display:flex;align-items:center;gap:10px;font-size:12px;font-weight:700;color:#0B2545;">
              <span class="icon-round icon-sm" style="background:var(--ep-teal);">
                <span class="material-symbols-outlined">person</span>
              </span>
              Nom complet
            </div>
            <input class="inp" type="text" name="name" value="{{ old('name') }}" placeholder="Votre nom" />
            @error('name')<div style="font-size:12px;color:#d94040;">{{ $message }}</div>@enderror
          </div>
          <div style="display:grid;gap:8px;">
            <div style="display:flex;align-items:center;gap:10px;font-size:12px;font-weight:700;color:#0B2545;">
              <span class="icon-round icon-sm" style="background:#185FA5;">
                <span class="material-symbols-outlined">email</span>
              </span>
              Email
            </div>
            <input class="inp" type="email" name="email" value="{{ old('email') }}" placeholder="votre@email.com" />
            @error('email')<div style="font-size:12px;color:#d94040;">{{ $message }}</div>@enderror
          </div>
          <div style="display:grid;gap:8px;">
            <div style="display:flex;align-items:center;gap:10px;font-size:12px;font-weight:700;color:#0B2545;">
              <span class="icon-round icon-sm" style="background:var(--ep-gold);">
                <span class="material-symbols-outlined">phone</span>
              </span>
              Téléphone
            </div>
            <input class="inp" type="tel" name="phone" value="{{ old('phone') }}" placeholder="+237 6XX XXX XXX" />
            @error('phone')<div style="font-size:12px;color:#d94040;">{{ $message }}</div>@enderror
          </div>
          <div style="display:grid;gap:8px;">
            <div style="display:flex;align-items:center;gap:10px;font-size:12px;font-weight:700;color:#0B2545;">
              <span class="icon-round icon-sm" style="background:var(--ep-red);">
                <span class="material-symbols-outlined">flag</span>
              </span>
              Sujet
            </div>
            <select class="select" name="subject">
              <option value="">Sélectionnez un sujet</option>
              <option value="Intégration établissement" {{ old('subject') == 'Intégration établissement' ? 'selected' : '' }}>Intégration établissement</option>
              <option value="Problème de paiement" {{ old('subject') == 'Problème de paiement' ? 'selected' : '' }}>Problème de paiement</option>
              <option value="Partenariat" {{ old('subject') == 'Partenariat' ? 'selected' : '' }}>Partenariat</option>
              <option value="Autre question" {{ old('subject') == 'Autre question' ? 'selected' : '' }}>Autre question</option>
            </select>
            @error('subject')<div style="font-size:12px;color:#d94040;">{{ $message }}</div>@enderror
          </div>
          <div style="display:grid;gap:8px;">
            <div style="display:flex;align-items:center;gap:10px;font-size:12px;font-weight:700;color:#0B2545;">
              <span class="icon-round icon-sm" style="background:#7C3AED;">
                <span class="material-symbols-outlined">chat_bubble</span>
              </span>
              Message
            </div>
            <textarea class="textarea" name="message" placeholder="Écrivez votre message ici...">{{ old('message') }}</textarea>
            @error('message')<div style="font-size:12px;color:#d94040;">{{ $message }}</div>@enderror
          </div>
          <button class="btn-p" style="width:auto;padding:13px 24px;">Envoyer le message</button>
        </div>
      </div>
    </div>
  </form>
</div>

<div class="ep-footer">
  <div class="footer-grid">
    <div>
      <div class="footer-logo">Edu<span>Pay</span> Cameroun</div>
      <div class="footer-desc">La première plateforme de paiement scolaire made in Cameroon.</div>
      <div><span class="footer-badge">TLS 1.3</span><span class="footer-badge">PCI-DSS</span><span class="footer-badge">COBAC</span></div>
    </div>
    <div>
      <div class="footer-col-title">Produit</div>
      <a class="footer-link" href="{{ route('landing') }}">Accueil</a>
      <a class="footer-link" href="{{ route('about') }}">À propos</a>
      <a class="footer-link" href="{{ route('temoignages') }}">Témoignages</a>
    </div>
    <div>
      <div class="footer-col-title">Établissements</div>
      <a class="footer-link" href="{{ route('register.ecole.step1') }}">Inscription</a>
      <a class="footer-link" href="#">Guide</a>
      <a class="footer-link" href="#">Support</a>
    </div>
    <div>
      <div class="footer-col-title">Informations</div>
      <a class="footer-link" href="{{ route('contact') }}">Contact</a>
      <a class="footer-link" href="#">Politique de confidentialité</a>
      <a class="footer-link" href="#">Conditions d'utilisation</a>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="footer-legal">© 2026 EduPay Cameroun — Tous droits réservés</div>
    <div class="footer-socials">
      <div class="social-btn">in</div>
      <div class="social-btn">X</div>
      <div class="social-btn">W</div>
      <div class="social-btn">f</div>
    </div>
  </div>
</div>

@endsection
