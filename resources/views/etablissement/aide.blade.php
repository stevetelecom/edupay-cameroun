@extends('layouts.etablissement')

@section('title', __('etablissement.guide_support'))

@section('content')

<div class="flex items-center justify-between mb-5">
  <div>
    <h1 class="text-xl font-bold text-gray-900">{{ __('etablissement.guide_support') }}</h1>
    <p class="text-sm text-gray-500 mt-0.5">{{ __('etablissement.aide_sous_titre') }}</p>
  </div>
</div>

<div class="seclbl" style="margin-top:0;">{{ __('etablissement.guide_par_module') }}</div>
<div style="display:grid;gap:12px;margin-bottom:24px;">

  <div class="epcard" style="display:flex;gap:14px;align-items:flex-start;">
    <div style="width:34px;height:34px;border-radius:10px;background:var(--ep-teal-lt);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--ep-teal)" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
    </div>
    <div>
      <div style="font-size:13px;font-weight:700;margin-bottom:3px;">{{ __('etablissement.apprenants') }}</div>
      <div style="font-size:12px;color:#666;line-height:1.6;">{!! __('etablissement.aide_apprenants_desc', ['url' => route('etablissement.apprenants.import.template')]) !!}</div>
    </div>
  </div>

  <div class="epcard" style="display:flex;gap:14px;align-items:flex-start;">
    <div style="width:34px;height:34px;border-radius:10px;background:var(--ep-gold-lt);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--ep-gold)" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
    </div>
    <div>
      <div style="font-size:13px;font-weight:700;margin-bottom:3px;">{{ __('etablissement.frais_echeanciers_titre') }}</div>
      <div style="font-size:12px;color:#666;line-height:1.6;">{{ __('etablissement.aide_frais_desc') }}</div>
    </div>
  </div>

  <div class="epcard" style="display:flex;gap:14px;align-items:flex-start;">
    <div style="width:34px;height:34px;border-radius:10px;background:#E8F1FC;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#185FA5" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
    </div>
    <div>
      <div style="font-size:13px;font-weight:700;margin-bottom:3px;">{{ __('etablissement.paiements_titre') }}</div>
      <div style="font-size:12px;color:#666;line-height:1.6;">{{ __('etablissement.aide_paiements_desc') }}</div>
    </div>
  </div>

  <div class="epcard" style="display:flex;gap:14px;align-items:flex-start;">
    <div style="width:34px;height:34px;border-radius:10px;background:#F3E8FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    </div>
    <div>
      <div style="font-size:13px;font-weight:700;margin-bottom:3px;">{{ __('etablissement.impayes_titre') }}</div>
      <div style="font-size:12px;color:#666;line-height:1.6;">{{ __('etablissement.aide_impayes_desc') }}</div>
    </div>
  </div>

  <div class="epcard" style="display:flex;gap:14px;align-items:flex-start;">
    <div style="width:34px;height:34px;border-radius:10px;background:var(--ep-teal-lt);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--ep-teal)" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    </div>
    <div>
      <div style="font-size:13px;font-weight:700;margin-bottom:3px;">{{ __('etablissement.rapports_titre') }}</div>
      <div style="font-size:12px;color:#666;line-height:1.6;">{{ __('etablissement.aide_rapports_desc') }}</div>
    </div>
  </div>

  <div class="epcard" style="display:flex;gap:14px;align-items:flex-start;">
    <div style="width:34px;height:34px;border-radius:10px;background:var(--ep-gold-lt);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--ep-gold)" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/></svg>
    </div>
    <div>
      <div style="font-size:13px;font-weight:700;margin-bottom:3px;">{{ __('etablissement.remboursements') }}</div>
      <div style="font-size:12px;color:#666;line-height:1.6;">{{ __('etablissement.aide_remboursements_desc') }}</div>
    </div>
  </div>

  <div class="epcard" style="display:flex;gap:14px;align-items:flex-start;">
    <div style="width:34px;height:34px;border-radius:10px;background:#E8F1FC;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#185FA5" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
    </div>
    <div>
      <div style="font-size:13px;font-weight:700;margin-bottom:3px;">{{ __('etablissement.multi_sites_titre') }}</div>
      <div style="font-size:12px;color:#666;line-height:1.6;">{{ __('etablissement.aide_multi_desc') }}</div>
    </div>
  </div>

  <div class="epcard" style="display:flex;gap:14px;align-items:flex-start;">
    <div style="width:34px;height:34px;border-radius:10px;background:#F3E8FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
    </div>
    <div>
      <div style="font-size:13px;font-weight:700;margin-bottom:3px;">{{ __('etablissement.utilisateurs_titre') }}</div>
      <div style="font-size:12px;color:#666;line-height:1.6;">{{ __('etablissement.aide_utilisateurs_desc') }}</div>
    </div>
  </div>

</div>

<div class="seclbl">{{ __('etablissement.faq') }}</div>
<div style="display:grid;gap:10px;margin-bottom:24px;">

  <div class="epcard">
    <div style="font-size:13px;font-weight:700;margin-bottom:5px;color:#0B2545;">{{ __('etablissement.faq1_titre') }}</div>
    <div style="font-size:13px;color:#666;line-height:1.7;">{{ __('etablissement.faq1_desc') }}</div>
  </div>

  <div class="epcard">
    <div style="font-size:13px;font-weight:700;margin-bottom:5px;color:#0B2545;">{{ __('etablissement.faq2_titre') }}</div>
    <div style="font-size:13px;color:#666;line-height:1.7;">{{ __('etablissement.faq2_desc') }}</div>
  </div>

  <div class="epcard">
    <div style="font-size:13px;font-weight:700;margin-bottom:5px;color:#0B2545;">{{ __('etablissement.faq3_titre') }}</div>
    <div style="font-size:13px;color:#666;line-height:1.7;">{{ __('etablissement.faq3_desc') }}</div>
  </div>

</div>

<div class="seclbl">{{ __('etablissement.aide_besoin') }}</div>
<div class="g2">

  <div style="background:#E0F5EE;border-radius:16px;padding:18px;display:flex;gap:14px;align-items:flex-start;">
    <span class="icon-round" style="background:var(--ep-teal);">
      <span class="material-symbols-outlined">email</span>
    </span>
    <div>
      <div style="font-size:13px;font-weight:700;color:#0B2545;">{{ __('etablissement.email') }}</div>
      <div style="font-size:13px;color:#555;line-height:1.6;">contact@mekontso.gsi2026.com</div>
    </div>
  </div>

  <div style="background:#E8F1FC;border-radius:16px;padding:18px;display:flex;gap:14px;align-items:flex-start;">
    <span class="icon-round" style="background:#185FA5;">
      <span class="material-symbols-outlined">call</span>
    </span>
    <div>
      <div style="font-size:13px;font-weight:700;color:#0B2545;">{{ __('etablissement.telephone') }}</div>
      <div style="font-size:13px;color:#555;line-height:1.6;">+237 654 862 989 · +237 688 462 229</div>
    </div>
  </div>

</div>

@endsection
