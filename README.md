# 🏦 EduPay Cameroun

> **Plateforme de Paiement Électronique des Frais de Scolarité**
> Référence : `CDC-EDUPAY-CM-2026-001` — Version `v1.0` — Mars 2026

![EduPay Banner](https://img.shields.io/badge/EduPay-Cameroun-0D9E75?style=for-the-badge)
![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel)
![Livewire](https://img.shields.io/badge/Livewire-3.x-4E56A6?style=for-the-badge)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql)
![Status](https://img.shields.io/badge/Status-En%20développement-orange?style=for-the-badge)

---

## 📋 Table des matières

1. [Présentation du projet](#-présentation-du-projet)
2. [Maquette interactive](#-maquette-interactive)
3. [Architecture technique](#️-architecture-technique)
4. [Structure du projet](#-structure-du-projet)
5. [Équipe & répartition des tâches](#-équipe--répartition-des-tâches)
6. [Workflow GitHub](#-workflow-github)
7. [Installation locale](#-installation-locale)
8. [Base de données](#️-base-de-données)
9. [Sprints & planning](#-sprints--planning)
10. [Conventions de code](#-conventions-de-code)
11. [Variables d'environnement](#-variables-denvironnement)
12. [Contacts](#-contacts)

---

## 🎯 Présentation du projet

**EduPay Cameroun** est une plateforme web de paiement électronique des frais de scolarité, pensée pour les réalités camerounaises. Elle connecte les établissements scolaires aux parents et étudiants via **MTN Mobile Money**, **Orange Money** et la **carte bancaire**.

### Problème résolu

La quasi-totalité des établissements scolaires camerounais gère encore les paiements **en espèces, manuellement**, avec tous les risques que cela implique :
- Détournements et fraudes
- Files d'attente interminables
- Absence de traçabilité
- Perte de reçus

### Solution EduPay

| Fonctionnalité | Description |
|---|---|
| 💳 Paiement Mobile Money | MTN MoMo & Orange Money intégrés nativement |
| 🧾 Reçu PDF automatique | Généré et envoyé après chaque paiement validé |
| 📊 Dashboard établissement | Suivi en temps réel des encaissements et impayés |
| 📅 Paiement fractionné | En 2 ou 3 tranches selon l'échéancier de l'école |
| 📱 Multi-établissements | Un parent gère plusieurs enfants dans plusieurs écoles |
| 🔒 Sécurité PCI-DSS | TLS 1.3, 2FA, conformité COBAC/BEAC |

### 3 modules principaux

```
┌─────────────────────────────────────────────────────────┐
│  MODULE PAYEUR          │  MODULE ÉTABLISSEMENT          │
│  (Parents / Étudiants)  │  (Directeur / Comptable)       │
│  - Inscription          │  - Config. des frais           │
│  - Paiement MoMo/Carte  │  - Annuaire des apprenants     │
│  - Historique & reçus   │  - Suivi impayés & relances    │
│  - Paiement fractionné  │  - Rapports financiers         │
├─────────────────────────────────────────────────────────┤
│  MODULE SUPER ADMIN  (Toi — MEKONTSO Olivier)           │
│  - Gestion de tous les établissements                   │
│  - Supervision des transactions globales                │
│  - Configuration des commissions (0,5%/transaction)     │
│  - Logs de sécurité & audit                             │
└─────────────────────────────────────────────────────────┘
```

---

## 🖼️ Maquette interactive

> **Le fichier `EduPay_Maquette_Interactive.html`** est joint à ce dépôt.
> Ouvre-le directement dans **Google Chrome** pour naviguer entre toutes les pages.

### Pages disponibles dans la maquette

| # | Page | Description |
|---|------|-------------|
| 01 | **Accueil (Landing)** | Page publique principale avec hero, features, footer |
| 02 | **À Propos** | Histoire, valeurs, équipe, feuille de route |
| 03 | **Témoignages** | Avis des directeurs, comptables, parents |
| 04 | **Connexion** | Login email/téléphone + OTP SMS |
| 05 | **Inscription Parent** | Formulaire en 3 étapes (compte, enfant, confirmation) |
| 06 | **Inscription École** | Formulaire en 4 étapes avec agrément MINESEC |
| 07 | **Dashboard Parent** | Vue enfants, paiements, historique |
| 08 | **Paiement MoMo** | Tunnel de paiement MTN/Orange/Carte |
| 09 | **Back-office École** | Dashboard comptable avec KPIs, relances |
| 10 | **Super Admin** | Vue globale plateforme, commissions, KPIs |

> ⚠️ **Note importante :** La page Super Admin n'est **pas accessible publiquement** en production.
> URL cachée : `/admin-ep2026/login` — authentification 2FA obligatoire.

---

## 🏗️ Architecture technique

```
┌──────────────────── COUCHE PRÉSENTATION ─────────────────────┐
│   Laravel Blade + Livewire (Web)                             │
│   React Native / Flutter (Mobile — Phase 2)                  │
└──────────────────────────────────────────────────────────────┘
                              │
┌──────────────────── COUCHE MÉTIER / API ─────────────────────┐
│   Laravel 12 (Routes, Controllers, Services)                 │
│   Laravel Sanctum (Auth API)                                 │
│   Spatie Laravel Permission (Rôles & permissions)            │
│   Laravel Queues (Notifications async)                       │
└──────────────────────────────────────────────────────────────┘
                              │
┌──────────────────── COUCHE DONNÉES ──────────────────────────┐
│   MySQL 8.0 (Base de données principale)                     │
│   Redis (Cache, sessions, files de tâches)                   │
│   AWS S3 / Local (Reçus PDF, documents)                      │
└──────────────────────────────────────────────────────────────┘
                              │
┌──────────────────── INTÉGRATIONS PAIEMENT ───────────────────┐
│   MTN Mobile Money API (Cameroun)                            │
│   Orange Money API (Cameroun)                                │
│   CinetPay (Carte Visa/Mastercard)                           │
└──────────────────────────────────────────────────────────────┘
```

### Stack complet

| Couche | Technologie | Version |
|--------|-------------|---------|
| Framework | Laravel | 12.x |
| Frontend | Blade + Livewire | 3.x |
| CSS | Tailwind CSS | 3.x |
| Base de données | MySQL | 8.0 |
| Auth | Laravel Sanctum + Spatie Permission | — |
| PDF | Laravel DomPDF (barryvdh/laravel-dompdf) | — |
| SMS | Africa's Talking API | — |
| Email | Mailtrap (dev) / Mailgun (prod) | — |
| Files | Laravel Queues + Redis | — |
| Tests | PHPUnit + Pest | — |
| Versioning | Git + GitHub | — |

---

## 📁 Structure du projet

```
edupay-cameroun/
│
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/                    # Inscription, Connexion, OTP
│   │   │   ├── Payeur/                  # Dashboard parent, paiements
│   │   │   ├── Etablissement/           # Back-office école
│   │   │   └── Admin/                   # Super admin
│   │   ├── Livewire/                    # Composants Livewire
│   │   └── Middleware/
│   ├── Models/
│   │   ├── User.php
│   │   ├── Etablissement.php
│   │   ├── Apprenant.php
│   │   ├── CategoriesFrais.php
│   │   ├── Echeancier.php
│   │   ├── FraisApprenant.php
│   │   ├── Paiement.php
│   │   ├── Transaction.php
│   │   ├── Commission.php
│   │   └── Notification.php
│   ├── Services/
│   │   ├── MtnMomoService.php           # Intégration MTN
│   │   ├── OrangeMoneyService.php       # Intégration Orange
│   │   ├── CinetPayService.php          # Intégration CinetPay
│   │   ├── PdfService.php               # Génération reçus PDF
│   │   └── SmsService.php               # Envoi SMS
│   └── Jobs/
│       ├── SendPaymentNotification.php
│       └── SendSmsRelance.php
│
├── database/
│   ├── migrations/                      # 14 migrations
│   └── seeders/                         # Données de test
│
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   ├── auth/                        # Login, Register
│   │   ├── payeur/                      # Dashboard parent
│   │   ├── etablissement/               # Back-office
│   │   ├── admin/                       # Super admin
│   │   └── emails/                      # Templates email
│   ├── css/
│   └── js/
│
├── routes/
│   ├── web.php                          # Routes publiques + dashboards
│   └── admin.php                        # Routes super admin (cachées)
│
├── tests/
│   ├── Unit/
│   └── Feature/
│
├── EduPay_Maquette_Interactive.html     # 👈 Maquette UI/UX
├── .env.example
├── README.md
└── composer.json
```

---

## 👥 Équipe & répartition des tâches

> **Chef de projet : MEKONTSO OLIVIER STEVE**
> Dépôt GitHub : `https://github.com/[username]/edupay-cameroun`

---

### 👑 MEKONTSO OLIVIER STEVE — Chef de projet & Super Admin

**Branche GitHub :** `main` (merge final) + `feature/super-admin`

**Responsabilités :**
- Création et gestion du dépôt GitHub (initialisation, protection de `main`)
- Review et validation de toutes les Pull Requests avant merge
- Architecture globale du projet, configuration Laravel initiale
- Module **Super Admin** complet :
  - Page de login sécurisée (2FA obligatoire) — URL cachée
  - Dashboard KPIs globaux (volume, commissions, établissements actifs)
  - Gestion des établissements (activation, suspension, suppression)
  - Configuration du taux de commission par établissement
  - Logs de sécurité et audit (toutes les actions sensibles)
  - Rapports réglementaires COBAC/BEAC
- Intégration finale **MTN Mobile Money API** (Cameroun)
- Déploiement en production (quand prêt)

**Fichiers principaux :**
```
app/Http/Controllers/Admin/
app/Models/ (tous les modèles)
database/migrations/
routes/admin.php
resources/views/admin/
app/Services/MtnMomoService.php
```

**Issues GitHub à créer :**
- `#1` — Setup Laravel + config DB + migrations complètes
- `#2` — Module Super Admin : authentification 2FA
- `#3` — Module Super Admin : dashboard KPIs
- `#4` — Intégration MTN Mobile Money sandbox
- `#5` — Logs sécurité + audit trail

---

### 💻 WANDJI NGUELE — Dev Back-end & API

**Branche GitHub :** `feature/backend-api`

**Responsabilités :**
- Module **Authentification complet** (inscription, connexion, OTP SMS)
- Middleware de rôles avec **Spatie Laravel Permission**
  - 5 rôles : `super_admin`, `directeur`, `comptable`, `caissier`, `parent`
- Module **Paiements** (logique métier côté serveur) :
  - Traitement des transactions
  - Calcul des commissions automatiques
  - Gestion des paiements fractionnés et échéanciers
- API RESTful pour les futures intégrations mobile
- Intégration **Orange Money API** (Cameroun)
- Configuration **Laravel Queues** (notifications async)
- Écriture des **tests PHPUnit** pour les paiements

**Fichiers principaux :**
```
app/Http/Controllers/Auth/
app/Http/Controllers/Payeur/PaiementController.php
app/Services/OrangeMoneyService.php
app/Jobs/SendPaymentNotification.php
app/Models/Paiement.php
app/Models/Transaction.php
app/Models/Commission.php
database/seeders/
routes/web.php (routes auth + paiement)
tests/Feature/PaiementTest.php
```

**Issues GitHub à créer :**
- `#6` — Auth : inscription parent + login multi-rôles
- `#7` — Auth : OTP SMS via Africa's Talking
- `#8` — Logique paiement + calcul commission automatique
- `#9` — Paiement fractionné + gestion échéanciers
- `#10` — Intégration Orange Money sandbox
- `#11` — Laravel Queues + jobs notifications

---

### 🎨 EBODE BIKORO — Dev Front-end & UI

**Branche GitHub :** `feature/frontend-ui`

**Responsabilités :**
- Intégration **Tailwind CSS** + design system EduPay (couleurs, composants)
- **Layout global** : navbar, sidebar, footer
- Pages **publiques** (Blade) : Landing, À propos, Témoignages
- Formulaires : Connexion, Inscription Parent, Inscription École
- **Dashboard Parent** complet (Livewire) :
  - Liste des enfants avec statut de paiement
  - Tunnel de paiement (MTN / Orange / Carte)
  - Historique des transactions
  - Téléchargement des reçus PDF
- Responsive design (mobile-first)
- Intégration des **icônes SVG** et composants visuels

**Fichiers principaux :**
```
resources/views/layouts/
resources/views/auth/
resources/views/payeur/
resources/css/app.css (Tailwind config)
resources/js/app.js
app/Http/Livewire/Payeur/
tailwind.config.js
```

**Issues GitHub à créer :**
- `#12` — Layout global + design system Tailwind EduPay
- `#13` — Pages publiques : Landing + À propos + Témoignages
- `#14` — Formulaires Auth : Connexion + Inscription Parent
- `#15` — Dashboard Parent : vue enfants + KPIs
- `#16` — Tunnel de paiement (composant Livewire)
- `#17` — Responsive mobile + accessibilité

---

### 🏫 MAKUETA NGAMBA — Dev Back-office École

**Branche GitHub :** `feature/backoffice-ecole`

**Responsabilités :**
- Module **Établissement** complet :
  - Formulaire d'inscription école (4 étapes)
  - Configuration des catégories de frais (inscription, scolarité, cantine, examens...)
  - Gestion des échéanciers par tranche
  - Annuaire des apprenants (CRUD + import CSV/Excel)
  - Gestion des utilisateurs internes (Directeur, Comptable, Caissier)
- **Back-office Comptable** (Livewire) :
  - Dashboard financier en temps réel (KPIs)
  - Suivi des paiements individuels par élève
  - Module impayés + relances SMS automatiques et manuelles
  - Génération de **rapports PDF et Excel** (exportables)
- Intégration **CinetPay** (Carte Visa/Mastercard)

**Fichiers principaux :**
```
app/Http/Controllers/Etablissement/
app/Http/Livewire/Etablissement/
app/Models/Etablissement.php
app/Models/Apprenant.php
app/Models/CategoriesFrais.php
app/Models/Echeancier.php
app/Services/CinetPayService.php
resources/views/etablissement/
```

**Issues GitHub à créer :**
- `#18` — Inscription établissement (formulaire + validation)
- `#19` — Config frais : catégories + montants + échéanciers
- `#20` — Annuaire apprenants : CRUD + import CSV
- `#21` — Dashboard comptable : KPIs temps réel
- `#22` — Module impayés + relances SMS groupées
- `#23` — Export rapports PDF et Excel
- `#24` — Intégration CinetPay sandbox

---

### 🧪 MAFFO NDJOUMESSI — QA, Tests & Notifications

**Branche GitHub :** `feature/qa-tests-notifications`

**Responsabilités :**
- Plan de tests complet (cas de test fonctionnels et non fonctionnels)
- **Tests PHPUnit/Pest** pour tous les modules :
  - Tests unitaires (modèles, services)
  - Tests fonctionnels (routes, formulaires, paiements)
- **Génération des reçus PDF** (DomPDF) :
  - Template reçu officiel EduPay avec QR code
  - Numérotation automatique des reçus
  - Signature électronique
- Système de **notifications** complet :
  - Templates SMS (paiement confirmé, rappel échéance, impayé)
  - Templates email (reçu PDF en pièce jointe, bienvenue, relance)
  - Attestations de paiement automatiques
- Configuration **CI/CD GitHub Actions** (tests automatiques à chaque push)
- Documentation technique (wiki GitHub)

**Fichiers principaux :**
```
tests/Unit/
tests/Feature/
app/Services/PdfService.php
app/Services/SmsService.php
resources/views/pdf/recu.blade.php
resources/views/emails/
.github/workflows/tests.yml
```

**Issues GitHub à créer :**
- `#25` — Template reçu PDF officiel EduPay (DomPDF)
- `#26` — Templates SMS : paiement, relance, impayé
- `#27` — Templates email : reçu, bienvenue, rappel
- `#28` — Tests PHPUnit : module authentification
- `#29` — Tests PHPUnit : module paiement
- `#30` — CI/CD GitHub Actions (tests auto sur push)
- `#31` — Documentation wiki GitHub

---

## 🔀 Workflow GitHub

### 1. Initialisation du dépôt (Chef de projet — Olivier)

```bash
# 1. Créer le projet Laravel localement
composer create-project laravel/laravel edupay-cameroun
cd edupay-cameroun

# 2. Initialiser Git
git init
git add .
git commit -m "chore: initialisation projet Laravel EduPay"

# 3. Créer le dépôt sur GitHub et pousser
git remote add origin https://github.com/[ton-username]/edupay-cameroun.git
git branch -M main
git push -u origin main
```

### 2. Configuration des branches protégées

Sur GitHub → Settings → Branches → Add rule pour `main` :
- ✅ Require a pull request before merging
- ✅ Require approvals (1 reviewer minimum)
- ✅ Require status checks to pass (tests CI)

### 3. Chaque développeur clone et crée sa branche

```bash
# Cloner le dépôt
git clone https://github.com/[username]/edupay-cameroun.git
cd edupay-cameroun

# Créer sa branche personnelle
git checkout -b feature/[nom-du-membre]

# Exemples :
git checkout -b feature/backend-api          # WANDJI
git checkout -b feature/frontend-ui          # EBODE
git checkout -b feature/backoffice-ecole     # MAKUETA
git checkout -b feature/qa-tests-notifications  # MAFFO
```

### 4. Cycle de travail quotidien

```bash
# 1. Toujours partir de la dernière version de main
git checkout main
git pull origin main

# 2. Revenir sur sa branche et intégrer les changements
git checkout feature/[ma-branche]
git merge main

# 3. Travailler, puis committer régulièrement
git add .
git commit -m "feat: ajout du formulaire d'inscription parent"

# 4. Pousser sa branche
git push origin feature/[ma-branche]

# 5. Créer une Pull Request sur GitHub vers main
#    → Le chef de projet (Olivier) review et merge
```

### 5. Convention de nommage des commits

```
feat:     Nouvelle fonctionnalité
fix:      Correction de bug
style:    Changement CSS/UI uniquement
refactor: Refactorisation du code
test:     Ajout ou modification de tests
docs:     Documentation
chore:    Config, dépendances
db:       Migration ou seeder

Exemples :
  feat: ajout paiement MTN MoMo
  fix: correction calcul commission
  db: migration table paiements
  test: tests unitaires PaiementService
  docs: mise à jour README
```

### 6. Tableau des branches

| Branche | Développeur | Rôle |
|---------|------------|------|
| `main` | MEKONTSO Olivier | Production / Merge final |
| `develop` | Tous | Intégration commune |
| `feature/super-admin` | MEKONTSO Olivier | Module super admin |
| `feature/backend-api` | WANDJI Nguele | Auth + API + paiements |
| `feature/frontend-ui` | EBODE Bikoro | UI/UX + pages publiques |
| `feature/backoffice-ecole` | MAKUETA Ngamba | Back-office école |
| `feature/qa-tests-notifications` | MAFFO Ndjoumessi | Tests + PDF + SMS |

---

## 🚀 Installation locale

### Prérequis

- PHP 8.2+
- Composer 2.x
- MySQL 8.0
- Node.js 18+ et npm
- Git

### Étapes d'installation

```bash
# 1. Cloner le projet
git clone https://github.com/[username]/edupay-cameroun.git
cd edupay-cameroun

# 2. Installer les dépendances PHP
composer install

# 3. Copier le fichier d'environnement
cp .env.example .env

# 4. Générer la clé d'application
php artisan key:generate

# 5. Configurer la base de données dans .env
# DB_DATABASE=edupay
# DB_USERNAME=root
# DB_PASSWORD=

# 6. Créer la base de données
mysql -u root -p -e "CREATE DATABASE edupay CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 7. Lancer les migrations et seeders
php artisan migrate --seed

# 8. Installer les dépendances frontend
npm install

# 9. Compiler les assets
npm run dev

# 10. Lancer le serveur de développement
php artisan serve

# 11. (Optionnel) Lancer les queues pour les notifications
php artisan queue:work
```

### Accès local

| Interface | URL | Identifiants (dev) |
|-----------|-----|-------------------|
| Application web | `http://localhost:8000` | — |
| Back-office école | `http://localhost:8000/etablissement` | comptable@test.cm / password |
| Super Admin | `http://localhost:8000/admin-ep2026` | admin@edupay.cm / Admin2026! |
| phpMyAdmin (XAMPP) | `http://localhost/phpmyadmin` | root / — |

---

## 🗄️ Base de données

### Schéma — 14 tables

```sql
users                    → Tous les utilisateurs (parent, directeur, comptable, caissier, admin)
roles                    → Rôles (Spatie Permission)
model_has_roles          → Liaison user ↔ rôle

etablissements           → Toutes les écoles inscrites sur EduPay
apprenants               → Élèves/étudiants liés à un établissement
user_apprenant           → Liaison parent ↔ enfant (pivot)

categories_frais         → Types de frais (inscription, scolarité, cantine...)
echeanciers              → Calendrier de paiement par tranche
frais_apprenant          → Montant dû par apprenant et par catégorie

paiements                → Chaque paiement effectué (statut, montant, mode)
transactions             → Détail technique API (référence opérateur, callback)
commissions              → Commission EduPay prélevée par transaction

notifications            → SMS/email envoyés (historique)
audit_logs               → Toutes les actions sensibles (sécurité)
```

### Lancer les migrations

```bash
# Toutes les migrations
php artisan migrate

# Annuler la dernière migration
php artisan migrate:rollback

# Réinitialiser complètement (⚠️ efface tout)
php artisan migrate:fresh --seed
```

---

## 📅 Sprints & planning

| Sprint | Dates | Objectif | Responsable principal |
|--------|-------|----------|-----------------------|
| **Sprint 0** | Semaine 1 | Setup Laravel, DB, GitHub, maquettes validées | MEKONTSO Olivier |
| **Sprint 1** | Semaines 2-3 | Auth multi-rôles, migrations complètes, seeders | WANDJI + MEKONTSO |
| **Sprint 2** | Semaines 4-5 | Module Établissement (config frais, annuaire) | MAKUETA |
| **Sprint 3** | Semaines 6-7 | Module Payeur + UI (dashboard parent, paiement) | EBODE + WANDJI |
| **Sprint 4** | Semaine 8 | Intégrations MoMo sandbox + PDF reçus | MEKONTSO + MAFFO |
| **Sprint 5** | Semaine 9 | Super Admin + commissions + rapports | MEKONTSO |
| **Sprint 6** | Semaine 10 | Tests complets + corrections + CI/CD | MAFFO + tous |
| **Sprint 7** | Semaine 11 | Déploiement pilote (5 écoles) | MEKONTSO |

---

## 📏 Conventions de code

### PHP / Laravel

```php
// Nommage des classes : PascalCase
class PaiementController extends Controller {}

// Nommage des méthodes : camelCase
public function initialisePaiement(Request $request) {}

// Nommage des variables : camelCase
$montantTotal = $paiement->montant + $commission->montant;

// Nommage des tables DB : snake_case pluriel
// frais_apprenants, categories_frais, audit_logs

// Constantes : UPPER_SNAKE_CASE
const TAUX_COMMISSION_DEFAULT = 0.005;
```

### Blade / Livewire

```blade
{{-- Toujours utiliser les directives Blade --}}
@if($paiement->statut === 'valide')
    <span class="badge badge-success">Validé</span>
@endif

{{-- Composants Livewire : kebab-case --}}
<livewire:payeur.tunnel-paiement :paiement="$paiement" />
```

### CSS / Tailwind

```html
<!-- Classes utilitaires Tailwind uniquement -->
<!-- Couleurs EduPay définies dans tailwind.config.js -->
<div class="bg-ep-teal text-white px-4 py-2 rounded-lg">
    Payer maintenant
</div>
```

---

## 🔐 Variables d'environnement

Copier `.env.example` en `.env` et remplir :

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
MAIL_USERNAME=            # Depuis Mailtrap.io
MAIL_PASSWORD=            # Depuis Mailtrap.io
MAIL_FROM_ADDRESS=noreply@edupay.cm
MAIL_FROM_NAME="EduPay Cameroun"

# ── SMS (Africa's Talking) ────────────────────
AT_API_KEY=               # Depuis africastalking.com
AT_USERNAME=sandbox        # "sandbox" pour les tests
AT_SENDER_ID=EduPay

# ── MTN Mobile Money ─────────────────────────
MTN_MOMO_SUBSCRIPTION_KEY=
MTN_MOMO_API_USER=
MTN_MOMO_API_KEY=
MTN_MOMO_ENV=sandbox       # "sandbox" ou "production"

# ── Orange Money ──────────────────────────────
ORANGE_CLIENT_ID=
ORANGE_CLIENT_SECRET=
ORANGE_MOMO_ENV=sandbox

# ── CinetPay (Carte bancaire) ─────────────────
CINETPAY_API_KEY=
CINETPAY_SITE_ID=
CINETPAY_ENV=sandbox

# ── Super Admin (URL cachée) ──────────────────
ADMIN_URL_PREFIX=admin-ep2026

# ── Queues ────────────────────────────────────
QUEUE_CONNECTION=database
```

> ⚠️ **Ne jamais committer le fichier `.env`** — il est dans `.gitignore`

---

## 📞 Contacts

| Membre | Rôle | Responsabilité |
|--------|------|----------------|
| **MEKONTSO OLIVIER STEVE** | Chef de projet | Super Admin, Architecture, MTN MoMo |
| **WANDJI NGUELE** | Dev Back-end | Auth, API, Orange Money, Queues |
| **EBODE BIKORO** | Dev Front-end | UI/UX, Blade, Livewire, Pages publiques |
| **MAKUETA NGAMBA** | Dev École | Back-office, CinetPay, Rapports |
| **MAFFO NDJOUMESSI** | QA / Tests | Tests, PDF, SMS, CI/CD |

**Établissement :** ESTLC Ambam — GSI — Groupes 14 & 15
**Référence CDC :** `CDC-EDUPAY-CM-2026-001`

---

## 📜 Licence

Projet académique — Usage interne strictement réservé.
© 2026 EduPay Cameroun — Groupes 14 & 15 GSI — ESTLC Ambam

---

> 💡 **Conseil chef de projet :** Créez les **30 issues GitHub** listées dans ce README dès le début, assignez chacune à la bonne personne, et utilisez les **Projects GitHub** (Kanban) pour suivre l'avancement en temps réel. Faites un point d'équipe chaque vendredi via WhatsApp ou en présentiel.
