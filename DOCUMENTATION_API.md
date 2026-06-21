# 📡 Documentation API — EduPay Cameroun

> Version `v1.0` — Juin 2026  
> Référence CDC : `CDC-EDUPAY-CM-2026-001`  
> Framework : Laravel 13 / PHP 8.5 · Auth : Laravel Sanctum

---

## 📋 Table des matières

1. [Vue d'ensemble](#-vue-densemble)
2. [Authentification](#-authentification)
3. [Module Payeur](#-module-payeur)
4. [Module Établissement](#-module-établissement)
5. [Module Super Admin](#-module-super-admin)
6. [Intégrations paiement](#-intégrations-paiement)
7. [Notifications & webhooks](#-notifications--webhooks)
8. [Codes d'erreur](#-codes-derreur)
9. [Modèles de données](#-modèles-de-données)

---

## 🌐 Vue d'ensemble

### Base URLs

| Environnement | URL |
|---------------|-----|
| Développement local | `http://localhost:8000` |
| Production | `https://edupay.cm` |
| API prefix (web) | `/api/v1` |
| Admin prefix | `/admin-ep2026` |

### Headers obligatoires (API)

```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
```

### Guards Laravel

| Guard | Modèle | Usage |
|-------|--------|-------|
| `web` | `User` | Parents, élèves, staff école |
| `admin` | `Admin` | Super administrateur uniquement |

### Rôles (Spatie Permission)

| Rôle | Accès |
|------|-------|
| `super_admin` | Toute la plateforme via guard `admin` |
| `directeur` | Back-office école complet |
| `comptable` | Lecture + gestion paiements école |
| `caissier` | Saisie paiements uniquement |
| `parent` | Dashboard payeur (enfants) |
| `eleve/etudiant` | Dashboard payeur (soi-même) |
| 


### Format de réponse standard

```json
{
  "success": true,
  "message": "Opération réussie",
  "data": { ... },
  "meta": {
    "timestamp": "2026-06-01T10:30:00+01:00",
    "version": "1.0"
  }
}
```

**Réponse paginée :**
```json
{
  "success": true,
  "data": [ ... ],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 98
  }
}
```

---

## 🔐 Authentification

### Routes publiques (pas de token requis)

#### `POST /api/v1/auth/register`
Inscription d'un nouvel utilisateur (parent ou élève/étudiant).

**Body :**
```json
{
  "profil": "parent",
  "prenom": "Marie",
  "nom": "FONO",
  "email": "marie.fono@gmail.com",
  "telephone": "691234567",
  "password": "MonMotDePasse1!",
  "password_confirmation": "MonMotDePasse1!"
}
```
`profil` accepte : `parent` | `eleve` | `etudiant`

**Réponse 201 :**
```json
{
  "success": true,
  "message": "Compte créé. Un code OTP a été envoyé par SMS.",
  "data": {
    "user_id": 42,
    "email": "marie.fono@gmail.com",
    "telephone": "691234567",
    "role": "parent"
  }
}
```

---

#### `POST /api/v1/auth/verify-otp`
Vérification du code OTP reçu par SMS.

**Body :**
```json
{
  "telephone": "691234567",
  "code": "482910"
}
```

**Réponse 200 :**
```json
{
  "success": true,
  "message": "Compte vérifié avec succès.",
  "data": {
    "token": "eyJ0eXAiOiJKV1Qi...",
    "user": { "id": 42, "nom": "FONO", "prenom": "Marie", "role": "parent" }
  }
}
```

---

#### `POST /api/v1/auth/login`
Connexion par email/téléphone + mot de passe.

**Body :**
```json
{
  "identifiant": "marie.fono@gmail.com",
  "password": "MonMotDePasse1!"
}
```
`identifiant` accepte email ou numéro de téléphone.

**Réponse 200 :**
```json
{
  "success": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1Qi...",
    "user": {
      "id": 42,
      "nom": "FONO",
      "prenom": "Marie",
      "email": "marie.fono@gmail.com",
      "telephone": "691234567",
      "role": "parent"
    }
  }
}
```

---

#### `POST /api/v1/auth/resend-otp`
Renvoyer un OTP SMS.

**Body :**
```json
{ "telephone": "691234567" }
```

---

#### `POST /api/v1/auth/logout`
🔒 *Token requis.*

Révoque le token Sanctum en cours.

---

#### `POST /api/v1/auth/forgot-password`
Demande de réinitialisation de mot de passe par email.

**Body :**
```json
{ "email": "marie.fono@gmail.com" }
```

---

#### `POST /api/v1/auth/reset-password`
Réinitialisation avec le token reçu par email.

**Body :**
```json
{
  "token": "abc123...",
  "email": "marie.fono@gmail.com",
  "password": "NouveauPass1!",
  "password_confirmation": "NouveauPass1!"
}
```

---

### Auth Super Admin (guard `admin`)

#### `POST /admin-ep2026/login`
URL cachée — 2FA obligatoire.

**Étape 1 — Email + mot de passe :**
```json
{
  "email": "admin@edupay.cm",
  "password": "Admin2026!"
}
```

**Réponse :** `{ "requires_2fa": true, "session_token": "..." }`

**Étape 2 — Code 2FA (Google Authenticator ou SMS) :**
```json
{
  "session_token": "...",
  "otp_code": "482910"
}
```

**Réponse 200 :** Token admin Sanctum.

---

## 👨‍👩‍👧 Module Payeur

> Préfixe : `/api/v1/payeur/`  
> 🔒 Token `web` + rôle `parent` ou `eleve` requis sur toutes les routes.

---

### Onboarding — Rattachement établissement

#### `GET /api/v1/etablissements/search?q={terme}`
Rechercher un établissement partenaire.

**Paramètres :** `q` (min 2 caractères), `type` (optionnel : `maternelle|primaire|secondaire|universite`)

**Réponse 200 :**
```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "nom": "Lycée de Melen",
      "ville": "Yaoundé",
      "type": "secondaire",
      "code": "LM-2026"
    }
  ]
}
```

---

#### `POST /api/v1/payeur/apprenants`
Rattacher un enfant (ou soi-même si élève) à un établissement.

**Body :**
```json
{
  "etablissement_id": 5,
  "prenom": "Brice",
  "nom": "FONO",
  "matricule": "EP-1184",
  "classe": "3ème",
  "code_etablissement": "LM-2026"
}
```

**Réponse 201 :**
```json
{
  "success": true,
  "data": {
    "apprenant_id": 88,
    "nom_complet": "FONO Brice",
    "etablissement": "Lycée de Melen",
    "classe": "3ème",
    "statut_paiement": "en_attente"
  }
}
```

---

#### `DELETE /api/v1/payeur/apprenants/{id}`
Détacher un apprenant du compte payeur.

---

### Dashboard & suivi

#### `GET /api/v1/payeur/dashboard`
Vue synthétique du tableau de bord.

**Réponse 200 :**
```json
{
  "success": true,
  "data": {
    "vue": "famille",
    "utilisateur": { "prenom": "Marie", "nom": "FONO", "ville": "Yaoundé" },
    "paiements_en_attente": 2,
    "apprenants": [
      {
        "id": 88,
        "nom": "FONO Brice",
        "etablissement": "Lycée de Melen",
        "classe": "3ème",
        "statut_paiement": "partiel",
        "montant_du": 26250,
        "prochaine_echeance": "2026-07-15"
      },
      {
        "id": 91,
        "nom": "FONO Chloé",
        "etablissement": "École Primaire NBC",
        "classe": "CM2",
        "statut_paiement": "impaye",
        "montant_du": 45000,
        "prochaine_echeance": "2026-06-30"
      }
    ]
  }
}
```

---

#### `GET /api/v1/payeur/frais/{apprenant_id}`
Détail des frais dus pour un apprenant.

**Réponse 200 :**
```json
{
  "success": true,
  "data": {
    "apprenant": { "nom": "FONO Brice", "classe": "3ème" },
    "annee_scolaire": "2025-2026",
    "categories": [
      {
        "id": 3,
        "libelle": "Frais de scolarité",
        "montant_total": 52500,
        "montant_paye": 26250,
        "solde": 26250,
        "statut": "partiel"
      },
      {
        "id": 4,
        "libelle": "Inscription",
        "montant_total": 5000,
        "montant_paye": 5000,
        "solde": 0,
        "statut": "paye"
      }
    ],
    "echeanciers": [
      {
        "tranche": 2,
        "montant": 26250,
        "date_limite": "2026-07-15",
        "statut": "en_attente"
      }
    ]
  }
}
```

---

#### `GET /api/v1/payeur/historique`
Historique complet des paiements.

**Paramètres :** `page`, `per_page`, `apprenant_id` (filtre optionnel)

**Réponse 200 :**
```json
{
  "success": true,
  "data": [
    {
      "id": 201,
      "reference": "EP-2026-0004891",
      "apprenant": "FONO Brice",
      "libelle": "Scolarité T1",
      "montant": 26250,
      "mode_paiement": "mtn_momo",
      "statut": "valide",
      "date": "2026-03-15T09:42:00+01:00",
      "recu_url": "/api/v1/payeur/recu/201"
    }
  ],
  "pagination": { "current_page": 1, "last_page": 3, "total": 52 }
}
```

---

#### `GET /api/v1/payeur/recu/{paiement_id}`
Télécharger le reçu PDF d'un paiement.

**Réponse :** Fichier PDF (`application/pdf`)

---

#### `GET /api/v1/payeur/certificats`
Liste des attestations de paiement disponibles.

---

### Paiement

#### `POST /api/v1/payeur/paiement/initier`
Initier un paiement.

**Body :**
```json
{
  "apprenant_id": 88,
  "categorie_frais_id": 3,
  "echeancier_id": 7,
  "mode_paiement": "mtn_momo",
  "type_paiement": "tranche",
  "montant": 26250,
  "telephone_momo": "677000000"
}
```
`mode_paiement` : `mtn_momo` | `orange_money` | `carte_bancaire`  
`type_paiement` : `integral` | `tranche`

**Réponse 200 :**
```json
{
  "success": true,
  "message": "Paiement initié. Confirmez sur votre téléphone.",
  "data": {
    "paiement_id": 312,
    "reference_edupay": "EP-2026-0005012",
    "reference_operateur": "MTN-REF-20260601-ABC123",
    "montant": 26250,
    "mode": "mtn_momo",
    "statut": "en_attente",
    "expire_at": "2026-06-01T10:45:00+01:00"
  }
}
```

---

#### `GET /api/v1/payeur/paiement/{paiement_id}/statut`
Vérifier le statut d'un paiement en cours.

**Réponse 200 :**
```json
{
  "success": true,
  "data": {
    "paiement_id": 312,
    "statut": "valide",
    "message": "Paiement confirmé et reçu envoyé par email.",
    "recu_url": "/api/v1/payeur/recu/312"
  }
}
```
`statut` : `en_attente` | `valide` | `echoue` | `annule` | `rembourse`

---

### Réclamations

#### `GET /api/v1/payeur/reclamations`
Lister ses réclamations.

#### `POST /api/v1/payeur/reclamations`
Ouvrir une nouvelle réclamation.

**Body :**
```json
{
  "paiement_id": 312,
  "type": "paiement_non_credite",
  "description": "Le paiement a été débité sur mon téléphone mais non confirmé sur EduPay."
}
```
`type` : `paiement_non_credite` | `montant_incorrect` | `double_paiement` | `autre`

#### `GET /api/v1/payeur/reclamations/{id}`
Détail et suivi d'une réclamation.

---

### Profil

#### `GET /api/v1/payeur/profil`
Récupérer son profil.

#### `PUT /api/v1/payeur/profil`
Mettre à jour ses informations (nom, téléphone, email, mot de passe).

---

## 🏫 Module Établissement

> Préfixe : `/api/v1/etablissement/`  
> 🔒 Token `web` + rôle `directeur` | `comptable` | `caissier` requis.

---

### Inscription école

#### `POST /api/v1/etablissements/register`
Route publique — Inscription d'un établissement.

**Body :**
```json
{
  "nom": "Lycée de Melen",
  "type": "secondaire",
  "region": "Centre",
  "ville": "Yaoundé",
  "quartier": "Melen",
  "telephone": "222214567",
  "email": "direction@lyceemelen.cm",
  "site_web": "https://lyceemelen.cm",
  "admin_prenom": "Jean",
  "admin_nom": "ESSOMBA",
  "admin_email": "j.essomba@lyceemelen.cm",
  "admin_password": "MonPass2026!",
  "numero_agrement": "MINESEC/2018/045",
  "description": "Lycée public fondé en 1985..."
}
```

**Réponse 201 :**
```json
{
  "success": true,
  "message": "Demande envoyée. Votre compte sera activé sous 24-48h.",
  "data": { "etablissement_id": 12, "statut": "en_attente_validation" }
}
```

---

### Dashboard

#### `GET /api/v1/etablissement/dashboard`
KPIs financiers en temps réel.

**Réponse 200 :**
```json
{
  "success": true,
  "data": {
    "encaissements_jour": 1250000,
    "encaissements_mois": 18400000,
    "taux_recouvrement": 73.5,
    "total_apprenants": 412,
    "payants": 302,
    "partiels": 23,
    "impayes": 87,
    "transactions_recentes": [ ... ]
  }
}
```

---

### Apprenants

#### `GET /api/v1/etablissement/apprenants`
Annuaire complet des apprenants.

**Paramètres :** `q` (recherche nom/matricule), `statut_paiement`, `classe`, `page`

#### `POST /api/v1/etablissement/apprenants`
Ajouter manuellement un apprenant.

#### `PUT /api/v1/etablissement/apprenants/{id}`
Modifier un apprenant.

#### `DELETE /api/v1/etablissement/apprenants/{id}`
Supprimer un apprenant.

#### `POST /api/v1/etablissement/apprenants/import`
Import CSV/Excel de la liste des apprenants.

**Content-Type :** `multipart/form-data`  
**Body :** `file` (CSV ou XLSX), `annee_scolaire`

---

### Frais & Échéanciers

#### `GET /api/v1/etablissement/categories-frais`
Lister toutes les catégories de frais.

#### `POST /api/v1/etablissement/categories-frais`
Créer une catégorie de frais.

**Body :**
```json
{
  "libelle": "Frais de scolarité",
  "montant": 52500,
  "annee_scolaire": "2025-2026",
  "obligatoire": true,
  "description": "Frais annuels de scolarité 3ème"
}
```

#### `PUT /api/v1/etablissement/categories-frais/{id}`
Modifier une catégorie.

#### `DELETE /api/v1/etablissement/categories-frais/{id}`
Supprimer une catégorie (si aucun paiement lié).

---

#### `GET /api/v1/etablissement/echeanciers`
Lister les échéanciers.

#### `POST /api/v1/etablissement/echeanciers`
Créer un échéancier de paiement.

**Body :**
```json
{
  "categorie_frais_id": 3,
  "tranches": [
    { "numero": 1, "pourcentage": 50, "date_limite": "2025-10-15" },
    { "numero": 2, "pourcentage": 50, "date_limite": "2026-02-28" }
  ]
}
```

---

### Impayés & Relances

#### `GET /api/v1/etablissement/impayes`
Liste des apprenants avec solde impayé.

**Paramètres :** `categorie_id`, `classe`, `date_echeance_avant`

**Réponse 200 :**
```json
{
  "success": true,
  "data": [
    {
      "apprenant_id": 91,
      "nom": "FONO Chloé",
      "classe": "CM2",
      "montant_du": 45000,
      "dernier_paiement": null,
      "derniere_relance": "2026-05-20T08:00:00+01:00",
      "telephone_parent": "695000001"
    }
  ]
}
```

#### `POST /api/v1/etablissement/impayes/relance-groupee`
Lancer une relance SMS groupée.

**Body :**
```json
{
  "filtre": {
    "categorie_id": 3,
    "date_echeance_avant": "2026-06-30"
  },
  "message": "Rappel : les frais de scolarité de {prenom} sont en attente. Payez sur edupay.cm"
}
```

**Réponse 200 :**
```json
{
  "success": true,
  "data": {
    "sms_envoyes": 87,
    "job_id": "relance_2026_06_01_001"
  }
}
```

#### `POST /api/v1/etablissement/impayes/{apprenant_id}/relance`
Relance individuelle manuelle.

---

### Remboursements

#### `GET /api/v1/etablissement/remboursements`
Lister les demandes de remboursement.

#### `POST /api/v1/etablissement/remboursements/{paiement_id}/approuver`
Approuver un remboursement.

#### `POST /api/v1/etablissement/remboursements/{paiement_id}/rejeter`
Rejeter avec motif.

---

### Rapports

#### `GET /api/v1/etablissement/rapports`
Générer un rapport financier.

**Paramètres :** `periode` (`jour|semaine|mois|annee`), `date_debut`, `date_fin`, `format` (`json|pdf|excel`)

**Si `format=pdf` ou `format=excel` :** Retourne le fichier binaire.

---

### Équipe interne

#### `GET /api/v1/etablissement/equipe`
Liste des utilisateurs internes.

#### `POST /api/v1/etablissement/equipe`
Ajouter un membre (comptable, caissier).

**Body :**
```json
{
  "prenom": "Paul",
  "nom": "ATEBA",
  "email": "p.ateba@lyceemelen.cm",
  "role": "comptable"
}
```
`role` : `directeur` | `comptable` | `caissier`

#### `DELETE /api/v1/etablissement/equipe/{user_id}`
Révoquer l'accès d'un membre.

---

### Multi-sites

#### `GET /api/v1/etablissement/sites`
Lister les sous-sites rattachés.

#### `POST /api/v1/etablissement/sites`
Ajouter un site secondaire.

---

### Paramètres

#### `GET /api/v1/etablissement/parametres`
Récupérer les paramètres de l'établissement.

#### `PUT /api/v1/etablissement/parametres`
Mettre à jour (logo, horaires, notifications…).

---

## 🛡️ Module Super Admin

> Préfixe : `/admin-ep2026/api/`  
> 🔒 Guard `admin` + 2FA requis sur toutes les routes.

---

### Vue globale

#### `GET /admin-ep2026/api/dashboard`
KPIs globaux de la plateforme.

**Réponse 200 :**
```json
{
  "success": true,
  "data": {
    "periode": "Mars 2026",
    "volume_transactions": 124800000,
    "nombre_transactions": 2847,
    "etablissements_actifs": 38,
    "nouveaux_etablissements_mois": 5,
    "commissions_percues": 624000,
    "taux_commission_moyen": 0.5,
    "repartition_modes": {
      "mtn_momo": 58.4,
      "orange_money": 31.2,
      "carte_bancaire": 10.4
    }
  }
}
```

---

### Établissements

#### `GET /admin-ep2026/api/etablissements`
Liste tous les établissements.

**Paramètres :** `statut` (`actif|en_attente|suspendu`), `type`, `region`, `q`

#### `POST /admin-ep2026/api/etablissements/{id}/activer`
Activer un établissement après validation du dossier.

#### `POST /admin-ep2026/api/etablissements/{id}/suspendre`
Suspendre un établissement avec motif.

**Body :** `{ "motif": "Non-conformité réglementaire" }`

#### `DELETE /admin-ep2026/api/etablissements/{id}`
Supprimer définitivement un établissement (irréversible).

#### `PUT /admin-ep2026/api/etablissements/{id}/commission`
Modifier le taux de commission.

**Body :**
```json
{
  "taux": 0.5,
  "type": "pourcentage",
  "commentaire": "Taux standard — établissement secondaire"
}
```

---

### Transactions

#### `GET /admin-ep2026/api/transactions`
Vue agrégée de toutes les transactions.

**Paramètres :** `etablissement_id`, `mode_paiement`, `statut`, `date_debut`, `date_fin`, `page`

#### `GET /admin-ep2026/api/transactions/{id}`
Détail complet d'une transaction avec logs opérateur.

---

### Commissions

#### `GET /admin-ep2026/api/commissions`
Tableau de bord des commissions perçues.

#### `GET /admin-ep2026/api/commissions/configuration`
Lire la configuration globale des taux.

#### `PUT /admin-ep2026/api/commissions/configuration`
Modifier les taux par défaut.

**Body :**
```json
{
  "taux_defaut": 0.5,
  "taux_minimum_fcfa": 50,
  "taux_maximum_fcfa": 5000
}
```

---

### Réclamations

#### `GET /admin-ep2026/api/reclamations`
Toutes les réclamations de la plateforme.

**Paramètres :** `statut` (`ouverte|en_cours|resolue|fermee`), `type`, `etablissement_id`

#### `PUT /admin-ep2026/api/reclamations/{id}`
Mettre à jour le statut d'une réclamation.

**Body :**
```json
{
  "statut": "resolue",
  "resolution": "Remboursement effectué le 2026-06-01."
}
```

---

### Logs sécurité

#### `GET /admin-ep2026/api/logs`
Journal des actions sensibles.

**Paramètres :** `user_id`, `action`, `date_debut`, `date_fin`, `page`

**Réponse 200 :**
```json
{
  "success": true,
  "data": [
    {
      "id": 8821,
      "user_type": "admin",
      "user_id": 1,
      "action": "etablissement.suspendu",
      "details": "Établissement ID 17 suspendu : motif fraude.",
      "ip": "10.0.0.5",
      "user_agent": "Mozilla/5.0...",
      "created_at": "2026-06-01T14:22:00+01:00"
    }
  ]
}
```

---

### Exports réglementaires

#### `POST /admin-ep2026/api/exports/cobac`
Générer le rapport COBAC.

**Body :** `{ "periode": "2026-05", "format": "pdf" }`

#### `POST /admin-ep2026/api/exports/beac`
Générer le rapport BEAC.

#### `GET /admin-ep2026/api/exports`
Lister les exports déjà générés.

---

### Paramètres système

#### `GET /admin-ep2026/api/parametres`
Lire la configuration globale.

#### `PUT /admin-ep2026/api/parametres`
Modifier les paramètres système.

**Body :**
```json
{
  "modes_paiement_actifs": ["mtn_momo", "orange_money", "carte_bancaire"],
  "langues_disponibles": ["fr", "en"],
  "maintenance_mode": false,
  "max_tentatives_paiement": 3
}
```

---

## 💳 Intégrations paiement

### MTN Mobile Money (Cameroun)

**Service Laravel :** `app/Services/MtnMomoService.php`

```php
// Initier une collecte (Request to Pay)
$result = $mtnService->requestToPay([
    'amount'     => 26250,
    'currency'   => 'XAF',
    'externalId' => 'EP-2026-0005012',
    'payer'      => ['partyIdType' => 'MSISDN', 'partyId' => '677000000'],
    'payerMessage' => 'Frais scolarite Lycee de Melen',
    'payeeNote'    => 'EduPay Cameroun',
]);
```

**Callback webhook :**

```
POST /webhooks/mtn-momo/callback
```

**Body reçu :**
```json
{
  "externalId": "EP-2026-0005012",
  "status": "SUCCESSFUL",
  "financialTransactionId": "MTN-FIN-ABC123",
  "amount": "26250",
  "currency": "XAF"
}
```

`status` : `SUCCESSFUL` | `FAILED` | `PENDING`

---

### Orange Money (Cameroun)

**Service Laravel :** `app/Services/OrangeMoneyService.php`

**Callback webhook :**
```
POST /webhooks/orange-money/callback
```

---

### CinetPay (Carte Visa/Mastercard)

**Service Laravel :** `app/Services/CinetPayService.php`

**Initialisation paiement :**
```php
$result = $cinetPayService->initPayment([
    'transaction_id' => 'EP-2026-0005012',
    'amount'         => 26250,
    'currency'       => 'XAF',
    'description'    => 'Frais scolarite EduPay',
    'notify_url'     => 'https://edupay.cm/webhooks/cinetpay',
    'return_url'     => 'https://edupay.cm/paiement/confirmation',
    'customer_name'  => 'FONO Marie',
    'customer_email' => 'marie.fono@gmail.com',
]);
```

**Callback webhook :**
```
POST /webhooks/cinetpay/callback
```

---

### Sécurité des webhooks

Toutes les routes webhook vérifient la signature HMAC avant traitement :

```php
// Vérification HMAC dans le middleware WebhookSignature
$signature = hash_hmac('sha256', $request->getContent(), config('services.mtn.webhook_secret'));
if (!hash_equals($signature, $request->header('X-Signature'))) {
    abort(401, 'Signature invalide');
}
```

---

## 📩 Notifications & webhooks

### Routes webhook (publiques, signature vérifiée)

| Route | Opérateur | Description |
|-------|-----------|-------------|
| `POST /webhooks/mtn-momo/callback` | MTN | Confirmation paiement Mobile Money |
| `POST /webhooks/orange-money/callback` | Orange | Confirmation paiement Orange Money |
| `POST /webhooks/cinetpay/callback` | CinetPay | Confirmation paiement carte bancaire |

### Jobs asynchrones (Laravel Queue)

| Job | Déclencheur | Action |
|-----|------------|--------|
| `SendPaymentNotification` | Paiement validé | Email + SMS au payeur avec reçu |
| `GenerateRecuPdf` | Paiement validé | Génération PDF DomPDF + stockage |
| `SendSmsRelance` | Relance manuelle ou auto | SMS aux familles en impayé |

### Templates SMS

```
✅ Paiement confirmé :
"EduPay: Paiement de {montant} FCFA recu pour {apprenant} ({etablissement}).
Ref: {reference}. Recu sur {email}."

⏰ Rappel échéance :
"EduPay: Rappel - La tranche {numero} de {montant} FCFA pour {apprenant}
est due avant le {date_limite}. Payez sur edupay.cm"

❌ Impayé :
"EduPay: Les frais de {apprenant} ({montant} FCFA) sont toujours impayés.
Contactez {etablissement} ou payez sur edupay.cm"
```

---

## ⚠️ Codes d'erreur

| Code HTTP | Code interne | Signification |
|-----------|-------------|---------------|
| 200 | `OK` | Succès |
| 201 | `CREATED` | Ressource créée |
| 400 | `VALIDATION_ERROR` | Données invalides |
| 401 | `UNAUTHORIZED` | Token manquant ou expiré |
| 403 | `FORBIDDEN` | Rôle insuffisant |
| 404 | `NOT_FOUND` | Ressource introuvable |
| 409 | `CONFLICT` | Doublon (email déjà utilisé, etc.) |
| 422 | `PAYMENT_FAILED` | Paiement refusé par l'opérateur |
| 429 | `TOO_MANY_REQUESTS` | Rate limiting |
| 500 | `SERVER_ERROR` | Erreur serveur interne |
| 503 | `SERVICE_UNAVAILABLE` | Opérateur paiement indisponible |

**Format erreur standard :**
```json
{
  "success": false,
  "message": "Les données fournies sont invalides.",
  "errors": {
    "telephone": ["Le numéro de téléphone doit comporter 9 chiffres."],
    "password": ["Le mot de passe doit contenir au moins 8 caractères."]
  }
}
```

---

## 📊 Modèles de données

### User

```php
// Champs : id, prenom, nom, email, telephone, password, role,
//          email_verified_at, telephone_verified_at, statut,
//          created_at, updated_at
// Relations : apprenants(), paiements(), reclamations()
// Rôles Spatie : parent, eleve, directeur, comptable, caissier
```

### Etablissement

```php
// Champs : id, nom, type (enum), region, ville, quartier,
//          telephone, email, site_web, logo, numero_agrement,
//          statut (enum: en_attente|actif|suspendu),
//          taux_commission, description, created_at, updated_at
// Relations : apprenants(), categoriesFrais(), echeanciers(),
//             paiements(), equipe(), sites()
```

### Paiement

```php
// Champs : id, reference (unique EP-YYYY-XXXXXXX), user_id,
//          apprenant_id, categorie_frais_id, echeancier_id,
//          montant, mode_paiement (enum), type_paiement (enum),
//          statut (enum: en_attente|valide|echoue|annule|rembourse),
//          telephone_momo, transaction_id, recu_pdf_path,
//          created_at, updated_at
// Relations : user(), apprenant(), transaction(), commission()
```

### Transaction

```php
// Champs : id, paiement_id, reference_operateur,
//          reference_financiere, statut_operateur,
//          payload_request (json), payload_response (json),
//          callback_received_at, created_at, updated_at
```

### AuditLog

```php
// Champs : id, user_type (admin|user), user_id, action,
//          details (text), ip_address, user_agent,
//          created_at
// Toujours en lecture seule — jamais de mise à jour ni suppression
```

---

## 🧪 Tests

### Lancer les tests

```bash
# Tous les tests
php artisan test

# Avec couverture de code
php artisan test --coverage

# Un groupe spécifique
php artisan test --filter PaiementTest
php artisan test --filter AuthTest
```

### Structure des tests

```
tests/
├── Unit/
│   ├── MtnMomoServiceTest.php
│   ├── PdfServiceTest.php
│   └── CommissionCalculTest.php
└── Feature/
    ├── Auth/
    │   ├── RegisterTest.php
    │   └── LoginOtpTest.php
    ├── Payeur/
    │   ├── DashboardTest.php
    │   └── PaiementTest.php
    ├── Etablissement/
    │   ├── FraisTest.php
    │   └── ImpayesTest.php
    └── Admin/
        ├── EtablissementGestionTest.php
        └── TransactionSupervisionTest.php
```

---

*Documentation maintenue par MEKONTSO OLIVIER STEVE — EduPay Cameroun — ESTLC Ambam — Juin 2026*
