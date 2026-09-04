<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Referentiels\PrioriteTache;
use App\Models\Referentiels\TypeTache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tâche (§20, §21). Assignée à une personne (assignee_id) ; terminer pose la
 * date d'achèvement. Fidèle à app.Tache.
 */
class Tache extends Model
{
    /** @use HasFactory<\Database\Factories\TacheFactory> */
    use HasFactory;

    protected $table = 'taches';

    protected $fillable = [
        'type_id', 'titre', 'description', 'lead_id', 'societe_id', 'contact_id',
        'opportunite_id', 'assignee_id', 'echeance_le', 'priorite_id', 'statut',
        'terminee_le', 'actif', 'cree_par',
    ];

    protected function casts(): array
    {
        return [
            'echeance_le' => 'datetime',
            'terminee_le' => 'datetime',
            'actif' => 'boolean',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(TypeTache::class, 'type_id');
    }

    public function priorite(): BelongsTo
    {
        return $this->belongsTo(PrioriteTache::class, 'priorite_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }
}
