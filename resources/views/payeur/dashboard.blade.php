@extends('layouts.payeur')

@section('title', 'Mon espace')


@push('modals')

{{-- ══ MODAL : Rattacher un enfant / apprenant ══ --}}
<div id="modal-rattacher" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-lg">
    <div class="ep-modal-head">
      <h3>+ Rattacher un enfant / apprenant</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-rattacher')">×</button>
    </div>
    <div class="ep-modal-body">

      {{-- Recherche établissement --}}
      <div style="margin-bottom:16px;">
        <div class="lbl">Rechercher un établissement *</div>
        <div style="position:relative;margin-bottom:8px;">
          <input type="text" id="m-etab-search"
                 placeholder="Tapez le nom ou la ville…"
                 style="width:100%;padding:11px 12px 11px 36px;border:1px solid #ddd;border-radius:8px;font-size:13px;outline:none;"
                 oninput="mFiltrerEtabs(this.value)"
                 onfocus="document.getElementById('m-etab-liste').style.display='block'" />
          <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#aaa;"
               width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </div>
        <div id="m-etab-liste"
             style="border:1px solid #e0e0e0;border-radius:8px;background:#fff;max-height:180px;overflow-y:auto;box-shadow:0 4px 16px rgba(0,0,0,.1);">
          @foreach($etablissements ?? [] as $etab)
            <div class="m-etab-item"
                 data-id="{{ $etab->id }}"
                 data-nom="{{ $etab->nom }}"
                 data-ville="{{ $etab->ville ?? '' }}"
                 data-code="{{ $etab->code_etablissement ?? '' }}"
                 data-type="{{ $etab->type ?? '' }}"
                 onclick="mSelectionnerEtab(this)"
                 style="padding:10px 14px;cursor:pointer;border-bottom:1px solid #f5f5f5;display:flex;align-items:center;justify-content:space-between;">
              <div>
                <div style="font-size:13px;font-weight:600;">{{ $etab->nom }}</div>
                <div style="font-size:11px;color:#888;">
                  @if($etab->code_etablissement)
                    [{{ $etab->code_etablissement }}]
                  @endif
                  {{ $etab->ville ?? '' }} {{ $etab->type ? '· ' . $etab->type : '' }}
                </div>
              </div>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2"
                   class="m-etab-check" style="opacity:0;flex-shrink:0;">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
            </div>
          @endforeach
        </div>
      </div>

      <form method="POST" action="{{ route('payeur.onboarding.store') }}" id="m-onb-form">
        @csrf
        <input type="hidden" name="lien" value="parent" />
        <input type="hidden" name="etablissement_id"  id="m-h-etab-id"  value="" />
        <input type="hidden" name="etablissement_nom" id="m-h-etab-nom" value="" />

        <div class="g2">
          <div>
            <div class="lbl">Prénom de l'enfant *</div>
            <input class="inp" name="prenom_apprenant" placeholder="Brice" required />
          </div>
          <div>
            <div class="lbl">Nom *</div>
            <input class="inp" name="nom_apprenant" placeholder="FONO" required />
          </div>
          <div>
            <div class="lbl">Classe *</div>
            <input class="inp" name="classe" placeholder="3ème A" required />
          </div>
          <div>
            <div class="lbl">Matricule (si connu)</div>
            <input class="inp" name="matricule" placeholder="EP-0001" />
          </div>
        </div>

        <div style="background:var(--ep-teal-lt);border-radius:8px;padding:10px 14px;font-size:12px;color:#085041;margin-top:4px;">
          Apprenant introuvable ? Votre demande sera en pré-rattachement, validée par l'établissement.
        </div>

      </form>
    </div>
    <div class="ep-modal-foot">
      <button type="button" class="btn-o" style="width:auto;padding:8px 16px;"
              onclick="epModal.close('modal-rattacher')">Annuler</button>
      <button type="button" class="btn-p" style="width:auto;padding:8px 20px;"
              onclick="mSoumettre()">Rattacher →</button>
    </div>
  </div>
