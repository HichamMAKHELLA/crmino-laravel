<?php

declare(strict_types=1);

namespace App\Support\Import;

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

/**
 * Lecture d'un classeur .xlsx (§42, ÉCART-008 côté .NET). Rend la MÊME forme
 * que LectureTabulee : l'aperçu et l'import ne dépendent pas du format d'entrée.
 *
 * Un classeur est un format ACTIF, et l'on n'en tire que de l'INERTE : la valeur
 * CALCULÉE en cache, jamais le texte de la formule (setReadDataOnly), aucun lien
 * suivi, la PREMIÈRE feuille seule. Deux bornes contre la bombe zip : lignes et
 * colonnes plafonnées.
 */
final class LectureClasseur
{
    private const MAX_LIGNES = 20000;
    private const MAX_COLONNES = 64;

    /** @return array{entetes: list<string>, lignes: list<array<string,string>>, separateur: string} */
    public static function analyser(string $chemin): array
    {
        $lecteur = new Xlsx();
        $lecteur->setReadDataOnly(true);   // la valeur, jamais la formule
        $lecteur->setReadEmptyCells(false);
        $classeur = $lecteur->load($chemin);
        $feuille = $classeur->getSheet(0); // la première feuille seule

        $nbLignes = min($feuille->getHighestDataRow(), self::MAX_LIGNES);
        $nbColonnes = min(
            \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($feuille->getHighestDataColumn()),
            self::MAX_COLONNES,
        );

        // En-têtes (ligne 1).
        $entetes = [];
        for ($col = 1; $col <= $nbColonnes; $col++) {
            $val = (string) $feuille->getCell([$col, 1])->getValue();
            $entetes[] = self::normaliserEntete($val);
        }

        $lignes = [];
        for ($row = 2; $row <= $nbLignes; $row++) {
            $ligne = [];
            $vide = true;
            for ($col = 1; $col <= $nbColonnes; $col++) {
                // getFormattedValue rend la valeur CALCULÉE en cache, pas la formule.
                $cell = $feuille->getCell([$col, $row]);
                $val = trim((string) $cell->getFormattedValue());
                $ligne[$entetes[$col - 1]] = $val;
                if ($val !== '') {
                    $vide = false;
                }
            }
            if (! $vide) {
                $lignes[] = $ligne;
            }
        }

        $classeur->disconnectWorksheets();

        return ['entetes' => $entetes, 'lignes' => $lignes, 'separateur' => 'xlsx'];
    }

    private static function normaliserEntete(string $e): string
    {
        $e = mb_strtolower(trim($e));
        $translit = \transliterator_transliterate('Any-Latin; Latin-ASCII', $e);
        $e = $translit !== false ? $translit : $e;

        return preg_replace('/[^a-z0-9]+/', '_', $e) ?? $e;
    }
}
