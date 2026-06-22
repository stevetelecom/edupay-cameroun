@extends('layouts.public')
@section('title', 'Inscription École — EduPay Cameroun')
@section('content')
<div style="min-height:100vh;background:#f1f3f5;display:flex;flex-direction:column;">
  <div class="form-header">
    <div style="display:flex;align-items:center;gap:12px;">
      <a href="{{ route('landing') }}" style="background:transparent;color:rgba(255,255,255,.7);border:none;cursor:pointer;padding:0;text-decoration:none;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.7)" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
      </a>
      <div class="logo-t" style="font-size:17px;">Edu<span>Pay</span> — Inscrire mon établissement</div>
    </div>
    <div style="font-size:12px;color:rgba(255,255,255,.5);">Déjà inscrit ? <a href="{{ route('login') }}" style="color:#5DCAA5;">Accéder au back-office</a></div>
  </div>

  <div class="form-body" style="padding-top:28px;">
    <div class="form-card-wide">

      {{-- Barre 4 étapes --}}
      <div style="display:flex;align-items:center;margin-bottom:24px;">
        @foreach (['Établissement', 'Responsable', 'Configuration', 'Validation'] as $i => $label)
          @php $n = $i + 1; @endphp
          <div style="flex:1;text-align:center;">
            <div style="width:30px;height:30px;border-radius:50%;{{ $step >= $n ? 'background:var(--ep-teal);color:#fff;' : 'border:2px solid #ddd;color:#ccc;' }}display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;margin:0 auto 5px;">{{ $n }}</div>
            <div style="font-size:11px;font-weight:{{ $step >= $n ? '600' : '400' }};color:{{ $step >= $n ? 'var(--ep-teal)' : '#aaa' }};">{{ $label }}</div>
          </div>
          @if (!$loop->last)
            <div style="flex:1;height:2px;background:#e0e0e0;margin-top:-16px;"></div>
          @endif
        @endforeach
      </div>

      @if (session('error'))
        <div style="background:#fde8e8;color:#c0392b;border-radius:8px;padding:12px 14px;margin-bottom:16px;font-size:13px;">{{ session('error') }}</div>
      @endif

      @if ($errors->any())
        <div style="background:#fde8e8;color:#c0392b;border-radius:8px;padding:12px 14px;margin-bottom:16px;font-size:13px;">
          <strong>Merci de corriger :</strong>
          <ul style="margin:6px 0 0 18px;">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      {{-- ───────────────────────── ÉTAPE 1 : ÉTABLISSEMENT ───────────────────────── --}}
      @if ($step === 1)
        <div class="form-title">Informations de l'établissement</div>
        <div class="form-sub">Renseignez les informations officielles de votre établissement</div>

        <form method="POST" action="{{ route('register.ecole.step1.post') }}">
          @csrf

          <div class="form-section">Identité de l'établissement</div>
          <div class="lbl">Nom officiel de l'établissement *</div>
          <input class="inp" name="nom" value="{{ old('nom', $old['nom'] ?? '') }}" placeholder="ex : Lycée Bilingue de Melen" required />

          <div class="inp-row">
            <div>
              <div class="lbl">Type d'établissement *</div>
              <select class="select" name="type" required>
                <option value="">-- Choisir --</option>
                @foreach ([
                  'maternelle' => 'École maternelle', 'primaire' => 'École primaire',
                  'college' => 'Collège', 'lycee_general' => 'Lycée général',
                  'lycee_technique' => 'Lycée technique', 'universite' => 'Université',
                  'institut_prive' => 'Institut privé', 'groupe_scolaire' => 'Groupe scolaire (multi-niveaux)',
                ] as $value => $label)
                  <option value="{{ $value }}" @selected(old('type', $old['type'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <div class="lbl">Statut juridique *</div>
              <select class="select" name="statut_juridique" required>
                <option value="">-- Choisir --</option>
                @foreach ([
                  'public' => 'Public (État)', 'prive_laic' => 'Privé laïc',
                  'prive_catholique' => 'Privé confessionnel (catholique)',
                  'prive_protestant' => 'Privé confessionnel (protestant)',
                  'prive_islamique' => 'Privé confessionnel (islamique)',
                ] as $value => $label)
                  <option value="{{ $value }}" @selected(old('statut_juridique', $old['statut_juridique'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="inp-row">
            <div><div class="lbl">Numéro d'agrément / MINESEC *</div><input class="inp" name="numero_agrement" value="{{ old('numero_agrement', $old['numero_agrement'] ?? '') }}" placeholder="ex : 12345/MINESEC/2024" required /></div>
            <div>
              <div class="lbl">Nombre approximatif d'élèves</div>
              <select class="select" name="nb_eleves">
                <option value="">-- Choisir --</option>
                @foreach ([
                  'moins_100' => 'Moins de 100', '100_300' => '100 – 300',
                  '300_500' => '300 – 500', '500_1000' => '500 – 1000', 'plus_1000' => 'Plus de 1000',
                ] as $value => $label)
                  <option value="{{ $value }}" @selected(old('nb_eleves', $old['nb_eleves'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="form-section">Localisation</div>
          <div class="inp-row">
            <div>
              <div class="lbl">Région *</div>
              <select class="select" name="region" required>
                <option value="">-- Choisir une région --</option>
                @foreach ([
                  'centre' => 'Centre', 'littoral' => 'Littoral', 'ouest' => 'Ouest',
                  'nord' => 'Nord', 'adamaoua' => 'Adamaoua', 'est' => 'Est',
                  'sud' => 'Sud', 'sud_ouest' => 'Sud-Ouest', 'nord_ouest' => 'Nord-Ouest', 'extreme_nord' => 'Extrême-Nord',
                ] as $value => $label)
                  <option value="{{ $value }}" @selected(old('region', $old['region'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div><div class="lbl">Ville *</div><input class="inp" name="ville" value="{{ old('ville', $old['ville'] ?? '') }}" placeholder="ex : Yaoundé" required /></div>
          </div>
          <div class="inp-row">
            <div><div class="lbl">Quartier / Arrondissement</div><input class="inp" name="quartier" value="{{ old('quartier', $old['quartier'] ?? '') }}" placeholder="ex : Melen, Biyem-Assi..." /></div>
            <div><div class="lbl">Boîte Postale</div><input class="inp" name="boite_postale" value="{{ old('boite_postale', $old['boite_postale'] ?? '') }}" placeholder="ex : BP 1234 Yaoundé" /></div>
          </div>

          <div style="display:flex;gap:10px;margin-top:10px;">
            <a href="{{ route('landing') }}" class="btn-o" style="flex:0 0 auto;width:auto;padding:10px 20px;">Annuler</a>
            <button type="submit" class="btn-p">Continuer →</button>
          </div>
        </form>
      @endif

      {{-- ───────────────────────── ÉTAPE 2 : RESPONSABLE ───────────────────────── --}}
      @if ($step === 2)
        <div class="form-title">Contact & compte du responsable</div>
        <div class="form-sub">Ces informations serviront à créer le compte qui gérera l'établissement</div>

        <form method="POST" action="{{ route('register.ecole.step2.post') }}">
          @csrf

          <div class="form-section">Coordonnées de l'établissement</div>
          <div class="inp-row">
            <div><div class="lbl">Téléphone principal *</div><input class="inp" name="telephone" value="{{ old('telephone', $old['telephone'] ?? '') }}" placeholder="6XX XXX XXX" required /></div>
            <div><div class="lbl">Email officiel *</div><input class="inp" name="email" value="{{ old('email', $old['email'] ?? '') }}" placeholder="secretariat@lycee.cm" required /></div>
          </div>
          <div class="inp-row">
            <div><div class="lbl">Site web (si disponible)</div><input class="inp" name="site_web" value="{{ old('site_web', $old['site_web'] ?? '') }}" placeholder="https://www.monlycee.cm" /></div>
            <div>
              <div class="lbl">Compte Mobile Money principal</div>
              <select class="select" name="mobile_money_principal">
                <option value="">-- Choisir --</option>
                @foreach (['mtn' => 'MTN Mobile Money', 'orange' => 'Orange Money', 'les_deux' => 'Les deux'] as $value => $label)
                  <option value="{{ $value }}" @selected(old('mobile_money_principal', $old['mobile_money_principal'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="form-section">Compte administrateur principal (responsable)</div>
          <div class="inp-row">
            <div><div class="lbl">Prénom du directeur / responsable *</div><input class="inp" name="resp_prenom" value="{{ old('resp_prenom', $old['resp_prenom'] ?? '') }}" placeholder="ex : Jean-Pierre" required /></div>
            <div><div class="lbl">Nom *</div><input class="inp" name="resp_nom" value="{{ old('resp_nom', $old['resp_nom'] ?? '') }}" placeholder="ex : MVONDO" required /></div>
          </div>
          <div class="inp-row">
            <div><div class="lbl">Téléphone du responsable *</div><input class="inp" name="resp_telephone" value="{{ old('resp_telephone', $old['resp_telephone'] ?? '') }}" placeholder="6XX XXX XXX" required /></div>
            <div><div class="lbl">Email du responsable *</div><input class="inp" name="resp_email" value="{{ old('resp_email', $old['resp_email'] ?? '') }}" placeholder="directeur@lycee.cm" required /></div>
          </div>
          <div class="inp-row">
            <div><div class="lbl">Mot de passe *</div><input class="inp" type="password" name="resp_password" placeholder="Min. 8 caractères" required /></div>
            <div><div class="lbl">Confirmer le mot de passe *</div><input class="inp" type="password" name="resp_password_confirmation" placeholder="Répétez" required /></div>
          </div>

          <div style="display:flex;gap:10px;margin-top:10px;">
            <a href="{{ route('register.ecole.step1') }}" class="btn-o" style="flex:0 0 auto;width:auto;padding:10px 20px;">← Retour</a>
            <button type="submit" class="btn-p">Continuer →</button>
          </div>
        </form>
      @endif

      {{-- ───────────────────────── ÉTAPE 3 : CONFIGURATION / DOCUMENT ───────────────────────── --}}
      @if ($step === 3)
        <div class="form-title">Document justificatif</div>
        <div class="form-sub">Joignez votre agrément ou autorisation d'ouverture</div>

        <form method="POST" action="{{ route('register.ecole.step3.post') }}" enctype="multipart/form-data">
          @csrf

          <div class="form-section">Document justificatif</div>
          <label style="display:block;border:2px dashed #ddd;border-radius:8px;padding:20px;text-align:center;margin-bottom:12px;cursor:pointer;background:#fafafa;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#aaa" stroke-width="2" style="display:block;margin:0 auto 8px;"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            <div style="font-size:13px;font-weight:600;color:#888;">Déposer l'agrément ou l'autorisation d'ouverture</div>
            <div style="font-size:11px;color:#aaa;margin-top:4px;">PDF, JPG, PNG · Max 5 Mo</div>
            <input type="file" name="document_agrement" accept=".pdf,.jpg,.jpeg,.png" required style="margin-top:10px;" />
          </label>

          <div class="form-section">Informations supplémentaires</div>
          <div class="lbl">Décrivez brièvement votre établissement (optionnel)</div>
          <textarea class="textarea" name="description" placeholder="Historique, spécialités, niveaux enseignés, effectifs, particularités...">{{ old('description', $old['description'] ?? '') }}</textarea>

          <div style="display:flex;gap:10px;margin-top:10px;">
            <a href="{{ route('register.ecole.step2') }}" class="btn-o" style="flex:0 0 auto;width:auto;padding:10px 20px;">← Retour</a>
            <button type="submit" class="btn-p">Continuer →</button>
          </div>
        </form>
      @endif

      {{-- ───────────────────────── ÉTAPE 4 : VALIDATION ───────────────────────── --}}
      @if ($step === 4)
        @if (session('success'))
          <div class="form-title">🎉 Demande envoyée avec succès</div>
          <div class="form-sub">Votre code établissement : <strong>{{ session('code_etablissement') }}</strong></div>
          <div style="background:var(--ep-teal-lt);border-radius:8px;padding:14px;margin:16px 0;font-size:13px;color:#085041;">
            Notre équipe vérifiera votre dossier et activera votre compte sous 24 heures ouvrables. Vous serez notifié par SMS et email.
          </div>
          <a href="{{ route('login') }}" class="btn-p" style="display:inline-block;text-decoration:none;text-align:center;">Aller à la connexion</a>
        @else
          <div class="form-title">Récapitulatif</div>
          <div class="form-sub">Vérifiez vos informations avant de soumettre</div>

          <div style="background:#fafafa;border-radius:8px;padding:16px;margin-bottom:16px;font-size:13px;line-height:1.8;">
            <strong>Établissement :</strong> {{ $data['step1']['nom'] ?? '' }}<br>
            <strong>Ville :</strong> {{ $data['step1']['ville'] ?? '' }}<br>
            <strong>Email :</strong> {{ $data['step2']['email'] ?? '' }}<br>
            <strong>Responsable :</strong> {{ ($data['step2']['resp_prenom'] ?? '') . ' ' . ($data['step2']['resp_nom'] ?? '') }}<br>
            <strong>Email responsable :</strong> {{ $data['step2']['resp_email'] ?? '' }}
          </div>

          <form method="POST" action="{{ route('register.ecole.store') }}">
            @csrf
            <div class="check-row"><input type="checkbox" name="certification_accepted" value="1" required /><label>Je certifie que les informations fournies sont exactes et que je suis habilité(e) à représenter cet établissement sur EduPay Cameroun.</label></div>
            <div class="check-row"><input type="checkbox" name="cgu_accepted" value="1" required /><label>J'accepte les <span style="color:var(--ep-teal);">Conditions Générales</span> et la <span style="color:var(--ep-teal);">Politique de confidentialité</span> d'EduPay Cameroun.</label></div>

            <div style="display:flex;gap:10px;margin-top:14px;">
              <a href="{{ route('register.ecole.step3') }}" class="btn-o" style="flex:0 0 auto;width:auto;padding:10px 20px;">← Retour</a>
              <button type="submit" class="btn-p">Soumettre ma demande d'inscription →</button>
            </div>
          </form>
        @endif
      @endif

    </div>
  </div>

  <div style="background:var(--ep-navy);padding:14px 28px;display:flex;justify-content:space-between;align-items:center;margin-top:24px;">
    <div style="font-size:11px;color:rgba(255,255,255,.35);">© 2026 EduPay Cameroun · Onboarding sous 24h garanti</div>
    <div style="display:flex;gap:8px;"><span class="footer-badge">TLS 1.3</span><span class="footer-badge">PCI-DSS</span></div>
  </div>
</div>
@endsection