@extends('layouts.public')
@section('title', 'Inscription — EduPay Cameroun')

@section('content')
<div style="min-height:100vh;background:#f1f3f5;display:flex;flex-direction:column;">

  {{-- HEADER --}}
  <div class="form-header">
    <div style="display:flex;align-items:center;gap:12px;">
      <a href="{{ route('landing') }}" style="color:rgba(255,255,255,.7);text-decoration:none;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
      </a>
      <div class="logo-t" style="font-size:17px;">Edu<span>Pay</span> Cameroun — Inscription Payeur</div>
    </div>
    <div style="font-size:12px;color:rgba(255,255,255,.5);">
      Déjà un compte ? <a href="{{ route('login') }}" style="color:#5DCAA5;font-weight:600;">Se connecter</a>
    </div>
  </div>

  <div class="form-body" style="padding-top:28px;padding-bottom:40px;">
    <div class="form-card-wide">

      {{-- BARRE ÉTAPES --}}
      <div style="display:flex;align-items:center;margin-bottom:24px;">
        <div style="flex:1;text-align:center;">
          <div style="width:30px;height:30px;border-radius:50%;background:var(--ep-teal);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;margin:0 auto 5px;">1</div>
          <div style="font-size:11px;font-weight:600;color:var(--ep-teal);">Compte</div>
        </div>
        <div style="flex:1;height:2px;background:#e0e0e0;margin-top:-16px;"></div>
        <div style="flex:1;text-align:center;">
          <div style="width:30px;height:30px;border-radius:50%;border:2px solid #ddd;color:#ccc;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;margin:0 auto 5px;">2</div>
          <div style="font-size:11px;color:#aaa;" id="step2-label">Établissement</div>
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

      <div class="form-title" id="reg-title">Créer votre compte</div>
      <div class="form-sub" id="reg-sub">Renseignez vos informations pour payer les frais scolaires en ligne</div>

      <form method="POST" action="{{ route('register.parent.step1.post') }}">
        @csrf
        <input type="hidden" name="code_etablissement" value="{{ old('code_etablissement', request('code_etablissement')) }}" />

        @if(request('code_etablissement'))
          @php
            $etabPreselec = \App\Models\Etablissement::where('code_etablissement', request('code_etablissement'))->first();
          @endphp
          @if($etabPreselec)
          <div style="background:var(--ep-teal-lt);border:1px solid #5DCAA5;border-radius:8px;
                      padding:10px 14px;margin-bottom:16px;display:flex;align-items:center;gap:10px;">
            <span class="material-symbols-outlined" style="font-size:18px;color:#0D9E75;">check_circle</span>
            <div style="font-size:12px;color:#085041;">
              Inscription pour <strong>{{ $etabPreselec->nom }}</strong> — {{ $etabPreselec->ville }}
            </div>
          </div>
          @endif
        @endif

        {{-- PROFIL --}}
        <div class="form-section">Vous êtes :</div>
        <div style="display:flex;gap:10px;margin-bottom:16px;">

          <label style="flex:1;padding:14px 10px;border:2px solid var(--ep-teal);border-radius:8px;cursor:pointer;text-align:center;background:var(--ep-teal-lt);" id="lbl-parent">
            <input type="radio" name="profil" value="parent" style="display:none;" {{ (old('profil', request('profil')) && old('profil', request('profil')) !== 'parent') ? '' : 'checked' }} onchange="switchProfil('parent')">
            {{-- Icône famille --}}
            <div class="prof-label"  style="display:flex;justify-content:center;margin-bottom:6px;">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="1.5">
                <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                <path d="M16 3.13a4 4 0 010 7.75"/>
              </svg>
            </div>
            <div style="font-size:12px;font-weight:700;color:#085041;">Parent</div>
            <div style="font-size:10px;color:#1B9E75;margin-top:2px;">Je paye pour mes enfants</div>
          </label>

          <label style="flex:1;padding:14px 10px;border:2px solid #ddd;border-radius:8px;cursor:pointer;text-align:center;background:#fff;" id="lbl-eleve">
            <input type="radio" name="profil" value="eleve" style="display:none;" {{ old('profil', request('profil'))==='eleve'?'checked':'' }} onchange="switchProfil('eleve')">
            {{-- Icône élève --}}
            <div class="prof-label" style="display:flex;justify-content:center;margin-bottom:6px;">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="1.5">
                <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                <path d="M6 12v5c3 3 9 3 12 0v-5"/>
              </svg>
            </div>
            <div style="font-size:12px;font-weight:700;color:#333;">Élève</div>
            <div style="font-size:10px;color:#888;margin-top:2px;">Je paye mes propres frais</div>
          </label>

          <label  style="flex:1;padding:14px 10px;border:2px solid #ddd;border-radius:8px;cursor:pointer;text-align:center;background:#fff;" id="lbl-etudiant">
            <input type="radio" name="profil" value="etudiant" style="display:none;" {{ old('profil', request('profil'))==='etudiant'?'checked':'' }} onchange="switchProfil('etudiant')">
            {{-- Icône étudiant --}}
            <div class="prof-label"  style="display:flex;justify-content:center;margin-bottom:6px;">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="1.5">
                <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
              </svg>
            </div>
            <div style="font-size:12px;font-weight:700;color:#333;">Étudiant</div>
            <div style="font-size:10px;color:#888;margin-top:2px;">Université / Institut</div>
          </label>

        </div>

        {{-- INFOS PERSONNELLES --}}
        <div class="form-section">Informations personnelles</div>
        <div class="inp-row">
          <div>
            <div class="lbl">Prénom *</div>
            <input class="inp" name="prenom" value="{{ old('prenom') }}" placeholder="ex : Marie" required />
          </div>
          <div>
            <div class="lbl">Nom *</div>
            <input class="inp" name="nom" value="{{ old('nom') }}" placeholder="ex : FONO" required />
          </div>
        </div>
        <div class="inp-row">
          <div>
            <div class="lbl">Numéro de téléphone *</div>
            <input class="inp phone-input-parent" name="telephone" value="{{ old('telephone') }}" placeholder="6XX XXX XXX" required />
          </div>
          <div>
            <div class="lbl">Adresse email</div>
            <input class="inp" type="email" name="email" value="{{ old('email') }}" placeholder="exemple@email.cm" />
          </div>
        </div>
        <div class="inp-row">
          <div>
            <div class="lbl">Ville de résidence *</div>
            <select class="select" name="ville" required>
              <option value="">-- Choisir --</option>
              @foreach(['Yaoundé','Douala','Bafoussam','Garoua','Maroua','Ngaoundéré','Kribi','Buea','Bamenda','Ambam','Ebolowa','Bertoua','Autre'] as $v)
                <option {{ old('ville')===$v?'selected':'' }}>{{ $v }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <div class="lbl">Quartier / Arrondissement</div>
            <input class="inp" name="quartier" value="{{ old('quartier') }}" placeholder="ex : Melen, Biyem-Assi..." />
          </div>
        </div>

        {{-- SÉCURITÉ --}}
        <div class="form-section">Sécurité du compte</div>
        <div class="inp-row">
          <div>
            <div class="lbl">Mot de passe *</div>
            <div style="position:relative;">
              <input class="inp" type="password" name="password" id="pwd" placeholder="Min. 8 caractères" required style="padding-right:40px;" />
              <span onclick="togglePwd('pwd')" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:#aaa;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </span>
            </div>
          </div>
          <div>
            <div class="lbl">Confirmer le mot de passe *</div>
            <div style="position:relative;">
              <input class="inp" type="password" name="password_confirmation" id="pwd2" placeholder="Répétez le mot de passe" required style="padding-right:40px;" />
              <span onclick="togglePwd('pwd2')" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:#aaa;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </span>
            </div>
          </div>
        </div>
        <div style="background:#f8f9fa;border-radius:8px;padding:12px;margin-bottom:16px;font-size:12px;color:#666;">
          <div style="font-weight:600;margin-bottom:4px;">Le mot de passe doit contenir :</div>
          <div style="display:flex;gap:16px;flex-wrap:wrap;">
            <span>• 8 caractères minimum</span>
            <span>• 1 majuscule</span>
            <span>• 1 chiffre</span>
            <span>• 1 caractère spécial</span>
          </div>
        </div>

        {{-- CGU --}}
        <div class="form-section">Conditions d'utilisation</div>
        <div class="check-row">
          <input type="checkbox" name="cgu_accepted" value="1" id="cgu1" required {{ old('cgu_accepted')?'checked':'' }} />
          <label for="cgu1">J'accepte les <span style="color:var(--ep-teal);cursor:pointer;">Conditions Générales d'Utilisation</span> et la <span style="color:var(--ep-teal);cursor:pointer;">Politique de confidentialité</span> d'EduPay Cameroun.</label>
        </div>
        <div class="check-row">
          <input type="checkbox" name="notif_sms" value="1" id="cgu2" checked />
          <label for="cgu2">J'accepte de recevoir des notifications par SMS et email concernant les paiements et rappels d'échéances.</label>
        </div>

        {{-- BOUTONS --}}
        <div style="display:flex;gap:10px;margin-top:18px;">
          <a href="{{ route('login') }}" class="btn-o" style="flex:0 0 auto;width:auto;padding:10px 20px;">Annuler</a>
          <button type="submit" class="btn-p">Créer mon compte et continuer →</button>
        </div>
        <div style="text-align:center;margin-top:14px;font-size:12px;color:#888;">
          Déjà un compte ? <a href="{{ route('login') }}" style="color:var(--ep-teal);font-weight:600;">Se connecter</a>
        </div>

      </form>
    </div>
  </div>

  {{-- FOOTER --}}
  <div style="background:var(--ep-navy);padding:14px 28px;display:flex;justify-content:space-between;align-items:center;">
    <div style="font-size:11px;color:rgba(255,255,255,.35);">© 2026 EduPay Cameroun · Données chiffrées AES-256</div>
    <div style="display:flex;gap:8px;">
      <span class="footer-badge">TLS 1.3</span>
      <span class="footer-badge">PCI-DSS</span>
      <span class="footer-badge">COBAC</span>
    </div>
  </div>

</div>

<script>
function switchProfil(val) {
  var configs = {
    parent:   { border: 'var(--ep-teal)', bg: 'var(--ep-teal-lt)', stroke: '#0D9E75', titre: 'Créer votre compte Parent',   sub: 'Renseignez vos informations pour payer les frais scolaires de vos enfants', step2: 'Enfant(s)' },
    eleve:    { border: '#185FA5',         bg: '#EFF6FF',           stroke: '#185FA5', titre: 'Créer votre compte Élève',    sub: 'Renseignez vos informations pour gérer et payer vos propres frais scolaires',  step2: 'Mon école' },
    etudiant: { border: '#7C3AED',         bg: '#F5F3FF',           stroke: '#7C3AED', titre: 'Créer votre compte Étudiant', sub: 'Renseignez vos informations pour gérer et payer vos frais universitaires',      step2: 'Mon université' }
  };

  ['parent','eleve','etudiant'].forEach(function(p) {
    var lbl = document.getElementById('lbl-' + p);
    var cfg = configs[p];
    if (p === val) {
      lbl.style.border     = '2px solid ' + cfg.border;
      lbl.style.background = cfg.bg;
      lbl.querySelector('svg').setAttribute('stroke', cfg.stroke);
      lbl.querySelector('.prof-label').style.color = cfg.border;
    } else {
      lbl.style.border     = '2px solid #ddd';
      lbl.style.background = '#fff';
      lbl.querySelector('svg').setAttribute('stroke', '#555');
      lbl.querySelector('.prof-label').style.color = '#333';
    }
  });

  document.getElementById('reg-title').textContent  = configs[val].titre;
  document.getElementById('reg-sub').textContent    = configs[val].sub;
  document.getElementById('step2-label').textContent = configs[val].step2;
}

function togglePwd(id) {
  var inp = document.getElementById(id);
  inp.type = inp.type === 'password' ? 'text' : 'password';
}

// Formatage +237 pour téléphone
function formatPhoneNumber(input) {
  let value = input.value.trim();
  
  // Si c'est un email, ne pas modifier
  if (value.includes('@')) return;
  
  // Enlever tous les caractères non-numériques sauf le +
  value = value.replace(/[^\d+]/g, '');
  
  // Si commence par +237, c'est bon
  if (value.startsWith('+237')) {
    input.value = value;
    return;
  }
  
  // Si commence par 237 sans +, ajouter le +
  if (value.startsWith('237')) {
    input.value = '+' + value;
    return;
  }
  
  // Si commence par 6 ou 7, ajouter +237
  if (value && /^[67]/.test(value)) {
    input.value = '+237' + value;
    return;
  }
  
  // Sinon laisser tel quel
  input.value = value;
}

document.addEventListener('DOMContentLoaded', function() {
  var checked = document.querySelector('input[name="profil"]:checked');
  switchProfil(checked ? checked.value : 'parent');

  // Clic sur label → cocher le radio
  ['parent','eleve','etudiant'].forEach(function(p) {
    document.getElementById('lbl-' + p).addEventListener('click', function() {
      document.querySelector('input[name="profil"][value="' + p + '"]').checked = true;
      switchProfil(p);
    });
  });

  // Formatage +237 pour le téléphone
  const phoneInputParent = document.querySelector('.phone-input-parent');
  if (phoneInputParent) {
    // Formater au chargement (old('telephone'))
    formatPhoneNumber(phoneInputParent);
    
    // Formater à chaque saisie
    phoneInputParent.addEventListener('input', function(e) {
      formatPhoneNumber(e.target);
    });
  }
});
</script>
@endsection
