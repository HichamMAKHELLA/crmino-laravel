<?php

declare(strict_types=1);

namespace App\Models\Referentiels;

class StatutLead extends Referentiel
{
    protected $table = 'statuts_lead';

    // La conversion se lit sur societe_id du lead, jamais sur ce libellé (§13).
}
