<?php

declare(strict_types=1);

namespace App\Models\Referentiels;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Referentiel
{
    protected $table = 'regions';

    public function pays(): BelongsTo
    {
        return $this->belongsTo(Pays::class);
    }

    public function villes(): HasMany
    {
        return $this->hasMany(Ville::class);
    }
}
