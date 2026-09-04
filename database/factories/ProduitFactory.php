<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Referentiels\GammeProduit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Produit>
 */
class ProduitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'gamme_id' => GammeProduit::query()->orderBy('ordre')->value('id'),
            'code' => strtoupper(fake()->unique()->bothify('PRD-####')),
            'designation' => fake()->words(3, true),
            'prix_catalogue' => fake()->numberBetween(1000, 100000),
            'type' => 'Licence',
            'actif' => true,
        ];
    }
}
