<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Lead;

/**
 * Recherche de doublons (§41). Croise ICE exact, téléphone et courriel (via les
 * contacts), domaine Internet exact et raison sociale normalisée EN PRÉFIXE.
 * Une fiche remontée par plusieurs critères garde le plus fort : « ICE
 * identique » vaut mieux que « raison sociale proche ».
 *
 * Ne porte encore que les LEADS ; les sociétés viendront avec leur table (§6).
 */
class DetecteurDoublons
{
    private const POIDS_ICE = 100;
    private const POIDS_TELEPHONE = 80;
    private const POIDS_EMAIL = 80;
    private const POIDS_DOMAINE = 60;
    private const POIDS_RAISON_SOCIALE = 50;

    /**
     * @return list<Candidat>
     */
    public function rechercher(
        ?string $raisonSociale,
        ?string $ice,
        ?string $telephone,
        ?string $email,
        ?string $siteWeb,
        ?int $exclureLeadId = null,
    ): array {
        $normalisee = Doublons::normaliserRaisonSociale($raisonSociale);
        $tel = Doublons::normaliserTelephone($telephone);
        // Le domaine se déduit du site OU, à défaut, du courriel professionnel.
        $domaine = Doublons::extraireDomaine($siteWeb) ?? Doublons::extraireDomaine($email);
        $courriel = ($email === null || trim($email) === '') ? null : mb_strtolower(trim($email));
        $iceNettoye = ($ice === null || trim($ice) === '') ? null : trim($ice);

        // Rien d'exploitable : ne pas interroger la base. Le contrôle est appelé
        // à chaque frappe du formulaire.
        if ($normalisee === null && $iceNettoye === null && $tel === null
            && $courriel === null && $domaine === null) {
            return [];
        }

        /** @var array<int, Candidat> $parId */
        $parId = [];
        $retenir = function (iterable $leads, string $critere, int $poids) use (&$parId, $exclureLeadId): void {
            foreach ($leads as $l) {
                if ($exclureLeadId !== null && $l->id === $exclureLeadId) {
                    continue;
                }
                $existant = $parId[$l->id] ?? null;
                if ($existant === null || $poids > $existant->poids) {
                    $parId[$l->id] = new Candidat('Lead', $l->id, $l->numero, $l->raison_sociale, $l->ice, $critere, $poids);
                }
            }
        };

        if ($iceNettoye !== null) {
            $retenir(Lead::query()->where('actif', true)->where('ice', $iceNettoye)->get(),
                'ICE identique', self::POIDS_ICE);
        }

        if ($tel !== null) {
            $retenir(
                Lead::query()->where('actif', true)->whereHas('contacts', fn ($c) => $c
                    ->where('actif', true)
                    ->where(fn ($w) => $w->where('telephone_normalise', $tel)->orWhere('gsm_normalise', $tel)))
                    ->get(),
                'Téléphone identique', self::POIDS_TELEPHONE);
        }

        if ($courriel !== null) {
            $retenir(
                Lead::query()->where('actif', true)->whereHas('contacts', fn ($c) => $c
                    ->where('actif', true)->whereRaw('LOWER(email) = ?', [$courriel]))
                    ->get(),
                'Courriel identique', self::POIDS_EMAIL);
        }

        if ($domaine !== null) {
            $retenir(Lead::query()->where('actif', true)->where('domaine_web', $domaine)->get(),
                'Même domaine Internet', self::POIDS_DOMAINE);
        }

        if ($normalisee !== null) {
            // Préfixe : l'existant COMMENCE par le candidat normalisé.
            $retenir(
                Lead::query()->where('actif', true)
                    ->where('raison_sociale_normalisee', 'like', self::echapper($normalisee).'%')->get(),
                'Raison sociale proche', self::POIDS_RAISON_SOCIALE);
        }

        $candidats = array_values($parId);
        usort($candidats, fn (Candidat $a, Candidat $b) => $b->poids <=> $a->poids
            ?: strcmp((string) $a->raisonSociale, (string) $b->raisonSociale));

        return $candidats;
    }

    /** Neutralise les métacaractères LIKE d'un préfixe (% et _). */
    private static function echapper(string $valeur): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $valeur);
    }
}