</div>

@endpush

@section('content')

@if($estSolo)
    {{-- ════════════════════════════════════════
         PANNEAU SOLO — Élève / Étudiant
         ════════════════════════════════════════ --}}
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div>
            <div style="font-size:18px;font-weight:700;">
                Bonjour, {{ Auth::user()->prenom ?? Str::of(Auth::user()->name)->explode(' ')->first() }}
            </div>
            <div style="font-size:13px;color:#888;">
                {{ $nbEnfantsDus ?? 0 }} paiement(s) en attente
                @if($monDossier && $monDossier->etablissement) · {{ $monDossier->etablissement->nom }} @endif
            </div>
        </div>
        @if($premierFraisImpaye)
            <a href="{{ route('payeur.paiement.show', $premierFraisImpaye) }}" class="btn-p" style="width:auto;">
                Payer maintenant
            </a>
        @endif
    </div>

    {{-- ── KPIs solo ── --}}
    <div class="g4" style="margin-bottom:18px;">
        <div class="kpi">
            <div class="kval" style="color:var(--ep-red);">{{ number_format($totalDu ?? 0, 0, ',', ' ') }}</div>
            <div class="klbl">FCFA dus</div>
        </div>
        <div class="kpi">
            <div class="kval" style="color:var(--ep-teal);">{{ number_format($totalPaye ?? 0, 0, ',', ' ') }}</div>
            <div class="klbl">FCFA payés</div>
        </div>
        <div class="kpi">
            <div class="kval">{{ $pourcentageGlobal ?? 0 }}%</div>
            <div class="klbl">Solde réglé</div>
        </div>
        <div class="kpi">
            <div class="kval">{{ $nbRecus ?? 0 }}</div>
            <div class="klbl">Reçus PDF</div>
        </div>
    </div>

    {{-- ── Mon dossier scolaire ── --}}
    <div class="seclbl" style="margin-top:0;">Mon dossier scolaire</div>

    @if(!$monDossier)
        <div class="epcard" style="text-align:center;color:#999;padding:30px 0;margin-bottom:18px;">
            Vous n'êtes pas encore rattaché(e) à un établissement.
            <div style="margin-top:10px;">
                <button onclick="epModal.open('modal-rattacher')" class="btn-p" style="width:auto;">Me rattacher maintenant</button>
            </div>
        </div>
    @else
        @php
            $totalSolo = $monDossier->frais->sum('montant_total');
            $payeSolo  = $monDossier->frais->sum('montant_paye');
            $resteSolo = $totalSolo - $payeSolo;
            $pourcentageSolo = $totalSolo > 0 ? round(($payeSolo / $totalSolo) * 100) : 0;
            $statutSolo = $resteSolo <= 0 ? 'regle' : ($payeSolo > 0 ? 'partiel' : 'impaye');
            $premierFraisImpayeSolo = $monDossier->frais->firstWhere('statut', '!=', 'regle');
        @endphp
        <div class="epcard" style="border-left:3px solid {{ match($statutSolo) {
            'regle' => 'var(--ep-teal)', 'partiel' => 'var(--ep-teal)', 'impaye' => 'var(--ep-red)', default => 'var(--ep-teal)',
        } }};margin-bottom:18px;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;">
                <div>
                    <div style="font-size:15px;font-weight:700;">{{ $monDossier->prenom }} {{ $monDossier->nom }}</div>
                    <div style="font-size:11px;color:#888;">
                        {{ $monDossier->etablissement->nom ?? '—' }} · {{ $monDossier->classe }}
                    </div>
                </div>
                <span class="pill {{ match($statutSolo) {
                    'regle' => 'pg', 'partiel' => 'pa', 'impaye' => 'pr', default => 'pa',
                } }}">
                    {{ match($statutSolo) {
                        'regle' => 'À jour', 'partiel' => 'Partiel', 'impaye' => 'Impayé', default => $statutSolo,
                    } }}
                </span>
            </div>

            @if($resteSolo > 0)
                <div style="font-size:12px;color:#666;margin-bottom:6px;">
                    Reste : <strong>{{ number_format($resteSolo, 0, ',', ' ') }} FCFA</strong>
                    sur {{ number_format($totalSolo, 0, ',', ' ') }} FCFA
                </div>
                <div class="prog"><div class="pfill" style="width:{{ $pourcentageSolo }}%"></div></div>
                <div style="font-size:10px;color:#888;margin-top:3px;margin-bottom:12px;">
                    {{ $pourcentageSolo }}% réglé
                </div>
            @else
                <div style="font-size:12px;color:var(--ep-teal);font-weight:600;margin-bottom:12px;">
                    ✓ Tous les frais sont réglés pour cette année
                </div>
            @endif

            <div style="display:flex;gap:8px;">
                @if($premierFraisImpayeSolo)
                    <a href="{{ route('payeur.paiement.show', $premierFraisImpayeSolo) }}" class="btn-p" style="font-size:12px;padding:8px;">
                        Payer maintenant →
                    </a>
                @endif
                <a href="{{ route('payeur.apprenant.edit', $monDossier) }}" class="btn-o" style="font-size:12px;padding:8px;width:auto;flex:0 0 auto;">
                    ✎ Modifier mon profil
                </a>
            </div>
        </div>
    @endif

    {{-- ── Derniers paiements (solo) ── --}}
    <div class="seclbl">Derniers paiements</div>
    <div class="epcard">
        @forelse ($derniersPaiements ?? [] as $paiement)
            <div class="row">
                <div>
                    <div style="font-size:13px;font-weight:600;">
                        {{ $paiement->fraisApprenant->categorieFrais->nom ?? 'Paiement' }}
                    </div>
                    <div style="font-size:11px;color:#888;">
                        {{ $paiement->date_paiement ? \Carbon\Carbon::parse($paiement->date_paiement)->format('d M Y') : '—' }}
                        ·
                        {{ match($paiement->mode_paiement) {
                            'mtn_momo' => 'MTN MoMo', 'orange_money' => 'Orange Money', 'carte' => 'Carte', default => $paiement->mode_paiement,
                        } }}
                    </div>
                </div>
                <div style="text-align:right;">
                    <div style="font-weight:600;color:{{ $paiement->statut === 'valide' ? 'var(--ep-teal)' : 'var(--ep-gold)' }};">
                        {{ number_format($paiement->montant, 0, ',', ' ') }} FCFA
                    </div>
                    <span class="pill {{ match($paiement->statut) {
                        'valide' => 'pg', 'en_attente' => 'pa', 'echoue' => 'pr', 'rembourse' => 'pb', default => 'pa',
                    } }}">
                        {{ match($paiement->statut) {
                            'valide' => 'Validé', 'en_attente' => 'En attente', 'echoue' => 'Échoué', 'rembourse' => 'Remboursé', default => $paiement->statut,
                        } }}
                    </span>
                </div>
            </div>
        @empty
            <div style="text-align:center;color:#999;font-size:13px;padding:20px 0;">
                Aucun paiement enregistré pour le moment.
            </div>
        @endforelse
    </div>

    @if(($derniersPaiements ?? collect())->isNotEmpty())
        <div style="text-align:center;margin-top:14px;">
            <a href="{{ route('payeur.historique') }}" style="color:var(--ep-teal);text-decoration:none;font-size:13px;font-weight:500;">
                Voir tout l'historique →
            </a>
        </div>
    @endif

