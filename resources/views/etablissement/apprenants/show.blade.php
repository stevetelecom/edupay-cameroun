@extends('layouts.etablissement')
@section('title', $apprenant->nom . ' ' . $apprenant->prenom)

@push('modals')
{{-- ══ MODAL : Modifier apprenant ══ --}}
<div id="modal-modifier-apprenant" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3>{{ __('etablissement.modifier_apprenant') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-modifier-apprenant')">×</button>
    </div>
    <form method="POST" action="{{ route('etablissement.apprenants.update', $apprenant) }}">
      @csrf @method('PUT')
      <div class="ep-modal-body">
        <div style="font-size:12px;color:#888;margin-bottom:14px;font-weight:500;">
          {{ $apprenant->nom }} {{ $apprenant->prenom }} · {{ $apprenant->classe }}
        </div>
        <div class="g2">
          <div>
            <div class="lbl">{{ __('etablissement.lbl_nom') }}</div>
            <input class="inp" name="nom" value="{{ old('nom', $apprenant->nom) }}" required />
          </div>
          <div>
            <div class="lbl">{{ __('etablissement.lbl_prenom') }}</div>
            <input class="inp" name="prenom" value="{{ old('prenom', $apprenant->prenom) }}" required />
          </div>
        </div>
        <div class="g2">
          <div>
            <div class="lbl">{{ __('etablissement.lbl_classe') }}</div>
            <input class="inp" name="classe" value="{{ old('classe', $apprenant->classe) }}" required />
          </div>
          <div>
            <div class="lbl">{{ __('etablissement.matricule') }}</div>
            <input class="inp" name="matricule" value="{{ old('matricule', $apprenant->matricule) }}" />
          </div>
        </div>
        <div class="g2">
          <div>
            <div class="lbl">{{ __('etablissement.ddn') }}</div>
            <input class="inp" type="date" name="date_naissance"
                   value="{{ old('date_naissance', $apprenant->date_naissance ? \Carbon\Carbon::parse($apprenant->date_naissance)->format('Y-m-d') : '') }}" />
          </div>
          <div>
            <div class="lbl">{{ __('etablissement.sexe') }}</div>
            <select class="select" name="sexe">
              <option value="">{{ __('etablissement.non_precise') }}</option>
              <option value="M" {{ old('sexe', $apprenant->sexe) === 'M' ? 'selected' : '' }}>{{ __('etablissement.masculin') }}</option>
              <option value="F" {{ old('sexe', $apprenant->sexe) === 'F' ? 'selected' : '' }}>{{ __('etablissement.feminin') }}</option>
            </select>
          </div>
        </div>
        <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
          <input type="checkbox" name="actif" value="1"
                 {{ old('actif', $apprenant->actif) ? 'checked' : '' }} />
          {{ __('etablissement.apprenant_actif_annee') }}
        </label>
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;"
                onclick="epModal.close('modal-modifier-apprenant')">{{ __('etablissement.annuler') }}</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">
          {{ __('etablissement.enregistrer_modifs') }}
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Désaffecter une catégorie de frais ══ --}}
<div id="modal-desaffecter" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm ep-modal-danger">
    <div class="ep-modal-head">
      <h3>{{ __('etablissement.desaffecter_titre') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-desaffecter')">×</button>
    </div>
    <div class="ep-modal-body">
      <p style="font-size:13px;color:#555;line-height:1.6;">
        {!! __('etablissement.desaffecter_confirm', ['prenom' => $apprenant->prenom, 'nom' => $apprenant->nom]) !!}
        <strong id="desaffecter-categorie-nom"></strong>
      </p>
      <div style="background:#fdf3f3;border:1px solid #f5c6c6;border-radius:8px;padding:10px 12px;font-size:12px;color:#b13a3a;margin-top:10px;">
        {{ __('etablissement.desaffecter_avertissement') }}
      </div>
    </div>
    <div class="ep-modal-foot">
      <button type="button" class="btn-o" style="width:auto;padding:8px 16px;"
              onclick="epModal.close('modal-desaffecter')">{{ __('etablissement.annuler') }}</button>
      <form id="desaffecter-form" method="POST" style="display:inline;">
        @csrf @method('DELETE')
        <button type="submit" class="btn-r" style="width:auto;padding:8px 18px;">{{ __('etablissement.desaffecter') }}</button>
      </form>
    </div>
  </div>
</div>
@endpush

@section('content')

<div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
    <a href="{{ route('etablissement.apprenants.index') }}"
       style="color:#888;text-decoration:none;font-size:13px;">{{ __('etablissement.retour_liste') }}</a>
</div>

@if(session('success'))
<div class="epcard" style="background:#d1fae5;border-left:4px solid #059669;color:#065f46;margin-bottom:16px;padding:12px 16px;">
    ✓ {{ session('success') }}
</div>
@endif

<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:18px;">
    <div>
        <div style="font-size:19px;font-weight:700;">{{ $apprenant->nom }} {{ $apprenant->prenom }}</div>
        <div style="font-size:13px;color:#888;margin-top:2px;">
            {{ $apprenant->classe }}
            @if($apprenant->matricule) {{ __('etablissement.matricule_apos', ['matricule' => $apprenant->matricule]) }} @endif
            @if($apprenant->sexe) · {{ $apprenant->sexe === 'M' ? __('etablissement.masculin') : __('etablissement.feminin') }} @endif
        </div>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
        <span class="pill {{ match($apprenant->statut_paiement) {
            'regle' => 'pg', 'partiel' => 'pa', 'impaye' => 'pr', default => 'pa',
        } }}">
            {{ match($apprenant->statut_paiement) {
                'regle' => __('etablissement.regle'), 'partiel' => __('etablissement.partiel'), 'impaye' => __('etablissement.impaye'), default => $apprenant->statut_paiement,
            } }}
        </span>
        <button onclick="epModal.open('modal-modifier-apprenant')"
                class="btn-o" style="width:auto;padding:8px 16px;">
            {{ __('etablissement.dt_title_modifier') }}
        </button>
    </div>
