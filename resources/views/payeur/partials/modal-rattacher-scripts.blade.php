<script>
var PAYEUR_L10N = {
    select_etab: {!! json_encode(__('payeur.alert_select_etab')) !!},
    select_classe: {!! json_encode(__('payeur.alert_select_classe')) !!},
    chargement_annuaire: {!! json_encode(__('payeur.l10n_chargement_annuaire')) !!},
    aucun_resultat: {!! json_encode(__('payeur.l10n_aucun_resultat')) !!},
    aucun_apprenant: {!! json_encode(__('payeur.l10n_aucun_apprenant')) !!},
    remplir_champs: {!! json_encode(__('payeur.l10n_remplir_champs')) !!},
    erreur_connexion: {!! json_encode(__('payeur.l10n_erreur_connexion')) !!},
    tapez_nom: {!! json_encode(__('payeur.l10n_tapez_nom')) !!}
};
function mEscapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

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
    // Réafficher la liste des établissements (le clic global peut l'avoir masquée)
    var listeEtabs = document.getElementById('m-etab-liste');
    if (listeEtabs) listeEtabs.style.display = 'block';
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
        alert(PAYEUR_L10N.select_etab);
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
        alert(PAYEUR_L10N.select_classe);
        return;
    }

    document.getElementById('m-onb-form').submit();
}

document.addEventListener('click', function(e) {
    var liste  = document.getElementById('m-etab-liste');
    var search = document.getElementById('m-etab-search');
    var villeInp = document.getElementById('m-etab-ville');
    var codeInp  = document.getElementById('m-etab-code');
    var changerBtn = document.getElementById('m-btn-changer-etab');
    if (!liste) return;
    // Ne pas masquer si le clic vient d'un des filtres, de la liste, ou du bouton « Changer d'établissement »
    var cibleFiltre = (search    && search.contains(e.target))
                   || (villeInp  && villeInp.contains(e.target))
                   || (codeInp   && codeInp.contains(e.target))
                   || (changerBtn && changerBtn.contains(e.target))
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
    liste.innerHTML = '<div style="padding:12px;text-align:center;color:#888;font-size:12px;">' + PAYEUR_L10N.chargement_annuaire + '</div>';
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
                    ? PAYEUR_L10N.aucun_resultat.replace('%%Q%%', mEscapeHtml(q))
                    : PAYEUR_L10N.aucun_apprenant;
                liste.innerHTML =
                    '<div style="padding:14px;text-align:center;">' +
                    '<div style="font-size:13px;color:#888;margin-bottom:8px;">' + msgVide + '</div>' +
                    '<div style="font-size:11px;color:#aaa;">' + PAYEUR_L10N.remplir_champs + '</div>' +
                    '</div>';
                mAfficherSaisieManuelle(true);
                return;
            }

            var html = '';
            apprenants.forEach(function(a) {
                var safeId        = mEscapeHtml(a.id);
                var safeNom       = mEscapeHtml(a.nom);
                var safePrenom    = mEscapeHtml(a.prenom);
                var safeClasse    = mEscapeHtml(a.classe || '');
                var safeMatricule = mEscapeHtml(a.matricule || '');
                html += '<div class="m-app-item" data-id="' + safeId + '" data-nom="' + safeNom + '" ' +
                    'data-prenom="' + safePrenom + '" data-classe="' + safeClasse + '" ' +
                    'data-matricule="' + safeMatricule + '" ' +
                    'onclick="mSelectionnerApprenant(this)" ' +
                    'style="padding:10px 14px;cursor:pointer;border-bottom:1px solid #f5f5f5;' +
                    'display:flex;align-items:center;justify-content:space-between;">' +
                    '<div>' +
                    '<div style="font-size:13px;font-weight:600;">' + safePrenom + ' ' + safeNom + '</div>' +
                    '<div style="font-size:11px;color:#888;">' + (safeClasse||'—') +
                    (safeMatricule ? ' · Mat. ' + safeMatricule : '') + '</div>' +
                    '</div>' +
                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2" ' +
                    'class="m-app-check" style="opacity:0;flex-shrink:0;"><polyline points="20 6 9 17 4 12"/></svg>' +
                    '</div>';
            });
            liste.innerHTML = html;
            mAfficherSaisieManuelle(false);
        })
        .catch(function() {
            liste.innerHTML = '<div style="padding:12px;color:var(--ep-red);font-size:12px;">' + PAYEUR_L10N.erreur_connexion + '</div>';
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
        liste.innerHTML = '<div style="padding:12px;text-align:center;color:#aaa;font-size:12px;">' + PAYEUR_L10N.tapez_nom + '</div>';
        liste.style.display = 'block';
    }
    var searchInp = document.getElementById('m-apprenant-search');
    if (searchInp) { searchInp.value = ''; }
    mAfficherSaisieManuelle(false);
}

</script>
