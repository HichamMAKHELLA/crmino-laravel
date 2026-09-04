<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ligne du journal d'audit (§46). Ajout seul — jamais modifiée ni supprimée.
 */
class AuditJournal extends Model
{
    protected $table = 'audit_journal';

    public $timestamps = false;

    protected $fillable = [
        'le', 'utilisateur_id', 'action', 'entite_type', 'entite_id',
        'champ', 'ancienne_valeur', 'nouvelle_valeur', 'adresse_ip', 'trace_id',
    ];

    protected function casts(): array
    {
        return ['le' => 'datetime'];
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
}
