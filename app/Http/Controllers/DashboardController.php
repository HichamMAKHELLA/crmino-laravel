<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Opportunite;
use App\Models\Tache;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Accueil commercial (§77). Agrège dans le PÉRIMÈTRE de l'appelant. « Non scoré »
 * et les taux sans base suivent RG-IND-001 : une absence de mesure n'est pas un
 * zéro.
 */
class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $u = $request->user();
        $peutLire = $u->peut('lead.consulter');

        // Leads à traiter : dans mon périmètre, actifs, non convertis.
        $mesLeads = $peutLire
            ? Lead::query()->dansPerimetre($u, 'lead.consulter')
                ->where('actif', true)->whereNull('societe_id')->count()
            : 0;

        // Pipeline pondéré : la somme des affaires OUVERTES de mon périmètre.
        $pipeline = Opportunite::query()->dansPerimetre($u, 'opportunite.consulter')
            ->where('statut', 'Ouverte');
        $pipelinePondere = (float) (clone $pipeline)->sum('montant_pondere');
        $nbAffairesOuvertes = (clone $pipeline)->count();

        // Mes tâches du jour : celles qui me sont assignées, échues ou d'aujourd'hui.
        $mesTaches = Tache::query()->where('assignee_id', $u->id)
            ->whereIn('statut', ['AFaire', 'EnCours'])->where('actif', true)
            ->where(fn ($q) => $q->whereNull('echeance_le')->orWhere('echeance_le', '<=', now()->endOfDay()))
            ->count();

        // CA gagné ce mois, dans mon périmètre.
        $gagnees = Opportunite::query()->dansPerimetre($u, 'opportunite.consulter')
            ->where('statut', 'Gagnee')
            ->whereBetween('date_cloture', [now()->startOfMonth(), now()->endOfMonth()]);
        $caGagneMois = (float) (clone $gagnees)->sum('montant_ht');

        // Taux de gain (RG-IND-001) : gagnées / closes. NULL si aucune close.
        $closes = Opportunite::query()->dansPerimetre($u, 'opportunite.consulter')
            ->whereIn('statut', ['Gagnee', 'Perdue']);
        $nbClose = (clone $closes)->count();
        $nbGagne = (clone $closes)->where('statut', 'Gagnee')->count();
        $tauxGain = $nbClose > 0 ? round($nbGagne / $nbClose * 100, 1) : null;

        return Inertia::render('Dashboard', [
            'prenom' => $u->prenom ?: $u->name,
            'tuiles' => [
                'mes_leads' => $mesLeads,
                'pipeline_pondere' => $pipelinePondere,
                'nb_affaires_ouvertes' => $nbAffairesOuvertes,
                'mes_taches' => $mesTaches,
                'ca_gagne_mois' => $caGagneMois,
                'taux_gain' => $tauxGain, // null = pas de base (RG-IND-001)
            ],
        ]);
    }
}
