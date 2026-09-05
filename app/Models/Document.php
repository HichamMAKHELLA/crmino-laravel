<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Referentiels\TypeDocument;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Document (§44). Rattachement POLYMORPHE (cible_type / cible_id). Le nom
 * d'origine (nom) et le nom de stockage (nom_stockage) sont distincts : un nom
 * fourni par l'utilisateur n'atteint jamais le disque (§58).
 */
class Document extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentFactory> */
    use HasFactory;

    protected $table = 'documents';

    protected $fillable = [
        'cible_type', 'cible_id', 'type_document_id', 'nom', 'nom_stockage',
        'chemin_stockage', 'content_type', 'taille_octets', 'actif', 'depose_par',
    ];

    protected function casts(): array
    {
        return [
            'taille_octets' => 'integer',
            'actif' => 'boolean',
            'depose_le' => 'datetime',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(TypeDocument::class, 'type_document_id');
    }

    public function deposant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'depose_par');
    }
}
