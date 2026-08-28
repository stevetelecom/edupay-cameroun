@extends('layouts.public')
@section('title', $etablissement->nom . ' — EduPay Cameroun')
@section('content')
@include('layouts._navbar_public')

<div class="hero-band">
  <div style="padding:36px 28px 28px;background:#0B2545;">
    <div style="max-width:900px;margin:0 auto;display:flex;align-items:center;gap:18px;flex-wrap:wrap;">
      @if($etablissement->logo)
        <img src="{{ asset('storage/'.$etablissement->logo) }}"
             alt="{{ $etablissement->nom }}"
             style="width:72px;height:72px;border-radius:14px;object-fit:cover;border:2px solid rgba(255,255,255,.15);flex-shrink:0;" />
      @else
        <div style="width:72px;height:72px;border-radius:14px;background:var(--ep-teal-lt);
                    display:flex;align-items:center;justify-content:center;flex-shrink:0;
                    font-size:28px;font-weight:700;color:var(--ep-teal);">
          {{ strtoupper(substr($etablissement->nom, 0, 1)) }}
        </div>
      @endif
      <div>
        <div class="hero-tag" style="margin-bottom:6px;">
          {{ ucfirst(str_replace('_', ' ', $etablissement->type)) }}
        </div>
        <div style="font-size:24px;font-weight:700;color:#fff;">{{ $etablissement->nom }}</div>
        <div style="font-size:13px;color:rgba(255,255,255,.6);display:flex;align-items:center;gap:6px;margin-top:4px;">
          <span class="material-symbols-outlined" style="font-size:16px;">location_on</span>
          {{ $etablissement->ville }}{{ $etablissement->quartier ? ' — '.$etablissement->quartier : '' }}
        </div>
      </div>
    </div>

    {{-- KPIs rapides --}}
    <div style="max-width:900px;margin:22px auto 0;display:flex;gap:22px;flex-wrap:wrap;">
      <div style="text-align:center;">
        <div style="font-size:20px;font-weight:700;color:#5DCAA5;">{{ $nbApprenants }}</div>
        <div style="font-size:11px;color:rgba(255,255,255,.5);">{{ __('public.etab_apprenants_inscrits') }}</div>
      </div>
      <div style="text-align:center;">
        <div style="font-size:20px;font-weight:700;color:#5DCAA5;">{{ $etablissement->categoriesFrais->count() }}</div>
        <div style="font-size:11px;color:rgba(255,255,255,.5);">{{ __('public.etab_categories_frais') }}</div>
      </div>
      <div style="text-align:center;">
        <div style="font-size:20px;font-weight:700;color:#5DCAA5;">{{ $etablissement->code_etablissement }}</div>
        <div style="font-size:11px;color:rgba(255,255,255,.5);">{{ __('public.etab_code') }}</div>
      </div>
    </div>
  </div>
</div>

