# 🧪 Guide de Test - F12 Notifications SMS/Email

## 📋 Prérequis

- ✅ Laravel app en local
- ✅ Queue configurée (par défaut: 'sync')
- ✅ Mail configured (par défaut: log)
- ✅ Base de données avec données de test

---

## 🚀 Test 1: Confirmation de Paiement (manuel)

### Étape 1 - Ouvrir Tinker

```bash
cd /home/whitehack/edupay-cameroun
php artisan tinker
```

### Étape 2 - Simuler un paiement validé

```php
$paiement = \App\Models\Paiement::find(1);
$paiement->update(['statut' => 'valide']);
// → L'événement SendConfirmationPaiement est dispatché automatiquement!
```

### Étape 3 - Vérifier le log

```bash
# Dans un nouveau terminal
tail -f storage/logs/admin-*.log
# Vous devriez voir: "Email confirmation paiement envoyé"
```

---

## 🚀 Test 2: Alerte Impayé (manuel)

### Étape 1 - Dispatch le job

```php
$apprenant = \App\Models\Apprenant::with('parents')->first();
dispatch(new \App\Jobs\SendAlerteImpaye(
    $apprenant,
    'Frais de scolarité 2026',
    50000,
    '23/06/2026'
));
```

### Étape 2 - Vérifier les logs

```bash
tail -f storage/logs/admin-*.log
# Vous devriez voir: "Email alerte impayé envoyé" et "SMS alerte impayé envoyé"
```

---

## 🚀 Test 3: Schedulers (automatique)

### Vérifier les schedulers configurés

```bash
php artisan schedule:list
```

Vous devriez voir:

```
E07 SendSmsRelanceImpaye       07:00 ⏰ (J-5 avant échéance)
F12 SendAlerteImpayeJournaliere 18:00 ⏰ (Quotidien)
```

### Exécuter les schedulers manuellement

```bash
# Relance J-5
php artisan SendSmsRelanceImpaye

# Alertes impayé
php artisan SendAlerteImpayeJournaliere
```

---

## 📧 Test 4: Tests PHPUnit

### Exécuter les tests

```bash
php artisan test tests/Feature/F12NotificationsTest.php

# Ou avec verbose
php artisan test tests/Feature/F12NotificationsTest.php -v
```

### Résultats attendus

```
✓ confirmation_paiement_dispatch_on_validation
✓ confirmation_paiement_mail_sent
✓ alerte_impaye_dispatch
✓ alerte_impaye_mail_sent
✓ scheduler_relance_j5
✓ scheduler_alerte_quotidienne
```

---

## 🔍 Vérifier les Logs

### Logs admin

```bash
# Voir les 50 dernières lignes
tail -50 storage/logs/admin-*.log

# Suivre en temps réel
tail -f storage/logs/admin-*.log

# Chercher les erreurs
grep ERROR storage/logs/admin-*.log
```

### Logs mails (debug)

```bash
# Les mails sont envoyés au format log (config/mail.php)
# Vérifiez dans les logs ou utilisez: php artisan tinker
\App\Mail\ConfirmationPaiementMail
```

---

## 📤 Configuration SMS/Email

### Configuration actuelle

```
SMS Provider: AfricasTalking (config/services.php)
Email Driver: log (config/mail.php) — utiliser 'mailtrap' ou 'gmail' en production
```

### Pour tester avec de vrais SMS (Cameroun)

```php
// Dans tinker
$smsService = resolve(\App\Services\SmsService::class);
$ok = $smsService->envoyer('699123456', 'Test EduPay');
```

---

## ✅ Checklist de Test Complet

- [ ] Test confirmation paiement (Email dispatché)
- [ ] Test confirmation paiement (SMS dispatché)
- [ ] Test alerte impayé (Email dispatché)
- [ ] Test alerte impayé (SMS dispatché)
- [ ] Vérifier logs admin-\*.log
- [ ] Exécuter tests PHPUnit
- [ ] Vérifier schedulers listés
- [ ] Simuler un paiement réel (si possible)

---

## 🐛 Troubleshooting

**Q: Les jobs ne s'exécutent pas?**

- A: Vérifiez que `QUEUE_CONNECTION=sync` dans `.env`

**Q: Les emails ne sont pas envoyés?**

- A: Vérifiez `MAIL_DRIVER=log` dans `.env`, les logs se trouvent dans `storage/logs/`

**Q: Les notifications ne s'envoient pas?**

- A: Vérifiez que `notif_email` et `notif_sms` sont à `true` pour l'utilisateur

**Q: Comment voir les vrais SMS/Emails?**

- A: Configurez `MAIL_DRIVER=mailtrap` ou `gmail` et `SMS_PROVIDER=africastalking` avec vos clés API

---

## 📞 Support

Pour plus d'aide: `support@edupay-cameroun.cm`
