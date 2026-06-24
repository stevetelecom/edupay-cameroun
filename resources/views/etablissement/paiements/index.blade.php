@extends('layouts.etablissement')

@section('title', 'Paiements')

@section('content')

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div>
            <div style="font-size:17px;font-weight:700;">Paiements</div>
            <div style="font-size:12px;color:#888;">{{ $paiements->total() ?? $paiements->count() }} transaction(s)</div>
        </div>
    </div>

    {{-- ── Filtres ── --}}
    <form method="GET" action="{{ route('etablissement.paiements.index') }}" class="epcard" style="margin-bottom:16px;display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
        <div style="flex:2;min-width:180px;">
            <div class="lbl">Recherche (référence, apprenant)</div>
            <input type="text" name="q" value="{{ request('q') }}" class="inp" style="margin-bottom:0;" placeholder="Ex: EP2026-04471">
        </div>
        <div style="flex:1;min-width:140px;">
            <div class="lbl">Statut</div>
            <select name="statut" class="select" style="margin-bottom:0;">
                <option value="">Tous</option>
                <option value="valide" @selected(request('statut') === 'valide')>Validé</option>
                <option value="en_attente" @selected(request('statut') === 'en_attente')>En attente</option>
                <option value="echoue" @selected(request('statut') === 'echoue')>Échoué</option>
                <option value="rembourse" @selected(request('statut') === 'rembourse')>Remboursé</option>
            </select>
        </div>
        <div style="flex:1;min-width:140px;">
            <div class="lbl">Moyen de paiement</div>
            <select name="mode_paiement" class="select" style="margin-bottom:0;">
                <option value="">Tous</option>
                <option value="mtn_momo" @selected(request('mode_paiement') === 'mtn_momo')>MTN MoMo</option>
                <option value="orange_money" @selected(request('mode_paiement') === 'orange_money')>Orange Money</option>
                <option value="carte" @selected(request('mode_paiement') === 'carte')>Carte</option>
            </select>
        </div>
        <button type="submit" class="btn-p" style="width:auto;padding:10px 20px;">Filtrer</button>
        @if(request()->hasAny(['q','statut','mode_paiement']))
            <a href="{{ route('etablissement.paiements.index') }}" class="btn-o" style="width:auto;padding:10px 16px;">Réinitialiser</a>
        @endif
    </form>

    {{-- ── KPIs rapides ── --}}
    <div class="g3" style="margin-bottom:16px;">
        <div class="kpi">
            <div class="kval" style="color:var(--ep-teal);">{{ number_format($totalValide ?? 0, 0, ',', ' ') }}</div>
            <div class="klbl">FCFA validés</div>
        </div>
        <div class="kpi">
            <div class="kval" style="color:var(--ep-gold);">{{ number_format($totalEnAttente ?? 0, 0, ',', ' ') }}</div>
            <div class="klbl">FCFA en attente</div>
        </div>
        <div class="kpi">
            <div class="kval">{{ $paiements->total() ?? $paiements->count() }}</div>
            <div class="klbl">Transactions</div>
        </div>
    </div>

    {{-- ── Tableau ── --}}
    <div class="epcard" style="padding:0;overflow:hidden;">
        <table class="ep-table">
            <thead>
                <tr>
                    <th>Référence</th>
                    <th>Apprenant</th>
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
                        <td style="font-weight:600;">
                            {{ $paiement->apprenant->nom ?? '—' }} {{ $paiement->apprenant->prenom ?? '' }}
                            <div style="font-size:11px;color:#999;font-weight:400;">{{ $paiement->apprenant->classe ?? '' }}</div>
                        </td>
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
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;color:#999;padding:30px 0;">
                            Aucun paiement trouvé.
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