<div class="ep-body2" style="max-width:900px;">

  {{-- ── Informations générales ── --}}
  <div class="seclbl" style="margin-top:4px;">{{ __('public.etab_infos_generales') }}</div>
  <div class="epcard" style="margin-bottom:20px;">
    <div class="g2">
      <div style="padding:8px 0;border-bottom:1px solid #f5f5f5;">
        <div style="font-size:11px;color:#888;">{{ __('public.etab_statut_juridique') }}</div>
        <div style="font-size:13px;font-weight:600;">{{ ucfirst(str_replace('_', ' ', $etablissement->statut_juridique)) }}</div>
      </div>
      <div style="padding:8px 0;border-bottom:1px solid #f5f5f5;">
        <div style="font-size:11px;color:#888;">{{ __('public.etab_region') }}</div>
        <div style="font-size:13px;font-weight:600;">{{ ucfirst($etablissement->region) }}</div>
      </div>
      @if($etablissement->telephone)
      <div style="padding:8px 0;border-bottom:1px solid #f5f5f5;">
        <div style="font-size:11px;color:#888;">{{ __('public.etab_telephone') }}</div>
        <div style="font-size:13px;font-weight:600;">{{ $etablissement->telephone }}</div>
      </div>
      @endif
      @if($etablissement->email)
      <div style="padding:8px 0;border-bottom:1px solid #f5f5f5;">
        <div style="font-size:11px;color:#888;">{{ __('public.etab_email') }}</div>
        <div style="font-size:13px;font-weight:600;">{{ $etablissement->email }}</div>
      </div>
      @endif
    </div>
    @if($etablissement->description)
      <div style="margin-top:12px;font-size:13px;color:#555;line-height:1.6;">
        {{ $etablissement->description }}
      </div>
    @endif
  </div>

  {{-- ── Frais scolaires ── --}}
  <div class="seclbl">{{ __('public.etab_frais_proposes') }}</div>
  @if($etablissement->categoriesFrais->isEmpty())
    <div class="epcard" style="text-align:center;color:#999;padding:24px 0;margin-bottom:20px;">
      {{ __('public.etab_aucune_categorie') }}
    </div>
  @else
    <div class="g2" style="margin-bottom:20px;">
      @foreach($etablissement->categoriesFrais as $frais)
        <div class="epcard">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
            <div style="font-size:14px;font-weight:700;">{{ $frais->nom }}</div>
            @if($frais->fractionnable)
              <span class="pill pb" style="font-size:10px;">{{ __('public.etab_fractionnable') }}</span>
            @endif
          </div>
          <div style="font-size:18px;font-weight:700;color:var(--ep-teal);">
            {{ number_format($frais->montant_total, 0, ',', ' ') }} FCFA
          </div>
          <div style="font-size:11px;color:#888;">{{ $frais->annee_scolaire }}</div>
        </div>
      @endforeach
    </div>
  @endif

  {{-- ── Rejoindre cet établissement — 3 profils ── --}}
  <div class="seclbl">{{ __('public.etab_rejoindre') }} {{ $etablissement->nom }}</div>
  <div style="font-size:13px;color:#666;margin-bottom:16px;">
    {{ __('public.etab_rejoindre_desc') }}
  </div>

  <div class="g3" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin-bottom:32px;">

    <a href="{{ route('register.parent.step1', ['code_etablissement' => $etablissement->code_etablissement, 'profil' => 'parent']) }}"
       style="text-decoration:none;display:block;background:#fff;border:2px solid var(--ep-teal);
              border-radius:12px;padding:20px;text-align:center;transition:box-shadow .2s,transform .2s;"
       onmouseover="this.style.boxShadow='0 8px 24px rgba(13,158,117,.18)';this.style.transform='translateY(-3px)'"
       onmouseout="this.style.boxShadow='none';this.style.transform='translateY(0)'">
      <div style="width:48px;height:48px;border-radius:12px;background:var(--ep-teal-lt);
                  display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
        <span class="material-symbols-outlined" style="font-size:24px;color:#0D9E75;">family_restroom</span>
      </div>
      <div style="font-size:14px;font-weight:700;color:#1a1a2e;margin-bottom:4px;">{{ __('public.etab_je_suis_parent') }}</div>
      <div style="font-size:12px;color:#888;">{{ __('public.etab_payer_frais_enfants') }}</div>
    </a>

    <a href="{{ route('register.parent.step1', ['code_etablissement' => $etablissement->code_etablissement, 'profil' => 'eleve']) }}"
       style="text-decoration:none;display:block;background:#fff;border:2px solid #185FA5;
              border-radius:12px;padding:20px;text-align:center;transition:box-shadow .2s,transform .2s;"
       onmouseover="this.style.boxShadow='0 8px 24px rgba(24,95,165,.18)';this.style.transform='translateY(-3px)'"
       onmouseout="this.style.boxShadow='none';this.style.transform='translateY(0)'">
      <div style="width:48px;height:48px;border-radius:12px;background:#EFF6FF;
                  display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
        <span class="material-symbols-outlined" style="font-size:24px;color:#185FA5;">school</span>
      </div>
      <div style="font-size:14px;font-weight:700;color:#1a1a2e;margin-bottom:4px;">{{ __('public.etab_je_suis_eleve') }}</div>
      <div style="font-size:12px;color:#888;">{{ __('public.etab_payer_propres_frais') }}</div>
    </a>

    <a href="{{ route('register.parent.step1', ['code_etablissement' => $etablissement->code_etablissement, 'profil' => 'etudiant']) }}"
       style="text-decoration:none;display:block;background:#fff;border:2px solid #7C3AED;
              border-radius:12px;padding:20px;text-align:center;transition:box-shadow .2s,transform .2s;"
       onmouseover="this.style.boxShadow='0 8px 24px rgba(124,58,237,.18)';this.style.transform='translateY(-3px)'"
       onmouseout="this.style.boxShadow='none';this.style.transform='translateY(0)'">
      <div style="width:48px;height:48px;border-radius:12px;background:#F5F3FF;
                  display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
        <span class="material-symbols-outlined" style="font-size:24px;color:#7C3AED;">history_edu</span>
      </div>
      <div style="font-size:14px;font-weight:700;color:#1a1a2e;margin-bottom:4px;">{{ __('public.etab_je_suis_etudiant') }}</div>
      <div style="font-size:12px;color:#888;">{{ __('public.etab_payer_frais_univ') }}</div>
    </a>

  </div>

  <div style="text-align:center;margin-bottom:24px;">
    <a href="{{ route('landing') }}" style="color:var(--ep-teal);text-decoration:none;font-size:13px;font-weight:500;">
      {{ __('public.retour_liste_etabs') }}
    </a>
  </div>

</div>

@endsection
