<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Document>
 *
 * La cible (cible_type / cible_id) est à préciser par le test.
 */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cible_type' => 'Lead',
            'nom' => fake()->words(2, true).'.pdf',
            'nom_stockage' => fake()->unique()->sha1().'.pdf',
            'chemin_stockage' => 'documents/'.fake()->sha1().'.pdf',
            'content_type' => 'application/pdf',
            'taille_octets' => fake()->numberBetween(1000, 500000),
            'actif' => true,
            'depose_par' => User::factory(),
        ];
    }
}
