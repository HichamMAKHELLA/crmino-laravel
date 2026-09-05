<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Lead;
use App\Models\Opportunite;
use App\Models\Referentiels\ParametreAlerte;
use App\Models\Tache;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Alertes commerciales (§35). Commandées par le PARAMÉTRAGE
 * (parametres_alerte), jamais par le code : délais, seuils et liste viennent de
 * la table. Une ligne DÉSACTIVÉE éteint son alerte — son compte vaut null, pas
 * zéro (RG-IND-001 appliqué à un décompte : « désactivée » n'est pas « aucun »).
 *
 * PRODUCTEURS est le SEUL endroit qui déclare ce que ce service sait calculer ;
 * `surveille` et `sansProducteur` s'en dérivent. Un paramètre actif mais
 * INCALCULABLE est NOMMÉ, jamais tu — le taire ferait croire qu'il veille.
 */
final class Alertes
{
    /** Les codes que ce service sait calculer sur le schéma existant. */
    public const PRODUCTEURS = [
        'LEAD_SANS_ACTIVITE', 'OPP_SANS_ACTIVITE', 'DEVIS_SANS_RELANCE',
        'OPP_DATE_DEPASSEE', 'TACHE_EN_RETARD', 'OPP_SANS_ACTION',
    ];

    /** Les alertes dont la requête LIT un seuil de montant. */
    public const LECTEURS_SEUIL = ['OPP_SANS_ACTION'];

    /**
     * Le tableau de bord des alertes de l'appelant.
     *
     * @return array{lignes:list<array{code:string, libelle:string, delai_jours:int, seuil_montant:?float, actif:bool, surveille:bool, seuil_lu:bool, compte:?int, calculable:bool}>, sans_producteur:list<string>}
     */
    public function pour(User $user, ?Carbon $maintenant = null): array
    {
        $maintenant ??= Carbon::now();
        $parametres = ParametreAlerte::query()->orderBy('ordre')->get();

        $lignes = [];
        $sansProducteur = [];

        foreach ($parametres as $p) {
            $surveille = in_array($p->code, self::PRODUCTEURS, true);
            if (! $surveille) {
                // Actif mais incalculable : nommé, jamais tu.
                if ($p->actif) {
                    $sansProducteur[] = $p->code;
                }
            }

            // Désactivée → compte NUL (pas zéro) ; incalculable → nul aussi.
            $compte = ($surveille && $p->actif) ? $this->compter($p, $user, $maintenant) : null;

            $lignes[] = [
                'id' => $p->id,
                'code' => $p->code,
                'libelle' => $p->libelle,
                'delai_jours' => $p->delai_jours,
                'seuil_montant' => $p->seuil_montant !== null ? (float) $p->seuil_montant : null,
                'actif' => $p->actif,
                'surveille' => $surveille,
                'seuil_lu' => in_array($p->code, self::LECTEURS_SEUIL, true),
                'compte' => $compte,
                'calculable' => $surveille,
            ];
        }

        return ['lignes' => $lignes, 'sans_producteur' => $sansProducteur];
    }

    private function compter(ParametreAlerte $p, User $user, Carbon $maintenant): int
    {
        // Le point de bascule se calcule ICI, une seule fois, depuis le paramètre.
        $limite = $maintenant->copy()->subDays($p->delai_jours);

        return match ($p->code) {
            // Un lead JAMAIS touché est dormant depuis sa CRÉATION (COALESCE) :
            // sans cela, le plus inquiétant serait le seul à ne pas alerter.
            'LEAD_SANS_ACTIVITE' => Lead::query()
                ->dansPerimetre($user, 'lead.consulter')
                ->where('actif', true)->whereNull('societe_id')
                ->whereRaw('COALESCE(derniere_activite_le, created_at) < ?', [$limite])
                ->count(),

            'OPP_SANS_ACTIVITE' => Opportunite::query()
                ->dansPerimetre($user, 'opportunite.consulter')
                ->where('actif', true)->where('statut', 'Ouverte')
                ->whereRaw('COALESCE(derniere_activite_le, created_at) < ?', [$limite])
                ->count(),

            // « Devis » se lit sur l'ÉTAPE (PROPOSITION), pas sur un document
            // déposé : le §44 rend le dépôt facultatif.
            'DEVIS_SANS_RELANCE' => Opportunite::query()
                ->dansPerimetre($user, 'opportunite.consulter')
                ->where('actif', true)->where('statut', 'Ouverte')
                ->whereHas('etape', fn ($e) => $e->where('code', 'PROPOSITION'))
                ->whereRaw('COALESCE(derniere_activite_le, created_at) < ?', [$limite])
                ->count(),

            'OPP_DATE_DEPASSEE' => Opportunite::query()
                ->dansPerimetre($user, 'opportunite.consulter')
                ->where('actif', true)->where('statut', 'Ouverte')
                ->whereNotNull('date_cloture_estimee')->where('date_cloture_estimee', '<', $limite)
                ->count(),

            // Le périmètre des tâches se prend sur l'ASSIGNÉ (pas d'équipe).
            'TACHE_EN_RETARD' => Tache::query()
                ->where('actif', true)->whereIn('statut', ['AFaire', 'EnCours'])
                ->whereNotNull('echeance_le')->where('echeance_le', '<', $limite)
                ->where('assignee_id', $user->id)
                ->count(),

            // Opportunité IMPORTANTE (montant >= seuil) sans prochaine action.
            'OPP_SANS_ACTION' => Opportunite::query()
                ->dansPerimetre($user, 'opportunite.consulter')
                ->where('actif', true)->where('statut', 'Ouverte')
                ->whereNull('prochaine_action_le')
                ->when($p->seuil_montant !== null, fn ($q) => $q->where('montant_ht', '>=', $p->seuil_montant))
                ->count(),

            default => 0,
        };
    }
}
