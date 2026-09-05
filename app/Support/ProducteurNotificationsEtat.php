<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Production des notifications d'ÉTAT (§36). Contrairement aux événements (tâche
 * assignée…), rien ne se passe le jour où une tâche devient en retard : c'est le
 * TEMPS qui passe. Ces deux notifications demandent donc une HORLOGE — un job
 * planifié —, pas un appelant de plus.
 *
 * L'IDEMPOTENCE est le vrai problème : sans garde, un passage toutes les 15 min
 * émettrait des dizaines de rappels par jour. app.Notification est SA PROPRE
 * trace — une table d'état séparée pourrait diverger de ce qui a été écrit.
 * La garde porte sur l'ID de l'activité / de la tâche, JAMAIS sur la fiche
 * parente ni le libellé : deux RDV avec la même société le même jour, ou deux
 * tâches homonymes, doivent rester distincts.
 *
 * @phpstan-type Production array{rdv:int, taches:int}
 */
final class ProducteurNotificationsEtat
{
    /** @return Production */
    public function produireEtats(?Carbon $maintenant = null): array
    {
        $maintenant ??= Carbon::now();
        $debutJour = $maintenant->copy()->startOfDay();
        $finJour = $debutJour->copy()->addDay();

        return DB::transaction(function () use ($maintenant, $debutJour, $finJour): array {
            $rdv = $this->rappelsRendezVous($debutJour, $finJour);
            $taches = $this->tachesEnRetard($maintenant, $debutJour);

            return ['rdv' => $rdv, 'taches' => $taches];
        });
    }

    /**
     * « Rappel RDV » se lit sur la JOURNÉE, comme le compteur du §77. La cible
     * est l'ACTIVITÉ elle-même : viser la société confondrait deux RDV du même
     * jour. Produit une SEULE fois pour toujours — un événement daté ne se
     * rappelle pas deux fois.
     */
    private function rappelsRendezVous(Carbon $debutJour, Carbon $finJour): int
    {
        $rdvs = DB::table('activites as a')
            ->join('types_activite as ta', 'ta.id', '=', 'a.type_id')
            ->join('users as u', function ($j) {
                $j->on('u.id', '=', 'a.utilisateur_id')->where('u.actif', true);
            })
            ->where('a.actif', true)
            ->where('ta.categorie', 'Rdv')
            ->where('a.debut_le', '>=', $debutJour)
            ->where('a.debut_le', '<', $finJour)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('notifications_crmino as n')
                    ->where('n.type', Notification::RAPPEL_RENDEZ_VOUS)
                    ->whereColumn('n.cible_id', 'a.id');
            })
            ->get(['a.id', 'a.utilisateur_id', 'a.objet']);

        foreach ($rdvs as $r) {
            Notifications::notifier(
                (int) $r->utilisateur_id, 'Rendez-vous aujourd\'hui',
                $r->objet ?: 'Rendez-vous', 'Activite', (int) $r->id, Notification::RAPPEL_RENDEZ_VOUS,
            );
        }

        return $rdvs->count();
    }

    /**
     * Le retard se mesure sur l'INSTANT (une tâche due à 9 h l'est dès 9 h 01),
     * mais le rappel se borne à la JOURNÉE — sinon chaque passage en émettrait
     * un. Une tâche reste en retard des semaines : une fois PAR JOUR.
     */
    private function tachesEnRetard(Carbon $maintenant, Carbon $debutJour): int
    {
        $taches = DB::table('taches as t')
            ->join('users as u', function ($j) {
                $j->on('u.id', '=', 't.assignee_id')->where('u.actif', true);
            })
            ->where('t.actif', true)
            ->whereIn('t.statut', ['AFaire', 'EnCours'])
            ->whereNotNull('t.echeance_le')
            ->where('t.echeance_le', '<', $maintenant)
            ->whereNotExists(function ($q) use ($debutJour) {
                $q->select(DB::raw(1))->from('notifications_crmino as n')
                    ->where('n.type', Notification::TACHE_EN_RETARD)
                    ->whereColumn('n.cible_id', 't.id')
                    ->where('n.cree_le', '>=', $debutJour);
            })
            ->get(['t.id', 't.assignee_id', 't.titre']);

        foreach ($taches as $t) {
            Notifications::notifier(
                (int) $t->assignee_id, 'Tâche en retard',
                $t->titre, 'Tache', (int) $t->id, Notification::TACHE_EN_RETARD,
            );
        }

        return $taches->count();
    }
}
