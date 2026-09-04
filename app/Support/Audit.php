<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\AuditJournal;

/**
 * Traçage d'audit (§46). Ajout seul. Une ligne signifie « ceci a CHANGÉ »,
 * jamais « ceci a été soumis » — les couples identiques sont écartés, et le
 * vide est ramené à l'absence (une case blanche du formulaire ne trace rien).
 */
final class Audit
{
    public const CREATION = 'Creation';
    public const MODIFICATION = 'Modification';
    public const CHANGEMENT_STATUT = 'ChangementStatut';
    public const CHANGEMENT_MONTANT = 'ChangementMontant';
    public const CHANGEMENT_PROPRIETAIRE = 'ChangementProprietaire';
    public const CHANGEMENT_DROITS = 'ChangementDroits';
    public const SUPPRESSION_LOGIQUE = 'SuppressionLogique';
    public const EXPORT = 'Export';

    public static function tracer(
        ?int $utilisateurId,
        string $action,
        string $entiteType,
        int $entiteId,
        ?string $champ = null,
        ?string $ancienne = null,
        ?string $nouvelle = null,
    ): void {
        AuditJournal::create([
            'le' => now(),
            'utilisateur_id' => $utilisateurId,
            'action' => $action,
            'entite_type' => $entiteType,
            'entite_id' => $entiteId,
            'champ' => $champ,
            'ancienne_valeur' => $ancienne,
            'nouvelle_valeur' => $nouvelle,
            'adresse_ip' => request()->ip(),
        ]);
    }

    /**
     * Trace CHAMP PAR CHAMP, en n'écrivant que ce qui a réellement changé.
     *
     * @param  array<string, array{0: mixed, 1: mixed}>  $changements  [champ => [ancienne, nouvelle]]
     */
    public static function tracerChamps(
        ?int $utilisateurId,
        string $entiteType,
        int $entiteId,
        array $changements,
        string $action = self::MODIFICATION,
    ): void {
        foreach ($changements as $champ => [$ancienne, $nouvelle]) {
            $a = self::texte($ancienne);
            $n = self::texte($nouvelle);
            // Écarte les couples identiques : une ligne = un changement réel.
            if ($a === $n) {
                continue;
            }
            self::tracer($utilisateurId, $action, $entiteType, $entiteId, $champ, $a, $n);
        }
    }

    /** Ramène le vide à l'absence : « » et null sont indistincts pour l'audit. */
    private static function texte(mixed $valeur): ?string
    {
        if ($valeur === null) {
            return null;
        }
        $s = trim((string) $valeur);

        return $s === '' ? null : $s;
    }
}
