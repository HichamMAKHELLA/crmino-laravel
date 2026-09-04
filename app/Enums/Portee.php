<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * L'étendue d'une permission (port de app.RolePermission.Portee du .NET).
 *
 * L'ordre est croissant et il compte : une portée plus haute inclut la
 * précédente. Aucune = permission non accordée (défaut = refus).
 */
enum Portee: string
{
    case Aucune = 'Aucune';
    case Siennes = 'Siennes';
    case Equipe = 'Equipe';
    case Toutes = 'Toutes';

    public function rang(): int
    {
        return match ($this) {
            self::Aucune => 0,
            self::Siennes => 1,
            self::Equipe => 2,
            self::Toutes => 3,
        };
    }

    /**
     * La portée Équipe sans équipe rattachée retombe sur Siennes.
     *
     * Sans cette réduction, un responsable sans équipe verrait soit rien, soit
     * tout, selon la façon dont le SQL traite le NULL — et les deux seraient
     * faux (RG-HAB, port de PerimetreUtilisateur.PorteeEffective).
     */
    public function effective(?int $equipeId): self
    {
        return $this === self::Equipe && $equipeId === null ? self::Siennes : $this;
    }
}
