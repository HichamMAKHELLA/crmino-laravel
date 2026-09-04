<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Referentiels\TypeCampagne;
use App\Support\Numerotation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Campagne (§12). Son retour se calcule sur les leads et affaires rattachés.
 * Fidèle à app.Campagne. Le statut DÉCRIT, il n'arbitre pas — une campagne
 * « Planifiée » qui a déjà produit compte quand même.
 */
class Campagne extends Model
{
    /** @use HasFactory<\Database\Factories\CampagneFactory> */
    use HasFactory;

    protected $table = 'campagnes';

    protected $fillable = [
        'nom', 'type_campagne_id', 'date_debut', 'date_fin', 'responsable_id',
        'budget', 'cible', 'produit_id', 'statut', 'commentaire', 'actif', 'cree_par',
    ];

    protected function casts(): array
    {
        return [
            'budget' => 'decimal:4',
            'date_debut' => 'date',
            'date_fin' => 'date',
            'actif' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Campagne $c): void {
            if (blank($c->numero)) {
                $c->numero = Numerotation::suivant(Numerotation::PREFIXE_CAMPAGNE);
            }
        });
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(TypeCampagne::class, 'type_campagne_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function opportunites(): HasMany
    {
        return $this->hasMany(Opportunite::class);
    }
}
