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
        <div style="flex:1;text-align:center;">
          <div style="width:30px;height:30px;border-radius:50%;background:var(--ep-teal);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;margin:0 auto 5px;">1</div>
          <div style="font-size:11px;font-weight:600;color:var(--ep-teal);">Établissement</div>
        </div>
        <div style="flex:1;height:2px;background:#e0e0e0;margin-top:-16px;"></div>
        <div style="flex:1;text-align:center;">
          <div style="width:30px;height:30px;border-radius:50%;border:2px solid #ddd;color:#ccc;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;margin:0 auto 5px;">2</div>
          <div style="font-size:11px;color:#aaa;">Responsable</div>
        </div>
        <div style="flex:1;height:2px;background:#e0e0e0;margin-top:-16px;"></div>
        <div style="flex:1;text-align:center;">
          <div style="width:30px;height:30px;border-radius:50%;border:2px solid #ddd;color:#ccc;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;margin:0 auto 5px;">3</div>
          <div style="font-size:11px;color:#aaa;">Configuration</div>
        </div>
        <div style="flex:1;height:2px;background:#e0e0e0;margin-top:-16px;"></div>
        <div style="flex:1;text-align:center;">
          <div style="width:30px;height:30px;border-radius:50%;border:2px solid #ddd;color:#ccc;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;margin:0 auto 5px;">4</div>
          <div style="font-size:11px;color:#aaa;">Validation</div>
        </div>
      </div>

      <div class="form-title">Informations de l'établissement</div>
      <div class="form-sub">Renseignez les informations officielles de votre établissement pour commencer l'onboarding EduPay</div>

      <form method="POST" action="{{ route('register.ecole.step1.post') }}">
        @csrf

        <div class="form-section">Identité de l'établissement</div>
        <div class="lbl">Nom officiel de l'établissement *</div>
        <input class="inp" name="nom" placeholder="ex : Lycée Bilingue de Melen" required />

        <div class="inp-row">
          <div>
            <div class="lbl">Type d'établissement *</div>
            <select class="select" name="type" required>
              <option value="">-- Choisir --</option>
              <option>École maternelle</option><option>École primaire</option>
              <option>Collège</option><option>Lycée général</option>
              <option>Lycée technique</option><option>Université</option>
              <option>Institut privé</option><option>Groupe scolaire (multi-niveaux)</option>
            </select>
          </div>
          <div>
            <div class="lbl">Statut juridique *</div>
            <select class="select" name="statut_juridique" required>
              <option value="">-- Choisir --</option>
              <option>Public (État)</option><option>Privé laïc</option>
              <option>Privé confessionnel (catholique)</option>
              <option>Privé confessionnel (protestant)</option>
              <option>Privé confessionnel (islamique)</option>
            </select>
          </div>
        </div>

        <div class="inp-row">
          <div><div class="lbl">Numéro d'agrément / MINESEC *</div><input class="inp" name="agrement" placeholder="ex : 12345/MINESEC/2024" required /></div>
          <div>
            <div class="lbl">Nombre approximatif d'élèves</div>
            <select class="select" name="nb_eleves">
              <option>Moins de 100</option><option>100 – 300</option>
              <option>300 – 500</option><option selected>500 – 1000</option><option>Plus de 1000</option>
            </select>
          </div>
        </div>

        <div class="form-section">Localisation</div>
        <div class="inp-row">
          <div>
            <div class="lbl">Région *</div>
            <select class="select" name="region" required>
              <option value="">-- Choisir une région --</option>
              <option>Centre</option><option>Littoral</option><option>Ouest</option>
              <option>Nord</option><option>Adamaoua</option><option>Est</option>
              <option>Sud</option><option>Sud-Ouest</option><option>Nord-Ouest</option><option>Extrême-Nord</option>
            </select>
          </div>
          <div><div class="lbl">Ville *</div><input class="inp" name="ville" placeholder="ex : Yaoundé" required /></div>
        </div>
        <div class="inp-row">
          <div><div class="lbl">Quartier / Arrondissement</div><input class="inp" name="quartier" placeholder="ex : Melen, Biyem-Assi..." /></div>
          <div><div class="lbl">Boîte Postale</div><input class="inp" name="bp" placeholder="ex : BP 1234 Yaoundé" /></div>
        </div>

        <div class="form-section">Coordonnées de contact</div>
        <div class="inp-row">
          <div><div class="lbl">Téléphone principal *</div><input class="inp" name="telephone" placeholder="6XX XXX XXX" required /></div>
          <div><div class="lbl">Email officiel *</div><input class="inp" name="email" placeholder="secretariat@lycee.cm" required /></div>
        </div>
        <div class="inp-row">
          <div><div class="lbl">Site web (si disponible)</div><input class="inp" name="site_web" placeholder="https://www.monlycee.cm" /></div>
          <div>
            <div class="lbl">Compte Mobile Money principal</div>
            <select class="select" name="mobile_money">
              <option value="">-- Choisir --</option>
              <option>MTN Mobile Money</option><option>Orange Money</option><option>Les deux</option>
            </select>
          </div>
        </div>

        <div class="form-section">Compte administrateur principal</div>
        <div class="inp-row">
          <div><div class="lbl">Prénom du directeur / responsable *</div><input class="inp" name="directeur_prenom" placeholder="ex : Jean-Pierre" required /></div>
          <div><div class="lbl">Nom *</div><input class="inp" name="directeur_nom" placeholder="ex : MVONDO" required /></div>
        </div>
        <div class="inp-row">
          <div><div class="lbl">Téléphone du responsable *</div><input class="inp" name="directeur_telephone" placeholder="6XX XXX XXX" required /></div>
          <div><div class="lbl">Email du responsable *</div><input class="inp" name="directeur_email" placeholder="directeur@lycee.cm" required /></div>
        </div>
        <div class="inp-row">
          <div><div class="lbl">Mot de passe *</div><input class="inp" type="password" name="password" placeholder="Min. 8 caractères" required /></div>
          <div><div class="lbl">Confirmer le mot de passe *</div><input class="inp" type="password" name="password_confirmation" placeholder="Répétez" required /></div>
        </div>

        <div class="form-section">Document justificatif</div>
        <div style="border:2px dashed #ddd;border-radius:8px;padding:20px;text-align:center;margin-bottom:12px;cursor:pointer;background:#fafafa;">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#aaa" stroke-width="2" style="display:block;margin:0 auto 8px;"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          <div style="font-size:13px;font-weight:600;color:#888;">Déposer l'agrément ou l'autorisation d'ouverture</div>
          <div style="font-size:11px;color:#aaa;margin-top:4px;">PDF, JPG, PNG · Max 5 Mo</div>
        </div>

        <div class="form-section">Informations supplémentaires</div>
        <div class="lbl">Décrivez brièvement votre établissement (optionnel)</div>
        <textarea class="textarea" name="description" placeholder="Historique, spécialités, niveaux enseignés, effectifs, particularités..."></textarea>

        <div class="check-row"><input type="checkbox" required /><label>Je certifie que les informations fournies sont exactes et que je suis habilité(e) à représenter cet établissement sur EduPay Cameroun.</label></div>
        <div class="check-row"><input type="checkbox" required /><label>J'accepte les <span style="color:var(--ep-teal);">Conditions Générales</span> et la <span style="color:var(--ep-teal);">Politique de confidentialité</span> d'EduPay Cameroun.</label></div>

        <div style="background:var(--ep-teal-lt);border-radius:8px;padding:14px;margin-bottom:16px;font-size:12px;color:#085041;display:flex;gap:10px;align-items:flex-start;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <div><strong>Activation sous 24h :</strong> après soumission, notre équipe vérifiera votre dossier et activera votre compte sous 24 heures ouvrables. Vous serez notifié par SMS et email.</div>
        </div>

        <div style="display:flex;gap:10px;">
          <a href="{{ route('landing') }}" class="btn-o" style="flex:0 0 auto;width:auto;padding:10px 20px;">Annuler</a>
          <button type="submit" class="btn-p">Soumettre ma demande d'inscription →</button>
        </div>
      </form>
    </div>
  </div>

  <div style="background:var(--ep-navy);padding:14px 28px;display:flex;justify-content:space-between;align-items:center;margin-top:24px;">
    <div style="font-size:11px;color:rgba(255,255,255,.35);">© 2026 EduPay Cameroun · Onboarding sous 24h garanti</div>
    <div style="display:flex;gap:8px;"><span class="footer-badge">TLS 1.3</span><span class="footer-badge">PCI-DSS</span></div>
  </div>
</div>
@endsection
