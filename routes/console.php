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
