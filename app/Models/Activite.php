<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Referentiels\TypeActivite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Activité (§19, §73). Rattachée à un lead OU une société. Porte la prochaine
 * action, répercutée sur la fiche parente (§20).
 */
class Activite extends Model
{
    /** @use HasFactory<\Database\Factories\ActiviteFactory> */
    use HasFactory;

    protected $table = 'activites';

    protected $fillable = [
        'type_id', 'lead_id', 'societe_id', 'contact_id', 'opportunite_id', 'utilisateur_id',
        'debut_le', 'duree_minutes', 'objet', 'commentaire', 'resultat',
        'prochaine_action_le', 'prochaine_action_libelle', 'actif', 'cree_par',
    ];

    protected function casts(): array
    {
        return [
            'debut_le' => 'datetime',
            'prochaine_action_le' => 'datetime',
            'duree_minutes' => 'integer',
            'actif' => 'boolean',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(TypeActivite::class, 'type_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function societe(): BelongsTo
    {
        return $this->belongsTo(Societe::class);
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
}
