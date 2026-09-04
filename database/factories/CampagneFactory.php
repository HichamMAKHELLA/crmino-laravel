<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Referentiels\TypeCampagne;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Campagne>
 */
class CampagneFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom' => 'Campagne '.fake()->unique()->numerify('####'),
            'type_campagne_id' => TypeCampagne::query()->orderBy('ordre')->value('id'),
            'date_debut' => now()->subMonth(),
            'responsable_id' => User::factory(),
            'statut' => 'Planifiee',
            'actif' => true,
        ];
    }
}
