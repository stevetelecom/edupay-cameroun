@extends('layouts.etablissement')
@section('title', 'Frais & Échéanciers')

@push('modals')

{{-- ══ MODAL : Créer une catégorie ══ --}}
<div id="modal-create-frais" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3>+ Nouvelle catégorie de frais</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-create-frais')">×</button>
    </div>
    <form method="POST" action="{{ route('etablissement.frais.store') }}">
      @csrf
      <div class="ep-modal-body">
        <div class="g2">
          <div>
            <div class="lbl">Nom *</div>
            <input class="inp" name="nom" value="{{ old('nom') }}" placeholder="ex : Scolarité, Inscription" required />
          </div>
          <div>
            <div class="lbl">Année scolaire *</div>
            <input class="inp" name="annee_scolaire" value="{{ old('annee_scolaire', $etablissement->annee_scolaire_active ?? '2025-2026') }}" required />
          </div>
        </div>
        <div>
          <div class="lbl">Description</div>
          <input class="inp" name="description" value="{{ old('description') }}" placeholder="optionnel" />
        </div>
        <div class="g2">
          <div>
            <div class="lbl">Montant total (FCFA) *</div>
            <input class="inp" type="number" name="montant_total" value="{{ old('montant_total') }}" placeholder="ex : 52500" required />
          </div>
          <div>
            <div class="lbl">Nb tranches max</div>
            <input class="inp" type="number" name="nb_tranches_max" value="{{ old('nb_tranches_max', 2) }}" min="1" max="12" />
          </div>
        </div>
        <div style="display:flex;gap:20px;align-items:center;">
          <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
            <input type="checkbox" name="fractionnable" value="1" {{ old('fractionnable') ? 'checked' : '' }}>
            Paiement fractionnable
          </label>
          <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
            <input type="checkbox" name="actif" value="1" checked>
            Active
          </label>
        </div>
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="epModal.close('modal-create-frais')">Annuler</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">Créer</button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Modifier une catégorie ══ --}}
<div id="modal-edit-frais" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3>✎ Modifier la catégorie</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-edit-frais')">×</button>
    </div>
    <form id="edit-frais-form" method="POST">
      @csrf @method('PUT')
      <div class="ep-modal-body">
        <div class="g2">
          <div>
            <div class="lbl">Nom *</div>
            <input class="inp" name="nom" id="edit-frais-nom" required />
          </div>
          <div>
            <div class="lbl">Année scolaire *</div>
            <input class="inp" name="annee_scolaire" id="edit-frais-annee" required />
          </div>
        </div>
        <div>
          <div class="lbl">Description</div>
          <input class="inp" name="description" id="edit-frais-desc" />
        </div>
        <div class="g2">
          <div>
            <div class="lbl">Montant total (FCFA) *</div>
            <input class="inp" type="number" name="montant_total" id="edit-frais-montant" required />
          </div>
          <div>
            <div class="lbl">Nb tranches max</div>
            <input class="inp" type="number" name="nb_tranches_max" id="edit-frais-tranches" min="1" max="12" />
          </div>
        </div>
        <div style="display:flex;gap:20px;align-items:center;">
          <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
            <input type="checkbox" name="fractionnable" id="edit-frais-frac" value="1">
            Paiement fractionnable
          </label>
          <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
            <input type="checkbox" name="actif" id="edit-frais-actif" value="1">
            Active
          </label>
        </div>
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="epModal.close('modal-edit-frais')">Annuler</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">Enregistrer</button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Supprimer une catégorie ══ --}}
<div id="modal-delete-frais" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm ep-modal-danger">
    <div class="ep-modal-head">
      <h3>🗑 Supprimer la catégorie</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-delete-frais')">×</button>
    </div>
    <div class="ep-modal-body">
      <p style="font-size:13px;color:#555;line-height:1.6;">
        Vous allez supprimer <strong id="delete-frais-nom" style="color:var(--ep-red);"></strong>.<br><br>
        ⚠ Toutes les <strong>échéances</strong> associées seront supprimées.<br>
        Les <strong>paiements déjà enregistrés</strong> resteront dans l'historique.
      </p>
    </div>
    <div class="ep-modal-foot">
      <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="epModal.close('modal-delete-frais')">Annuler</button>
      <form id="delete-frais-form" method="POST" style="display:inline;">
        @csrf @method('DELETE')
        <button type="submit" class="btn-r" style="width:auto;padding:8px 18px;">Supprimer</button>
      </form>
    </div>
  </div>
</div>

