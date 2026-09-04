<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\Portee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Le périmètre commercial par ligne — port de ClausePerimetre du .NET.
 *
 * UN SEUL endroit produit la restriction, appelé EXPLICITEMENT par les requêtes
 * (comme les dépôts .NET), jamais un global scope silencieux : un périmètre qui
 * fuit ne lève aucune erreur, il rend des lignes de trop.
 *
 * RG-HAB-001 — LES SIENNES, TOUJOURS. La propriété est un OU avec le périmètre,
 * jamais un ET. Gardée même portée fermée : un commercial qui perd une
 * habilitation ne doit pas perdre la vue de son propre travail.
 */
trait AvecPerimetre
{
    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeDansPerimetre(
        Builder $query,
        User $utilisateur,
        string $permission,
        string $colonneProprietaire = 'proprietaire_id',
        string $colonneEquipe = 'equipe_id',
    ): Builder {
        $portee = $utilisateur->porteePour($permission)->effective($utilisateur->equipe_id);

        // Portée ouverte : aucun filtre. On n'alourdit pas la requête d'un
        // prédicat « 1=1 » que l'optimiseur devrait écarter.
        if ($portee === Portee::Toutes) {
            return $query;
        }

        return $query->where(function (Builder $w) use ($utilisateur, $portee, $colonneProprietaire, $colonneEquipe): void {
            // RG-HAB-001 : la propriété d'abord, et toujours.
            $w->where($colonneProprietaire, $utilisateur->id);

            // effective() garantit qu'une portée Équipe porte bien une équipe.
            if ($portee === Portee::Equipe) {
                $w->orWhere($colonneEquipe, $utilisateur->equipe_id);
            }
        });
    }
}
