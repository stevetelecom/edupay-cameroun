@extends('layouts.public')
@section('title', 'Rattachement — EduPay Cameroun')

@section('content')
<div class="video-bg-container" style="min-height:100vh;display:flex;flex-direction:column;"><video class="video-bg" autoplay muted loop playsinline><source src="{{ asset('videos/hero-payment.mp4') }}" type="video/mp4"></video><div class="video-bg-overlay"></div>

  <div class="form-header">
    <div style="display:flex;align-items:center;gap:9px;"><span style="width:52px;height:52px;border-radius:14px;background:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;box-shadow:0 3px 12px rgba(0,0,0,.2);"><img src="{{ asset('images/logo.jpeg') }}" alt="EduPay Cameroun" style="width:100%;height:100%;object-fit:cover;" /></span></div>
    <div style="font-size:12px;color:rgba(255,255,255,.5);">Étape 2 sur 3</div>
  </div>

  <div class="form-body" style="padding-top:28px;padding-bottom:40px;">
    <div class="form-card-wide">

      {{-- Barre étapes --}}
      <div style="display:flex;align-items:center;margin-bottom:24px;">
        <div style="flex:1;text-align:center;">
          <div style="width:30px;height:30px;border-radius:50%;background:var(--ep-teal);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;margin:0 auto 5px;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <div style="font-size:11px;font-weight:600;color:var(--ep-teal);">Compte</div>
        </div>
        <div style="flex:1;height:2px;background:var(--ep-teal);margin-top:-16px;"></div>
        <div style="flex:1;text-align:center;">
          <div style="width:30px;height:30px;border-radius:50%;background:var(--ep-teal);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;margin:0 auto 5px;">2</div>
          <div style="font-size:11px;font-weight:600;color:var(--ep-teal);">
            {{ in_array(Auth::user()->profil, ['eleve','etudiant']) ? 'Mon école' : 'Mon enfant' }}
          </div>
        </div>
        <div style="flex:1;height:2px;background:#e0e0e0;margin-top:-16px;"></div>
        <div style="flex:1;text-align:center;">
          <div style="width:30px;height:30px;border-radius:50%;border:2px solid #ddd;color:#ccc;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;margin:0 auto 5px;">3</div>
          <div style="font-size:11px;color:#aaa;">Confirmation</div>
        </div>
      </div>

      @if($errors->any())
      <div style="background:#FEE2E2;border:1px solid #FCA5A5;border-radius:8px;padding:12px 16px;margin-bottom:18px;">
        <div style="font-size:13px;font-weight:600;color:#991B1B;margin-bottom:6px;">Veuillez corriger les erreurs :</div>
        <ul style="margin:0;padding-left:18px;">
          @foreach($errors->all() as $e)<li style="font-size:12px;color:#B91C1C;">{{ $e }}</li>@endforeach
        </ul>
      </div>
      @endif

      @php $estSolo = in_array(Auth::user()->profil, ['eleve','etudiant']); @endphp

      <div style="font-size:18px;font-weight:700;margin-bottom:6px;">
        {{ $estSolo ? 'Rattachez votre profil à votre établissement' : 'Rattachez votre enfant à son établissement' }}
      </div>
      <div style="font-size:13px;color:#888;margin-bottom:20px;">
        {{ $estSolo
            ? 'Recherchez votre établissement partenaire EduPay, puis retrouvez votre profil dans l\'annuaire.'
            : 'Recherchez l\'établissement de votre enfant, puis retrouvez-le dans l\'annuaire. Vous pourrez en ajouter d\'autres depuis votre tableau de bord.' }}
      </div>

      {{-- ══ ÉTAPE A : Recherche établissement ══ --}}
      <div id="o-step1">
        <div style="font-size:11px;font-weight:700;color:#0D9E75;text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;">
          Étape 1 — Choisir l'établissement
        </div>

        <div style="position:relative;margin-bottom:10px;">
          <input type="text" id="o-etab-search"
                 placeholder="Tapez le nom ou la ville…"
                 style="width:100%;padding:11px 12px 11px 36px;border:1px solid #ddd;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                 oninput="oFiltrerEtabs(this.value)"
                 onfocus="document.getElementById('o-etab-liste').style.display='block'" />
          <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#aaa;"
               width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </div>

        <div id="o-etab-liste"
             style="border:1px solid #e0e0e0;border-radius:8px;background:#fff;
                    max-height:260px;overflow-y:auto;box-shadow:0 4px 16px rgba(0,0,0,.1);">
          @foreach($etablissements as $etab)
          <div class="o-etab-item"
               data-id="{{ $etab->id }}"
               data-nom="{{ $etab->nom }}"
               data-ville="{{ $etab->ville }}"
               data-type="{{ $etab->type }}"
               onclick="oSelectionnerEtab(this)"
               style="padding:10px 14px;cursor:pointer;border-bottom:1px solid #f5f5f5;display:flex;align-items:center;gap:10px;">
            @if($etab->logo)
              <img src="{{ asset('storage/'.$etab->logo) }}" alt="{{ $etab->nom }}"
                   style="width:36px;height:36px;border-radius:8px;object-fit:cover;flex-shrink:0;border:1px solid #eee;" />
            @else
              <div style="width:36px;height:36px;border-radius:8px;background:var(--ep-teal-lt);
                          display:flex;align-items:center;justify-content:center;
                          font-size:14px;font-weight:700;color:var(--ep-teal);flex-shrink:0;">
                {{ strtoupper(substr($etab->nom, 0, 1)) }}
              </div>
            @endif
            <div style="flex:1;min-width:0;">
              <div style="font-size:13px;font-weight:600;">{{ $etab->nom }}</div>
              <div style="font-size:11px;color:#888;">{{ $etab->ville }} · {{ ucfirst($etab->type) }}</div>
            </div>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2"
                 style="flex-shrink:0;opacity:0;" class="o-etab-check">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
          </div>
          @endforeach
          @if($etablissements->isEmpty())
          <div style="padding:20px;text-align:center;color:#aaa;font-size:13px;">
            Aucun établissement partenaire pour le moment.
          </div>
          @endif
        </div>
      </div>

      {{-- ══ ÉTAPE B : Annuaire apprenants (après sélection établissement) ══ --}}
      <div id="o-step2" style="display:none;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
          <div style="font-size:11px;font-weight:700;color:#0D9E75;text-transform:uppercase;letter-spacing:.05em;">
            Étape 2 — Trouver dans l'annuaire
          </div>
          <button type="button" onclick="oReinitEtab()"
                  style="font-size:11px;color:#888;background:none;border:none;cursor:pointer;text-decoration:underline;">
            Changer d'établissement
          </button>
        </div>

        <div style="background:var(--ep-teal-lt);border-radius:8px;padding:10px 14px;margin-bottom:14px;display:flex;align-items:center;gap:10px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2"><rect x="2" y="7" width="20" height="15"/><polyline points="16 2 12 7 8 2"/></svg>
          <div>
            <div style="font-size:13px;font-weight:600;color:#085041;" id="o-etab-badge-nom"></div>
            <div style="font-size:11px;color:#1B9E75;" id="o-etab-badge-ville"></div>
          </div>
        </div>

        <div style="position:relative;margin-bottom:10px;">
          <input type="text" id="o-apprenant-search"
                 placeholder="Chercher par nom, prénom ou classe…"
                 style="width:100%;padding:11px 12px 11px 36px;border:1px solid #ddd;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                 oninput="oRechercherApprenant(this.value)" />
          <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#aaa;" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </div>

        <div id="o-apprenant-liste"
             style="border:1px solid #e0e0e0;border-radius:8px;background:#fff;max-height:200px;overflow-y:auto;margin-bottom:16px;">
          <div style="padding:16px;text-align:center;color:#aaa;font-size:13px;">
            Chargement de l'annuaire…
          </div>
        </div>

        {{-- Formulaire de soumission --}}
        <form method="POST" action="{{ route('payeur.onboarding.store') }}" id="onb-form">
          @csrf
          <input type="hidden" name="lien" value="{{ $estSolo ? 'soi-meme' : 'parent' }}" />
          <input type="hidden" name="etablissement_id"  id="h-etab-id"  value="{{ old('etablissement_id') }}" />
          <input type="hidden" name="etablissement_nom" id="h-etab-nom" value="{{ old('etablissement_nom') }}" />
          <input type="hidden" name="apprenant_id"      id="h-apprenant-id" value="" />

          {{-- Badge apprenant sélectionné --}}
          <div id="o-app-badge"
               style="display:none;background:var(--ep-teal-lt);border-radius:8px;
                      padding:10px 14px;margin-bottom:12px;
                      align-items:center;justify-content:space-between;">
            <div>
              <div style="font-size:13px;font-weight:600;color:#085041;" id="o-app-badge-nom"></div>
              <div style="font-size:11px;color:#1B9E75;" id="o-app-badge-info"></div>
            </div>
            <button type="button" onclick="oReinitApprenant()"
                    style="background:none;border:none;color:#888;cursor:pointer;font-size:18px;line-height:1;">×</button>
          </div>

          {{-- Champs manuels (si pas trouvé dans annuaire) --}}
          <div id="o-saisie-manuelle" style="display:none;">
            <div style="font-size:12px;color:#888;margin-bottom:12px;padding:10px 12px;background:#f9fafb;border-radius:6px;">
              @if($estSolo)
                Pas trouvé dans l'annuaire ? Remplissez ci-dessous — votre demande sera validée par l'établissement.
              @else
                Pas trouvé dans l'annuaire ? Saisissez les informations de l'enfant manuellement.
              @endif
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
              @unless($estSolo)
              <div>
                <div class="lbl">Prénom de l'enfant *</div>
                <input class="inp" id="o-prenom" name="prenom_apprenant" value="{{ old('prenom_apprenant') }}" placeholder="Brice" />
              </div>
              <div>
                <div class="lbl">Nom *</div>
                <input class="inp" id="o-nom" name="nom_apprenant" value="{{ old('nom_apprenant') }}" placeholder="FONO" />
              </div>
              @endunless
              <div>
                <div class="lbl">{{ Auth::user()->profil === 'etudiant' ? 'Filière / Niveau *' : 'Classe *' }}</div>
                <input class="inp" id="o-classe" name="classe" value="{{ old('classe') }}"
                       placeholder="{{ Auth::user()->profil === 'etudiant' ? 'Ex : Licence 2 GSI' : 'Ex : 3ème A' }}" />
              </div>
              <div>
                <div class="lbl">Matricule (si connu)</div>
                <input class="inp" id="o-matricule" name="matricule" value="{{ old('matricule') }}" placeholder="Ex : EP-1184" />
              </div>
            </div>
          </div>

          <div style="display:flex;gap:10px;margin-top:8px;">
            <a href="{{ route('payeur.dashboard') }}" class="btn-o" style="flex:0 0 auto;width:auto;padding:10px 20px;">
              Plus tard
            </a>
            <button type="submit" class="btn-p" id="btn-submit" style="flex:1;">
              Terminer et accéder à mon tableau de bord →
            </button>
          </div>
        </form>
      </div>

    </div>
  </div>

  <div style="background:var(--ep-navy);padding:14px 28px;display:flex;justify-content:space-between;align-items:center;margin-top:auto;">
    <div style="font-size:11px;color:rgba(255,255,255,.35);">© 2026 EduPay Cameroun</div>
    <div style="display:flex;gap:8px;">
      <span class="footer-badge">TLS 1.3</span>
      <span class="footer-badge">COBAC</span>
    </div>
  </div>
