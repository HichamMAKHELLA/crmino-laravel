<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Referentiels\TypeActivite;
use App\Models\Societe;
use App\Support\Audit;
use App\Support\TransitionSociete;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
            'activites' => fn ($q) => $q->where('actif', true)->orderByDesc('debut_le')->with(['type:id,libelle', 'utilisateur:id,prenom,nom']),
        ]);

        // §45 : les notes internes de la fiche (les plus récentes d'abord).
        $notes = \App\Models\Commentaire::query()
            ->where('cible_type', 'Societe')->where('cible_id', $societe->id)->where('actif', true)
            ->with('auteur:id,prenom,nom')->latest()->get();

        // §44 : les documents de la fiche.
        $documents = \App\Models\Document::query()
            ->where('cible_type', 'Societe')->where('cible_id', $societe->id)->where('actif', true)
            ->with('type:id,libelle')->latest('depose_le')->get();

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
            'activites' => $societe->activites->map(fn ($a) => [
                'id' => $a->id,
                'type' => $a->type?->libelle,
                'objet' => $a->objet,
                'resultat' => $a->resultat,
                'debut_le' => $a->debut_le?->toIso8601String(),
                'utilisateur' => $a->utilisateur ? trim(($a->utilisateur->prenom ?? '').' '.($a->utilisateur->nom ?? '')) : null,
                'prochaine_action_le' => $a->prochaine_action_le?->toIso8601String(),
                'prochaine_action_libelle' => $a->prochaine_action_libelle,
            ]),
            'typesActivite' => TypeActivite::query()->where('actif', true)->orderBy('ordre')->get(['id', 'libelle'])->map(fn ($t) => ['id' => $t->id, 'libelle' => $t->libelle]),
            // §32 : les états atteignables depuis l'état courant (RG-SOC-001).
            'ciblesEtat' => $request->user()->peut('societe.modifier') ? TransitionSociete::cibles($societe->etat) : [],
            'moiId' => $request->user()->id,
            'peutDeposer' => $request->user()->peut('document.deposer'),
            'peutSupprimer' => $request->user()->peut('document.supprimer'),
            'typesDocument' => \App\Models\Referentiels\TypeDocument::query()->where('actif', true)->orderBy('ordre')
                ->get(['id', 'libelle'])->map(fn ($t) => ['id' => $t->id, 'libelle' => $t->libelle]),
            'documents' => $documents->map(fn ($d) => [
                'id' => $d->id,
                'nom' => $d->nom,
                'type' => $d->type?->libelle,
                'taille_octets' => $d->taille_octets,
                'depose_le' => $d->depose_le?->toIso8601String(),
            ]),
            'notes' => $notes->map(fn ($n) => [
                'id' => $n->id,
                'texte' => $n->texte,
                'auteur' => $n->auteur ? trim(($n->auteur->prenom ?? '').' '.($n->auteur->nom ?? '')) : null,
                'auteur_id' => $n->auteur_id,
                'cree_le' => $n->created_at?->toIso8601String(),
                'modifie_le' => $n->modifie_le?->toIso8601String(),
            ]),
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

    /**
     * §32 : change l'ÉTAT de la relation (RG-SOC-001). UNE seule route pour tous
     * les sens — le jugement vit dans TransitionSociete, deux routes le
     * dédoubleraient. Trace ChangementStatut (le §46 le distingue de Modification).
     * RG-SOC-001 : le passage à Client pose la date de passage si elle manque.
     */
    public function etat(Request $request, Societe $societe): RedirectResponse
    {
        abort_unless($request->user()->peut('societe.modifier'), 403);
        abort_unless(
            Societe::query()->whereKey($societe->id)
                ->dansPerimetre($request->user(), 'societe.modifier')->exists(),
            404,
        );

        $data = $request->validate([
            'etat' => ['required', 'string', 'in:'.implode(',', TransitionSociete::ETATS)],
        ]);

        $actuel = $societe->etat;
        $cible = $data['etat'];

        if (! TransitionSociete::admise($actuel, $cible)) {
            return back()->with('error',
                "RG-SOC-001 : une société ne passe pas de « {$actuel} » à « {$cible} ». ".
                'Un client ne redevient pas prospect : rendez-le inactif.');
        }

        if ($actuel === $cible) {
            return back();
        }

        $societe->etat = $cible;
        // RG-SOC-001 : Client porte sa date de passage — elle survit ensuite (§72).
        if ($cible === 'Client' && $societe->devenu_client_le === null) {
            $societe->devenu_client_le = Carbon::now();
        }
        $societe->save();

        Audit::tracer($request->user()->id, Audit::CHANGEMENT_STATUT, 'Societe', $societe->id, 'etat', $actuel, $cible);

        return back()->with('success', "État de la relation : {$cible}.");
    }
}
