<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// §36 : les notifications d'état demandent une HORLOGE (rien ne se passe le jour
// où une tâche devient en retard, c'est le temps qui passe). Toutes les 15 min,
// comme le .NET ; la commande est idempotente, donc sûre à rejouer.
Schedule::command('crmino:notifier')->everyFifteenMinutes()->withoutOverlapping();
