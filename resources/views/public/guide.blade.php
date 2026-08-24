@extends('layouts.public')

@section('title', "Guide d'utilisation — EduPay Cameroun")

@section('content')

@include('layouts._navbar_public')

<div class="hero-band">
  <div style="padding:36px 28px 28px;text-align:center;background:#0B2545">
    <div class="hero-tag" style="justify-content:center;">Guide pour les établissements</div>
    <div style="font-size:28px;font-weight:700;color:#fff;margin:10px 0;line-height:1.3;">Démarrer avec <em style="font-style:normal;color:#5DCAA5;">EduPay</em></div>
    <div style="font-size:14px;color:rgba(255,255,255,.55);max-width:520px;margin:0 auto;line-height:1.7;">De l'inscription de votre établissement au premier paiement encaissé — tout ce qu'il faut savoir.</div>
  </div>
</div>

<div class="ep-body2">

  <div class="seclbl" style="margin-top:4px;">Comment démarrer en 6 étapes</div>
  <div style="display:grid;gap:12px;margin-bottom:24px;">

    <div class="epcard" style="display:flex;gap:16px;align-items:flex-start;">
      <div style="width:32px;height:32px;border-radius:50%;background:var(--ep-teal);color:#fff;font-weight:700;font-size:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">1</div>
      <div>
        <div style="font-size:14px;font-weight:700;margin-bottom:3px;">Inscrivez votre établissement</div>
        <div style="font-size:13px;color:#666;line-height:1.6;">Remplissez le formulaire en 4 étapes : informations de l'établissement, contact et responsable, documents (agrément, logo), puis validation finale.</div>
        <a href="{{ route('register.ecole.step1') }}" style="font-size:12px;color:var(--ep-teal);font-weight:600;text-decoration:none;display:inline-block;margin-top:6px;">Inscrire mon établissement →</a>
      </div>
    </div>

    <div class="epcard" style="display:flex;gap:16px;align-items:flex-start;">
      <div style="width:32px;height:32px;border-radius:50%;background:var(--ep-gold);color:#fff;font-weight:700;font-size:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">2</div>
      <div>
        <div style="font-size:14px;font-weight:700;margin-bottom:3px;">Validation par l'équipe EduPay</div>
        <div style="font-size:13px;color:#666;line-height:1.6;">Votre dossier est étudié (agrément, informations du responsable) sous 24 à 48h. Vous recevez un email dès l'activation de votre compte.</div>
      </div>
    </div>

    <div class="epcard" style="display:flex;gap:16px;align-items:flex-start;">
      <div style="width:32px;height:32px;border-radius:50%;background:#185FA5;color:#fff;font-weight:700;font-size:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">3</div>
      <div>
        <div style="font-size:14px;font-weight:700;margin-bottom:3px;">Configurez vos frais et échéanciers</div>
        <div style="font-size:13px;color:#666;line-height:1.6;">Définissez vos catégories de frais (inscription, scolarité, cantine, examen...) et, si besoin, un paiement fractionné en plusieurs tranches avec échéances.</div>
      </div>
    </div>

    <div class="epcard" style="display:flex;gap:16px;align-items:flex-start;">
      <div style="width:32px;height:32px;border-radius:50%;background:#7C3AED;color:#fff;font-weight:700;font-size:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">4</div>
      <div>
        <div style="font-size:14px;font-weight:700;margin-bottom:3px;">Ajoutez vos apprenants</div>
        <div style="font-size:13px;color:#666;line-height:1.6;">Manuellement un par un, ou en une seule fois via import CSV/Excel avec notre modèle prêt à l'emploi.</div>
      </div>
    </div>

    <div class="epcard" style="display:flex;gap:16px;align-items:flex-start;">
      <div style="width:32px;height:32px;border-radius:50%;background:var(--ep-teal);color:#fff;font-weight:700;font-size:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">5</div>
      <div>
        <div style="font-size:14px;font-weight:700;margin-bottom:3px;">Les familles paient en ligne</div>
        <div style="font-size:13px;color:#666;line-height:1.6;">Chaque parent reçoit ses accès et règle les frais scolaires via MTN Mobile Money ou Orange Money, avec confirmation USSD instantanée et reçu PDF automatique.</div>
      </div>
    </div>

    <div class="epcard" style="display:flex;gap:16px;align-items:flex-start;">
      <div style="width:32px;height:32px;border-radius:50%;background:#E8A020;color:#fff;font-weight:700;font-size:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">6</div>
      <div>
        <div style="font-size:14px;font-weight:700;margin-bottom:3px;">Suivez tout en temps réel</div>
        <div style="font-size:13px;color:#666;line-height:1.6;">Tableau de bord financier, liste des impayés avec relances automatiques, rapports exportables en PDF/Excel — tout depuis un seul écran.</div>
      </div>
    </div>

  </div>

  <div class="seclbl">Fonctionnalités du back-office</div>
  <div class="g4" style="margin-bottom:24px;">
    <div class="value-card" style="border-left-color:var(--ep-teal);">
      <div style="font-size:13px;font-weight:700;margin-bottom:5px;">Apprenants</div>
      <div style="font-size:12px;color:#666;line-height:1.6;">Annuaire complet avec statut de paiement par élève.</div>
    </div>
    <div class="value-card" style="border-left-color:var(--ep-gold);">
      <div style="font-size:13px;font-weight:700;margin-bottom:5px;">Frais &amp; échéanciers</div>
      <div style="font-size:12px;color:#666;line-height:1.6;">Catégories de frais et calendriers de paiement personnalisés.</div>
    </div>
    <div class="value-card" style="border-left-color:#185FA5;">
      <div style="font-size:13px;font-weight:700;margin-bottom:5px;">Paiements</div>
      <div style="font-size:12px;color:#666;line-height:1.6;">Historique complet des transactions validées en temps réel.</div>
    </div>
    <div class="value-card" style="border-left-color:#7C3AED;">
      <div style="font-size:13px;font-weight:700;margin-bottom:5px;">Impayés</div>
      <div style="font-size:12px;color:#666;line-height:1.6;">Relances SMS automatiques et manuelles vers les familles.</div>
    </div>
    <div class="value-card" style="border-left-color:var(--ep-teal);">
      <div style="font-size:13px;font-weight:700;margin-bottom:5px;">Rapports</div>
      <div style="font-size:12px;color:#666;line-height:1.6;">Exports PDF et Excel, journaliers, mensuels et annuels.</div>
    </div>
    <div class="value-card" style="border-left-color:var(--ep-gold);">
      <div style="font-size:13px;font-weight:700;margin-bottom:5px;">Remboursements</div>
      <div style="font-size:12px;color:#666;line-height:1.6;">Traitement des demandes de remboursement partiel ou total.</div>
    </div>
    <div class="value-card" style="border-left-color:#185FA5;">
      <div style="font-size:13px;font-weight:700;margin-bottom:5px;">Multi-sites</div>
      <div style="font-size:12px;color:#666;line-height:1.6;">Gestion centralisée de plusieurs sites pour les groupes scolaires.</div>
    </div>
    <div class="value-card" style="border-left-color:#7C3AED;">
      <div style="font-size:13px;font-weight:700;margin-bottom:5px;">Utilisateurs internes</div>
      <div style="font-size:12px;color:#666;line-height:1.6;">Rôles Directeur, Comptable, Caissier avec droits différenciés.</div>
    </div>
  </div>

  <div style="background:#0B2545;border-radius:var(--radius-lg);padding:28px;text-align:center;">
    <div style="font-size:17px;font-weight:700;color:#fff;margin-bottom:6px;">Besoin d'aide supplémentaire ?</div>
    <div style="font-size:13px;color:rgba(255,255,255,.6);margin-bottom:18px;">Notre équipe support répond à toutes vos questions.</div>
    <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
      <a href="{{ route('support') }}" style="background:#0D9E75;color:#fff;text-decoration:none;font-size:13px;font-weight:600;padding:11px 22px;border-radius:10px;">Voir la FAQ →</a>
      <a href="{{ route('contact') }}" style="background:transparent;color:#fff;text-decoration:none;font-size:13px;font-weight:600;padding:11px 22px;border-radius:10px;border:1px solid rgba(255,255,255,.25);">Contacter le support</a>
    </div>
  </div>

