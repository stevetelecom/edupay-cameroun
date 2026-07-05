@extends('layouts.etablissement')

@section('title', 'Guide & Support')

@section('content')

<div class="flex items-center justify-between mb-5">
  <div>
    <h1 class="text-xl font-bold text-gray-900">Guide &amp; Support</h1>
    <p class="text-sm text-gray-500 mt-0.5">Comment utiliser votre back-office EduPay et obtenir de l'aide</p>
  </div>
</div>

<div class="seclbl" style="margin-top:0;">Guide par module</div>
<div style="display:grid;gap:12px;margin-bottom:24px;">

  <div class="epcard" style="display:flex;gap:14px;align-items:flex-start;">
    <div style="width:34px;height:34px;border-radius:10px;background:var(--ep-teal-lt);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--ep-teal)" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
    </div>
    <div>
      <div style="font-size:13px;font-weight:700;margin-bottom:3px;">Apprenants</div>
      <div style="font-size:12px;color:#666;line-height:1.6;">Ajoutez vos élèves un par un via le bouton « Nouvel apprenant », ou importez toute votre liste en une fois depuis <a href="{{ route('etablissement.apprenants.import.template') }}" style="color:var(--ep-teal);font-weight:600;">notre modèle CSV/Excel</a>.</div>
    </div>
  </div>

  <div class="epcard" style="display:flex;gap:14px;align-items:flex-start;">
    <div style="width:34px;height:34px;border-radius:10px;background:var(--ep-gold-lt);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--ep-gold)" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
    </div>
    <div>
      <div style="font-size:13px;font-weight:700;margin-bottom:3px;">Frais &amp; échéanciers</div>
      <div style="font-size:12px;color:#666;line-height:1.6;">Créez vos catégories de frais (inscription, scolarité, cantine...) et affectez-les à vos apprenants. Activez le paiement fractionné pour définir des tranches avec échéances.</div>
    </div>
  </div>

  <div class="epcard" style="display:flex;gap:14px;align-items:flex-start;">
    <div style="width:34px;height:34px;border-radius:10px;background:#E8F1FC;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#185FA5" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
    </div>
    <div>
      <div style="font-size:13px;font-weight:700;margin-bottom:3px;">Paiements</div>
      <div style="font-size:12px;color:#666;line-height:1.6;">Retrouvez toutes les transactions validées par vos familles via MTN Mobile Money ou Orange Money, avec statut et référence de chaque paiement.</div>
    </div>
  </div>

  <div class="epcard" style="display:flex;gap:14px;align-items:flex-start;">
    <div style="width:34px;height:34px;border-radius:10px;background:#F3E8FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    </div>
    <div>
      <div style="font-size:13px;font-weight:700;margin-bottom:3px;">Impayés</div>
      <div style="font-size:12px;color:#666;line-height:1.6;">Filtrez les élèves avec solde non réglé et lancez une relance SMS groupée, ou une relance individuelle personnalisée pour les cas critiques.</div>
    </div>
  </div>

  <div class="epcard" style="display:flex;gap:14px;align-items:flex-start;">
    <div style="width:34px;height:34px;border-radius:10px;background:var(--ep-teal-lt);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--ep-teal)" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    </div>
    <div>
      <div style="font-size:13px;font-weight:700;margin-bottom:3px;">Rapports</div>
      <div style="font-size:12px;color:#666;line-height:1.6;">Générez des rapports financiers journaliers, mensuels ou annuels, exportables en PDF ou Excel pour votre direction.</div>
    </div>
  </div>

  <div class="epcard" style="display:flex;gap:14px;align-items:flex-start;">
    <div style="width:34px;height:34px;border-radius:10px;background:var(--ep-gold-lt);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--ep-gold)" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/></svg>
    </div>
    <div>
      <div style="font-size:13px;font-weight:700;margin-bottom:3px;">Remboursements</div>
      <div style="font-size:12px;color:#666;line-height:1.6;">Traitez les demandes de remboursement partiel ou total en cas d'erreur de paiement, avec suivi du statut jusqu'à l'approbation.</div>
    </div>
  </div>

  <div class="epcard" style="display:flex;gap:14px;align-items:flex-start;">
    <div style="width:34px;height:34px;border-radius:10px;background:#E8F1FC;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#185FA5" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
    </div>
    <div>
      <div style="font-size:13px;font-weight:700;margin-bottom:3px;">Multi-sites</div>
      <div style="font-size:12px;color:#666;line-height:1.6;">Si vous gérez un groupe scolaire, administrez tous vos sites depuis un seul compte centralisé.</div>
    </div>
  </div>

  <div class="epcard" style="display:flex;gap:14px;align-items:flex-start;">
    <div style="width:34px;height:34px;border-radius:10px;background:#F3E8FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
    </div>
    <div>
      <div style="font-size:13px;font-weight:700;margin-bottom:3px;">Utilisateurs internes</div>
      <div style="font-size:12px;color:#666;line-height:1.6;">Invitez votre comptable ou votre caissier avec des rôles et permissions différenciés depuis ce module.</div>
    </div>
  </div>

</div>

<div class="seclbl">Questions fréquentes</div>
<div style="display:grid;gap:10px;margin-bottom:24px;">

  <div class="epcard">
    <div style="font-size:13px;font-weight:700;margin-bottom:5px;color:#0B2545;">Un parent me dit avoir payé mais le statut reste « en attente »</div>
    <div style="font-size:13px;color:#666;line-height:1.7;">Le paiement peut prendre jusqu'à 2 minutes à se confirmer après validation USSD. Si le statut ne change pas, demandez au parent la référence affichée dans son historique et transmettez-la au support.</div>
  </div>

  <div class="epcard">
    <div style="font-size:13px;font-weight:700;margin-bottom:5px;color:#0B2545;">Comment modifier le taux de commission appliqué ?</div>
    <div style="font-size:13px;color:#666;line-height:1.7;">Le taux de commission est configuré par l'équipe EduPay lors de l'activation de votre compte. Contactez le support pour toute demande d'ajustement.</div>
  </div>

  <div class="epcard">
    <div style="font-size:13px;font-weight:700;margin-bottom:5px;color:#0B2545;">Puis-je changer le numéro Mobile Money de reversement ?</div>
    <div style="font-size:13px;color:#666;line-height:1.7;">Oui, rendez-vous dans Paramètres pour mettre à jour le numéro et l'opérateur utilisés pour recevoir vos reversements.</div>
  </div>

</div>

<div class="seclbl">Besoin d'aide supplémentaire ?</div>
<div class="g2">

  <div style="background:#E0F5EE;border-radius:16px;padding:18px;display:flex;gap:14px;align-items:flex-start;">
    <span class="icon-round" style="background:var(--ep-teal);">
      <span class="material-symbols-outlined">email</span>
    </span>
    <div>
      <div style="font-size:13px;font-weight:700;color:#0B2545;">Email</div>
      <div style="font-size:13px;color:#555;line-height:1.6;">contact@mekontso.gsi2026.com</div>
    </div>
  </div>

  <div style="background:#E8F1FC;border-radius:16px;padding:18px;display:flex;gap:14px;align-items:flex-start;">
    <span class="icon-round" style="background:#185FA5;">
      <span class="material-symbols-outlined">call</span>
    </span>
    <div>
      <div style="font-size:13px;font-weight:700;color:#0B2545;">Téléphone</div>
      <div style="font-size:13px;color:#555;line-height:1.6;">+237 654 862 989 · +237 688 462 229</div>
    </div>
  </div>

</div>

@endsection
