<?php

declare(strict_types=1);

namespace App\Support;

/**
 * RG-LEA-001 — données minimales à la création d'un lead (§71). Port de
 * Domain/Leads.ControlerDonneesMinimales.
 *
 * La règle est COMPOSÉE : une identité (raison sociale OU nom de contact) ET un
 * moyen de joindre (téléphone OU GSM OU courriel). On note un prospect au
 * téléphone sans connaître sa raison sociale exacte, mais une fiche qui ne
 * désigne personne ne se rapproche de rien (§41).
 */
final class ReglesLead
{
    public const REGLE = 'RG-LEA-001';

    /**
     * @return array{code: string, message: string}|null  null si conforme
     */
    public static function controlerDonneesMinimales(
        ?string $raisonSociale,
        ?string $nomContact,
        ?string $telephone,
        ?string $gsm,
        ?string $email,
    ): ?array {
        $identifiant = ! self::vide($raisonSociale) || ! self::vide($nomContact);
        $joignable = ! self::vide($telephone) || ! self::vide($gsm) || ! self::vide($email);

        if (! $identifiant && ! $joignable) {
            return ['code' => 'LEAD_INCOMPLET',
                'message' => 'Un lead exige une raison sociale ou un contact, et un téléphone ou un courriel.'];
        }
        if (! $identifiant) {
            return ['code' => 'LEAD_SANS_IDENTIFIANT',
                'message' => "Renseignez une raison sociale ou le nom d'un contact."];
        }
        if (! $joignable) {
            return ['code' => 'LEAD_SANS_CONTACT',
                'message' => 'Renseignez un téléphone, un GSM ou un courriel.'];
        }

        return null;
    }

    private static function vide(?string $v): bool
    {
        return $v === null || trim($v) === '';
    }
}
