<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Lead;
use App\Models\Referentiels\CritereScore;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Suggestion de score §15. Le score est SUGGÉRÉ, jamais écrit d'office — ce
 * service CALCULE une proposition et son détail ; le score enregistré reste
 * celui qu'un commercial valide.
 *
 * Trois états par critère, pas deux : ACQUIS / pas encore acquis / HORS
 * D'ATTEINTE. Un critère dont le vocabulaire a disparu est « hors d'atteinte »
 * (RG-LEA-004) — ses points sortent du plafond au lieu de compter pour zéro.
 * D'où un plafond ATTEIGNABLE calculé, jamais « /100 » en dur (RG-IND-001).
 */
final class ScoreSuggestion
{
    /** Nombre de jours au-delà duquel une interaction n'est plus « récente ». */
    private const JOURS_INTERACTION = 30;

    /** @return array<string, string|null> code du critère → nom du référentiel source (null si pur colonne). */
    private const SOURCE_PAR_CRITERE = [
        'BESOIN' => 'besoins',
        'BUDGET' => 'tranches_budget',
        'DELAI' => 'horizons_decision',
        'RDV' => 'types_activite',
        'DEVIS' => 'types_document',
        'DECISIONNAIRE' => null,
        'PROJET' => null,
        'INTERACTION' => null,
    ];

    /**
     * @return array{suggestion:int, total:int, total_atteignable:int, criteres:list<array{code:string, libelle:string, poids:int, acquis:bool, evaluable:bool}>}
     */
    public function pour(Lead $lead, ?Carbon $maintenant = null): array
    {
        $maintenant ??= Carbon::now();
        $faits = $this->faits($lead, $maintenant);
        $disponible = $this->vocabulaireDisponible();

        $criteres = CritereScore::query()->where('actif', true)->orderBy('ordre')->get();

        $lignes = [];
        $suggestion = 0;
        $total = 0;
        $atteignable = 0;

        foreach ($criteres as $c) {
            $evaluable = in_array($c->code, $disponible, true);
            $acquis = $evaluable && ($faits[$c->code] ?? false);
            $total += $c->poids;
            if ($evaluable) {
                $atteignable += $c->poids;
            }
            if ($acquis) {
                $suggestion += $c->poids;
            }
            $lignes[] = [
                'code' => $c->code, 'libelle' => $c->libelle, 'poids' => $c->poids,
                'acquis' => $acquis, 'evaluable' => $evaluable,
            ];
        }

        return [
            'suggestion' => $suggestion,
            'total' => $total,
            'total_atteignable' => $atteignable,
            'criteres' => $lignes,
        ];
    }

    /** @return array<string, bool> les huit faits, par code. */
    private function faits(Lead $lead, Carbon $maintenant): array
    {
        $limite = $maintenant->copy()->subDays(self::JOURS_INTERACTION);
        $q = $lead->qualification;

        return [
            // Trois critères d'intention : le NUL vaut « on ne sait pas ». Seule
            // une valeur renseignée atteste que la question a été posée ET répondue.
            'BUDGET' => $q?->tranche_budget_id !== null,
            'DELAI' => $q?->horizon_decision_id !== null,
            'PROJET' => (bool) ($q?->projet_defini),
            'BESOIN' => $lead->besoins()->exists(),
            'DECISIONNAIRE' => $lead->contacts()->where('actif', true)->where('decisionnaire', true)->exists(),
            // RDV EFFECTUÉ : un RDV à venir ne prouve rien encore — la date passée
            // distingue une intention d'un fait.
            'RDV' => $lead->activites()->where('actif', true)
                ->where('debut_le', '<=', $maintenant)
                ->whereHas('type', fn ($t) => $t->where('categorie', 'Rdv'))->exists(),
            // Le §15 dit « demande de devis » ; le schéma porte le document qui en
            // résulte (DEVIS/PROPOSITION). Interprétation assumée, pas dissimulée.
            'DEVIS' => DB::table('documents as d')
                ->join('types_document as td', 'td.id', '=', 'd.type_document_id')
                ->where('d.cible_type', 'Lead')->where('d.cible_id', $lead->id)->where('d.actif', true)
                ->whereIn('td.code', ['DEVIS', 'PROPOSITION'])->exists(),
            'INTERACTION' => $lead->derniere_activite_le !== null && $lead->derniere_activite_le >= $limite,
        ];
    }

    /**
     * Les critères dont le VOCABULAIRE existe encore. Ceux qui ne dépendent
     * d'aucune liste (décisionnaire, projet, interaction) sont toujours
     * évaluables ; les autres exigent une valeur active dans leur référentiel.
     *
     * @return list<string>
     */
    private function vocabulaireDisponible(): array
    {
        // Les critères sans source sont dérivés de la carte, jamais réécrits.
        $disponibles = array_keys(array_filter(self::SOURCE_PAR_CRITERE, fn ($s) => $s === null));

        $actif = fn (string $table) => DB::table($table)->where('actif', true)->exists();

        if (DB::table('besoins')->where('actif', true)->exists()) {
            $disponibles[] = 'BESOIN';
        }
        if ($actif('tranches_budget')) {
            $disponibles[] = 'BUDGET';
        }
        if ($actif('horizons_decision')) {
            $disponibles[] = 'DELAI';
        }
        if (DB::table('types_activite')->where('actif', true)->where('categorie', 'Rdv')->exists()) {
            $disponibles[] = 'RDV';
        }
        if (DB::table('types_document')->where('actif', true)->whereIn('code', ['DEVIS', 'PROPOSITION'])->exists()) {
            $disponibles[] = 'DEVIS';
        }

        return $disponibles;
    }
}
