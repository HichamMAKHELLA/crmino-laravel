<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\AvecPerimetre;
use App\Models\Referentiels\Pays;
use App\Models\Referentiels\Region;
use App\Models\Referentiels\Secteur;
use App\Models\Referentiels\Source;
use App\Models\Referentiels\StatutLead;
use App\Models\Referentiels\Ville;
use App\Support\Doublons;
use App\Support\Numerotation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lead (§13). Porteur d'un périmètre commercial : le trait AvecPerimetre borne
 * chaque liste à ce dont l'utilisateur est propriétaire, en OU avec sa portée.
 */
class Lead extends Model
{
    /** @use HasFactory<\Database\Factories\LeadFactory> */
    use AvecPerimetre;
    use HasFactory;

    protected $fillable = [
        'raison_sociale', 'ice', 'rc',
        'secteur_id', 'activite_entreprise_id', 'effectif', 'ca_estime',
        'adresse', 'ville_id', 'region_id', 'pays_id', 'site_web', 'domaine_web',
        'source_id', 'campagne_id', 'statut_id', 'score',
        'proprietaire_id', 'equipe_id', 'affecte_le',
        'prochaine_action_le', 'prochaine_action_libelle', 'derniere_activite_le',
        'commentaire', 'actif', 'cree_par',
    ];

    protected function casts(): array
    {
        return [
            'effectif' => 'integer',
            'ca_estime' => 'decimal:4',
            'score' => 'integer',
            'actif' => 'boolean',
            'affecte_le' => 'datetime',
            'prochaine_action_le' => 'datetime',
            'derniere_activite_le' => 'datetime',
            'converti_le' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Numéro de séquence (§10) posé à la création, jamais réécrit ensuite.
        static::creating(function (Lead $lead): void {
            if (blank($lead->numero)) {
                $lead->numero = Numerotation::suivant(Numerotation::PREFIXE_LEAD);
            }
        });

        // §41 : les colonnes de rapprochement se recalculent à CHAQUE écriture.
        // Les laisser figées ferait retrouver l'ANCIENNE raison sociale après un
        // renommage, et créer un doublon de la fiche qu'on vient de corriger.
        static::saving(function (Lead $lead): void {
            $lead->raison_sociale_normalisee = Doublons::normaliserRaisonSociale($lead->raison_sociale);
            $lead->domaine_web = Doublons::extraireDomaine($lead->site_web);
        });
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function statut(): BelongsTo
    {
        return $this->belongsTo(StatutLead::class, 'statut_id');
    }

    public function secteur(): BelongsTo
    {
        return $this->belongsTo(Secteur::class);
    }

    public function ville(): BelongsTo
    {
        return $this->belongsTo(Ville::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function pays(): BelongsTo
    {
        return $this->belongsTo(Pays::class);
    }

    public function proprietaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proprietaire_id');
    }

    public function equipe(): BelongsTo
    {
        return $this->belongsTo(Equipe::class);
    }

    public function contacts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function qualification(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(QualificationLead::class, 'lead_id');
    }
}
