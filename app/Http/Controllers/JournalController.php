<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditJournal;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Journal d'audit (§46, §80). Consultation seule. `journal.consulter` n'est pas
 * une lecture ordinaire : le journal traverse tous les portefeuilles et porte
 * les anciennes et nouvelles valeurs de chaque champ.
 */
class JournalController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->peut('journal.consulter'), 403);

        $lignes = AuditJournal::query()
            ->with('utilisateur:id,prenom,nom')
            ->when($request->query('action'), fn ($q, $a) => $q->where('action', $a))
            ->latest('le')
            ->paginate(30)
            ->through(fn (AuditJournal $j) => [
                'id' => $j->id,
                'le' => $j->le?->toIso8601String(),
                'utilisateur' => $j->utilisateur
                    ? trim(($j->utilisateur->prenom ?? '').' '.($j->utilisateur->nom ?? '')) : 'Système',
                'action' => $j->action,
                'entite' => $j->entite_type.' #'.$j->entite_id,
                'champ' => $j->champ,
                // §46 : un champ vide se DIT « vide », jamais une case blanche.
                'ancienne' => $j->champ !== null ? ($j->ancienne_valeur ?? 'vide') : null,
                'nouvelle' => $j->champ !== null ? ($j->nouvelle_valeur ?? 'vide') : null,
            ]);

        return Inertia::render('Journal/Index', [
            'lignes' => $lignes,
            'action' => $request->query('action'),
        ]);
    }
}
