<?php

declare(strict_types=1);

namespace App\Models\Referentiels;

/**
 * Critère de score §15. Porte un POIDS ; la somme des poids ÉVALUABLES donne le
 * plafond atteignable — jamais « /100 » en dur.
 */
class CritereScore extends Referentiel
{
    protected $table = 'criteres_score';

    protected function casts(): array
    {
        return array_merge(parent::casts(), ['poids' => 'integer']);
    }
}
