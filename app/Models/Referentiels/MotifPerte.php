<?php

declare(strict_types=1);

namespace App\Models\Referentiels;

class MotifPerte extends Referentiel
{
    protected $table = 'motifs_perte';

    protected function casts(): array
    {
        return array_merge(parent::casts(), ['commentaire_obligatoire' => 'boolean']);
    }
}
