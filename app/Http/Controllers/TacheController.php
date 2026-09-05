<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Referentiels\PrioriteTache;
use App\Models\Referentiels\TypeTache;
use App\Models\Tache;
use App\Models\User;
use App\Support\Notifications;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TacheController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->peut('tache.consulter'), 403);

        // « Mes tâches » (§20) : celles qui me sont assignées, non terminées.
        $taches = Tache::query()
            ->where('assignee_id', $request->user()->id)
            ->whereIn('statut', ['AFaire', 'EnCours'])
            ->where('actif', true)
            ->with(['type:id,libelle', 'priorite:id,libelle,niveau'])
            ->get();

        // Tri §20 : échues d'abord, sans échéance en dernier ; à échéance égale,
        // la priorité la plus haute passe devant. Une tâche en retard reléguée
        // n'est pas rattrapée ; une sans échéance n'est pas urgente.
        $ordonnees = $taches->sort(function (Tache $a, Tache $b) {
            $ga = $a->echeance_le === null ? 1 : 0;
            $gb = $b->echeance_le === null ? 1 : 0;
            if ($ga !== $gb) {
                return $ga <=> $gb;
            }
            if (($a->echeance_le?->timestamp) !== ($b->echeance_le?->timestamp)) {
                return ($a->echeance_le?->timestamp ?? 0) <=> ($b->echeance_le?->timestamp ?? 0);
            }

            return ($b->priorite->niveau ?? 0) <=> ($a->priorite->niveau ?? 0);
        })->values();

        return Inertia::render('Taches/Index', [
            'taches' => $ordonnees->map(fn (Tache $t) => [
                'id' => $t->id,
                'titre' => $t->titre,
                'type' => $t->type?->libelle,
                'priorite' => $t->priorite?->libelle,
                'echeance_le' => $t->echeance_le?->toIso8601String(),
                'en_retard' => $t->echeance_le !== null && $t->echeance_le->isPast(),
            ]),
            'types' => TypeTache::query()->where('actif', true)->orderBy('ordre')->get(['id', 'libelle'])
                ->map(fn ($t) => ['id' => $t->id, 'libelle' => $t->libelle]),
            'priorites' => PrioriteTache::query()->where('actif', true)->orderBy('niveau')->get(['id', 'libelle'])
                ->map(fn ($p) => ['id' => $p->id, 'libelle' => $p->libelle]),
            'utilisateurs' => User::query()->where('actif', true)->orderBy('nom')->get(['id', 'prenom', 'nom'])
                ->map(fn ($u) => ['id' => $u->id, 'nom' => trim(($u->prenom ?? '').' '.($u->nom ?? '')) ?: $u->name]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->peut('tache.creer'), 403);

        $data = $request->validate([
            'titre' => ['required', 'string', 'max:200'],
            'type_id' => ['required', 'integer', 'exists:types_tache,id'],
            'priorite_id' => ['required', 'integer', 'exists:priorites_tache,id'],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'echeance_le' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        // Assignée au créateur à défaut de choix.
        $assigneeId = $data['assignee_id'] ?? $request->user()->id;

        $tache = Tache::create([
            'titre' => $data['titre'],
            'type_id' => $data['type_id'],
            'priorite_id' => $data['priorite_id'],
            'echeance_le' => $data['echeance_le'] ?? null,
            'description' => $data['description'] ?? null,
            'assignee_id' => $assigneeId,
            'statut' => 'AFaire',
            'cree_par' => $request->user()->id,
        ]);

        // §36 : une tâche assignée à QUELQU'UN D'AUTRE le notifie. On ne se
        // notifie pas soi-même — la liste cesserait d'être lue.
        if ($assigneeId !== $request->user()->id) {
            Notifications::notifier($assigneeId, 'Tâche assignée', $tache->titre, 'Tache', $tache->id, \App\Models\Notification::TACHE_ASSIGNEE);
        }

        return back()->with('success', 'Tâche créée.');
    }

    public function terminer(Request $request, Tache $tache): RedirectResponse
    {
        // On termine une tâche qui nous est assignée. « Mes tâches » ne montre
        // que les siennes ; peut('tache.modifier') vaudrait vrai en portée
        // Siennes sans vérifier la propriété, ce qui ouvrirait les tâches d'autrui.
        abort_unless($tache->assignee_id === $request->user()->id, 403);

        // Terminer pose la date d'achèvement (CK_Tache_Terminee) ; le statut est
        // absent des routes de modification ordinaires, c'est SA route.
        if ($tache->statut !== 'Terminee') {
            $tache->forceFill([
                'statut' => 'Terminee',
                'terminee_le' => now(),
                'modifie_par' => $request->user()->id,
            ])->save();
        }

        return back()->with('success', 'Tâche terminée.');
    }
}
