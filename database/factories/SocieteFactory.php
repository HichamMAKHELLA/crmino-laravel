<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Societe>
 */
class SocieteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'raison_sociale' => fake()->unique()->company(),
            'etat' => 'Prospect',
            'actif' => true,
        ];
    }
}
