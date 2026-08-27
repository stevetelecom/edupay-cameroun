@extends('layouts.public')

@section('title', 'Témoignages — EduPay Cameroun')

@section('content')

@include('layouts._navbar_public')
<div class="hero-band">
  <div style="padding:32px 28px 24px;text-align:center;background:#0B2545">
    <div class="hero-tag" style="justify-content:center;">Ce qu'ils disent de nous</div>
    <div style="font-size:26px;font-weight:700;color:#fff;margin:10px 0 8px;">Des utilisateurs satisfaits<br>à travers tout le <em style="font-style:normal;color:#5DCAA5;">Cameroun</em></div>
    <div style="display:flex;justify-content:center;gap:24px;margin-top:18px;flex-wrap:wrap;" data-stats-container>
      <div style="text-align:center;">
        <div style="font-size:22px;font-weight:700;color:#5DCAA5;" class="stat-counter" data-count="{{ $stats['nb_etablissements'] }}">0</div>
        <div style="font-size:11px;color:rgba(255,255,255,.5);">Établissements actifs</div>
      </div>
      <div style="text-align:center;">
        <div style="font-size:22px;font-weight:700;color:#5DCAA5;" class="stat-counter" data-count="{{ $stats['nb_apprenants'] }}">0</div>
        <div style="font-size:11px;color:rgba(255,255,255,.5);">Apprenants inscrits</div>
      </div>
      <div style="text-align:center;">
        <div style="font-size:22px;font-weight:700;color:#5DCAA5;" class="stat-counter" data-count="{{ $stats['nb_paiements'] }}">0</div>
        <div style="font-size:11px;color:rgba(255,255,255,.5);">Paiements validés</div>
      </div>
      <div style="text-align:center;">
        <div style="font-size:22px;font-weight:700;color:#5DCAA5;" class="stat-counter" data-count="{{ $stats['montant_total'] }}" data-suffix=" FCFA">0 FCFA</div>
        <div style="font-size:11px;color:rgba(255,255,255,.5);">Collectés</div>
      </div>
    </div>
    <div style="margin-top:14px;font-size:11px;color:rgba(255,255,255,.4);font-style:italic;">
      Plateforme en phase pilote — chiffres en croissance quotidienne à mesure que de nouveaux établissements rejoignent EduPay.
    </div>
  </div>
</div>

