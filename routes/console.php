<?php

use Illuminate\Support\Facades\Schedule;
use App\Jobs\SendSmsRelanceImpaye;

// E07 — Relances automatiques impayés J-5 avant chaque echeance
Schedule::job(new SendSmsRelanceImpaye)->dailyAt('07:00');
