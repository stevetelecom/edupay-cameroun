@extends('layouts.payeur')
@section('title', 'Mes enfants — EduPay')

@section('content')

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
  <div>
    <div style="font-size:18px;font-weight:700;">Mes enfants</div>
    <div style="font-size:13px;color:#888;">
      {{ $apprenants->count() }} enfant(s) suivi(s)
      @if(Auth::user()->ville) · {{ Auth::user()->ville }} @endif
    </div>
  </div>
  <div style="display:flex;gap:8px;">
    <button onclick="epModal.open('modal-rattacher')" class="btn-o" style="width:auto;padding:9px 16px;font-size:12px;">
      + Rattacher un enfant
    </button>
    @if($premierFraisImpaye)
      <a href="{{ route('payeur.paiement.show', $premierFraisImpaye) }}" class="btn-p" style="width:auto;">
        Payer maintenant
      </a>
    @endif
  </div>
</div>

{{-- ── F13 : Mes enfants (multi-enfants) ── --}}
@if($apprenants->isEmpty())
  <div class="epcard" style="text-align:center;color:#999;padding:40px 0;margin-bottom:18px;">
    <div style="font-size:32px;margin-bottom:12px;">👨‍👧‍👦</div>
    <div style="font-size:14px;font-weight:600;margin-bottom:6px;">Aucun enfant rattaché à votre compte.</div>
    <div style="font-size:12px;color:#aaa;margin-bottom:16px;">Rattachez votre premier enfant pour suivre ses frais scolaires.</div>
    <button onclick="epModal.open('modal-rattacher')" class="btn-p" style="width:auto;">
      Rattacher un enfant
    </button>
  </div>
@else
  <div class="g2" style="margin-bottom:18px;">
    @foreach($apprenants as $apprenant)
      @php
        $totalA  = $apprenant->frais->sum('montant_total');
        $payeA   = $apprenant->frais->sum('montant_paye');
        $resteA  = $totalA - $payeA;
        $pctA    = $totalA > 0 ? round(($payeA / $totalA) * 100) : 0;
        $statutA = $totalA <= 0 ? 'aucun' : ($resteA <= 0 ? 'regle' : ($payeA > 0 ? 'partiel' : 'impaye'));
        $premierImpayeA = $apprenant->frais->first(fn($f) => $f->statut !== 'regle');
      @endphp
      <div class="epcard" style="border-left:3px solid {{ match($statutA) {
          'regle' => 'var(--ep-teal)', 'partiel' => 'var(--ep-gold)', 'impaye' => 'var(--ep-red)', 'aucun' => 'var(--ep-blue-lt)', default => '#ddd',
      } }};">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;">
          <div>
            <div style="font-size:15px;font-weight:700;">{{ $apprenant->nom }} {{ $apprenant->prenom }}</div>
            <div style="font-size:11px;color:#888;">
              {{ $apprenant->etablissement->nom ?? '—' }} · {{ $apprenant->classe }}
            </div>
          </div>
          <span class="pill {{ match($statutA) { 'regle' => 'pg', 'partiel' => 'pa', 'impaye' => 'pr', default => 'pa' } }}">
            {{ match($statutA) { 'regle' => 'Réglé', 'partiel' => 'Partiel', 'impaye' => 'Impayé', default => $statutA } }}
          </span>
        </div>

        {{-- Frais ventilés par catégorie --}}
        @forelse($apprenant->frais as $frais)
          @php $resteF = $frais->montant_total - $frais->montant_paye; @endphp
          <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 0;border-bottom:1px solid #f5f5f5;">
            <span style="color:#666;">{{ $frais->categorieFrais->nom ?? 'Frais' }}</span>
            <span style="font-weight:600;color:{{ $resteF > 0 ? 'var(--ep-red)' : 'var(--ep-teal)' }};">
              {{ $resteF > 0 ? number_format($resteF,0,',',' ').' FCFA' : '✓ Réglé' }}
            </span>
          </div>
        @empty
          <div style="font-size:12px;color:#aaa;padding:4px 0;">Aucun frais enregistré.</div>
        @endforelse

        @if($resteA > 0)
          <div class="prog" style="margin-top:10px;margin-bottom:4px;">
            <div class="pfill" style="width:{{ $pctA }}%;"></div>
          </div>
          <div style="font-size:10px;color:#888;margin-bottom:10px;">{{ $pctA }}% réglé</div>
        @else
          <div style="font-size:12px;color:var(--ep-teal);font-weight:600;margin-top:10px;margin-bottom:10px;">
            ✓ Tous les frais sont réglés
          </div>
        @endif

        <div style="display:flex;gap:6px;">
          @if($premierImpayeA)
            <a href="{{ route('payeur.paiement.show', $premierImpayeA) }}"
               class="{{ $statutA === 'impaye' ? 'btn-r' : 'btn-p' }}"
               style="flex:1;text-align:center;padding:8px;font-size:12px;display:block;">
              {{ $statutA === 'impaye' ? 'Payer →' : 'Continuer →' }}
            </a>
          @endif
          <a href="{{ route('payeur.frais.apprenant', $apprenant) }}"
             class="btn-o" style="flex:1;text-align:center;padding:8px;font-size:12px;">
            Détail →
          </a>
        </div>
      </div>
    @endforeach
  </div>
