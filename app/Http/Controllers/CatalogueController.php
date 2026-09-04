<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Produit;
use App\Models\Referentiels\GammeProduit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogueController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->peut('catalogue.consulter'), 403);

        $produits = Produit::query()
            ->with(['gamme:id,libelle,famille_id', 'gamme.famille:id,libelle'])
            ->orderByDesc('actif')->orderBy('ordre')
            ->get()
            ->map(fn (Produit $p) => [
                'id' => $p->id,
                'code' => $p->code,
                'designation' => $p->designation,
                'famille' => $p->gamme?->famille?->libelle,
                'gamme' => $p->gamme?->libelle,
                'type' => $p->type,
                'prix_catalogue' => $p->prix_catalogue !== null ? (float) $p->prix_catalogue : null,
                'actif' => $p->actif,
            ]);

        return Inertia::render('Catalogue/Index', [
            'produits' => $produits,
            'peutGerer' => $request->user()->peut('catalogue.gerer'),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()->peut('catalogue.gerer'), 403);

        return Inertia::render('Catalogue/Create', [
            'gammes' => GammeProduit::query()->where('actif', true)->orderBy('ordre')
                ->with('famille:id,libelle')->get(['id', 'libelle', 'famille_id'])
                ->map(fn ($g) => ['id' => $g->id, 'libelle' => trim(($g->famille?->libelle ?? '').' — '.$g->libelle)]),
            'types' => ['Licence', 'Abonnement', 'Prestation', 'Formation', 'Materiel'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->peut('catalogue.gerer'), 403);

        $data = $request->validate([
            'gamme_id' => ['required', 'integer', 'exists:gammes_produit,id'],
            // Le code est réservé à vie : unique sur TOUS les produits, même retirés.
            'code' => ['required', 'string', 'max:32', 'unique:produits,code'],
            'designation' => ['required', 'string', 'max:200'],
            'type' => ['required', 'in:Licence,Abonnement,Prestation,Formation,Materiel'],
            'prix_catalogue' => ['nullable', 'numeric', 'min:0'],
            'unite' => ['nullable', 'string', 'max:20'],
        ]);

        Produit::create([...$data, 'code' => strtoupper($data['code']), 'actif' => true, 'cree_par' => $request->user()->id]);

        return to_route('catalogue.index')->with('success', 'Produit ajouté au catalogue.');
    }

    public function desactiver(Request $request, Produit $produit): RedirectResponse
    {
        abort_unless($request->user()->peut('catalogue.gerer'), 403);

        $produit->forceFill(['actif' => false, 'modifie_par' => $request->user()->id])->save();

        return back()->with('success', "Produit {$produit->code} retiré du catalogue.");
    }

    public function reactiver(Request $request, Produit $produit): RedirectResponse
    {
        abort_unless($request->user()->peut('catalogue.gerer'), 403);

        // §47 : un produit retiré revient. Le code, resté réservé, n'a jamais
        // désigné un autre produit — la remise au catalogue est sûre.
        $produit->forceFill(['actif' => true, 'modifie_par' => $request->user()->id])->save();

        return back()->with('success', "Produit {$produit->code} remis au catalogue.");
    }
}
