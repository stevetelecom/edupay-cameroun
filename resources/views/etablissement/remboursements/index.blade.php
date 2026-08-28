@extends('layouts.etablissement')
@section('title', 'Remboursements')

@push('modals')

{{-- ══ MODAL : Nouvelle demande de remboursement ══ --}}
<div id="modal-rembours-create" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3>{{ __('etablissement.nouvelle_demande') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-rembours-create')">×</button>
    </div>
    <form method="POST" action="{{ route('etablissement.remboursements.store') }}">
      @csrf
      <div class="ep-modal-body">
        <div class="lbl">{{ __('etablissement.paiement_concerne') }}</div>
        <select class="select" name="paiement_id" id="sel-paiement" required
                onchange="majMontantMax(this)">
          <option value="">{{ __('etablissement.choisir_paiement') }}</option>
          @foreach($paiementsRemboursables as $p)
          <option value="{{ $p->id }}"
                  data-montant="{{ $p->montant }}"
                  data-fmt="{{ number_format($p->montant,0,',',' ') }}">
            {{ $p->apprenant->prenom }} {{ $p->apprenant->nom }}
            — {{ $p->fraisApprenant->categorieFrais->nom ?? __('etablissement.paiement') }}
            ({{ number_format($p->montant,0,',',' ') }} FCFA · réf. {{ $p->reference }})
          </option>
          @endforeach
        </select>
        @if($paiementsRemboursables->isEmpty())
        <div style="background:var(--ep-gold-lt);border-radius:var(--radius-md);padding:10px 12px;font-size:12px;color:#854F0B;margin-bottom:12px;">
          {{ __('etablissement.aucun_remboursable') }}
        </div>
        @endif

        <div class="lbl">
          {{ __('etablissement.montant_rembourser') }}
          <span id="montant-max-hint" style="color:#888;font-weight:400;display:none;">
            {{ __('etablissement.max_hint') }} <strong id="montant-max-val"></strong> FCFA
          </span>
        </div>
        <input class="inp" type="number" name="montant" id="inp-montant"
               min="1" required placeholder="{{ __('etablissement.montant_ph') }}"
               value="{{ old('montant') }}" />

        <div class="lbl">{{ __('etablissement.motif') }}</div>
        <input class="inp" name="motif" maxlength="255" required
               value="{{ old('motif') }}"
               placeholder="{{ __('etablissement.motif_ph') }}" />

        <div style="background:var(--ep-gold-lt);border-radius:var(--radius-md);padding:10px 12px;font-size:12px;color:#854F0B;margin-top:4px;">
          {{ __('etablissement.demande_validation_hint') }}
        </div>
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;"
                onclick="epModal.close('modal-rembours-create')">{{ __('etablissement.annuler') }}</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">
          {{ __('etablissement.soumettre_demande') }}
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Approuver ══ --}}
<div id="modal-approuver" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm">
    <div class="ep-modal-head">
      <h3 style="color:var(--ep-teal);">{{ __('etablissement.approuver_rembours') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-approuver')">×</button>
    </div>
    <div class="ep-modal-body">
      <p style="font-size:13px;color:#555;line-height:1.6;">
        {!! __('etablissement.confirm_approuver') !!}
      </p>
    </div>
    <div class="ep-modal-foot">
      <button type="button" class="btn-o" style="width:auto;padding:8px 16px;"
              onclick="epModal.close('modal-approuver')">{{ __('etablissement.annuler') }}</button>
      <form id="approuver-form" method="POST" style="display:inline;">
        @csrf
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">
          {{ __('etablissement.confirmer_approbation') }}
        </button>
      </form>
    </div>
  </div>
</div>

{{-- ══ MODAL : Refuser ══ --}}
<div id="modal-refuser" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm ep-modal-danger">
    <div class="ep-modal-head">
      <h3>{{ __('etablissement.refuser_rembours') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-refuser')">×</button>
    </div>
    <form id="refuser-form" method="POST">
      @csrf
      <div class="ep-modal-body">
        <p style="font-size:13px;color:#555;margin-bottom:14px;">
          {!! __('etablissement.confirm_refuser') !!}
        </p>
        <div class="lbl">{{ __('etablissement.motif_refus') }}</div>
        <input class="inp" name="motif_refus" placeholder="{{ __('etablissement.motif_refus_ph') }}" style="margin-bottom:0;" />
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;"
                onclick="epModal.close('modal-refuser')">{{ __('etablissement.annuler') }}</button>
        <button type="submit" class="btn-r" style="width:auto;padding:8px 18px;">
          {{ __('etablissement.confirmer_refus') }}
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Détail remboursement ══ --}}
<div id="modal-detail-rembours" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3>{{ __('etablissement.detail_rembours') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-detail-rembours')">×</button>
    </div>
    <div class="ep-modal-body" id="detail-rembours-body"></div>
    <div class="ep-modal-foot">
      <button class="btn-p" style="width:auto;padding:8px 20px;"
              onclick="epModal.close('modal-detail-rembours')">{{ __('etablissement.fermer') }}</button>
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
    <div class="klbl">{{ __('etablissement.total_demandes') }}</div>
  </div>
  <div class="kpi">
    <div class="kval" style="color:#E8A020;">{{ $remboursements->where('statut','en_attente')->count() }}</div>
    <div class="klbl">{{ __('etablissement.st_en_attente') }}</div>
  </div>
  <div class="kpi">
    <div class="kval" style="color:var(--ep-teal);">{{ $remboursements->where('statut','approuve')->count() }}</div>
    <div class="klbl">{{ __('etablissement.approuves') }}</div>
  </div>
  <div class="kpi">
    <div class="kval">{{ number_format($remboursements->where('statut','approuve')->sum('montant'),0,',',' ') }}</div>
    <div class="klbl">{{ __('etablissement.fcfa_rembourses') }}</div>
  </div>
</div>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
  <div style="font-size:17px;font-weight:700;">{{ __('etablissement.demandes_remboursement') }}</div>
  <button class="btn-p" style="width:auto;padding:9px 16px;font-size:13px;"
          onclick="epModal.open('modal-rembours-create')">
    {{ __('etablissement.nouvelle_demande_btn') }}
  </button>
</div>

<div class="epcard" style="padding:0;overflow:hidden;">
  <table class="ep-table">
    <thead>
      <tr>
        <th>{{ __('etablissement.reference') }}</th>
        <th>{{ __('etablissement.apprenant_col') }}</th>
        <th>{{ __('etablissement.motif_col') }}</th>
        <th>{{ __('etablissement.montant') }}</th>
        <th>{{ __('etablissement.date') }}</th>
        <th>{{ __('etablissement.statut') }}</th>
        <th style="text-align:right;">{{ __('etablissement.actions') }}</th>
      </tr>
    </thead>
    <tbody>
      @forelse($remboursements as $r)
      <tr>
        <td style="color:#888;font-size:12px;">{{ $r->reference }}</td>
        <td style="font-weight:600;">
          {{ $r->paiement->apprenant->prenom }} {{ $r->paiement->apprenant->nom }}
          <div style="font-size:11px;color:#888;font-weight:400;">
            {{ $r->paiement->fraisApprenant->categorieFrais->nom ?? __('etablissement.paiement') }}
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
            <span class="pill pa">{{ __('etablissement.st_en_attente') }}</span>
          @elseif($r->statut === 'approuve')
            <span class="pill pg">{{ __('etablissement.approuve') }}</span>
          @else
            <span class="pill pr">{{ __('etablissement.refuse') }}</span>
          @endif
        </td>
        <td style="text-align:right;white-space:nowrap;">
          <button onclick="voirDetailRembours(
              '{{ $r->reference }}',
              '{{ addslashes($r->paiement->apprenant->prenom.' '.$r->paiement->apprenant->nom) }}',
              '{{ addslashes($r->paiement->fraisApprenant->categorieFrais->nom ?? __('etablissement.paiement')) }}',
              '{{ number_format($r->montant,0,',',' ') }}',
              '{{ addslashes($r->motif) }}',
              '{{ $r->statut }}',
              '{{ $r->created_at->format('d M Y') }}',
              '{{ $r->traiteur ? addslashes($r->traiteur->prenom.' '.$r->traiteur->nom) : '' }}',
              '{{ addslashes($r->motif_refus ?? '') }}'
          )"
          style="color:#185FA5;background:none;border:none;font-size:12px;cursor:pointer;margin-right:6px;">
            {{ __('etablissement.detail') }}
          </button>

          @if($r->statut === 'en_attente')
            @can('role:directeur|comptable')
            <button onclick="approuverRembours(
                {{ $r->id }},
                '{{ addslashes($r->paiement->apprenant->prenom.' '.$r->paiement->apprenant->nom) }}',
                '{{ number_format($r->montant,0,',',' ') }}'
            )"
            style="color:var(--ep-teal);background:none;border:none;font-size:12px;cursor:pointer;margin-right:6px;">
              {{ __('etablissement.approuver') }}
            </button>
            <button onclick="refuserRembours(
                {{ $r->id }},
                '{{ addslashes($r->paiement->apprenant->prenom.' '.$r->paiement->apprenant->nom) }}',
                '{{ number_format($r->montant,0,',',' ') }}'
            )"
            style="color:var(--ep-red);background:none;border:none;font-size:12px;cursor:pointer;">
              {{ __('etablissement.refuser') }}
            </button>
            @endcan
          @endif
        </td>
      </tr>
      @empty
      <tr>
        <td colspan="7" style="text-align:center;color:#aaa;padding:30px 0;">
          {{ __('etablissement.aucun_demande') }}
        </td>
      </tr>
      @endforelse
    </tbody>
  </table>
</div>

@endsection

@push('scripts')
<script>
// ── Messages localisés injectés depuis la config de langue ──
const RB_LANG = {
    st_en_attente:   @json(__('etablissement.st_en_attente')),
    refuse:          @json(__('etablissement.refuse')),
    approuve:        @json(__('etablissement.approuve')),
    statut:          @json(__('etablissement.statut')),
    reference:       @json(__('etablissement.reference')),
    apprenant_col:   @json(__('etablissement.apprenant_col')),
    categorie:       @json(__('etablissement.categorie')),
    montant:         @json(__('etablissement.montant')),
    date_demande:    @json(__('etablissement.date_demande')),
    motif_demande:   @json(__('etablissement.motif_demande')),
    motif_refus_colon: @json(__('etablissement.motif_refus_colon')),
    traite_par:      @json(__('etablissement.traite_par', ['user' => '__USER__'])),
};

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
        ? '<span class="pill pa">' + RB_LANG.st_en_attente + '</span>'
        : statut === 'approuve'
            ? '<span class="pill pg">' + RB_LANG.approuve + '</span>'
            : '<span class="pill pr">' + RB_LANG.refuse + '</span>';

    var html = '<div class="g2" style="gap:16px;margin-bottom:14px;">'
        + '<div><div class="lbl">' + RB_LANG.reference + '</div><div style="font-weight:600;font-size:13px;">'+ref+'</div></div>'
        + '<div><div class="lbl">' + RB_LANG.statut + '</div>'+statutHtml+'</div>'
        + '<div><div class="lbl">' + RB_LANG.apprenant_col + '</div><div style="font-weight:600;">'+nom+'</div></div>'
        + '<div><div class="lbl">' + RB_LANG.categorie + '</div><div>'+cat+'</div></div>'
        + '<div><div class="lbl">' + RB_LANG.montant + '</div><div style="font-weight:700;color:#085041;font-size:15px;">'+montant+' FCFA</div></div>'
        + '<div><div class="lbl">' + RB_LANG.date_demande + '</div><div>'+date+'</div></div>'
        + '</div>'
        + '<div style="background:#f5f6f7;border-radius:var(--radius-md);padding:12px;margin-bottom:10px;">'
        + '<div class="lbl">' + RB_LANG.motif_demande + '</div>'
        + '<div style="font-size:13px;">'+motif+'</div>'
        + '</div>';

    if (traiteur) {
        html += '<div style="font-size:12px;color:#888;margin-top:6px;">' + RB_LANG.traite_par.replace('__USER__', traiteur) + '</div>';
    }
    if (motifRefus) {
        html += '<div style="background:var(--ep-red-lt);border-radius:var(--radius-md);padding:10px 12px;margin-top:10px;font-size:12px;color:#9B2C2C;">'
            + '<strong>' + RB_LANG.motif_refus_colon + '</strong> '+motifRefus+'</div>';
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
