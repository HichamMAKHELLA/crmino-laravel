<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Les 4 rôles socles (port du seed .NET). `systeme = true` les protège du
 * retrait (RG-REF-001). Le catalogue des permissions et la matrice du §78
 * suivront dans une tranche dédiée.
 */
class SecuriteSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['code' => 'ADMIN', 'libelle' => 'Administrateur'],
            ['code' => 'DIRECTION', 'libelle' => 'Direction'],
            ['code' => 'RESP_COMM', 'libelle' => 'Responsable commercial'],
            ['code' => 'COMMERCIAL', 'libelle' => 'Commercial'],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(
                ['code' => $role['code']],
                ['libelle' => $role['libelle'], 'systeme' => true, 'actif' => true],
            );
        }
    }
}
