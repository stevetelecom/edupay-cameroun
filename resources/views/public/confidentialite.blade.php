@extends('layouts.public')

@section('title', 'Politique de confidentialité — EduPay Cameroun')

@section('content')

@include('layouts._navbar_public')

<div class="hero-band">
  <div style="padding:36px 28px 28px;text-align:center;background:#0B2545">
    <div class="hero-tag" style="justify-content:center;">Vie privée &amp; protection des données</div>
    <div style="font-size:28px;font-weight:700;color:#fff;margin:10px 0;line-height:1.3;">Politique de <em style="font-style:normal;color:#5DCAA5;">confidentialité</em></div>
    <div style="font-size:14px;color:rgba(255,255,255,.55);max-width:520px;margin:0 auto;line-height:1.7;">Dernière mise à jour : Juillet 2026</div>
  </div>
</div>

<div class="ep-body2">

  <div class="epcard" style="margin-bottom:16px;background:var(--ep-teal-lt);border-color:rgba(13,158,117,.2);">
    <div style="font-size:12px;color:#0F6E56;line-height:1.7;">Ce document décrit comment EduPay Cameroun collecte, utilise et protège les données personnelles des établissements scolaires, des responsables/directeurs, des payeurs (parents, étudiants) et des apprenants inscrits sur la plateforme.</div>
  </div>

  <div class="seclbl" style="margin-top:4px;">1. Données collectées</div>
  <div class="epcard" style="margin-bottom:14px;">
    <div style="font-size:13px;color:#555;line-height:1.8;">
      <strong>Établissements :</strong> nom, type, statut juridique, numéro d'agrément, coordonnées, logo, document d'agrément.<br>
      <strong>Responsables/directeurs :</strong> nom, prénom, téléphone, email, mot de passe (chiffré).<br>
      <strong>Payeurs (parents, étudiants) :</strong> nom, prénom, téléphone, email, mot de passe (chiffré).<br>
      <strong>Apprenants :</strong> nom, prénom, classe, statut de paiement — renseignés par l'établissement, jamais collectés directement auprès des mineurs.<br>
      <strong>Transactions :</strong> montants, opérateur Mobile Money, statut, référence — nécessaires au traitement des paiements et à la conformité réglementaire.
    </div>
  </div>

  <div class="seclbl">2. Utilisation des données</div>
  <div class="epcard" style="margin-bottom:14px;">
    <div style="font-size:13px;color:#555;line-height:1.8;">
      Les données sont utilisées pour : créer et gérer les comptes, traiter les paiements de frais de scolarité, générer les reçus, envoyer des notifications (email, SMS), produire les rapports financiers exigés par les établissements et les autorités compétentes, et assurer la sécurité de la plateforme.
    </div>
  </div>

  <div class="seclbl">3. Partage des données</div>
  <div class="epcard" style="margin-bottom:14px;">
    <div style="font-size:13px;color:#555;line-height:1.8;">
      Les données de paiement nécessaires sont transmises à notre agrégateur Mobile Money <strong>AangaraaPay</strong> ainsi qu'aux opérateurs <strong>MTN Mobile Money</strong> et <strong>Orange Money</strong>, uniquement dans le cadre du traitement de la transaction. EduPay Cameroun ne vend ni ne loue aucune donnée personnelle à des tiers à des fins commerciales.
    </div>
  </div>

  <div class="seclbl">4. Conservation des données</div>
  <div class="epcard" style="margin-bottom:14px;">
    <div style="font-size:13px;color:#555;line-height:1.8;">
      Les données de transaction sont conservées conformément aux exigences de traçabilité applicables (COBAC, BEAC). Les comptes inactifs ou supprimés font l'objet d'un archivage sécurisé (suppression douce) avant purge définitive.
    </div>
  </div>

  <div class="seclbl">5. Sécurité</div>
  <div class="epcard" style="margin-bottom:14px;">
    <div style="font-size:13px;color:#555;line-height:1.8;">
      Toutes les communications transitent en HTTPS/TLS 1.3. Les mots de passe sont hachés (bcrypt) et ne sont jamais stockés en clair. L'accès aux données sensibles est restreint par des rôles (directeur, comptable, caissier, super administrateur) et journalisé.
    </div>
  </div>

  <div class="seclbl">6. Vos droits</div>
  <div class="epcard" style="margin-bottom:14px;">
    <div style="font-size:13px;color:#555;line-height:1.8;">
      Vous pouvez à tout moment demander l'accès, la rectification ou la suppression de vos données personnelles en contactant notre équipe via la <a href="{{ route('contact') }}" style="color:var(--ep-teal);font-weight:600;">page Contact</a>.
    </div>
  </div>

  <div class="epcard" style="background:#FEF9EC;border-color:rgba(232,160,32,.25);">
    <div style="font-size:12px;color:#854F0B;line-height:1.7;">Ce document a une portée informative et pourra être mis à jour à mesure que la plateforme évolue. Pour toute question spécifique sur la conformité réglementaire, contactez notre équipe.</div>
  </div>

</div>

<div class="ep-footer">
  <div class="footer-grid">
    <div><div class="footer-logo" style="display:flex;align-items:center;gap:10px;"><span style="width:44px;height:44px;border-radius:12px;background:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.15);"><img src="{{ asset('images/logo.jpeg') }}" alt="EduPay Cameroun" style="width:100%;height:100%;object-fit:cover;" /></span>EduPay Cameroun</div><div class="footer-desc">La première plateforme de paiement scolaire made in Cameroon.</div><div><span class="footer-badge">TLS 1.3</span><span class="footer-badge">PCI-DSS</span><span class="footer-badge">COBAC</span></div></div>
    <div><div class="footer-col-title">Produit</div><a class="footer-link" href="{{ route('landing') }}">Accueil</a><a class="footer-link" href="{{ route('temoignages') }}">Témoignages</a></div>
    <div><div class="footer-col-title">Établissements</div><a class="footer-link" href="{{ route('register.ecole.step1') }}">Inscription</a><a class="footer-link" href="{{ route('guide') }}">Guide</a></div>
    <div><div class="footer-col-title">Légal</div><a class="footer-link" href="{{ route('confidentialite') }}">Confidentialité</a><a class="footer-link" href="{{ route('cgu') }}">CGU</a></div>
  </div>
  <div class="footer-bottom"><div class="footer-legal">© 2026 EduPay Cameroun — Tous droits réservés</div><div class="footer-socials"><div class="social-btn">in</div><div class="social-btn">X</div><div class="social-btn">W</div><div class="social-btn">f</div></div></div>
</div>

@endsection