{{-- ══ MODAL : Ajouter une tranche ══ --}}
<div id="modal-add-tranche" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3 id="add-tranche-titre">+ Ajouter une tranche</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-add-tranche')">×</button>
    </div>
    <form id="add-tranche-form" method="POST">
      @csrf
      <div class="ep-modal-body">
        <div class="g2">
          <div>
            <div class="lbl">N° tranche *</div>
            <input class="inp" type="number" name="numero_tranche" id="add-tranche-num" min="1" required />
          </div>
          <div>
            <div class="lbl">Montant (FCFA) *</div>
            <input class="inp" type="number" name="montant" placeholder="ex : 26250" required />
          </div>
        </div>
        <div class="g2">
          <div>
            <div class="lbl">Date d'échéance *</div>
            <input class="inp" type="date" name="date_echeance" required />
          </div>
          <div>
            <div class="lbl">Libellé</div>
            <input class="inp" name="libelle" placeholder="ex : Tranche 1 — Inscription" />
          </div>
        </div>
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="epModal.close('modal-add-tranche')">Annuler</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">Ajouter</button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Affecter aux apprenants ══ --}}
<div id="modal-affecter" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm">
    <div class="ep-modal-head">
      <h3 id="affecter-titre">↓ Affecter aux apprenants</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-affecter')">×</button>
    </div>
    <form id="affecter-form" method="POST">
      @csrf
      <div class="ep-modal-body">
        <div style="background:#f0fdf4;border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:12px;color:#166534;">
          Affecte cette catégorie à tous les apprenants actifs.<br>
          Laisse "Classe" vide pour affecter à <strong>tout l'établissement</strong>.
        </div>
        <div class="lbl">Filtrer par classe (optionnel)</div>
        <input class="inp" name="classe" placeholder="ex : 3ème B — vide = tous" />
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="epModal.close('modal-affecter')">Annuler</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">Affecter</button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Voir échéancier ══ --}}
<div id="modal-voir-frais" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3 id="voir-frais-titre">📅 Échéancier</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-voir-frais')">×</button>
    </div>
    <div class="ep-modal-body" id="voir-frais-body"></div>
    <div class="ep-modal-foot">
      <button class="btn-p" style="width:auto;padding:8px 20px;" onclick="epModal.close('modal-voir-frais')">Fermer</button>
    </div>
  </div>
</div>

@endpush

@section('content')

@if(session('success'))
  <div class="epcard" style="background:#d1fae5;border-left:4px solid #059669;color:#065f46;margin-bottom:16px;padding:12px 16px;">
    ✓ {{ session('success') }}
  </div>
@endif
@if(session('error'))
  <div class="epcard" style="background:#fbeaea;border-left:4px solid var(--ep-red);color:#9b2c2c;margin-bottom:16px;padding:12px 16px;">
    ✗ {{ session('error') }}
  </div>
@endif

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
  <div>
    <div style="font-size:17px;font-weight:700;">Catégories de frais &amp; échéanciers — {{ $etablissement->annee_scolaire_active ?? '2025-2026' }}</div>
    <div style="font-size:12px;color:#888;">{{ $categories->count() }} catégorie(s)</div>
  </div>
  <button onclick="epModal.open('modal-create-frais')" class="btn-p" style="width:auto;">
    + Nouvelle catégorie
  </button>
</div>

