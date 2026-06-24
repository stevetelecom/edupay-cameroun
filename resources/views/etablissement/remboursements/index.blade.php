@extends('layouts.etablissement')
@section('title', 'Remboursements')

@push('modals')

{{-- ══ MODAL : Nouvelle demande de remboursement ══ --}}
<div id="modal-rembours-create" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3>+ Nouvelle demande de remboursement</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-rembours-create')">×</button>
    </div>
    <form method="POST" action="{{ route('etablissement.remboursements.store') }}">
      @csrf
      <div class="ep-modal-body">
        <div class="lbl">Paiement concerné *</div>
        <select class="select" name="paiement_id" id="sel-paiement" required
                onchange="majMontantMax(this)">
          <option value="">— Choisir un paiement validé —</option>
          @foreach($paiementsRemboursables as $p)
          <option value="{{ $p->id }}"
                  data-montant="{{ $p->montant }}"
                  data-fmt="{{ number_format($p->montant,0,',',' ') }}">
            {{ $p->apprenant->prenom }} {{ $p->apprenant->nom }}
            — {{ $p->fraisApprenant->categorieFrais->nom ?? 'Paiement' }}
            ({{ number_format($p->montant,0,',',' ') }} FCFA · réf. {{ $p->reference }})
          </option>
          @endforeach
        </select>
        @if($paiementsRemboursables->isEmpty())
        <div style="background:var(--ep-gold-lt);border-radius:var(--radius-md);padding:10px 12px;font-size:12px;color:#854F0B;margin-bottom:12px;">
          Aucun paiement remboursable disponible pour le moment.
        </div>
        @endif

        <div class="lbl">
          Montant à rembourser (FCFA) *
          <span id="montant-max-hint" style="color:#888;font-weight:400;display:none;">
            — max : <strong id="montant-max-val"></strong> FCFA
          </span>
        </div>
        <input class="inp" type="number" name="montant" id="inp-montant"
               min="1" required placeholder="ex : 15000"
               value="{{ old('montant') }}" />

        <div class="lbl">Motif *</div>
        <input class="inp" name="motif" maxlength="255" required
               value="{{ old('motif') }}"
               placeholder="Ex : Erreur de saisie, double paiement, frais annulé…" />

        <div style="background:var(--ep-gold-lt);border-radius:var(--radius-md);padding:10px 12px;font-size:12px;color:#854F0B;margin-top:4px;">
          La demande sera soumise à validation par le directeur ou le comptable avant exécution.
        </div>
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;"
                onclick="epModal.close('modal-rembours-create')">Annuler</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">
          Soumettre la demande
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Approuver ══ --}}
<div id="modal-approuver" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm">
    <div class="ep-modal-head">
      <h3 style="color:var(--ep-teal);">✓ Approuver le remboursement</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-approuver')">×</button>
    </div>
    <div class="ep-modal-body">
      <p style="font-size:13px;color:#555;line-height:1.6;">
        Confirmer le remboursement de
        <strong id="approuver-montant" style="color:var(--ep-teal);"></strong> FCFA
        pour <strong id="approuver-nom"></strong> ?<br><br>
        Le statut du paiement sera mis à jour en <span class="pill pg">remboursé</span> si le montant est total.
      </p>
    </div>
    <div class="ep-modal-foot">
      <button type="button" class="btn-o" style="width:auto;padding:8px 16px;"
              onclick="epModal.close('modal-approuver')">Annuler</button>
      <form id="approuver-form" method="POST" style="display:inline;">
        @csrf
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">
          Confirmer l'approbation
        </button>
      </form>
    </div>
  </div>
</div>

{{-- ══ MODAL : Refuser ══ --}}
<div id="modal-refuser" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm ep-modal-danger">
    <div class="ep-modal-head">
      <h3>✗ Refuser le remboursement</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-refuser')">×</button>
    </div>
    <form id="refuser-form" method="POST">
      @csrf
      <div class="ep-modal-body">
        <p style="font-size:13px;color:#555;margin-bottom:14px;">
          Refuser la demande de remboursement de
          <strong id="refuser-montant" style="color:var(--ep-red);"></strong> FCFA
          pour <strong id="refuser-nom"></strong> ?
        </p>
        <div class="lbl">Motif du refus (optionnel)</div>
        <input class="inp" name="motif_refus" placeholder="Ex : Délai de remboursement dépassé…" style="margin-bottom:0;" />
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;"
                onclick="epModal.close('modal-refuser')">Annuler</button>
        <button type="submit" class="btn-r" style="width:auto;padding:8px 18px;">
          Confirmer le refus
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Détail remboursement ══ --}}
<div id="modal-detail-rembours" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3>Détail du remboursement</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-detail-rembours')">×</button>
    </div>
    <div class="ep-modal-body" id="detail-rembours-body"></div>
    <div class="ep-modal-foot">
      <button class="btn-p" style="width:auto;padding:8px 20px;"
              onclick="epModal.close('modal-detail-rembours')">Fermer</button>
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
<div class="epcard" style="background:var(--ep-red-lt);border-left:4px solid var(--ep-red);color:#9B2C2C;margin-bottom:16px;padding:12px 16px;">
  {{ session('error') }}
