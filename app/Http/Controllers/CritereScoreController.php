<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\ScoreSuggestion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Administration des critères de score (§15, §50). Écran de DIAGNOSTIC : il
 * montre le poids de chaque critère, sa source, et POURQUOI un critère est hors
 * d'atteinte — sans lui, l'écran de qualification signalerait un plafond réduit
 * que personne ne saurait diagnostiquer. Réservé à qui gère les référentiels.
 */
class CritereScoreController extends Controller
{
    public function index(Request $request, ScoreSuggestion $score): Response
    {
        abort_unless($request->user()->peut('referentiel.gerer'), 403);

        return Inertia::render('CriteresScore/Index', $score->diagnostic());
    }
}
