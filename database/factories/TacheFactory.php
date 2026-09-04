<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Referentiels\PrioriteTache;
use App\Models\Referentiels\TypeTache;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Tache>
 */
class TacheFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type_id' => TypeTache::query()->orderBy('ordre')->value('id'),
            'titre' => fake()->sentence(4),
            'assignee_id' => User::factory(),
            'priorite_id' => PrioriteTache::query()->orderBy('niveau')->value('id'),
            'statut' => 'AFaire',
            'actif' => true,
        ];
    }
}
