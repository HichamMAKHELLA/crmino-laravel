<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Numérotation par séquence (§10, §25). Port de Domain/Numerotation :
 * "{PREFIXE}-{ANNEE}-{SEQUENCE:5}". Le verrou de ligne sérialise les
 * lecteurs concurrents ; la séquence est globale (jamais réinitialisée),
 * l'année n'est que décorative.
 */
final class Numerotation
{
    public const PREFIXE_LEAD = 'LEAD';
    public const PREFIXE_SOCIETE = 'SOC';
    public const PREFIXE_OPPORTUNITE = 'OPP';
    public const PREFIXE_CAMPAGNE = 'CAM';

    public static function suivant(string $prefixe): string
    {
        $valeur = DB::transaction(function () use ($prefixe) {
            $ligne = DB::table('sequences')->where('code', $prefixe)->lockForUpdate()->first();

            if ($ligne === null) {
                DB::table('sequences')->insert(['code' => $prefixe, 'valeur' => 1]);

                return 1;
            }

            $suivant = $ligne->valeur + 1;
            DB::table('sequences')->where('code', $prefixe)->update(['valeur' => $suivant]);

            return $suivant;
        });

        return self::composer($prefixe, (int) date('Y'), $valeur);
    }

    public static function composer(string $prefixe, int $annee, int $sequence): string
    {
        if (trim($prefixe) === '') {
            throw new \InvalidArgumentException('Le préfixe est obligatoire.');
        }
        if ($sequence < 1) {
            throw new \InvalidArgumentException('La séquence commence à 1.');
        }

        return sprintf('%s-%04d-%05d', $prefixe, $annee, $sequence);
    }
}
