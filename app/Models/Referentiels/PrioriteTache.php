<?php

declare(strict_types=1);

namespace App\Models\Referentiels;

class PrioriteTache extends Referentiel
{
    protected $table = 'priorites_tache';

    // Le TRI des tâches se fait sur le niveau, jamais sur le libellé (§20).
    protected function casts(): array
    {
        return array_merge(parent::casts(), ['niveau' => 'integer']);
    }
}
