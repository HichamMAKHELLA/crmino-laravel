<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreOpportuniteRequest;
use App\Models\Opportunite;
use App\Models\Referentiels\EtapePipeline;
use App\Models\Referentiels\MotifPerte;
use App\Models\Societe;
use App\Support\Audit;
use App\Support\ClotureOpportunite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OpportuniteController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Opportunite::class);

        // Le pipeline : les affaires OUVERTES, par étape. Les colonnes viennent
        // du référentiel (catégorie « Ouverte »), pas d'un libellé codé (§64).
        $etapes = EtapePipeline::query()->where('categorie', 'Ouverte')->where('actif', true)
            ->orderBy('ordre')->get(['id', 'libelle']);

        $affaires = Opportunite::query()
            ->dansPerimetre($request->user(), 'opportunite.consulter')
            ->where('statut', 'Ouverte')
            ->with(['societe:id,raison_sociale', 'etape:id'])
            ->orderByDesc('montant_ht')
            ->get(['id', 'numero', 'intitule', 'societe_id', 'etape_id', 'montant_ht', 'montant_pondere', 'probabilite']);

        $colonnes = $etapes->map(function ($e) use ($affaires) {
            $cartes = $affaires->where('etape_id', $e->id)->values();

            return [
                'id' => $e->id,
                'libelle' => $e->libelle,
                'couleur' => null,
                'total_pondere' => (float) $cartes->sum('montant_pondere'),
                'cartes' => $cartes->map(fn ($o) => [
                    'id' => $o->id,
                    'intitule' => $o->intitule,
                    'societe' => $o->societe?->raison_sociale,
                    'montant_ht' => (float) $o->montant_ht,
                    'montant_pondere' => (float) $o->montant_pondere,
                    'probabilite' => $o->probabilite,
                ])->values(),
            ];
        });

        return Inertia::render('Opportunites/Index', ['colonnes' => $colonnes]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Opportunite::class);

        return Inertia::render('Opportunites/Create', [
            'societes' => Societe::query()
                ->dansPerimetre($request->user(), 'societe.consulter')
                ->orderBy('raison_sociale')
                ->get(['id', 'raison_sociale'])
                ->map(fn ($s) => ['id' => $s->id, 'raison_sociale' => $s->raison_sociale]),
            'etapes' => EtapePipeline::query()->where('categorie', 'Ouverte')->where('actif', true)
                ->orderBy('ordre')->get(['id', 'libelle', 'probabilite'])
                ->map(fn ($e) => ['id' => $e->id, 'libelle' => $e->libelle, 'probabilite' => $e->probabilite]),
            'societePreselectionnee' => $request->integer('societe') ?: null,
        ]);
    }

    public function show(Request $request, Opportunite $opportunite): Response
    {
        abort_unless($request->user()->peut('opportunite.consulter'), 403);
        abort_unless($this->dansPerimetre($request, $opportunite, 'opportunite.consulter'), 404);

        $opportunite->load(['societe:id,numero,raison_sociale,etat', 'etape:id,libelle', 'motifPerte:id,libelle', 'proprietaire']);

        return Inertia::render('Opportunites/Show', [
            'opportunite' => [
                'id' => $opportunite->id,
                'numero' => $opportunite->numero,
                'intitule' => $opportunite->intitule,
                'statut' => $opportunite->statut,
                'etape' => $opportunite->etape?->libelle,
                'probabilite' => $opportunite->probabilite,
                'montant_ht' => (float) $opportunite->montant_ht,
                'montant_pondere' => (float) $opportunite->montant_pondere,
                'motif_perte' => $opportunite->motifPerte?->libelle,
                'commentaire_perte' => $opportunite->commentaire_perte,
                'proprietaire' => $opportunite->proprietaire
                    ? trim(($opportunite->proprietaire->prenom ?? '').' '.($opportunite->proprietaire->nom ?? ''))
                    : null,
            ],
            'societe' => [
                'id' => $opportunite->societe?->id,
                'numero' => $opportunite->societe?->numero,
                'raison_sociale' => $opportunite->societe?->raison_sociale,
                'etat' => $opportunite->societe?->etat,
            ],
            'peutCloturer' => $request->user()->peut('opportunite.cloturer'),
            'motifs' => MotifPerte::query()->where('actif', true)->orderBy('ordre')
                ->get(['id', 'libelle', 'commentaire_obligatoire'])
                ->map(fn ($m) => ['id' => $m->id, 'libelle' => $m->libelle, 'commentaire_obligatoire' => $m->commentaire_obligatoire]),
        ]);
    }

    public function gagner(Request $request, Opportunite $opportunite, ClotureOpportunite $cloture): RedirectResponse
    {
        abort_unless($request->user()->peut('opportunite.cloturer'), 403);
        abort_unless($this->dansPerimetre($request, $opportunite, 'opportunite.cloturer'), 404);

        // RG-OPP-005 : une affaire close ne se referme pas.
        if ($opportunite->estClose()) {
            return back()->with('error', 'RG-OPP-005 : cette affaire est close. Son étape ne change plus.');
        }

        $cloture->gagner($opportunite, $request->user());
        Audit::tracer($request->user()->id, Audit::CHANGEMENT_STATUT, 'Opportunite', $opportunite->id, 'statut', 'Ouverte', 'Gagnee');

        return back()->with('success',
            "Affaire gagnée. {$opportunite->societe?->raison_sociale} devient cliente (RG-OPP-003).");
    }

    public function perdre(Request $request, Opportunite $opportunite, ClotureOpportunite $cloture): RedirectResponse
    {
        abort_unless($request->user()->peut('opportunite.cloturer'), 403);
        abort_unless($this->dansPerimetre($request, $opportunite, 'opportunite.cloturer'), 404);

        // RG-OPP-005 AVANT le motif : sinon une affaire close rendrait le refus
        // d'une perte sans motif, et l'on chercherait un motif qui ne débloque rien.
        if ($opportunite->estClose()) {
            return back()->with('error', 'RG-OPP-005 : cette affaire est close. Son étape ne change plus.');
        }

        // RG-OPP-002 : une perte exige un motif.
        $data = $request->validate([
            'motif_perte_id' => ['required', 'integer', 'exists:motifs_perte,id'],
            'commentaire' => ['nullable', 'string', 'max:2000'],
        ]);

        // RG-OPP-002 : certains motifs exigent un commentaire.
        $motif = MotifPerte::query()->find($data['motif_perte_id']);
        if ($motif?->commentaire_obligatoire && trim((string) ($data['commentaire'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'commentaire' => 'RG-OPP-002 : ce motif de perte exige un commentaire.',
            ]);
        }

        $cloture->perdre($opportunite, (int) $data['motif_perte_id'], $data['commentaire'] ?? null, $request->user());
        Audit::tracer($request->user()->id, Audit::CHANGEMENT_STATUT, 'Opportunite', $opportunite->id, 'statut', 'Ouverte', 'Perdue');

        return back()->with('success', 'Affaire clôturée en perte.');
    }

    private function dansPerimetre(Request $request, Opportunite $opportunite, string $permission): bool
    {
        return Opportunite::query()->whereKey($opportunite->id)
            ->dansPerimetre($request->user(), $permission)->exists();
    }

    public function store(StoreOpportuniteRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $etape = EtapePipeline::query()->findOrFail($data['etape_id']);

        $opportunite = Opportunite::create([
            'intitule' => $data['intitule'],
            'societe_id' => $data['societe_id'],
            'etape_id' => $etape->id,
            'statut' => 'Ouverte',
            // RG-OPP-004 : à défaut de saisie, la probabilité de l'étape.
            'probabilite' => $data['probabilite'] ?? $etape->probabilite,
            'montant_ht' => $data['montant_ht'] ?? 0,
            'date_cloture_estimee' => $data['date_cloture_estimee'] ?? null,
            'commentaire' => $data['commentaire'] ?? null,
            'proprietaire_id' => $request->user()->id,
            'equipe_id' => $request->user()->equipe_id,
            'cree_par' => $request->user()->id,
        ]);

        return to_route('opportunites.index')
            ->with('success', "Affaire {$opportunite->numero} ouverte.");
    }
}
