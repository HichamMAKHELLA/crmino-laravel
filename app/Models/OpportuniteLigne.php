<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ligne d'opportunité (§28). Le montant HT est GÉNÉRÉ par le schéma
 * (quantité × prix unitaire) : jamais dans $fillable, jamais écrit par l'app.
 * C'est ce total que la ventilation du §76 somme, par produit.
 */
class OpportuniteLigne extends Model
{
    /** @use HasFactory<\Database\Factories\OpportuniteLigneFactory> */
    use HasFactory;

    protected $table = 'opportunite_lignes';

    protected $fillable = [
        'opportunite_id', 'produit_id', 'designation', 'quantite', 'unite',
        'prix_unitaire', 'ordre',
    ];

    protected function casts(): array
    {
        return [
            'quantite' => 'decimal:4',
            'prix_unitaire' => 'decimal:4',
            'montant_ht' => 'decimal:4',
            'ordre' => 'integer',
        ];
    }

    public function opportunite(): BelongsTo
    {
        return $this->belongsTo(Opportunite::class, 'opportunite_id');
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }
}