</div>

<script>
var oEtabSelecte = null;
var oApprenantSelectionne = null;
var oAnnuaireTimeout = null;

// ── Étape 1 : filtrer les établissements ──
function oFiltrerEtabs(q) {
    var items = document.querySelectorAll('.o-etab-item');
    var ql = q.toLowerCase().trim();
    items.forEach(function(item) {
        var nom   = item.dataset.nom.toLowerCase();
        var ville = item.dataset.ville.toLowerCase();
        var show  = !ql || nom.includes(ql) || ville.includes(ql);
        item.style.display = show ? 'flex' : 'none';
    });
    document.getElementById('o-etab-liste').style.display = 'block';
}

// ── Étape 1 : sélectionner un établissement → passer à l'étape 2 ──
function oSelectionnerEtab(el) {
    document.querySelectorAll('.o-etab-item').forEach(function(i) {
        i.style.background = '';
        i.querySelector('.o-etab-check').style.opacity = '0';
    });
    el.style.background = '#f0fdf4';
    el.querySelector('.o-etab-check').style.opacity = '1';

    oEtabSelecte = {id: el.dataset.id, nom: el.dataset.nom, ville: el.dataset.ville};

    document.getElementById('h-etab-id').value  = el.dataset.id;
    document.getElementById('h-etab-nom').value = el.dataset.nom;

    document.getElementById('o-etab-badge-nom').textContent   = el.dataset.nom;
    document.getElementById('o-etab-badge-ville').textContent = el.dataset.ville + ' · ' + el.dataset.type;

    document.getElementById('o-step1').style.display = 'none';
    document.getElementById('o-step2').style.display = 'block';

    oReinitApprenant();
    oRechercherApprenant('');

    setTimeout(function() {
        var inp = document.getElementById('o-apprenant-search');
        if (inp) inp.focus();
    }, 150);
}

