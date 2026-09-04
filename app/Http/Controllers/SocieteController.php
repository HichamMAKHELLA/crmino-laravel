<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Societe;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SocieteController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Societe::class);

        $societes = Societe::query()
            ->dansPerimetre($request->user(), 'societe.consulter')
            ->with(['ville', 'proprietaire'])
            ->latest()
            ->paginate(20)
            ->through(fn (Societe $s) => [
                'id' => $s->id,
                'numero' => $s->numero,
                'raison_sociale' => $s->raison_sociale,
                'etat' => $s->etat,
                'ville' => $s->ville?->libelle,
                'proprietaire' => $s->proprietaire
                    ? trim(($s->proprietaire->prenom ?? '').' '.($s->proprietaire->nom ?? ''))
                    : null,
            ]);

        return Inertia::render('Societes/Index', ['societes' => $societes]);
    }
}
