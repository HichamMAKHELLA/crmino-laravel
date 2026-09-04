<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Referentiels\EtapePipeline;
use App\Models\Societe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Opportunite>
 *
 * Suppose les référentiels d'étapes semés. La société et le propriétaire sont
 * à préciser par le test (l'affaire naît sur une société, §25).
 */
class OpportuniteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'intitule' => 'Affaire '.fake()->unique()->numerify('####'),
            'societe_id' => Societe::factory(),
            'etape_id' => EtapePipeline::query()->where('categorie', 'Ouverte')->orderBy('ordre')->value('id'),
            'statut' => 'Ouverte',
            'probabilite' => 20,
            'montant_ht' => fake()->numberBetween(10000, 500000),
            'actif' => true,
        ];
    }
}