// ── Revenir à l'étape 1 ──
function oReinitEtab() {
    document.getElementById('h-etab-id').value  = '';
    document.getElementById('h-etab-nom').value = '';
    document.getElementById('o-etab-search').value = '';
    document.querySelectorAll('.o-etab-item').forEach(function(i) {
        i.style.background = '';
        i.querySelector('.o-etab-check').style.opacity = '0';
        i.style.display = 'flex';
    });
    document.getElementById('o-step1').style.display = 'block';
    document.getElementById('o-step2').style.display = 'none';
    oReinitApprenant();
}

// ── Étape 2 : rechercher dans l'annuaire ──
function oRechercherApprenant(q) {
    var etabId = document.getElementById('h-etab-id').value;
    if (!etabId) return;

    clearTimeout(oAnnuaireTimeout);
    var liste = document.getElementById('o-apprenant-liste');
    liste.innerHTML = '<div style="padding:14px;text-align:center;color:#888;font-size:12px;">Chargement de l\'annuaire…</div>';

    oAnnuaireTimeout = setTimeout(function() {
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
                    '<div style="padding:16px;text-align:center;">' +
                    '<div style="font-size:13px;color:#888;margin-bottom:6px;">' + msgVide + '</div>' +
                    '<div style="font-size:11px;color:#aaa;">Remplissez les champs ci-dessous pour un pré-rattachement.</div>' +
                    '</div>';
                oAfficherSaisieManuelle(true);
                return;
            }

            var html = '';
            apprenants.forEach(function(a) {
                html += '<div class="o-app-item" data-id="' + a.id + '" data-nom="' + a.nom + '" ' +
                    'data-prenom="' + a.prenom + '" data-classe="' + (a.classe||'') + '" ' +
                    'data-matricule="' + (a.matricule||'') + '" ' +
                    'onclick="oSelectionnerApprenant(this)" ' +
                    'style="padding:10px 14px;cursor:pointer;border-bottom:1px solid #f5f5f5;' +
                    'display:flex;align-items:center;justify-content:space-between;">' +
                    '<div>' +
                    '<div style="font-size:13px;font-weight:600;">' + a.prenom + ' ' + a.nom + '</div>' +
                    '<div style="font-size:11px;color:#888;">' + (a.classe||'—') +
                    (a.matricule ? ' · Mat. ' + a.matricule : '') + '</div>' +
                    '</div>' +
                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2" ' +
                    'class="o-app-check" style="opacity:0;flex-shrink:0;"><polyline points="20 6 9 17 4 12"/></svg>' +
                    '</div>';
            });
            liste.innerHTML = html;
            oAfficherSaisieManuelle(false);
        })
        .catch(function() {
            liste.innerHTML = '<div style="padding:14px;color:var(--ep-red);font-size:12px;">Erreur de connexion — vérifiez votre réseau.</div>';
        });
    }, 350);
}

