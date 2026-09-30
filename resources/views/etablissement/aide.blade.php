@extends('layouts.etablissement')

@section('title', __('etablissement.guide_support'))

@section('content')

{{-- En-tête de page style tableau de bord (pastille dorée + Poppins) --}}
<div class="ep-entete" style="margin-bottom:2px;">
  <span class="material-symbols-outlined">help</span>
  <h3>{{ __('etablissement.guide_support') }}</h3>
</div>
<div class="ep-sous-titre" style="margin-bottom:18px;">{{ __('etablissement.aide_sous_titre') }}</div>

<div class="seclbl" style="margin-top:0;">{{ __('etablissement.guide_par_module') }}</div>
<div style="display:grid;gap:12px;margin-bottom:24px;">

  <div class="epcard" style="display:flex;gap:14px;align-items:flex-start;">
    <div class="ep-ico vert"><span class="material-symbols-outlined">groups</span></div>
    <div>
      <div class="ep-guide-titre">{{ __('etablissement.apprenants') }}</div>
      <div class="ep-guide-texte">{!! __('etablissement.aide_apprenants_desc', ['url' => e(route('etablissement.apprenants.import.template'))]) !!}</div>
    </div>
  </div>

  <div class="epcard" style="display:flex;gap:14px;align-items:flex-start;">
    <div class="ep-ico or"><span class="material-symbols-outlined">request_quote</span></div>
    <div>
      <div class="ep-guide-titre">{{ __('etablissement.frais_echeanciers_titre') }}</div>
      <div class="ep-guide-texte">{{ __('etablissement.aide_frais_desc') }}</div>
    </div>
  </div>

  <div class="epcard" style="display:flex;gap:14px;align-items:flex-start;">
    <div class="ep-ico bleu"><span class="material-symbols-outlined">payments</span></div>
    <div>
      <div class="ep-guide-titre">{{ __('etablissement.paiements_titre') }}</div>
      <div class="ep-guide-texte">{{ __('etablissement.aide_paiements_desc') }}</div>
    </div>
  </div>

  <div class="epcard" style="display:flex;gap:14px;align-items:flex-start;">
    <div class="ep-ico rouge"><span class="material-symbols-outlined">report</span></div>
    <div>
      <div class="ep-guide-titre">{{ __('etablissement.impayes_titre') }}</div>
      <div class="ep-guide-texte">{{ __('etablissement.aide_impayes_desc') }}</div>
    </div>
  </div>

  <div class="epcard" style="display:flex;gap:14px;align-items:flex-start;">
    <div class="ep-ico bleu"><span class="material-symbols-outlined">monitoring</span></div>
    <div>
      <div class="ep-guide-titre">{{ __('etablissement.rapports_titre') }}</div>
      <div class="ep-guide-texte">{{ __('etablissement.aide_rapports_desc') }}</div>
    </div>
  </div>

  <div class="epcard" style="display:flex;gap:14px;align-items:flex-start;">
    <div class="ep-ico vert"><span class="material-symbols-outlined">assignment_return</span></div>
    <div>
      <div class="ep-guide-titre">{{ __('etablissement.remboursements') }}</div>
      <div class="ep-guide-texte">{{ __('etablissement.aide_remboursements_desc') }}</div>
    </div>
  </div>

  <div class="epcard" style="display:flex;gap:14px;align-items:flex-start;">
    <div class="ep-ico navy"><span class="material-symbols-outlined">domain</span></div>
    <div>
      <div class="ep-guide-titre">{{ __('etablissement.multi_sites_titre') }}</div>
      <div class="ep-guide-texte">{{ __('etablissement.aide_multi_desc') }}</div>
    </div>
  </div>

  <div class="epcard" style="display:flex;gap:14px;align-items:flex-start;">
    <div class="ep-ico purple"><span class="material-symbols-outlined">badge</span></div>
    <div>
      <div class="ep-guide-titre">{{ __('etablissement.utilisateurs_titre') }}</div>
      <div class="ep-guide-texte">{{ __('etablissement.aide_utilisateurs_desc') }}</div>
    </div>
  </div>

</div>

<div class="seclbl">{{ __('etablissement.faq') }}</div>
{{-- FAQ accordéon v3 : <details> natif (accessible, sans JS) + pastille
     « ? » dorée + chevron rotatif, motif validé par l'utilisateur --}}
<div style="max-width:760px;margin-bottom:24px;">

  <details class="ep-faq-item">
    <summary class="ep-faq-q">
      <span class="ep-faq-ico" aria-hidden="true">?</span>
      {{ __('etablissement.faq1_titre') }}
      <span class="material-symbols-outlined ep-faq-chev" aria-hidden="true">expand_more</span>
    </summary>
    <div class="ep-faq-r">{{ __('etablissement.faq1_desc') }}</div>
  </details>

  <details class="ep-faq-item">
    <summary class="ep-faq-q">
      <span class="ep-faq-ico" aria-hidden="true">?</span>
      {{ __('etablissement.faq2_titre') }}
      <span class="material-symbols-outlined ep-faq-chev" aria-hidden="true">expand_more</span>
    </summary>
    <div class="ep-faq-r">{{ __('etablissement.faq2_desc') }}</div>
  </details>

  <details class="ep-faq-item">
    <summary class="ep-faq-q">
      <span class="ep-faq-ico" aria-hidden="true">?</span>
      {{ __('etablissement.faq3_titre') }}
      <span class="material-symbols-outlined ep-faq-chev" aria-hidden="true">expand_more</span>
    </summary>
    <div class="ep-faq-r">{{ __('etablissement.faq3_desc') }}</div>
  </details>

  <details class="ep-faq-item">
    <summary class="ep-faq-q">
      <span class="ep-faq-ico" aria-hidden="true">?</span>
      {{ __('etablissement.faq4_titre') }}
      <span class="material-symbols-outlined ep-faq-chev" aria-hidden="true">expand_more</span>
    </summary>
    <div class="ep-faq-r">{{ __('etablissement.faq4_desc') }}</div>
  </details>

  <details class="ep-faq-item">
    <summary class="ep-faq-q">
      <span class="ep-faq-ico" aria-hidden="true">?</span>
      {{ __('etablissement.faq5_titre') }}
      <span class="material-symbols-outlined ep-faq-chev" aria-hidden="true">expand_more</span>
    </summary>
    <div class="ep-faq-r">{{ __('etablissement.faq5_desc') }}</div>
  </details>

</div>

<div class="seclbl">{{ __('etablissement.aide_besoin') }}</div>
<div class="g2">

  <div style="background:var(--ep-teal-lt);border-radius:16px;padding:18px;display:flex;gap:14px;align-items:center;">
    <div class="ep-ico vert" style="width:44px;height:44px;border-radius:12px;">
      <span class="material-symbols-outlined" style="font-size:23px;">email</span>
    </div>
    <div>
      <div class="ep-guide-titre">{{ __('etablissement.email') }}</div>
      <div class="ep-guide-texte">contact@mekontso.gsi2026.com</div>
    </div>
  </div>

  <div style="background:var(--ep-blue-lt);border-radius:16px;padding:18px;display:flex;gap:14px;align-items:center;">
    <div class="ep-ico bleu" style="width:44px;height:44px;border-radius:12px;">
      <span class="material-symbols-outlined" style="font-size:23px;">call</span>
    </div>
    <div>
      <div class="ep-guide-titre">{{ __('etablissement.telephone') }}</div>
      <div class="ep-guide-texte">+237 654 862 989 · +237 688 462 229</div>
    </div>
  </div>

</div>

@endsection
