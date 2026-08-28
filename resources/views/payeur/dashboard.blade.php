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

@endpush

@section('content')

@if(isset($notifications) && $notifications->count() > 0)
    @foreach($notifications as $notif)
    <div style="background:{{ $notif->type === 'error' ? '#FEF2F2' : '#FFFBEB' }};
                border:1.5px solid {{ $notif->type === 'error' ? '#D94040' : '#E8A020' }};
                border-radius:10px;padding:14px 16px;margin-bottom:14px;display:flex;align-items:flex-start;gap:12px;">
        <span class="material-symbols-outlined" style="font-size:20px;color:{{ $notif->type === 'error' ? '#D94040' : '#E8A020' }};flex-shrink:0;">
            {{ $notif->type === 'error' ? 'error' : 'info' }}
        </span>
        <div style="flex:1;">
            <div style="font-size:13px;font-weight:700;color:{{ $notif->type === 'error' ? '#7F1D1D' : '#92400E' }};margin-bottom:2px;">
                {{ $notif->titre }}
            </div>
            <div style="font-size:12px;color:{{ $notif->type === 'error' ? '#7F1D1D' : '#92400E' }};opacity:.85;">
                {{ $notif->message }}
            </div>
        </div>
        <form method="POST" action="{{ route('payeur.notifications.lu', $notif) }}">
            @csrf @method('PATCH')
            <button type="submit" style="background:none;border:none;cursor:pointer;color:#888;font-size:18px;line-height:1;flex-shrink:0;">×</button>
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

  {{-- ── KPIs solo ── --}}
  <div class="g4" style="margin-bottom:18px;">
    <div class="kpi">
      <div class="kval" style="color:var(--ep-red);">{{ number_format($totalDu ?? 0, 0, ',', ' ') }}</div>
      <div class="klbl">{{ __('payeur.fcfa_dus') }}</div>
    </div>
    <div class="kpi">
      <div class="kval" style="color:var(--ep-teal);">{{ number_format($totalPaye ?? 0, 0, ',', ' ') }}</div>
      <div class="klbl">{{ __('payeur.fcfa_payes') }}</div>
    </div>
    <div class="kpi">
      <div class="kval">{{ $pourcentageGlobal ?? 0 }}%</div>
      <div class="klbl">{{ __('payeur.solde_regle') }}</div>
    </div>
    <div class="kpi">
      <div class="kval">{{ $nbRecus ?? 0 }}</div>
      <div class="klbl">{{ __('payeur.recus_pdf') }}</div>
    </div>
  </div>

  @if(!$monDossier)
    {{-- Pas encore rattaché --}}
    <div class="epcard" style="text-align:center;color:#999;padding:40px 0;margin-bottom:18px;">
      <div style="font-size:32px;margin-bottom:12px;">🏫</div>
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
          <div style="font-size:15px;font-weight:700;">{{ $monDossier->prenom }} {{ $monDossier->nom }}</div>
          <div style="font-size:11px;color:#888;">
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
      <div style="display:flex;gap:8px;">
        <a href="{{ route('payeur.frais.apprenant', $monDossier) }}" class="btn-o" style="font-size:12px;padding:8px 14px;width:auto;">
          {{ __('payeur.voir_tous_mes_frais') }} →
        </a>
        <button type="button" onclick="epModal.open('modal-modifier-apprenant')"
                class="btn-o" style="width:auto;font-size:12px;padding:8px 14px;">
          ✎ {{ __('payeur.modifier') }}
        </button>
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
            <div style="font-size:13px;font-weight:700;">{{ $frais->categorieFrais->nom ?? 'Frais scolaires' }}</div>
            <div style="font-size:11px;color:#888;">{{ $frais->annee_scolaire }}</div>
          </div>
          <div style="text-align:right;">
            <div style="font-size:15px;font-weight:700;color:{{ $resteF > 0 ? 'var(--ep-red)' : 'var(--ep-teal)' }};">
              {{ $resteF > 0 ? number_format($resteF,0,',',' ').' '. __('payeur.fcfa_restant') : '✓ '. __('payeur.regle') }}
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

  {{-- ── Derniers paiements (solo) ── --}}
  <div class="seclbl" style="margin-top:8px;">{{ __('payeur.derniers_paiements') }}</div>
  <div class="epcard">
    @forelse ($derniersPaiements ?? [] as $paiement)
      <div class="row">
        <div>
          <div style="font-size:13px;font-weight:600;">
            {{ $paiement->fraisApprenant->categorieFrais->nom ?? __('payeur.paiement') }}
          </div>
          <div style="font-size:11px;color:#888;">
            {{ $paiement->date_paiement ? \Carbon\Carbon::parse($paiement->date_paiement)->format('d M Y') : '—' }}
            · {{ match($paiement->mode_paiement) { 'mtn_momo' => 'MTN MoMo', 'orange_money' => 'Orange Money', default => $paiement->mode_paiement } }}
          </div>
        </div>
        <div style="text-align:right;">
          <div style="font-weight:600;color:{{ $paiement->statut === 'valide' ? 'var(--ep-teal)' : 'var(--ep-gold)' }};">
            {{ number_format($paiement->montant,0,',',' ') }} {{ __('payeur.fcfa_short') }}
          </div>
          <span class="pill {{ match($paiement->statut) { 'valide' => 'pg', 'en_attente' => 'pa', 'echoue' => 'pr', default => 'pa' } }}">
            {{ match($paiement->statut) { 'valide' => __('payeur.statut_valide'), 'en_attente' => __('payeur.statut_en_attente'), 'echoue' => __('payeur.statut_echoue'), default => $paiement->statut } }}
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

