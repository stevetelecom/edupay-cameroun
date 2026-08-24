# RAPPORT D'AUDIT DE SÉCURITÉ — EduPay Cameroun

**Référence** : AUDIT-EDUPAY-CM-2026-001
**Date** : 24 août 2026
**Périmètre** : Code source complet (Laravel 13), routes, contrôleurs, services, configuration, dépendances, conformité au CDC (CDC-EDUPAY-CM-2026-001 v1.0)
**Méthode** : Revue de code statique manuelle, analyse des flux d'authentification et de paiement, audit de configuration, `composer audit` / `npm audit`, exécution de la suite de tests
**Classification** : CONFIDENTIEL — Usage interne strictement réservé

---

## 1. SYNTHÈSE EXÉCUTIVE

L'application EduPay Cameroun présente une **base architecturale solide** pour un produit FinTech/EdTech : vérification serveur-à-serveur des webhooks de paiement, idempotence des paiements, verrous pessimistes anti double-crédit, RBAC via spatie/permission, journalisation d'audit admin, échappement XSS quasi systématique dans les vues Blade.

Cependant, l'audit révèle **4 vulnérabilités critiques, 6 élevées, 8 moyennes et plusieurs dysfonctionnements fonctionnels** qui doivent être corrigés **avant toute mise en production avec de l'argent réel**. Le point le plus grave : le module de connexion OTP n'envoie jamais le code par SMS (il est écrit dans les logs) et n'est pas limité en tentatives → **prise de contrôle totale de n'importe quel compte** pour quiconque accède aux logs ou peut brute-forcer.

| Sévérité | Nombre |
|---|---|
| 🔴 CRITIQUE | 4 |
| 🟠 ÉLEVÉE | 6 |
| 🟡 MOYENNE | 8 |
| 🔵 INFÉRIEURE / HYGIÈNE | 7+ |

---

## 2. VULNÉRABILITÉS CRITIQUES

### C-01 — Connexion OTP cassée et dangereuse (F02 du CDC non conforme)
- **Fichier** : `app/Http/Controllers/Auth/LoginController.php:120-134`
- **Constat** :
  1. Le code OTP est généré puis **uniquement écrit dans `laravel.log`** (`Log::info("OTP Code for {$login}: {$otp}")`) — jamais envoyé par SMS (« À implémenter Sprint 2 »).
  2. L'OTP est stocké **en session sans expiration**, comparé en clair.
  3. La route `/connexion/otp` (GET + POST) n'a **aucun throttle** ; le compteur de 3 tentatives est stocké en session et se réinitialise à chaque nouvelle session → **brute-force trivial du code à 6 chiffres (1 000 000 combinaisons)**.
  4. **Énumération d'utilisateurs** : « Utilisateur non trouvé » révèle si un email/téléphone existe.
  5. `Auth::login($user, true)` connecte avec remember=true permanent.
- **Impact** : prise de contrôle de n'importe quel compte (parent, directeur, caissier) ; fuite de code dans les logs accessibles à tout opérateur serveur.
- **Recommandation** : intégrer Africa's Talking (déjà dans composer.json), stocker l'OTP hashé en cache avec TTL 5 min, compteur de tentatives en cache côté serveur, throttle `throttle:5,1` sur la route, réponse générique, désactiver la fonctionnalité tant qu'elle n'est pas terminée.

### C-02 — Oracle de mot de passe Super Admin via réinitialisation
- **Fichier** : `app/Http/Controllers/Admin/AdminAuthController.php:260-265` (`resetPassword`)
- **Constat** : la vérification « nouveau ≠ ancien » (`Hash::check($request->password, $adminCheck->password)`) est exécutée **AVANT** la vérification du code à 6 chiffres. Un attaquant qui connaît l'email d'un admin obtient `admin_reset_id` en session via `/password/forgot` (route sans throttle), puis peut **tester des mots de passe candidats indéfiniment** : la réponse diffère selon que le mot de passe soumis est le mot de passe actuel de l'admin ou non.
- **Routes concernées** (`routes/admin.php:31-34`) : aucune n'a de middleware throttle.
- **Impact** : devinement offline-ish du mot de passe Super Admin = compte le plus privilégié de la plateforme.
- **Recommandation** : déplacer le check « différent de l'ancien » APRÈS validation du code ; ajouter throttle sur toutes les routes admin auth ; limiter les tentatives de code (compteur cache).

