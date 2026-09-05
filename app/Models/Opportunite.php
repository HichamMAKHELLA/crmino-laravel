<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\AvecPerimetre;
use App\Models\Referentiels\EtapePipeline;
use App\Models\Referentiels\MotifPerte;
use App\Models\Referentiels\Source;
use App\Support\Numerotation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Opportunité (§25). Née sur une société. Le montant pondéré (RG-OPP-001) est
 * une colonne GÉNÉRÉE — jamais fillable. Fidèle à app.Opportunite.
 */
class Opportunite extends Model
{
    /** @use HasFactory<\Database\Factories\OpportuniteFactory> */
    use AvecPerimetre;
    use HasFactory;

    protected $table = 'opportunites';

    protected $fillable = [
        'intitule', 'societe_id', 'lead_id', 'contact_principal_id',
        'proprietaire_id', 'equipe_id', 'source_id', 'campagne_id', 'etape_id',
        'statut', 'probabilite', 'montant_ht', 'date_cloture_estimee',
        'prochaine_action_le', 'prochaine_action_libelle', 'commentaire',
        'actif', 'cree_par',
    ];

    protected function casts(): array
    {
        return [
            'probabilite' => 'integer',
            'montant_ht' => 'decimal:4',
            'montant_pondere' => 'decimal:4',
            'actif' => 'boolean',
            'date_cloture_estimee' => 'date',
            'date_cloture' => 'date',
            'prochaine_action_le' => 'datetime',
            'derniere_activite_le' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Opportunite $o): void {
            if (blank($o->numero)) {
                $o->numero = Numerotation::suivant(Numerotation::PREFIXE_OPPORTUNITE);
            }
        });
    }

    /** Une affaire close ne change plus d'étape ni de montant (RG-OPP-005/006/007). */
    public function estClose(): bool
    {
        return $this->statut !== 'Ouverte';
    }

    public function societe(): BelongsTo
    {
        return $this->belongsTo(Societe::class);
    }

    public function etape(): BelongsTo
    {
        return $this->belongsTo(EtapePipeline::class, 'etape_id');
    }

    public function proprietaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proprietaire_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function motifPerte(): BelongsTo
    {
        return $this->belongsTo(MotifPerte::class, 'motif_perte_id');
    }

    public function contactPrincipal(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_principal_id');
    }

    /** @return HasMany<OpportuniteLigne, $this> */
    public function lignes(): HasMany
    {
        return $this->hasMany(OpportuniteLigne::class, 'opportunite_id');
    }
}
