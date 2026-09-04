<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Produit;
use App\Models\Referentiels\FamilleProduit;
use App\Models\Referentiels\GammeProduit;
use Illuminate\Database\Seeder;

/**
 * Catalogue de démonstration (§28) — gamme Sage 100.
 */
class CatalogueSeeder extends Seeder
{
    public function run(): void
    {
        $logiciel = FamilleProduit::query()->firstOrCreate(['code' => 'LOGICIEL'], ['libelle' => 'Logiciels', 'ordre' => 10, 'systeme' => true]);
        $service = FamilleProduit::query()->firstOrCreate(['code' => 'SERVICE'], ['libelle' => 'Services', 'ordre' => 20, 'systeme' => true]);

        $sage = GammeProduit::query()->firstOrCreate(['code' => 'SAGE100'], ['famille_id' => $logiciel->id, 'libelle' => 'Sage 100', 'ordre' => 10, 'systeme' => true]);
        $presta = GammeProduit::query()->firstOrCreate(['code' => 'PRESTA'], ['famille_id' => $service->id, 'libelle' => 'Prestations', 'ordre' => 20, 'systeme' => true]);

        $produits = [
            ['SAGE100_COMPTA', 'Sage 100 Comptabilité', 15000, 'Licence', $sage->id, 10],
            ['SAGE100_GESCO', 'Sage 100 Gestion Commerciale', 18000, 'Licence', $sage->id, 20],
            ['SAGE100_PAIE', 'Sage 100 Paie & RH', 22000, 'Licence', $sage->id, 30],
            ['MAINT_SAGE', 'Contrat de maintenance annuel', 3000, 'Abonnement', $presta->id, 40],
            ['FORM_SAGE', 'Formation Sage 100 (jour)', 2500, 'Formation', $presta->id, 50],
        ];
        foreach ($produits as [$code, $designation, $prix, $type, $gamme, $ordre]) {
            Produit::query()->firstOrCreate(['code' => $code], [
                'gamme_id' => $gamme, 'designation' => $designation,
                'prix_catalogue' => $prix, 'type' => $type, 'ordre' => $ordre, 'actif' => true,
            ]);
        }
    }
}
