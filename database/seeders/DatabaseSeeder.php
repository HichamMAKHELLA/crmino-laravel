<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Rôles, permissions et matrice du §78 — socle de sécurité.
        $this->call(SecuriteSeeder::class);

        // Compte administrateur de développement (local uniquement).
        User::factory()->create([
            'name' => 'Test User',
            'prenom' => 'Test',
            'nom' => 'User',
            'email' => 'test@example.com',
            'role_id' => Role::query()->where('code', 'ADMIN')->value('id'),
        ]);
    }
}
