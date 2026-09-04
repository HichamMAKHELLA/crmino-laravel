<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Referentiels\Source;
use App\Models\Referentiels\StatutLead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Lead>
 *
 * Suppose les référentiels semés (source et statut obligatoires sur le Lead).
 */
class LeadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'raison_sociale' => fake()->unique()->company(),
            'source_id' => Source::query()->inRandomOrder()->value('id'),
            'statut_id' => StatutLead::query()->inRandomOrder()->value('id'),
            'actif' => true,
        ];
    }
}
