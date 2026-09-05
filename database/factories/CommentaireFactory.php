<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Commentaire>
 *
 * La cible (cible_type / cible_id) est à préciser par le test.
 */
class CommentaireFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cible_type' => 'Lead',
            'texte' => fake()->sentence(),
            'auteur_id' => User::factory(),
            'actif' => true,
        ];
    }
}
