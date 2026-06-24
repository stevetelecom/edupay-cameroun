<?php

use Illuminate\Support\Facades\Schedule;
use App\Jobs\SendSmsRelanceImpaye;
use App\Jobs\SendAlerteImpayeJournaliere;

// E07 — Relances automatiques impayés J-5 avant chaque echeance
Schedule::job(new SendSmsRelanceImpaye)->dailyAt('07:00');

// F12 — Alertes impayés automatiques (frais dépassant la date d'échéance)
Schedule::job(new SendAlerteImpayeJournaliere)->dailyAt('18:00');
