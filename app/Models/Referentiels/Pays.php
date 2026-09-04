<?php

declare(strict_types=1);

namespace App\Models\Referentiels;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Pays extends Referentiel
{
    protected $table = 'pays';

    public function regions(): HasMany
    {
        return $this->hasMany(Region::class);
    }
}
