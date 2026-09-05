<?php

declare(strict_types=1);

namespace App\Support;

/**
 * RG-SOC-001 (§32). L'état de la société décrit la RELATION commerciale, distinct
 * du retrait de la fiche : une société inactive reste visible, cherchable et
 * comptée dans les rapports — c'est justement ce qui permet de la relancer.
 *
 * Un CLIENT ne redevient jamais prospect : il a des affaires gagnées, un
 * historique, une date de passage — rétrograder effacerait la trace commerciale
 * du gain. Une relation qui s'éteint devient INACTIVE, ce qui se dit et se
 * mesure. Depuis « Inactif », les deux reprises sont ouvertes.
 *
 * UNE SEULE définition, deux lecteurs : le jugement (admise) et la liste des
 * cibles (cibles) s'en dérivent. Deux règles écrites ailleurs divergeraient.
 */
final class TransitionSociete
{
    public const ETATS = ['Prospect', 'Client', 'Inactif'];

    /** @var array<string, list<string>> les cibles atteignables depuis chaque état. */
    private const MATRICE = [
        'Prospect' => ['Client', 'Inactif'],
        'Client' => ['Inactif'],
        'Inactif' => ['Prospect', 'Client'],
    ];

    public static function admise(string $actuel, string $cible): bool
    {
        if ($actuel === $cible) {
            return true;
        }

        return in_array($cible, self::MATRICE[$actuel] ?? [], true);
    }

    /** @return list<string> les états vers lesquels la société peut aller (hors état courant). */
    public static function cibles(string $actuel): array
    {
        return self::MATRICE[$actuel] ?? [];
    }
}
