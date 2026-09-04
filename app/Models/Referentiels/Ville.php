<?php

declare(strict_types=1);

namespace App\Models\Referentiels;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ville extends Referentiel
{
    protected $table = 'villes';

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
