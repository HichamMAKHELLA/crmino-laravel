<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\ProducteurNotificationsEtat;
use Illuminate\Console\Command;

/**
 * Produit les notifications d'ÉTAT du §36 (rappel RDV, tâche en retard).
 * Équivalent du drapeau .NET `--notifier`. Planifiée par le Scheduler ; sûre à
 * rejouer (idempotente).
 */
class ProduireNotificationsEtat extends Command
{
    protected $signature = 'crmino:notifier';

    protected $description = 'Produit les notifications d\'état (§36) : rappels de RDV et tâches en retard.';

    public function handle(ProducteurNotificationsEtat $producteur): int
    {
        $r = $producteur->produireEtats();

        $this->info("Notifications produites — RDV : {$r['rdv']}, tâches en retard : {$r['taches']}.");

        return self::SUCCESS;
    }
}