### C-03 — Brute-force possible sur le code 2FA admin
- **Fichier** : `app/Http/Controllers/Admin/AdminAuthController.php:138-171` (`verify2fa`)
- **Constat** : aucune limite de tentatives sur la vérification du code 2FA (le blocage à 5 essais ne concerne que l'étape mot de passe). Code à 6 chiffres valable 5 min en cache → ~1M combinaisons testables. Route `admin.login.2fa.verify` sans throttle.
- **Recommandation** : compteur d'échecs par session/admin (ex. blocage après 5 échecs), `throttle:10,1` minimum sur la route.

### C-04 — Clés API de production exposées dans `.env` local + secrets faibles
- **Fichiers** : `.env` (non suivi par git — bon point)
- **Constat** :
  - `AANGARAA_APP_KEY=MTPP-3675-CENB-K5S4` pointant vers l'API **de production** (`api-production.aangaraa-pay.com`) alors que `APP_ENV=local` et `APP_DEBUG=true`. Toute exécution locale initie de vrais paiements.
  - Mot de passe **réutilisé** entre MySQL et SMTP (`Rois@10720`).
  - `APP_DEBUG=true` : si ces valeurs sont recopiées telles quelles en production, toute erreur PHP expose stack traces, variables d'environnement et clés.
- **Recommandation** : clé sandbox en local, rotation immédiate de la clé de prod (considérée comme compromise dès lors qu'elle a circulé), mots de passe distincts par service, checklist de déploiement (`APP_ENV=production`, `APP_DEBUG=false`).

---

## 3. VULNÉRABILITÉS ÉLEVÉES

### E-01 — Fuite de données personnelles de mineurs + auto-rattachement à n'importe quel apprenant
- **Fichiers** : `app/Http/Controllers/Payeur/OnboardingController.php:204-228` (`searchApprenants`), `:25-122` (`store`)
- **Constat** : tout parent authentifié peut interroger n'importe quel `etablissement_id` et recevoir **nom, prénom, classe, matricule** des enfants (limit 30). Il peut ensuite se rattacher à **n'importe quel apprenant par ID** (`apprenant_id`) et consulter immédiatement ses frais/solde — `autoriserAcces` (PaiementController:540) ne vérifie que le rattachement, **pas** `valide_par_etablissement`.
- **Impact** : violation de confidentialité de données de mineurs (RGPD-loi africaine/CDC §2.4), consultation financière non autorisée.
- **Recommandation** : exiger matricule + nom exacts pour la recherche, restreindre la lecture des frais jusqu'à validation par l'établissement, notifier l'école avant accès, journaliser les rattachements.

### E-02 — Appel HTTP externe (reversement Mobile Money) DANS la transaction DB verrouillée
- **Fichier** : `app/Http/Controllers/Payeur/PaiementController.php:285-355` (`traiterPaiementValide`)
- **Constat** : `reverserEtablissement()` (timeout 30 s) est appelé **à l'intérieur** de `DB::transaction` avec `lockForUpdate` sur la ligne paiement. En cas de lenteur d'AangaraaPay, le verrou est maintenu jusqu'à ~45 s → risque de deadlock, saturation du pool de connexions, échecs en cascade sous charge (CDC §6.4 : 500 transactions/min exigées).
- **Recommandation** : sortir le reversement de la transaction — créer la commission dans la transaction, pousser le reversement dans une file (job) avec retry.

### E-03 — Paiements marqués ÉCHOUÉS à tort sur erreur réseau API
- **Fichier** : `app/Services/AangaraaPayService.php:309-317` + `PaiementController.php:265-273`
- **Constat** : en cas d'exception réseau/timeout sur `verifierStatut()`, le service retourne `'statut' => 'FAILED'`. Le polling client comme le webhook appellent ensuite `marquerEchoue()`. Or la réconciliation (`reconcilierPaiement`, ligne 472) **ignore les paiements déjà 'echoue'** → un paiement réellement débité mais vérifié pendant un hiccup réseau reste définitivement « échoué » : **perte de confiance / litiges financiers**.
- **Recommandation** : distinguer « erreur technique » (statut neutre `inconnu`, retry) d'un vrai `FAILED` opérateur ; inclure les paiements `echoue` récents dans la réconciliation.

### E-04 — Aucune limitation de débit sur la connexion principale ni sur l'initiation de paiement
- **Fichiers** : `routes/web.php:29` (POST `/connexion`), `:86` (POST `paiement/{fraisApprenant}/initier`)
- **Constat** : pas de `throttle` sur le login (brute-force de mots de passe) ni sur l'initiation de paiement qui contient de surcroît un `usleep(1500000)` (bloque un worker PHP-FPM 1,5 s par requête, ligne 189) et deux appels API externes (timeouts 30 s + 15 s). Le CDC exige un rate limiting global (100 req/min/IP).
- **Recommandation** : `throttle:5,1` sur login, `throttle:10,1` sur initier, supprimer le `usleep` (faire la vérification immédiate en job/file), rate limiting global au niveau Nginx/Cloudflare.

### E-05 — `env()` appelé dans le fichier de routes → casse l'application avec `config:cache`
- **Fichier** : `routes/web.php:185`
- **Constat** : `Route::prefix(env('ADMIN_URL_PREFIX', 'admin-ep2026'))`. En production on exécute `php artisan config:cache` ; `env()` retourne alors `null` (pas une erreur, comportement documenté) → préfixe `null` → **toutes les routes admin se retrouvent montées à la racine du site** (`/login`, `/register`, `/logout`…) en collision avec les routes publiques. Comportement imprévisible, potentiellement exposition de l'espace admin.
- **Recommandation** : remplacer par `config('app.admin_url_prefix', 'admin-ep2026')` avec la clé correspondante dans `config/app.php`.

### E-06 — Dépendances vulnérables connues
- **Sources** : `composer audit`, `npm audit`
- **Constat** :
  - `league/commonmark` < 2.9.0 : **3 advisory dont 2 HIGH** (DoS par parsing markdown quadratique, bypass de filtre unsafe-link). Livré transitivement avec Laravel.
  - npm : **4 vulnérabilités high** (Vite toolchain).
- **Recommandation** : `composer update league/commonmark --with-dependencies`, `npm audit fix`, ajouter `composer audit` + `npm audit` dans la CI (.github/workflows).

---

## 4. VULNÉRABILITÉS MOYENNES

| ID | Titre | Localisation | Détail & recommandation |
|---|---|---|---|
| M-01 | Jeton de reset = ID auto-incrémenté, jamais expiré | `PasswordResetController.php:64-67, 99-103` ; `Models/PasswordReset.php` | Après vérification du code, le lien contient l'ID de ligne (prévisible). `resetPassword` vérifie `is_verified` mais **jamais `verified_at`** → un enregistrement vérifié non consommé reste utilisable indéfiniment. Utiliser un token aléatoire 64 hex, expirer 15 min après `verified_at`. |
| M-02 | Headers de sécurité uniquement sur l'espace admin | `SuperAdminMiddleware.php` (fin) | X-Frame-Options, nosniff, Referrer-Policy posés seulement pour `admin.*`. Rien globalement : **pas de CSP, pas de HSTS** (exigences CDC §8.3). Ajouter un middleware global + HSTS/CSP au niveau Nginx. |
| M-03 | Cookies de session non forcés `secure` | `.env` / `config/session.php` | `SESSION_SECURE_COOKIE` absent → cookie non-Secure possible même derrière HTTPS. Définir `secure=true`, `same_site=lax` (ok), `http_only=true` (ok), et `SESSION_ENCRYPT=true` idéalement. |
| M-04 | Données personnelles loggées en masse | `AangaraaPayService.php:99-103, 238-244, 283-286` ; `PaiementController.php:364` ; `LandingController.php:91-124` | Numéros de téléphone complets, payloads webhook bruts, PII du formulaire contact, stack traces complètes → journaux non conformes au principe de minimisation (CDC §8.3 « logs chiffrés, immuables »). Masquer (`07****89`), réduire les champs, rotation/rétention définie. |
| M-05 | Code 2FA admin fallback dans les logs + email hardcodé | `AdminAuthController.php:104-107` ; `PaiementController.php:417` | Si l'email échoue, le code 2FA part en clair dans les logs. Adresse Gmail personnelle codée en dur pour les alertes webhook. Paramétrer (`ADMIN_ALERT_EMAIL`) et ne jamais logger le code. |
| M-06 | Code debug « ee0550 » résiduel en production | `AangaraaPayService.php:20-42` ; `PaiementController.php:44-49,144-156` | Écriture de logs JSON dans `base_path('.cursor/debug-ee0550.log')` à CHAQUE initiation de paiement (données téléphone incluses) + tags `[debug-ee0550]`. Supprimer ce bloc, c'est un reste de session de debug IA. |
| M-07 | Blocage admin basé sur IP+email en cache fichier | `AdminAuthController.php:47-58` | Avec `CACHE_STORE=file` et éventuel load-balancer, l'IP client peut être celle du proxy (si pas de trusted proxies configuré) → blocage massif de faux positifs ou contournement via X-Forwarded-For forgé. Configurer `TrustProxies` et utiliser le `RateLimiter` natif Laravel. |
| M-08 | Comparaison de token non constant-time | `AdminAuthController.php:356,373` | `$token !== config(...)` pour l'inscription admin. Risque timing faible mais utiliser `hash_equals()` par principe. Vérifier aussi que `ADMIN_REGISTER_TOKEN` est fort et que la route est fermée après usage. |

---

## 5. BUGS & DYSFONCTIONNEMENTS SIGNALÉS (hors sécurité)

1. **Fichiers poubelle suivis dans git** à la racine : `email`, `er->email;`, `integer,`, `roles` — résidus de redirections shell accidentelles (l'un contient un dump de l'aide `less`). À supprimer du dépôt.
2. **Tests en échec** : 14/23 erreurs — `phpunit.xml` utilise SQLite `:memory:` mais l'environnement n'a pas `pdo_sqlite` ; aucun test d'intégration paiement/auth réel ne s'exécute donc. Le CDC exige ≥ 80 % de couverture (§6.4) : très loin du compte (4 fichiers de tests seulement).
3. **`config/app.php:121 et 129`** : clé `admin_register_token` dupliquée.
4. **`.env`** : `AANGARAA_NOTIFY_URL` défini deux fois ; `QUEUE_CONNECTION=sync` → emails/SMS/PDF synchrones (lenteurs utilisateur + perte de fiabilité) ; `SESSION_DRIVER=file` non scalable (CDC prévoit Redis).
5. **`usleep(1500000)`** dans le flux web de paiement (`PaiementController.php:189`) — anti-pattern, bloque le worker.
6. **Commission avalée silencieusement** : le `catch (QueryException)` de `traiterPaiementValide` (ligne 326) attrape TOUTE exception SQL et retourne `true` — un vrai bug DB passerait inaperçu sans commission créée. Cibler le code d'erreur d'unicité (23000/1062).
7. **OTP login « Sprint 2 » jamais fait** mais la route est active et visible (voir C-01) — dysfonctionnel de bout en bout.
8. **Seeder admin avec mot de passe connu** (`AdminSeeder.php:38` : `Admin@EduPay2026!`) — danger si `db:seed` est lancé en prod ; forcer une génération aléatoire ou bloquer le seeding hors environnement local.
9. **PDF reçu non signé** : le CDC (F10) demande un « reçu PDF signé électroniquement » ; les reçus générés (dompdf) ne portent aucune signature/HMAC → falsifiable par copie. Ajouter un HMAC de référence vérifiable ou QR code signé.
10. **Absence totale d'API REST et d'app mobile** : `routes/api.php` vide, Sanctum installé mais inutilisé, pas de JWT — écarts majeurs au CDC (architecture §8.1, livrables L06/L07, phase 4 mobile).

---

## 6. CONFORMITÉ AU CAHIER DES CHARGES (CDC-EDUPAY-CM-2026-001 v1.0)

Exigence CDC §8.3 / §6.4 | Statut | Commentaire
---|---|---
Authentification JWT 15 min + refresh | ❌ Non conforme | Sessions web classiques ; aucune API/JWT. Acceptable pour le web pur, mais écart documenté.
2FA OTP SMS (option) | ⚠️ Partielle | Admin : 2FA email OK (mais cf. C-02/C-03). Payeur : OTP SMS cassé (C-01).
RBAC strict par endpoint | ✅ Conforme | Spatie roles + checks établissement systématiques constatés (Apprenant, Remboursement, Utilisateur, Recu…).
HTTPS/TLS 1.3 + HSTS | ⚠️ Partiel | Non vérifiable côté code ; HSTS absent du code, à garantir au niveau Nginx/CDN.
bcrypt cost 12 | ✅ Conforme | Cast `password => hashed` + défauts Laravel actuels (cost 12).
Chiffrement AES-256 données sensibles au repos | ❌ Non conforme | Téléphones et données financières en clair en base.
Rate limiting 100 req/min | ❌ Insuffisant | Throttles ponctuels seulement ; endpoints critiques ouverts (E-04).
Protection XSS / CSRF / SQLi | ✅ Bonne | CSRF global (webhook exempté à raison), Eloquent paramétré partout, échappement Blade correct (1 seul `{!!` correctement échappé via `e()`), `strip_tags` défense en profondeur.
Aucune donnée sensible en clair | ⚠️ Partiel | Logs chargés de PII (M-04) ; app_key transmise au PSP (imposé par le PSP).
Logs chiffrés immuables 12 mois | ❌ Non conforme | Logs fichiers standard, non chiffrés, rétention non définie.
Backup RPO≤6h / RTO≤4h, monitoring 24/7 | ❌ Hors périmètre code | Aucun artefact (Prometheus/Grafana, Sentry) présent.
Audit & pentest avant MEP majeure | 🔵 En cours | Le présent rapport constitue l'audit de code ; pentest externe recommandé avant lancement.
KPI 500 tx/min ; pages < 3 s | ⚠️ À risque | Queue sync, reversement dans transaction, usleep (E-02/E-04) rendent l'objectif irréaliste en l'état.

**Fonctionnalités CDC implémentées et conformes** : F01–F07/F09–F14 (compte, connexion mdp, dashboard, rattachement, frais, paiement MoMo MTN/Orange, tranches, reçus PDF, historique, notifications, multi-enfants, réclamations), E01–E12 (établissement complet : frais, échéanciers, annuaire, imports CSV, rapports PDF/Excel, remboursements, utilisateurs internes, multi-sites), S01–S08 (super admin complet : établissements, transactions, commissions, logs de sécurité, exports COBAC/BEAC, paramètres système).

---

## 7. POINTS POSITIFS À PRÉSERVER

1. **Webhook paiement** : ne jamais faire confiance au statut annoncé, revérification serveur-à-serveur obligatoire (`PaiementController.webhook:402-405`) — excellent design, rare dans les projets de cette taille.
2. **Idempotence et anti double-crédit** : verrou pessimiste + re-check dans la transaction + contrainte UNIQUE commission + réconciliation planifiée (`aangaraa:reconcilie`).
3. **Anti-énumération** sur le reset password payeur (message générique, codes hashés, max 5 tentatives, TTL 15 min).
4. **Défense en profondeur XSS** : strip_tags métier + échappement Blade systématique.
5. **Journalisation d'audit admin** horodatée avec IP/user-agent/niveau (base pour COBAC/BEAC).
6. **Gestion fine des états de paiement** (annulation manuelle sans toucher au statut réel, synchronisation anti-blocage 5 min).

---

## 8. PLAN DE REMÉDIATION PRIORISÉ

### Avant mise en production (bloquant — semaine 1)
1. Corriger/désactiver le login OTP (C-01).
2. Réordonner `resetPassword` admin + throttles sur toutes les routes d'auth admin (C-02, C-03).
3. Rotation de `AANGARAA_APP_KEY` prod ; séparer config locale/prod ; `APP_DEBUG=false` garanti (C-04).
4. Rate limiting login/initier + suppression du `usleep` (E-04).
5. `env('ADMIN_URL_PREFIX')` → `config()` (E-05).

### Semaines 2–3 (avant pilote)
6. Sortir le reversement de la transaction DB + queue jobs (E-02).
7. Gestion des erreurs API paiement : statut `inconnu` + réconciliation étendue (E-03).
8. Restreindre `searchApprenants` et conditionner l'accès aux frais à la validation établissement (E-01).
9. `composer update league/commonmark`, `npm audit fix`, CI avec audits automatiques (E-06).
10. Token de reset aléatoire + expiration (M-01) ; headers sécurité globaux + HSTS/CSP (M-02/M-03).

### Continu
11. Assainissement des logs (masquage PII), retrait du debug `ee0550` (M-04/M-06).
12. Nettoyage dépôt (fichiers poubelle, doublon config/app.php, .env dupliqué).
13. Campagne de tests : installer pdo_sqlite en CI, tests d'intégration paiement (webhook forgé, double webhook, race conditions), objectif couverture CDC 80 %.
14. Pentest externe boîte grise + test de charge (500 tx/min) avant généralisation.

---

## 9. LIMITES DE L'AUDIT

- Audit statique + revue de configuration ; aucun test d'intrusion actif contre l'infrastructure de production (hors périmètre accordé).
- Suite de tests non exécutable sur cette machine (absence de `pdo_sqlite`) ; l'analyse des tests s'est limitée à leur conception.
- Les configurations serveur (Nginx, TLS, pare-feu, backups O2Switch/mekontso.gsi2026.com) n'étaient pas accessibles et doivent faire l'objet d'une revue infra séparée.

---
*Fin du rapport — AUDIT-EDUPAY-CM-2026-001*
