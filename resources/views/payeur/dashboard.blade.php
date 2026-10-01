@extends('layouts.payeur')

@section('title', __('payeur.titre_espace'))

@push('modals')

{{-- ══ MODAL : Rattacher un enfant / apprenant (F04 + F13) ══ --}}
@include('payeur.partials.modal-rattacher')

{{-- ══ MODAL : Modifier mon dossier apprenant ══ --}}
<div id="modal-modifier-apprenant" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-lg">
    <div class="ep-modal-head">
      <h3>{{ __('payeur.modal_modifier_dossier_titre') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-modifier-apprenant')">×</button>
    </div>
    @if($monDossier)
    <form method="POST" action="{{ route('payeur.apprenant.update', $monDossier) }}">
      @csrf @method('PUT')
      <div class="ep-modal-body">
        <div class="g2">
          <div>
            <div class="lbl">{{ __('messages.prenom') }}</div>
            <input class="inp" name="prenom" value="{{ $monDossier->prenom }}" required />
          </div>
          <div>
            <div class="lbl">{{ __('messages.nom') }}</div>
            <input class="inp" name="nom" value="{{ $monDossier->nom }}" required />
          </div>
        </div>
        <div class="lbl">{{ __('payeur.classe_niveau') }}</div>
        <input class="inp" name="classe" value="{{ $monDossier->classe }}" required />
        <div class="lbl">{{ __('payeur.matricule') }}</div>
        <input class="inp" name="matricule" value="{{ $monDossier->matricule }}" placeholder="EP-XXXX" />
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;"
                onclick="epModal.close('modal-modifier-apprenant')">{{ __('messages.annuler') }}</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">
          {{ __('messages.enregistrer') }} →
        </button>
      </div>
    </form>
    @endif
  </div>
</div>

{{-- ══ MODAL : Détacher mon dossier (élève/étudiant, vue solo) ══ --}}
@if($monDossier)
<div id="modal-detacher-apprenant" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm">
    <div class="ep-modal-head">
      <h3 style="display:flex;align-items:center;gap:9px;color:var(--ep-red);">
        <span class="ep-ico rouge ep-ico-side"><span class="material-symbols-outlined">link_off</span></span>
        {{ __('payeur.detacher_titre') }}
      </h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-detacher-apprenant')">×</button>
    </div>
    <form method="POST" action="{{ route('payeur.apprenant.detach', $monDossier) }}" id="form-detacher">
      @csrf @method('DELETE')
      <div class="ep-modal-body" style="padding:18px 20px;">
        {{-- Récapitulatif v3 (même langage visuel que le modal rattacher) --}}
        <div class="ep-m-conf-carte" style="margin-bottom:12px;">
          <div class="ep-m-conf-ligne">
            <div class="ep-m-conf-lib">{{ __('messages.etablissement') }}</div>
            <div class="ep-m-conf-val" style="font-weight:700;">{{ $monDossier->etablissement->nom ?? '—' }}</div>
          </div>
          <div class="ep-m-conf-ligne">
            <div class="ep-m-conf-lib">{{ __('payeur.m_conf_app_solo') }}</div>
            <div class="ep-m-conf-val">
              <div style="font-weight:700;">{{ $monDossier->prenom }} {{ $monDossier->nom }}</div>
              <div style="font-size:11px;color:#888;">{{ $monDossier->classe }}</div>
            </div>
          </div>
        </div>
        <div class="ep-m-conf-alerte" style="margin-bottom:14px;">
          <span class="material-symbols-outlined" style="font-size:15px;">warning</span>
          <span>{{ __('payeur.detacher_avertissement') }}</span>
        </div>
        <label style="display:flex;align-items:flex-start;gap:8px;font-size:12.5px;color:#555;cursor:pointer;line-height:1.5;">
          <input type="checkbox" id="detacher-confirm" style="margin-top:2px;" />
          <span>{{ __('payeur.detacher_confirm_check') }}</span>
        </label>
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;"
                onclick="epModal.close('modal-detacher-apprenant')">{{ __('messages.annuler') }}</button>
        <button type="submit" class="btn-r" id="btn-detacher-confirm" disabled style="width:auto;padding:8px 20px;">
          {{ __('payeur.detacher') }}
        </button>
      </div>
    </form>
  </div>
</div>
@endif

@endpush

@section('content')

{{-- Ancre du compteur header : la cloche pointe ici --}}
<div id="ep-notifications"></div>

@if(isset($notifications) && $notifications->count() > 0)
    @foreach($notifications as $notif)
    <div class="ep-bandeau {{ $notif->type === 'error' ? 'danger' : 'attente' }}" style="align-items:center;">
        <div class="ep-bandeau-ico">
            <span class="material-symbols-outlined">{{ $notif->type === 'error' ? 'error' : 'info' }}</span>
        </div>
        <div class="ep-bandeau-corps">
            <div class="ep-bandeau-titre">{{ $notif->titre }}</div>
            <div class="ep-bandeau-texte">{{ $notif->message }}</div>
        </div>
        <form method="POST" action="{{ route('payeur.notifications.lu', $notif) }}">
            @csrf @method('PATCH')
            <button type="submit" title="Marquer comme lu"
                    style="width:30px;height:30px;border-radius:10px;border:1.5px solid var(--ep-bordure,#E4E9EE);background:#fff;cursor:pointer;color:#8B93A1;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .2s ease;"
                    onmouseover="this.style.borderColor='var(--ep-teal)';this.style.color='var(--ep-teal2)'"
                    onmouseout="this.style.borderColor='var(--ep-bordure,#E4E9EE)';this.style.color='#8B93A1'">
                <span class="material-symbols-outlined" style="font-size:16px;">close</span>
            </button>
        </form>
    </div>
    @endforeach
@endif

@if($estSolo)
{{-- ════════════════════════════════════════════════════════════
     VUE SOLO — Élève / Étudiant
     F03 Tableau de bord + F05 Frais ventilés par catégorie
     ════════════════════════════════════════════════════════════ --}}

  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
    <div>
      <div style="font-size:18px;font-weight:700;">
        {{ __('payeur.bonjour') }}, {{ Auth::user()->prenom ?? Str::of(Auth::user()->name)->explode(' ')->first() }}
      </div>
      <div style="font-size:13px;color:#888;">
        @if($estSolo && $monDossier && $monDossier->frais->isEmpty())
          {{ __('payeur.aucun_frais') }}
        @else
          {{ $nbEnfantsDus > 0 ? $nbEnfantsDus . ' ' . __('payeur.paiements_en_attente') : __('payeur.tout_a_jour') }}
        @endif
        @if($monDossier && $monDossier->etablissement) · {{ $monDossier->etablissement->nom }} @endif
      </div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <button onclick="epModal.open('modal-rattacher')"
              class="btn-o" style="width:auto;padding:9px 16px;font-size:12px;">
        + {{ __('payeur.me_rattacher') }}
      </button>
      @if($premierFraisImpayeSolo)
        <a href="{{ route('payeur.paiement.show', $premierFraisImpayeSolo) }}" class="btn-p" style="width:auto;">
          {{ __('payeur.payer_maintenant') }}
        </a>
      @endif
    </div>
  </div>

@else
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
    <div>
      <div style="font-size:18px;font-weight:700;">
        {{ __('payeur.bonjour') }}, {{ Auth::user()->prenom ?? Str::of(Auth::user()->name)->explode(' ')->first() }}
      </div>
      <div style="font-size:13px;color:#888;">
        @if($totalDu <= 0)
          {{ __('payeur.aucun_frais') }}
        @else
          {{ $nbEnfantsDus > 0 ? $nbEnfantsDus . ' ' . __('payeur.paiements_en_attente') : __('payeur.tout_a_jour') }}
        @endif
        @if(Auth::user()->ville) · {{ Auth::user()->ville }} @endif
      </div>
    </div>
    <div style="display:flex;gap:8px;">
      <button onclick="epModal.open('modal-rattacher')" class="btn-o" style="width:auto;padding:9px 16px;font-size:12px;">
        + {{ __('payeur.rattacher_un_enfant') }}
      </button>
      @if($premierFraisImpaye)
        <a href="{{ route('payeur.paiement.show', $premierFraisImpaye) }}" class="btn-p" style="width:auto;">
          {{ __('payeur.payer_maintenant') }}
        </a>
      @endif
    </div>
  </div>
@endif

  {{-- ════════════════════════════════════════════════════════════
       BLOCS COMMUNS aux deux vues (solo / famille) — dédupliqués :
       une seule rangée de KPI et UN SEUL jeu de canvas (ids uniques
       chart-situation / chart-enfants), plus aucun id dupliqué.
       ════════════════════════════════════════════════════════════ --}}

  {{-- ── KPIs (la 3e carte dépend de la vue) ── --}}
  <div class="g4" style="margin-bottom:18px;">
    <div class="kpi ep-kpi">
      <div class="ep-ico rouge"><span class="material-symbols-outlined">hourglass_top</span></div>
      <div>
        <div class="kval" data-ep-count>{{ number_format($totalDu ?? 0, 0, ',', ' ') }}</div>
        <div class="klbl">{{ __('payeur.fcfa_dus') }}</div>
      </div>
    </div>
    <div class="kpi ep-kpi">
      <div class="ep-ico vert"><span class="material-symbols-outlined">savings</span></div>
      <div>
        <div class="kval" data-ep-count>{{ number_format($totalPaye ?? 0, 0, ',', ' ') }}</div>
        <div class="klbl">{{ __('payeur.fcfa_payes') }}</div>
      </div>
    </div>
@if($estSolo)
    <div class="kpi ep-kpi">
      <div class="ep-ico bleu"><span class="material-symbols-outlined">pie_chart</span></div>
      <div>
        <div class="kval" data-ep-count>{{ $pourcentageGlobal ?? 0 }}%</div>
        <div class="klbl">{{ __('payeur.solde_regle') }}</div>
      </div>
    </div>
@else
    <div class="kpi ep-kpi">
      <div class="ep-ico bleu"><span class="material-symbols-outlined">family_restroom</span></div>
      <div>
        <div class="kval" data-ep-count>{{ $apprenants->count() }}</div>
        <div class="klbl">{{ __('payeur.enfants_suivis') }}</div>
      </div>
    </div>
@endif
    <div class="kpi ep-kpi">
      <div class="ep-ico or"><span class="material-symbols-outlined">receipt_long</span></div>
      <div>
        <div class="kval" data-ep-count>{{ $nbRecus ?? 0 }}</div>
        <div class="klbl">{{ __('payeur.recus_pdf') }}</div>
      </div>
    </div>
  </div>

  {{-- ── Graphique : répartition payé / restant (Chart.js) — commun ── --}}
  <div class="g2" style="margin-bottom:18px;">
    <div class="epcard">
      <div class="seclbl" style="margin:0 0 10px;">{{ __('payeur.situation_globale') ?? 'Situation globale' }}</div>
      <div style="height:210px;">
        <canvas id="chart-situation" data-ep-role="situation"></canvas>
      </div>
    </div>
    <div class="epcard">
      <div class="seclbl" style="margin:0 0 10px;">{{ __('payeur.par_enfant_categorie') ?? 'Par enfant / catégorie' }}</div>
      <div style="height:210px;">
        <canvas id="chart-enfants" data-ep-role="enfants"></canvas>
      </div>
    </div>
  </div>

@if($estSolo)
  @if(!$monDossier)
    {{-- Pas encore rattaché --}}
    <div class="epcard" style="text-align:center;color:#999;padding:40px 0;margin-bottom:18px;">
      <div style="width:48px;height:48px;background:#f0f0f0;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
        <span class="material-symbols-outlined" style="font-size:24px;color:#aaa;">apartment</span>
      </div>
      <div style="font-size:14px;font-weight:600;margin-bottom:6px;">{{ __('payeur.pas_rattache') }}</div>
      <div style="font-size:12px;color:#aaa;margin-bottom:16px;">{{ __('payeur.recherchez_etablissement') }}</div>
      <button onclick="epModal.open('modal-rattacher')" class="btn-p" style="width:auto;">
        {{ __('payeur.me_rattacher_maintenant') }}
      </button>
    </div>

  @else
    {{-- ── Dossier scolaire ── --}}
    <div class="seclbl" style="margin-top:0;">{{ __('payeur.mon_dossier_scolaire') }}</div>

    @php
      $totalSolo   = $monDossier->frais->sum('montant_total');
      $payeSolo    = $monDossier->frais->sum('montant_paye');
      $resteSolo   = $totalSolo - $payeSolo;
      $pctSolo     = $totalSolo > 0 ? round(($payeSolo / $totalSolo) * 100) : 0;
      $statutSolo  = $totalSolo <= 0 ? 'aucun' : ($resteSolo <= 0 ? 'regle' : ($payeSolo > 0 ? 'partiel' : 'impaye'));
    @endphp

    <div class="epcard" style="border-left:3px solid {{ match($statutSolo) {
        'regle' => 'var(--ep-teal)', 'partiel' => 'var(--ep-gold)', 'impaye' => 'var(--ep-red)', 'aucun' => 'var(--ep-blue-lt)', default => '#ddd',
    } }};margin-bottom:18px;">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;">
        <div>
          <div class="ep-frais-titre" style="font-size:15px;">{{ $monDossier->prenom }} {{ $monDossier->nom }}</div>
          <div style="font-size:11.5px;color:#888;font-weight:500;">
            {{ $monDossier->etablissement->nom ?? '—' }} · {{ $monDossier->classe }}
            @if($monDossier->matricule) · Mat. {{ $monDossier->matricule }} @endif
          </div>
        </div>
        <span class="pill {{ match($statutSolo) { 'regle' => 'pg', 'partiel' => 'pa', 'impaye' => 'pr', 'aucun' => 'pb', default => 'pa' } }}">
          {{ match($statutSolo) { 'regle' => __('payeur.statut_a_jour'), 'partiel' => __('payeur.statut_partiel'), 'impaye' => __('payeur.statut_impaye'), 'aucun' => __('payeur.statut_aucun_frais'), default => $statutSolo } }}
        </span>
      </div>
      <div class="prog" style="margin-bottom:4px;">
        <div class="pfill" style="width:{{ $pctSolo }}%;"></div>
      </div>
      <div style="font-size:10px;color:#888;margin-bottom:14px;">
        {{ $pctSolo }}% {{ __('payeur.regle') }} — {{ number_format($payeSolo,0,',',' ') }} / {{ number_format($totalSolo,0,',',' ') }} {{ __('payeur.fcfa_short') }}
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="{{ route('payeur.frais.apprenant', $monDossier) }}" class="btn-o" style="font-size:12px;padding:8px 14px;width:auto;">
          {{ __('payeur.voir_tous_mes_frais') }} →
        </a>
        <button type="button" onclick="epModal.open('modal-modifier-apprenant')"
                class="btn-o" style="width:auto;font-size:12px;padding:8px 14px;display:inline-flex;align-items:center;gap:5px;">
          <span class="material-symbols-outlined" style="font-size:15px;">edit</span> {{ __('payeur.modifier') }}
        </button>
        @php $peutDetacherSolo = $monDossier->frais->isEmpty(); @endphp
        @if($peutDetacherSolo)
          <button type="button" onclick="epModal.open('modal-detacher-apprenant')"
                  class="btn-r" style="width:auto;font-size:12px;padding:8px 14px;">
            {{ __('payeur.detacher') }}
          </button>
        @else
          <button type="button" title="{{ __('payeur.detacher_impossible_frais') }}"
                  class="btn-r" style="width:auto;font-size:12px;padding:8px 14px;opacity:.45;cursor:not-allowed;" disabled>
            {{ __('payeur.detacher') }}
          </button>
        @endif
      </div>
    </div>

    {{-- ── F05 : Frais ventilés par catégorie ── --}}
    <div class="seclbl">{{ __('payeur.mes_frais_par_categorie') }}</div>

    @forelse($monDossier->frais as $frais)
      @php
        $resteF = $frais->montant_total - $frais->montant_paye;
        $pctF   = $frais->montant_total > 0 ? round(($frais->montant_paye / $frais->montant_total) * 100) : 0;
      @endphp
      <div class="epcard" style="margin-bottom:10px;border-left:3px solid {{ match($frais->statut) {
          'regle' => 'var(--ep-teal)', 'partiel' => 'var(--ep-gold)', 'impaye' => 'var(--ep-red)', default => '#ddd',
      } }};">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
          <div>
            <div class="ep-frais-titre">{{ $frais->categorieFrais->nom ?? 'Frais scolaires' }}</div>
            <div style="font-size:11.5px;color:#888;font-weight:500;">{{ $frais->annee_scolaire }}</div>
          </div>
          <div style="text-align:right;">
            <div style="font-size:15px;font-weight:700;color:{{ $resteF > 0 ? 'var(--ep-red)' : 'var(--ep-teal)' }};">
              {{ $resteF > 0 ? number_format($resteF,0,',',' ').' '. __('payeur.fcfa_restant') : __('payeur.regle') }}
            </div>
            <div style="font-size:11px;color:#aaa;">{{ __('payeur.total') }} : {{ number_format($frais->montant_total,0,',',' ') }} {{ __('payeur.fcfa_short') }}</div>
          </div>
        </div>
        <div class="prog" style="margin-bottom:4px;">
          <div class="pfill" style="width:{{ $pctF }}%;background:{{ $frais->statut === 'impaye' ? 'var(--ep-red)' : 'var(--ep-teal)' }};"></div>
        </div>
        <div style="font-size:10px;color:#888;margin-bottom:10px;">{{ $pctF }}% {{ __('payeur.regle') }}</div>
        @if($resteF > 0)
          <a href="{{ route('payeur.paiement.show', $frais) }}" class="btn-p"
             style="display:block;text-align:center;padding:8px;font-size:12px;">
            {{ __('payeur.payer') }} {{ number_format($resteF,0,',',' ') }} {{ __('payeur.fcfa_short') }} →
          </a>
        @endif
      </div>
    @empty
      <div class="epcard" style="text-align:center;color:#aaa;padding:24px 0;font-size:13px;">
        {{ __('payeur.aucun_frais_enregistre') }}
      </div>
    @endforelse

  @endif

