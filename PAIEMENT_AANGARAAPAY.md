# Intégration Paiement Mobile Money — EduPay Cameroun

> **Agrégateur utilisé :** AangaraaPay (MTN Mobile Money + Orange Money)
> **Mode choisi :** Paiement direct sans redirection (`/no_redirect/payment`)
> **Référence CDC :** F06 (Paiement Mobile Money), F09 (Paiement fractionné)

---

## 1. Vue d'ensemble du flux

```
Payeur (navigateur)          Laravel (EduPay)              AangaraaPay API
        │                           │                              │
        │── Soumet le formulaire ──▶│                              │
        │   (montant, téléphone,    │                              │
        │    opérateur)             │                              │
        │                           │── POST /no_redirect/payment ▶│
        │                           │   (phone, amount, app_key)   │
        │                           │                              │── Prompt USSD ──▶ Téléphone client
        │                           │◀── payToken + PENDING ───────│
        │◀── Redirige vers /attente─│                              │
        │                           │                              │
        │   [Client confirme sur    │                              │
        │    son téléphone]         │                              │
        │                           │                              │
        │── GET /statut (poll 5s) ──▶                              │
        │                           │── POST /aangaraa_check_status▶│
        │                           │◀── SUCCESSFUL ───────────────│
        │                           │                              │
        │                           │  Met à jour paiement en DB   │
        │                           │  (statut=valide,             │
        │                           │   montant_paye++)            │
        │◀── Affiche "Confirmé !" ──│                              │
        │                           │                              │
        │                    [En parallèle]                        │
        │                           │◀── Webhook POST /webhook/aangaraapay
        │                           │    (double confirmation)     │
```

---

## 2. Les acteurs

| Acteur | Rôle |
|--------|------|
| **Payeur** (parent / étudiant / eleve) | Initie le paiement depuis son espace |
| **Laravel EduPay** | Orchestre : crée le paiement en DB, appelle AangaraaPay, met à jour les statuts |
| **AangaraaPay** | Agrégateur qui contacte MTN ou Orange et envoie le prompt USSD au client |
| **MTN / Orange** | Opérateur qui débite le compte MoMo du client |

---

## 3. Détail étape par étape

### Étape 1 — Le payeur remplit le formulaire

Page : `/espace/paiement/{fraisApprenant}`

Le payeur choisit :
- **Option de paiement** : intégral ou par tranche
- **Moyen** : MTN Mobile Money ou Orange Money
- **Numéro de téléphone** : pré-rempli depuis son profil

### Étape 2 — Soumission (`PaiementController@initier`)

1. Validation des données (montant, mode, téléphone)
2. Calcul du montant :
   - Intégral → `montant_total - montant_paye`
   - Tranche → `reste / nb_tranches_max`
3. Création du `Paiement` en base avec `statut = en_attente`
4. Détection automatique de l'opérateur depuis le numéro :
   - `650-654`, `670-683` → `MTN_Cameroon`
   - `655-659`, `690-699` → `Orange_Cameroon`
5. Appel `AangaraaPayService@initierPaiement`
6. AangaraaPay envoie le **prompt USSD** sur le téléphone du client
7. Réponse : `payToken` + `statut PENDING`
8. Sauvegarde du `pay_token` dans la table `paiements`
9. Redirection vers la page d'attente

### Étape 3 — Page d'attente (`/espace/paiement/{paiement}/attente`)

- Affiche le montant et le numéro en attente
- Un script JS **poll toutes les 5 secondes** l'endpoint `/statut`
- Timeout : 2 minutes (24 tentatives × 5s) → affiche échec si dépassé

### Étape 4 — Vérification statut (`PaiementController@verifierStatut`)

Appelé par le poll JS :
1. Appel `AangaraaPayService@verifierStatut(payToken)`
2. Réponse AangaraaPay :

| Statut AangaraaPay | Action EduPay |
|--------------------|---------------|
| `SUCCESSFUL` | `paiement.statut = valide`, `frais_apprenant.montant_paye += montant`, recalcul `statut_paiement` apprenant |
| `PENDING` | Rien — on réessaie dans 5s |
| `FAILED` | `paiement.statut = echoue` |

### Étape 5 — Webhook (double sécurité)

Route : `POST /webhook/aangaraapay` (publique, sans CSRF)

AangaraaPay notifie EduPay dès que le paiement est confirmé côté opérateur.
Même logique que la vérification statut — garantit que le paiement est validé
même si le payeur ferme son navigateur avant la fin du poll.

---

## 4. Structure des fichiers

```
app/
├── Services/
│   └── AangaraaPayService.php      ← Appels API AangaraaPay
├── Http/Controllers/Payeur/
│   └── PaiementController.php      ← show, initier, attente,
│                                      verifierStatut, webhook, historique

resources/views/payeur/
├── paiement.blade.php              ← Formulaire de paiement
└── paiement_attente.blade.php      ← Page d'attente + poll JS

database/migrations/
└── ..._add_aangaraa_fields_to_paiements_table.php

config/
└── services.php                    ← aangaraa.api_url + aangaraa.app_key
```

---

## 5. Colonnes ajoutées à la table `paiements`

| Colonne | Type | Rôle |
|---------|------|------|
| `pay_token` | string nullable | Token AangaraaPay pour vérifier le statut |
| `aangaraa_transaction_id` | string nullable | Référence interne AangaraaPay |
| `operateur` | string nullable | `MTN_Cameroon` ou `Orange_Cameroon` |

---

## 6. Variables d'environnement requises

```env
AANGARAA_API_URL=https://api-production.aangaraa-pay.com/api/v1
AANGARAA_APP_KEY=ta_cle_api_aangaraapay
```

Obtenir la clé : `https://aangaraa-pay.com/aangarapay_register`

---

## 7. Routes

| Méthode | URL | Nom | Accès |
|---------|-----|-----|-------|
| GET | `/espace/paiement/{fraisApprenant}` | `payeur.paiement.show` | Auth payeur |
| POST | `/espace/paiement/{fraisApprenant}/initier` | `payeur.paiement.initier` | Auth payeur |
| GET | `/espace/paiement/{paiement}/attente` | `payeur.paiement.attente` | Auth payeur |
| GET | `/espace/paiement/{paiement}/statut` | `payeur.paiement.statut` | Auth payeur |
| POST | `/webhook/aangaraapay` | `payeur.paiement.webhook` | **Public** (sans CSRF) |

---

## 8. Statuts du paiement dans EduPay

```
en_attente  ──▶  valide     (SUCCESSFUL reçu)
            ──▶  echoue     (FAILED reçu ou timeout 2min)

valide      ──▶  rembourse  (via module remboursement)
```

---

## 9. Prochaines intégrations

| Fonctionnalité | Référence CDC | Sprint |
|----------------|---------------|--------|
| Reçu PDF après paiement validé | F10 (Must) | Sprint courant — DomPDF prêt |
| Notification SMS de confirmation | F12 (Must) | Sprint courant — Africa's Talking prêt |
| Relances automatiques impayés | E07 (Must) | Sprint courant — Job `SendSmsRelance` prêt |
| Rôle Caissier (saisie uniquement) | E10 (Must) | Sprint courant — Spatie role à créer |
| Virement bancaire | F08 (Could) | Sprint futur |

---

## 10. Contact AangaraaPay

- Site : https://aangaraa-pay.com
- Tél : +237 674 506 841
- Email : contact@aangaraa-pay.com
- Adresse : Yaoundé, Quartier Melen