</div>
@endif
@if(session('info'))
<div class="epcard" style="background:var(--ep-blue-lt);border-left:4px solid #1A4F8A;color:#1A4F8A;margin-bottom:16px;padding:12px 16px;">
  {{ session('info') }}
</div>
@endif

{{-- KPIs --}}
<div class="g4" style="margin-bottom:20px;">
  <div class="kpi">
    <div class="kval">{{ $remboursements->count() }}</div>
    <div class="klbl">Total demandes</div>
  </div>
  <div class="kpi">
    <div class="kval" style="color:#E8A020;">{{ $remboursements->where('statut','en_attente')->count() }}</div>
    <div class="klbl">En attente</div>
  </div>
  <div class="kpi">
    <div class="kval" style="color:var(--ep-teal);">{{ $remboursements->where('statut','approuve')->count() }}</div>
    <div class="klbl">Approuvés</div>
  </div>
  <div class="kpi">
    <div class="kval">{{ number_format($remboursements->where('statut','approuve')->sum('montant'),0,',',' ') }}</div>
    <div class="klbl">FCFA remboursés</div>
  </div>
</div>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
  <div style="font-size:17px;font-weight:700;">Demandes de remboursement</div>
  <button class="btn-p" style="width:auto;padding:9px 16px;font-size:13px;"
          onclick="epModal.open('modal-rembours-create')">
    + Nouvelle demande
  </button>
</div>

<div class="epcard" style="padding:0;overflow:hidden;">
  <table class="ep-table">
    <thead>
      <tr>
        <th>Référence</th>
        <th>Apprenant</th>
        <th>Motif</th>
        <th>Montant</th>
        <th>Date</th>
        <th>Statut</th>
        <th style="text-align:right;">Actions</th>
      </tr>
    </thead>
    <tbody>
      @forelse($remboursements as $r)
      <tr>
        <td style="color:#888;font-size:12px;">{{ $r->reference }}</td>
        <td style="font-weight:600;">
          {{ $r->paiement->apprenant->prenom }} {{ $r->paiement->apprenant->nom }}
          <div style="font-size:11px;color:#888;font-weight:400;">
            {{ $r->paiement->fraisApprenant->categorieFrais->nom ?? 'Paiement' }}
          </div>
        </td>
        <td style="font-size:12px;color:#555;max-width:180px;">
          <span title="{{ $r->motif }}">{{ Str::limit($r->motif, 40) }}</span>
        </td>
        <td style="font-weight:700;color:#085041;">
          {{ number_format($r->montant,0,',',' ') }} FCFA
        </td>
        <td style="font-size:12px;color:#888;">
          {{ $r->created_at->format('d M Y') }}
        </td>
        <td>
          @if($r->statut === 'en_attente')
            <span class="pill pa">En attente</span>
          @elseif($r->statut === 'approuve')
            <span class="pill pg">Approuvé</span>
          @else
            <span class="pill pr">Refusé</span>
          @endif
        </td>
        <td style="text-align:right;white-space:nowrap;">
          <button onclick="voirDetailRembours(
              '{{ $r->reference }}',
              '{{ addslashes($r->paiement->apprenant->prenom.' '.$r->paiement->apprenant->nom) }}',
              '{{ addslashes($r->paiement->fraisApprenant->categorieFrais->nom ?? 'Paiement') }}',
              '{{ number_format($r->montant,0,',',' ') }}',
              '{{ addslashes($r->motif) }}',
              '{{ $r->statut }}',
              '{{ $r->created_at->format('d M Y') }}',
              '{{ $r->traiteur ? addslashes($r->traiteur->prenom.' '.$r->traiteur->nom) : '' }}',
              '{{ addslashes($r->motif_refus ?? '') }}'
          )"
          style="color:#185FA5;background:none;border:none;font-size:12px;cursor:pointer;margin-right:6px;">
            Détail
          </button>

          @if($r->statut === 'en_attente')
            @can('role:directeur|comptable')
            <button onclick="approuverRembours(
                {{ $r->id }},
                '{{ addslashes($r->paiement->apprenant->prenom.' '.$r->paiement->apprenant->nom) }}',
                '{{ number_format($r->montant,0,',',' ') }}'
            )"
            style="color:var(--ep-teal);background:none;border:none;font-size:12px;cursor:pointer;margin-right:6px;">
              Approuver
            </button>
            <button onclick="refuserRembours(
                {{ $r->id }},
                '{{ addslashes($r->paiement->apprenant->prenom.' '.$r->paiement->apprenant->nom) }}',
                '{{ number_format($r->montant,0,',',' ') }}'
            )"
            style="color:var(--ep-red);background:none;border:none;font-size:12px;cursor:pointer;">
              Refuser
            </button>
            @endcan
          @endif
        </td>
      </tr>
      @empty
      <tr>
        <td colspan="7" style="text-align:center;color:#aaa;padding:30px 0;">
          Aucune demande de remboursement pour le moment.
        </td>
      </tr>
      @endforelse
    </tbody>
  </table>
