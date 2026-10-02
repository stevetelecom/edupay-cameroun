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
    {{-- Barre retour + export : même pill que paiement/frais_apprenant --}}
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
        <a href="{{ route('payeur.dashboard') }}" class="ep-retour-lien">
            <span class="material-symbols-outlined" style="font-size:15px;">arrow_back</span>
            {{ __('payeur.hist_retour_dashboard') }}
        </a>
        {{-- Export PDF : bouton vert (dégradé teal) avec icône Material --}}
        <a href="{{ route('payeur.historique') }}?export=pdf" class="ep-btn-pdf-vert"
           style="text-decoration:none;margin-left:auto;">
            <span class="material-symbols-outlined" style="font-size:17px;">picture_as_pdf</span>
            {{ __('payeur.hist_exporter_pdf') }}
        </a>
    </div>

    {{-- En-tête de page v3 : grande pastille époxy + titre Poppins (style dashboard) --}}
    <div class="ep-entete ep-entete-page" style="margin-bottom:18px;">
        <div class="ep-ico bleu ep-ico-entete"><span class="material-symbols-outlined">history</span></div>
        <div>
            <h3 style="margin:0;">{{ __('payeur.hist_titre') }}</h3>
            <div class="ep-sous-titre" style="margin-top:2px;">{{ __('payeur.hist_transactions', ['count' => $paiements->total() ?? $paiements->count()]) }}</div>
        </div>
    </div>

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
                        {{-- Moyen : logo couleur + libellé (pastillesMTN or / Orange),
                             comme sur la fiche frais — plus lisible qu'un texte brut. --}}
                        <td>
                            <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:var(--ep-gris);">
                                <span style="width:22px;height:22px;border-radius:7px;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;{{ match($paiement->mode_paiement) {
                                    'mtn_momo'     => 'background:#FFFBE6;color:#996600;',
                                    'orange_money' => 'background:#FFF5EE;color:#FF6600;',
                                    default        => 'background:var(--ep-fond);color:var(--ep-gris);',
                                } }}">@if($paiement->mode_paiement === 'orange_money')<span class="material-symbols-outlined" style="font-size:14px;">signal_cellular_alt</span>@elseif($paiement->mode_paiement === 'mtn_momo')<span class="material-symbols-outlined" style="font-size:14px;">network_cell</span>@else<span class="material-symbols-outlined" style="font-size:14px;">credit_card</span>@endif</span>
                                {{ match($paiement->mode_paiement) {
                                    'mtn_momo' => 'MTN MoMo', 'orange_money' => 'Orange Money', 'carte' => 'Carte', default => $paiement->mode_paiement,
                                } }}
                            </span>
                        </td>
                        <td>{{ $paiement->date_paiement ? \Carbon\Carbon::parse($paiement->date_paiement)->format('d/m/Y H:i') : '—' }}</td>
                        <td>
                            <span class="pill {{ match($paiement->statut) {
                                'valide' => 'pg', 'en_attente' => 'pa', 'echoue' => 'pr', 'rembourse' => 'pb', 'annule' => 'pb', default => 'pa',
                            } }}">
                                {{ match($paiement->statut) {
                                    'valide' => __('payeur.statut_valide'), 'en_attente' => __('payeur.statut_en_attente'), 'echoue' => __('payeur.statut_echoue'), 'rembourse' => __('payeur.statut_rembourse'), 'annule' => __('payeur.statut_annule'), default => $paiement->statut,
                                } }}
                            </span>
                            @if($paiement->statut !== 'rembourse' && $paiement->remboursements->isNotEmpty())
                                @php $totalRembourse = $paiement->remboursements->sum('montant'); @endphp
                                <div style="font-size:10px;color:#1A4F8A;margin-top:3px;">
                                    {{ __('payeur.hist_dont_rembourses', ['montant' => number_format($totalRembourse, 0, ',', ' ')]) }}
                                </div>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            <div style="display:flex;gap:5px;justify-content:flex-end;flex-wrap:wrap;">
                                {{-- Actions : icônes Material Symbols (plus lisibles que du texte) --}}
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
                                        data-statut-badge="{{ match($paiement->statut) { 'valide' => __('payeur.statut_valide'), 'en_attente' => __('payeur.statut_en_attente'), 'echoue' => __('payeur.statut_echoue'), 'rembourse' => __('payeur.statut_rembourse'), 'annule' => __('payeur.statut_annule'), default => $paiement->statut } }}"
                                        class="ep-act-btn ep-act-btn-bleu"
                                        title="{{ __('payeur.hist_voir_detail') }}"
                                        aria-label="{{ __('payeur.hist_voir_detail') }}">
                                    <span class="material-symbols-outlined">visibility</span>
                                </button>

                                @if($paiement->statut === 'en_attente' && ! $paiement->estAnnule())
                                    <form method="POST" action="{{ route('payeur.paiement.annuler', $paiement) }}"
                                          onsubmit="return confirm('{{ __('payeur.hist_confirm_annuler') }}')">
                                        @csrf
                                        <button type="submit"
                                                class="ep-act-btn ep-act-btn-or"
                                                title="{{ __('payeur.hist_annuler_titre') }}"
                                                aria-label="{{ __('payeur.hist_annuler_titre') }}">
                                            <span class="material-symbols-outlined">cancel</span>
                                        </button>
                                    </form>
                                @endif

                                @if($paiement->statut === 'echoue' && $paiement->fraisApprenant)
                                    <a href="{{ route('payeur.paiement.show', $paiement->fraisApprenant) }}"
                                       class="ep-act-btn ep-act-btn-vert"
                                       title="{{ __('payeur.hist_reesayer_titre') }}"
                                       aria-label="{{ __('payeur.hist_reesayer_titre') }}">
                                        <span class="material-symbols-outlined">refresh</span>
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
    document.getElementById('detail-total').textContent      = btn.dataset.total;    document.getElementById('detail-moyen').textContent  = btn.dataset.moyen;
    document.getElementById('detail-operateur').textContent  = btn.dataset.operateur;
    document.getElementById('detail-telephone').textContent  = btn.dataset.telephone;
    document.getElementById('detail-date').textContent       = btn.dataset.date;
    document.getElementById('detail-statut').textContent     = btn.dataset.statutBadge;
    epModal.open('modal-detail-paiement');
}
</script>
@endpush
