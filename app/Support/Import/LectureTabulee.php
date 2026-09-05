<?php

declare(strict_types=1);

namespace App\Support\Import;

/**
 * Lecture d'un fichier tabulé (CSV / texte, §42). Le séparateur est détecté sur
 * la première ligne. La lecture xlsx passera par un autre lecteur et rendra la
 * MÊME forme — l'aperçu et l'import ne dépendent pas du format d'entrée
 * (l'écart .xlsx du .NET se levait ainsi, une branche d'un côté).
 *
 * @phpstan-type FichierTabule array{entetes: list<string>, lignes: list<array<string,string>>, separateur: string}
 */
final class LectureTabulee
{
    /** @return FichierTabule */
    public static function analyser(string $contenu): array
    {
        $contenu = preg_replace('/^\xEF\xBB\xBF/', '', $contenu) ?? $contenu; // BOM UTF-8
        $lignesTexte = preg_split('/\r\n|\r|\n/', trim($contenu)) ?: [];

        if ($lignesTexte === [] || $lignesTexte[0] === '') {
            return ['entetes' => [], 'lignes' => [], 'separateur' => ','];
        }

        $sep = self::detecterSeparateur($lignesTexte[0]);
        $entetes = array_map(self::normaliserEntete(...), str_getcsv($lignesTexte[0], $sep, '"', '\\'));

        $lignes = [];
        foreach (array_slice($lignesTexte, 1) as $texte) {
            if (trim($texte) === '') {
                continue;
            }
            $cellules = str_getcsv($texte, $sep, '"', '\\');
            $ligne = [];
            foreach ($entetes as $i => $entete) {
                $ligne[$entete] = trim((string) ($cellules[$i] ?? ''));
            }
            $lignes[] = $ligne;
        }

        return ['entetes' => $entetes, 'lignes' => $lignes, 'separateur' => $sep];
    }

    private static function detecterSeparateur(string $premiere): string
    {
        foreach ([';', "\t", ','] as $c) {
            if (str_contains($premiere, $c)) {
                return $c;
            }
        }

        return ',';
    }

    /** En-tête normalisé : minuscules, sans accents, espaces → underscore. */
    private static function normaliserEntete(string $e): string
    {
        $e = mb_strtolower(trim($e));
        $translit = \transliterator_transliterate('Any-Latin; Latin-ASCII', $e);
        $e = $translit !== false ? $translit : $e;

        return preg_replace('/[^a-z0-9]+/', '_', $e) ?? $e;
    }
}
