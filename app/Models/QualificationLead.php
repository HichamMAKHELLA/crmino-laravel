<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Qualification d'un lead (§13). Satellite 1-1 : la clé primaire est le lead_id,
 * écrit par remplacement. Pas d'horodatage de création — c'est celui du lead.
 */
class QualificationLead extends Model
{
    protected $table = 'qualification_leads';

    protected $primaryKey = 'lead_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'lead_id',
        'nb_sites', 'nb_agences', 'logiciel_actuel', 'erp_actuel', 'version_actuelle',
        'nb_utilisateurs', 'hebergement', 'base_donnees', 'prestataire_actuel',
        'usage_sage', 'version_sage', 'nb_utilisateurs_sage', 'revendeur_actuel',
        'contrat_sage', 'date_renouvellement_sage', 'modifie_le', 'modifie_par',
    ];

    protected function casts(): array
    {
        return [
            'nb_sites' => 'integer',
            'nb_agences' => 'integer',
            'nb_utilisateurs' => 'integer',
            'nb_utilisateurs_sage' => 'integer',
            'contrat_sage' => 'boolean',
            'date_renouvellement_sage' => 'date',
            'modifie_le' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
