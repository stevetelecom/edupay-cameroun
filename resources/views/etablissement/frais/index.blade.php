@extends('layouts.etablissement')
@section('title', __('etablissement.frais_echeanciers_titre'))

@push('modals')

{{-- ══ MODAL : Créer une catégorie ══ --}}
<div id="modal-create-frais" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3>@lang('etablissement.nouvelle_categorie_frais')</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-create-frais')">×</button>
    </div>
    <form method="POST" action="{{ route('etablissement.frais.store') }}">
      @csrf
      <div class="ep-modal-body">
        <div class="g2">
          <div>
            <div class="lbl">@lang('etablissement.lbl_nom')</div>
            <input class="inp" name="nom" value="{{ old('nom') }}" placeholder="ex : Scolarité, Inscription" required />
          </div>
          <div>
            <div class="lbl">@lang('etablissement.annee_scolaire')</div>
            <input class="inp" name="annee_scolaire" value="{{ old('annee_scolaire', $etablissement->annee_scolaire_active ?? '2025-2026') }}" required />
          </div>
        </div>
        <div>
          <div class="lbl">@lang('etablissement.description')</div>
          <input class="inp" name="description" value="{{ old('description') }}" placeholder="@lang('etablissement.description_optionnelle_ph')" />
        </div>
        <div class="g2">
          <div>
            <div class="lbl">@lang('etablissement.montant_total_fcfa')</div>
            <input class="inp" type="number" name="montant_total" value="{{ old('montant_total') }}" placeholder="ex : 52500" required />
          </div>
          <div>
            <div class="lbl">@lang('etablissement.nb_tranches_max')</div>
            <input class="inp" type="number" name="nb_tranches_max" value="{{ old('nb_tranches_max', 2) }}" min="1" max="12" />
            <div style="font-size:12px;color:#666;margin-top:6px;">@lang('etablissement.echeances_optionnelles')</div>
          </div>
        </div>
        <div style="display:flex;gap:20px;align-items:center;">
          <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
            <input type="checkbox" name="fractionnable" value="1" {{ old('fractionnable') ? 'checked' : '' }}>
            @lang('etablissement.paiement_fractionnable')
          </label>
          <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
            <input type="checkbox" name="actif" value="1" checked>
            @lang('etablissement.active')
          </label>
        </div>
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="epModal.close('modal-create-frais')">@lang('etablissement.annuler')</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">@lang('etablissement.creer')</button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Modifier une catégorie ══ --}}
<div id="modal-edit-frais" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3>@lang('etablissement.modifier_categorie')</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-edit-frais')">×</button>
    </div>
    <form id="edit-frais-form" method="POST">
      @csrf @method('PUT')
      <div class="ep-modal-body">
        <div class="g2">
          <div>
            <div class="lbl">@lang('etablissement.lbl_nom')</div>
            <input class="inp" name="nom" id="edit-frais-nom" required />
          </div>
          <div>
            <div class="lbl">@lang('etablissement.annee_scolaire')</div>
            <input class="inp" name="annee_scolaire" id="edit-frais-annee" required />
          </div>
        </div>
        <div>
          <div class="lbl">@lang('etablissement.description')</div>
          <input class="inp" name="description" id="edit-frais-desc" />
        </div>
        <div class="g2">
          <div>
            <div class="lbl">@lang('etablissement.montant_total_fcfa')</div>
            <input class="inp" type="number" name="montant_total" id="edit-frais-montant" required />
          </div>
          <div>
            <div class="lbl">@lang('etablissement.nb_tranches_max')</div>
            <input class="inp" type="number" name="nb_tranches_max" id="edit-frais-tranches" min="1" max="12" />
          </div>
        </div>
        <div style="display:flex;gap:20px;align-items:center;">
          <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
            <input type="checkbox" name="fractionnable" id="edit-frais-frac" value="1">
            @lang('etablissement.paiement_fractionnable')
          </label>
          <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
            <input type="checkbox" name="actif" id="edit-frais-actif" value="1">
            @lang('etablissement.active')
          </label>
        </div>
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="epModal.close('modal-edit-frais')">@lang('etablissement.annuler')</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">{{ __('etablissement.enregistrer') }}</button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Supprimer une catégorie ══ --}}
<div id="modal-delete-frais" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm ep-modal-danger">
    <div class="ep-modal-head">
      <h3>@lang('etablissement.supprimer_categorie')</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-delete-frais')">×</button>
    </div>
    <div class="ep-modal-body">
      <p style="font-size:13px;color:#555;line-height:1.6;">
        {!! __('etablissement.confirm_suppr_categorie') !!}
        <strong id="delete-frais-nom"></strong>
      </p>
    </div>
    <div class="ep-modal-foot">
      <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="epModal.close('modal-delete-frais')">@lang('etablissement.annuler')</button>
      <form id="delete-frais-form" method="POST" style="display:inline;">
        @csrf @method('DELETE')
        <button type="submit" class="btn-r" style="width:auto;padding:8px 18px;">@lang('etablissement.dt_title_supprimer')</button>
      </form>
    </div>
  </div>
