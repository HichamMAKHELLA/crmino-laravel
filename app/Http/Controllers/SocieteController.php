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

    public function show(Request $request, Societe $societe): Response
    {
        abort_unless($request->user()->peut('societe.consulter'), 403);

        // §58 : une fiche hors périmètre rend 404, jamais 403.
        abort_unless(
            Societe::query()->whereKey($societe->id)
                ->dansPerimetre($request->user(), 'societe.consulter')->exists(),
            404,
        );

        $societe->load([
            'ville', 'source', 'proprietaire',
            'contacts' => fn ($q) => $q->where('actif', true)->orderByDesc('principal'),
            'contacts.fonction',
        ]);

        return Inertia::render('Societes/Show', [
            'societe' => [
                'id' => $societe->id,
                'numero' => $societe->numero,
                'raison_sociale' => $societe->raison_sociale,
                'etat' => $societe->etat,
                'ice' => $societe->ice,
                'site_web' => $societe->site_web,
                'ville' => $societe->ville?->libelle,
                'source' => $societe->source?->libelle,
                'devenu_client_le' => $societe->devenu_client_le?->format('Y-m-d'),
                'proprietaire' => $societe->proprietaire
                    ? trim(($societe->proprietaire->prenom ?? '').' '.($societe->proprietaire->nom ?? ''))
                    : null,
            ],
            'contacts' => $societe->contacts->map(fn ($c) => [
                'id' => $c->id,
                'nom' => trim(($c->prenom ?? '').' '.$c->nom),
                'fonction' => $c->fonction?->libelle ?? $c->fonction_libre,
                'gsm' => $c->gsm,
                'telephone' => $c->telephone,
                'email' => $c->email,
                'principal' => $c->principal,
            ]),
        ]);
    }
}
