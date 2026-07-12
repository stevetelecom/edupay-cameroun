@extends('layouts.etablissement')
@section('title', 'Apprenants')

@push('modals')

{{-- ══ MODAL : Ajouter un apprenant ══ --}}
<div id="modal-apprenant-create" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3>+ Ajouter un apprenant</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-apprenant-create')">×</button>
    </div>
    <form method="POST" action="{{ route('etablissement.apprenants.store') }}">
      @csrf
      <div class="ep-modal-body">
        <div class="g2">
          <div>
            <div class="lbl">Nom *</div>
            <input class="inp" name="nom" value="{{ old('nom') }}" placeholder="FONO" required />
          </div>
          <div>
            <div class="lbl">Prénom *</div>
            <input class="inp" name="prenom" value="{{ old('prenom') }}" placeholder="Brice" required />
          </div>
        </div>
        <div class="g2">
          <div>
            <div class="lbl">Classe *</div>
            <input class="inp" name="classe" value="{{ old('classe') }}" placeholder="3ème A" required />
          </div>
          <div>
            <div class="lbl">Matricule</div>
            <input class="inp" name="matricule" value="{{ old('matricule') }}" placeholder="EP-0001 (auto si vide)" />
          </div>
        </div>
        <div class="g2">
          <div>
            <div class="lbl">Date de naissance</div>
            <input class="inp" type="date" name="date_naissance" value="{{ old('date_naissance') }}" />
          </div>
          <div>
            <div class="lbl">Sexe</div>
            <select class="select" name="sexe">
              <option value="">— Non précisé —</option>
              <option value="M" {{ old('sexe')=='M'?'selected':'' }}>Masculin</option>
              <option value="F" {{ old('sexe')=='F'?'selected':'' }}>Féminin</option>
            </select>
          </div>
        </div>
        <div>
          <div class="lbl">Catégorie de frais</div>
          <select class="select" name="categorie_frais_id">
            <option value="">— Aucune (à assigner plus tard) —</option>
            @foreach($categories ?? [] as $cat)
              <option value="{{ $cat->id }}" {{ old('categorie_frais_id')==$cat->id?'selected':'' }}>
                {{ $cat->nom }} — {{ number_format($cat->montant_total,0,',',' ') }} FCFA ({{ $cat->annee_scolaire }})
              </option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="epModal.close('modal-apprenant-create')">Annuler</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">Enregistrer</button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Import CSV ══ --}}
<div id="modal-import-csv" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3>↑ Importer des apprenants (CSV)</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-import-csv')">×</button>
        <div id="categorie-details" style="background:#f5f5f5;border-radius:8px;padding:12px;margin-bottom:12px;display:none;">
          <div style="font-size:12px;font-weight:600;color:#333;margin-bottom:8px;">📋 Détail de la catégorie sélectionnée</div>
          <div id="categorie-info" style="font-size:12px;color:#666;">
            <!-- Rempli par JavaScript -->
          </div>
        </div>
    </div>
    <form method="POST" action="{{ route('etablissement.apprenants.import') }}" enctype="multipart/form-data">
      @csrf
      <div class="ep-modal-body">
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px;margin-bottom:16px;font-size:12px;color:#166534;">
          <strong>Colonnes attendues :</strong><br>
          <code style="font-size:11px;">nom, prenom, classe, matricule, date_naissance, sexe</code><br>
          <span style="color:#4b7c60;margin-top:4px;display:block;">Les colonnes <em>matricule</em>, <em>date_naissance</em> et <em>sexe</em> sont optionnelles.</span>
        </div>
        <div class="lbl">Fichier CSV *</div>
        <input type="file" name="fichier_csv" accept=".csv,.txt" class="inp" style="padding:8px;" required />
        <div style="font-size:11px;color:#888;margin-top:-8px;margin-bottom:12px;">Taille max. : 2 Mo — encodage UTF-8 recommandé</div>
        <a href="{{ route('etablissement.apprenants.import.template') }}" style="font-size:12px;color:var(--ep-teal);text-decoration:none;">
          ↓ Télécharger le fichier modèle (.csv)
        </a>
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="epModal.close('modal-import-csv')">Annuler</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">Lancer l'import</button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Supprimer (générique, rempli par JS) ══ --}}
<div id="modal-delete-apprenant" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm ep-modal-danger">
    <div class="ep-modal-head">
      <h3>🗑 Supprimer l'apprenant</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-delete-apprenant')">×</button>
    </div>
    <div class="ep-modal-body">
      <p style="font-size:13px;color:#555;margin-bottom:8px;">
        Vous allez supprimer <strong id="delete-apprenant-nom"></strong>.<br>
        Cette action est <strong style="color:var(--ep-red);">irréversible</strong> — tous ses paiements liés seront conservés mais l'apprenant sera retiré du système.
      </p>
    </div>
    <div class="ep-modal-foot">
      <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="epModal.close('modal-delete-apprenant')">Annuler</button>
      <form id="delete-apprenant-form" method="POST" style="display:inline;">
        @csrf @method('DELETE')
        <button type="submit" class="btn-r" style="width:auto;padding:8px 18px;">Supprimer définitivement</button>
      </form>
    </div>
  </div>