</div>

{{-- ══ MODAL : Ajouter une tranche ══ --}}
<div id="modal-add-tranche" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3 id="add-tranche-titre">@lang('etablissement.ajouter_tranche')</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-add-tranche')">×</button>
    </div>
    <form id="add-tranche-form" method="POST">
      @csrf
      <div class="ep-modal-body">
        <div class="g2">
          <div>
            <div class="lbl">@lang('etablissement.ntranche')</div>
            <input class="inp" type="number" name="numero_tranche" id="add-tranche-num" min="1" required />
          </div>
          <div>
            <div class="lbl">@lang('etablissement.montant_fcfa')</div>
            <input class="inp" type="number" name="montant" placeholder="ex : 26250" required />
          </div>
        </div>
        <div class="g2">
          <div>
            <div class="lbl">@lang('etablissement.date_echeance')</div>
            <input class="inp" type="date" name="date_echeance" required />
          </div>
          <div>
            <div class="lbl">@lang('etablissement.libelle')</div>
            <input class="inp" name="libelle" placeholder="ex : Tranche 1 — Inscription" />
          </div>
        </div>
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="epModal.close('modal-add-tranche')">@lang('etablissement.annuler')</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">@lang('etablissement.ajouter')</button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Affecter aux apprenants ══ --}}
<div id="modal-affecter" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm">
    <div class="ep-modal-head">
      <h3 id="affecter-titre">@lang('etablissement.affecter_apprenants')</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-affecter')">×</button>
    </div>
    <form id="affecter-form" method="POST">
      @csrf
      <div class="ep-modal-body">
        <div style="background:#f0fdf4;border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:12px;color:#166534;">
          {!! __('etablissement.affecter_hint') !!}
        </div>
        <div class="lbl">@lang('etablissement.filtrer_classe')</div>
        <select class="select" name="classe">
          <option value="">@lang('etablissement.toutes_classes')</option>
          @foreach(($classes ?? []) as $classe)
            <option value="{{ $classe }}">{{ $classe }}</option>
          @endforeach
        </select>
        @if(empty($classes->count()))
          <div style="font-size:12px;color:#666;margin-top:8px;">@lang('etablissement.aucune_classe')</div>
        @endif
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="epModal.close('modal-affecter')">@lang('etablissement.annuler')</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">@lang('etablissement.affecter')</button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Voir échéancier ══ --}}
<div id="modal-voir-frais" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3 id="voir-frais-titre">@lang('etablissement.echeancier')</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-voir-frais')">×</button>
    </div>
    <div class="ep-modal-body" id="voir-frais-body"></div>
    <div class="ep-modal-foot">
      <button class="btn-p" style="width:auto;padding:8px 20px;" onclick="epModal.close('modal-voir-frais')">@lang('etablissement.fermer')</button>
    </div>
  </div>
</div>

