@extends('layouts.public')
@section('title', 'Rattachement — EduPay Cameroun')

@section('content')
<div style="min-height:100vh;background:#f1f3f5;display:flex;flex-direction:column;">

  {{-- HEADER --}}
  <div class="form-header">
    <div class="logo-t" style="font-size:17px;">Edu<span>Pay</span> — Bienvenue</div>
    <div style="font-size:12px;color:rgba(255,255,255,.5);">Étape 2 sur 3</div>
  </div>

  <div class="form-body" style="padding-top:28px;padding-bottom:40px;">
    <div class="form-card-wide">

      {{-- BARRE ÉTAPES --}}
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
            {{ in_array(Auth::user()->profil, ['eleve','etudiant']) ? 'Mon école' : 'Enfant(s)' }}
          </div>
        </div>
        <div style="flex:1;height:2px;background:#e0e0e0;margin-top:-16px;"></div>
        <div style="flex:1;text-align:center;">
          <div style="width:30px;height:30px;border-radius:50%;border:2px solid #ddd;color:#ccc;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;margin:0 auto 5px;">3</div>
          <div style="font-size:11px;color:#aaa;">Confirmation</div>
        </div>
      </div>

      {{-- ERREURS --}}
      @if($errors->any())
        <div style="background:#FEE2E2;border:1px solid #FCA5A5;border-radius:8px;padding:12px 16px;margin-bottom:18px;">
          <div style="font-size:13px;font-weight:600;color:#991B1B;margin-bottom:6px;">Veuillez corriger les erreurs :</div>
          <ul style="margin:0;padding-left:18px;">
            @foreach($errors->all() as $error)
              <li style="font-size:12px;color:#B91C1C;">{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      @php
        $estSoloOnb = in_array(Auth::user()->profil, ['eleve', 'etudiant']);
        $estEtudiantOnb = Auth::user()->profil === 'etudiant';
      @endphp

      @if($estSoloOnb)
        <div class="form-title">Rattachez votre profil à votre établissement</div>
        <div class="form-sub">Recherchez votre établissement dans la liste des partenaires EduPay pour accéder à votre dossier scolaire.</div>
      @else
        <div class="form-title">Rattachez votre enfant à son établissement</div>
        <div class="form-sub">Recherchez l'établissement de votre enfant dans la liste des partenaires EduPay. Vous pourrez en rattacher d'autres ensuite depuis votre tableau de bord.</div>
      @endif

      <form method="POST" action="{{ route('payeur.onboarding.store') }}">
        @csrf
        <input type="hidden" name="lien" value="{{ $estSoloOnb ? 'soi-meme' : 'parent' }}" />

        @if($estSoloOnb)
          {{-- ════════════ BLOC SOLO — Élève / Étudiant ════════════ --}}
          <div class="form-section" style="margin-top:0;">
            {{ $estEtudiantOnb ? 'Mon profil scolaire (Université / Institut)' : 'Mon profil scolaire (Primaire / Secondaire)' }}
          </div>
          <div class="inp-row">
            <div>
              <div class="lbl">Nom de mon établissement *</div>
              <input class="inp" name="etablissement_nom" list="etabs-list" id="etab-input"
                     placeholder="Rechercher dans la liste des établissements partenaires..." required value="{{ old('etablissement_nom') }}" />
              <input type="hidden" name="etablissement_id" id="etablissement_id" value="{{ old('etablissement_id') }}" />
            </div>
            <div>
              <div class="lbl">Matricule (si connu)</div>
              <input class="inp" name="matricule" placeholder="ex : EP-1184" value="{{ old('matricule') }}" />
            </div>
          </div>
          <div class="inp-row">
            <div>
              <div class="lbl">{{ $estEtudiantOnb ? 'Niveau / Filière *' : 'Classe *' }}</div>
              <input class="inp" name="classe" required
                     placeholder="{{ $estEtudiantOnb ? 'ex : Licence 2 GSI, Master 1...' : 'ex : 3ème, Tle A...' }}"
                     value="{{ old('classe') }}" />
            </div>
            <div>
              <div class="lbl">Code établissement (optionnel)</div>
              <input class="inp" name="code_etablissement" placeholder="Ex : UD-2026" value="{{ old('code_etablissement') }}" />
            </div>
          </div>
          <div style="font-size:11px;color:#888;margin:-8px 0 14px;">
            Établissement absent de la liste ? Contactez le support EduPay pour l'inscrire.
          </div>
          <div style="background:var(--ep-teal-lt);border-radius:var(--radius-md);padding:12px;margin-bottom:16px;font-size:12px;color:#085041;display:flex;gap:10px;align-items:flex-start;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span>En tant qu'{{ $estEtudiantOnb ? 'étudiant' : 'élève' }}, vous êtes à la fois apprenant et payeur de votre propre compte : vous gérerez et payerez directement vos frais de scolarité.</span>
          </div>
        @else
          {{-- ════════════ BLOC PARENT — Un enfant ════════════ --}}
          <div class="epcard" style="margin-bottom:12px;border-left:3px solid var(--ep-teal);">
            <div style="font-size:11px;font-weight:700;color:#999;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;">Enfant</div>
            <div class="inp-row">
              <div>
                <div class="lbl">Établissement de l'enfant *</div>
                <input class="inp" name="etablissement_nom" list="etabs-list" id="etab-input"
                       placeholder="Rechercher par nom dans la liste des établissements partenaires..." required value="{{ old('etablissement_nom') }}" />
                <input type="hidden" name="etablissement_id" id="etablissement_id" value="{{ old('etablissement_id') }}" />
              </div>
              <div>
                <div class="lbl">Code établissement (optionnel)</div>
                <input class="inp" name="code_etablissement" placeholder="Ex : LYC-MEL-2026" value="{{ old('code_etablissement') }}" />
              </div>
            </div>
            <div class="inp-row">
              <div>
                <div class="lbl">Prénom de l'enfant *</div>
                <input class="inp" name="prenom_apprenant" placeholder="Ex : Brice" required value="{{ old('prenom_apprenant') }}" />
              </div>
              <div>
                <div class="lbl">Nom de l'enfant *</div>
                <input class="inp" name="nom_apprenant" placeholder="Ex : FONO" required value="{{ old('nom_apprenant') }}" />
              </div>
            </div>
            <div class="inp-row">
              <div>
                <div class="lbl">Classe *</div>
                <input class="inp" name="classe" placeholder="Ex : 3ème" required value="{{ old('classe') }}" />
              </div>
              <div>
                <div class="lbl">Matricule (si connu)</div>
                <input class="inp" name="matricule" placeholder="Ex : EP-1184" value="{{ old('matricule') }}" />
              </div>
            </div>
          </div>
          <div style="background:var(--ep-teal-lt);border-radius:var(--radius-md);padding:12px;margin-bottom:16px;font-size:12px;color:#085041;display:flex;gap:10px;align-items:flex-start;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span>
              Vous pourrez rattacher d'autres enfants depuis votre tableau de bord, onglet « Mes enfants ».
              Apprenant introuvable dans l'annuaire de l'établissement ? Votre demande sera soumise en pré-rattachement, validée ensuite par l'établissement.
            </span>
          </div>
        @endif

        {{-- BOUTONS --}}
        <div style="display:flex;gap:10px;margin-top:6px;">
          <a href="{{ route('payeur.dashboard') }}" class="btn-o" style="flex:0 0 auto;width:auto;padding:10px 20px;">
            Plus tard, depuis mon tableau de bord
          </a>
          <button type="submit" class="btn-p">Terminer et accéder à mon tableau de bord →</button>
        </div>
      </form>
    </div>
  </div>

  {{-- DATALIST établissements partenaires --}}
  <datalist id="etabs-list">
    @foreach($etablissements as $etab)
      <option data-id="{{ $etab->id }}" value="{{ $etab->nom }}">{{ $etab->nom }} — {{ $etab->ville }}</option>
    @endforeach
  </datalist>

  {{-- FOOTER --}}
  <div style="background:var(--ep-navy);padding:14px 28px;display:flex;justify-content:space-between;align-items:center;">
    <div style="font-size:11px;color:rgba(255,255,255,.35);">© 2026 EduPay Cameroun · Données chiffrées AES-256</div>
    <div style="display:flex;gap:8px;">
      <span class="footer-badge">TLS 1.3</span>
      <span class="footer-badge">PCI-DSS</span>
      <span class="footer-badge">RGPD</span>
    </div>
  </div>

</div>

<script>
  document.getElementById('etab-input').addEventListener('input', function () {
    var list = document.getElementById('etabs-list');
    var options = list.querySelectorAll('option');
    var matched = null;
    options.forEach(function (opt) {
      if (opt.value === this.value) matched = opt;
    }, this);
    document.getElementById('etablissement_id').value = matched ? matched.getAttribute('data-id') : '';
  });
</script>
@endsection
