<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Portee;
use App\Models\Opportunite;
use App\Models\User;

/**
 * Habilitations de l'opportunité (§78). Consultation par ligne = RG-HAB-001.
 */
class OpportunitePolicy
{
    public function viewAny(User $u): bool
    {
        return $u->peut('opportunite.consulter');
    }

    public function create(User $u): bool
    {
        return $u->peut('opportunite.creer');
    }

    public function view(User $u, Opportunite $o): bool
    {
        if (! $u->peut('opportunite.consulter')) {
            return false;
        }
        $portee = $u->porteePour('opportunite.consulter')->effective($u->equipe_id);

        return match ($portee) {
            Portee::Toutes => true,
            Portee::Equipe => $o->proprietaire_id === $u->id
                || ($u->equipe_id !== null && $o->equipe_id === $u->equipe_id),
            Portee::Siennes => $o->proprietaire_id === $u->id,
            Portee::Aucune => false,
        };
    }
}
