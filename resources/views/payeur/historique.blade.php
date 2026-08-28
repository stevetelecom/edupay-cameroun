@extends('layouts.payeur')

@section('title', __('payeur.hist_titre'))

@push('modals')
<div id="modal-detail-paiement" class="ep-modal-overlay">
  <div class="ep-modal">
    <div class="ep-modal-head">
      <h3>{{ __('payeur.hist_detail_titre') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-detail-paiement')">×</button>
    </div>
    <div class="ep-modal-body">
      <div class="row"><span style="color:#888;">{{ __('payeur.hist_reference') }}</span><strong id="detail-reference"></strong></div>
      <div class="row"><span style="color:#888;">{{ __('payeur.hist_enfant') }}</span><strong id="detail-enfant"></strong></div>
      <div class="row"><span style="color:#888;">{{ __('payeur.hist_categorie') }}</span><strong id="detail-categorie"></strong></div>
      <div class="row"><span style="color:#888;">{{ __('payeur.hist_montant_frais') }}</span><strong id="detail-montant"></strong></div>
      <div class="row"><span style="color:#888;">{{ __('payeur.hist_frais_service') }}</span><strong id="detail-frais"></strong></div>
      <div class="row"><span style="color:#888;">{{ __('payeur.hist_total_debite') }}</span><strong id="detail-total"></strong></div>
      <div class="row"><span style="color:#888;">{{ __('payeur.hist_moyen') }}</span><strong id="detail-moyen"></strong></div>
      <div class="row"><span style="color:#888;">{{ __('payeur.hist_operateur') }}</span><strong id="detail-operateur"></strong></div>
      <div class="row"><span style="color:#888;">{{ __('payeur.hist_telephone') }}</span><strong id="detail-telephone"></strong></div>
      <div class="row"><span style="color:#888;">{{ __('payeur.hist_date') }}</span><strong id="detail-date"></strong></div>
      <div class="row"><span style="color:#888;">{{ __('payeur.hist_statut') }}</span><span id="detail-statut"></span></div>
    </div>
    <div class="ep-modal-foot">
      <button type="button" class="btn-o" style="width:auto;padding:8px 16px;"
              onclick="epModal.close('modal-detail-paiement')">{{ __('payeur.hist_fermer') }}</button>
    </div>
  </div>
</div>
@endpush

@section('content')
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
        <a href="{{ route('payeur.dashboard') }}" style="color:#888;text-decoration:none;font-size:13px;">&#8592; {{ __('payeur.hist_retour_dashboard') }}</a>
        <a href="{{ route('payeur.historique') }}?export=pdf" style="display:inline-flex;align-items:center;gap:7px;padding:8px 16px;background:#fff;border:1px solid #ddd !important;border-radius:8px;font-size:13px;font-weight:500;color:#444 !important;text-decoration:none;outline:none;box-shadow:none;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            {{ __('payeur.hist_exporter_pdf') }}
        </a>
    </div>


    <div style="font-size:17px;font-weight:700;margin-bottom:4px;">{{ __('payeur.hist_titre') }}</div>
    <div style="font-size:12px;color:#888;margin-bottom:18px;">{{ __('payeur.hist_transactions', ['count' => $paiements->total() ?? $paiements->count()]) }}</div>

    <div class="epcard" style="padding:0;overflow:hidden;">
        <table class="ep-table">
            <thead>
                <tr>
                    <th>{{ __('payeur.hist_reference') }}</th>
                    <th>{{ __('payeur.hist_enfant') }}</th>
                    <th>{{ __('payeur.hist_categorie') }}</th>
                    <th>{{ __('payeur.hist_montant') }}</th>
                    <th>{{ __('payeur.hist_moyen') }}</th>
                    <th>{{ __('payeur.hist_date') }}</th>
                    <th>{{ __('payeur.hist_statut') }}</th>
                    <th style="text-align:right;">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($paiements as $paiement)
                    <tr>
                        <td style="color:#888;">{{ $paiement->reference }}</td>
                        <td style="font-weight:600;">{{ $paiement->apprenant->nom ?? '—' }} {{ $paiement->apprenant->prenom ?? '' }}</td>
                        <td>{{ $paiement->fraisApprenant->categorieFrais->nom ?? '—' }}</td>
                        <td style="font-weight:600;">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</td>
                        <td>{{ match($paiement->mode_paiement) {
                            'mtn_momo' => 'MTN MoMo', 'orange_money' => 'Orange Money', 'carte' => 'Carte', default => $paiement->mode_paiement,
                        } }}</td>
                        <td>{{ $paiement->date_paiement ? \Carbon\Carbon::parse($paiement->date_paiement)->format('d/m/Y H:i') : '—' }}</td>
                        <td>
                            @if($paiement->statut === 'en_attente' && $paiement->annule_manuellement)
                                <span class="pill pb">{{ __('payeur.hist_annule_verif') }}</span>
                            @else
                                <span class="pill {{ match($paiement->statut) {
                                    'valide' => 'pg', 'en_attente' => 'pa', 'echoue' => 'pr', 'rembourse' => 'pb', default => 'pa',
                                } }}">
                                    {{ match($paiement->statut) {
                                        'valide' => __('payeur.statut_valide'), 'en_attente' => __('payeur.statut_en_attente'), 'echoue' => __('payeur.statut_echoue'), 'rembourse' => __('payeur.statut_rembourse'), default => $paiement->statut,
                                    } }}
                                </span>
                            @endif
                            @if($paiement->statut !== 'rembourse' && $paiement->remboursements->isNotEmpty())
                                @php $totalRembourse = $paiement->remboursements->sum('montant'); @endphp
                                <div style="font-size:10px;color:#1A4F8A;margin-top:3px;">
                                    {{ __('payeur.hist_dont_rembourses', ['montant' => number_format($totalRembourse, 0, ',', ' ')]) }}
                                </div>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            <div style="display:flex;gap:5px;justify-content:flex-end;flex-wrap:wrap;">
                                <button type="button"
                                        onclick="ouvrirDetail(this)"
                                        data-reference="{{ $paiement->reference }}"
                                        data-enfant="{{ ($paiement->apprenant->nom ?? '—') . ' ' . ($paiement->apprenant->prenom ?? '') }}"
                                        data-categorie="{{ $paiement->fraisApprenant->categorieFrais->nom ?? '—' }}"
                                        data-montant="{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA"
                                        data-frais="{{ number_format($paiement->frais_service ?? 0, 0, ',', ' ') }} FCFA"
                                        data-total="{{ number_format($paiement->montant_total_paye ?? $paiement->montant, 0, ',', ' ') }} FCFA"
                                        data-moyen="{{ match($paiement->mode_paiement) { 'mtn_momo' => 'MTN MoMo', 'orange_money' => 'Orange Money', default => $paiement->mode_paiement } }}"
                                        data-operateur="{{ $paiement->operateur ?? '—' }}"
                                        data-telephone="{{ $paiement->telephone_paiement ?? '—' }}"
                                        data-date="{{ $paiement->date_paiement ? \Carbon\Carbon::parse($paiement->date_paiement)->format('d/m/Y H:i') : '—' }}"
                                        data-statut-badge="{{ match($paiement->statut) { 'valide' => __('payeur.statut_valide'), 'en_attente' => __('payeur.statut_en_attente'), 'echoue' => __('payeur.statut_echoue'), 'rembourse' => __('payeur.statut_rembourse'), default => $paiement->statut } }}"
                                        style="font-size:11px;color:#1A4F8A;background:var(--ep-blue-lt);border:none;padding:5px 10px;border-radius:20px;cursor:pointer;"
                                        title="{{ __('payeur.hist_voir_detail') }}">
                                    {{ __('payeur.hist_detail') }}
                                </button>

                                @if($paiement->statut === 'en_attente' && ! $paiement->annule_manuellement)
                                    <form method="POST" action="{{ route('payeur.paiement.annuler', $paiement) }}"
                                          onsubmit="return confirm('{{ __('payeur.hist_confirm_annuler') }}')">
                                        @csrf
                                        <button type="submit"
                                                style="font-size:11px;color:#854F0B;background:var(--ep-gold-lt);border:none;padding:5px 10px;border-radius:20px;cursor:pointer;"
                                                title="{{ __('payeur.hist_annuler_titre') }}">
                                            {{ __('payeur.hist_annuler') }}
                                        </button>
                                    </form>
                                @endif

                                @if($paiement->statut === 'echoue' && $paiement->fraisApprenant)
                                    <a href="{{ route('payeur.paiement.show', $paiement->fraisApprenant) }}"
                                       style="font-size:11px;color:#085041;background:var(--ep-teal-lt);text-decoration:none;padding:5px 10px;border-radius:20px;display:inline-block;"
                                       title="{{ __('payeur.hist_reesayer_titre') }}">
                                        {{ __('payeur.hist_reesayer') }}
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center;color:#999;padding:30px 0;">
                            {{ __('payeur.aucun_paiement_enregistre') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (method_exists($paiements ?? null, 'links'))
        <div style="margin-top:16px;">
            {{ $paiements->links() }}
        </div>
    @endif

@endsection

@push('scripts')
<script>
function ouvrirDetail(btn) {
    document.getElementById('detail-reference').textContent  = btn.dataset.reference;
    document.getElementById('detail-enfant').textContent     = btn.dataset.enfant;
    document.getElementById('detail-categorie').textContent  = btn.dataset.categorie;
    document.getElementById('detail-montant').textContent    = btn.dataset.montant;
    document.getElementById('detail-frais').textContent      = btn.dataset.frais;
    document.getElementById('detail-total').textContent      = btn.dataset.total;
    document.getElementById('detail-moyen').textContent      = btn.dataset.moyen;
    document.getElementById('detail-operateur').textContent  = btn.dataset.operateur;
    document.getElementById('detail-telephone').textContent  = btn.dataset.telephone;
    document.getElementById('detail-date').textContent       = btn.dataset.date;
    document.getElementById('detail-statut').textContent     = btn.dataset.statutBadge;
    epModal.open('modal-detail-paiement');
}
</script>
@endpush
