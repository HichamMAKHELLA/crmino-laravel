<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Campagne;
use App\Models\Referentiels\TypeCampagne;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CampagneController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->peut('campagne.consulter'), 403);

        $campagnes = Campagne::query()->where('actif', true)
            ->with(['type:id,libelle', 'responsable:id,prenom,nom'])
            ->withCount('leads')
            ->latest()
            ->paginate(20)
            ->through(fn (Campagne $c) => [
                'id' => $c->id,
                'numero' => $c->numero,
                'nom' => $c->nom,
                'type' => $c->type?->libelle,
                'statut' => $c->statut,
                'budget' => $c->budget !== null ? (float) $c->budget : null,
                'nb_leads' => $c->leads_count,
                'responsable' => $c->responsable
                    ? trim(($c->responsable->prenom ?? '').' '.($c->responsable->nom ?? '')) : null,
            ]);

        return Inertia::render('Campagnes/Index', ['campagnes' => $campagnes]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()->peut('campagne.creer'), 403);

        return Inertia::render('Campagnes/Create', [
            'types' => TypeCampagne::query()->where('actif', true)->orderBy('ordre')->get(['id', 'libelle'])
                ->map(fn ($t) => ['id' => $t->id, 'libelle' => $t->libelle]),
            'responsables' => User::query()->where('actif', true)->orderBy('nom')->get(['id', 'prenom', 'nom'])
                ->map(fn ($u) => ['id' => $u->id, 'nom' => trim(($u->prenom ?? '').' '.($u->nom ?? '')) ?: $u->name]),
        ]);
    }

    public function show(Request $request, Campagne $campagne): Response
    {
        abort_unless($request->user()->peut('campagne.consulter'), 403);

        $campagne->load(['type:id,libelle', 'responsable:id,prenom,nom']);

        $nbLeads = $campagne->leads()->count();
        $gagnees = $campagne->opportunites()->where('statut', 'Gagnee');
        $nbGagnees = (clone $gagnees)->count();
        $caGagne = (float) (clone $gagnees)->sum('montant_ht');
        $budget = $campagne->budget !== null ? (float) $campagne->budget : null;

        // §12 / RG-IND-002 : sans budget, le retour n'est pas nul — il n'existe
        // pas. Une campagne sans budget n'a pas un ROI de zéro.
        $retour = ($budget !== null && $budget > 0) ? round($caGagne / $budget, 2) : null;

        return Inertia::render('Campagnes/Show', [
            'campagne' => [
                'id' => $campagne->id,
                'numero' => $campagne->numero,
                'nom' => $campagne->nom,
                'type' => $campagne->type?->libelle,
                'statut' => $campagne->statut,
                'date_debut' => $campagne->date_debut?->format('Y-m-d'),
                'date_fin' => $campagne->date_fin?->format('Y-m-d'),
                'budget' => $budget,
                'cible' => $campagne->cible,
                'responsable' => $campagne->responsable
                    ? trim(($campagne->responsable->prenom ?? '').' '.($campagne->responsable->nom ?? '')) : null,
            ],
            'roi' => [
                'nb_leads' => $nbLeads,
                'nb_gagnees' => $nbGagnees,
                'ca_gagne' => $caGagne,
                'retour' => $retour, // null si pas de budget (RG-IND-002)
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->peut('campagne.creer'), 403);

        $data = $request->validate([
            'nom' => ['required', 'string', 'max:200'],
            'type_campagne_id' => ['required', 'integer', 'exists:types_campagne,id'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'responsable_id' => ['required', 'integer', 'exists:users,id'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'cible' => ['nullable', 'string', 'max:500'],
        ]);

        $campagne = Campagne::create([
            ...$data,
            'statut' => 'Planifiee',
            'cree_par' => $request->user()->id,
        ]);

        return to_route('campagnes.show', $campagne->id)->with('success', "Campagne {$campagne->numero} créée.");
    }
}