<div class="epcard" style="padding:0;overflow:hidden;">
  <div style="padding:14px 18px;border-bottom:1px solid #f0f0f0;">
    <span style="font-size:11px;font-weight:600;color:#999;text-transform:uppercase;letter-spacing:.05em;">CATÉGORIES DE FRAIS</span>
  </div>

  @forelse($categories as $cat)
  <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid #f5f5f5;flex-wrap:wrap;gap:10px;">
    <div style="flex:1;min-width:200px;">
      <div style="font-weight:600;font-size:14px;">{{ $cat->nom }}</div>
      <div style="font-size:12px;color:#888;margin-top:2px;">{{ $cat->description ?? '' }}</div>
      <div style="display:flex;gap:8px;margin-top:6px;flex-wrap:wrap;align-items:center;">
        <span class="pill pb">{{ $cat->annee_scolaire }}</span>
        <span class="pill {{ $cat->actif ? 'pg' : 'pr' }}">{{ $cat->actif ? 'Active' : 'Inactive' }}</span>
        <span class="pill pa">{{ $cat->nb_tranches_max }} tranche{{ $cat->nb_tranches_max > 1 ? 's' : '' }}</span>
        @if($cat->echeanciers->count())
          <span class="pill" style="background:#EDE9FE;color:#5B21B6;cursor:pointer;"
                onclick="voirEcheancier({{ $cat->id }}, '{{ addslashes($cat->nom) }}', {{ $cat->echeanciers->toJson() }})">
            📅 {{ $cat->echeanciers->count() }} échéance(s)
          </span>
        @endif
      </div>
    </div>
    <div style="font-size:16px;font-weight:700;color:#085041;margin:0 16px;white-space:nowrap;">
      {{ number_format($cat->montant_total, 0, ',', ' ') }} FCFA
    </div>
    <div style="display:flex;gap:6px;flex-wrap:wrap;">
      <button onclick="affecterFrais({{ $cat->id }}, '{{ addslashes($cat->nom) }}')"
              class="btn-o" style="width:auto;padding:6px 12px;font-size:12px;">
        ↓ Affecter
      </button>
      <button onclick="ajouterTranche({{ $cat->id }}, '{{ addslashes($cat->nom) }}', {{ $cat->echeanciers->count() + 1 }})"
              class="btn-o" style="width:auto;padding:6px 12px;font-size:12px;">
        + Tranche
      </button>
      <button onclick="modifierFrais({{ $cat->id }}, '{{ addslashes($cat->nom) }}', '{{ addslashes($cat->description ?? '') }}', {{ $cat->montant_total }}, {{ $cat->nb_tranches_max }}, {{ $cat->fractionnable ? 'true' : 'false' }}, {{ $cat->actif ? 'true' : 'false' }}, '{{ $cat->annee_scolaire }}')"
              class="btn-o" style="width:auto;padding:6px 12px;font-size:12px;">
        ✎ Modifier
      </button>
      <button onclick="supprimerFrais({{ $cat->id }}, '{{ addslashes($cat->nom) }}')"
              style="width:auto;padding:6px 12px;font-size:12px;background:transparent;color:var(--ep-red);border:2px solid var(--ep-red);border-radius:var(--radius-md);cursor:pointer;">
        🗑
      </button>
    </div>
  </div>
  @empty
  <div style="padding:40px;text-align:center;color:#aaa;">
    <div style="font-size:32px;margin-bottom:8px;">📋</div>
    Aucune catégorie de frais.
    <button onclick="epModal.open('modal-create-frais')" style="color:var(--ep-teal);background:none;border:none;cursor:pointer;font-size:13px;">
      Créer la première
    </button>
  </div>
  @endforelse
</div>

<div style="margin-top:14px;background:var(--ep-gold-lt);border-radius:var(--radius-md);padding:12px 16px;font-size:12px;color:#854F0B;border-left:3px solid var(--ep-gold);">
  <strong>CDC E02 / E03 :</strong> Max 3 tranches · La somme des tranches doit égaler le montant total · Rappel SMS automatique J‑5 avant chaque échéance.
</div>

@endsection

@push('scripts')
<script>
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
    document.getElementById('add-tranche-titre').textContent = '+ Tranche — ' + nom;
    document.getElementById('add-tranche-form').action = "{{ url('etablissement/frais') }}/" + catId + "/echeancier";
    document.getElementById('add-tranche-num').value = nextNum;
    epModal.open('modal-add-tranche');
}

function affecterFrais(id, nom) {
    document.getElementById('affecter-titre').textContent = '↓ Affecter — ' + nom;
    document.getElementById('affecter-form').action = "{{ url('etablissement/frais') }}/" + id + "/affecter";
    epModal.open('modal-affecter');
}

function voirEcheancier(id, nom, echeances) {
    document.getElementById('voir-frais-titre').textContent = '📅 Échéancier — ' + nom;
    var html = '<table class="ep-table"><thead><tr><th>Tranche</th><th>Libellé</th><th>Montant</th><th>Échéance</th></tr></thead><tbody>';
    echeances.forEach(function(e) {
        html += '<tr>'
            + '<td><span class="pill pb">T' + e.numero_tranche + '</span></td>'
            + '<td>' + (e.libelle || 'Tranche ' + e.numero_tranche) + '</td>'
            + '<td style="font-weight:600;">' + Number(e.montant).toLocaleString('fr-FR') + ' FCFA</td>'
            + '<td>' + (e.date_echeance ? new Date(e.date_echeance).toLocaleDateString('fr-FR') : '—') + '</td>'
            + '</tr>';
    });
    html += '</tbody></table>';
    document.getElementById('voir-frais-body').innerHTML = html;
    epModal.open('modal-voir-frais');
}
</script>
@endpush