@else

  {{-- ── F13 : Mes enfants (aperçu dashboard) ── --}}
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <div class="seclbl" style="margin:0;">{{ __('payeur.mes_enfants') }}</div>
    <a href="{{ route('payeur.mes-enfants') }}" style="font-size:12px;color:var(--ep-teal);font-weight:500;text-decoration:none;">
      {{ __('payeur.voir_tous_mes_enfants') }} →
    </a>
  </div>

  @if($apprenants->isEmpty())
    <div class="epcard" style="text-align:center;color:#999;padding:24px 0;margin-bottom:18px;">
      <div style="font-size:14px;font-weight:600;margin-bottom:6px;">{{ __('payeur.aucun_enfant_rattache') }}</div>
      <button onclick="epModal.open('modal-rattacher')" class="btn-p" style="width:auto;margin-top:10px;">
        {{ __('payeur.rattacher_un_enfant') }}
      </button>
    </div>
  @else
    <div class="g2" style="margin-bottom:8px;">
      @foreach($apprenants->take(2) as $apprenant)
        @php
          $totalA  = $apprenant->frais->sum('montant_total');
          $payeA   = $apprenant->frais->sum('montant_paye');
          $resteA  = $totalA - $payeA;
          $pctA    = $totalA > 0 ? round(($payeA / $totalA) * 100) : 0;
          $statutA = $totalA <= 0 ? 'aucun' : ($resteA <= 0 ? 'regle' : ($payeA > 0 ? 'partiel' : 'impaye'));
          $premierImpayeA = $apprenant->frais->first(fn($f) => $f->statut !== 'regle');
        @endphp
        <div class="epcard" style="border-left:3px solid {{ match($statutA) {
            'regle' => 'var(--ep-teal)', 'partiel' => 'var(--ep-gold)', 'impaye' => 'var(--ep-red)', 'aucun' => 'var(--ep-blue-lt)', default => '#ddd',
        } }};">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;">
            <div>
              <div class="ep-frais-titre" style="font-size:15px;">{{ $apprenant->nom }} {{ $apprenant->prenom }}</div>
              <div style="font-size:11.5px;color:#888;font-weight:500;">
                {{ $apprenant->etablissement->nom ?? '—' }} · {{ $apprenant->classe }}
              </div>
            </div>
            <span class="pill {{ match($statutA) { 'regle' => 'pg', 'partiel' => 'pa', 'impaye' => 'pr', default => 'pa' } }}">
              {{ match($statutA) { 'regle' => __('payeur.statut_regle'), 'partiel' => __('payeur.statut_partiel'), 'impaye' => __('payeur.statut_impaye'), default => $statutA } }}
            </span>
          </div>
          @if($resteA > 0)
            <div class="prog" style="margin-bottom:4px;">
              <div class="pfill" style="width:{{ $pctA }}%;"></div>
            </div>
            <div style="font-size:10px;color:#888;margin-bottom:10px;">{{ $pctA }}% {{ __('payeur.regle') }}</div>
          @elseif($statutA === 'aucun')
            <div style="font-size:12px;color:#999;margin-bottom:10px;">{{ __('payeur.aucun_frais') }}</div>
          @else
            <div style="font-size:12px;color:var(--ep-teal);font-weight:600;margin-bottom:10px;">{{ __('payeur.tous_frais_regles') }}</div>
          @endif
          <div style="display:flex;gap:6px;">
            @if($premierImpayeA)
              <a href="{{ route('payeur.paiement.show', $premierImpayeA) }}"
                 class="{{ $statutA === 'impaye' ? 'btn-r' : 'btn-p' }}"
                 style="flex:1;text-align:center;padding:8px;font-size:12px;display:block;">
                {{ $statutA === 'impaye' ? __('payeur.payer').' →' : __('payeur.continuer').' →' }}
              </a>
            @endif
            <a href="{{ route('payeur.frais.apprenant', $apprenant) }}"
               class="btn-o" style="flex:1;text-align:center;padding:8px;font-size:12px;">{{ __('payeur.detail') }} →</a>
          </div>
        </div>
      @endforeach
    </div>
    @if($apprenants->count() > 2)
      <div style="text-align:center;margin-bottom:18px;">
        <a href="{{ route('payeur.mes-enfants') }}" style="color:var(--ep-teal);text-decoration:none;font-size:13px;font-weight:500;">
          {{ __('payeur.voir_tous_les', ['count' => $apprenants->count()]) }} →
        </a>
      </div>
    @endif
  @endif

