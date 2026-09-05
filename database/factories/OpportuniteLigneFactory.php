<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Opportunite;
use App\Models\Produit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\OpportuniteLigne>
 */
class OpportuniteLigneFactory extends Factory
{
    public function definition(): array
    {
        return [
            'opportunite_id' => Opportunite::factory(),
            'produit_id' => Produit::factory(),
            'designation' => fake()->words(3, true),
            'quantite' => fake()->numberBetween(1, 5),
            'prix_unitaire' => fake()->numberBetween(1000, 50000),
            'ordre' => 0,
        ];
    }
}