{{-- ══ MODAL : Éditer une tranche ══ --}}
<div id="modal-edit-tranche" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3>@lang('etablissement.modifier_tranche')</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-edit-tranche')">×</button>
    </div>
    <form id="edit-tranche-form" method="POST">
      @csrf @method('PUT')
      <div class="ep-modal-body">
        <div class="g2">
          <div>
            <div class="lbl">@lang('etablissement.ntranche')</div>
            <input class="inp" type="number" name="numero_tranche" id="edit-tranche-num" min="1" required />
          </div>
          <div>
            <div class="lbl">@lang('etablissement.montant_fcfa')</div>
            <input class="inp" type="number" name="montant" id="edit-tranche-montant" required />
          </div>
        </div>
        <div class="g2">
          <div>
            <div class="lbl">@lang('etablissement.date_echeance')</div>
            <input class="inp" type="date" name="date_echeance" id="edit-tranche-date" required />
          </div>
          <div>
            <div class="lbl">@lang('etablissement.libelle')</div>
            <input class="inp" name="libelle" id="edit-tranche-libelle" />
          </div>
        </div>
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="epModal.close('modal-edit-tranche')">@lang('etablissement.annuler')</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">{{ __('etablissement.enregistrer') }}</button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Supprimer une tranche ══ --}}
<div id="modal-delete-tranche" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm ep-modal-danger">
    <div class="ep-modal-head">
      <h3>@lang('etablissement.supprimer_tranche')</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-delete-tranche')">×</button>
    </div>
    <div class="ep-modal-body">
      <p style="font-size:13px;color:#555;line-height:1.6;">
        {!! __('etablissement.confirm_suppr_tranche') !!}
        <strong id="delete-tranche-nom"></strong>
      </p>
    </div>
    <div class="ep-modal-foot">
      <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="epModal.close('modal-delete-tranche')">@lang('etablissement.annuler')</button>
      <form id="delete-tranche-form" method="POST" style="display:inline;">
        @csrf @method('DELETE')
        <button type="submit" class="btn-r" style="width:auto;padding:8px 18px;">@lang('etablissement.dt_title_supprimer')</button>
      </form>
    </div>
  </div>
</div>

@endpush

@section('content')


<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
  <div>
    <div style="font-size:17px;font-weight:700;">{{ __('etablissement.categories_titre', ['annee' => $etablissement->annee_scolaire_active ?? '2025-2026']) }}</div>
    <div style="font-size:12px;color:#888;">{{ __('etablissement.nb_categories', ['count' => $categories->count()]) }}</div>
  </div>
  <button onclick="epModal.open('modal-create-frais')" class="btn-p" style="width:auto;">
    @lang('etablissement.nouvelle_categorie_btn')
  </button>
</div>

