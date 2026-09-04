<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Referentiels\GammeProduit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Produit du catalogue (§28). Son code est réservé à vie (UQ_Produit_Code).
 * Le retrait est logique (§47) : un produit retiré revient par réactivation,
 * jamais par un doublon sous un autre code.
 */
class Produit extends Model
{
    /** @use HasFactory<\Database\Factories\ProduitFactory> */
    use HasFactory;

    protected $table = 'produits';

    protected $fillable = [
        'gamme_id', 'code', 'designation', 'prix_catalogue', 'unite', 'type',
        'ordre', 'actif', 'cree_par',
    ];

    protected function casts(): array
    {
        return [
            'prix_catalogue' => 'decimal:4',
            'ordre' => 'integer',
            'actif' => 'boolean',
        ];
    }

    public function gamme(): BelongsTo
    {
        return $this->belongsTo(GammeProduit::class, 'gamme_id');
    }
}
