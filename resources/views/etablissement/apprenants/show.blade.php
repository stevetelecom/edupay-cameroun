@extends('layouts.etablissement')
@section('title', $apprenant->nom . ' ' . $apprenant->prenom)

@push('modals')
{{-- ══ MODAL : Modifier apprenant ══ --}}
<div id="modal-modifier-apprenant" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3>✏ Modifier l'apprenant</h3>
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
            <div class="lbl">Nom *</div>
            <input class="inp" name="nom" value="{{ old('nom', $apprenant->nom) }}" required />
          </div>
          <div>
            <div class="lbl">Prénom *</div>
            <input class="inp" name="prenom" value="{{ old('prenom', $apprenant->prenom) }}" required />
          </div>
        </div>
        <div class="g2">
          <div>
            <div class="lbl">Classe *</div>
            <input class="inp" name="classe" value="{{ old('classe', $apprenant->classe) }}" required />
          </div>
          <div>
            <div class="lbl">Matricule</div>
            <input class="inp" name="matricule" value="{{ old('matricule', $apprenant->matricule) }}" />
          </div>
        </div>
        <div class="g2">
          <div>
            <div class="lbl">Date de naissance</div>
            <input class="inp" type="date" name="date_naissance"
                   value="{{ old('date_naissance', $apprenant->date_naissance ? \Carbon\Carbon::parse($apprenant->date_naissance)->format('Y-m-d') : '') }}" />
          </div>
          <div>
            <div class="lbl">Sexe</div>
            <select class="select" name="sexe">
              <option value="">— Non précisé —</option>
              <option value="M" {{ old('sexe', $apprenant->sexe) === 'M' ? 'selected' : '' }}>Masculin</option>
              <option value="F" {{ old('sexe', $apprenant->sexe) === 'F' ? 'selected' : '' }}>Féminin</option>
            </select>
          </div>
        </div>
        <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
          <input type="checkbox" name="actif" value="1"
                 {{ old('actif', $apprenant->actif) ? 'checked' : '' }} />
          Apprenant actif (inscrit pour l'année en cours)
        </label>
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;"
                onclick="epModal.close('modal-modifier-apprenant')">Annuler</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">
          Enregistrer les modifications
        </button>
      </div>
    </form>
  </div>
</div>
@endpush

@section('content')

<div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
    <a href="{{ route('etablissement.apprenants.index') }}"
       style="color:#888;text-decoration:none;font-size:13px;">← Retour à la liste</a>
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
            @if($apprenant->matricule) · Matricule {{ $apprenant->matricule }} @endif
            @if($apprenant->sexe) · {{ $apprenant->sexe === 'M' ? 'Masculin' : 'Féminin' }} @endif
        </div>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
        <span class="pill {{ match($apprenant->statut_paiement) {
            'regle' => 'pg', 'partiel' => 'pa', 'impaye' => 'pr', default => 'pa',
        } }}">
            {{ match($apprenant->statut_paiement) {
                'regle' => 'Réglé', 'partiel' => 'Partiel', 'impaye' => 'Impayé', default => $apprenant->statut_paiement,
            } }}
        </span>
        <button onclick="epModal.open('modal-modifier-apprenant')"
                class="btn-o" style="width:auto;padding:8px 16px;">
            Modifier
        </button>
    </div>
</div>

{{-- ── Parents liés ── --}}
@if($apprenant->parents->isNotEmpty())
<div class="seclbl" style="margin-top:0;">Parents / tuteurs liés</div>
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
    Frais scolaires — {{ $apprenant->frais->first()->annee_scolaire ?? '2025-2026' }}
</div>
<div class="epcard" style="padding:0;overflow:hidden;margin-bottom:18px;">
    <table class="ep-table">
        <thead>
            <tr>
                <th>Catégorie</th><th>Montant total</th>
                <th>Montant payé</th><th>Reste</th><th>Statut</th>
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
                            'regle' => 'Réglé', 'partiel' => 'Partiel', 'impaye' => 'Impayé', default => $frais->statut,
                        } }}
                    </span>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center;color:#999;padding:20px 0;">Aucun frais défini.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ── Historique paiements ── --}}
<div class="seclbl" style="margin-top:0;">Historique des paiements</div>
<div class="epcard" style="padding:0;overflow:hidden;">
    <table class="ep-table">
        <thead>
            <tr>
                <th>Référence</th><th>Montant</th>
                <th>Moyen</th><th>Date</th><th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @forelse($apprenant->paiements()->latest('date_paiement')->get() as $paiement)
            <tr>
                <td style="color:#888;">{{ $paiement->reference }}</td>
                <td style="font-weight:600;">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</td>
                <td>{{ match($paiement->mode_paiement) {
                    'mtn_momo' => 'MTN MoMo', 'orange_money' => 'Orange Money',
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
                            'valide' => 'Validé', 'en_attente' => 'En attente',
                            'echoue' => 'Échoué', 'rembourse' => 'Remboursé',
                            default => $paiement->statut,
                        } }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align:center;color:#999;padding:20px 0;">
                    Aucun paiement enregistré.
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
</script>
@endpush