</div>

{{-- ── Parents liés ── --}}
@if($apprenant->parents->isNotEmpty())
<div class="seclbl" style="margin-top:0;">{{ __('etablissement.parents_tuteurs') }}</div>
<div class="epcard" style="margin-bottom:18px;">
    @foreach($apprenant->parents as $parent)
    <div class="row">
        <div>
            <div style="font-size:13px;font-weight:600;">{{ $parent->name }}</div>
            <div style="font-size:11px;color:#888;">
                {{ $parent->telephone ?? $parent->email }} · {{ ucfirst($parent->pivot->lien) }}
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- ── Frais ── --}}
<div class="seclbl" style="margin-top:0;">
    {{ __('etablissement.frais_scolaires', ['annee' => $apprenant->frais->first()->annee_scolaire ?? '2025-2026']) }}
</div>
<div class="epcard" style="padding:0;overflow:hidden;margin-bottom:18px;">
    <table class="ep-table">
        <thead>
            <tr>
                <th>{{ __('etablissement.categorie') }}</th><th>{{ __('etablissement.montant_total') }}</th>
                <th>{{ __('etablissement.montant_paye') }}</th><th>{{ __('etablissement.reste') }}</th><th>{{ __('etablissement.statut') }}</th>
                <th>{{ __('etablissement.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($apprenant->frais as $frais)
            <tr>
                <td style="font-weight:600;">{{ $frais->categorieFrais->nom ?? '—' }}</td>
                <td>{{ number_format($frais->montant_total, 0, ',', ' ') }} FCFA</td>
                <td style="color:var(--ep-teal);">{{ number_format($frais->montant_paye, 0, ',', ' ') }} FCFA</td>
                <td style="color:var(--ep-red);">
                    {{ number_format($frais->montant_total - $frais->montant_paye, 0, ',', ' ') }} FCFA
                </td>
                <td>
                    <span class="pill {{ match($frais->statut) {
                        'regle' => 'pg', 'partiel' => 'pa', 'impaye' => 'pr', default => 'pa',
                    } }}">
                        {{ match($frais->statut) {
                            'regle' => __('etablissement.regle'), 'partiel' => __('etablissement.partiel'), 'impaye' => __('etablissement.impaye'), default => $frais->statut,
                        } }}
                    </span>
                </td>
                <td>
                    @if($frais->paiements->isEmpty())
                        <button type="button" class="btn-r" style="width:auto;font-size:11px;padding:5px 12px;box-shadow:0 1px 2px rgba(0,0,0,.08);display:inline-flex;align-items:center;gap:4px;"
                                data-desaffecter data-url="{{ route('etablissement.apprenants.desaffecter', [$apprenant, $frais]) }}"
                                data-nom="{{ $frais->categorieFrais->nom ?? '' }}">
                            <span class="material-symbols-outlined" style="font-size:13px;color:#fff;">logout</span>{{ __('etablissement.desaffecter') }}
                        </button>
                    @else
                        <button type="button" class="btn-r" style="width:auto;font-size:11px;padding:5px 12px;opacity:.45;cursor:not-allowed;filter:grayscale(1);display:inline-flex;align-items:center;gap:4px;"
                                title="{{ __('etablissement.desaffecter_impossible_paiement') }}" disabled>
                            <span class="material-symbols-outlined" style="font-size:13px;">lock</span>{{ __('etablissement.desaffecter') }}
                        </button>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center;color:#999;padding:20px 0;">{{ __('etablissement.aucun_frais') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ── Historique paiements ── --}}
