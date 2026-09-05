<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Amorçage de PRODUCTION (§12). Ne pose que le socle indispensable : rôles,
 * permissions, matrice §78, et les référentiels. AUCUN compte de démonstration
 * ni catalogue d'exemple — le premier admin se pose par `crmino:creer-admin`,
 * le catalogue réel se saisit dans l'application (§28).
 */
class ProductionSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(SecuriteSeeder::class);
        $this->call(ReferentielsSeeder::class);
        $this->call(ReferentielsGeoSeeder::class);
    }
}
