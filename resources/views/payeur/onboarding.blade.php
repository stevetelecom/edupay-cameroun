@extends('layouts.public')
@section('title', 'Rattachement — EduPay Cameroun')

@section('content')
<div style="min-height:100vh;background:#f1f3f5;display:flex;flex-direction:column;">

  <div class="form-header">
    <div class="logo-t" style="font-size:17px;">Edu<span>Pay</span> — Bienvenue</div>
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
            ? 'Recherchez votre établissement partenaire EduPay ci-dessous.'
            : 'Recherchez l\'établissement de votre enfant. Vous pourrez en ajouter d\'autres depuis votre tableau de bord.' }}
      </div>

      {{-- ══ BLOC RECHERCHE ÉTABLISSEMENT ══ --}}
      <div style="margin-bottom:20px;">
        <div class="lbl">Rechercher un établissement *</div>
        <div style="position:relative;margin-bottom:10px;">
          <input type="text" id="etab-search"
                 placeholder="Tapez le nom ou la ville…"
                 style="width:100%;padding:11px 12px 11px 36px;border:1px solid #ddd;border-radius:8px;font-size:13px;outline:none;"
                 oninput="filtrerEtabs(this.value)"
                 onfocus="document.getElementById('etab-liste').style.display='block'" />
          <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#aaa;"
               width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </div>

        {{-- Liste établissements — visible dès l'ouverture, filtrée en temps réel --}}
        <div id="etab-liste"
             style="border:1px solid #e0e0e0;border-radius:8px;background:#fff;
                    max-height:220px;overflow-y:auto;box-shadow:0 4px 16px rgba(0,0,0,.1);">
          @foreach($etablissements as $etab)
          <div class="etab-item"
               data-id="{{ $etab->id }}"
               data-nom="{{ $etab->nom }}"
               data-ville="{{ $etab->ville }}"
               data-type="{{ $etab->type }}"
               onclick="selectionnerEtab(this)"
               style="padding:10px 14px;cursor:pointer;border-bottom:1px solid #f5f5f5;display:flex;align-items:center;justify-content:space-between;">
            <div>
              <div style="font-size:13px;font-weight:600;">{{ $etab->nom }}</div>
              <div style="font-size:11px;color:#888;">{{ $etab->ville }} · {{ $etab->type }}</div>
            </div>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2"
                 style="flex-shrink:0;opacity:0;" class="etab-check">
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

        {{-- Établissement sélectionné (badge) --}}
        <div id="etab-selectionne" style="display:none;margin-top:10px;background:var(--ep-teal-lt);border-radius:8px;padding:10px 14px;display:none;align-items:center;justify-content:space-between;">
          <div>
            <div style="font-size:13px;font-weight:600;color:#085041;" id="etab-sel-nom"></div>
            <div style="font-size:11px;color:#1B9E75;" id="etab-sel-ville"></div>
          </div>
          <button type="button" onclick="reinitialiserEtab()"
                  style="background:none;border:none;color:#888;cursor:pointer;font-size:18px;line-height:1;">×</button>
        </div>
      </div>

      {{-- Champs cachés pour la soumission --}}
      <form method="POST" action="{{ route('payeur.onboarding.store') }}" id="onb-form">
        @csrf
        <input type="hidden" name="lien" value="{{ $estSolo ? 'soi-meme' : 'parent' }}" />
        <input type="hidden" name="etablissement_id"  id="h-etab-id"  value="{{ old('etablissement_id') }}" />
        <input type="hidden" name="etablissement_nom" id="h-etab-nom" value="{{ old('etablissement_nom') }}" />

        @if(!$estSolo)
        {{-- Parent : infos de l'enfant --}}
        <div style="border:1px solid #e8e8e8;border-radius:8px;padding:16px;margin-bottom:16px;">
          <div style="font-size:11px;font-weight:700;color:#999;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">
            Informations de l'enfant
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div>
              <div class="lbl">Prénom *</div>
              <input class="inp" name="prenom_apprenant" value="{{ old('prenom_apprenant') }}"
                     placeholder="Brice" required />
            </div>
            <div>
              <div class="lbl">Nom *</div>
              <input class="inp" name="nom_apprenant" value="{{ old('nom_apprenant') }}"
                     placeholder="FONO" required />
            </div>
            <div>
              <div class="lbl">Classe *</div>
              <input class="inp" name="classe" value="{{ old('classe') }}"
                     placeholder="Ex : 3ème A" required />
            </div>
            <div>
              <div class="lbl">Matricule (si connu)</div>
              <input class="inp" name="matricule" value="{{ old('matricule') }}"
                     placeholder="Ex : EP-1184" />
            </div>
          </div>
        </div>
        @else
        {{-- Élève/Étudiant : juste la classe --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
          <div>
            <div class="lbl">{{ Auth::user()->profil === 'etudiant' ? 'Filière / Niveau *' : 'Classe *' }}</div>
            <input class="inp" name="classe" value="{{ old('classe') }}"
                   placeholder="{{ Auth::user()->profil === 'etudiant' ? 'Ex : Licence 2 GSI' : 'Ex : Tle A' }}"
                   required />
          </div>
          <div>
            <div class="lbl">Matricule (si connu)</div>
            <input class="inp" name="matricule" value="{{ old('matricule') }}"
                   placeholder="Ex : EP-1184" />
          </div>
        </div>
        @endif

        <div style="background:var(--ep-teal-lt);border-radius:8px;padding:12px;margin-bottom:18px;font-size:12px;color:#085041;display:flex;gap:10px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2" style="flex-shrink:0;">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          <span>
            {{ $estSolo
                ? 'Apprenant introuvable dans l\'annuaire ? Votre demande sera en pré-rattachement, validée par l\'établissement.'
                : 'Vous pourrez rattacher d\'autres enfants depuis votre tableau de bord.' }}
          </span>
        </div>

        <div style="display:flex;gap:10px;">
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

  <div style="background:var(--ep-navy);padding:14px 28px;display:flex;justify-content:space-between;align-items:center;margin-top:auto;">
    <div style="font-size:11px;color:rgba(255,255,255,.35);">© 2026 EduPay Cameroun</div>
    <div style="display:flex;gap:8px;">
      <span class="footer-badge">TLS 1.3</span>
      <span class="footer-badge">COBAC</span>
    </div>
  </div>
</div>

<script>
var etabSelecte = null;

// ── Filtre la liste en temps réel ──
function filtrerEtabs(q) {
    var items = document.querySelectorAll('.etab-item');
    var ql = q.toLowerCase().trim();
    var visible = 0;
    items.forEach(function(item) {
        var nom   = item.dataset.nom.toLowerCase();
        var ville = item.dataset.ville.toLowerCase();
        var show  = !ql || nom.includes(ql) || ville.includes(ql);
        item.style.display = show ? 'flex' : 'none';
        if (show) visible++;
    });
    document.getElementById('etab-liste').style.display = 'block';
}

// ── Sélectionner un établissement ──
function selectionnerEtab(el) {
    // Reset précédent
    document.querySelectorAll('.etab-item').forEach(function(i) {
        i.style.background = '';
        i.querySelector('.etab-check').style.opacity = '0';
    });

    el.style.background = '#f0fdf4';
    el.querySelector('.etab-check').style.opacity = '1';

    etabSelecte = {id: el.dataset.id, nom: el.dataset.nom, ville: el.dataset.ville};

    document.getElementById('h-etab-id').value  = el.dataset.id;
    document.getElementById('h-etab-nom').value = el.dataset.nom;
    document.getElementById('etab-search').value = el.dataset.nom;

    // Afficher le badge
    document.getElementById('etab-sel-nom').textContent   = el.dataset.nom;
    document.getElementById('etab-sel-ville').textContent = el.dataset.ville + ' · ' + el.dataset.type;
    var badge = document.getElementById('etab-selectionne');
    badge.style.display = 'flex';

    // Masquer la liste
    document.getElementById('etab-liste').style.display = 'none';
}

// ── Réinitialiser la sélection ──
function reinitialiserEtab() {
    etabSelecte = null;
    document.getElementById('h-etab-id').value  = '';
    document.getElementById('h-etab-nom').value = '';
    document.getElementById('etab-search').value = '';
    document.getElementById('etab-selectionne').style.display = 'none';
    document.getElementById('etab-liste').style.display = 'block';
    document.querySelectorAll('.etab-item').forEach(function(i) {
        i.style.background = '';
        i.querySelector('.etab-check').style.opacity = '0';
        i.style.display = 'flex';
    });
}

// ── Fermer la liste si clic extérieur ──
document.addEventListener('click', function(e) {
    var liste  = document.getElementById('etab-liste');
    var search = document.getElementById('etab-search');
    if (!liste.contains(e.target) && e.target !== search) {
        liste.style.display = 'none';
    }
});

// ── Validation avant soumission ──
document.getElementById('onb-form').addEventListener('submit', function(e) {
    if (!document.getElementById('h-etab-id').value &&
        !document.getElementById('h-etab-nom').value) {
        e.preventDefault();
        document.getElementById('etab-search').style.border = '1.5px solid var(--ep-red)';
        document.getElementById('etab-search').focus();
        alert('Veuillez sélectionner un établissement dans la liste.');
    }
});

// ── Restaurer la sélection si old() disponible ──
document.addEventListener('DOMContentLoaded', function() {
    var oldId  = '{{ old("etablissement_id") }}';
    var oldNom = '{{ old("etablissement_nom") }}';
    if (oldId || oldNom) {
        var items = document.querySelectorAll('.etab-item');
        items.forEach(function(item) {
            if (item.dataset.id === oldId || item.dataset.nom === oldNom) {
                selectionnerEtab(item);
            }
        });
    }
});
</script>
@endsection