@else
{{-- ════════════════════════════════════════════════════════════
     VUE FAMILLE — Parent
     F03 Tableau de bord + F13 Multi-enfants
     ════════════════════════════════════════════════════════════ --}}

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

  {{-- ── KPIs famille ── --}}
  <div class="g4" style="margin-bottom:18px;">
    <div class="kpi">
      <div class="kval" style="color:var(--ep-red);">{{ number_format($totalDu ?? 0, 0, ',', ' ') }}</div>
      <div class="klbl">{{ __('payeur.fcfa_dus') }}</div>
    </div>
    <div class="kpi">
      <div class="kval" style="color:var(--ep-teal);">{{ number_format($totalPaye ?? 0, 0, ',', ' ') }}</div>
      <div class="klbl">{{ __('payeur.fcfa_payes') }}</div>
    </div>
    <div class="kpi">
      <div class="kval">{{ $apprenants->count() }}</div>
      <div class="klbl">{{ __('payeur.enfants_suivis') }}</div>
    </div>
    <div class="kpi">
      <div class="kval">{{ $nbRecus ?? 0 }}</div>
      <div class="klbl">{{ __('payeur.recus_pdf') }}</div>
    </div>
  </div>

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
              <div style="font-size:15px;font-weight:700;">{{ $apprenant->nom }} {{ $apprenant->prenom }}</div>
              <div style="font-size:11px;color:#888;">
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
          @else
            <div style="font-size:12px;color:var(--ep-teal);font-weight:600;margin-bottom:10px;">✓ {{ __('payeur.tous_frais_regles') }}</div>
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


  {{-- Derniers paiements (multi-enfants) --}}
  @if(($derniersPaiements ?? collect())->isNotEmpty())
  <div class="seclbl" style="margin-top:18px;">{{ __('payeur.derniers_paiements') }}</div>
  <div class="epcard">
    @foreach($derniersPaiements as $paiement)
    <div class="row">
      <div>
        <div style="font-size:13px;font-weight:600;">
          {{ $paiement->fraisApprenant->categorieFrais->nom ?? __('payeur.paiement') }}
          @if($paiement->apprenant) — {{ $paiement->apprenant->prenom }} @endif
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
        <span class="pill {{ match($paiement->statut) { 'valide' => 'pg', 'en_attente' => 'pa', 'echoue' => 'pr', 'rembourse' => 'pb', default => 'pa' } }}">
          {{ match($paiement->statut) { 'valide' => __('payeur.statut_valide'), 'en_attente' => __('payeur.statut_en_attente'), 'echoue' => __('payeur.statut_echoue'), 'rembourse' => __('payeur.statut_rembourse'), default => $paiement->statut } }}
        </span>
      </div>
    </div>
    @endforeach
  </div>
  <div style="text-align:center;margin-top:14px;">
    <a href="{{ route('payeur.historique') }}" style="color:var(--ep-teal);text-decoration:none;font-size:13px;font-weight:500;">{{ __('payeur.voir_historique') }} →</a>
  </div>
  @endif


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
@endpush