@endif

@endsection

@push('modals')
<div id="modal-rattacher" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-lg">
    <div class="ep-modal-head">
      <h3>{{ in_array(Auth::user()->profil ?? '', ['eleve','etudiant']) ? 'Me rattacher à un établissement' : 'Rattacher un enfant / étudiant' }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-rattacher')">×</button>
    </div>
    <div class="ep-modal-body">
      <div id="m-step1">
        <div style="font-size:11px;font-weight:600;color:#0D9E75;text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;">Étape 1 — Choisir l'établissement</div>
        <div style="display:flex;gap:8px;margin-bottom:8px;flex-wrap:wrap;">
          <div style="position:relative;flex:1;min-width:160px;">
            <input type="text" id="m-etab-search" placeholder="Nom de l'établissement…"
                   style="width:100%;padding:9px 12px 9px 34px;border:1px solid #ddd;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                   oninput="mFiltrerEtabs()" onfocus="document.getElementById('m-etab-liste').style.display='block'" />
            <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#aaa;" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          </div>
          <input type="text" id="m-etab-ville" placeholder="Ville…"
                 style="width:110px;padding:9px 12px;border:1px solid #ddd;border-radius:8px;font-size:13px;outline:none;"
                 oninput="mFiltrerEtabs()" onfocus="document.getElementById('m-etab-liste').style.display='block'" />
          <input type="text" id="m-etab-code" placeholder="Code…"
                 style="width:100px;padding:9px 12px;border:1px solid #ddd;border-radius:8px;font-size:13px;outline:none;"
                 oninput="mFiltrerEtabs()" onfocus="document.getElementById('m-etab-liste').style.display='block'" />
          <button type="button" onclick="mFiltrerEtabs();document.getElementById('m-etab-liste').style.display='block';"
                  style="background:#0D9E75;color:#fff;border:none;padding:9px 14px;border-radius:8px;font-size:13px;font-weight:500;cursor:pointer;white-space:nowrap;">Rechercher</button>
        </div>
        <div id="m-etab-liste" style="border:1px solid #e0e0e0;border-radius:8px;background:#fff;max-height:200px;overflow-y:auto;">
          @foreach($etablissements ?? [] as $etab)
            <div class="m-etab-item"
                 data-id="{{ $etab->id }}"
                 data-nom="{{ $etab->nom }}"
                 data-ville="{{ $etab->ville ?? '' }}"
                 data-type="{{ $etab->type ?? '' }}"
                 data-code="{{ $etab->code_etablissement ?? '' }}"
                 onclick="mSelectionnerEtab(this)"
                 style="padding:10px 14px;cursor:pointer;border-bottom:1px solid #f5f5f5;display:flex;align-items:center;gap:10px;transition:background .12s;">
              @if($etab->logo)
                <img src="{{ asset('storage/'.$etab->logo) }}" alt="{{ $etab->nom }}"
                     style="width:36px;height:36px;border-radius:8px;object-fit:cover;flex-shrink:0;border:1px solid #eee;" />
              @else
                <div style="width:36px;height:36px;border-radius:8px;background:#E0F5EE;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:#0D9E75;flex-shrink:0;">
                  {{ strtoupper(substr($etab->nom, 0, 1)) }}
                </div>
              @endif
              <div style="flex:1;min-width:0;">
                <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $etab->nom }}</div>
                <div style="font-size:11px;color:#888;">📍 {{ $etab->ville ?? '—' }} · {{ ucfirst(str_replace('_',' ',$etab->type ?? '')) }} · <span style="color:#0D9E75;font-family:monospace;">{{ $etab->code_etablissement }}</span></div>
              </div>
              <div class="m-etab-check" style="display:none;color:#0D9E75;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              </div>
            </div>
          @endforeach
          @if(($etablissements ?? collect())->isEmpty())
            <div style="padding:20px;text-align:center;color:#aaa;font-size:13px;">Aucun établissement actif trouvé.</div>
          @endif
        </div>
      </div>
      <div id="m-step2" style="display:none;margin-top:16px;">
        <div style="font-size:11px;font-weight:600;color:#0D9E75;text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;">Étape 2 — Informations de l'apprenant</div>
        <div id="m-etab-selected-info" style="background:#E0F5EE;border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:13px;color:#065F46;font-weight:500;"></div>
        <form id="form-rattacher" method="POST" action="{{ route('payeur.onboarding.store') }}">
          @csrf
          <input type="hidden" name="etablissement_id" id="m-etab-id-input" />
          <div class="g2" style="margin-bottom:12px;">
            <div>
              <label style="font-size:12px;font-weight:500;color:#555;display:block;margin-bottom:4px;">Prénom *</label>
              <input type="text" name="prenom_apprenant" required style="width:100%;padding:9px 12px;border:1px solid #ddd;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;" />
            </div>
            <div>
              <label style="font-size:12px;font-weight:500;color:#555;display:block;margin-bottom:4px;">Nom *</label>
              <input type="text" name="nom_apprenant" required style="width:100%;padding:9px 12px;border:1px solid #ddd;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;" />
            </div>
            <div>
              <label style="font-size:12px;font-weight:500;color:#555;display:block;margin-bottom:4px;">Matricule</label>
              <input type="text" name="matricule" style="width:100%;padding:9px 12px;border:1px solid #ddd;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;" />
            </div>
            <div>
              <label style="font-size:12px;font-weight:500;color:#555;display:block;margin-bottom:4px;">Classe *</label>
              <input type="text" name="classe" required style="width:100%;padding:9px 12px;border:1px solid #ddd;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;" />
            </div>
          </div>
          <div style="margin-bottom:12px;">
            <label style="font-size:12px;font-weight:500;color:#555;display:block;margin-bottom:4px;">Lien avec l'apprenant</label>
            <select name="lien" style="width:100%;padding:9px 12px;border:1px solid #ddd;border-radius:8px;font-size:13px;outline:none;">
                <option value="parent">Parent</option>
                <option value="soi-meme">Moi-même (étudiant)</option>
              </select>
          </div>
        </form>
      </div>
    </div>
    <div class="ep-modal-foot">
      <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="epModal.close('modal-rattacher')">Annuler</button>
      <button type="button" class="btn-p" style="width:auto;padding:8px 20px;" onclick="mSoumettre()">
        {{ in_array(Auth::user()->profil ?? '', ['eleve','etudiant']) ? 'Me rattacher →' : 'Rattacher →' }}
      </button>
    </div>
  </div>
