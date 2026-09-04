<?php

declare(strict_types=1);

namespace App\Models\Referentiels;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Base des référentiels (port de la forme commune ref.*). Pas de timestamps :
 * une table de référence n'en porte pas.
 */
abstract class Referentiel extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['actif' => 'boolean', 'systeme' => 'boolean'];
    }

    /** @param Builder<static> $query */
    public function scopeActif(Builder $query): Builder
    {
        return $query->where('actif', true);
    }

    /** @param Builder<static> $query */
    public function scopeOrdonne(Builder $query): Builder
    {
        return $query->orderBy('ordre');
    }

    /**
     * RG-REF-001 — une valeur SOCLE (`systeme`) ne se retire pas : un calcul la
     * nomme, la supprimer casserait une règle en silence. Elle se RENOMME, mais
     * ne disparaît pas.
     */
    public function estRetirable(): bool
    {
        return ! $this->systeme;
    }
}