</div>


{{-- ══ MODAL : Valider le rattachement ══ --}}
<div id="modal-valider-apprenant" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm">
    <div class="ep-modal-head">
      <h3>Valider le rattachement</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-valider-apprenant')">x</button>
    </div>
    <div class="ep-modal-body">
      <p style="font-size:13px;color:#555;margin-bottom:8px;">
        Vous allez valider le rattachement de <strong id="valider-apprenant-nom"></strong>.<br>
        Le payeur sera notifié et pourra consulter les frais de cet apprenant.
      </p>
    </div>
    <div class="ep-modal-foot">
      <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="epModal.close('modal-valider-apprenant')">Annuler</button>
      <form id="valider-apprenant-form" method="POST" style="display:inline;">
        @csrf @method('PATCH')
        <button type="submit" class="btn-p" style="width:auto;padding:8px 18px;">Confirmer la validation</button>
      </form>
    </div>
  </div>
</div>

{{-- ══ MODAL : Rejeter le rattachement ══ --}}
<div id="modal-rejeter-apprenant" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm ep-modal-danger">
    <div class="ep-modal-head">
      <h3>Rejeter le rattachement</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-rejeter-apprenant')">x</button>
    </div>
    <div class="ep-modal-body">
      <p style="font-size:13px;color:#555;margin-bottom:8px;">
        Vous allez rejeter définitivement la demande de rattachement de <strong id="rejeter-apprenant-nom"></strong>.<br>
        Cette action est <strong style="color:var(--ep-red);">irréversible</strong> — le payeur sera notifié du refus.
      </p>
    </div>
    <div class="ep-modal-foot">
      <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="epModal.close('modal-rejeter-apprenant')">Annuler</button>
      <form id="rejeter-apprenant-form" method="POST" style="display:inline;">
        @csrf @method('DELETE')
        <button type="submit" class="btn-r" style="width:auto;padding:8px 18px;">Rejeter définitivement</button>
      </form>
    </div>
  </div>
</div>

{{-- ══ MODAL : Voir apprenant (lecture seule) ══ --}}
<div id="modal-voir-apprenant" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3 id="voir-titre">Fiche apprenant</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-voir-apprenant')">×</button>
    </div>
    <div class="ep-modal-body" id="voir-body">
      <div style="text-align:center;color:#aaa;padding:20px;">Chargement…</div>
    </div>
    <div class="ep-modal-foot">
      <button class="btn-p" style="width:auto;padding:8px 20px;" onclick="epModal.close('modal-voir-apprenant')">Fermer</button>
    </div>
  </div>
</div>

{{-- ══ MODAL : Modifier apprenant ══ --}}
<div id="modal-modifier-apprenant" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3>✏ Modifier l'apprenant</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-modifier-apprenant')">×</button>
    </div>
    <form id="modifier-apprenant-form" method="POST">
      @csrf @method('PUT')
      <div class="ep-modal-body">
        <div class="g2">
          <div>
            <div class="lbl">Nom *</div>
            <input class="inp" name="nom" id="edit-nom" required />
          </div>
          <div>
            <div class="lbl">Prénom *</div>
            <input class="inp" name="prenom" id="edit-prenom" required />
          </div>
        </div>
        <div class="g2">
          <div>
            <div class="lbl">Classe *</div>
            <input class="inp" name="classe" id="edit-classe" required />
          </div>
          <div>
            <div class="lbl">Matricule</div>
            <input class="inp" name="matricule" id="edit-matricule" />
          </div>
        </div>
        <div class="g2">
          <div>
            <div class="lbl">Date de naissance</div>
            <input class="inp" type="date" name="date_naissance" id="edit-ddn" />
          </div>
          <div>
            <div class="lbl">Sexe</div>
            <select class="select" name="sexe" id="edit-sexe">
              <option value="">— Non précisé —</option>
              <option value="M">Masculin</option>
              <option value="F">Féminin</option>
            </select>
          </div>
        </div>
        <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
          <input type="checkbox" name="actif" value="1" id="edit-actif" />
          Apprenant actif
        </label>
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="epModal.close('modal-modifier-apprenant')">Annuler</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">Enregistrer les modifications</button>
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
  <strong>⚠ Problèmes détectés lors de l'import :</strong>
  <ul style="margin:8px 0 0;padding-left:18px;">
    @foreach(session('import_erreurs') as $err)<li style="font-size:13px;">{{ $err }}</li>@endforeach
  </ul>
