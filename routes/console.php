<?php

use Illuminate\Support\Facades\Schedule;
use App\Jobs\SendSmsRelanceImpaye;
use App\Jobs\SendAlerteImpayeJournaliere;

// E07 — Relances SMS impayés J-5 avant chaque échéance
Schedule::job(new SendSmsRelanceImpaye)
    ->dailyAt('07:00')
    ->timezone('Africa/Douala')
    ->withoutOverlapping()
    ->name('sms-relance-impaye');

// F12-C — Alertes impayés email + SMS chaque soir à 18h00
Schedule::job(new SendAlerteImpayeJournaliere)
    ->dailyAt('18:00')
    ->timezone('Africa/Douala')
    ->withoutOverlapping()
    ->name('alerte-impaye-journaliere');

// Filet de securite AangaraaPay — reverifie les paiements en_attente
// independamment du webhook (peu fiable) et du polling client (limite a ~20 min)
Schedule::command('aangaraa:reconcilie')->everyTwoMinutes();

// Abonnements : le statut (actif / grace_period / expire) est une valeur
// DERIVEE de date_fin et grace_period_fin. Il n'etait recalcule que par le
// middleware CheckAbonnement, donc seulement quand un utilisateur de
// l'etablissement visitait une page : le back office pouvait afficher
// « actif » pour une periode terminee la veille (constate le 27/09/2026).
// hourly() suffit : la precision n'a de sens qu'a la journee, et le
// middleware continue d'appliquer le blocage immediat cote etablissement.
Schedule::command('abonnements:synchroniser')
    ->hourly()
    ->withoutOverlapping()
    ->name('synchronisation-abonnements');

// Filet de securite des reversements — rattrape les commissions restees
// 'calculee' (queue perdue, job echoue avant de passer a 'echec'...).
// Volontairement limite aux etats sur lesquels un rejeu automatique ne peut
// pas payer deux fois : 'a_verifier' et 'echec' exigent un humain.
Schedule::command('aangaraa:reversements:rejouer')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->name('rejeu-reversements-aangaraa');

// Diagnostic de la configuration AangaraaPay (notify_url joignable ou non).
// Ecrit dans les logs : un .env errone reste invisible sinon.
Schedule::call(function () {
    $controle = app(App\Services\AangaraaPayService::class)
        ->verifierNotifyUrl(config('services.aangaraa.notify_url'));

    if (! $controle['ok']) {
        Illuminate\Support\Facades\Log::critical('Configuration AangaraaPay : ' . $controle['raison']);
    }
})->hourly()->name('diagnostic-aangaraa');

// Traitement de la file d'attente (QUEUE_CONNECTION=database) — sécurité E-02.
// Sur hébergement mutualisé (o2switch), pas de worker permanent possible :
// on traite les jobs en attente chaque minute via le scheduler déjà actif,
// avec --stop-when-empty pour ne pas laisser tourner un process indéfiniment.
//
// `withoutOverlapping(2)` et non le défaut (1440 minutes = 24 h) : sur mutualisé,
// un `queue:work` tué en cours de route laisse le mutex posé, et le défaut
// bloquait alors la file EN SILENCE pendant 24 h. Constat réel le 30/09/2026 :
// 2 reversements ont attendu 1 h 45 sans traitement, sans une seule erreur,
// `php artisan schedule:list` affichant « Has Mutex ». Deux minutes suffisent
// car le process se termine de lui-même après --max-time=50.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping(2)
    ->name('queue-worker-minute');
