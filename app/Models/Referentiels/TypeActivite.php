<?php

declare(strict_types=1);

namespace App\Models\Referentiels;

class TypeActivite extends Referentiel
{
    protected $table = 'types_activite';

    // La pastille/le décompte (§39) se lisent sur `categorie`, jamais le libellé.
}
