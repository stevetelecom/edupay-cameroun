@extends('layouts.payeur')

@section('title', 'Réclamations')

@push('modals')

{{-- ══ MODAL : Nouvelle réclamation ══ --}}
<div id="modal-create-reclamation" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3>+ Nouvelle réclamation</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-create-reclamation')">×</button>
    </div>
    <form method="POST" action="{{ route('payeur.reclamations.store') }}">
      @csrf
      <div class="ep-modal-body">

        @if($errors->any())
          <div style="background:#FEE2E2;border:1px solid #FCA5A5;border-radius:8px;padding:10px 14px;margin-bottom:14px;">
            @foreach($errors->all() as $error)
              <div style="font-size:12px;color:#B91C1C;">{{ $error }}</div>
            @endforeach
          </div>
        @endif

        <div class="lbl">Transaction concernée</div>
        <select class="inp" name="paiement_id" style="margin-bottom:12px;">
          <option value="">Autre / paiement introuvable</option>
          @foreach($paiements as $p)
            <option value="{{ $p->id }}" {{ old('paiement_id') == $p->id ? 'selected' : '' }}>
              Réf. #{{ $p->reference }} — {{ $p->fraisApprenant->categorieFrais->nom ?? 'Paiement' }}
              ({{ number_format($p->montant, 0, ',', ' ') }} FCFA)
            </option>
          @endforeach
        </select>

        <div class="lbl">Objet *</div>
        <input class="inp" name="sujet" maxlength="150" required
               placeholder="Ex : Paiement débité deux fois"
               value="{{ old('sujet') }}" />

        <div class="lbl">Description *</div>
        <textarea class="inp" name="description" rows="4" required
                  style="resize:vertical;"
                  placeholder="Expliquez le problème rencontré…">{{ old('description') }}</textarea>

      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;"
                onclick="epModal.close('modal-create-reclamation')">Annuler</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">
          Envoyer
        </button>
      </div>
    </form>
  </div>
</div>

@endpush

@section('content')

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <div style="font-size:17px;font-weight:700;">Mes réclamations</div>
    <button class="btn-p" style="width:auto;padding:9px 16px;font-size:12px;"
            onclick="epModal.open('modal-create-reclamation')">
        + Nouvelle réclamation
    </button>
</div>

<div class="epcard">
    @forelse($reclamations as $reclamation)
        <div class="row">
            <div>
                <div style="font-size:13px;font-weight:600;">
                    #{{ $reclamation->numero_ticket }} — {{ $reclamation->sujet }}
                </div>
                <div style="font-size:11px;color:#888;">
                    Ouvert le {{ $reclamation->created_at->format('d M Y') }}
                    @if($reclamation->paiement)
                        · {{ $reclamation->paiement->fraisApprenant->categorieFrais->nom ?? 'Paiement' }}
                    @endif
                </div>
            </div>
            <span class="pill {{ match($reclamation->statut) {
                'resolu' => 'pg', 'en_cours' => 'pa', 'rejete' => 'pr', 'ouvert' => 'pb', default => 'pa',
            } }}">
                {{ match($reclamation->statut) {
                    'resolu' => 'Résolu', 'en_cours' => 'En cours', 'rejete' => 'Rejetée', 'ouvert' => 'Ouvert', default => $reclamation->statut,
                } }}
            </span>
        </div>
    @empty
        <div style="text-align:center;color:#999;font-size:13px;padding:20px 0;">
            Vous n'avez envoyé aucune réclamation pour le moment.
        </div>
    @endforelse
</div>

@endsection

@push('scripts')
<script>
// Rouvrir le modal si erreurs de validation
@if($errors->any())
    document.addEventListener('DOMContentLoaded', function() {
        epModal.open('modal-create-reclamation');
    });
@endif
</script>
@endpush
