<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Opportunite;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Rapports (§38, §75). L'onglet et la période vivent dans l'ADRESSE (§77) :
 * « regarde les motifs de mars » se partage en collant un lien. Les données
 * sont bornées au périmètre de l'appelant.
 */
class RapportController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->peut('rapport.consulter'), 403);

        $onglet = in_array($request->query('onglet'), ['previsionnel', 'motifs', 'entonnoir', 'ventilation'], true)
            ? $request->query('onglet') : 'previsionnel';

        return Inertia::render('Rapports/Index', [
            'onglet' => $onglet,
            'du' => $request->query('du'),
            'au' => $request->query('au'),
            'previsionnel' => $onglet === 'previsionnel' ? $this->previsionnel($request) : null,
            'motifs' => $onglet === 'motifs' ? $this->motifs($request) : null,
            'entonnoir' => $onglet === 'entonnoir' ? $this->entonnoir($request) : null,
            'ventilation' => $onglet === 'ventilation' ? $this->ventilation($request) : null,
        ]);
    }

    /**
     * §76 : la ventilation du chiffre par PRODUIT. Elle somme les LIGNES
     * ventilables (produit_id non nul), jamais le montant d'en-tête — celui-ci
     * est saisi indépendamment (§25). La couverture compare la somme des lignes
     * au TOTAL d'en-tête : sans ce total, absent du tableau, l'écart entre §75 et
     * §76 passerait pour une erreur. RG-IND-001 : un taux sans base est NULL,
     * jamais zéro — un rapport à 0 % de couverture se lirait « rien n'est ventilé »
     * là où il n'y a simplement rien à ventiler.
     */
    private function ventilation(Request $request): array
    {
        $enPerimetre = fn () => Opportunite::query()
            ->dansPerimetre($request->user(), 'opportunite.consulter')
            ->where('actif', true)
            ->whereIn('statut', ['Ouverte', 'Gagnee']);

        // Le détail par produit : on ne somme que les lignes rattachées à un produit.
        $lignes = \App\Models\OpportuniteLigne::query()
            ->whereNotNull('produit_id')
            ->whereHas('opportunite', fn ($q) => $enPerimetre())
            ->with(['opportunite:id,statut', 'produit:id,designation,gamme_id', 'produit.gamme:id,libelle,famille_id', 'produit.gamme.famille:id,libelle'])
            ->get(['id', 'opportunite_id', 'produit_id', 'montant_ht']);

        $parProduit = $lignes->groupBy('produit_id')->map(function ($grp) {
            $p = $grp->first()->produit;
            $ouvertes = $grp->filter(fn ($l) => $l->opportunite?->statut === 'Ouverte');
            $gagnees = $grp->filter(fn ($l) => $l->opportunite?->statut === 'Gagnee');

            return [
                'produit' => $p?->designation ?? '—',
                'gamme' => $p?->gamme?->libelle,
                'famille' => $p?->gamme?->famille?->libelle,
                'opportunites_ouvertes' => $ouvertes->pluck('opportunite_id')->unique()->count(),
                'pipeline' => (float) $ouvertes->sum('montant_ht'),
                'gagnees' => $gagnees->pluck('opportunite_id')->unique()->count(),
                'ca_gagne' => (float) $gagnees->sum('montant_ht'),
            ];
        })->sortByDesc('ca_gagne')->values()->all();

        // La couverture, en comparant les en-têtes aux lignes ventilables.
        $affaires = $enPerimetre()->withSum(
            ['lignes as ventile' => fn ($q) => $q->whereNotNull('produit_id')],
            'montant_ht'
        )->get(['id', 'statut', 'montant_ht']);

        $ouvertes = $affaires->where('statut', 'Ouverte');
        $gagnees = $affaires->where('statut', 'Gagnee');

        $pipelineTotal = (float) $ouvertes->sum('montant_ht');
        $pipelineVentile = (float) $ouvertes->sum(fn ($o) => (float) ($o->ventile ?? 0));
        $caGagneTotal = (float) $gagnees->sum('montant_ht');
        $caGagneVentile = (float) $gagnees->sum(fn ($o) => (float) ($o->ventile ?? 0));

        return [
            'lignes' => $parProduit,
            'pipeline_total' => $pipelineTotal,
            'pipeline_ventile' => $pipelineVentile,
            // RG-IND-001 : NULL, jamais zéro, quand il n'y a pas de base.
            'taux_pipeline' => $pipelineTotal > 0 ? round($pipelineVentile / $pipelineTotal * 100, 1) : null,
            'ca_gagne_total' => $caGagneTotal,
            'ca_gagne_ventile' => $caGagneVentile,
            'taux_ca_gagne' => $caGagneTotal > 0 ? round($caGagneVentile / $caGagneTotal * 100, 1) : null,
            'affaires_ouvertes_sans_ligne' => $ouvertes->filter(fn ($o) => (float) ($o->ventile ?? 0) === 0.0)->count(),
        ];
    }

    /**
     * §39 : l'entonnoir de conversion. Compte des LEADS (jamais des réunions),
     * si bien que chaque palier est un sous-ensemble du précédent :
     * affectés >= contactés >= rendez-vous. « Contacté » = au moins une activité ;
     * « rendez-vous » se lit sur ref.TypeActivite.categorie = 'Rdv', jamais un code.
     */
    private function entonnoir(Request $request): array
    {
        $base = fn () => \App\Models\Lead::query()
            ->dansPerimetre($request->user(), 'lead.consulter')
            ->where('actif', true);

        $affectes = $base()->count();
        $contactes = $base()->whereHas('activites', fn ($a) => $a->where('actif', true))->count();
        $rdv = $base()->whereHas('activites', fn ($a) => $a->where('actif', true)
            ->whereHas('type', fn ($t) => $t->where('categorie', 'Rdv')))->count();
        $convertis = $base()->whereNotNull('societe_id')->count();

        return [
            ['palier' => 'Leads affectés', 'nb' => $affectes],
            ['palier' => 'Contactés', 'nb' => $contactes],
            ['palier' => 'Rendez-vous', 'nb' => $rdv],
            ['palier' => 'Convertis', 'nb' => $convertis],
        ];
    }

    /** §75 : le prévisionnel par étape, avec la probabilité EFFECTIVE (pondéré/montant). */
    private function previsionnel(Request $request): array
    {
        $ouvertes = Opportunite::query()
            ->dansPerimetre($request->user(), 'opportunite.consulter')
            ->where('statut', 'Ouverte')
            ->with('etape:id,libelle,ordre')
            ->get(['id', 'etape_id', 'montant_ht', 'montant_pondere']);

        return $ouvertes->groupBy(fn ($o) => $o->etape?->libelle ?? '—')
            ->map(function ($lignes, $etape) {
                $montant = (float) $lignes->sum('montant_ht');
                $pondere = (float) $lignes->sum('montant_pondere');

                return [
                    'etape' => $etape,
                    'ordre' => $lignes->first()->etape?->ordre ?? 0,
                    'nb' => $lignes->count(),
                    'montant' => $montant,
                    'pondere' => $pondere,
                    // §75 : probabilité effective, pas celle de l'étape.
                    'probabilite_effective' => $montant > 0 ? round($pondere / $montant * 100, 1) : null,
                ];
            })
            ->sortBy('ordre')->values()->all();
    }

    /** §38 : les motifs de perte sur la période (datés sur la clôture). */
    private function motifs(Request $request): array
    {
        $perdues = Opportunite::query()
            ->dansPerimetre($request->user(), 'opportunite.consulter')
            ->where('statut', 'Perdue')
            ->when($request->query('du'), fn ($q, $du) => $q->whereDate('date_cloture', '>=', $du))
            ->when($request->query('au'), fn ($q, $au) => $q->whereDate('date_cloture', '<', $au))
            ->with('motifPerte:id,libelle')
            ->get(['id', 'motif_perte_id', 'montant_ht']);

        return $perdues->groupBy(fn ($o) => $o->motifPerte?->libelle ?? 'Sans motif')
            ->map(fn ($lignes, $motif) => [
                'motif' => $motif,
                'nb' => $lignes->count(),
                'montant' => (float) $lignes->sum('montant_ht'),
            ])
            ->sortByDesc('nb')->values()->all();
    }
}