function oSelectionnerApprenant(el) {
    document.querySelectorAll('.o-app-item').forEach(function(i) {
        i.style.background = '';
        var chk = i.querySelector('.o-app-check');
        if (chk) chk.style.opacity = '0';
    });
    el.style.background = '#f0fdf4';
    var chk = el.querySelector('.o-app-check');
    if (chk) chk.style.opacity = '1';

    oApprenantSelectionne = {
        id: el.dataset.id, nom: el.dataset.nom, prenom: el.dataset.prenom,
        classe: el.dataset.classe, matricule: el.dataset.matricule
    };

    document.getElementById('h-apprenant-id').value = el.dataset.id;

    var inp = {
        'prenom_apprenant': el.dataset.prenom,
        'nom_apprenant':    el.dataset.nom,
        'classe':           el.dataset.classe,
        'matricule':        el.dataset.matricule
    };
    Object.keys(inp).forEach(function(name) {
        var field = document.querySelector('#onb-form [name="' + name + '"]');
        if (field) field.value = inp[name] || '';
    });

    var badge = document.getElementById('o-app-badge');
    badge.style.display = 'flex';
    document.getElementById('o-app-badge-nom').textContent  = el.dataset.prenom + ' ' + el.dataset.nom;
    document.getElementById('o-app-badge-info').textContent = (el.dataset.classe||'') + (el.dataset.matricule ? ' · ' + el.dataset.matricule : '');

    document.getElementById('o-apprenant-liste').style.display = 'none';
    oAfficherSaisieManuelle(true);
}

