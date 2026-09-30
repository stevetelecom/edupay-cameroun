@extends('layouts.etablissement')

@section('title', __('etablissement.rev_titre'))

@section('content')

    <div class="ep-entete ep-entete-page" style="justify-content:space-between;margin-bottom:18px;">
        <div style="display:flex;align-items:center;gap:12px;">
            <div class="ep-ico bleu ep-ico-entete"><span class="material-symbols-outlined">currency_exchange</span></div>
            <div>
                <h3 style="margin:0;">{{ __('etablissement.rev_titre') }}</h3>
                <div class="ep-sous-titre" style="margin-top:2px;">{{ __('etablissement.rev_sous_titre') }}</div>
            </div>
        </div>
    </div>

    @if($nbATraiter > 0)
        <div style="background:var(--ep-red-lt);border:1px solid #F3C6C6;border-radius:var(--radius-md);padding:12px 16px;margin-bottom:16px;display:flex;gap:10px;align-items:flex-start;">
            <span class="material-symbols-outlined" style="color:var(--ep-red);font-size:18px;flex-shrink:0;">warning</span>
            <div style="font-size:12.5px;color:#9B2C2C;">
                <strong>{{ __('etablissement.rev_attention') }}</strong>
                {{ __('etablissement.rev_a_traiter_msg', ['count' => $nbATraiter, 'montant' => number_format($aTraiter, 0, ',', ' ')]) }}
            </div>
        </div>
    @endif

    {{-- ── Filtres ── --}}
    <form method="GET" action="{{ route('etablissement.reversements.index') }}" class="epcard" style="margin-bottom:16px;display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
        <div style="flex:2;min-width:180px;">
            <div class="lbl">{{ __('etablissement.rev_recherche') }}</div>
            <input type="text" name="q" value="{{ request('q') }}" class="inp" style="margin-bottom:0;" placeholder="{{ __('etablissement.rev_recherche_ph') }}">
        </div>
        <div style="flex:1;min-width:150px;">
            <div class="lbl">{{ __('etablissement.statut') }}</div>
            <select name="statut" class="select" style="margin-bottom:0;">
                <option value="">{{ __('etablissement.tous') }}</option>
                <option value="prelevee" @selected(request('statut') === 'prelevee')>{{ __('etablissement.rev_st_prelevee') }}</option>
                <option value="en_cours" @selected(request('statut') === 'en_cours')>{{ __('etablissement.rev_st_en_cours') }}</option>
                <option value="calculee" @selected(request('statut') === 'calculee')>{{ __('etablissement.rev_st_calculee') }}</option>
                <option value="a_verifier" @selected(request('statut') === 'a_verifier')>{{ __('etablissement.rev_st_a_verifier') }}</option>
                <option value="echec" @selected(request('statut') === 'echec')>{{ __('etablissement.rev_st_echec') }}</option>
            </select>
        </div>
        <button type="submit" class="btn-p" style="width:auto;padding:10px 20px;">{{ __('etablissement.filtrer') }}</button>
        @if(request()->hasAny(['q','statut']))
            <a href="{{ route('etablissement.reversements.index') }}" class="btn-o" style="width:auto;padding:10px 16px;">{{ __('etablissement.reinitialiser') }}</a>
        @endif
    </form>

    {{-- ── KPIs ── --}}
    <div class="g3" style="margin-bottom:16px;">
        <div class="kpi ep-kpi">
            <div class="ep-ico vert"><span class="material-symbols-outlined">task_alt</span></div>
            <div>
                <div class="kval" data-ep-count>{{ number_format($totalReverse, 0, ',', ' ') }}</div>
                <div class="klbl">{{ __('etablissement.rev_total_reverse') }}</div>
            </div>
        </div>
        <div class="kpi ep-kpi">
            <div class="ep-ico or"><span class="material-symbols-outlined">schedule</span></div>
            <div>
                <div class="kval" data-ep-count>{{ number_format($totalEnCours, 0, ',', ' ') }}</div>
                <div class="klbl">{{ __('etablissement.rev_en_cours') }}</div>
            </div>
        </div>
        <div class="kpi ep-kpi">
            <div class="ep-ico {{ $nbATraiter > 0 ? 'rouge' : 'bleu' }}"><span class="material-symbols-outlined">report</span></div>
            <div>
                <div class="kval" data-ep-count>{{ number_format($aTraiter, 0, ',', ' ') }}</div>
                <div class="klbl">{{ __('etablissement.rev_a_traiter', ['count' => $nbATraiter]) }}</div>
            </div>
        </div>
    </div>

    {{-- ── Tableau ── --}}
    <div class="epcard" style="padding:0;overflow:hidden;">
        <table class="ep-table">
            <thead>
                <tr>
                    <th>{{ __('etablissement.rev_paiement') }}</th>
                    <th>{{ __('etablissement.rev_apprenant') }}</th>
                    <th>{{ __('etablissement.rev_montant_transaction') }}</th>
                    <th>{{ __('etablissement.rev_net') }}</th>
                    <th>{{ __('etablissement.rev_reference') }}</th>
                    <th>{{ __('etablissement.rev_date') }}</th>
                    <th>{{ __('etablissement.statut') }}</th>
                    <th style="text-align:right;">{{ __('etablissement.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reversements as $commission)
                    <tr>
                        <td style="color:#888;">{{ $commission->paiement?->reference ?? '#' . $commission->paiement_id }}</td>
                        <td style="font-weight:600;">
                            {{ $commission->paiement?->apprenant?->nom ?? '—' }} {{ $commission->paiement?->apprenant?->prenom ?? '' }}
                            <div style="font-size:11px;color:#999;font-weight:400;">{{ $commission->paiement?->apprenant?->classe ?? '' }}</div>
                        </td>
                        <td>{{ number_format($commission->montant_transaction, 0, ',', ' ') }} FCFA</td>
                        <td style="font-weight:700;color:var(--ep-teal2);">{{ number_format($commission->montant_net_etablissement, 0, ',', ' ') }} FCFA</td>
                        <td style="color:#888;">
                            {{ $commission->reference_reversement ?? '—' }}
                            @if($commission->reversement_erreur)
                                <div style="font-size:11px;color:var(--ep-red);font-weight:400;max-width:200px;" title="{{ $commission->reversement_erreur }}">
                                    {{ \Illuminate\Support\Str::limit($commission->reversement_erreur, 60) }}
                                </div>
                            @endif
                        </td>
                        <td>{{ $commission->reversed_at ? \Carbon\Carbon::parse($commission->reversed_at)->format('d/m/Y H:i') : ($commission->reversement_tente_le ? \Carbon\Carbon::parse($commission->reversement_tente_le)->format('d/m/Y H:i') : '—') }}</td>
                        <td>
                            <span class="pill {{ match($commission->statut) {
                                'prelevee' => 'pg', 'calculee' => 'pb', 'en_cours' => 'pa', 'a_verifier' => 'pa', 'echec' => 'pr', default => 'pa',
                            } }}">
                                {{ match($commission->statut) {
                                    'prelevee' => __('etablissement.rev_st_prelevee'),
                                    'calculee' => __('etablissement.rev_st_calculee'),
                                    'en_cours' => __('etablissement.rev_st_en_cours'),
                                    'a_verifier' => __('etablissement.rev_st_a_verifier'),
                                    'echec' => __('etablissement.rev_st_echec'),
                                    default => $commission->statut,
                                } }}
                            </span>
                        </td>
                        <td style="text-align:right;">
                            <button type="button" class="ep-btn-icon ep-btn-blue" style="border:none;"
                                    onclick="epModal.open('modal-rev-{{ $commission->id }}')"
                                    title="{{ __('etablissement.rev_detail') }}">
                                <span class="material-symbols-outlined" style="font-size:16px;color:#1e40af;">visibility</span>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center;color:#999;padding:30px 0;">
                            {{ __('etablissement.rev_aucun') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (method_exists($reversements, 'links'))
        <div style="margin-top:16px;">
            {{ $reversements->links() }}
        </div>
    @endif

    {{-- ── Modales de détail ── --}}
    @foreach ($reversements as $commission)
        <div class="ep-modal-overlay" id="modal-rev-{{ $commission->id }}">
            <div class="ep-modal ep-modal-md">
                <div class="ep-modal-head">
                    <h3>{{ __('etablissement.rev_detail') }} — {{ $commission->paiement?->reference ?? '#' . $commission->paiement_id }}</h3>
                    <button type="button" class="ep-modal-close" data-modal-close="modal-rev-{{ $commission->id }}">&times;</button>
                </div>
                <div class="ep-modal-body">
                    <div class="row"><span style="color:#888;">{{ __('etablissement.apprenant_col') }}</span><span style="font-weight:600;">{{ $commission->paiement?->apprenant?->nom ?? '—' }} {{ $commission->paiement?->apprenant?->prenom ?? '' }}</span></div>
                    <div class="row"><span style="color:#888;">{{ __('etablissement.rev_montant_transaction') }}</span><span style="font-weight:600;">{{ number_format($commission->montant_transaction, 0, ',', ' ') }} FCFA</span></div>
                    <div class="row"><span style="color:#888;">{{ __('etablissement.rev_taux') }}</span><span style="font-weight:600;">{{ number_format($commission->taux, 2, ',', ' ') }} %</span></div>
                    <div class="row"><span style="color:#888;">{{ __('etablissement.rev_commission') }}</span><span style="font-weight:600;">{{ number_format($commission->montant_commission, 0, ',', ' ') }} FCFA</span></div>
                    <div class="row"><span style="color:#888;">{{ __('etablissement.rev_net') }}</span><span style="font-weight:700;color:var(--ep-teal2);">{{ number_format($commission->montant_net_etablissement, 0, ',', ' ') }} FCFA</span></div>
                    <div class="row"><span style="color:#888;">{{ __('etablissement.rev_reference') }}</span><span style="font-weight:600;">{{ $commission->reference_reversement ?? '—' }}</span></div>
                    <div class="row"><span style="color:#888;">{{ __('etablissement.date') }}</span><span style="font-weight:600;">{{ $commission->reversed_at ? \Carbon\Carbon::parse($commission->reversed_at)->format('d/m/Y H:i') : '—' }}</span></div>
                    <div class="row"><span style="color:#888;">{{ __('etablissement.statut') }}</span><span style="font-weight:600;">{{ match($commission->statut) { 'prelevee' => __('etablissement.rev_st_prelevee'), 'calculee' => __('etablissement.rev_st_calculee'), 'en_cours' => __('etablissement.rev_st_en_cours'), 'a_verifier' => __('etablissement.rev_st_a_verifier'), 'echec' => __('etablissement.rev_st_echec'), default => $commission->statut } }}</span></div>
                    @if($commission->reversement_erreur)
                        <div style="background:var(--ep-red-lt);border-radius:var(--radius-md);padding:10px 12px;font-size:12px;color:#9B2C2C;margin-top:10px;">
                            <strong>{{ __('etablissement.rev_motif') }}</strong><br/>{{ $commission->reversement_erreur }}
                        </div>
                    @endif
                </div>
                <div class="ep-modal-foot">
                    <button type="button" class="btn-o" data-modal-close="modal-rev-{{ $commission->id }}">{{ __('etablissement.fermer') }}</button>
                </div>
            </div>
        </div>
    @endforeach

@endsection