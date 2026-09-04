<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Portee;
use App\Models\Lead;
use App\Models\User;

/**
 * Habilitations du Lead (§78). La consultation par ligne reprend RG-HAB-001 :
 * on voit toujours ses propres fiches, en OU avec la portée.
 */
class LeadPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->peut('lead.consulter');
    }

    public function view(User $u, Lead $lead): bool
    {
        if (! $u->peut('lead.consulter')) {
            return false;
        }

        return $this->dansPerimetre($u, $lead, 'lead.consulter');
    }

    public function create(User $u): bool
    {
        return $u->peut('lead.creer');
    }

    public function update(User $u, Lead $lead): bool
    {
        return $u->peut('lead.modifier') && $this->dansPerimetre($u, $lead, 'lead.modifier');
    }

    private function dansPerimetre(User $u, Lead $lead, string $permission): bool
    {
        $portee = $u->porteePour($permission)->effective($u->equipe_id);

        return match ($portee) {
            Portee::Toutes => true,
            Portee::Equipe => $lead->proprietaire_id === $u->id
                || ($u->equipe_id !== null && $lead->equipe_id === $u->equipe_id),
            Portee::Siennes => $lead->proprietaire_id === $u->id,
            Portee::Aucune => false,
        };
    }
}