<div class="epcard" style="padding:0;overflow:hidden;">
  <div style="padding:14px 18px;border-bottom:1px solid #f0f0f0;">
    <span style="font-size:11px;font-weight:600;color:#999;text-transform:uppercase;letter-spacing:.05em;">@lang('etablissement.categories_de_frais_upper')</span>
  </div>

  @forelse($categories as $cat)
  <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid #f5f5f5;flex-wrap:wrap;gap:10px;">
    <div style="flex:1;min-width:200px;">
      <div style="font-weight:600;font-size:14px;">{{ $cat->nom }}</div>
      <div style="font-size:12px;color:#888;margin-top:2px;">{{ $cat->description ?? '' }}</div>
      <div style="display:flex;gap:8px;margin-top:6px;flex-wrap:wrap;align-items:center;">
        <span class="pill pb">{{ $cat->annee_scolaire }}</span>
        <span class="pill {{ $cat->actif ? 'pg' : 'pr' }}">{{ $cat->actif ? __('etablissement.active') : __('etablissement.inactive') }}</span>
        @if($cat->fractionnable)
          <span class="pill pa">{{ $cat->nb_tranches_max }} {{ $cat->nb_tranches_max > 1 ? __('etablissement.tranches_unit') : __('etablissement.tranche_unit') }}</span>
          @if($cat->echeanciers->count())
            <span class="pill" style="background:#EDE9FE;color:#5B21B6;cursor:pointer;display:inline-flex;align-items:center;gap:4px;"
                  onclick="voirEcheancier({{ $cat->id }}, '{{ addslashes($cat->nom) }}', {{ $cat->echeanciers->toJson() }})">
              <span class="material-symbols-outlined" style="font-size:13px;color:#5B21B6;">calendar_month</span>
              {{ __('etablissement.echeance_compte', ['count' => $cat->echeanciers->count()]) }}
            </span>
          @else
            <span class="pill" style="background:#F3F4F6;color:#888;">@lang('etablissement.pas_echeancier')</span>
          @endif
        @endif
      </div>
    </div>
    <div style="font-size:16px;font-weight:700;color:#085041;margin:0 16px;white-space:nowrap;">
      {{ number_format($cat->montant_total, 0, ',', ' ') }} FCFA
    </div>
    <div style="display:flex;gap:6px;flex-wrap:wrap;">
      <button onclick="affecterFrais({{ $cat->id }}, '{{ addslashes($cat->nom) }}')"
              class="btn-o" style="width:auto;padding:6px 12px;font-size:12px;">
        @lang('etablissement.affecter_btn')
      </button>
      @if($cat->fractionnable)
        <button onclick="ajouterTranche({{ $cat->id }}, '{{ addslashes($cat->nom) }}', {{ $cat->echeanciers->count() + 1 }})"
                class="btn-o" style="width:auto;padding:6px 12px;font-size:12px;">
          @lang('etablissement.ajouter_tranche_btn')
        </button>
      @endif
      <button onclick="modifierFrais({{ $cat->id }}, '{{ addslashes($cat->nom) }}', '{{ addslashes($cat->description ?? '') }}', {{ $cat->montant_total }}, {{ $cat->nb_tranches_max }}, {{ $cat->fractionnable ? 'true' : 'false' }}, {{ $cat->actif ? 'true' : 'false' }}, '{{ $cat->annee_scolaire }}')"
              class="btn-o" style="width:auto;padding:6px 12px;font-size:12px;">
        @lang('etablissement.modifier_btn')
      </button>
      <button onclick="supprimerFrais({{ $cat->id }}, '{{ addslashes($cat->nom) }}')"
              title="@lang('etablissement.supprimer_categorie')"
              style="width:auto;padding:6px 12px;font-size:12px;background:transparent;color:var(--ep-red);border:2px solid var(--ep-red);border-radius:var(--radius-md);cursor:pointer;display:inline-flex;align-items:center;justify-content:center;">
        <span class="material-symbols-outlined" style="font-size:16px;color:var(--ep-red);">delete</span>
      </button>
    </div>
  </div>
  @empty
  <div style="padding:40px;text-align:center;color:#aaa;">
    <div style="width:48px;height:48px;background:#f0f0f0;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
      <span class="material-symbols-outlined" style="font-size:24px;color:#aaa;">receipt_long</span>
    </div>
    @lang('etablissement.aucune_categorie_frais')
    <button onclick="epModal.open('modal-create-frais')" style="color:var(--ep-teal);background:none;border:none;cursor:pointer;font-size:13px;">
      @lang('etablissement.creer_premiere')
    </button>
  </div>
  @endforelse
</div>

<div style="margin-top:14px;background:var(--ep-gold-lt);border-radius:var(--radius-md);padding:12px 16px;font-size:12px;color:#854F0B;border-left:3px solid var(--ep-gold);">
  {!! __('etablissement.cdc_note') !!}
</div>

@endsection

@push('scripts')
<script>
@php
$epFraisL10N = [
    'ajouter_tranche_prefix' => __('etablissement.ajouter_tranche_btn'),
    'affecter_prefix'       => __('etablissement.affecter_btn'),
    'echeancier_prefix'     => __('etablissement.echeancier'),
    'col_tranche'           => __('etablissement.tranche'),
    'col_libelle'           => __('etablissement.libelle'),
    'col_montant'           => __('etablissement.montant'),
    'col_echeance'          => __('etablissement.echeance_col'),
    'col_actions'           => __('etablissement.actions'),
    'tranche_word'          => __('etablissement.tranche'),
];
@endphp
const EP_FRAIS = @json($epFraisL10N);

function modifierFrais(id, nom, desc, montant, tranches, frac, actif, annee) {
    document.getElementById('edit-frais-form').action = "{{ url('etablissement/frais') }}/" + id;
    document.getElementById('edit-frais-nom').value     = nom;
    document.getElementById('edit-frais-desc').value    = desc;
    document.getElementById('edit-frais-montant').value = montant;
    document.getElementById('edit-frais-tranches').value = tranches;
    document.getElementById('edit-frais-annee').value   = annee;
    document.getElementById('edit-frais-frac').checked  = frac;
    document.getElementById('edit-frais-actif').checked = actif;
    epModal.open('modal-edit-frais');
}

