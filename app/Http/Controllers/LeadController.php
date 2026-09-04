<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadRequest;
use App\Models\Lead;
use App\Models\Referentiels\PalierScore;
use App\Models\Referentiels\Source;
use App\Models\Referentiels\StatutLead;
use App\Support\DetecteurDoublons;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Lead::class);

        $leads = Lead::query()
            ->dansPerimetre($request->user(), 'lead.consulter')
            ->with(['statut', 'source', 'ville'])
            ->latest()
            ->paginate(20)
            ->through(fn (Lead $l) => [
                'id' => $l->id,
                'numero' => $l->numero,
                'raison_sociale' => $l->raison_sociale,
                'statut' => $l->statut?->libelle,
                'source' => $l->source?->libelle,
                'ville' => $l->ville?->libelle,
            ]);

        return Inertia::render('Leads/Index', [
            'leads' => $leads,
            'peutCreer' => $request->user()->can('create', Lead::class),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Lead::class);

        return Inertia::render('Leads/Create', [
            'sources' => Source::query()->actif()->ordonne()
                ->get(['id', 'libelle'])->map(fn ($s) => ['id' => $s->id, 'libelle' => $s->libelle]),
        ]);
    }

    public function show(Request $request, Lead $lead): Response
    {
        // Sans la permission, le module est fermé (403).
        abort_unless($request->user()->peut('lead.consulter'), 403);

        // §58 : une fiche hors périmètre rend 404, jamais 403 — révéler son
        // existence est une fuite.
        abort_unless(
            Lead::query()->whereKey($lead->id)
                ->dansPerimetre($request->user(), 'lead.consulter')->exists(),
            404,
        );

        $lead->load([
            'statut', 'source', 'ville', 'proprietaire',
            'contacts' => fn ($q) => $q->where('actif', true)->orderByDesc('principal'),
            'contacts.fonction', 'qualification',
        ]);

        // §15 : le palier se dérive du score. « Non scoré » n'est PAS un zéro.
        $palier = $lead->score !== null ? PalierScore::pour($lead->score) : null;

        return Inertia::render('Leads/Show', [
            'lead' => [
                'id' => $lead->id,
                'numero' => $lead->numero,
                'raison_sociale' => $lead->raison_sociale,
                'ice' => $lead->ice,
                'site_web' => $lead->site_web,
                'statut' => $lead->statut?->libelle,
                'source' => $lead->source?->libelle,
                'ville' => $lead->ville?->libelle,
                'proprietaire' => $lead->proprietaire
                    ? trim(($lead->proprietaire->prenom ?? '').' '.($lead->proprietaire->nom ?? ''))
                    : null,
                'score' => $lead->score,
                'commentaire' => $lead->commentaire,
            ],
            'palier' => $palier ? [
                'libelle' => $palier->libelle,
                'borne_min' => $palier->borne_min,
                'borne_max' => $palier->borne_max,
                'couleur' => $palier->couleur,
            ] : null,
            'contacts' => $lead->contacts->map(fn ($c) => [
                'id' => $c->id,
                'nom' => trim(($c->prenom ?? '').' '.$c->nom),
                'fonction' => $c->fonction?->libelle ?? $c->fonction_libre,
                'telephone' => $c->telephone,
                'gsm' => $c->gsm,
                'email' => $c->email,
                'principal' => $c->principal,
            ]),
            'qualification' => $lead->qualification ? [
                'usage_sage' => $lead->qualification->usage_sage,
                'version_sage' => $lead->qualification->version_sage,
                'revendeur_actuel' => $lead->qualification->revendeur_actuel,
                'logiciel_actuel' => $lead->qualification->logiciel_actuel,
                'erp_actuel' => $lead->qualification->erp_actuel,
                'nb_utilisateurs' => $lead->qualification->nb_utilisateurs,
                'hebergement' => $lead->qualification->hebergement,
            ] : null,
        ]);
    }

    public function store(StoreLeadRequest $request, DetecteurDoublons $detecteur): RedirectResponse
    {
        $data = $request->validated();
        $contact = (array) ($data['contact'] ?? []);

        // §41/§71 : on MONTRE les doublons, on ne bloque pas. Un premier envoi
        // sans « forcer » les fait remonter ; l'utilisateur tranche et renvoie.
        if (! $request->boolean('forcer')) {
            try {
                $candidats = $detecteur->rechercher(
                    $data['raison_sociale'] ?? null,
                    $data['ice'] ?? null,
                    $contact['telephone'] ?? $contact['gsm'] ?? null,
                    $contact['email'] ?? null,
                    $data['site_web'] ?? null,
                );
            } catch (\Throwable $e) {
                // Si la VÉRIFICATION tombe, la création reste possible : un
                // doublon se rattrape, un prospect perdu au téléphone non.
                report($e);
                $candidats = [];
            }

            if ($candidats !== []) {
                return back()->withInput()->with('doublons', array_map(
                    fn ($c) => (array) $c, $candidats));
            }
        }

        $lead = DB::transaction(function () use ($data, $contact, $request) {
            $lead = Lead::create([
                'source_id' => $data['source_id'],
                'raison_sociale' => $data['raison_sociale'] ?? null,
                'ice' => $data['ice'] ?? null,
                'site_web' => $data['site_web'] ?? null,
                'commentaire' => $data['commentaire'] ?? null,
                'statut_id' => StatutLead::query()->where('code', 'NOUVEAU')->value('id'),
                // Le créateur devient propriétaire : un lead sans propriétaire
                // n'est relancé par personne (§20).
                'proprietaire_id' => $request->user()->id,
                'equipe_id' => $request->user()->equipe_id,
                'affecte_le' => now(),
                'cree_par' => $request->user()->id,
            ]);

            // Le contact n'est créé que s'il porte un NOM : la table l'exige, et
            // un lead peut n'être qu'une raison sociale avec un standard.
            if (! blank($contact['nom'] ?? null)) {
                $lead->contacts()->create([
                    'nom' => $contact['nom'],
                    'prenom' => $contact['prenom'] ?? null,
                    'telephone' => $contact['telephone'] ?? null,
                    'gsm' => $contact['gsm'] ?? null,
                    'email' => $contact['email'] ?? null,
                    'principal' => true,
                    'cree_par' => $request->user()->id,
                ]);
            }

            return $lead;
        });

        return to_route('leads.index')->with('success', "Lead {$lead->numero} créé.");
    }
}
