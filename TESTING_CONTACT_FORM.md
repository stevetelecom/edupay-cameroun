# 📧 Guide Complet: Tests Formulaire de Contact avec Logs Laravel

## 📌 Vue d'ensemble

Ce guide explique comment **tester le formulaire de contact** et voir les **logs de Laravel** pour confirmer que tout fonctionne correctement.

---

## 🚀 Exécution rapide (1 commande)

```bash
bash run-contact-tests.sh
```

Ce script va:

1. ✅ Nettoyer les anciens logs
2. ✅ Exécuter TOUS les tests du formulaire de contact
3. ✅ Afficher les logs générés avec les détails des messages

---

## 🔍 Commandes individuelles pour plus de contrôle

### Option 1: Exécuter UN TEST SPÉCIFIQUE

```bash
# Test du flux complet (celui avec le plus de logs)
php artisan test tests/Feature/ContactFormTest.php::test_complete_contact_flow_with_logs --verbose

# Test de soumission simple
php artisan test tests/Feature/ContactFormTest.php::test_can_submit_contact_message --verbose

# Test de validation (nom obligatoire)
php artisan test tests/Feature/ContactFormTest.php::test_contact_message_requires_name --verbose

# Test avec plusieurs messages
php artisan test tests/Feature/ContactFormTest.php::test_multiple_contact_messages --verbose
```

### Option 2: Exécuter TOUS les tests

```bash
php artisan test tests/Feature/ContactFormTest.php --verbose
```

### Option 3: Voir les logs EN DIRECT pendant les tests

**Terminal 1** - Surveiller les logs:

```bash
tail -f storage/logs/laravel.log
```

**Terminal 2** - Exécuter les tests:

```bash
php artisan test tests/Feature/ContactFormTest.php --verbose
```

### Option 4: Filtrer les logs pour ne voir que les messages de contact

```bash
# Afficher tous les logs de contact
grep "NOUVEAU MESSAGE DE CONTACT\|Envoi de l'email\|Email de contact envoyé" storage/logs/laravel.log

# Ou utiliser tail avec grep
tail -f storage/logs/laravel.log | grep -i "contact\|email"
```

---

## 📊 Qu'est-ce qui va être loggué ?

### Les logs doivent contenir des entrées comme celles-ci:

#### 1️⃣ Réception d'un message

```
[2026-06-25 10:30:45] local.INFO: 📧 NOUVEAU MESSAGE DE CONTACT {
  "name": "Jean Dupont",
  "email": "jean@example.com",
  "phone": "+237 654 862 989",
  "subject": "Question sur les frais",
  "message_length": 45,
  "timestamp": "2026-06-25 10:30:45"
}
```

#### 2️⃣ Tentative d'envoi d'email

```
[2026-06-25 10:30:45] local.INFO: 📨 Envoi de l'email à: support@edupay.cm {
  "from": "jean@example.com",
  "name": "Jean Dupont"
}
```

#### 3️⃣ Succès d'envoi

```
[2026-06-25 10:30:46] local.INFO: ✅ Email de contact envoyé avec succès {
  "from": "jean@example.com",
  "to": "support@edupay.cm"
}
```

#### 4️⃣ Erreur (s'il y en a)

```
[2026-06-25 10:30:46] local.ERROR: ❌ Erreur lors de l'envoi du message de contact {
  "email": "jean@example.com",
  "error": "Could not send message",
  "trace": "..."
}
```

---

## 🗂️ Fichiers de logs

### Après les tests, vous trouverez:

- **`storage/logs/laravel.log`** - Tous les logs (défaut)
- **`storage/logs/laravel-2026-06-25.log`** - Logs du jour (si log daily est utilisé)

### Afficher les logs:

```bash
# Dernières 50 lignes
tail -50 storage/logs/laravel.log

# Toutes les lignes
cat storage/logs/laravel.log

# Nombre total de lignes
wc -l storage/logs/laravel.log

# Filtrer les logs du contact
grep "NOUVEAU MESSAGE DE CONTACT" storage/logs/laravel.log

# Filtrer les erreurs
grep "ERROR" storage/logs/laravel.log

# En temps réel (tant que l'app s'exécute)
tail -f storage/logs/laravel.log
```

---

## 🧪 Description des tests

### Tests de validation (✅ Obligatoires)

| Test                                         | Description                          |
| -------------------------------------------- | ------------------------------------ |
| `test_can_view_contact_form`                 | Vérifier que le formulaire s'affiche |
| `test_can_submit_contact_message`            | Soumettre un message valide          |
| `test_contact_message_requires_name`         | Le nom est obligatoire               |
| `test_contact_message_requires_email`        | L'email est obligatoire              |
| `test_contact_message_requires_phone`        | Le téléphone est obligatoire         |
| `test_contact_message_requires_subject`      | Le sujet est obligatoire             |
| `test_contact_message_requires_message_text` | Le texte du message est obligatoire  |
| `test_contact_message_requires_valid_email`  | L'email doit être valide             |

### Tests de limites (✅ Longueurs)

| Test                                      | Description                 |
| ----------------------------------------- | --------------------------- |
| `test_contact_message_name_max_length`    | Nom max 100 caractères      |
| `test_contact_message_subject_max_length` | Sujet max 100 caractères    |
| `test_contact_message_message_max_length` | Message max 2000 caractères |

### Tests principaux (🌟 Avec logs)

| Test                                   | Description                       |
| -------------------------------------- | --------------------------------- |
| `test_complete_contact_flow_with_logs` | Flux complet - affiche les étapes |
| `test_multiple_contact_messages`       | 3 messages d'affilée              |

---

## 🎯 Plan d'exécution recommandé

