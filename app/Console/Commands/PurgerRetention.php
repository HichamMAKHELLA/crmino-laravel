<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Purge de rétention (§72). SIMULATION par défaut : ne supprime rien sans
 * `--executer`. Les durées viennent de la configuration (contractuelles, §72),
 * jamais codées en dur. La suppression se fait par LOTS pour ne pas escalader
 * les verrous ni gonfler le journal des transactions.
 *
 * Le journal d'audit est append-only pour l'application ; seule cette commande,
 * lancée hors application par un compte habilité, en retire les lignes échues.
 */
class PurgerRetention extends Command
{
    protected $signature = 'crmino:purger {--executer : Applique réellement (sinon simulation)}';

    protected $description = 'Purge les lignes échues (§72) : journal d\'audit et notifications lues. Simulation par défaut.';

    public function handle(): int
    {
        $executer = (bool) $this->option('executer');
        $lot = (int) config('crmino.purge_taille_lot');
        $maintenant = Carbon::now();

        $auditAvant = $maintenant->copy()->subYears((int) config('crmino.retention.audit_annees'));
        $notifsAvant = $maintenant->copy()->subDays((int) config('crmino.retention.notifications_jours'));

        $audit = $this->purger('audit_journal', fn ($q) => $q->where('le', '<', $auditAvant), $lot, $executer);
        // Seules les notifications LUES se purgent : une non lue reste due.
        $notifs = $this->purger('notifications_crmino',
            fn ($q) => $q->whereNotNull('lue_le')->where('cree_le', '<', $notifsAvant), $lot, $executer);

        $verbe = $executer ? 'supprimées' : 'à supprimer (simulation)';
        $this->info("Journal d'audit : {$audit} ligne(s) {$verbe} (avant {$auditAvant->toDateString()}).");
        $this->info("Notifications lues : {$notifs} ligne(s) {$verbe} (avant {$notifsAvant->toDateString()}).");

        if (! $executer) {
            $this->warn('Simulation : rien n\'a été supprimé. Relancez avec --executer pour appliquer.');
        }

        return self::SUCCESS;
    }

    /**
     * @param  callable(\Illuminate\Database\Query\Builder): \Illuminate\Database\Query\Builder  $filtre
     */
    private function purger(string $table, callable $filtre, int $lot, bool $executer): int
    {
        $compte = $filtre(DB::table($table))->count();

        if ($executer) {
            do {
                $supprimees = $filtre(DB::table($table))->limit($lot)->delete();
            } while ($supprimees > 0);
        }

        return $compte;
    }
}
