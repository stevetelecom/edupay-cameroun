@extends('layouts.public')

@section('title', 'Support dédié — EduPay Cameroun')

@section('content')

@include('layouts._navbar_public')

<div class="hero-band">
  <div style="padding:36px 28px 28px;text-align:center;background:#0B2545">
    <div class="hero-tag" style="justify-content:center;">Support dédié</div>
    <div style="font-size:28px;font-weight:700;color:#fff;margin:10px 0;line-height:1.3;">Nous sommes <em style="font-style:normal;color:#5DCAA5;">là pour vous aider</em></div>
    <div style="font-size:14px;color:rgba(255,255,255,.55);max-width:520px;margin:0 auto;line-height:1.7;">Retrouvez les réponses aux questions les plus fréquentes, ou contactez directement notre équipe.</div>
  </div>
</div>

<div class="ep-body2">

  <div class="seclbl" style="margin-top:4px;">Questions fréquentes</div>
  <div style="display:grid;gap:10px;margin-bottom:24px;">

    <div class="epcard">
      <div style="font-size:13px;font-weight:700;margin-bottom:5px;color:#0B2545;">Combien de temps pour activer mon compte établissement ?</div>
      <div style="font-size:13px;color:#666;line-height:1.7;">Après soumission de votre dossier d'inscription, l'équipe EduPay étudie les informations et l'agrément fourni sous 24 à 48h. Vous recevez un email de confirmation dès l'activation.</div>
    </div>

    <div class="epcard">
      <div style="font-size:13px;font-weight:700;margin-bottom:5px;color:#0B2545;">Quels moyens de paiement sont disponibles pour les familles ?</div>
      <div style="font-size:13px;color:#666;line-height:1.7;">MTN Mobile Money et Orange Money, avec confirmation USSD directement sur le téléphone du payeur. Le paiement peut être intégral ou fractionné en plusieurs tranches selon l'échéancier défini par l'établissement.</div>
    </div>

    <div class="epcard">
      <div style="font-size:13px;font-weight:700;margin-bottom:5px;color:#0B2545;">Comment ajouter mes apprenants sur la plateforme ?</div>
      <div style="font-size:13px;color:#666;line-height:1.7;">Depuis le module Apprenants du back-office, ajoutez-les un par un ou importez toute votre liste en une fois via un fichier CSV/Excel avec le modèle fourni.</div>
    </div>

    <div class="epcard">
      <div style="font-size:13px;font-weight:700;margin-bottom:5px;color:#0B2545;">Que faire si le paiement d'un parent a échoué ?</div>
      <div style="font-size:13px;color:#666;line-height:1.7;">Le parent peut réessayer directement depuis son espace. Si le prélèvement a bien eu lieu sans confirmation côté EduPay, contactez notre support avec la référence de la transaction affichée dans son historique.</div>
    </div>

    <div class="epcard">
      <div style="font-size:13px;font-weight:700;margin-bottom:5px;color:#0B2545;">Comment obtenir un reçu ou une attestation de paiement ?</div>
      <div style="font-size:13px;color:#666;line-height:1.7;">Chaque paiement validé génère automatiquement un reçu PDF téléchargeable depuis l'espace du payeur, envoyé également par email et SMS.</div>
    </div>

    <div class="epcard">
      <div style="font-size:13px;font-weight:700;margin-bottom:5px;color:#0B2545;">Puis-je gérer plusieurs sites d'un même groupe scolaire ?</div>
      <div style="font-size:13px;color:#666;line-height:1.7;">Oui, le module Multi-sites permet une administration centralisée de plusieurs établissements rattachés à un même groupe.</div>
    </div>

  </div>

  <div class="seclbl">Contactez-nous directement</div>
  <div class="g2" style="margin-bottom:8px;">

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
        <div style="font-size:13px;color:#555;line-height:1.6;">contact@mekontso.gsi2026.com</div>
      </div>
    </div>

    <div style="background:#FEF3DC;border-radius:16px;padding:18px;display:flex;gap:14px;align-items:flex-start;">
      <span class="icon-round" style="background:var(--ep-gold);">
        <span class="material-symbols-outlined">schedule</span>
      </span>
      <div>
        <div style="font-size:13px;font-weight:700;color:#0B2545;">Disponibilité</div>
        <div style="font-size:13px;color:#555;line-height:1.6;">Lundi – Vendredi, 8h – 18h</div>
      </div>
    </div>

  </div>

  <div style="text-align:center;margin-top:20px;">
    <a href="{{ route('contact') }}" style="background:#0D9E75;color:#fff;text-decoration:none;font-size:13px;font-weight:600;padding:12px 26px;border-radius:10px;display:inline-block;">Envoyer un message →</a>
  </div>

</div>

<div class="ep-footer">
  <div class="footer-grid">
    <div><div class="footer-logo">Edu<span>Pay</span> Cameroun</div><div class="footer-desc">La première plateforme de paiement scolaire made in Cameroon.</div><div><span class="footer-badge">TLS 1.3</span><span class="footer-badge">PCI-DSS</span><span class="footer-badge">COBAC</span></div></div>
    <div><div class="footer-col-title">Produit</div><a class="footer-link" href="{{ route('landing') }}">Accueil</a><a class="footer-link" href="{{ route('temoignages') }}">Témoignages</a></div>
    <div><div class="footer-col-title">Établissements</div><a class="footer-link" href="{{ route('register.ecole.step1') }}">Inscription</a><a class="footer-link" href="{{ route('guide') }}">Guide</a></div>
    <div><div class="footer-col-title">Légal</div><a class="footer-link" href="#">Confidentialité</a><a class="footer-link" href="#">CGU</a></div>
  </div>
  <div class="footer-bottom"><div class="footer-legal">© 2026 EduPay Cameroun — Tous droits réservés</div><div class="footer-socials"><div class="social-btn">in</div><div class="social-btn">X</div><div class="social-btn">W</div><div class="social-btn">f</div></div></div>
</div>

@endsection
