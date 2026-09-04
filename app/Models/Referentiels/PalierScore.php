<?php

declare(strict_types=1);

namespace App\Models\Referentiels;

/**
 * Palier de score (§15). Ses bornes classent un score en Froid / Tiède / Chaud /
 * Très chaud. « Non scoré » n'est PAS un score de zéro (RG-IND-001) : l'absence
 * de score appelle une qualification, un zéro range en Froid.
 */
class PalierScore extends Referentiel
{
    protected $table = 'palier_scores';

    protected function casts(): array
    {
        return [
            'borne_min' => 'integer',
            'borne_max' => 'integer',
            'actif' => 'boolean',
            'systeme' => 'boolean',
        ];
    }

    /** Le palier d'un score, ou null si aucun ne le couvre. */
    public static function pour(int $score): ?self
    {
        return self::query()
            ->where('actif', true)
            ->where('borne_min', '<=', $score)
            ->where('borne_max', '>=', $score)
            ->orderBy('ordre')
            ->first();
    }
}
