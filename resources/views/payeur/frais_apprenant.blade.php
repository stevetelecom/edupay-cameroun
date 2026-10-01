@extends('layouts.payeur')

@section('title', __('payeur.frais') . ' — ' . $apprenant->prenom . ' ' . $apprenant->nom)

@push('modals')
<div id="modal-modifier-apprenant" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-lg">
    <div class="ep-modal-head">
      <h3>{{ __('payeur.modif_dossier') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-modifier-apprenant')">×</button>
    </div>
    <form method="POST" action="{{ route('payeur.apprenant.update', $apprenant) }}">
      @csrf @method('PUT')
      <div class="ep-modal-body">
        <div class="g2">
          <div>
            <div class="lbl">{{ __('payeur.prenom_lbl') }}</div>
            <input class="inp" name="prenom" value="{{ $apprenant->prenom }}" required />
          </div>
          <div>
            <div class="lbl">{{ __('payeur.nom_lbl') }}</div>
            <input class="inp" name="nom" value="{{ $apprenant->nom }}" required />
          </div>
        </div>
        <div class="lbl">Classe / Niveau</div>
        <input class="inp" name="classe" value="{{ $apprenant->classe }}" required />
        <div class="lbl">Matricule</div>
        <input class="inp" name="matricule" value="{{ $apprenant->matricule }}" placeholder="EP-XXXX" />
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;"
                onclick="epModal.close('modal-modifier-apprenant')">{{ __('messages.annuler') }}</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">
          {{ __('messages.enregistrer') }} →
        </button>
      </div>
    </form>
  </div>
</div>

{{-- Modal de confirmation du détachement (taille réduite, centrée) --}}
<div id="modal-detacher-apprenant" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm">
    <div class="ep-modal-head">
      <h3 style="display:flex;align-items:center;gap:9px;color:var(--ep-red);">
        <span class="ep-ico rouge ep-ico-side"><span class="material-symbols-outlined">link_off</span></span>
        {{ __('payeur.detacher_titre') }}
      </h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-detacher-apprenant')">×</button>
    </div>
    <form method="POST" action="{{ route('payeur.apprenant.detach', $apprenant) }}" id="form-detacher">
      @csrf @method('DELETE')
      <div class="ep-modal-body" style="padding:18px 20px;">
        {{-- Récapitulatif v3 (même langage visuel que le modal rattacher) --}}
        <div class="ep-m-conf-carte" style="margin-bottom:12px;">
          <div class="ep-m-conf-ligne">
            <div class="ep-m-conf-lib">{{ __('messages.etablissement') }}</div>
            <div class="ep-m-conf-val" style="font-weight:700;">{{ $apprenant->etablissement->nom ?? '—' }}</div>
          </div>
          <div class="ep-m-conf-ligne">
            <div class="ep-m-conf-lib">{{ __('payeur.m_conf_app_solo') }}</div>
            <div class="ep-m-conf-val">
              <div style="font-weight:700;">{{ $apprenant->prenom }} {{ $apprenant->nom }}</div>
              <div style="font-size:11px;color:#888;">{{ $apprenant->classe }}</div>
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
@endpush

@section('content')

    {{-- En-tête de page aligné sur le tableau de bord (pastille dorée + Poppins) --}}
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <a href="{{ route('payeur.dashboard') }}" class="ep-retour-lien">
            <span class="material-symbols-outlined" style="font-size:15px;">arrow_back</span>
            {{ __('payeur.retour') }}
        </a>
    </div>

    {{-- En-tête apprenant --}}
    <div class="epcard" style="margin-bottom:18px;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="ep-ico navy" style="width:44px;height:44px;border-radius:12px;">
                    <span class="material-symbols-outlined" style="font-size:24px;">person</span>
                </div>
                <div>
                    <div style="font-size:15px;font-weight:700;color:var(--ep-navy);">
                        {{ $apprenant->prenom }} {{ $apprenant->nom }}
                    </div>
                    <div class="ep-sous-titre">
                        {{ $apprenant->etablissement->nom ?? '—' }} · {{ $apprenant->classe }}
                        @if($apprenant->matricule) · Mat. {{ $apprenant->matricule }} @endif
                    </div>
                </div>
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
                {{-- Le détachement est interdit si des frais/paiements existent --}}
                @php
                    $peutDetacher = $apprenant->frais->isEmpty();
                @endphp
                @if($peutDetacher)
                    <button type="button" onclick="epModal.open('modal-detacher-apprenant')"
                            class="btn-r" style="width:auto;font-size:12px;padding:8px 14px;">
                        {{ __('payeur.detacher') }}
                    </button>
                @else
                    <button type="button" title="{{ __('payeur.detacher_impossible_frais') }}"
                            class="btn-r" style="width:auto;font-size:12px;padding:8px 14px;opacity:.45;cursor:not-allowed;"
                            disabled>
                        {{ __('payeur.detacher') }}
                    </button>
                @endif
                <button type="button" onclick="epModal.open('modal-modifier-apprenant')"
                        class="btn-o" style="width:auto;font-size:12px;padding:8px 14px;display:inline-flex;align-items:center;gap:5px;">
                    <span class="material-symbols-outlined" style="font-size:15px;">edit</span> {{ __('payeur.modifier') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Liste des frais --}}
    <div class="seclbl">{{ __('payeur.frais_scolaires_detail') }}</div>

    @forelse ($apprenant->frais as $frais)
        @php
            $reste      = $frais->montant_total - $frais->montant_paye;
            $pourcentage = $frais->montant_total > 0
                ? round(($frais->montant_paye / $frais->montant_total) * 100)
                : 0;
            $dernierPaiement = $frais->paiements->first();
        @endphp

        <div class="epcard" style="margin-bottom:14px;border-left:3px solid {{ match($frais->statut) {
            'regle'   => 'var(--ep-teal)',
            'partiel' => 'var(--ep-gold)',
            'impaye'  => 'var(--ep-red)',
            default   => '#ddd',
        } }};">

            {{-- En-tête frais --}}
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;">
                <div>
                    <div class="ep-frais-titre">
                        {{ $frais->categorieFrais->nom ?? __('payeur.categorie_frais_defaut') }}
                    </div>
                    <div style="font-size:11.5px;color:#888;font-weight:500;">{{ $frais->annee_scolaire }}</div>
                </div>
                <span class="pill {{ match($frais->statut) {
                    'regle' => 'pg', 'partiel' => 'pa', 'impaye' => 'pr', default => 'pa',
                } }}">
                    {{ match($frais->statut) {
                        'regle' => __('payeur.regle_st'), 'partiel' => __('payeur.partiel_st'), 'impaye' => __('payeur.impaye_st'), default => $frais->statut,
                    } }}
                </span>
            </div>

            {{-- Montants --}}
            <div style="display:flex;gap:20px;margin-bottom:10px;">
                <div>
                    <div style="font-size:10px;color:#aaa;margin-bottom:2px;">{{ __('payeur.total_lbl') }}</div>
                    <div style="font-size:16px;font-weight:700;">
                        {{ number_format($frais->montant_total, 0, ',', ' ') }} FCFA
                    </div>
                </div>
                <div>
                    <div style="font-size:10px;color:#aaa;margin-bottom:2px;">{{ __('payeur.paye_lbl') }}</div>
                    <div style="font-size:16px;font-weight:700;color:var(--ep-teal);">
                        {{ number_format($frais->montant_paye, 0, ',', ' ') }} FCFA
                    </div>
                </div>
                @if($reste > 0)
                    <div>
                        <div style="font-size:10px;color:#aaa;margin-bottom:2px;">{{ __('payeur.reste_lbl') }}</div>
                        <div style="font-size:16px;font-weight:700;color:var(--ep-red);">
                            {{ number_format($reste, 0, ',', ' ') }} FCFA
                        </div>
                    </div>
                @endif
            </div>

            {{-- Barre de progression --}}
            <div class="prog" style="margin-bottom:4px;">
                <div class="pfill" style="width:{{ $pourcentage }}%;background:{{ $frais->statut === 'impaye' ? 'var(--ep-red)' : 'var(--ep-teal)' }};"></div>
            </div>
            <div style="font-size:10px;color:#888;margin-bottom:12px;">{{ __('payeur.pct_regle', ['pct' => $pourcentage]) }}</div>

            {{-- Échéancier --}}
            @if($frais->categorieFrais->echeanciers->count())
                <div class="seclbl" style="margin-bottom:6px;">{{ __('payeur.echeancier') }}</div>
                @foreach($frais->categorieFrais->echeanciers as $ech)
                    <div style="display:flex;justify-content:space-between;align-items:center;
                                padding:7px 0;border-bottom:1px solid #f5f5f5;font-size:12px;">
                        <div>
                            <span style="font-weight:600;">{{ __('payeur.tranche_t') }}{{ $ech->numero_tranche }}</span>
                            @if($ech->libelle) — {{ $ech->libelle }} @endif
                        </div>
                        <div style="text-align:right;">
                            <div style="font-weight:600;">{{ number_format($ech->montant, 0, ',', ' ') }} FCFA</div>
                            <div style="font-size:10px;color:#aaa;">
                                {{ __('payeur.echeance_fmt') }} {{ $ech->date_echeance->format('d/m/Y') }}
                            </div>
                        </div>
                    </div>
                @endforeach
                <div style="margin-bottom:10px;"></div>
            @endif

            {{-- Dernier paiement --}}
            @if($dernierPaiement)
                <div style="font-size:11px;color:#888;margin-bottom:10px;">
                    {{ __('payeur.dernier_paiement_valide') }}
                    {{ number_format($dernierPaiement->montant, 0, ',', ' ') }} FCFA
                    {{ __('payeur.le_date_fmt') }} {{ \Carbon\Carbon::parse($dernierPaiement->date_validation)->format('d/m/Y') }}
                    {{ __('payeur.via_fmt') }} {{ match($dernierPaiement->mode_paiement) {
                        'mtn_momo' => 'MTN MoMo',
                        'orange_money' => 'Orange Money',
                        default => $dernierPaiement->mode_paiement,
                    } }}
                </div>
            @endif

            {{-- Bouton payer --}}
            @if($reste > 0)
                <a href="{{ route('payeur.paiement.show', $frais) }}"
                   class="btn-p" style="display:block;text-align:center;padding:10px;">
                    {{ __('payeur.payer_btn') }} {{ number_format($reste, 0, ',', ' ') }} FCFA →
                </a>
            @else
                <div style="text-align:center;font-size:12px;color:var(--ep-teal);font-weight:600;padding:8px 0;">
                    {{ __('payeur.entierement_regle') }}
                </div>
            @endif

        </div>
    @empty
        <div class="epcard" style="text-align:center;color:#999;padding:30px 0;">
            {{ __('payeur.aucun_frais_pour_apprenant') }}
        </div>
    @endforelse

@endsection

@push('scripts')
<script>
(function(){
  document.addEventListener('DOMContentLoaded', function(){
    var frm = document.getElementById('form-detacher');
    if (!frm) return;
    var coche = document.getElementById('detacher-confirm');
    var btn   = document.getElementById('btn-detacher-confirm');
    coche.addEventListener('change', function(){
      btn.disabled = !coche.checked;
    });
  });
})();
</script>
@endpush
