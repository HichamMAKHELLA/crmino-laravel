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

        $onglet = in_array($request->query('onglet'), ['previsionnel', 'motifs'], true)
            ? $request->query('onglet') : 'previsionnel';

        return Inertia::render('Rapports/Index', [
            'onglet' => $onglet,
            'du' => $request->query('du'),
            'au' => $request->query('au'),
            'previsionnel' => $onglet === 'previsionnel' ? $this->previsionnel($request) : null,
            'motifs' => $onglet === 'motifs' ? $this->motifs($request) : null,
        ]);
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
