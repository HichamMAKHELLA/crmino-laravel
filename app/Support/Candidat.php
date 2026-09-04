<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Un doublon potentiel (§41), avec le critère qui l'a rapproché et son poids.
 * L'ICE est certain (100) ; le reste sont des indices.
 */
final readonly class Candidat
{
    public function __construct(
        public string $nature,
        public int $id,
        public string $numero,
        public ?string $raisonSociale,
        public ?string $ice,
        public string $critere,
        public int $poids,
    ) {}
}
