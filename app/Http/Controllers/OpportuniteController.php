<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreOpportuniteRequest;
use App\Models\Opportunite;
use App\Models\Referentiels\EtapePipeline;
use App\Models\Societe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OpportuniteController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Opportunite::class);

        // Le pipeline : les affaires OUVERTES, par étape. Les colonnes viennent
        // du référentiel (catégorie « Ouverte »), pas d'un libellé codé (§64).
        $etapes = EtapePipeline::query()->where('categorie', 'Ouverte')->where('actif', true)
            ->orderBy('ordre')->get(['id', 'libelle']);

        $affaires = Opportunite::query()
            ->dansPerimetre($request->user(), 'opportunite.consulter')
            ->where('statut', 'Ouverte')
            ->with(['societe:id,raison_sociale', 'etape:id'])
            ->orderByDesc('montant_ht')
            ->get(['id', 'numero', 'intitule', 'societe_id', 'etape_id', 'montant_ht', 'montant_pondere', 'probabilite']);

        $colonnes = $etapes->map(function ($e) use ($affaires) {
            $cartes = $affaires->where('etape_id', $e->id)->values();

            return [
                'id' => $e->id,
                'libelle' => $e->libelle,
                'couleur' => null,
                'total_pondere' => (float) $cartes->sum('montant_pondere'),
                'cartes' => $cartes->map(fn ($o) => [
                    'id' => $o->id,
                    'intitule' => $o->intitule,
                    'societe' => $o->societe?->raison_sociale,
                    'montant_ht' => (float) $o->montant_ht,
                    'montant_pondere' => (float) $o->montant_pondere,
                    'probabilite' => $o->probabilite,
                ])->values(),
            ];
        });

        return Inertia::render('Opportunites/Index', ['colonnes' => $colonnes]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Opportunite::class);

        return Inertia::render('Opportunites/Create', [
            'societes' => Societe::query()
                ->dansPerimetre($request->user(), 'societe.consulter')
                ->orderBy('raison_sociale')
                ->get(['id', 'raison_sociale'])
                ->map(fn ($s) => ['id' => $s->id, 'raison_sociale' => $s->raison_sociale]),
            'etapes' => EtapePipeline::query()->where('categorie', 'Ouverte')->where('actif', true)
                ->orderBy('ordre')->get(['id', 'libelle', 'probabilite'])
                ->map(fn ($e) => ['id' => $e->id, 'libelle' => $e->libelle, 'probabilite' => $e->probabilite]),
            'societePreselectionnee' => $request->integer('societe') ?: null,
        ]);
    }

    public function store(StoreOpportuniteRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $etape = EtapePipeline::query()->findOrFail($data['etape_id']);

        $opportunite = Opportunite::create([
            'intitule' => $data['intitule'],
            'societe_id' => $data['societe_id'],
            'etape_id' => $etape->id,
            'statut' => 'Ouverte',
            // RG-OPP-004 : à défaut de saisie, la probabilité de l'étape.
            'probabilite' => $data['probabilite'] ?? $etape->probabilite,
            'montant_ht' => $data['montant_ht'] ?? 0,
            'date_cloture_estimee' => $data['date_cloture_estimee'] ?? null,
            'commentaire' => $data['commentaire'] ?? null,
            'proprietaire_id' => $request->user()->id,
            'equipe_id' => $request->user()->equipe_id,
            'cree_par' => $request->user()->id,
        ]);

        return to_route('opportunites.index')
            ->with('success', "Affaire {$opportunite->numero} ouverte.");
    }
}