function supprimerFrais(id, nom) {
    document.getElementById('delete-frais-nom').textContent = nom;
    document.getElementById('delete-frais-form').action = "{{ url('etablissement/frais') }}/" + id;
    epModal.open('modal-delete-frais');
}

function ajouterTranche(catId, nom, nextNum) {
    document.getElementById('add-tranche-titre').textContent = EP_FRAIS.ajouter_tranche_prefix + ' — ' + nom;
    document.getElementById('add-tranche-form').action = "{{ url('etablissement/frais') }}/" + catId + "/echeancier";
    document.getElementById('add-tranche-num').value = nextNum;
    epModal.open('modal-add-tranche');
}

function affecterFrais(id, nom) {
    document.getElementById('affecter-titre').textContent = EP_FRAIS.affecter_prefix + ' — ' + nom;
    document.getElementById('affecter-form').action = "{{ url('etablissement/frais') }}/" + id + "/affecter";
    epModal.open('modal-affecter');
}

function voirEcheancier(id, nom, echeances) {
    document.getElementById('voir-frais-titre').textContent = EP_FRAIS.echeancier_prefix + ' — ' + nom;
  var html = '<table class="ep-table"><thead><tr><th>' + EP_FRAIS.col_tranche + '</th><th>' + EP_FRAIS.col_libelle + '</th><th>' + EP_FRAIS.col_montant + '</th><th>' + EP_FRAIS.col_echeance + '</th><th>' + EP_FRAIS.col_actions + '</th></tr></thead><tbody>';
  echeances.forEach(function(e) {
    html += '<tr>'
      + '<td><span class="pill pb">T' + e.numero_tranche + '</span></td>'
      + '<td>' + (e.libelle || EP_FRAIS.tranche_word + ' ' + e.numero_tranche) + '</td>'
      + '<td style="font-weight:600;">' + Number(e.montant).toLocaleString('fr-FR') + ' FCFA</td>'
      + '<td>' + (e.date_echeance ? new Date(e.date_echeance).toLocaleDateString('fr-FR') : '—') + '</td>'
      + '<td style="white-space:nowrap;">'
      + '<button class="btn-o" style="padding:6px 10px;margin-right:6px;font-size:12px;display:inline-flex;align-items:center;" onclick="editTranche(' + id + ',' + e.id + ',' + e.numero_tranche + ', \'' + (e.libelle ? addslashes(e.libelle) : '') + '\',' + e.montant + ', \'' + (e.date_echeance ? e.date_echeance : '') + '\')"><span class="material-symbols-outlined" style="font-size:14px;color:var(--ep-teal);">edit</span></button>'
      + '<button class="btn-r" style="padding:6px 10px;font-size:12px;display:inline-flex;align-items:center;" onclick="deleteTranche(' + id + ',' + e.id + ', \'' + (e.libelle ? addslashes(e.libelle) : '') + '\')"><span class="material-symbols-outlined" style="font-size:14px;color:#fff;">delete</span></button>'
      + '</td>'
      + '</tr>';
  });
  html += '</tbody></table>';
    document.getElementById('voir-frais-body').innerHTML = html;
    epModal.open('modal-voir-frais');
}

function editTranche(catId, echeId, numero, libelle, montant, date) {
  // action: PUT to /etablissement/frais/{catId}/echeancier/{echeId}
  document.getElementById('edit-tranche-form').action = "{{ url('etablissement/frais') }}/" + catId + "/echeancier/" + echeId;
  document.getElementById('edit-tranche-num').value = numero;
  document.getElementById('edit-tranche-libelle').value = libelle || '';
  document.getElementById('edit-tranche-montant').value = montant || '';
  document.getElementById('edit-tranche-date').value = date || '';
  epModal.open('modal-edit-tranche');
}

function deleteTranche(catId, echeId, libelle) {
  document.getElementById('delete-tranche-nom').textContent = libelle || (EP_FRAIS.tranche_word + ' ' + echeId);
  document.getElementById('delete-tranche-form').action = "{{ url('etablissement/frais') }}/" + catId + "/echeancier/" + echeId;
  epModal.open('modal-delete-tranche');
}

// helper to escape quotes inside JS-generated attributes
function addslashes(str) {
  if (!str) return '';
  return String(str).replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/\"/g, '\\"').replace(/\n/g, '\\n');
}
</script>
@endpush
