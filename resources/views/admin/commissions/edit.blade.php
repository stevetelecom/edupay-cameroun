@extends('layouts.admin')
@section('title', __('messages.commissions'))

{{-- Cette page editait le taux de commission d'un etablissement. Ce
     reglage par etablissement n'a plus lieu d'etre : le CDC S0 #3 veut un taux
     selon le PROFIL D'ABONNEMENT, et ce taux est desormais regle depuis
     /commissions (un champ par plan). Garder cette page aurait propose un
     reglage sans effet sur les prelevements — c'est exactement le piege qui
     faisait afficher 0,5 % au lieu des 2,3 % reels.

     Le taux d'un etablissement n'est plus modifiable : il se deduit de son
     abonnement via AangaraaPayService::tauxCommissionEtablissement(). --}}

<div class="ep-entete" style="justify-content:space-between;margin-bottom:18px;">
  <div style="display:flex;align-items:center;gap:10px;">
    <span class="material-symbols-outlined">percent</span>
    <div>
      <h3>{{ __('admin.taux_par_profil') }}</h3>
      <div class="ep-sous-titre" style="margin-top:2px;">{{ __('admin.taux_par_profil_aide') }}</div>
    </div>
  </div>
  <a href="{{ route('admin.commissions.index') }}" class="btn-o"
     style="width:auto;padding:9px 18px;font-size:13px;display:inline-flex;align-items:center;gap:7px;">
    <span class="material-symbols-outlined" style="font-size:17px;">arrow_back</span>
    {{ __('admin.retour_commissions') }}
  </a>
</div>

<div class="bg-white border border-gray-200 rounded-xl p-4" style="max-width:560px;">
  <div class="ep-bandeau" style="background:#F2F7FC;border-left:3px solid #1E5A8A;margin-bottom:16px;">
    <span class="material-symbols-outlined" style="color:#1E5A8A;">info</span>
    <div class="ep-bandeau-droite">
      <div class="ep-bandeau-titre" style="color:#1E5A8A;">{{ $etablissement->nom }}</div>
      <div style="font-size:12px;color:#1E5A8A;">{{ __('admin.taux_par_profil_explication') }}</div>
    </div>
  </div>

  <div class="grid grid-cols-2 gap-3 text-center mb-4">
    <div class="bg-gray-50 rounded-lg p-3">
      <div class="text-lg font-bold text-gray-800">{{ number_format($tauxActuel * 100, 2, ',', '') }}%</div>
      <div class="text-xs text-gray-500 mt-0.5">{{ __('admin.taux_global_config') }}</div>
    </div>
    <div class="bg-gray-50 rounded-lg p-3">
      <div class="text-lg font-bold text-gray-800">{{ number_format($tauxAangaraa * 100, 2, ',', '') }}%</div>
      <div class="text-xs text-gray-500 mt-0.5">{{ __('admin.cout_aangaraa') }}</div>
    </div>
  </div>

  <div style="background:#FEF3DC;border-left:3px solid #E8A020;border-radius:6px;padding:10px 12px;margin-bottom:16px;">
    <div style="font-size:12px;color:#854F0B;">
      {!! __('admin.taux_actuel_plateforme', ['pct' => e(number_format($tauxActuel * 100, 2).'%')]) !!}
    </div>
  </div>

  <a href="{{ route('admin.commissions.index') }}" class="btn-p"
     style="width:auto;padding:10px 18px;font-size:13px;display:inline-flex;align-items:center;gap:7px;">
    <span class="material-symbols-outlined" style="font-size:17px;">edit</span>
    {{ __('admin.modifier_taux') }}
  </a>
</div>
