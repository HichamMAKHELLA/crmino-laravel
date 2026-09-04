<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Referentiels\FonctionContact;
use App\Support\Doublons;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Contact (§13). Rattaché à un Lead OU à une Société (CK_Contact_Rattachement).
 * Porte les moyens de joindre, dont les colonnes normalisées du §41.
 */
class Contact extends Model
{
    /** @use HasFactory<\Database\Factories\ContactFactory> */
    use HasFactory;

    protected $fillable = [
        'lead_id', 'societe_id',
        'civilite', 'nom', 'prenom', 'fonction_id', 'fonction_libre',
        'telephone', 'gsm', 'whatsapp', 'email', 'linkedin',
        'principal', 'decisionnaire', 'commentaire', 'actif', 'cree_par',
    ];

    protected function casts(): array
    {
        return [
            'principal' => 'boolean',
            'decisionnaire' => 'boolean',
            'actif' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // §41 : le numéro normalisé se recalcule à chaque écriture. La détection
        // de doublons compare sur lui, jamais sur le numéro saisi.
        static::saving(function (Contact $contact): void {
            $contact->telephone_normalise = Doublons::normaliserTelephone($contact->telephone);
            $contact->gsm_normalise = Doublons::normaliserTelephone($contact->gsm);
        });
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function fonction(): BelongsTo
    {
        return $this->belongsTo(FonctionContact::class, 'fonction_id');
    }
}
