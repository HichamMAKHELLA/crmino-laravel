<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Géographie (Pays -> Région -> Ville) et fonctions de contact. Hiérarchie
 * résolue par code. Idempotent (upsert sur le code).
 */
class ReferentielsGeoSeeder extends Seeder
{
    public function run(): void
    {
        // pays : [code, iso, libellé, ordre, systeme]
        $this->upsert('pays', ['code', 'code_iso', 'libelle', 'ordre', 'systeme'], [
            ['MA', 'MA', 'Maroc', 10, true],
            ['FR', 'FR', 'France', 20, false],
            ['ES', 'ES', 'Espagne', 30, false],
            ['DZ', 'DZ', 'Algérie', 40, false],
            ['TN', 'TN', 'Tunisie', 50, false],
            ['SN', 'SN', 'Sénégal', 60, false],
            ['CI', 'CI', "Côte d'Ivoire", 70, false],
        ]);
        $pays = DB::table('pays')->pluck('id', 'code');

        // regions : [pays_code, code, libellé, ordre]
        $regions = [
            ['MA', 'MA_CASA', 'Casablanca-Settat', 10],
            ['MA', 'MA_RABAT', 'Rabat-Salé-Kénitra', 20],
            ['MA', 'MA_TANGER', 'Tanger-Tétouan-Al Hoceïma', 30],
            ['MA', 'MA_FES', 'Fès-Meknès', 40],
            ['MA', 'MA_MARRAK', 'Marrakech-Safi', 50],
            ['MA', 'MA_SOUSS', 'Souss-Massa', 60],
            ['MA', 'MA_ORIENT', 'Oriental', 70],
            ['MA', 'MA_BENI', 'Béni Mellal-Khénifra', 80],
            ['MA', 'MA_DRAA', 'Drâa-Tafilalet', 90],
            ['MA', 'MA_GUELMIM', 'Guelmim-Oued Noun', 100],
            ['MA', 'MA_LAAYOUN', 'Laâyoune-Sakia El Hamra', 110],
            ['MA', 'MA_DAKHLA', 'Dakhla-Oued Ed-Dahab', 120],
        ];
        $this->upsert('regions', ['pays_id', 'code', 'libelle', 'ordre'],
            array_map(fn ($r) => [$pays[$r[0]], $r[1], $r[2], $r[3]], $regions));
        $reg = DB::table('regions')->pluck('id', 'code');

        // villes : [region_code, code, libellé, ordre]
        $villes = [
            ['MA_CASA', 'CASABLANCA', 'Casablanca', 10],
            ['MA_CASA', 'MOHAMMEDIA', 'Mohammedia', 20],
            ['MA_CASA', 'SETTAT', 'Settat', 30],
            ['MA_CASA', 'BERRECHID', 'Berrechid', 40],
            ['MA_CASA', 'ELJADIDA', 'El Jadida', 50],
            ['MA_RABAT', 'RABAT', 'Rabat', 60],
            ['MA_RABAT', 'SALE', 'Salé', 70],
            ['MA_RABAT', 'TEMARA', 'Témara', 80],
            ['MA_RABAT', 'KENITRA', 'Kénitra', 90],
            ['MA_TANGER', 'TANGER', 'Tanger', 100],
            ['MA_TANGER', 'TETOUAN', 'Tétouan', 110],
            ['MA_FES', 'FES', 'Fès', 120],
            ['MA_FES', 'MEKNES', 'Meknès', 130],
            ['MA_MARRAK', 'MARRAKECH', 'Marrakech', 140],
            ['MA_MARRAK', 'SAFI', 'Safi', 150],
            ['MA_SOUSS', 'AGADIR', 'Agadir', 160],
            ['MA_ORIENT', 'OUJDA', 'Oujda', 170],
            ['MA_ORIENT', 'NADOR', 'Nador', 180],
            ['MA_BENI', 'BENIMELLAL', 'Béni Mellal', 190],
            ['MA_LAAYOUN', 'LAAYOUNE', 'Laâyoune', 200],
            ['MA_DAKHLA', 'DAKHLA', 'Dakhla', 210],
        ];
        $this->upsert('villes', ['region_id', 'code', 'libelle', 'ordre'],
            array_map(fn ($v) => [$reg[$v[0]], $v[1], $v[2], $v[3]], $villes));

        // fonctions_contact : [code, libellé, ordre]
        $this->upsert('fonctions_contact', ['code', 'libelle', 'ordre'], [
            ['DG', 'Directeur général', 10],
            ['DAF', 'Directeur administratif et financier', 20],
            ['DSI', "Directeur des systèmes d'information", 30],
            ['DRH', 'Directeur des ressources humaines', 40],
            ['CHEF_COMPTA', 'Chef comptable', 50],
            ['COMPTABLE', 'Comptable', 60],
            ['RESP_ACHATS', 'Responsable achats', 70],
            ['RESP_COMMERCIAL', 'Responsable commercial', 80],
            ['RESP_LOGISTIQUE', 'Responsable logistique', 90],
            ['RESP_IT', 'Responsable informatique', 100],
            ['GERANT', 'Gérant', 110],
            ['ASSISTANT', 'Assistant(e)', 120],
            ['AUTRE', 'Autre', 999],
        ]);
    }

    private function upsert(string $table, array $colonnes, array $lignes): void
    {
        $rows = array_map(fn (array $l) => array_combine($colonnes, $l), $lignes);
        DB::table($table)->upsert($rows, ['code'], array_values(array_diff($colonnes, ['code'])));
    }
}
