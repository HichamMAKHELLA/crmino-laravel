<?php

declare(strict_types=1);

namespace App\Models\Referentiels;

class EtapePipeline extends Referentiel
{
    protected $table = 'etapes_pipeline';

    protected function casts(): array
    {
        return array_merge(parent::casts(), ['probabilite' => 'integer']);
    }
}
