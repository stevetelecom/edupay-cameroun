# 🏦 EduPay Cameroun

> **Plateforme de Paiement Électronique des Frais de Scolarité**  
> Référence : `CDC-EDUPAY-CM-2026-001` — Version `v2.0` — Juin 2026

![EduPay](https://img.shields.io/badge/EduPay-Cameroun-0D9E75?style=for-the-badge)
![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?style=for-the-badge&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql)
![Status](https://img.shields.io/badge/Status-En%20développement-orange?style=for-the-badge)
![Branch](https://img.shields.io/badge/Branche%20active-develop-blue?style=for-the-badge)

---

## 📋 Table des matières

1. [Présentation du projet](#-présentation-du-projet)
2. [Pages de la maquette](#-pages-de-la-maquette)
3. [Architecture technique](#️-architecture-technique)
4. [Structure du projet](#-structure-du-projet)
5. [Workflow Git solo](#-workflow-git-solo)
6. [Installation locale](#-installation-locale)
7. [Base de données](#️-base-de-données)
8. [Variables d'environnement](#-variables-denvironnement)
9. [Conventions de code](#-conventions-de-code)
10. [Planning de développement](#-planning-de-développement)

---

## 🎯 Présentation du projet

**EduPay Cameroun** est une plateforme web de paiement électronique des frais de scolarité, conçue pour les réalités camerounaises. Elle connecte les établissements scolaires aux parents et étudiants via **MTN Mobile Money** et **Orange Money**, agrégés via **AangaraaPay**.

> **Développeur solo :** MEKONTSO OLIVIER STEVE  
> **Établissement :** ESTLC Ambam — GSI Niveau 3/4  
> **Branche de développement :** `develop` → merge final sur `main`

### Problème résolu

La quasi-totalité des établissements scolaires camerounais gère encore les paiements **en espèces, manuellement** :
- Détournements et fraudes
- Files d'attente interminables
- Absence de traçabilité
- Perte de reçus physiques

### 3 modules principaux

```
┌────────────────────────────────────────────────────────────────┐
│  MODULE PAYEUR                 │  MODULE ÉTABLISSEMENT         │
│  (Parents / Élèves)            │  (Directeur / Comptable)      │
│  - Inscription multi-profil    │  - Config frais & échéanciers │
│  - Paiement MTN MoMo / Orange Money   │  - Annuaire des apprenants    │
│  - Paiement fractionné 2–3×    │  - Suivi impayés & relances   │
│  - Historique & reçus PDF      │  - Rapports PDF & Excel       │
│  - Réclamations en ligne       │  - Multi-sites & équipe       │
├────────────────────────────────────────────────────────────────┤
│  MODULE SUPER ADMIN  (MEKONTSO Olivier)                        │
│  - Vue globale KPIs plateforme                                 │
│  - Gestion des établissements (activation / suspension)        │
│  - Supervision transactions & commissions                      │
│  - Réclamations, logs sécurité, exports COBAC/BEAC             │
│  - URL cachée : /admin-ep2026/login · 2FA obligatoire          │
└────────────────────────────────────────────────────────────────┘
```

---

## 🖼️ Pages de la maquette

> Ouvre `EduPay_Maquette_Interactive_version-final.html` dans **Google Chrome** pour naviguer.

### Pages publiques

| # | ID Maquette | Page | Description |
|---|-------------|------|-------------|
| 01 | `s-landing` | **Accueil (Landing)** | Hero, 6 features cards, CTA, footer complet |
| 02 | `s-about` | **À Propos** | Histoire, équipe, feuille de route 2026 |
| 03 | `s-testi` | **Témoignages** | Avis directeurs, comptables, parents |

### Authentification

| # | ID Maquette | Page | Description |
|---|-------------|------|-------------|
| 04 | `s-login` | **Connexion** | Email/téléphone + mot de passe + OTP SMS |
| 05 | `s-register-parent` | **Inscription Payeur** | Formulaire multi-profil : Parent ou Élève/Étudiant |
| 06 | `s-onboarding` | **Rattachement** | Rattacher enfants/soi-même à un établissement |
| 07 | `s-register-school` | **Inscription École** | Identité, localisation, contact, admin, documents |
| 08 | `s-adminlogin` | **Login Super Admin** | URL cachée + 2FA obligatoire |

### Dashboards

| # | ID Maquette | Module | Onglets disponibles |
|---|-------------|--------|---------------------|
| 09 | `s-parent` | **Dashboard Parent / Élève** | Tableau de bord · Mes enfants · Historique · Reçus & Certificats · Réclamations · Profil |
| 10 | `s-payment` | **Tunnel de paiement** | Choix montant (intégral / tranche dynamique N×) · MTN MoMo / Orange Money · USSD · Confirmation |
| 11 | `s-school` | **Back-office École** | Tableau de bord · Apprenants · Frais & Échéanciers · Impayés · Remboursements · Rapports · Utilisateurs · Multi-sites · Paramètres |
| 12 | `s-admin` | **Super Admin** | Vue globale · Établissements · Transactions · Commissions · Réclamations · Logs sécurité · Exports réglementaires · Paramètres sys. |

### Fonctionnalités notables de la maquette v3

- **Profil dual** dans le dashboard payeur : vue "Famille (multi-enfants)" et vue "Élève / Étudiant" (solo)
- **Elève autonome** : peut se rattacher à son propre établissement, gérer et payer ses frais
- **87 impayés** simulés dans le back-office avec bouton de relance SMS groupée
- **2FA Admin** simulé avec boîte OTP Gold
- **Bilingue FR/EN** via bouton de bascule dans la topnav
- **Feuille de route 2026** visible sur la page "À propos"

---

## 🏗️ Architecture technique

```
┌──────────────────── COUCHE PRÉSENTATION ──────────────────────┐
│   Laravel Blade + Tailwind CSS (vues HTML)                    │
│   Livewire 3.x (composants interactifs sans rechargement)     │
│   React Native / Flutter (Mobile — Phase 2, hors périmètre)  │
└───────────────────────────────────────────────────────────────┘
                               │
┌──────────────────── COUCHE MÉTIER / API ──────────────────────┐
│   Laravel 13 — PHP 8.5 (Contrôleurs, Services, Policies)     │
│   Laravel Sanctum (Auth API + tokens)                         │
│   Spatie Laravel Permission (6 rôles, guard : admin)          │
│   Laravel Queues + Jobs (Notifications async)                 │
│   Guard dédié : admin — prefix routes : admin-ep2026          │
└───────────────────────────────────────────────────────────────┘
                               │
┌──────────────────── COUCHE DONNÉES ───────────────────────────┐
│   MySQL 8.0 (Base principale)                                 │
│   Redis (Cache, sessions, files de tâches)                    │
│   Storage local / S3 (reçus PDF, logos établissements)        │
└───────────────────────────────────────────────────────────────┘
                               │
┌──────────────────── INTÉGRATIONS PAIEMENT ────────────────────┐
│   AangaraaPay — MTN MoMo + Orange Money                          │
└───────────────────────────────────────────────────────────────┘
```

### Stack complet

> 📄 Le détail complet du flux de paiement Mobile Money (étapes, routes, webhook, statuts) est documenté dans `PAIEMENT_AANGARAAPAY.md`.

| Couche | Technologie | Version |
|--------|-------------|---------|
| Framework | Laravel | 13.x |
| Langage | PHP | 8.5 |
| Frontend | Blade + Tailwind CSS | 3.x |
| Composants réactifs | Livewire | 3.x |
| Base de données | MySQL | 8.0 |
| Auth & rôles | Sanctum + Spatie Permission | — |
| Agrégateur Paiement | AangaraaPay (MTN MoMo + Orange Money) | API v1 |
| PDF | barryvdh/laravel-dompdf | — |
| SMS | Africa's Talking API | — |
| Email dev | Mailtrap | — |
| Email prod | Mailgun | — |
| Files de tâches | Laravel Queues + Redis | — |
| Tests | PHPUnit + Pest | — |
| Versioning | Git + GitHub (`develop` → `main`) | — |

---

## 📁 Structure du projet

```
edupay-cameroun/
│
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/                        # Inscription, Connexion, OTP
│   │   │   ├── Payeur/                      # Dashboard parent, paiements
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── PaiementController.php
│   │   │   │   ├── HistoriqueController.php
│   │   │   │   └── ReclamationController.php
│   │   │   ├── Etablissement/               # Back-office école
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── ApprenantController.php
│   │   │   │   ├── FraisController.php
│   │   │   │   ├── ImpayeController.php
│   │   │   │   ├── RapportController.php
│   │   │   │   └── EquipeController.php
│   │   │   └── Admin/                       # Super admin (guard: admin)
│   │   │       ├── AuthController.php
│   │   │       ├── DashboardController.php
│   │   │       ├── EtablissementController.php
│   │   │       ├── TransactionController.php
│   │   │       ├── CommissionController.php
│   │   │       ├── ReclamationController.php
│   │   │       ├── LogController.php
│   │   │       └── ExportController.php
│   │   ├── Livewire/
│   │   │   ├── Payeur/
│   │   │   │   ├── TunnelPaiement.php
│   │   │   │   ├── GestionEnfants.php
│   │   │   │   └── ReclamationForm.php
│   │   │   └── Etablissement/
│   │   │       ├── DashboardFinancier.php
│   │   │       ├── ListeImpayés.php
│   │   │       └── RelanceSms.php
│   │   └── Middleware/
│   │       ├── AdminGuard.php
│   │       └── CheckRole.php
│   │
│   ├── Models/
│   │   ├── User.php                         # Guard web (parents, élèves, staff école)
│   │   ├── Admin.php                        # Guard admin (super admin)
│   │   ├── Etablissement.php
│   │   ├── Apprenant.php
│   │   ├── CategorieFrais.php
│   │   ├── Echeancier.php
│   │   ├── FraisApprenant.php
│   │   ├── Paiement.php
│   │   ├── Transaction.php
│   │   ├── OtpCode.php
│   │   ├── Commission.php
│   │   ├── AuditLog.php
│   │   └── Reclamation.php
│   │
│   ├── Services/
│   │   ├── AangaraaPayService.php          # MTN MoMo + Orange Money
│   │   ├── PdfService.php
│   │   └── SmsService.php
│   │
│   └── Jobs/
│       ├── SendPaymentNotification.php
│       ├── SendSmsRelance.php
│       └── GenerateRecuPdf.php
│
├── database/
│   ├── migrations/
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── AdminSeeder.php
│       ├── EtablissementSeeder.php
│       └── UserSeeder.php
│
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   ├── public.blade.php
│   │   │   ├── payeur.blade.php
│   │   │   ├── etablissement.blade.php
│   │   │   └── admin.blade.php
│   │   ├── auth/
│   │   ├── payeur/
│   │   ├── etablissement/
│   │   ├── admin/
│   │   ├── pdf/
│   │   │   └── recu.blade.php
│   │   └── emails/
│   ├── css/app.css
│   └── js/app.js
│
├── routes/
│   ├── web.php                              # Routes publiques + payeur + école
│   └── admin.php                           # Routes super admin (préfixe admin-ep2026)
│
├── tests/
│   ├── Unit/
│   └── Feature/
│
├── EduPay_Maquette_Interactive_version-final.html
├── .env.example
├── README.md
└── composer.json
```

---

## 🔀 Workflow Git solo

Tu travailles seul sur la branche `develop`. La branche `main` reçoit uniquement les merges finaux validés.

### Structure des branches

| Branche | Rôle |
|---------|------|
| `main` | Production — code stable uniquement |
| `develop` | **Branche de travail principale** (active) |

### Cycle de travail quotidien

```bash
# 1. Toujours vérifier que tu es sur develop
git checkout develop
git status

# 2. Travailler, puis committer régulièrement
git add .
git commit -m "feat: ajout tunnel paiement MTN MoMo"

# 3. Pousser develop sur GitHub
git push origin develop

# 4. Quand une fonctionnalité est stable et testée → merge sur main
git checkout main
git merge develop
git push origin main
git checkout develop    # repasser sur develop immédiatement
```

### Convention de commits

```
feat:      Nouvelle fonctionnalité
fix:       Correction de bug
style:     Changement CSS/UI uniquement
refactor:  Refactorisation du code
test:      Ajout ou modification de tests
docs:      Documentation uniquement
chore:     Config, dépendances, .env
db:        Migration ou seeder
api:       Intégration API externe (AangaraaPay)

Exemples :
  feat: dashboard parent avec vue famille et vue solo
  fix: calcul commission arrondi FCFA
  db: migration table otpcodes
  api: intégration AangaraaPay (MTN MoMo + Orange Money) sandbox
  test: feature paiement fractionné 2 tranches
  docs: mise à jour README et DOCUMENTATION_API
```

---

## 🚀 Installation locale

### Prérequis

- PHP 8.5+
- Composer 2.x
- MySQL 8.0
- Node.js 20+ et npm
- Git

### Étapes

```bash
# 1. Cloner le projet et basculer sur develop
git clone https://github.com/[ton-username]/edupay-cameroun.git
cd edupay-cameroun
git checkout develop

# 2. Installer les dépendances PHP
composer install

# 3. Copier et configurer l'environnement
cp .env.example .env
php artisan key:generate

# 4. Créer la base de données
mysql -u root -p -e "CREATE DATABASE edupay CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 5. Migrations + seeders
php artisan migrate --seed

# 6. Assets frontend
npm install && npm run dev

# 7. Lancer le serveur
php artisan serve

# 8. (Optionnel) Lancer les queues
php artisan queue:work
```

### Accès locaux

| Interface | URL | Identifiants (dev) |
|-----------|-----|--------------------|
| Application web | `http://localhost:8000` | — |
| Dashboard parent | `http://localhost:8000/dashboard` | parent@test.cm / password |
| Back-office école | `http://localhost:8000/etablissement` | comptable@test.cm / password |
| Super Admin | `http://localhost:8000/admin-ep2026` | admin@edupay.cm / Admin2026! |
| phpMyAdmin | `http://localhost/phpmyadmin` | root / — |

---

## 🗄️ Base de données

### Tables — schéma complet

```sql
-- Utilisateurs et rôles
users                    → Parents, élèves, staff école (guard: web)
admins                   → Super administrateur (guard: admin)
roles                    → payeur, directeur, comptable, caissier, staff_ecole, super_admin
permissions              → Permissions granulaires
model_has_roles          → Liaison user ↔ rôle

-- Entités scolaires
etablissements           → Écoles partenaires (maternelle → université)
apprenants               → Élèves/étudiants inscrits
user_apprenant           → Liaison parent ↔ enfant (pivot)

-- Frais et échéanciers
categories_frais         → Types de frais (inscription, scolarité, cantine, examens…)
echeanciers              → Calendrier de paiement par tranche
frais_apprenant          → Montant dû par apprenant et par catégorie

-- Paiements et transactions
paiements                → Chaque paiement initié (statut, montant, mode, tranche,
                            pay_token, aangaraa_transaction_id, operateur)
transactions             → Réponse technique API opérateur (ref, callback, statut final)
otp_codes                → OTP SMS temporaires (connexion + vérification paiement)
commissions              → Commission EduPay prélevée par transaction

-- Suivi et sécurité
reclamations             → Tickets de réclamation (payeur ou établissement)
audit_logs               → Journal de toutes les actions sensibles
notifications            → Historique SMS/email envoyés
```

### Commandes utiles

```bash
# Lancer toutes les migrations
php artisan migrate

# Annuler la dernière migration
php artisan migrate:rollback

# Réinitialiser (⚠️ efface tout)
php artisan migrate:fresh --seed

# Créer une nouvelle migration
php artisan make:migration create_xxx_table

# Lancer les seeders seuls
php artisan db:seed
```

---

## 🔐 Variables d'environnement

```env
# ── Application ──────────────────────────────
APP_NAME="EduPay Cameroun"
APP_ENV=local
APP_URL=http://localhost:8000

# ── Base de données ───────────────────────────
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=edupay
DB_USERNAME=root
DB_PASSWORD=

# ── Mail (dev : Mailtrap) ─────────────────────
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@edupay.cm
MAIL_FROM_NAME="EduPay Cameroun"

# ── SMS (Africa's Talking) ────────────────────
AT_API_KEY=
AT_USERNAME=sandbox
AT_SENDER_ID=EduPay

# ── AangaraaPay (MTN MoMo + Orange Money) ─
AANGARAA_API_URL=https://api-production.aangaraa-pay.com/api/v1
AANGARAA_APP_KEY=

# ── Super Admin ───────────────────────────────
ADMIN_URL_PREFIX=admin-ep2026
ADMIN_GUARD=admin

# ── Queues ────────────────────────────────────
QUEUE_CONNECTION=database

# ── Cache / Sessions ──────────────────────────
CACHE_STORE=redis
SESSION_DRIVER=redis
```

> ⚠️ Ne jamais committer `.env` — il est dans `.gitignore`

---

## 📏 Conventions de code

### PHP / Laravel

```php
// Classes : PascalCase
class PaiementController extends Controller {}

// Méthodes : camelCase
public function initierPaiementMoMo(Request $request) {}

// Variables : camelCase
$montantTotal = $paiement->montant + $commission->montant;

// Tables DB : snake_case pluriel
// categories_frais, frais_apprenants, audit_logs

// Constantes : UPPER_SNAKE_CASE
const TAUX_COMMISSION_DEFAULT = 0.005;

// Nommage routes — préfixe admin
// Route: admin.etablissements.index
// URL:   /admin-ep2026/etablissements
```

### Blade / Livewire

```blade
{{-- Directives Blade obligatoires --}}
@if($paiement->statut === 'valide')
    <span class="badge-success">Validé</span>
@endif

{{-- Composants Livewire : kebab-case --}}
<livewire:payeur.tunnel-paiement :paiement="$paiement" />
```

### CSS / Tailwind

```html
<!-- Classes Tailwind uniquement — couleurs EduPay dans tailwind.config.js -->
<!-- --ep-navy: #0B2545 | --ep-teal: #0D9E75 -->
<div class="bg-ep-teal text-white px-4 py-2 rounded-lg">
    Payer maintenant
</div>
```

---

## 📅 Planning de développement

| Phase | Contenu | Priorité |
|-------|---------|----------|
| **Phase 1** | Setup Laravel 13 / PHP 8.5, config DB, migrations complètes, seeders | 🔴 Critique |
| **Phase 2** | Auth multi-rôles (Sanctum + Spatie), OTP SMS, inscription parent/école | 🔴 Critique |
| **Phase 3** | Module Établissement : config frais, annuaire, échéanciers | 🔴 Critique |
| **Phase 4** | Dashboard Parent : vue famille, vue élève, tunnel paiement Livewire | 🔴 Critique |
| **Phase 5** | Intégration AangaraaPay (MTN MoMo + Orange Money) — flux USSD + poll + webhook | 🟡 Important |
| **Phase 6** | Génération reçus PDF (DomPDF) + notifications SMS/email | 🟡 Important |
| **Phase 7** | Super Admin (guard admin, 2FA, KPIs, commissions, logs) | 🟡 Important |
| **Phase 8** | Rapports PDF & Excel, exports COBAC/BEAC | 🟠 Souhaitable |
| **Phase 9** | Tests PHPUnit / Pest — couverture min. 80% | 🟠 Souhaitable |
| **Phase 10** | Déploiement production + passage MoMo en prod | 🔵 Phase 2 |

---

## 📜 Licence

Projet académique — Usage interne strictement réservé.  
© 2026 EduPay Cameroun — MEKONTSO OLIVIER STEVE — ESTLC Ambam — GSI

---

> 💡 **Rappel workflow :** Tu travailles sur `develop`. Chaque fonctionnalité stable → commit clair → push `develop` → merge sur `main` uniquement quand l'ensemble est propre et testé.
