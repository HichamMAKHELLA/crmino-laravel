<?php

declare(strict_types=1);

namespace App\Models\Referentiels;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GammeProduit extends Referentiel
{
    protected $table = 'gammes_produit';

    public function famille(): BelongsTo
    {
        return $this->belongsTo(FamilleProduit::class, 'famille_id');
    }
}
