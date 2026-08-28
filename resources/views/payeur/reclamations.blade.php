@extends('layouts.payeur')

@section('title', __('payeur.recl_titre'))

@push('modals')

{{-- ══ MODAL : Nouvelle réclamation ══ --}}
<div id="modal-create-reclamation" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3>+ {{ __('payeur.recl_nouvelle') }}</h3>
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

        <div class="lbl">{{ __('payeur.recl_transaction_concernee') }}</div>
        <select class="inp" name="paiement_id" style="margin-bottom:12px;">
          <option value="">{{ __('payeur.recl_autre_introuvable') }}</option>
          @foreach($paiements as $p)
            <option value="{{ $p->id }}" {{ old('paiement_id') == $p->id ? 'selected' : '' }}>
              {{ __('payeur.recl_ref') }} #{{ $p->reference }} — {{ $p->fraisApprenant->categorieFrais->nom ?? __('payeur.paiement') }}
              ({{ number_format($p->montant, 0, ',', ' ') }} FCFA)
            </option>
          @endforeach
        </select>

        <div class="lbl">{{ __('payeur.recl_objet') }} *</div>
        <input class="inp" name="sujet" maxlength="150" required
               placeholder="{{ __('payeur.recl_ex_objet') }}"
               value="{{ old('sujet') }}" />

        <div class="lbl">{{ __('payeur.recl_description') }} *</div>
        <textarea class="inp" name="description" rows="4" required
                  style="resize:vertical;"
                  placeholder="{{ __('payeur.recl_ex_description') }}">{{ old('description') }}</textarea>

      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;"
                onclick="epModal.close('modal-create-reclamation')">{{ __('payeur.annuler') }}</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">
          {{ __('payeur.recl_envoyer') }}
        </button>
      </div>
    </form>
  </div>
</div>

@endpush

@section('content')

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <div style="font-size:17px;font-weight:700;">{{ __('payeur.recl_mes_reclamations') }}</div>
    <button class="btn-p" style="width:auto;padding:9px 16px;font-size:12px;"
            onclick="epModal.open('modal-create-reclamation')">
        + {{ __('payeur.recl_nouvelle') }}
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
                    {{ __('payeur.recl_ouvert_le') }} {{ $reclamation->created_at->format('d M Y') }}
                    @if($reclamation->paiement)
                        · {{ $reclamation->paiement->fraisApprenant->categorieFrais->nom ?? __('payeur.paiement') }}
                    @endif
                </div>
            </div>
            <span class="pill {{ match($reclamation->statut) {
                'resolu' => 'pg', 'en_cours' => 'pa', 'rejete' => 'pr', 'ouvert' => 'pb', default => 'pa',
            } }}">
                {{ match($reclamation->statut) {
                    'resolu' => __('payeur.recl_statut_resolu'), 'en_cours' => __('payeur.recl_statut_en_cours'), 'rejete' => __('payeur.recl_statut_rejetee'), 'ouvert' => __('payeur.recl_statut_ouvert'), default => $reclamation->statut,
                } }}
            </span>
        </div>
    @empty
        <div style="text-align:center;color:#999;font-size:13px;padding:20px 0;">
            {{ __('payeur.recl_aucune') }}
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
