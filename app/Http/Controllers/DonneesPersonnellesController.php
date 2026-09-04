<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Portee;
use App\Models\Activite;
use App\Models\Contact;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Droit d'accès aux données personnelles (§72). Réservé à l'ADMIN (portée
 * Toutes), et le périmètre commercial NE S'APPLIQUE PAS : un dossier borné au
 * portefeuille de celui qui le produit serait incomplet tout en se présentant
 * comme complet — une attestation FAUSSE, pire qu'une absence de réponse.
 */
class DonneesPersonnellesController extends Controller
{
    public function index(Request $request): Response
    {
        $this->exigerPorteeToutes($request->user());

        $q = trim((string) $request->query('q'));
        $contacts = strlen($q) >= 2
            ? Contact::query()->where('actif', true)
                ->where(fn ($w) => $w->where('nom', 'like', "%{$q}%")
                    ->orWhere('prenom', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"))
                ->limit(20)->get(['id', 'nom', 'prenom', 'email'])
                ->map(fn ($c) => ['id' => $c->id, 'nom' => trim(($c->prenom ?? '').' '.$c->nom), 'email' => $c->email])
            : collect();

        return Inertia::render('DonneesPersonnelles/Index', ['q' => $q, 'contacts' => $contacts->values()]);
    }

    public function dossier(Request $request, Contact $contact): Response
    {
        $this->exigerPorteeToutes($request->user());

        // Aucun filtre de périmètre : le dossier DOIT être complet.
        $contact->load(['lead:id,numero,raison_sociale', 'fonction:id,libelle']);
        $activites = Activite::query()->where('contact_id', $contact->id)
            ->with('type:id,libelle')->orderByDesc('debut_le')
            ->get(['id', 'type_id', 'objet', 'debut_le']);

        // §72 : la production laisse une trace d'audit Export (un POST, pas une
        // lecture — un GET n'écrirait rien).
        Audit::tracer($request->user()->id, Audit::EXPORT, 'Contact', $contact->id);

        return Inertia::render('DonneesPersonnelles/Dossier', [
            'produitLe' => now()->toIso8601String(),
            'contact' => [
                'id' => $contact->id,
                'nom' => trim(($contact->prenom ?? '').' '.$contact->nom),
                'civilite' => $contact->civilite,
                'fonction' => $contact->fonction?->libelle ?? $contact->fonction_libre,
                'telephone' => $contact->telephone,
                'gsm' => $contact->gsm,
                'email' => $contact->email,
                'linkedin' => $contact->linkedin,
                'rattachement' => $contact->lead
                    ? 'Lead '.$contact->lead->numero.' — '.($contact->lead->raison_sociale ?? '')
                    : 'Société #'.$contact->societe_id,
            ],
            'activites' => $activites->map(fn ($a) => [
                'type' => $a->type?->libelle,
                'objet' => $a->objet,
                'le' => $a->debut_le?->toIso8601String(),
            ]),
            // §72 : le dossier NOMME ses limites, pour que celui qui traite la
            // demande aille chercher ailleurs ce qu'il ne couvre pas.
            'limites' => [
                'Les documents et pièces jointes de la fiche',
                'Les journaux applicatifs (conservés 30 jours)',
                'Les sauvegardes de la base',
            ],
        ]);
    }

    private function exigerPorteeToutes(User $u): void
    {
        // §72 : la permission ne suffit pas, il faut la portée « Toutes » — une
        // portée « Siennes » posée par mégarde ouvrirait un dossier partiel.
        $portee = $u->porteePour('donneespersonnelles.dossier')->effective($u->equipe_id);
        abort_unless($portee === Portee::Toutes, 403);
    }
}