</div>

@endsection

@push('scripts')
<script>
// ── Mise à jour du montant max quand on choisit un paiement ──
function majMontantMax(sel) {
    var opt = sel.options[sel.selectedIndex];
    var hint = document.getElementById('montant-max-hint');
    var val  = document.getElementById('montant-max-val');
    var inp  = document.getElementById('inp-montant');
    if (opt.dataset.fmt) {
        hint.style.display = 'inline';
        val.textContent = opt.dataset.fmt;
        inp.max = opt.dataset.montant;
    } else {
        hint.style.display = 'none';
        inp.removeAttribute('max');
    }
}

// ── Voir détail ──
function voirDetailRembours(ref, nom, cat, montant, motif, statut, date, traiteur, motifRefus) {
    var statutHtml = statut === 'en_attente'
        ? '<span class="pill pa">En attente</span>'
        : statut === 'approuve'
            ? '<span class="pill pg">Approuvé</span>'
            : '<span class="pill pr">Refusé</span>';

    var html = '<div class="g2" style="gap:16px;margin-bottom:14px;">'
        + '<div><div class="lbl">Référence</div><div style="font-weight:600;font-size:13px;">'+ref+'</div></div>'
        + '<div><div class="lbl">Statut</div>'+statutHtml+'</div>'
        + '<div><div class="lbl">Apprenant</div><div style="font-weight:600;">'+nom+'</div></div>'
        + '<div><div class="lbl">Catégorie</div><div>'+cat+'</div></div>'
        + '<div><div class="lbl">Montant</div><div style="font-weight:700;color:#085041;font-size:15px;">'+montant+' FCFA</div></div>'
        + '<div><div class="lbl">Date de demande</div><div>'+date+'</div></div>'
        + '</div>'
        + '<div style="background:#f5f6f7;border-radius:var(--radius-md);padding:12px;margin-bottom:10px;">'
        + '<div class="lbl">Motif de la demande</div>'
        + '<div style="font-size:13px;">'+motif+'</div>'
        + '</div>';

    if (traiteur) {
        html += '<div style="font-size:12px;color:#888;margin-top:6px;">Traité par : <strong>'+traiteur+'</strong></div>';
    }
    if (motifRefus) {
        html += '<div style="background:var(--ep-red-lt);border-radius:var(--radius-md);padding:10px 12px;margin-top:10px;font-size:12px;color:#9B2C2C;">'
            + '<strong>Motif du refus :</strong> '+motifRefus+'</div>';
    }

    document.getElementById('detail-rembours-body').innerHTML = html;
    epModal.open('modal-detail-rembours');
}

// ── Approuver ──
function approuverRembours(id, nom, montant) {
    document.getElementById('approuver-nom').textContent     = nom;
    document.getElementById('approuver-montant').textContent = montant;
    document.getElementById('approuver-form').action =
        "{{ url('etablissement/remboursements') }}/" + id + "/approuver";
    epModal.open('modal-approuver');
}

// ── Refuser ──
function refuserRembours(id, nom, montant) {
    document.getElementById('refuser-nom').textContent     = nom;
    document.getElementById('refuser-montant').textContent = montant;
    document.getElementById('refuser-form').action =
        "{{ url('etablissement/remboursements') }}/" + id + "/refuser";
    epModal.open('modal-refuser');
}

// ── Réouvrir modal si erreurs de validation ──
@if($errors->any())
document.addEventListener('DOMContentLoaded', function(){
    epModal.open('modal-rembours-create');
});
@endif
</script>
@endpush
