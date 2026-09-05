<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Note interne / commentaire (§45). Rattachement POLYMORPHE (cible_type /
 * cible_id) sur Lead / Societe / Contact / Opportunite. Seul l'AUTEUR corrige ou
 * retire sa note ; modifie_le reste nul tant que rien n'a bougé.
 */
class Commentaire extends Model
{
    /** @use HasFactory<\Database\Factories\CommentaireFactory> */
    use HasFactory;

    protected $table = 'commentaires';

    protected $fillable = ['cible_type', 'cible_id', 'texte', 'auteur_id', 'actif'];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
            'modifie_le' => 'datetime',
        ];
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }
}