### Pour débuter rapidement (3 secondes):

```bash
php artisan test tests/Feature/ContactFormTest.php::test_can_submit_contact_message -v
```

### Pour voir le flux complet avec tous les logs:

```bash
php artisan test tests/Feature/ContactFormTest.php::test_complete_contact_flow_with_logs -v
```

### Pour tester TOUT et voir les logs:

```bash
bash run-contact-tests.sh
```

### Pour surveiller les logs en direct:

**Terminal 1:**

```bash
tail -f storage/logs/laravel.log
```

**Terminal 2:**

```bash
php artisan test tests/Feature/ContactFormTest.php::test_multiple_contact_messages -v
```

---

## 🔧 Configuration requise

Avant de lancer les tests, assurez-vous que:

1. ✅ **Base de données de test**: Configurée dans `phpunit.xml`

    ```xml
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
    ```

2. ✅ **Mail mailer**: Configuré pour les tests

    ```xml
    <env name="MAIL_MAILER" value="array"/>
    ```

3. ✅ **Logging**: Configuré dans `config/logging.php`

    ```php
    'default' => env('LOG_CHANNEL', 'stack'),
    'single' => [
        'path' => storage_path('logs/laravel.log'),
    ],
    ```

4. ✅ **Contact email**: Défini dans `config/mail.php`
    ```php
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'support@edupay.cm'),
        'name' => env('MAIL_FROM_NAME', 'EduPay'),
    ],
    ```

---

## 📝 Code du controller (ce qui génère les logs)

```php
public function submitContact(Request $request): RedirectResponse
{
    $data = $request->validate([
        'name' => ['required', 'string', 'max:100'],
        'email' => ['required', 'email', 'max:150'],
        'phone' => ['required', 'string', 'max:30'],
        'subject' => ['required', 'string', 'max:100'],
        'message' => ['required', 'string', 'max:2000'],
    ]);

    // 📝 Log 1: Message reçu
    Log::info('📧 NOUVEAU MESSAGE DE CONTACT', [
        'name' => $data['name'],
        'email' => $data['email'],
        'phone' => $data['phone'],
        'subject' => $data['subject'],
        'message_length' => strlen($data['message']),
        'timestamp' => now()->toDateTimeString(),
    ]);

    try {
        $recipientEmail = config('mail.from.address', 'support@edupay.cm');

        // 📝 Log 2: Tentative d'envoi
        Log::info("📨 Envoi de l'email à: {$recipientEmail}", [
            'from' => $data['email'],
            'name' => $data['name'],
        ]);

        Mail::to($recipientEmail)->send(new ContactMessageMail($data));

        // 📝 Log 3: Succès
        Log::info("✅ Email de contact envoyé avec succès", [
            'from' => $data['email'],
            'to' => $recipientEmail,
        ]);

    } catch (\Throwable $exception) {
        // 📝 Log 4: Erreur
        Log::error("❌ Erreur lors de l'envoi du message de contact", [
            'email' => $data['email'],
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        return back()->withInput()->with('error', 'Erreur lors de l\'envoi.');
    }

    return redirect()->route('contact')->with('success', 'Message envoyé!');
}
```

---

## 🐛 Troubleshooting

### ❌ Les tests fail

```bash
# Vérifier que les routes existent
php artisan route:list | grep contact

# Vérifier la configuration du mail
php artisan tinker
>>> config('mail.from.address')
```

### ❌ Les logs ne s'affichent pas

```bash
# Vérifier que le fichier log existe
ls -la storage/logs/

# Vérifier les permissions
chmod -R 777 storage/logs
```

### ❌ Le test dit "Email was not sent"

C'est NORMAL pour les tests. On utilise `Mail::fake()` pour ne pas envoyer de vrais emails. Les logs fonctionnent quand même!

### ❌ Je ne vois rien dans les logs

1. Vérifiez le niveau de log: `env('LOG_LEVEL', 'debug')`
2. Vérifiez que `storage/logs` a les bonnes permissions
3. Exécutez: `php artisan config:clear && php artisan cache:clear`

---

## ✨ Résultat attendu

Après `bash run-contact-tests.sh`, vous verrez:

```
✓ test_can_view_contact_form
✓ test_can_submit_contact_message
✓ test_contact_message_requires_name
✓ test_contact_message_requires_email
✓ test_contact_message_requires_phone
✓ test_contact_message_requires_subject
✓ test_contact_message_requires_message_text
✓ test_contact_message_requires_valid_email
✓ test_contact_message_name_max_length
✓ test_contact_message_subject_max_length
✓ test_contact_message_message_max_length
✓ test_complete_contact_flow_with_logs
✓ test_multiple_contact_messages

✅ 13 TESTS PASSED
```

Et dans `storage/logs/laravel.log`:

```
[2026-06-25 10:30:45] local.INFO: 📧 NOUVEAU MESSAGE DE CONTACT
[2026-06-25 10:30:45] local.INFO: 📨 Envoi de l'email à: support@edupay.cm
[2026-06-25 10:30:46] local.INFO: ✅ Email de contact envoyé avec succès
[2026-06-25 10:30:50] local.INFO: 📧 NOUVEAU MESSAGE DE CONTACT
[2026-06-25 10:30:50] local.INFO: 📨 Envoi de l'email à: support@edupay.cm
[2026-06-25 10:30:50] local.INFO: ✅ Email de contact envoyé avec succès
... (plus de logs pour les autres tests)
```

---

## 📞 Questions?

Si un test fail:

1. Regardez le message d'erreur du test
2. Vérifiez `storage/logs/laravel.log`
3. Regardez le code du test dans `tests/Feature/ContactFormTest.php`

---

**Bonne chance avec les tests! 🚀**
