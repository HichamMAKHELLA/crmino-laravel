<?php

declare(strict_types=1);

namespace App\Models\Referentiels;

use Illuminate\Database\Eloquent\Model;

/**
 * Paramètre d'une alerte commerciale (§35). Porte le délai et, pour certaines,
 * un seuil de montant. Deux drapeaux se DÉRIVENT du calcul (surveille, seuil_lu),
 * jamais saisis — voir App\Support\Alertes.
 */
class ParametreAlerte extends Model
{
    protected $table = 'parametres_alerte';

    public $timestamps = false;

    protected $fillable = ['delai_jours', 'seuil_montant', 'actif'];

    protected function casts(): array
    {
        return [
            'delai_jours' => 'integer',
            'seuil_montant' => 'decimal:4',
            'actif' => 'boolean',
            'systeme' => 'boolean',
        ];
    }
}
