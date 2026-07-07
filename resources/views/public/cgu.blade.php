@extends('layouts.public')

@section('title', "Conditions d'utilisation — EduPay Cameroun")

@section('content')

@include('layouts._navbar_public')

<div class="hero-band">
  <div style="padding:36px 28px 28px;text-align:center;background:#0B2545">
    <div class="hero-tag" style="justify-content:center;">Cadre contractuel</div>
    <div style="font-size:28px;font-weight:700;color:#fff;margin:10px 0;line-height:1.3;">Conditions <em style="font-style:normal;color:#5DCAA5;">d'utilisation</em></div>
    <div style="font-size:14px;color:rgba(255,255,255,.55);max-width:520px;margin:0 auto;line-height:1.7;">Dernière mise à jour : Juillet 2026</div>
  </div>
</div>

<div class="ep-body2">

  <div class="epcard" style="margin-bottom:16px;background:var(--ep-teal-lt);border-color:rgba(13,158,117,.2);">
    <div style="font-size:12px;color:#0F6E56;line-height:1.7;">En créant un compte ou en utilisant la plateforme EduPay Cameroun, vous acceptez les présentes conditions d'utilisation.</div>
  </div>

  <div class="seclbl" style="margin-top:4px;">1. Objet</div>
  <div class="epcard" style="margin-bottom:14px;">
    <div style="font-size:13px;color:#555;line-height:1.8;">
      EduPay Cameroun est une plateforme de paiement électronique des frais de scolarité, permettant aux établissements scolaires camerounais de collecter les paiements via MTN Mobile Money et Orange Money, et aux parents/étudiants de régler ces frais à distance.
    </div>
  </div>

  <div class="seclbl">2. Comptes utilisateurs</div>
  <div class="epcard" style="margin-bottom:14px;">
    <div style="font-size:13px;color:#555;line-height:1.8;">
      Trois types de comptes existent : <strong>établissement</strong> (directeur, comptable, caissier), <strong>payeur</strong> (parent, étudiant) et <strong>super administrateur</strong> (équipe EduPay). Chaque utilisateur est responsable de la confidentialité de ses identifiants. Un compte établissement reste en statut « en attente » jusqu'à validation de son dossier par l'équipe EduPay.
    </div>
  </div>

  <div class="seclbl">3. Obligations des établissements</div>
  <div class="epcard" style="margin-bottom:14px;">
    <div style="font-size:13px;color:#555;line-height:1.8;">
      L'établissement s'engage à fournir des informations exactes (agrément, coordonnées) et à maintenir à jour les catégories de frais et l'annuaire de ses apprenants. L'établissement est seul responsable des montants réclamés à ses familles.
    </div>
  </div>

  <div class="seclbl">4. Paiements et frais de service</div>
  <div class="epcard" style="margin-bottom:14px;">
    <div style="font-size:13px;color:#555;line-height:1.8;">
      Chaque transaction via MTN Mobile Money ou Orange Money peut inclure des frais de service EduPay, affichés avant validation du paiement. Une commission est également prélevée sur chaque transaction avant reversement à l'établissement, selon le taux convenu à l'activation du compte. Les paiements sont traités par notre agrégateur AangaraaPay ; EduPay Cameroun ne stocke aucune donnée bancaire.
    </div>
  </div>

  <div class="seclbl">5. Remboursements et litiges</div>
  <div class="epcard" style="margin-bottom:14px;">
    <div style="font-size:13px;color:#555;line-height:1.8;">
      Toute demande de remboursement doit être initiée par l'établissement concerné via son back-office. Les réclamations peuvent être soumises via la page <a href="{{ route('contact') }}" style="color:var(--ep-teal);font-weight:600;">Contact</a> ou directement depuis l'espace payeur.
    </div>
  </div>

  <div class="seclbl">6. Suspension et résiliation</div>
  <div class="epcard" style="margin-bottom:14px;">
    <div style="font-size:13px;color:#555;line-height:1.8;">
      EduPay Cameroun se réserve le droit de suspendre ou de supprimer un compte établissement en cas de manquement grave aux présentes conditions, de fraude avérée ou sur demande motivée de l'établissement lui-même.
    </div>
  </div>

  <div class="seclbl">7. Droit applicable</div>
  <div class="epcard" style="margin-bottom:14px;">
    <div style="font-size:13px;color:#555;line-height:1.8;">
      Les présentes conditions sont régies par le droit camerounais et les réglementations applicables de la zone CEMAC (COBAC, BEAC) en matière de paiement électronique.
    </div>
  </div>

  <div class="epcard" style="background:#FEF9EC;border-color:rgba(232,160,32,.25);">
    <div style="font-size:12px;color:#854F0B;line-height:1.7;">Ce document a une portée informative et ne remplace pas un avis juridique professionnel. Il pourra être mis à jour à mesure que la plateforme évolue.</div>
  </div>

</div>

<div class="ep-footer">
  <div class="footer-grid">
    <div><div class="footer-logo">Edu<span>Pay</span> Cameroun</div><div class="footer-desc">La première plateforme de paiement scolaire made in Cameroon.</div><div><span class="footer-badge">TLS 1.3</span><span class="footer-badge">PCI-DSS</span><span class="footer-badge">COBAC</span></div></div>
    <div><div class="footer-col-title">Produit</div><a class="footer-link" href="{{ route('landing') }}">Accueil</a><a class="footer-link" href="{{ route('temoignages') }}">Témoignages</a></div>
    <div><div class="footer-col-title">Établissements</div><a class="footer-link" href="{{ route('register.ecole.step1') }}">Inscription</a><a class="footer-link" href="{{ route('support') }}">Support</a></div>
    <div><div class="footer-col-title">Légal</div><a class="footer-link" href="{{ route('confidentialite') }}">Confidentialité</a><a class="footer-link" href="{{ route('cgu') }}">CGU</a></div>
  </div>
  <div class="footer-bottom"><div class="footer-legal">© 2026 EduPay Cameroun — Tous droits réservés</div><div class="footer-socials"><div class="social-btn">in</div><div class="social-btn">X</div><div class="social-btn">W</div><div class="social-btn">f</div></div></div>
</div>

@endsection
