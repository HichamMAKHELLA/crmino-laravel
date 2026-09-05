<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Referentiels\ParametreAlerte;
use App\Support\Alertes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Alertes commerciales (§35). Le tableau montre les compteurs de l'appelant
 * (bornés à son périmètre) ; le réglage (délai, seuil, activation) est réservé
 * à qui gère les référentiels. Ni création ni suppression d'alerte.
 */
class AlerteController extends Controller
{
    public function index(Request $request, Alertes $alertes): Response
    {
        $tableau = $alertes->pour($request->user());

        return Inertia::render('Alertes/Index', [
            'lignes' => $tableau['lignes'],
            'sansProducteur' => $tableau['sans_producteur'],
            'peutRegler' => $request->user()->peut('referentiel.gerer'),
        ]);
    }

    /**
     * §35 : règle un paramètre. Le délai est borné à 3650 jours (sinon le calcul
     * lève). Le seuil ne se règle QUE sur une alerte qui le LIT (seuil_lu) —
     * l'offrir ailleurs inviterait à régler ce que rien ne lit. Pas de création
     * ni de suppression : le code identifie l'alerte, il ne se change pas.
     */
    public function update(Request $request, ParametreAlerte $parametre): RedirectResponse
    {
        abort_unless($request->user()->peut('referentiel.gerer'), 403);

        $data = $request->validate([
            'actif' => ['required', 'boolean'],
            'delai_jours' => ['required', 'integer', 'between:0,3650'],
            'seuil_montant' => ['nullable', 'numeric', 'min:0'],
        ]);

        $parametre->actif = $data['actif'];
        $parametre->delai_jours = $data['delai_jours'];
        // Le seuil ne s'écrit que là où une requête le lit.
        if (in_array($parametre->code, Alertes::LECTEURS_SEUIL, true)) {
            $parametre->seuil_montant = $data['seuil_montant'] ?? null;
        }
        $parametre->save();

        return back()->with('success', 'Alerte réglée.');
    }
}
