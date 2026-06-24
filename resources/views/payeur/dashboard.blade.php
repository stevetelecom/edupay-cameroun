@extends('layouts.payeur')

@section('title', 'Mon espace')

@push('modals')

{{-- ══ MODAL : Rattacher un enfant / apprenant (F04 + F13) ══ --}}
<div id="modal-rattacher" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-lg">
    <div class="ep-modal-head">
      <h3>
        {{ in_array(Auth::user()->profil, ['eleve','etudiant']) ? 'Me rattacher à un établissement' : 'Rattacher un enfant / étudiant' }}
      </h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-rattacher')">×</button>
    </div>
    <div class="ep-modal-body">

      {{-- ETAPE 1 : Recherche établissement --}}
      <div id="m-step1">
        <div style="font-size:11px;font-weight:600;color:#0D9E75;text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;">
          Étape 1 — Choisir l'établissement
        </div>

        {{-- Filtres --}}
        <div style="display:flex;gap:8px;margin-bottom:8px;">
          <div style="position:relative;flex:1;">
            <input type="text" id="m-etab-search"
                   placeholder="Nom de l'établissement…"
                   style="width:100%;padding:9px 12px 9px 34px;border:1px solid #ddd;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                   oninput="mFiltrerEtabs()"
                 onfocus="document.getElementById('m-etab-liste').style.display='block'" />
            <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#aaa;" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          </div>
          <input type="text" id="m-etab-ville"
                 placeholder="Ville…"
                 style="width:130px;padding:9px 12px;border:1px solid #ddd;border-radius:8px;font-size:13px;outline:none;"
                 oninput="mFiltrerEtabs()"
                 onfocus="document.getElementById('m-etab-liste').style.display='block'" />
          <input type="text" id="m-etab-code"
                 placeholder="Code…"
                 style="width:110px;padding:9px 12px;border:1px solid #ddd;border-radius:8px;font-size:13px;outline:none;"
                 oninput="mFiltrerEtabs()"
                 onfocus="document.getElementById('m-etab-liste').style.display='block'" />
          <button type="button" onclick="mFiltrerEtabs();document.getElementById('m-etab-liste').style.display='block';"
                  style="background:var(--ep-teal);color:#fff;border:none;padding:9px 16px;
                         border-radius:8px;font-size:13px;font-weight:500;cursor:pointer;white-space:nowrap;flex-shrink:0;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" style="margin-right:4px;vertical-align:middle;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            Rechercher
          </button>
        </div>

        <div id="m-etab-liste"
             style="border:1px solid #e0e0e0;border-radius:8px;background:#fff;max-height:200px;overflow-y:auto;">
          @foreach($etablissements ?? [] as $etab)
            <div class="m-etab-item"
                 data-id="{{ $etab->id }}"
                 data-nom="{{ $etab->nom }}"
                 data-ville="{{ $etab->ville ?? '' }}"
                 data-type="{{ $etab->type ?? '' }}"
                 data-code="{{ $etab->code_etablissement ?? '' }}"
                 onclick="mSelectionnerEtab(this)"
                 style="padding:10px 14px;cursor:pointer;border-bottom:1px solid #f5f5f5;
                        display:flex;align-items:center;gap:10px;transition:background .12s;">
              {{-- Logo ou avatar initiale --}}
              @if($etab->logo)
                <img src="{{ asset('storage/'.$etab->logo) }}"
                     alt="{{ $etab->nom }}"
                     style="width:36px;height:36px;border-radius:8px;object-fit:cover;flex-shrink:0;border:1px solid #eee;" />
              @else
                <div style="width:36px;height:36px;border-radius:8px;background:var(--ep-teal-lt);
                            display:flex;align-items:center;justify-content:center;
                            font-size:14px;font-weight:700;color:var(--ep-teal);flex-shrink:0;">
                  {{ strtoupper(substr($etab->nom, 0, 1)) }}
                </div>
              @endif
              {{-- Infos --}}
              <div style="flex:1;min-width:0;">
                <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  {{ $etab->nom }}
                </div>
                <div style="font-size:11px;color:#888;">
                  📍 {{ $etab->ville ?? '—' }}
                  @if($etab->type) · {{ ucfirst($etab->type) }} @endif
                  @if($etab->code_etablissement)
                    · <span style="color:var(--ep-teal);font-weight:500;">{{ $etab->code_etablissement }}</span>
                  @endif
                </div>
              </div>
              {{-- Check icon --}}
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2.5"
                   class="m-etab-check" style="opacity:0;flex-shrink:0;">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
            </div>
          @endforeach
          @if(($etablissements ?? collect())->isEmpty())
            <div style="padding:20px;text-align:center;color:#aaa;font-size:13px;">Aucun établissement partenaire disponible.</div>
          @endif
        </div>
      </div>

      {{-- ETAPE 2 : Annuaire apprenants (apres selection etablissement) --}}
      <div id="m-step2" style="display:none;margin-top:16px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
          <div style="font-size:11px;font-weight:600;color:#0D9E75;text-transform:uppercase;letter-spacing:.05em;">
            Étape 2 — Trouver dans l'annuaire
          </div>
          <button type="button" onclick="mReinitEtab()"
                  style="font-size:11px;color:#888;background:none;border:none;cursor:pointer;text-decoration:underline;">
            Changer d'établissement
          </button>
        </div>

        {{-- Badge etablissement selectionne --}}
        <div style="background:var(--ep-teal-lt);border-radius:8px;padding:10px 14px;margin-bottom:12px;display:flex;align-items:center;gap:10px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2"><rect x="2" y="7" width="20" height="15"/><polyline points="16 2 12 7 8 2"/></svg>
          <div>
            <div style="font-size:13px;font-weight:600;color:#085041;" id="m-etab-badge-nom"></div>
            <div style="font-size:11px;color:#1B9E75;" id="m-etab-badge-ville"></div>
          </div>
        </div>

        {{-- Section annuaire — masquée jusqu'à sélection établissement --}}
        <div id="m-section-annuaire" style="display:none;">

        {{-- Recherche dans annuaire --}}
        <div style="position:relative;margin-bottom:10px;">
          <input type="text" id="m-apprenant-search"
                 placeholder="Chercher par nom, prénom ou classe…"
                 style="width:100%;padding:9px 12px 9px 34px;border:1px solid #ddd;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                 oninput="mRechercherApprenant(this.value)" />
          <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#aaa;" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </div>

        <div id="m-apprenant-liste"
             style="border:1px solid #e0e0e0;border-radius:8px;background:#fff;max-height:160px;overflow-y:auto;margin-bottom:12px;">
          <div style="padding:16px;text-align:center;color:#aaa;font-size:13px;">
            Tapez un nom pour rechercher dans l'annuaire…
          </div>
        </div>

        {{-- Formulaire --}}
        <form method="POST" action="{{ route('payeur.onboarding.store') }}" id="m-onb-form">
          @csrf
          <input type="hidden" name="lien" id="m-lien"
                 value="{{ in_array(Auth::user()->profil, ['eleve','etudiant']) ? 'soi-meme' : 'parent' }}" />
          <input type="hidden" name="etablissement_id"  id="m-h-etab-id"  value="" />
          <input type="hidden" name="etablissement_nom" id="m-h-etab-nom" value="" />
          <input type="hidden" name="matricule"         id="m-h-matricule" value="" />
          <input type="hidden" name="apprenant_id"      id="m-h-apprenant-id" value="" />

          {{-- Badge apprenant sélectionné --}}
          <div id="m-app-badge"
               style="display:none;background:var(--ep-teal-lt);border-radius:8px;
                      padding:10px 14px;margin-bottom:10px;
                      align-items:center;justify-content:space-between;">
            <div>
              <div style="font-size:13px;font-weight:600;color:#085041;" id="m-app-badge-nom"></div>
              <div style="font-size:11px;color:#1B9E75;" id="m-app-badge-info"></div>
            </div>
            <button type="button" onclick="mReinitApprenant()"
                    style="background:none;border:none;color:#888;cursor:pointer;font-size:18px;line-height:1;">×</button>
          </div>

          {{-- Champs manuels (si pas trouve dans annuaire) --}}
          <div id="m-saisie-manuelle" style="display:none;">
            <div style="font-size:12px;color:#888;margin-bottom:10px;padding:8px 12px;background:#f9fafb;border-radius:6px;">
              @if(in_array(Auth::user()->profil, ['eleve','etudiant']))
                Pas trouvé dans l'annuaire ? Remplissez ci-dessous — votre demande sera validée par l'établissement.
              @else
                Pas trouvé dans l'annuaire ? Saisissez les informations de l'enfant manuellement.
              @endif
            </div>

            @if(!in_array(Auth::user()->profil, ['eleve','etudiant']))
            <div class="g2" style="margin-bottom:10px;">
              <div>
                <div class="lbl">Prénom de l'enfant *</div>
                <input class="inp" id="m-prenom" name="prenom_apprenant" placeholder="Brice" />
              </div>
              <div>
                <div class="lbl">Nom *</div>
                <input class="inp" id="m-nom" name="nom_apprenant" placeholder="FONO" />
              </div>
            </div>
            @endif

            <div class="g2">
              <div>
                <div class="lbl">{{ Auth::user()->profil === 'etudiant' ? 'Filière / Niveau *' : 'Classe *' }}</div>
                <input class="inp" id="m-classe" name="classe"
                       placeholder="{{ Auth::user()->profil === 'etudiant' ? 'Ex : Licence 2 GSI' : 'Ex : 3ème A' }}" />
              </div>
              <div>
                <div class="lbl">Matricule</div>
                <input class="inp" id="m-mat-display" name="matricule_display" placeholder="EP-1184" readonly
                       style="background:#f5f5f5;" />
              </div>
            </div>
          </div>

        </form>

      </div>

    </div>
    <div class="ep-modal-foot">
      <button type="button" class="btn-o" style="width:auto;padding:8px 16px;"
              onclick="epModal.close('modal-rattacher')">Annuler</button>
      <button type="button" class="btn-p" style="width:auto;padding:8px 20px;" id="m-btn-submit"
              onclick="mSoumettre()">
        {{ in_array(Auth::user()->profil, ['eleve','etudiant']) ? 'Me rattacher →' : 'Rattacher →' }}
      </button>
    </div>
  </div>
</div>

{{-- ══ MODAL : Modifier mon dossier apprenant ══ --}}
<div id="modal-modifier-apprenant" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-lg">
    <div class="ep-modal-head">
      <h3>Modifier mon dossier</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-modifier-apprenant')">×</button>
    </div>
    @if($monDossier)
    <form method="POST" action="{{ route('payeur.apprenant.update', $monDossier) }}">
      @csrf @method('PUT')
      <div class="ep-modal-body">
        <div class="g2">
          <div>
            <div class="lbl">Prénom</div>
            <input class="inp" name="prenom" value="{{ $monDossier->prenom }}" required />
          </div>
          <div>
            <div class="lbl">Nom</div>
            <input class="inp" name="nom" value="{{ $monDossier->nom }}" required />
          </div>
        </div>
        <div class="lbl">Classe / Niveau</div>
        <input class="inp" name="classe" value="{{ $monDossier->classe }}" required />
        <div class="lbl">Matricule</div>
        <input class="inp" name="matricule" value="{{ $monDossier->matricule }}" placeholder="EP-XXXX" />
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;"
                onclick="epModal.close('modal-modifier-apprenant')">Annuler</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">
          Enregistrer →
        </button>
      </div>
    </form>
    @endif
  </div>
</div>

@endpush

@section('content')

@if($estSolo)
{{-- ════════════════════════════════════════════════════════════
     VUE SOLO — Élève / Étudiant
     F03 Tableau de bord + F05 Frais ventilés par catégorie
     ════════════════════════════════════════════════════════════ --}}

  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
    <div>
      <div style="font-size:18px;font-weight:700;">
        Bonjour, {{ Auth::user()->prenom ?? Str::of(Auth::user()->name)->explode(' ')->first() }}
      </div>
      <div style="font-size:13px;color:#888;">
        @if($estSolo && $monDossier && $monDossier->frais->isEmpty())
          Aucun frais enregistré pour le moment
        @else
          {{ $nbEnfantsDus > 0 ? $nbEnfantsDus . ' paiement(s) en attente' : 'Tout est à jour ✓' }}
        @endif
        @if($monDossier && $monDossier->etablissement) · {{ $monDossier->etablissement->nom }} @endif
      </div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <button onclick="epModal.open('modal-rattacher')"
              class="btn-o" style="width:auto;padding:9px 16px;font-size:12px;">
        + Me rattacher
      </button>
      @if($premierFraisImpayeSolo)
        <a href="{{ route('payeur.paiement.show', $premierFraisImpayeSolo) }}" class="btn-p" style="width:auto;">
          Payer maintenant
        </a>
      @endif
    </div>
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

  @if(!$monDossier)
    {{-- Pas encore rattaché --}}
    <div class="epcard" style="text-align:center;color:#999;padding:40px 0;margin-bottom:18px;">
      <div style="font-size:32px;margin-bottom:12px;">🏫</div>
      <div style="font-size:14px;font-weight:600;margin-bottom:6px;">Vous n'êtes pas encore rattaché à un établissement.</div>
      <div style="font-size:12px;color:#aaa;margin-bottom:16px;">Recherchez votre établissement pour consulter vos frais.</div>
      <button onclick="epModal.open('modal-rattacher')" class="btn-p" style="width:auto;">
        Me rattacher maintenant
      </button>
    </div>

  @else
    {{-- ── Dossier scolaire ── --}}
    <div class="seclbl" style="margin-top:0;">Mon dossier scolaire</div>

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
          {{ match($statutSolo) { 'regle' => 'À jour', 'partiel' => 'Partiel', 'impaye' => 'Impayé', 'aucun' => 'Aucun frais', default => $statutSolo } }}
        </span>
      </div>
      <div class="prog" style="margin-bottom:4px;">
        <div class="pfill" style="width:{{ $pctSolo }}%;"></div>
      </div>
      <div style="font-size:10px;color:#888;margin-bottom:14px;">
        {{ $pctSolo }}% réglé — {{ number_format($payeSolo,0,',',' ') }} / {{ number_format($totalSolo,0,',',' ') }} FCFA
      </div>
      <div style="display:flex;gap:8px;">
        <a href="{{ route('payeur.frais.apprenant', $monDossier) }}" class="btn-o" style="font-size:12px;padding:8px 14px;width:auto;">
          Voir tous mes frais →
        </a>
        <button type="button" onclick="epModal.open('modal-modifier-apprenant')"
                class="btn-o" style="width:auto;font-size:12px;padding:8px 14px;">
          ✎ Modifier
        </button>
      </div>

    {{-- ── F05 : Frais ventilés par catégorie ── --}}
    <div class="seclbl">Mes frais par catégorie</div>

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
              {{ $resteF > 0 ? number_format($resteF,0,',',' ').' FCFA restant' : '✓ Réglé' }}
            </div>
            <div style="font-size:11px;color:#aaa;">Total : {{ number_format($frais->montant_total,0,',',' ') }} FCFA</div>
          </div>
        </div>
        <div class="prog" style="margin-bottom:4px;">
          <div class="pfill" style="width:{{ $pctF }}%;background:{{ $frais->statut === 'impaye' ? 'var(--ep-red)' : 'var(--ep-teal)' }};"></div>
        </div>
        <div style="font-size:10px;color:#888;margin-bottom:10px;">{{ $pctF }}% réglé</div>
        @if($resteF > 0)
          <a href="{{ route('payeur.paiement.show', $frais) }}" class="btn-p"
             style="display:block;text-align:center;padding:8px;font-size:12px;">
            Payer {{ number_format($resteF,0,',',' ') }} FCFA →
          </a>
        @endif
      </div>
    @empty
      <div class="epcard" style="text-align:center;color:#aaa;padding:24px 0;font-size:13px;">
        Aucun frais enregistré pour le moment.
      </div>
    @endforelse

  @endif

  {{-- ── Derniers paiements (solo) ── --}}
  <div class="seclbl" style="margin-top:8px;">Derniers paiements</div>
  <div class="epcard">
    @forelse ($derniersPaiements ?? [] as $paiement)
      <div class="row">
        <div>
          <div style="font-size:13px;font-weight:600;">
            {{ $paiement->fraisApprenant->categorieFrais->nom ?? 'Paiement' }}
          </div>
          <div style="font-size:11px;color:#888;">
            {{ $paiement->date_paiement ? \Carbon\Carbon::parse($paiement->date_paiement)->format('d M Y') : '—' }}
            · {{ match($paiement->mode_paiement) { 'mtn_momo' => 'MTN MoMo', 'orange_money' => 'Orange Money', default => $paiement->mode_paiement } }}
          </div>
        </div>
        <div style="text-align:right;">
          <div style="font-weight:600;color:{{ $paiement->statut === 'valide' ? 'var(--ep-teal)' : 'var(--ep-gold)' }};">
            {{ number_format($paiement->montant,0,',',' ') }} FCFA
          </div>
          <span class="pill {{ match($paiement->statut) { 'valide' => 'pg', 'en_attente' => 'pa', 'echoue' => 'pr', default => 'pa' } }}">
            {{ match($paiement->statut) { 'valide' => 'Validé', 'en_attente' => 'En attente', 'echoue' => 'Échoué', default => $paiement->statut } }}
          </span>
        </div>
      </div>
    @empty
      <div style="text-align:center;color:#999;font-size:13px;padding:20px 0;">Aucun paiement pour le moment.</div>
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
{{-- ════════════════════════════════════════════════════════════
     VUE FAMILLE — Parent
     F03 Tableau de bord + F13 Multi-enfants
     ════════════════════════════════════════════════════════════ --}}

  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
    <div>
      <div style="font-size:18px;font-weight:700;">
        Bonjour, {{ Auth::user()->prenom ?? Str::of(Auth::user()->name)->explode(' ')->first() }}
      </div>
      <div style="font-size:13px;color:#888;">
        @if($totalDu <= 0)
          Aucun frais enregistré pour le moment
        @else
          {{ $nbEnfantsDus > 0 ? $nbEnfantsDus . ' paiement(s) en attente' : 'Tout est à jour ✓' }}
        @endif
        @if(Auth::user()->ville) · {{ Auth::user()->ville }} @endif
      </div>
    </div>
    <div style="display:flex;gap:8px;">
      <button onclick="epModal.open('modal-rattacher')" class="btn-o" style="width:auto;padding:9px 16px;font-size:12px;">
        + Rattacher un enfant
      </button>
      @if($premierFraisImpaye)
        <a href="{{ route('payeur.paiement.show', $premierFraisImpaye) }}" class="btn-p" style="width:auto;">
          Payer maintenant
        </a>
      @endif
    </div>
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

  {{-- ── F13 : Mes enfants (multi-enfants) ── --}}
  <div id="mes-enfants" class="seclbl" style="margin-top:0;">Mes enfants</div>

  @if($apprenants->isEmpty())
    <div class="epcard" style="text-align:center;color:#999;padding:40px 0;margin-bottom:18px;">
      <div style="font-size:32px;margin-bottom:12px;">👨‍👧‍👦</div>
      <div style="font-size:14px;font-weight:600;margin-bottom:6px;">Aucun enfant rattaché à votre compte.</div>
      <div style="font-size:12px;color:#aaa;margin-bottom:16px;">Rattachez votre premier enfant pour suivre ses frais scolaires.</div>
      <button onclick="epModal.open('modal-rattacher')" class="btn-p" style="width:auto;">
        Rattacher un enfant
      </button>
    </div>
  @else
    <div class="g2" style="margin-bottom:18px;">
      @foreach($apprenants as $apprenant)
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
              {{ match($statutA) { 'regle' => 'Réglé', 'partiel' => 'Partiel', 'impaye' => 'Impayé', default => $statutA } }}
            </span>
          </div>

          {{-- F05 : Frais ventilés par catégorie (résumé) --}}
          @forelse($apprenant->frais as $frais)
            @php $resteF = $frais->montant_total - $frais->montant_paye; @endphp
            <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 0;border-bottom:1px solid #f5f5f5;">
              <span style="color:#666;">{{ $frais->categorieFrais->nom ?? 'Frais' }}</span>
              <span style="font-weight:600;color:{{ $resteF > 0 ? 'var(--ep-red)' : 'var(--ep-teal)' }};">
                {{ $resteF > 0 ? number_format($resteF,0,',',' ').' FCFA' : '✓ Réglé' }}
              </span>
            </div>
          @empty
            <div style="font-size:12px;color:#aaa;padding:4px 0;">Aucun frais enregistré.</div>
          @endforelse

          @if($resteA > 0)
            <div class="prog" style="margin-top:10px;margin-bottom:4px;">
              <div class="pfill" style="width:{{ $pctA }}%;"></div>
            </div>
            <div style="font-size:10px;color:#888;margin-bottom:10px;">{{ $pctA }}% réglé</div>
          @else
            <div style="font-size:12px;color:var(--ep-teal);font-weight:600;margin-top:10px;margin-bottom:10px;">
              ✓ Tous les frais sont réglés
            </div>
          @endif

          <div style="display:flex;gap:6px;">
            @if($premierImpayeA)
              <a href="{{ route('payeur.paiement.show', $premierImpayeA) }}"
                 class="{{ $statutA === 'impaye' ? 'btn-r' : 'btn-p' }}"
                 style="flex:1;text-align:center;padding:8px;font-size:12px;display:block;">
                {{ $statutA === 'impaye' ? 'Payer →' : 'Continuer →' }}
              </a>
            @endif
            <a href="{{ route('payeur.frais.apprenant', $apprenant) }}"
               class="btn-o" style="flex:1;text-align:center;padding:8px;font-size:12px;">
              Détail →
            </a>
          </div>
        </div>
      @endforeach
    </div>
  @endif

  {{-- Derniers paiements (multi-enfants) --}}
  @if(($derniersPaiements ?? collect())->isNotEmpty())
  <div class="seclbl" style="margin-top:18px;">Derniers paiements</div>
  <div class="epcard">
    @foreach($derniersPaiements as $paiement)
    <div class="row">
      <div>
        <div style="font-size:13px;font-weight:600;">
          {{ $paiement->fraisApprenant->categorieFrais->nom ?? 'Paiement' }}
          @if($paiement->apprenant) — {{ $paiement->apprenant->prenom }} @endif
        </div>
        <div style="font-size:11px;color:#888;">
          {{ $paiement->date_paiement ? \Carbon\Carbon::parse($paiement->date_paiement)->format('d M Y') : '—' }}
          · {{ match($paiement->mode_paiement) { 'mtn_momo' => 'MTN MoMo', 'orange_money' => 'Orange Money', default => $paiement->mode_paiement } }}
        </div>
      </div>
      <div style="text-align:right;">
        <div style="font-weight:600;color:{{ $paiement->statut === 'valide' ? 'var(--ep-teal)' : ($paiement->statut === 'rembourse' ? 'var(--ep-red)' : 'var(--ep-gold)') }};">
          {{ $paiement->statut === 'rembourse' ? '– ' : '' }}{{ number_format($paiement->montant,0,',',' ') }} FCFA
        </div>
        <span class="pill {{ match($paiement->statut) { 'valide' => 'pg', 'en_attente' => 'pa', 'echoue' => 'pr', 'rembourse' => 'pb', default => 'pa' } }}">
          {{ match($paiement->statut) { 'valide' => 'Validé', 'en_attente' => 'En attente', 'echoue' => 'Échoué', 'rembourse' => 'Remboursé', default => $paiement->statut } }}
        </span>
      </div>
    </div>
    @endforeach
  </div>
  <div style="text-align:center;margin-top:14px;">
    <a href="{{ route('payeur.historique') }}" style="color:var(--ep-teal);text-decoration:none;font-size:13px;font-weight:500;">Voir tout l'historique →</a>
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
<script>
// ── Modal rattachement ──
function mFiltrerEtabs() {
    var nom   = (document.getElementById('m-etab-search').value || '').toLowerCase().trim();
    var ville = (document.getElementById('m-etab-ville').value  || '').toLowerCase().trim();
    var code  = (document.getElementById('m-etab-code').value   || '').toLowerCase().trim();
    document.querySelectorAll('.m-etab-item').forEach(function(item) {
        var iNom   = (item.dataset.nom   || '').toLowerCase();
        var iVille = (item.dataset.ville || '').toLowerCase();
        var iCode  = (item.dataset.code  || '').toLowerCase();
        var show = (!nom   || iNom.includes(nom))
                && (!ville || iVille.includes(ville))
                && (!code  || iCode.includes(code));
        item.style.display = show ? 'flex' : 'none';
    });
}

function mSelectionnerEtab(el) {
    // Highlight sélection
    document.querySelectorAll('.m-etab-item').forEach(function(i) {
        i.style.background = '';
        i.querySelector('.m-etab-check').style.opacity = '0';
    });
    el.style.background = '#f0fdf4';
    el.querySelector('.m-etab-check').style.opacity = '1';

    // Stocker les valeurs
    document.getElementById('m-h-etab-id').value  = el.dataset.id;
    document.getElementById('m-h-etab-nom').value = el.dataset.nom;

    // Mettre à jour le badge step2
    var badgeNom   = document.getElementById('m-etab-badge-nom');
    var badgeVille = document.getElementById('m-etab-badge-ville');
    if (badgeNom)   badgeNom.textContent   = el.dataset.nom;
    if (badgeVille) badgeVille.textContent = (el.dataset.ville || '') + (el.dataset.type ? ' · ' + el.dataset.type : '');

    // Passer à l'étape 2
    document.getElementById('m-step1').style.display = 'none';
    document.getElementById('m-step2').style.display = 'block';

    // Révéler la section annuaire (recherche apprenant) — masquée par défaut
    var sectionAnnuaire = document.getElementById('m-section-annuaire');
    if (sectionAnnuaire) sectionAnnuaire.style.display = 'block';

    // Réinitialiser l'annuaire apprenant
    mReinitApprenant();

    // Charger immédiatement la liste complète de l'établissement (annuaire consultable)
    mRechercherApprenant('');

    // Focus sur la recherche apprenant
    setTimeout(function() {
        var inp = document.getElementById('m-apprenant-search');
        if (inp) inp.focus();
    }, 150);
}

function mReinitEtab() {
    document.getElementById('m-h-etab-id').value  = '';
    document.getElementById('m-h-etab-nom').value = '';
    // Réinitialiser les filtres
    ['m-etab-search','m-etab-ville','m-etab-code'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.value = '';
    });
    // Réafficher tous les items
    document.querySelectorAll('.m-etab-item').forEach(function(i) {
        i.style.background = '';
        i.querySelector('.m-etab-check').style.opacity = '0';
        i.style.display = 'flex';
    });
    // Revenir à l'étape 1
    document.getElementById('m-step1').style.display = 'block';
    document.getElementById('m-step2').style.display = 'none';
    var sectionAnnuaire2 = document.getElementById('m-section-annuaire');
    if (sectionAnnuaire2) sectionAnnuaire2.style.display = 'none';
    mReinitApprenant();
}

function mSoumettre() {
    var etabId = document.getElementById('m-h-etab-id').value;
    var etabNom = document.getElementById('m-h-etab-nom').value;

    // Vérifier établissement
    if (!etabId && !etabNom) {
        document.getElementById('m-etab-search').style.border = '1.5px solid var(--ep-red)';
        document.getElementById('m-etab-search').focus();
        alert('Veuillez sélectionner un établissement.');
        return;
    }

    // Si apprenant trouvé dans annuaire → passer son ID
    var appId = document.getElementById('m-h-apprenant-id').value;
    if (appId) {
        // Rattachement direct via apprenant_id existant
        document.getElementById('m-h-matricule').value = mApprenantSelectionne
            ? mApprenantSelectionne.matricule : '';
    }

    // Vérifier classe obligatoire
    var classeInp = document.querySelector('#m-onb-form [name="classe"]');
    if (classeInp && !classeInp.value.trim()) {
        classeInp.style.border = '1.5px solid var(--ep-red)';
        classeInp.focus();
        alert('Veuillez indiquer la classe.');
        return;
    }

    document.getElementById('m-onb-form').submit();
}

document.addEventListener('click', function(e) {
    var liste  = document.getElementById('m-etab-liste');
    var search = document.getElementById('m-etab-search');
    var villeInp = document.getElementById('m-etab-ville');
    var codeInp  = document.getElementById('m-etab-code');
    if (!liste) return;
    // Ne pas masquer si le clic vient d'un des filtres ou de la liste elle-même
    var cibleFiltre = (search && search.contains(e.target))
                   || (villeInp && villeInp.contains(e.target))
                   || (codeInp && codeInp.contains(e.target))
                   || liste.contains(e.target);
    if (!cibleFiltre) {
        liste.style.display = 'none';
    }
});

// ── Annuaire apprenants (F04) ──
var mAnnuaireTimeout = null;
var mApprenantSelectionne = null;

function mRechercherApprenant(q) {
    var etabId = document.getElementById('m-h-etab-id').value;
    if (!etabId) {
        document.getElementById('m-etab-search').style.border = '1.5px solid var(--ep-red)';
        return;
    }

    clearTimeout(mAnnuaireTimeout);
    var liste = document.getElementById('m-apprenant-liste');
    liste.innerHTML = '<div style="padding:12px;text-align:center;color:#888;font-size:12px;">Chargement de l\'annuaire…</div>';
    liste.style.display = 'block';

    mAnnuaireTimeout = setTimeout(function() {
        var url = '{{ route("payeur.onboarding.search") }}?etablissement_id=' + etabId + '&q=' + encodeURIComponent(q);
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(apprenants) {
            if (apprenants.length === 0) {
                var msgVide = q.trim()
                    ? 'Aucun résultat pour "<strong>' + q + '</strong>"'
                    : 'Aucun apprenant enregistré dans cet établissement pour le moment.';
                liste.innerHTML =
                    '<div style="padding:14px;text-align:center;">' +
                    '<div style="font-size:13px;color:#888;margin-bottom:8px;">' + msgVide + '</div>' +
                    '<div style="font-size:11px;color:#aaa;">Remplissez les champs ci-dessous pour un pré-rattachement.</div>' +
                    '</div>';
                mAfficherSaisieManuelle(true);
                return;
            }

            var html = '';
            apprenants.forEach(function(a) {
                html += '<div class="m-app-item" data-id="' + a.id + '" data-nom="' + a.nom + '" ' +
                    'data-prenom="' + a.prenom + '" data-classe="' + (a.classe||'') + '" ' +
                    'data-matricule="' + (a.matricule||'') + '" ' +
                    'onclick="mSelectionnerApprenant(this)" ' +
                    'style="padding:10px 14px;cursor:pointer;border-bottom:1px solid #f5f5f5;' +
                    'display:flex;align-items:center;justify-content:space-between;">' +
                    '<div>' +
                    '<div style="font-size:13px;font-weight:600;">' + a.prenom + ' ' + a.nom + '</div>' +
                    '<div style="font-size:11px;color:#888;">' + (a.classe||'—') +
                    (a.matricule ? ' · Mat. ' + a.matricule : '') + '</div>' +
                    '</div>' +
                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2" ' +
                    'class="m-app-check" style="opacity:0;flex-shrink:0;"><polyline points="20 6 9 17 4 12"/></svg>' +
                    '</div>';
            });
            liste.innerHTML = html;
            mAfficherSaisieManuelle(false);
        })
        .catch(function() {
            liste.innerHTML = '<div style="padding:12px;color:var(--ep-red);font-size:12px;">Erreur de connexion — vérifiez votre réseau.</div>';
        });
    }, 350);
}