<div class="seclbl" style="margin-top:0;">{{ __('etablissement.historique_paiements') }}</div>
<div class="epcard" style="padding:0;overflow:hidden;">
    <table class="ep-table">
        <thead>
            <tr>
                <th>{{ __('etablissement.reference') }}</th><th>{{ __('etablissement.montant') }}</th>
                <th>{{ __('etablissement.moyen') }}</th><th>{{ __('etablissement.date') }}</th><th>{{ __('etablissement.statut') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($apprenant->paiements()->latest('date_paiement')->get() as $paiement)
            <tr>
                <td style="color:#888;">{{ $paiement->reference }}</td>
                <td style="font-weight:600;">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</td>
                <td>{{ match($paiement->mode_paiement) {
                    'mtn_momo' => __('etablissement.mtn_momo'), 'orange_money' => __('etablissement.orange_money'),
                    default => $paiement->mode_paiement,
                } }}</td>
                <td>{{ $paiement->date_paiement
                    ? \Carbon\Carbon::parse($paiement->date_paiement)->format('d/m/Y') : '—' }}</td>
                <td>
                    <span class="pill {{ match($paiement->statut) {
                        'valide' => 'pg', 'en_attente' => 'pa',
                        'echoue' => 'pr', 'rembourse' => 'pb', default => 'pa',
                    } }}">
                        {{ match($paiement->statut) {
                            'valide' => __('etablissement.st_valide'), 'en_attente' => __('etablissement.st_en_attente'),
                            'echoue' => __('etablissement.st_echoue'), 'rembourse' => __('etablissement.st_rembourse'),
                            default => $paiement->statut,
                        } }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align:center;color:#999;padding:20px 0;">
                    {{ __('etablissement.aucun_paiement_enregistre') }}
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection

@push('scripts')
<script>
@if($errors->any())
document.addEventListener('DOMContentLoaded', function() {
    epModal.open('modal-modifier-apprenant');
});
@endif
document.addEventListener('DOMContentLoaded', function() {
    var form   = document.getElementById('desaffecter-form');
    var nomEl  = document.getElementById('desaffecter-categorie-nom');
    if (!form) return;
    document.querySelectorAll('[data-desaffecter]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            form.action = btn.getAttribute('data-url');
            nomEl.textContent = btn.getAttribute('data-nom') || '';
            epModal.open('modal-desaffecter');
        });
    });
});
</script>
@endpush
