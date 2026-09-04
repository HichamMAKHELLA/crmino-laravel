<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Lead::class);

        $leads = Lead::query()
            ->dansPerimetre($request->user(), 'lead.consulter')
            ->with(['statut', 'source', 'ville'])
            ->latest()
            ->paginate(20)
            ->through(fn (Lead $l) => [
                'id' => $l->id,
                'numero' => $l->numero,
                'raison_sociale' => $l->raison_sociale,
                'statut' => $l->statut?->libelle,
                'source' => $l->source?->libelle,
                'ville' => $l->ville?->libelle,
            ]);

        return Inertia::render('Leads/Index', ['leads' => $leads]);
    }
}