</div>
@endpush

@push('scripts')
<script>
let mEtabSelectionne = null;
function mFiltrerEtabs() {
    const q     = (document.getElementById('m-etab-search')?.value || '').toLowerCase();
    const ville = (document.getElementById('m-etab-ville')?.value || '').toLowerCase();
    const code  = (document.getElementById('m-etab-code')?.value || '').toLowerCase();
    document.querySelectorAll('#m-etab-liste .m-etab-item').forEach(el => {
        const ok = (el.dataset.nom||'').toLowerCase().includes(q)
                && (el.dataset.ville||'').toLowerCase().includes(ville)
                && (el.dataset.code||'').toLowerCase().includes(code);
        el.style.display = ok ? 'flex' : 'none';
    });
}
function mSelectionnerEtab(el) {
    document.querySelectorAll('#m-etab-liste .m-etab-item').forEach(i => {
        i.style.background = '';
        const c = i.querySelector('.m-etab-check');
        if (c) c.style.display = 'none';
    });
    el.style.background = '#E0F5EE';
    const chk = el.querySelector('.m-etab-check');
    if (chk) chk.style.display = 'block';
    mEtabSelectionne = { id: el.dataset.id, nom: el.dataset.nom };
    document.getElementById('m-step2').style.display = 'block';
    document.getElementById('m-etab-id-input').value = el.dataset.id;
    document.getElementById('m-etab-selected-info').textContent = '✓ ' + el.dataset.nom + (el.dataset.ville ? ' — ' + el.dataset.ville : '');
    document.getElementById('m-etab-liste').style.display = 'none';
}
function mSoumettre() {
    if (!mEtabSelectionne) {
        epToast('Veuillez sélectionner un établissement.', 'error');
        return;
    }
    document.getElementById('form-rattacher').submit();
}
</script>
@endpush