</div>

<div class="ep-footer">
  <div class="footer-grid">
    <div><div class="footer-logo" style="display:flex;align-items:center;gap:10px;"><span style="width:44px;height:44px;border-radius:12px;background:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.15);"><img src="{{ asset('images/logo.jpeg') }}" alt="EduPay Cameroun" style="width:100%;height:100%;object-fit:cover;" /></span>EduPay Cameroun</div><div class="footer-desc">La première plateforme de paiement scolaire made in Cameroon.</div><div><span class="footer-badge">TLS 1.3</span><span class="footer-badge">PCI-DSS</span><span class="footer-badge">COBAC</span></div></div>
    <div><div class="footer-col-title">Produit</div><a class="footer-link" href="{{ route('landing') }}">Accueil</a><a class="footer-link" href="{{ route('temoignages') }}">Témoignages</a></div>
    <div><div class="footer-col-title">Établissements</div><a class="footer-link" href="{{ route('register.ecole.step1') }}">Inscription</a><a class="footer-link" href="{{ route('support') }}">Support</a></div>
    <div><div class="footer-col-title">Légal</div><a class="footer-link" href="{{ route('confidentialite') }}">Confidentialité</a><a class="footer-link" href="{{ route('cgu') }}">CGU</a></div>
  </div>
  <div class="footer-bottom"><div class="footer-legal">© 2026 EduPay Cameroun — Tous droits réservés</div><div class="footer-socials"><div class="social-btn">in</div><div class="social-btn">X</div><div class="social-btn">W</div><div class="social-btn">f</div></div></div>
</div>

@endsection
