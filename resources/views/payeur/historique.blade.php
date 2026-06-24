@extends('layouts.payeur')

@section('title', 'Historique des paiements')
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
                            <span class="pill {{ match($paiement->statut) {
                                'valide' => 'pg', 'en_attente' => 'pa', 'echoue' => 'pr', 'rembourse' => 'pb', default => 'pa',
                            } }}">
                                {{ match($paiement->statut) {
                                    'valide' => 'Validé', 'en_attente' => 'En attente', 'echoue' => 'Échoué', 'rembourse' => 'Remboursé', default => $paiement->statut,
                                } }}
                            </span>
                            @if($paiement->statut !== 'rembourse' && $paiement->remboursements->isNotEmpty())
                                @php $totalRembourse = $paiement->remboursements->sum('montant'); @endphp
                                <div style="font-size:10px;color:#1A4F8A;margin-top:3px;">
                                    dont {{ number_format($totalRembourse, 0, ',', ' ') }} FCFA remboursés
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;color:#999;padding:30px 0;">
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