@else
    {{-- ════════════════════════════════════════
         PANNEAU FAMILLE — Parent (multi-enfants)
         ════════════════════════════════════════ --}}

    {{-- ── Vue Résumé (par défaut) ── --}}
    <div id="vue-resume">

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div>
            <div style="font-size:18px;font-weight:700;">
                Bonjour, {{ Auth::user()->prenom ?? Str::of(Auth::user()->name)->explode(' ')->first() }}
            </div>
            <div style="font-size:13px;color:#888;">
                {{ $nbEnfantsDus ?? 0 }} paiement(s) en attente
                @if(Auth::user()->ville) · {{ Auth::user()->ville }} @endif
            </div>
        </div>
        @if($premierFraisImpaye)
            <a href="{{ route('payeur.paiement.show', $premierFraisImpaye) }}" class="btn-p" style="width:auto;">
                Payer maintenant
            </a>
        @endif
    </div>

    {{-- ── KPIs famille ── --}}
    <div class="g4" style="margin-bottom:18px;">
        <div class="kpi">
            <div class="kval" style="color:var(--ep-red);">{{ number_format($totalDu ?? 0, 0, ',', ' ') }}</div>
            <div class="klbl">FCFA dus</div>
        </div>
        <div class="kpi">
            <div class="kval" style="color:var(--ep-teal);">{{ number_format($totalPaye ?? 0, 0, ',', ' ') }}</div>
            <div class="klbl">FCFA payés</div>
        </div>
        <div class="kpi">
            <div class="kval">{{ $apprenants->count() }}</div>
            <div class="klbl">Enfant(s) suivi(s)</div>
        </div>
        <div class="kpi">
            <div class="kval">{{ $nbRecus ?? 0 }}</div>
            <div class="klbl">Reçus PDF</div>
        </div>
    </div>

    {{-- ── Mes enfants (résumé) ── --}}
    <div class="seclbl" style="margin-top:0;">Mes enfants</div>

    @if($apprenants->isEmpty())
        <div class="epcard" style="text-align:center;color:#999;padding:30px 0;margin-bottom:18px;">
            Aucun enfant rattaché à votre compte pour le moment.
            <div style="margin-top:10px;">
                <button onclick="epModal.open('modal-rattacher')" class="btn-p" style="width:auto;">+ Rattacher un enfant</button>
            </div>
        </div>
    @else
        <div class="g2" style="margin-bottom:18px;">
            @foreach($apprenants as $apprenant)
                @php
                    $totalApprenant = $apprenant->frais->sum('montant_total');
                    $payeApprenant  = $apprenant->frais->sum('montant_paye');
                    $resteApprenant = $totalApprenant - $payeApprenant;
                    $pourcentage    = $totalApprenant > 0 ? round(($payeApprenant / $totalApprenant) * 100) : 0;
                    $statut = $resteApprenant <= 0 ? 'regle' : ($payeApprenant > 0 ? 'partiel' : 'impaye');
                    $premierFraisImpayeEnfant = $apprenant->frais->firstWhere('statut', '!=', 'regle');
                @endphp
                <div class="epcard" style="border-left:3px solid {{ match($statut) {
                    'regle' => 'var(--ep-teal)', 'partiel' => 'var(--ep-teal)', 'impaye' => 'var(--ep-red)', default => 'var(--ep-teal)',
                } }};">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;">
                        <div>
                            <div style="font-size:15px;font-weight:700;">{{ $apprenant->nom }} {{ $apprenant->prenom }}</div>
                            <div style="font-size:11px;color:#888;">
                                {{ $apprenant->etablissement->nom ?? '—' }} · {{ $apprenant->classe }}
                            </div>
                        </div>
                        <span class="pill {{ match($statut) {
                            'regle' => 'pg', 'partiel' => 'pa', 'impaye' => 'pr', default => 'pa',
                        } }}">
                            {{ match($statut) {
                                'regle' => 'Réglé', 'partiel' => 'Partiel', 'impaye' => 'Impayé', default => $statut,
                            } }}
                        </span>
                    </div>

                    @if($resteApprenant > 0)
                        <div style="font-size:12px;color:#666;margin-bottom:6px;">
                            Reste : <strong>{{ number_format($resteApprenant, 0, ',', ' ') }} FCFA</strong>
                            sur {{ number_format($totalApprenant, 0, ',', ' ') }} FCFA
                        </div>
                        <div class="prog"><div class="pfill" style="width:{{ $pourcentage }}%"></div></div>
                        <div style="font-size:10px;color:#888;margin-top:3px;margin-bottom:12px;">
                            {{ $pourcentage }}% réglé
                        </div>
                    <a href="{{ route('payeur.frais.apprenant', $apprenant) }}"
                       style="font-size:11px;color:var(--ep-teal);text-decoration:none;display:block;text-align:center;margin-bottom:8px;">
                        Voir le détail des frais →
                    </a>
                        @if($premierFraisImpayeEnfant)
                            @if($statut === 'impaye')
                                <a href="{{ route('payeur.paiement.show', $premierFraisImpayeEnfant) }}"
                                   style="background:var(--ep-red);color:#fff;border:none;padding:8px;border-radius:var(--radius-md);font-size:12px;cursor:pointer;width:100%;text-align:center;text-decoration:none;display:block;">
                                    Payer maintenant →
                                </a>
                            @else
                                <a href="{{ route('payeur.paiement.show', $premierFraisImpayeEnfant) }}" class="btn-p" style="font-size:12px;padding:8px;">
                                    Continuer le paiement →
                                </a>
                            @endif
                        @endif
                    @else
                        <div style="font-size:12px;color:var(--ep-teal);font-weight:600;">
                            ✓ Tous les frais sont réglés pour cette année
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- ── Derniers paiements ── --}}
    <div class="seclbl">Derniers paiements</div>
    <div class="epcard">
        @forelse ($derniersPaiements ?? [] as $paiement)
            <div class="row">
                <div>
                    <div style="font-size:13px;font-weight:600;">
                        {{ $paiement->fraisApprenant->categorieFrais->nom ?? 'Paiement' }} — {{ $paiement->apprenant->prenom ?? '' }}
                    </div>
                    <div style="font-size:11px;color:#888;">
                        {{ $paiement->date_paiement ? \Carbon\Carbon::parse($paiement->date_paiement)->format('d M Y') : '—' }}
                        ·
                        {{ match($paiement->mode_paiement) {
                            'mtn_momo' => 'MTN MoMo', 'orange_money' => 'Orange Money', 'carte' => 'Carte', default => $paiement->mode_paiement,
                        } }}
                    </div>
                </div>
                <div style="text-align:right;">
                    <div style="font-weight:600;color:{{ $paiement->statut === 'valide' ? 'var(--ep-teal)' : 'var(--ep-gold)' }};">
                        {{ number_format($paiement->montant, 0, ',', ' ') }} FCFA
                    </div>
                    <span class="pill {{ match($paiement->statut) {
                        'valide' => 'pg', 'en_attente' => 'pa', 'echoue' => 'pr', 'rembourse' => 'pb', default => 'pa',
                    } }}">
                        {{ match($paiement->statut) {
                            'valide' => 'Validé', 'en_attente' => 'En attente', 'echoue' => 'Échoué', 'rembourse' => 'Remboursé', default => $paiement->statut,
                        } }}
                    </span>
                </div>
            </div>
        @empty
            <div style="text-align:center;color:#999;font-size:13px;padding:20px 0;">
                Aucun paiement enregistré pour le moment.
            </div>
        @endforelse
    </div>

    @if(($derniersPaiements ?? collect())->isNotEmpty())
        <div style="text-align:center;margin-top:14px;">
            <a href="{{ route('payeur.historique') }}" style="color:var(--ep-teal);text-decoration:none;font-size:13px;font-weight:500;">
                Voir tout l'historique →
            </a>
        </div>
    @endif

    </div> {{-- /#vue-resume --}}

    {{-- ── Vue détaillée "Mes enfants" (cachée par défaut) ── --}}
    <div id="vue-enfants" style="display:none;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <div style="font-size:17px;font-weight:700;">Mes enfants</div>
            <button onclick="epModal.open('modal-rattacher')" class="btn-p" style="width:auto;padding:9px 16px;font-size:12px;">+ Rattacher un enfant</button>
        </div>

        @if($apprenants->isEmpty())
            <div class="epcard" style="text-align:center;color:#999;padding:30px 0;">
                Aucun enfant rattaché à votre compte pour le moment.
            </div>
        @else
            <div class="g2">
                @foreach($apprenants as $apprenant)
                    @php
                        $totalEnf = $apprenant->frais->sum('montant_total');
                        $payeEnf  = $apprenant->frais->sum('montant_paye');
                        $resteEnf = $totalEnf - $payeEnf;
                        $statutEnf = $resteEnf <= 0 ? 'regle' : ($payeEnf > 0 ? 'partiel' : 'impaye');
                    @endphp
                    <div class="epcard" style="border-left:3px solid {{ match($statutEnf) {
                        'regle' => 'var(--ep-teal)', 'partiel' => 'var(--ep-teal)', 'impaye' => 'var(--ep-red)', default => 'var(--ep-teal)',
                    } }};">
                        <div style="display:flex;justify-content:space-between;gap:10px;">
                            <div style="min-width:0;">
                                <div style="font-size:15px;font-weight:700;">{{ $apprenant->prenom }} {{ $apprenant->nom }}</div>
                                <div style="font-size:11px;color:#888;">
                                    {{ $apprenant->etablissement->nom ?? '—' }} · {{ $apprenant->classe }}
                                    @if($apprenant->matricule) · Mat. {{ $apprenant->matricule }} @endif
                                </div>
                            </div>
                            <span class="pill {{ match($statutEnf) {
                                'regle' => 'pg', 'partiel' => 'pa', 'impaye' => 'pr', default => 'pa',
                            } }}" style="flex-shrink:0;height:fit-content;">
                                {{ match($statutEnf) {
                                    'regle' => 'À jour', 'partiel' => 'Partiel', 'impaye' => 'Impayé', default => $statutEnf,
                                } }}
                            </span>
                        </div>
                        <div style="height:1px;background:#f0f0f0;margin:10px 0;"></div>
                        <div class="row">
                            <span>Établissement</span>
                            <strong>{{ $apprenant->etablissement->nom ?? '—' }}@if($apprenant->etablissement?->ville), {{ $apprenant->etablissement->ville }}@endif</strong>
                        </div>
                        @forelse($apprenant->frais as $frais)
                            @php
                                $resteF = $frais->montant_total - $frais->montant_paye;
                                $labelStatutF = match($frais->statut) {
                                    'regle' => 'Payé', 'partiel' => 'Partiel', 'impaye' => 'Dû', default => $frais->statut,
                                };
                                $colorF = match($frais->statut) {
                                    'regle' => 'var(--ep-teal)', 'partiel' => 'var(--ep-gold)', 'impaye' => 'var(--ep-red)', default => '#666',
                                };
                            @endphp
                            <div class="row">
                                <span>{{ $frais->categorieFrais->nom ?? 'Frais' }} {{ $frais->annee_scolaire }}</span>
                                <strong style="color:{{ $colorF }};">
                                    {{ number_format($frais->statut === 'regle' ? $frais->montant_total : $resteF, 0, ',', ' ') }} FCFA · {{ $labelStatutF }}
                                </strong>
                            </div>
                        @empty
                            <div class="row"><span style="color:#999;">Aucun frais enregistré pour le moment.</span></div>
                        @endforelse

                        <a href="{{ route('payeur.apprenant.edit', $apprenant) }}" class="btn-o" style="margin-top:10px;font-size:11px;padding:7px;display:block;text-align:center;">
                            ✎ Modifier
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </div> {{-- /#vue-enfants --}}

@endif

@endsection

@push('scripts')
<script>
    function showVuePane(pane) {
        var resume  = document.getElementById('vue-resume');
        var enfants = document.getElementById('vue-enfants');
        if (!resume || !enfants) return;

        resume.style.display  = (pane === 'resume')  ? '' : 'none';
        enfants.style.display = (pane === 'enfants') ? '' : 'none';

        var tabDash = document.getElementById('tab-dashboard');
        var tabKids = document.getElementById('tab-children');
        if (tabDash && tabKids) {
            tabDash.classList.toggle('on', pane === 'resume');
            tabKids.classList.toggle('on', pane === 'enfants');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (window.location.hash === '#mes-enfants') {
            showVuePane('enfants');
        }
    });

    // ══════════════════════════════════════════════════════════════
    // Fonctions pour le modal de rattachement d'établissement
    // ══════════════════════════════════════════════════════════════

    // Filtrer la liste des établissements
    function mFiltrerEtabs(searchValue) {
        const items = document.querySelectorAll('.m-etab-item');
        const searchLower = searchValue.toLowerCase().trim();

        // Si recherche vide, afficher tous les items
        if (!searchLower) {
            items.forEach(item => item.style.display = '');
            return;
        }

        let hasMatch = false;
        items.forEach(item => {
            const nom = item.getAttribute('data-nom').toLowerCase();
            const ville = item.getAttribute('data-ville').toLowerCase();
            const code = item.getAttribute('data-code').toLowerCase();

            // Chercher dans nom, ville, ou code
            const match = nom.includes(searchLower) || 
                         ville.includes(searchLower) || 
                         code.includes(searchLower);

            item.style.display = match ? '' : 'none';
            if (match) hasMatch = true;
        });
    }

    // Sélectionner un établissement
    function mSelectionnerEtab(element) {
        const etabId = element.getAttribute('data-id');
        const etabNom = element.getAttribute('data-nom');

        // Remplir les champs cachés
        document.getElementById('m-h-etab-id').value = etabId;
        document.getElementById('m-h-etab-nom').value = etabNom;

        // Mettre à jour l'affichage (checkmark)
        document.querySelectorAll('.m-etab-item').forEach(item => {
            const checkmark = item.querySelector('.m-etab-check');
            if (item === element) {
                checkmark.style.opacity = '1';
                item.style.background = '#f8f8f8';
            } else {
                checkmark.style.opacity = '0';
                item.style.background = '';
            }
        });

        // Mettre à jour le champ de recherche avec le nom sélectionné
        document.getElementById('m-etab-search').value = etabNom;

        // Fermer la dropdown
        document.getElementById('m-etab-liste').style.display = 'none';
    }

    // Soumettre le formulaire
    function mSoumettre() {
        const etabId = document.getElementById('m-h-etab-id').value;
        const prenomApprenant = document.querySelector('input[name="prenom_apprenant"]').value.trim();
        const nomApprenant = document.querySelector('input[name="nom_apprenant"]').value.trim();
        const classe = document.querySelector('input[name="classe"]').value.trim();

        // Validation
        if (!etabId) {
            alert('Veuillez sélectionner un établissement');
            return;
        }
        if (!prenomApprenant) {
            alert('Veuillez entrer le prénom de l\'enfant');
            return;
        }
        if (!nomApprenant) {
            alert('Veuillez entrer le nom de l\'enfant');
            return;
        }
        if (!classe) {
            alert('Veuillez entrer la classe');
            return;
        }

        // Soumettre le formulaire
        document.getElementById('m-onb-form').submit();
    }

    // Gérer l'affichage/masquage de la dropdown au focus/blur
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('m-etab-search');
        const listDiv = document.getElementById('m-etab-liste');

        if (searchInput && listDiv) {
            // Masquer la liste au blur
            searchInput.addEventListener('blur', function () {
                setTimeout(() => {
                    listDiv.style.display = 'none';
                }, 150); // Délai pour laisser le temps de cliquer
            });

            // Afficher la liste au focus
            searchInput.addEventListener('focus', function () {
                listDiv.style.display = 'block';
            });
        }
    });
</script>
@endpush
