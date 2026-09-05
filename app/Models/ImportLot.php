<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Referentiels\Source;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lot d'import (§42). Sa colonne statut distingue un lot ÉCHOUÉ d'un import
 * réussi sur un fichier vide — sans elle, les deux se liraient pareil.
 */
class ImportLot extends Model
{
    protected $table = 'import_lots';

    public $timestamps = false;

    protected $fillable = [
        'nom_fichier', 'type', 'lignes_total', 'lignes_importees', 'lignes_rejetees',
        'lignes_doublons', 'statut', 'campagne_id', 'source_id', 'lance_par', 'termine_le',
    ];

    protected function casts(): array
    {
        return [
            'lignes_total' => 'integer',
            'lignes_importees' => 'integer',
            'lignes_rejetees' => 'integer',
            'lignes_doublons' => 'integer',
            'lance_le' => 'datetime',
            'termine_le' => 'datetime',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    /** @return HasMany<ImportLigne, $this> */
    public function lignes(): HasMany
    {
        return $this->hasMany(ImportLigne::class, 'import_lot_id');
    }
}
