# Bilan sécurité des formulaires — EduPay Cameroun

**Date :** 11 juillet 2026  
**Périmètre :** Formulaires web (public, auth, payeur, établissement, admin)  
**Mode :** Audit en lecture seule — aucune modification effectuée

## Synthèse globale

| Critère | État |
|---|---|
| Protection CSRF | ✅ Bon — `@csrf` présent sur les formulaires POST/PUT/DELETE |
| Validation serveur | ✅ Présente sur la quasi-totalité des contrôleurs |
| Authentification / rôles | ✅ Bonne base (`auth`, `role`, `super.admin`) |
| Autorisation par ressource (IDOR) | ⚠️ Partiellement couverte |
| Rate limiting | ❌ Faible sur les formulaires publics |
| Upload de fichiers | ⚠️ Validation basique, risques SVG |
| Webhook paiement | 🔴 Point critique |

## Points positifs

### 1. CSRF correctement géré
- Laravel active le CSRF sur le groupe `web`
- Seule exception : `webhook/aangaraapay` (légitime)
- Fichier : `bootstrap/app.php`
- Tous les formulaires Blade incluent `@csrf`

### 2. Validation serveur systématique
- Chaque contrôleur utilise `$request->validate()`
- Règles typées : `required`, `email`, `in:`, `max:`, `unique:`, `file|mimes:`

### 3. Mots de passe renforcés (payeur / établissement)
- Inscription parent : min 8 car., majuscule + chiffre + spécial
- Changement MDP : vérification ancien mot de passe + complexité

### 4. Contrôles IDOR bien faits
- Paiements payeur : vérifie rattachement parent/apprenant
- Apprenants établissement : vérifie `etablissement_id`
- Frais établissement : vérifie propriété de la catégorie
- Notifications payeur : vérifie `user_id`
- Réclamations : vérifie propriété du paiement

### 5. Espace admin mieux protégé
- URL préfixe cachée (`ADMIN_URL_PREFIX`)
- 2FA par email, OTP hashé en cache
- Rate limiting login admin (5 tentatives / 15 min)
- Audit logs + headers de sécurité HTTP

### 6. Session sécurisée
- `session()->regenerate()` après login
- Invalidation session à la déconnexion

## Vulnérabilités identifiées

### 🔴 Critique

#### 1. Webhook paiement sans authentification ni signature
- **Fichier :** `app/Http/Controllers/Payeur/PaiementController.php` — méthode `webhook()`
- **Risque :** POST forgé sur `/webhook/aangaraapay` avec `status: SUCCESSFUL` peut valider un paiement sans paiement réel
- **Recommandation :** signature HMAC / token secret AangaraaPay + whitelist IP

#### 2. Validation paiement déclenchable côté client
- **Fichier :** `PaiementController::verifierStatut()`
- **Risque :** combiné à l'absence de protection webhook = surface d'attaque financière

### 🟠 Élevé

#### 3. Rattachement payeur : création d'apprenants non validés
- **Fichier :** `app/Http/Controllers/Payeur/OnboardingController.php`
- **Risque :** faux profils, pollution annuaire, `etablissement_id` sans vérif statut `actif`

#### 4. IDOR sur `categorie_frais_id`
- **Fichier :** `app/Http/Controllers/Etablissement/ApprenantController.php`
- **Risque :** `exists:categories_frais,id` sans vérif appartenance à l'établissement

#### 5. Énumération de comptes (reset mot de passe public)
- **Fichier :** `app/Http/Controllers/Auth/PasswordResetController.php`
- **Risque :** message « Aucun compte trouvé » révèle si un email existe

#### 6. Codes OTP / reset en clair (logs + base)
- OTP login loggé en clair
- Codes reset stockés en clair en base (`PasswordReset.code`)
- **Recommandation :** hasher les codes, ne jamais les logger

#### 7. Brute-force codes 6 chiffres
- Reset public : pas de limite sur `verifyCode`
- Admin 2FA : pas de throttle sur `verify2fa` / `resend2fa`
- Contact, inscription, connexion : pas de rate limiting

#### 8. Mot de passe temporaire exposé en flash
- **Fichier :** `app/Http/Controllers/Etablissement/UtilisateurController.php`
- **Risque :** MDP temporaire visible dans la session flash si email échoue

### 🟡 Moyen

#### 9. Upload SVG autorisé (logos)
- Risque XSS si SVG malveillant servi depuis le même domaine

#### 10. XSS via innerHTML / onclick inline
- **Fichier :** `resources/views/etablissement/apprenants/index.blade.php`
- `addslashes()` insuffisant pour protéger le contexte JavaScript

#### 11. Inscription établissement : MDP directeur faible
- `min:8` seulement, pas de règle de complexité

#### 12. Pas de CAPTCHA sur formulaires publics
- Contact, inscription, connexion

#### 13. Recherche apprenants : fuite d'informations
- API `/espace/apprenants/search` expose données mineurs

#### 14. Mass assignment `suspendu` dans User::$fillable
- **Fichier :** `app/Models/User.php`

#### 15. Rôles établissement trop larges
- Caissier peut supprimer apprenants / catégories de frais

### 🟢 Faible

- Pas de classes FormRequest centralisées
- Formulaire contact sans rate limit
- Headers sécurité HTTP uniquement sur l'admin

## Tableau par zone

| Zone | CSRF | Validation | AuthZ | Points faibles |
|---|---|---|---|---|
| Public (contact) | ✅ | ✅ | N/A | Pas throttle, pas CAPTCHA |
| Auth (login, OTP, reset) | ✅ | ✅ | Guest | OTP loggé, énumération email |
| Inscription parent | ✅ | Forte | Guest | Pas throttle |
| Inscription établissement | ✅ | OK | Guest | MDP faible, SVG |
| Espace payeur | ✅ | OK | role + IDOR | Onboarding, webhook |
| Espace établissement | ✅ | OK | role + abonnement | IDOR frais, XSS, rôles |
| Admin | ✅ | ✅ | auth:admin | 2FA sans throttle verify |

## Priorités recommandées

1. **Urgent** — Sécuriser webhook AangaraaPay (signature + idempotence)
2. **Urgent** — Empêcher validation paiement sans preuve cryptographique
3. **Important** — Rate limiting (login, reset, 2FA, contact, inscription)
4. **Important** — Corriger IDOR `categorie_frais_id`
5. **Important** — Ne plus logger OTP/reset ; hasher codes en base
6. **Important** — Renforcer flux rattachement payeur
7. **Moyen** — Restreindre actions destructives par rôle
8. **Moyen** — Remplacer innerHTML / onclick par data-attributes
9. **Moyen** — Retirer SVG ou sanitizer ; message générique reset
10. **Moyen** — Retirer `suspendu` du $fillable User

## Conclusion

Base solide Laravel (CSRF, validation, middleware, IDOR partiels). Failles les plus graves : **webhook paiement non authentifié** et **onboarding payeur peu contrôlé**. Lacunes transverses : rate limiting, codes OTP en logs, quelques IDOR/XSS ciblés.

---

*Rapport généré par audit Cursor — EduPay Cameroun — 11/07/2026*