<div class="ep-body2">

  <div class="seclbl" style="margin-top:4px;">Directeurs &amp; Administrateurs</div>
  <div class="g2" style="margin-bottom:20px;">

    <div class="testi-card">
      <div class="stars">★★★★★</div>
      <div class="testi-quote">"</div>
      <div class="testi-text">Avant EduPay, nos caissiers passaient 3 heures par matin à gérer les files d'attente. Maintenant 80% de nos parents paient depuis chez eux. Le taux de recouvrement est passé de 54% à 91% en 4 mois.</div>
      <div class="testi-author">
        <div class="av" style="background:var(--ep-teal-lt);color:#085041;width:44px;height:44px;">DM</div>
        <div><div style="font-size:13px;font-weight:700;">Directeur MBONGO Charles</div><div style="font-size:11px;color:#888;">Lycée Bilingue de Melen · Yaoundé</div><span class="pill pg" style="font-size:10px;margin-top:4px;display:inline-block;">Établissement pilote</span></div>
      </div>
    </div>

    <div class="testi-card">
      <div class="stars">★★★★★</div>
      <div class="testi-quote">"</div>
      <div class="testi-text">La relance automatique par SMS m'économise 2 heures chaque semaine. Je sélectionne tous les impayés, je clique sur "Envoyer relances" et c'est fait. Les parents réagissent très vite au SMS.</div>
      <div class="testi-author">
        <div class="av" style="background:var(--ep-gold-lt);color:#8B5E10;width:44px;height:44px;">CF</div>
        <div><div style="font-size:13px;font-weight:700;">Mme FOUDA Caroline</div><div style="font-size:11px;color:#888;">Comptable · Collège Saint-André · Douala</div><span class="pill pa" style="font-size:10px;margin-top:4px;display:inline-block;">6 mois d'utilisation</span></div>
      </div>
    </div>

    <div class="testi-card">
      <div class="stars">★★★★☆</div>
      <div class="testi-quote">"</div>
      <div class="testi-text">Gérer 3 groupes scolaires depuis un seul tableau de bord, c'est exactement ce dont on avait besoin. Les rapports Excel nous font gagner un temps précieux lors des audits. Je recommande vivement.</div>
      <div class="testi-author">
        <div class="av" style="background:var(--ep-blue-lt);color:#1A4F8A;width:44px;height:44px;">PN</div>
        <div><div style="font-size:13px;font-weight:700;">Proviseur NKOA Pierre</div><div style="font-size:11px;color:#888;">Groupe Scolaire Excellence · Bafoussam</div><span class="pill pb" style="font-size:10px;margin-top:4px;display:inline-block;">Multi-établissements</span></div>
      </div>
    </div>

    <div class="testi-card">
      <div class="stars">★★★★★</div>
      <div class="testi-quote">"</div>
      <div class="testi-text">Le paiement fractionné en 3 tranches a réduit les abandons scolaires. Les familles qui ne pouvaient pas régler d'un coup suivent maintenant un échéancier clair et adapté à leur situation.</div>
      <div class="testi-author">
        <div class="av" style="background:var(--ep-purple-lt);color:#5B21B6;width:44px;height:44px;">AN</div>
        <div><div style="font-size:13px;font-weight:700;">Directrice ABOMO Nathalie</div><div style="font-size:11px;color:#888;">Lycée Technique de Ngaoundéré</div><span class="pill" style="background:var(--ep-purple-lt);color:#5B21B6;font-size:10px;margin-top:4px;display:inline-block;">Zone rurale</span></div>
      </div>
    </div>

  </div>

  <div class="seclbl">Parents &amp; Étudiants</div>
  <div class="g2" style="margin-bottom:20px;">

    <div class="testi-card">
      <div class="stars">★★★★★</div>
      <div class="testi-quote">"</div>
      <div class="testi-text">J'habite à Kribi, l'école de mes enfants est à Yaoundé. Avant je devais envoyer l'argent par transfert et appeler la secrétaire. Maintenant je paie en 2 min via MTN MoMo et le reçu arrive par email immédiatement.</div>
      <div class="testi-author">
        <div class="av" style="background:var(--ep-teal-lt);color:#085041;width:44px;height:44px;">BT</div>
        <div><div style="font-size:13px;font-weight:700;">M. BELINGA Thomas</div><div style="font-size:11px;color:#888;">Parent de 2 élèves · Kribi</div><span class="pill pg" style="font-size:10px;margin-top:4px;display:inline-block;">3 mois d'utilisation</span></div>
      </div>
    </div>

    <div class="testi-card">
      <div class="stars">★★★★★</div>
      <div class="testi-quote">"</div>
      <div class="testi-text">En tant qu'étudiante à l'IUT, payer mes frais universitaires depuis mon téléphone sans me déplacer à la banque, c'est révolutionnaire. Le paiement Orange Money est immédiat et le reçu PDF est accepté partout.</div>
      <div class="testi-author">
        <div class="av" style="background:var(--ep-gold-lt);color:#8B5E10;width:44px;height:44px;">KA</div>
        <div><div style="font-size:13px;font-weight:700;">Mme KAMGA Aurélie</div><div style="font-size:11px;color:#888;">Étudiante IUT · Université de Douala</div><span class="pill pa" style="font-size:10px;margin-top:4px;display:inline-block;">Orange Money</span></div>
      </div>
    </div>

  </div>

  <div style="background:var(--ep-navy);border-radius:var(--radius-lg);padding:32px 28px;text-align:center;margin-bottom:4px;">
    <div style="font-size:20px;font-weight:700;color:#fff;margin-bottom:8px;">Rejoignez les établissements qui font confiance à EduPay</div>
    <div style="font-size:13px;color:rgba(255,255,255,.55);margin-bottom:22px;">Inscription gratuite · Mise en ligne en 24h · Support dédié</div>
    <div style="display:flex;justify-content:center;gap:12px;flex-wrap:wrap;">
      <a href="{{ route('register.parent.step1') }}" class="hbtn-main">Créer mon compte parent</a>
      <a href="{{ route('register.ecole.step1') }}" class="hbtn-ghost">Inscrire mon établissement</a>
    </div>
  </div>

</div>

<div class="ep-footer">
  <div class="footer-grid">
    <div><div class="footer-logo" style="display:flex;align-items:center;gap:10px;"><span style="width:44px;height:44px;border-radius:12px;background:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.15);"><img src="{{ asset('images/logo.jpeg') }}" alt="EduPay Cameroun" style="width:100%;height:100%;object-fit:cover;" /></span>EduPay Cameroun</div><div class="footer-desc">La première plateforme de paiement scolaire made in Cameroon.</div><div><span class="footer-badge">TLS 1.3</span><span class="footer-badge">PCI-DSS</span><span class="footer-badge">COBAC</span></div></div>
    <div><div class="footer-col-title">Produit</div><a class="footer-link" href="{{ route('landing') }}">Accueil</a><a class="footer-link" href="{{ route('about') }}">À propos</a></div>
    <div><div class="footer-col-title">Établissements</div><a class="footer-link" href="{{ route('register.ecole.step1') }}">Inscription</a><a class="footer-link" href="{{ route('guide') }}">Guide</a></div>
    <div><div class="footer-col-title">Légal</div><a class="footer-link" href="{{ route('confidentialite') }}">Confidentialité</a><a class="footer-link" href="{{ route('cgu') }}">CGU</a></div>
  </div>
  <div class="footer-bottom"><div class="footer-legal">© 2026 EduPay Cameroun</div><div class="footer-socials">
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
