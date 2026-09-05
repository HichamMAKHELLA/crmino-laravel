<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Message de la file de sortie Sage (§48). Déposé dans la transaction du gain.
 * Pas de timestamps Laravel : le schéma porte cree_le / traite_le (fidèle à
 * app.Outbox).
 */
class Outbox extends Model
{
    protected $table = 'outbox';

    public $timestamps = false;

    protected $fillable = ['type', 'charge', 'etat', 'tentatives', 'derniere_erreur'];

    protected function casts(): array
    {
        return [
            'tentatives' => 'integer',
            'cree_le' => 'datetime',
            'traite_le' => 'datetime',
        ];
    }
}