@endif

  {{-- ════════════════════════════════════════════════════════════
       BLOCS COMMUNS (suite) : histogramme + derniers paiements.
       Mêmes données pour solo et famille ; seules les légendes
       changent (date en solo, prénom de l'enfant en famille).
       ════════════════════════════════════════════════════════════ --}}

  {{-- ── Histogramme des derniers paiements (montants) ── --}}
  @if(($derniersPaiements ?? collect())->filter(fn ($p) => $p->statut === 'valide')->isNotEmpty())
  <div class="epcard" style="margin-bottom:14px;">
    <div class="seclbl" style="margin:0 0 12px;display:flex;align-items:center;gap:8px;">
      <span class="material-symbols-outlined" style="color:var(--ep-gold);font-size:20px;">bar_chart</span>
      {{ __('payeur.derniers_paiements') }}
    </div>
    @php
      $paiementsHisto = $derniersPaiements->filter(fn ($p) => $p->statut === 'valide')->take(7)->values();
      $maxHisto = $paiementsHisto->max('montant') ?: 1;
    @endphp
    <div class="ep-histo" style="height:100px;">
      @foreach($paiementsHisto as $p)
      @php
        $quandH = $p->date_paiement ? \Carbon\Carbon::parse($p->date_paiement)->format('d/m') : null;
        $quiH   = (!$estSolo && $p->apprenant) ? $p->apprenant->prenom : null;
        // NB : conditions calculées ici en PHP pur — une directive Blade @if
        // collée après une lettre (ex. « FCFA@if ») n'est PAS compilée (règle
        // \B@ qui protège les adresses email) et casse l'équilibre if/endif.
        $tipH   = number_format($p->montant, 0, ',', ' ').' FCFA'
                . ($quiH ? ' · '.$quiH : '')
                . ($quandH ? ' · '.$quandH : '');
        $lblH   = $quiH ?? ($quandH ?? '—');
      @endphp
      <div class="ep-histo-col">
        <div class="ep-histo-bar" style="height:{{ max(6, round(($p->montant / $maxHisto) * 100)) }}%;animation-delay:{{ $loop->index * 50 }}ms;">
          <span class="ep-histo-tip">{{ $tipH }}</span>
        </div>
        <span class="ep-histo-lbl">{{ $lblH }}</span>
      </div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- ── Derniers paiements (commun : liste + état vide + lien historique) ── --}}
  <div class="seclbl" style="margin-top:18px;">{{ __('payeur.derniers_paiements') }}</div>
  <div class="epcard">
    @forelse ($derniersPaiements ?? [] as $paiement)
      <div class="row">
        <div>
          <div style="font-size:13px;font-weight:600;">
            {{ $paiement->fraisApprenant->categorieFrais->nom ?? __('payeur.paiement') }}
            @if(!$estSolo && $paiement->apprenant) — {{ $paiement->apprenant->prenom }} @endif
          </div>
          <div style="font-size:11px;color:#888;">
            {{ $paiement->date_paiement ? \Carbon\Carbon::parse($paiement->date_paiement)->format('d M Y') : '—' }}
            · {{ match($paiement->mode_paiement) { 'mtn_momo' => 'MTN MoMo', 'orange_money' => 'Orange Money', default => $paiement->mode_paiement } }}
          </div>
        </div>
        <div style="text-align:right;">
          <div style="font-weight:600;color:{{ $paiement->statut === 'valide' ? 'var(--ep-teal)' : ($paiement->statut === 'rembourse' ? 'var(--ep-red)' : 'var(--ep-gold)') }};">
            {{ $paiement->statut === 'rembourse' ? '– ' : '' }}{{ number_format($paiement->montant,0,',',' ') }} {{ __('payeur.fcfa_short') }}
          </div>
          <span class="pill {{ match($paiement->statut) { 'valide' => 'pg', 'en_attente' => 'pa', 'echoue' => 'pr', 'rembourse' => 'pb', 'annule' => 'pb', default => 'pa' } }}">
            {{ match($paiement->statut) { 'valide' => __('payeur.statut_valide'), 'en_attente' => __('payeur.statut_en_attente'), 'echoue' => __('payeur.statut_echoue'), 'rembourse' => __('payeur.statut_rembourse'), 'annule' => __('payeur.statut_annule'), default => $paiement->statut } }}
          </span>
        </div>
      </div>
    @empty
      <div style="text-align:center;color:#999;font-size:13px;padding:20px 0;">{{ __('payeur.aucun_paiement') }}</div>
    @endforelse
  </div>
  @if(($derniersPaiements ?? collect())->isNotEmpty())
    <div style="text-align:center;margin-top:14px;">
      <a href="{{ route('payeur.historique') }}" style="color:var(--ep-teal);text-decoration:none;font-size:13px;font-weight:500;">
        {{ __('payeur.voir_historique') }} →
      </a>
    </div>
  @endif

