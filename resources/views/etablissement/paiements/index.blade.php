@extends('layouts.etablissement')

@section('title', __('etablissement.paiement'))

@section('content')

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div>
            <div style="font-size:17px;font-weight:700;">{{ __('etablissement.paiement') }}</div>
            <div style="font-size:12px;color:#888;">{{ __('etablissement.nb_transactions', ['count' => $paiements->total() ?? $paiements->count()]) }}</div>
        </div>
    </div>

    {{-- ── Filtres ── --}}
    <form method="GET" action="{{ route('etablissement.paiements.index') }}" class="epcard" style="margin-bottom:16px;display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
        <div style="flex:2;min-width:180px;">
            <div class="lbl">{{ __('etablissement.recherche_paiement') }}</div>
            <input type="text" name="q" value="{{ request('q') }}" class="inp" style="margin-bottom:0;" placeholder="{{ __('etablissement.recherche_ph2') }}">
        </div>
        <div style="flex:1;min-width:140px;">
            <div class="lbl">{{ __('etablissement.statut') }}</div>
            <select name="statut" class="select" style="margin-bottom:0;">
                <option value="">{{ __('etablissement.tous') }}</option>
                <option value="valide" @selected(request('statut') === 'valide')>{{ __('etablissement.st_valide') }}</option>
                <option value="en_attente" @selected(request('statut') === 'en_attente')>{{ __('etablissement.st_en_attente') }}</option>
                <option value="echoue" @selected(request('statut') === 'echoue')>{{ __('etablissement.st_echoue') }}</option>
                <option value="rembourse" @selected(request('statut') === 'rembourse')>{{ __('etablissement.st_rembourse') }}</option>
            </select>
        </div>
        <div style="flex:1;min-width:140px;">
            <div class="lbl">{{ __('etablissement.moyen_paiement') }}</div>
            <select name="mode_paiement" class="select" style="margin-bottom:0;">
                <option value="">{{ __('etablissement.tous') }}</option>
                <option value="mtn_momo" @selected(request('mode_paiement') === 'mtn_momo')>{{ __('etablissement.mtn_momo') }}</option>
                <option value="orange_money" @selected(request('mode_paiement') === 'orange_money')>{{ __('etablissement.orange_money') }}</option>
                <option value="carte" @selected(request('mode_paiement') === 'carte')>{{ __('etablissement.carte') }}</option>
            </select>
        </div>
        <button type="submit" class="btn-p" style="width:auto;padding:10px 20px;">{{ __('etablissement.filtrer') }}</button>
        @if(request()->hasAny(['q','statut','mode_paiement']))
            <a href="{{ route('etablissement.paiements.index') }}" class="btn-o" style="width:auto;padding:10px 16px;">{{ __('etablissement.reinitialiser') }}</a>
        @endif
    </form>

    {{-- ── KPIs rapides ── --}}
    <div class="g3" style="margin-bottom:16px;">
        <div class="kpi">
            <div class="kval" style="color:var(--ep-teal);">{{ number_format($totalValide ?? 0, 0, ',', ' ') }}</div>
            <div class="klbl">{{ __('etablissement.fcfa_valides') }}</div>
        </div>
        <div class="kpi">
            <div class="kval" style="color:var(--ep-gold);">{{ number_format($totalEnAttente ?? 0, 0, ',', ' ') }}</div>
            <div class="klbl">{{ __('etablissement.fcfa_en_attente') }}</div>
        </div>
        <div class="kpi">
            <div class="kval">{{ $paiements->total() ?? $paiements->count() }}</div>
            <div class="klbl">{{ __('etablissement.transactions') }}</div>
        </div>
    </div>

    {{-- ── Tableau ── --}}
    <div class="epcard" style="padding:0;overflow:hidden;">
        <table class="ep-table">
            <thead>
                <tr>
                    <th>{{ __('etablissement.reference') }}</th>
                    <th>{{ __('etablissement.apprenant_col') }}</th>
                    <th>{{ __('etablissement.categorie') }}</th>
                    <th>{{ __('etablissement.montant') }}</th>
                    <th>{{ __('etablissement.moyen') }}</th>
                    <th>{{ __('etablissement.date') }}</th>
                    <th>{{ __('etablissement.statut') }}</th>
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
                            'mtn_momo' => __('etablissement.mtn_momo'), 'orange_money' => __('etablissement.orange_money'), 'carte' => __('etablissement.carte'), default => $paiement->mode_paiement,
                        } }}</td>
                        <td>{{ $paiement->date_paiement ? \Carbon\Carbon::parse($paiement->date_paiement)->format('d/m/Y H:i') : '—' }}</td>
                        <td>
                            <span class="pill {{ match($paiement->statut) {
                                'valide' => 'pg', 'en_attente' => 'pa', 'echoue' => 'pr', 'rembourse' => 'pb', default => 'pa',
                            } }}">
                                {{ match($paiement->statut) {
                                    'valide' => __('etablissement.st_valide'), 'en_attente' => __('etablissement.st_en_attente'), 'echoue' => __('etablissement.st_echoue'), 'rembourse' => __('etablissement.st_rembourse'), default => $paiement->statut,
                                } }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;color:#999;padding:30px 0;">
                            {{ __('etablissement.aucun_paiement_trouve') }}
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
