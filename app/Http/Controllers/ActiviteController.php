<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Activite;
use App\Models\Lead;
use App\Models\Societe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ActiviteController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->peut('activite.creer'), 403);

        $data = $request->validate([
            'cible_type' => ['required', Rule::in(['lead', 'societe'])],
            'cible_id' => ['required', 'integer'],
            'type_id' => ['required', 'integer', 'exists:types_activite,id'],
            'debut_le' => ['required', 'date'],
            'objet' => ['nullable', 'string', 'max:200'],
            'resultat' => ['nullable', 'string', 'max:500'],
            'duree_minutes' => ['nullable', 'integer', 'min:0'],
            'prochaine_action_le' => ['nullable', 'date'],
            'prochaine_action_libelle' => ['nullable', 'string', 'max:200'],
        ]);

        // §58 : la cible doit être dans le périmètre — sinon 404, jamais 403.
        [$parent, $colonne] = $data['cible_type'] === 'lead'
            ? [Lead::query()->whereKey($data['cible_id'])
                ->dansPerimetre($request->user(), 'lead.consulter')->first(), 'lead_id']
            : [Societe::query()->whereKey($data['cible_id'])
                ->dansPerimetre($request->user(), 'societe.consulter')->first(), 'societe_id'];
        abort_if($parent === null, 404);

        $debut = \Illuminate\Support\Carbon::parse($data['debut_le']);

        DB::transaction(function () use ($data, $parent, $colonne, $debut, $request): void {
            Activite::create([
                $colonne => $parent->id,
                'type_id' => $data['type_id'],
                'utilisateur_id' => $request->user()->id,
                'debut_le' => $debut,
                'objet' => $data['objet'] ?? null,
                'resultat' => $data['resultat'] ?? null,
                'duree_minutes' => $data['duree_minutes'] ?? null,
                'prochaine_action_le' => $data['prochaine_action_le'] ?? null,
                'prochaine_action_libelle' => $data['prochaine_action_libelle'] ?? null,
                'cree_par' => $request->user()->id,
            ]);

            // §20 : l'activité remonte sur la fiche. DerniereActiviteLe ne RECULE
            // jamais — corriger une date saisie trop tard ne rajeunit pas la fiche.
            if ($parent->derniere_activite_le === null || $debut > $parent->derniere_activite_le) {
                $parent->derniere_activite_le = $debut;
            }
            $parent->prochaine_action_le = $data['prochaine_action_le'] ?? null;
            // Seul le lead porte un libellé de prochaine action ; la société n'a
            // que la date (fidèle à app.Societe).
            if ($colonne === 'lead_id') {
                $parent->prochaine_action_libelle = $data['prochaine_action_libelle'] ?? null;
            }
            $parent->save();
        });

        return back()->with('success', 'Activité enregistrée.');
    }
}
