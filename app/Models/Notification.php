<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Notification (§36). Il n'existe aucune permission « avoir des notifications » :
 * elles sont gardées par l'appartenance (utilisateur_id = l'appelant).
 */
class Notification extends Model
{
    protected $table = 'notifications_crmino';

    public $timestamps = false;

    protected $fillable = [
        'utilisateur_id', 'titre', 'texte', 'cible_type', 'cible_id', 'lue_le', 'cree_le',
    ];

    protected function casts(): array
    {
        return [
            'lue_le' => 'datetime',
            'cree_le' => 'datetime',
        ];
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
}
