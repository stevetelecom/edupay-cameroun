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
    <div><div class="footer-logo" style="display:flex;align-items:center;gap:10px;"><span style="width:44px;height:44px;border-radius:12px;background:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.15);"><img src="{{ asset('images/logo.jpeg') }}" alt="EduPay Cameroun" style="width:100%;height:100%;object-fit:cover;" /></span>EduPay Cameroun</div><div class="footer-desc">La première plateforme de paiement scolaire made in Cameroon.</div><div><span class="footer-badge">TLS 1.3</span><span class="footer-badge">PCI-DSS</span><span class="footer-badge">COBAC</span></div></div>
    <div><div class="footer-col-title">Produit</div><a class="footer-link" href="{{ route('landing') }}">Accueil</a><a class="footer-link" href="{{ route('temoignages') }}">Témoignages</a></div>
    <div><div class="footer-col-title">Établissements</div><a class="footer-link" href="{{ route('register.ecole.step1') }}">Inscription</a><a class="footer-link" href="{{ route('support') }}">Support</a></div>
    <div><div class="footer-col-title">Légal</div><a class="footer-link" href="{{ route('confidentialite') }}">Confidentialité</a><a class="footer-link" href="{{ route('cgu') }}">CGU</a></div>
  </div>
  <div class="footer-bottom"><div class="footer-legal">© 2026 EduPay Cameroun — Tous droits réservés</div><div class="footer-socials">
  <a href="#" target="_blank" rel="noopener" class="social-btn" aria-label="LinkedIn" title="LinkedIn" style="background:#0A66C2;border-color:#0A66C2;color:#fff;">
    <i class="fa-brands fa-linkedin-in"></i>
  </a>
  <a href="#" target="_blank" rel="noopener" class="social-btn" aria-label="X (Twitter)" title="X" style="background:#000;border-color:#000;color:#fff;">
    <i class="fa-brands fa-x-twitter"></i>
  </a>
  <a href="#" target="_blank" rel="noopener" class="social-btn" aria-label="WhatsApp" title="WhatsApp" style="background:#25D366;border-color:#25D366;color:#fff;">
    <i class="fa-brands fa-whatsapp"></i>
  </a>
  <a href="#" target="_blank" rel="noopener" class="social-btn" aria-label="Facebook" title="Facebook" style="background:#1877F2;border-color:#1877F2;color:#fff;">
    <i class="fa-brands fa-facebook-f"></i>
  </a>
  <a href="#" target="_blank" rel="noopener" class="social-btn" aria-label="Instagram" title="Instagram" style="background:linear-gradient(45deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888);border:none;color:#fff;">
    <i class="fa-brands fa-instagram"></i>
  </a>
</div></div>
</div>

@endsection
