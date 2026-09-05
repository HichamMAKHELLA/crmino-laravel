<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Commentaire;
use App\Models\Lead;
use App\Models\Opportunite;
use App\Models\Societe;
use App\Support\Audit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Notes internes / commentaires (§45). Aucune permission dédiée — il n'existe
 * pas de droit « avoir des notes » : elles sont gardées par la VISIBILITÉ de la
 * fiche parente (périmètre du Lead / de la Société / de l'Opportunité).
 *
 * Seul l'AUTEUR corrige ou retire sa note, et le refus rend 404 (jamais 403) :
 * révéler l'existence de la note d'un confrère rouvrirait le piège de
 * l'affordance qui ment. La correction se VOIT (modifie_le) ; CreeLe ne bouge
 * pas — la note garde sa place dans la discussion.
 */
class CommentaireController extends Controller
{
    /** cible_type → [modèle, permission de consultation]. */
    private const CIBLES = [
        'Lead' => [Lead::class, 'lead.consulter'],
        'Societe' => [Societe::class, 'societe.consulter'],
        'Opportunite' => [Opportunite::class, 'opportunite.consulter'],
    ];

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cible_type' => ['required', 'string', 'in:'.implode(',', array_keys(self::CIBLES))],
            'cible_id' => ['required', 'integer'],
            // Le texte est rendu comme du TEXTE côté Vue (échappé) : aucune injection.
            'texte' => ['required', 'string', 'max:4000'],
        ]);

        // La note est gardée par la visibilité de sa fiche parente.
        abort_unless($this->cibleVisible($request, $data['cible_type'], (int) $data['cible_id']), 404);

        $c = Commentaire::create([
            'cible_type' => $data['cible_type'],
            'cible_id' => (int) $data['cible_id'],
            'texte' => trim($data['texte']),
            'auteur_id' => $request->user()->id,
        ]);

        Audit::tracer($request->user()->id, Audit::CREATION, 'Commentaire', $c->id);

        return back()->with('success', 'Note ajoutée.');
    }

    /**
     * §45 : réécrire sa PROPRE note. Le corps ne porte que le texte ; CreeLe ne
     * bouge pas, modifie_le est posé. 404 si la note n'existe pas OU n'est pas
     * la sienne — même réponse, pour ne pas révéler l'existence.
     */
    public function update(Request $request, Commentaire $commentaire): RedirectResponse
    {
        abort_unless($commentaire->auteur_id === $request->user()->id, 404);

        $data = $request->validate(['texte' => ['required', 'string', 'max:4000']]);

        $ancien = $commentaire->texte;
        $commentaire->texte = trim($data['texte']);
        $commentaire->modifie_le = Carbon::now();
        $commentaire->save();

        Audit::tracer($request->user()->id, Audit::MODIFICATION, 'Commentaire', $commentaire->id, 'texte', $ancien, $commentaire->texte);

        return back()->with('success', 'Note modifiée.');
    }

    public function destroy(Request $request, Commentaire $commentaire): RedirectResponse
    {
        abort_unless($commentaire->auteur_id === $request->user()->id, 404);

        // Suppression LOGIQUE (§47) : la trace n'efface jamais.
        $commentaire->update(['actif' => false]);
        Audit::tracer($request->user()->id, Audit::SUPPRESSION_LOGIQUE, 'Commentaire', $commentaire->id);

        return back()->with('success', 'Note retirée.');
    }

    /** La fiche parente est-elle dans le périmètre de l'appelant ? */
    private function cibleVisible(Request $request, string $cibleType, int $cibleId): bool
    {
        [$modele, $permission] = self::CIBLES[$cibleType];

        /** @var Builder<covariant \Illuminate\Database\Eloquent\Model> $q */
        $q = $modele::query()->whereKey($cibleId);

        return $q->dansPerimetre($request->user(), $permission)->exists();
    }
}