function mSelectionnerApprenant(el) {
    // Reset visuels
    document.querySelectorAll('.m-app-item').forEach(function(i) {
        i.style.background = '';
        var chk = i.querySelector('.m-app-check');
        if (chk) chk.style.opacity = '0';
    });

    el.style.background = '#f0fdf4';
    var chk = el.querySelector('.m-app-check');
    if (chk) chk.style.opacity = '1';

    mApprenantSelectionne = {
        id:        el.dataset.id,
        nom:       el.dataset.nom,
        prenom:    el.dataset.prenom,
        classe:    el.dataset.classe,
        matricule: el.dataset.matricule
    };

    // Remplir les champs cachés
    document.getElementById('m-h-apprenant-id').value = el.dataset.id;

    // Remplir les champs visibles (pré-remplis, modifiables)
    var inp = {
        'prenom_apprenant': el.dataset.prenom,
        'nom_apprenant':    el.dataset.nom,
        'classe':           el.dataset.classe,
        'matricule':        el.dataset.matricule
    };
    Object.keys(inp).forEach(function(name) {
        var field = document.querySelector('#m-onb-form [name="' + name + '"]');
        if (field) field.value = inp[name] || '';
    });

    // Afficher badge de confirmation
    var badge = document.getElementById('m-app-badge');
    if (badge) {
        badge.style.display = 'flex';
        var badgeNom = document.getElementById('m-app-badge-nom');
        var badgeInfo = document.getElementById('m-app-badge-info');
        if (badgeNom)  badgeNom.textContent  = el.dataset.prenom + ' ' + el.dataset.nom;
        if (badgeInfo) badgeInfo.textContent = (el.dataset.classe||'') + (el.dataset.matricule ? ' · ' + el.dataset.matricule : '');
    }

    // Masquer la liste, afficher saisie en lecture
    document.getElementById('m-apprenant-liste').style.display = 'none';
    mAfficherSaisieManuelle(true);
}

function mAfficherSaisieManuelle(show) {
    var bloc = document.getElementById('m-saisie-manuelle');
    if (bloc) bloc.style.display = show ? 'block' : 'none';
}

function mReinitApprenant() {
    mApprenantSelectionne = null;
    document.getElementById('m-h-apprenant-id').value = '';
    var badge = document.getElementById('m-app-badge');
    if (badge) badge.style.display = 'none';
    var liste = document.getElementById('m-apprenant-liste');
    if (liste) {
        liste.innerHTML = '<div style="padding:12px;text-align:center;color:#aaa;font-size:12px;">Tapez un nom pour rechercher dans l\'annuaire…</div>';
        liste.style.display = 'block';
    }
    var searchInp = document.getElementById('m-apprenant-search');
    if (searchInp) { searchInp.value = ''; }
    mAfficherSaisieManuelle(false);
}

</script>
@endpush
