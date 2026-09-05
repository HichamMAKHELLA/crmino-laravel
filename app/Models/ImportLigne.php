<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ligne d'un lot d'import (§42). Garde son contenu BRUT : « ligne 7 rejetée »
 * ne dit pas quoi corriger — sans le contenu, l'import se rejoue à l'aveugle.
 */
class ImportLigne extends Model
{
    protected $table = 'import_lignes';

    public $timestamps = false;

    protected $fillable = ['import_lot_id', 'numero_ligne', 'contenu', 'resultat', 'motif', 'cible_id'];

    protected function casts(): array
    {
        return ['numero_ligne' => 'integer'];
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(ImportLot::class, 'import_lot_id');
    }
}
