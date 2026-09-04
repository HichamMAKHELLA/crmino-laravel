<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Normalisations de texte partagées. UN SEUL endroit retire les accents : deux
 * implémentations finiraient par diverger, et la détection de doublons
 * rapprocherait alors des fiches que l'import séparerait — sans que rien ne le
 * signale. Port de Domain/Texte du .NET.
 */
final class Texte
{
    /** « Société » devient « Societe » : décomposition Unicode, on retire les marques. */
    public static function retirerAccents(string $valeur): string
    {
        $decompose = \Normalizer::normalize($valeur, \Normalizer::FORM_D);
        // \p{Mn} = marques diacritiques sans chasse.
        $sansMarques = preg_replace('/\p{Mn}+/u', '', $decompose);

        return \Normalizer::normalize($sansMarques, \Normalizer::FORM_C);
    }

    /** « I.C.E. », « ice » et « I C E » se ramènent tous à « ICE ». */
    public static function reduireAuxAlphanumeriques(string $valeur): string
    {
        $sansAccent = mb_strtoupper(self::retirerAccents($valeur));

        return preg_replace('/[^\p{L}\p{N}]+/u', '', $sansAccent);
    }
}