</div>
@endif

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
  <div>
    <div style="font-size:17px;font-weight:700;">Apprenants</div>
    <div style="font-size:12px;color:#888;">{{ $apprenants->total() ?? $apprenants->count() }} élève(s) enregistré(s)</div>
  </div>
  <div style="display:flex;gap:8px;">
    <button class="btn-o" style="width:auto;padding:8px 16px;font-size:13px;"
            onclick="epModal.open('modal-import-csv')">
      ↑ Importer CSV
    </button>
    <button class="btn-p" style="width:auto;"
            onclick="epModal.open('modal-apprenant-create')">
      + Ajouter un apprenant
    </button>
  </div>
</div>

{{-- Filtres --}}
<div class="epcard" style="margin-bottom:16px;display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
  <div style="flex:2;min-width:200px;">
    <div class="lbl">Recherche (nom, prénom, matricule)</div>
    <input type="text" id="f-search" class="inp" style="margin-bottom:0;" placeholder="Ex: FONO Brice">
  </div>
  <div style="flex:1;min-width:140px;">
    <div class="lbl">Classe</div>
    <select id="f-classe" class="select" style="margin-bottom:0;">
      <option value="">Toutes</option>
      @foreach(($classes ?? []) as $classe)
        <option value="{{ $classe }}">{{ $classe }}</option>
      @endforeach
    </select>
  </div>
  <div style="flex:1;min-width:140px;">
    <div class="lbl">Statut paiement</div>
    <select id="f-statut" class="select" style="margin-bottom:0;">
      <option value="">Tous</option>
      <option value="regle">Réglé</option>
      <option value="partiel">Partiel</option>
      <option value="impaye">Impayé</option>
    </select>
  </div>
  <button type="button" onclick="dtApprenants.ajax.reload()" class="btn-p" style="width:auto;padding:10px 20px;">Filtrer</button>
  <button type="button" onclick="reinitialiserFiltresApprenants()" class="btn-o" style="width:auto;padding:10px 16px;">Réinitialiser</button>
</div>

{{-- Tableau --}}
<div class="epcard" style="padding:0;">
  <table id="dt-apprenants" class="ep-dt text-sm">
    <thead>
      <tr>
        <th>Matricule</th><th>Nom complet</th><th>Classe</th><th>Sexe</th>
        <th>Statut paiement</th><th>Actif</th><th>Origine</th><th data-orderable="false">Actions</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>
@endsection

@push('scripts')
<script>
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
        '<div><div class="lbl">Matricule</div><div style="font-weight:600;">' + cells[0].textContent.trim() + '</div></div>' +
        '<div><div class="lbl">Nom complet</div><div style="font-weight:600;">' + cells[1].textContent.trim() + '</div></div>' +
        '<div><div class="lbl">Classe</div><div>' + cells[2].textContent.trim() + '</div></div>' +
        '<div><div class="lbl">Sexe</div><div>' + cells[3].textContent.trim() + '</div></div>' +
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

$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#dt-apprenants')) {
        $('#dt-apprenants').DataTable().destroy();
    }

    dtApprenants = epDT('#dt-apprenants', {
        serverSide: true,
        processing: true,
        ajax: {
            url: '{{ route("etablissement.apprenants.datatable") }}',
            type: 'GET',
            data: function(d) {
                d.classe = $('#f-classe').val();
                d.statut_paiement = $('#f-statut').val();
            }
        },
        columns: [
            { data: 0, orderable: true,  responsivePriority: 4 },
            { data: 1, orderable: true,  responsivePriority: 1 },
            { data: 2, orderable: true,  responsivePriority: 5 },
            { data: 3, orderable: true,  responsivePriority: 6 },
            { data: 4, orderable: true,  responsivePriority: 3 },
            { data: 5, orderable: true,  responsivePriority: 7 },
            { data: 6, orderable: false, responsivePriority: 8 },
            { data: 7, orderable: false, responsivePriority: 2 },
        ],
        order: [[1, 'asc']],
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
});

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
                    html += '<div style="font-size:11px;color:#666;"><strong>Échéanciers :</strong><br>';
                    categorie.echeanciers.forEach(function(ech) {
                        var date = new Date(ech.date_echeance).toLocaleDateString('fr-FR');
                        var montant = Number(ech.montant).toLocaleString('fr-FR');
                        html += '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2" style="vertical-align:middle;margin-right:4px;"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>'
                            + ' Tranche ' + ech.numero_tranche + ' : ' + date + ' — ' + montant + ' FCFA<br>';
                    });
                    html += '</div>';
                } else {
                    html += '<div style="font-size:11px;color:#999;">Aucun échéancier défini.</div>';
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
