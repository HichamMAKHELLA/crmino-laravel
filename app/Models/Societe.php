<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\AvecPerimetre;
use App\Models\Referentiels\Pays;
use App\Models\Referentiels\Region;
use App\Models\Referentiels\Secteur;
use App\Models\Referentiels\Source;
use App\Models\Referentiels\Ville;
use App\Support\Doublons;
use App\Support\Numerotation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Société (§6). Née d'une conversion de lead (§31). Porte l'état de la relation
 * commerciale (§32) et le point d'accroche Sage (§48). Fidèle à app.Societe.
 */
class Societe extends Model
{
    /** @use HasFactory<\Database\Factories\SocieteFactory> */
    use AvecPerimetre;
    use HasFactory;

    protected $table = 'societes';

    protected $fillable = [
        'raison_sociale', 'ice', 'rc', 'identifiant_fiscal',
        'secteur_id', 'activite_entreprise_id', 'effectif', 'ca_estime',
        'adresse', 'ville_id', 'region_id', 'pays_id', 'site_web', 'domaine_web',
        'telephone', 'email', 'etat', 'devenu_client_le',
        'proprietaire_id', 'equipe_id', 'source_id', 'campagne_id',
        'prochaine_action_le', 'derniere_activite_le', 'commentaire', 'actif', 'cree_par',
    ];

    protected function casts(): array
    {
        return [
            'effectif' => 'integer',
            'ca_estime' => 'decimal:4',
            'actif' => 'boolean',
            'devenu_client_le' => 'date',
            'prochaine_action_le' => 'datetime',
            'derniere_activite_le' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Societe $s): void {
            if (blank($s->numero)) {
                $s->numero = Numerotation::suivant(Numerotation::PREFIXE_SOCIETE);
            }
        });

        // §41 : colonnes de rapprochement recalculées à chaque écriture.
        static::saving(function (Societe $s): void {
            $s->raison_sociale_normalisee = Doublons::normaliserRaisonSociale($s->raison_sociale);
            $s->domaine_web = Doublons::extraireDomaine($s->site_web);
            $s->telephone_normalise = Doublons::normaliserTelephone($s->telephone);
        });
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

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function proprietaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proprietaire_id');
    }

    public function equipe(): BelongsTo
    {
        return $this->belongsTo(Equipe::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function activites(): HasMany
    {
        return $this->hasMany(Activite::class);
    }
}
