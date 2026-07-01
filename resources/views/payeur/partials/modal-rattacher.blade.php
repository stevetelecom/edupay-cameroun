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

            <div class="g2" style="margin-bottom:10px;">
              <div>
                <div class="lbl">Prénom {{ in_array(Auth::user()->profil, ['eleve','etudiant']) ? '' : "de l'enfant" }} *</div>
                <input class="inp" id="m-prenom" name="prenom_apprenant"
                       value="{{ in_array(Auth::user()->profil, ['eleve','etudiant']) ? Auth::user()->prenom : '' }}"
                       placeholder="Brice" />
              </div>
              <div>
                <div class="lbl">Nom *</div>
                <input class="inp" id="m-nom" name="nom_apprenant"
                       value="{{ in_array(Auth::user()->profil, ['eleve','etudiant']) ? Auth::user()->nom : '' }}"
                       placeholder="FONO" />
              </div>
            </div>

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
