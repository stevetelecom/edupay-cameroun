@extends('layouts.payeur')

@section('title', 'Historique des paiements')

@push('modals')
<div id="modal-detail-paiement" class="ep-modal-overlay">
  <div class="ep-modal">
    <div class="ep-modal-head">
      <h3>Détail du paiement</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-detail-paiement')">×</button>
    </div>
    <div class="ep-modal-body">
      <div class="row"><span style="color:#888;">Référence</span><strong id="detail-reference"></strong></div>
      <div class="row"><span style="color:#888;">Enfant</span><strong id="detail-enfant"></strong></div>
      <div class="row"><span style="color:#888;">Catégorie</span><strong id="detail-categorie"></strong></div>
      <div class="row"><span style="color:#888;">Montant frais</span><strong id="detail-montant"></strong></div>
      <div class="row"><span style="color:#888;">Frais de service</span><strong id="detail-frais"></strong></div>
      <div class="row"><span style="color:#888;">Total débité</span><strong id="detail-total"></strong></div>
      <div class="row"><span style="color:#888;">Moyen de paiement</span><strong id="detail-moyen"></strong></div>
      <div class="row"><span style="color:#888;">Opérateur</span><strong id="detail-operateur"></strong></div>
      <div class="row"><span style="color:#888;">Téléphone</span><strong id="detail-telephone"></strong></div>
      <div class="row"><span style="color:#888;">Date</span><strong id="detail-date"></strong></div>
      <div class="row"><span style="color:#888;">Statut</span><span id="detail-statut"></span></div>
    </div>
    <div class="ep-modal-foot">
      <button type="button" class="btn-o" style="width:auto;padding:8px 16px;"
              onclick="epModal.close('modal-detail-paiement')">Fermer</button>
    </div>
  </div>
</div>
@endpush

@section('content')
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
        <a href="{{ route('payeur.dashboard') }}" style="color:#888;text-decoration:none;font-size:13px;">&#8592; Retour au tableau de bord</a>
        <a href="{{ route('payeur.historique') }}?export=pdf" style="display:inline-flex;align-items:center;gap:7px;padding:8px 16px;background:#fff;border:1px solid #ddd !important;border-radius:8px;font-size:13px;font-weight:500;color:#444 !important;text-decoration:none;outline:none;box-shadow:none;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Exporter PDF
        </a>
    </div>


    <div style="font-size:17px;font-weight:700;margin-bottom:4px;">Historique des paiements</div>
    <div style="font-size:12px;color:#888;margin-bottom:18px;">{{ $paiements->total() ?? $paiements->count() }} transaction(s) effectuée(s)</div>

    <div class="epcard" style="padding:0;overflow:hidden;">
        <table class="ep-table">
            <thead>
                <tr>
                    <th>Référence</th>
                    <th>Enfant</th>
                    <th>Catégorie</th>
                    <th>Montant</th>
                    <th>Moyen</th>
                    <th>Date</th>
                    <th>Statut</th>
                    <th style="text-align:right;">Actions</th>
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
                                <span class="pill pb">Annulé (vérif. en cours)</span>
                            @else
                                <span class="pill {{ match($paiement->statut) {
                                    'valide' => 'pg', 'en_attente' => 'pa', 'echoue' => 'pr', 'rembourse' => 'pb', default => 'pa',
                                } }}">
                                    {{ match($paiement->statut) {
                                        'valide' => 'Validé', 'en_attente' => 'En attente', 'echoue' => 'Échoué', 'rembourse' => 'Remboursé', default => $paiement->statut,
                                    } }}
                                </span>
                            @endif
                            @if($paiement->statut !== 'rembourse' && $paiement->remboursements->isNotEmpty())
                                @php $totalRembourse = $paiement->remboursements->sum('montant'); @endphp
                                <div style="font-size:10px;color:#1A4F8A;margin-top:3px;">
                                    dont {{ number_format($totalRembourse, 0, ',', ' ') }} FCFA remboursés
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
                                        data-statut-badge="{{ match($paiement->statut) { 'valide' => 'Validé', 'en_attente' => 'En attente', 'echoue' => 'Échoué', 'rembourse' => 'Remboursé', default => $paiement->statut } }}"
                                        style="font-size:11px;color:#1A4F8A;background:var(--ep-blue-lt);border:none;padding:5px 10px;border-radius:20px;cursor:pointer;"
                                        title="Voir le détail">
                                    Détail
                                </button>

                                @if($paiement->statut === 'en_attente' && ! $paiement->annule_manuellement)
                                    <form method="POST" action="{{ route('payeur.paiement.annuler', $paiement) }}"
                                          onsubmit="return confirm('Annuler ce paiement en attente ? Si le montant a déjà été débité, il sera automatiquement régularisé plus tard.')">
                                        @csrf
                                        <button type="submit"
                                                style="font-size:11px;color:#854F0B;background:var(--ep-gold-lt);border:none;padding:5px 10px;border-radius:20px;cursor:pointer;"
                                                title="Annuler ce paiement en attente">
                                            Annuler
                                        </button>
                                    </form>
                                @endif

                                @if($paiement->statut === 'echoue' && $paiement->fraisApprenant)
                                    <a href="{{ route('payeur.paiement.show', $paiement->fraisApprenant) }}"
                                       style="font-size:11px;color:#085041;background:var(--ep-teal-lt);text-decoration:none;padding:5px 10px;border-radius:20px;display:inline-block;"
                                       title="Réessayer ce paiement">
                                        Réessayer
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center;color:#999;padding:30px 0;">
                            Aucun paiement enregistré pour le moment.
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