@endsection

@push('styles')
<style>
.m-etab-item:hover { background: #f0fdf4 !important; }
.m-etab-item:hover .m-etab-check { opacity: 0.4 !important; }
</style>
@endpush

@push('scripts')
@include('payeur.partials.modal-rattacher-scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var frm = document.getElementById('form-detacher');
    if (!frm) return;
    var coche = document.getElementById('detacher-confirm');
    var btn   = document.getElementById('btn-detacher-confirm');
    coche.addEventListener('change', function(){
        btn.disabled = !coche.checked;
    });
});
</script>

{{-- ══ Chart.js : situation globale et par enfant (données serveur injectées) ══ --}}
<script src="{{ asset('js/chart.umd.min.js') }}"></script>
<script>
(function () {
    'use strict';

    // ── Sélection des canvas PAR RÔLE (data-ep-role) : les blocs solo et
    //    famille ont été dédupliqués, il n'existe plus qu'UNE seule paire
    //    de canvas (ids uniques chart-situation / chart-enfants). ──
    function canvasParRole(role) {
        return document.querySelector('canvas[data-ep-role="' + role + '"]');
    }

    function detruire(idRole) {
        var c = canvasParRole(idRole);
        if (c && window.Chart && Chart.getChart(c)) Chart.getChart(c).destroy();
    }

    // ── Construction (ou re-construction) des graphiques au changement
    //    de thème clair / sombre (événement « ep-themechange »). ──
    function monterGraphiques() {

        // Détruit les instances existantes avant re-création (bascule de thème)
        detruire('situation');
        detruire('enfants');

    // Données injectées depuis le contrôleur — sécurité : @@json échappe correctement
    // NB : ne JAMAIS écrire la directive @@json à l'intérieur d'un commentaire
    // Blade la compile même dans un commentaire JS et génère un json_encode()
    // cassé (ParseError « unexpected , »).
    const totalDu   = @json((int) ($totalDu ?? 0));
    const totalPaye = @json((int) ($totalPaye ?? 0));
    const apprenants = @json($apprenants->map(function ($a) {
        $t = $a->frais->sum('montant_total');
        $p = $a->frais->sum('montant_paye');
        return ['nom' => $a->prenom . ' ' . $a->nom, 'total' => (int) $t, 'paye' => (int) $p];
    }));

    const TEAL = '#0D9E75', RED = '#D94040', NAVY = '#0B2545', GOLD = '#E8A020';
    // Thème actuel : adapte les couleurs de lecture des graphiques
    const sombre = document.documentElement.getAttribute('data-theme') === 'dark';
    Chart.defaults.font.family = "'Poppins', sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = sombre ? '#8CA0B8' : '#5A6472';

    // ══ Donut : payé vs restant ══
    const ctxSit = canvasParRole('situation');
    if (ctxSit) {
        if (totalDu + totalPaye <= 0) {
            // Aucun frais : donut neutre
            new Chart(ctxSit, {
                type: 'doughnut',
                data: { labels: ['Aucun frais'], datasets: [{ data: [1], backgroundColor: [sombre ? '#1C2C45' : '#E4E9EE'], borderWidth: 0 }] },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '70%',
                    plugins: { legend: { display: false } }
                }
            });
        } else {
            new Chart(ctxSit, {
                type: 'doughnut',
                data: {
                    labels: ['Déjà payé', 'Restant dû'],
                    datasets: [{
                        data: [totalPaye, totalDu],
                        backgroundColor: [TEAL, RED],
                        borderWidth: 3,
                        borderColor: sombre ? '#0E1A2E' : '#fff',
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '70%',
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, padding: 14 } },
                        tooltip: {
                            backgroundColor: NAVY, padding: 12, cornerRadius: 10,
                            callbacks: {
                                label: (ctx) => ' ' + ctx.parsed.toLocaleString('fr-FR') + ' FCFA'
                            }
                        }
                    }
                }
            });
        }
    }

    // ══ Barres horizontales : total vs payé par enfant ══
    const ctxEnf = canvasParRole('enfants');
    if (ctxEnf && apprenants.length > 0) {
        new Chart(ctxEnf, {
            type: 'bar',
            data: {
                labels: apprenants.map(a => a.nom),
                datasets: [
                    {
                        label: 'Total dû',
                        data: apprenants.map(a => a.total),
                        backgroundColor: 'rgba(11,37,69,.25)',
                        borderRadius: 6
                    },
                    {
                        label: 'Déjà payé',
                        data: apprenants.map(a => a.paye),
                        backgroundColor: TEAL,
                        borderRadius: 6
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 12 } },
                    tooltip: { backgroundColor: NAVY, padding: 12, cornerRadius: 10 }
                },
                scales: {
                    x: {
                        grid: { color: sombre ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.05)' },
                        ticks: { callback: (v) => (v >= 1000000 ? (v/1000000) + 'M' : (v >= 1000 ? (v/1000) + 'k' : v)) }
                    },
                    y: { grid: { display: false } }
                }
            }
        });
    } else if (ctxEnf) {
        // Aucun enfant : on masque la carte graphique proprement
        ctxEnf.closest('.epcard').style.display = 'none';
        var sitCard = ctxSit ? ctxSit.closest('.epcard') : null;
        if (sitCard) sitCard.style.gridColumn = '1 / -1';
    }

    } // ── fin monterGraphiques ──

    // Premier rendu : double requestAnimationFrame — garantit que les
    // canvas sont PEINTS (les animations d'entrée posent opacity:0 au
    // premier frame, ce qui donnait des graphiques vides).
    // IMPORTANT : ce premier rendu est déclenché UNE SEULE FOIS, en dehors
    // de monterGraphiques(). Placé À L'INTÉRIEUR, l'appel se re-planifiait
    // lui-même toutes les 2 frames : boucle infinie — les graphiques étaient
    // détruits/reconstruits en permanence (donut et barres apparemment vides,
    // CPU saturé) et les écouteurs ep-themechange s'accumulaient à chaque
    // passage. Le rendu au changement de thème passe par l'écouteur ci-dessous.
    requestAnimationFrame(function () {
        requestAnimationFrame(monterGraphiques);
    });

    // Re-rendu en fondu à chaque bascule clair/sombre (un seul écouteur).
    var epTimerTheme = null;
    document.addEventListener('ep-themechange', function () {
        clearTimeout(epTimerTheme);
        epTimerTheme = setTimeout(monterGraphiques, 350);
    });
})();
</script>
@endpush