function oAfficherSaisieManuelle(show) {
    var bloc = document.getElementById('o-saisie-manuelle');
    if (bloc) bloc.style.display = show ? 'block' : 'none';
}

function oReinitApprenant() {
    oApprenantSelectionne = null;
    document.getElementById('h-apprenant-id').value = '';
    document.getElementById('o-app-badge').style.display = 'none';
    var liste = document.getElementById('o-apprenant-liste');
    liste.innerHTML = '<div style="padding:16px;text-align:center;color:#aaa;font-size:13px;">Chargement de l\'annuaire…</div>';
    liste.style.display = 'block';
    var searchInp = document.getElementById('o-apprenant-search');
    if (searchInp) searchInp.value = '';
    oAfficherSaisieManuelle(false);
}

// ── Fermer la liste établissement si clic extérieur ──
document.addEventListener('click', function(e) {
    var liste  = document.getElementById('o-etab-liste');
    var search = document.getElementById('o-etab-search');
    if (liste && search && !liste.contains(e.target) && e.target !== search) {
        liste.style.display = 'none';
    }
});

// ── Validation avant soumission ──
document.getElementById('onb-form').addEventListener('submit', function(e) {
    if (!document.getElementById('h-etab-id').value && !document.getElementById('h-etab-nom').value) {
        e.preventDefault();
        alert('Veuillez sélectionner un établissement.');
        return;
    }
    var classeInp = document.getElementById('o-classe');
    if (classeInp && document.getElementById('o-saisie-manuelle').style.display !== 'none' && !classeInp.value.trim()) {
        e.preventDefault();
        classeInp.style.border = '1.5px solid var(--ep-red)';
        classeInp.focus();
        alert('Veuillez indiquer la classe.');
    }
});
</script>
@endsection
