@extends('layouts.etablissement')
@section('title', 'Apprenants')

@push('modals')

{{-- ══ MODAL : Ajouter un apprenant ══ --}}
<div id="modal-apprenant-create" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3>{{ __('etablissement.ajouter_apprenant_btn') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-apprenant-create')">×</button>
    </div>
    <form method="POST" action="{{ route('etablissement.apprenants.store') }}">
      @csrf
      <div class="ep-modal-body">
        <div class="g2">
          <div>
            <div class="lbl">{{ __('etablissement.lbl_nom') }}</div>
            <input class="inp" name="nom" value="{{ old('nom') }}" placeholder="FONO" required />
          </div>
          <div>
            <div class="lbl">{{ __('etablissement.lbl_prenom') }}</div>
            <input class="inp" name="prenom" value="{{ old('prenom') }}" placeholder="Brice" required />
          </div>
        </div>
        <div class="g2">
          <div>
            <div class="lbl">{{ __('etablissement.lbl_classe') }}</div>
            <input class="inp" name="classe" value="{{ old('classe') }}" placeholder="{{ __('etablissement.classe_ph') }}" required />
          </div>
          <div>
            <div class="lbl">{{ __('etablissement.matricule') }}</div>
            <input class="inp" name="matricule" value="{{ old('matricule') }}" placeholder="{{ __('etablissement.matricule_ph') }}" />
          </div>
        </div>
        <div class="g2">
          <div>
            <div class="lbl">{{ __('etablissement.ddn') }}</div>
            <input class="inp" type="date" name="date_naissance" value="{{ old('date_naissance') }}" />
          </div>
          <div>
            <div class="lbl">{{ __('etablissement.sexe') }}</div>
            <select class="select" name="sexe">
              <option value="">{{ __('etablissement.non_precise') }}</option>
              <option value="M" {{ old('sexe')=='M'?'selected':'' }}>{{ __('etablissement.masculin') }}</option>
              <option value="F" {{ old('sexe')=='F'?'selected':'' }}>{{ __('etablissement.feminin') }}</option>
            </select>
          </div>
        </div>
        <div>
          <div class="lbl">{{ __('etablissement.categorie_frais') }}</div>
          <select class="select" name="categorie_frais_id">
            <option value="">{{ __('etablissement.aucune_categorie_assign') }}</option>
            @foreach($categories ?? [] as $cat)
              <option value="{{ $cat->id }}" {{ old('categorie_frais_id')==$cat->id?'selected':'' }}>
                {{ $cat->nom }} — {{ number_format($cat->montant_total,0,',',' ') }} FCFA ({{ $cat->annee_scolaire }})
              </option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="epModal.close('modal-apprenant-create')">{{ __('etablissement.annuler') }}</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">{{ __('etablissement.enregistrer') }}</button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Import CSV ══ --}}
<div id="modal-import-csv" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3>{{ __('etablissement.importer_apprenants_csv') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-import-csv')">×</button>
        <div id="categorie-details" style="background:#f5f5f5;border-radius:8px;padding:12px;margin-bottom:12px;display:none;">
          <div style="font-size:12px;font-weight:600;color:#333;margin-bottom:8px;">{{ __('etablissement.categorie_details_titre') }}</div>
          <div id="categorie-info" style="font-size:12px;color:#666;">
            <!-- Rempli par JavaScript -->
          </div>
        </div>
    </div>
    <form method="POST" action="{{ route('etablissement.apprenants.import') }}" enctype="multipart/form-data">
      @csrf
      <div class="ep-modal-body">
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px;margin-bottom:16px;font-size:12px;color:#166534;">
          <strong>{{ __('etablissement.colonnes_attendues') }}</strong><br>
          <code style="font-size:11px;">nom, prenom, classe, matricule, date_naissance, sexe</code><br>
          <span style="color:#4b7c60;margin-top:4px;display:block;">{!! __('etablissement.colonnes_optionnelles') !!}</span>
        </div>
        <div class="lbl">{{ __('etablissement.fichier_csv') }}</div>
        <input type="file" name="fichier_csv" accept=".csv,.txt" class="inp" style="padding:8px;" required />
        <div style="font-size:11px;color:#888;margin-top:-8px;margin-bottom:12px;">{{ __('etablissement.csv_taille_hint') }}</div>
        <a href="{{ route('etablissement.apprenants.import.template') }}" style="font-size:12px;color:var(--ep-teal);text-decoration:none;">
          {{ __('etablissement.telecharger_modele') }}
        </a>
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="epModal.close('modal-import-csv')">{{ __('etablissement.annuler') }}</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">{{ __('etablissement.lancer_import') }}</button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Supprimer (générique, rempli par JS) ══ --}}
<div id="modal-delete-apprenant" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm ep-modal-danger">
    <div class="ep-modal-head">
      <h3>{{ __('etablissement.supprimer_apprenant') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-delete-apprenant')">×</button>
    </div>
    <div class="ep-modal-body">
      <p style="font-size:13px;color:#555;margin-bottom:8px;">
        {!! __('etablissement.confirm_delete_1') !!}
        <strong id="delete-apprenant-nom"></strong>
        {!! __('etablissement.confirm_delete_2') !!}
      </p>
    </div>
    <div class="ep-modal-foot">
      <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="epModal.close('modal-delete-apprenant')">{{ __('etablissement.annuler') }}</button>
      <form id="delete-apprenant-form" method="POST" style="display:inline;">
        @csrf @method('DELETE')
        <button type="submit" class="btn-r" style="width:auto;padding:8px 18px;">{{ __('etablissement.supprimer_definitivement') }}</button>
      </form>
    </div>
  </div>
</div>

{{-- ══ MODAL : Suppression multiple ══ --}}
<div id="modal-delete-multiple" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm ep-modal-danger">
    <div class="ep-modal-head">
      <h3>{{ __('etablissement.supprimer_selection') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-delete-multiple')">×</button>
    </div>
    <form id="delete-multiple-form" method="POST" action="{{ route('etablissement.apprenants.bulkDestroy') }}">
      @csrf @method('DELETE')
      <div class="ep-modal-body">
        <p style="font-size:13px;color:#555;line-height:1.6;">
          {!! __('etablissement.confirm_suppr_multiple') !!}
        </p>
        <div style="background:#fdf3f3;border:1px solid #f5c6c6;border-radius:8px;padding:10px 12px;font-size:12px;color:#b13a3a;margin-top:10px;">
          {{ __('etablissement.suppr_multiple_avertissement') }}
        </div>
        <div id="bulk-selection-ids"></div>
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;"
                onclick="epModal.close('modal-delete-multiple')">{{ __('etablissement.annuler') }}</button>
        <button type="submit" class="btn-r" style="width:auto;padding:8px 18px;">{{ __('etablissement.supprimer_definitivement') }}</button>
      </div>
    </form>
  </div>
</div>



{{-- ══ MODAL : Valider le rattachement ══ --}}
<div id="modal-valider-apprenant" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm">
    <div class="ep-modal-head">
      <h3>{{ __('etablissement.valider_rattachement') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-valider-apprenant')">x</button>
    </div>
    <div class="ep-modal-body">
      <p style="font-size:13px;color:#555;margin-bottom:8px;">
        {!! __('etablissement.confirm_valider_1') !!}
        {{ __('etablissement.confirm_valider_2') }}
      </p>
    </div>
    <div class="ep-modal-foot">
      <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="epModal.close('modal-valider-apprenant')">{{ __('etablissement.annuler') }}</button>
      <form id="valider-apprenant-form" method="POST" style="display:inline;">
        @csrf @method('PATCH')
        <button type="submit" class="btn-p" style="width:auto;padding:8px 18px;">{{ __('etablissement.confirmer_validation') }}</button>
      </form>
    </div>
  </div>
</div>

{{-- ══ MODAL : Rejeter le rattachement ══ --}}
<div id="modal-rejeter-apprenant" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm ep-modal-danger">
    <div class="ep-modal-head">
      <h3>{{ __('etablissement.rejeter_rattachement') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-rejeter-apprenant')">x</button>
    </div>
    <div class="ep-modal-body">
      <p style="font-size:13px;color:#555;margin-bottom:8px;">
        {!! __('etablissement.confirm_rejeter_1') !!}
        {!! __('etablissement.confirm_rejeter_2') !!}
      </p>
    </div>
    <div class="ep-modal-foot">
      <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="epModal.close('modal-rejeter-apprenant')">{{ __('etablissement.annuler') }}</button>
      <form id="rejeter-apprenant-form" method="POST" style="display:inline;">
        @csrf @method('DELETE')
        <button type="submit" class="btn-r" style="width:auto;padding:8px 18px;">{{ __('etablissement.rejeter_definitivement') }}</button>
      </form>
    </div>
  </div>
</div>

{{-- ══ MODAL : Voir apprenant (lecture seule) ══ --}}
<div id="modal-voir-apprenant" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3 id="voir-titre">{{ __('etablissement.fiche_apprenant') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-voir-apprenant')">×</button>
    </div>
    <div class="ep-modal-body" id="voir-body">
      <div style="text-align:center;color:#aaa;padding:20px;">{{ __('etablissement.chargement') }}</div>
    </div>
    <div class="ep-modal-foot">
      <button class="btn-p" style="width:auto;padding:8px 20px;" onclick="epModal.close('modal-voir-apprenant')">{{ __('etablissement.fermer') }}</button>
    </div>
  </div>
</div>

{{-- ══ MODAL : Modifier apprenant ══ --}}
<div id="modal-modifier-apprenant" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3>{{ __('etablissement.modifier_apprenant') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-modifier-apprenant')">×</button>
    </div>
    <form id="modifier-apprenant-form" method="POST">
      @csrf @method('PUT')
      <div class="ep-modal-body">
        <div class="g2">
          <div>
            <div class="lbl">{{ __('etablissement.lbl_nom') }}</div>
            <input class="inp" name="nom" id="edit-nom" required />
          </div>
          <div>
            <div class="lbl">{{ __('etablissement.lbl_prenom') }}</div>
            <input class="inp" name="prenom" id="edit-prenom" required />
          </div>
        </div>
        <div class="g2">
          <div>
            <div class="lbl">{{ __('etablissement.lbl_classe') }}</div>
            <input class="inp" name="classe" id="edit-classe" required />
          </div>
          <div>
            <div class="lbl">{{ __('etablissement.matricule') }}</div>
            <input class="inp" name="matricule" id="edit-matricule" />
          </div>
        </div>
        <div class="g2">
          <div>
            <div class="lbl">{{ __('etablissement.ddn') }}</div>
            <input class="inp" type="date" name="date_naissance" id="edit-ddn" />
          </div>
          <div>
            <div class="lbl">{{ __('etablissement.sexe') }}</div>
            <select class="select" name="sexe" id="edit-sexe">
              <option value="">{{ __('etablissement.non_precise') }}</option>
              <option value="M">{{ __('etablissement.masculin') }}</option>
              <option value="F">{{ __('etablissement.feminin') }}</option>
            </select>
          </div>
        </div>
        <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
          <input type="checkbox" name="actif" value="1" id="edit-actif" />
          {{ __('etablissement.apprenant_actif') }}
        </label>
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="epModal.close('modal-modifier-apprenant')">{{ __('etablissement.annuler') }}</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">{{ __('etablissement.enregistrer_modifs') }}</button>
      </div>
    </form>
  </div>
</div>

@endpush

@section('content')

@if(session('success'))
<div class="epcard" style="background:#d1fae5;border-left:4px solid #059669;color:#065f46;margin-bottom:16px;padding:12px 16px;">
  ✓ {{ session('success') }}
</div>
@endif
@if(session('import_erreurs'))
<div class="epcard" style="background:#fef3c7;border-left:4px solid #d97706;color:#92400e;margin-bottom:16px;padding:12px 16px;">
  <strong>{{ __('etablissement.probleme_import') }}</strong>
  <ul style="margin:8px 0 0;padding-left:18px;">
    @foreach(session('import_erreurs') as $err)<li style="font-size:13px;">{{ $err }}</li>@endforeach
  </ul>
</div>
@endif

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
  <div>
    <div style="font-size:17px;font-weight:700;">{{ __('etablissement.apprenants') }}</div>
    <div style="font-size:12px;color:#888;">{{ __('etablissement.nb_eleves_enregistres', ['count' => $apprenants->total() ?? $apprenants->count()]) }}</div>
  </div>
  <div style="display:flex;gap:8px;">
    <button class="btn-o" style="width:auto;padding:8px 16px;font-size:13px;"
            onclick="epModal.open('modal-import-csv')">
      {{ __('etablissement.importer_csv_btn') }}
    </button>
    <button class="btn-p" style="width:auto;"
            onclick="epModal.open('modal-apprenant-create')">
      {{ __('etablissement.ajouter_apprenant_btn') }}
    </button>
  </div>
</div>

{{-- Filtres --}}
<div class="epcard" style="margin-bottom:16px;display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
  <div style="flex:2;min-width:200px;">
    <div class="lbl">{{ __('etablissement.recherche_apprenant') }}</div>
    <input type="text" id="f-search" class="inp" style="margin-bottom:0;" placeholder="{{ __('etablissement.recherche_ph') }}">
  </div>
  <div style="flex:1;min-width:140px;">
    <div class="lbl">{{ __('etablissement.classe') }}</div>
    <select id="f-classe" class="select" style="margin-bottom:0;">
      <option value="">{{ __('etablissement.toutes') }}</option>
      @foreach(($classes ?? []) as $classe)
        <option value="{{ $classe }}">{{ $classe }}</option>
      @endforeach
    </select>
  </div>
  <div style="flex:1;min-width:140px;">
    <div class="lbl">{{ __('etablissement.statut_paiement') }}</div>
    <select id="f-statut" class="select" style="margin-bottom:0;">
      <option value="">{{ __('etablissement.tous') }}</option>
      <option value="regle">{{ __('etablissement.regle') }}</option>
      <option value="partiel">{{ __('etablissement.partiel') }}</option>
      <option value="impaye">{{ __('etablissement.impaye') }}</option>
    </select>
  </div>
  <button type="button" onclick="dtApprenants.ajax.reload()" class="btn-p" style="width:auto;padding:10px 20px;">{{ __('etablissement.filtrer') }}</button>
  <button type="button" onclick="reinitialiserFiltresApprenants()" class="btn-o" style="width:auto;padding:10px 16px;">{{ __('etablissement.reinitialiser') }}</button>
  <button type="button" id="btn-suppression-selection" onclick="ouvrirSuppressionSelection()"
          class="btn-r" disabled style="width:auto;padding:10px 16px;opacity:.5;cursor:not-allowed;">
    {{ __('etablissement.supprimer_selection') }} (<span id="nb-selection">0</span>)
  </button>
</div>

{{-- Tableau --}}
<div class="epcard" style="padding:0;">
  <table id="dt-apprenants" class="ep-dt text-sm">
    <thead>
      <tr>
        <th style="width:30px;"><input type="checkbox" id="select-all-apprenants" title="{{ __('etablissement.tout_selectionner') }}"></th>
        <th>{{ __('etablissement.matricule') }}</th><th>{{ __('etablissement.nom_complet') }}</th><th>{{ __('etablissement.classe') }}</th><th>{{ __('etablissement.sexe') }}</th>
        <th>{{ __('etablissement.statut_paiement') }}</th><th>{{ __('etablissement.actif') }}</th><th>{{ __('etablissement.origine') }}</th><th data-orderable="false">{{ __('etablissement.actions') }}</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>
@endsection

@push('scripts')
<script>
@php
$apprLabels = [
    'matricule'        => __('etablissement.matricule'),
    'nom_complet'      => __('etablissement.nom_complet'),
    'classe'           => __('etablissement.classe'),
    'sexe'             => __('etablissement.sexe'),
    'echeanciers_label'=> __('etablissement.echeanciers_label'),
    'aucun_echeancier' => __('etablissement.aucun_echeancier'),
    'tranche'          => __('etablissement.tranche'),
];
@endphp
var apprenantLabels = @json($apprLabels);

// ── Voir apprenant (lit la ligne DataTables via son id bouton) ──
function voirApprenantId(id) {
    var btn = document.querySelector('button[onclick*="voirApprenantId(' + id + ')"]');
    if (!btn) return;
    var row = btn.closest('tr');
    if (!row) return;
    var cells = row.querySelectorAll('td');
    document.getElementById('voir-titre').textContent = cells[1].textContent.trim();
    document.getElementById('voir-body').innerHTML =
        '<div class="g2" style="gap:16px;">' +
        '<div><div class="lbl">' + apprenantLabels.matricule + '</div><div style="font-weight:600;">' + cells[0].textContent.trim() + '</div></div>' +
        '<div><div class="lbl">' + apprenantLabels.nom_complet + '</div><div style="font-weight:600;">' + cells[1].textContent.trim() + '</div></div>' +
        '<div><div class="lbl">' + apprenantLabels.classe + '</div><div>' + cells[2].textContent.trim() + '</div></div>' +
        '<div><div class="lbl">' + apprenantLabels.sexe + '</div><div>' + cells[3].textContent.trim() + '</div></div>' +
        '</div>';
    epModal.open('modal-voir-apprenant');
}

// ── Valider rattachement ──
function ouvrirValidationApprenant(id, nom) {
    var baseUrl = "{{ url('etablissement/apprenants') }}/";
    document.getElementById('valider-apprenant-nom').textContent = nom;
    document.getElementById('valider-apprenant-form').action = baseUrl + id + '/valider';
    epModal.open('modal-valider-apprenant');
}

// ── Rejeter rattachement ──
function ouvrirRejetApprenant(id, nom) {
    var baseUrl = "{{ url('etablissement/apprenants') }}/";
    document.getElementById('rejeter-apprenant-nom').textContent = nom;
    document.getElementById('rejeter-apprenant-form').action = baseUrl + id + '/rejeter';
    epModal.open('modal-rejeter-apprenant');
}

// ── Modifier apprenant ──
function modifierApprenant(id, nom, prenom, classe, matricule, ddn, sexe, actif) {
    var baseUrl = "{{ url('etablissement/apprenants') }}/";
    document.getElementById('modifier-apprenant-form').action = baseUrl + id;
    document.getElementById('edit-nom').value       = nom;
    document.getElementById('edit-prenom').value    = prenom;
    document.getElementById('edit-classe').value    = classe;
    document.getElementById('edit-matricule').value = matricule || '';
    document.getElementById('edit-ddn').value       = ddn || '';
    document.getElementById('edit-sexe').value      = sexe || '';
    document.getElementById('edit-actif').checked   = actif;
    epModal.open('modal-modifier-apprenant');
}

// ── Supprimer apprenant ──
function supprimerApprenant(id, nom) {
    var baseUrl = "{{ url('etablissement/apprenants') }}/";
    document.getElementById('delete-apprenant-nom').textContent = nom;
    document.getElementById('delete-apprenant-form').action = baseUrl + id;
    epModal.open('modal-delete-apprenant');
}

// ── Catégorie de frais dans le modal create ──
var dtApprenants;
var btnSuppr, nbSpan, selectAll;

$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#dt-apprenants')) {
        $('#dt-apprenants').DataTable().destroy();
    }

    dtApprenants = epDT('#dt-apprenants', {
        serverSide: true,
        processing: true,
        lengthMenu: [[10, 15, 25, 50, -1], [10, 15, 25, 50, '{{ __('etablissement.dt_longueur_tous') }}']],
        language: {
            search: '',
            searchPlaceholder: '{{ __('etablissement.dt_rechercher') }}',
            lengthMenu: '{{ __('etablissement.dt_afficher_lignes') }}',
            info: '{{ __('etablissement.dt_info') }}',
            infoEmpty: '{{ __('etablissement.dt_info_empty') }}',
            infoFiltered: '{{ __('etablissement.dt_info_filtered') }}',
            zeroRecords: '{{ __('etablissement.dt_zero_records') }}',
            emptyTable: '{{ __('etablissement.dt_empty_table') }}',
        },
        ajax: {
            url: '{{ route("etablissement.apprenants.datatable") }}',
            type: 'GET',
            data: function(d) {
                d.classe = $('#f-classe').val();
                d.statut_paiement = $('#f-statut').val();
            }
        },
        columns: [
            { data: 0, orderable: false, responsivePriority: 9, className: 'dt-select', width: '30px' },
            { data: 1, orderable: true,  responsivePriority: 4 },
            { data: 2, orderable: true,  responsivePriority: 1 },
            { data: 3, orderable: true,  responsivePriority: 5 },
            { data: 4, orderable: true,  responsivePriority: 6 },
            { data: 5, orderable: true,  responsivePriority: 3 },
            { data: 6, orderable: true,  responsivePriority: 7 },
            { data: 7, orderable: false, responsivePriority: 8 },
            { data: 8, orderable: false, responsivePriority: 2 },
        ],
        order: [[2, 'asc']],
    });

    var searchTimer;
    $('#f-search').on('keyup', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            dtApprenants.search($('#f-search').val()).draw();
        }, 300);
    });

    $('#f-classe, #f-statut').on('change', function() {
        dtApprenants.ajax.reload();
    });

    // ── Sélection multiple / suppression groupée ──
    btnSuppr  = document.getElementById('btn-suppression-selection');
    nbSpan    = document.getElementById('nb-selection');
    selectAll = document.getElementById('select-all-apprenants');

    function mettreAJourSelection() {
        var cbs = document.querySelectorAll('#dt-apprenants tbody .select-apprenant:checked');
        var n = cbs.length;
        if (nbSpan) nbSpan.textContent = n;
        if (btnSuppr) {
            btnSuppr.disabled = n === 0;
            btnSuppr.style.opacity = n === 0 ? '.5' : '1';
            btnSuppr.style.cursor  = n === 0 ? 'not-allowed' : 'pointer';
        }
    }

    $('#dt-apprenants tbody').on('change', '.select-apprenant', function() {
        mettreAJourSelection();
    });

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            var checked = this.checked;
            document.querySelectorAll('#dt-apprenants tbody .select-apprenant').forEach(function(cb) {
                cb.checked = checked;
            });
            mettreAJourSelection();
        });
    }

    dtApprenants.on('draw', mettreAJourSelection);
    mettreAJourSelection();
});

function ouvrirSuppressionSelection() {
    var cbs = document.querySelectorAll('#dt-apprenants tbody .select-apprenant:checked');
    var ids = Array.from(cbs).map(function(cb) { return cb.value; });
    if (ids.length === 0) return;

    var holder = document.getElementById('bulk-selection-ids');
    holder.innerHTML = '';
    var total = document.getElementById('nb-selection');
    ids.forEach(function(id) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = id;
        holder.appendChild(input);
    });

    if (document.getElementById('select-all-apprenants') &&
        document.getElementById('select-all-apprenants').checked && nbSpan && nbSpan.textContent) {
        var all = document.createElement('input');
        all.type = 'hidden';
        all.name = 'supprimer_toutes_les_pages';
        all.value = '1';
        holder.appendChild(all);
    }

    epModal.open('modal-delete-multiple');
}

function reinitialiserFiltresApprenants() {
    $('#f-search').val('');
    $('#f-classe').val('');
    $('#f-statut').val('');
    dtApprenants.ajax.reload();
}

document.addEventListener('DOMContentLoaded', function() {
    var categoriesData = @json($categories ?? []);

    var selectCat = document.querySelector('select[name="categorie_frais_id"]');
    if (selectCat) {
        selectCat.addEventListener('change', function() {
            var categId = this.value;
            var detailsDiv = document.getElementById('categorie-details');
            var infoDiv    = document.getElementById('categorie-info');
            if (!categId || !detailsDiv) { if (detailsDiv) detailsDiv.style.display = 'none'; return; }

            var categorie = categoriesData.find(function(c) { return c.id == categId; });
            if (categorie) {
                var html = '<div style="margin-bottom:8px;">'
                    + '<strong>' + categorie.nom + '</strong><br>'
                    + '<span style="color:#888;">' + (categorie.annee_scolaire || '2025-2026') + '</span>'
                    + '</div>';

                if (categorie.echeanciers && categorie.echeanciers.length > 0) {
                    html += '<div style="font-size:11px;color:#666;"><strong>' + apprenantLabels.echeanciers_label + '</strong><br>';
                    categorie.echeanciers.forEach(function(ech) {
                        var date = new Date(ech.date_echeance).toLocaleDateString('fr-FR');
                        var montant = Number(ech.montant).toLocaleString('fr-FR');
                        html += '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2" style="vertical-align:middle;margin-right:4px;"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>'
                            + ' ' + apprenantLabels.tranche + ' ' + ech.numero_tranche + ' : ' + date + ' — ' + montant + ' FCFA<br>';
                    });
                    html += '</div>';
                } else {
                    html += '<div style="font-size:11px;color:#999;">' + apprenantLabels.aucun_echeancier + '</div>';
                }

                if (infoDiv) infoDiv.innerHTML = html;
                if (detailsDiv) detailsDiv.style.display = 'block';
            }
        });
    }

    // ── Ouvrir modal create si erreurs de validation ──
    @if($errors->any() && old('nom'))
    epModal.open('modal-apprenant-create');
    @endif
});
</script>
@endpush
