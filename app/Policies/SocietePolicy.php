<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Portee;
use App\Models\Societe;
use App\Models\User;

/**
 * Habilitations de la société (§78). Consultation par ligne = RG-HAB-001.
 * Pas de `create` : une société naît d'une conversion (§31).
 */
class SocietePolicy
{
    public function viewAny(User $u): bool
    {
        return $u->peut('societe.consulter');
    }

    public function view(User $u, Societe $societe): bool
    {
        if (! $u->peut('societe.consulter')) {
            return false;
        }
        $portee = $u->porteePour('societe.consulter')->effective($u->equipe_id);

        return match ($portee) {
            Portee::Toutes => true,
            Portee::Equipe => $societe->proprietaire_id === $u->id
                || ($u->equipe_id !== null && $societe->equipe_id === $u->equipe_id),
            Portee::Siennes => $societe->proprietaire_id === $u->id,
            Portee::Aucune => false,
        };
    }
}
