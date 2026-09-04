<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Referentiels\TypeActivite;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Activite>
 */
class ActiviteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type_id' => TypeActivite::query()->orderBy('ordre')->value('id'),
            'utilisateur_id' => User::factory(),
            'debut_le' => now(),
            'objet' => fake()->sentence(3),
            'actif' => true,
        ];
    }
}
