<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Campagne;
use App\Models\ImportLot;
use App\Models\Referentiels\Source;
use App\Support\Import\ImportLeads;
use App\Support\Import\LectureClasseur;
use App\Support\Import\LectureTabulee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Assistant d'import (§42). En DEUX temps : analyser (aperçu, aucune écriture)
 * puis exécuter (import atomique). La campagne est FACULTATIVE et son omission
 * est IRRÉVERSIBLE — l'import crée les leads d'un coup ; les rattacher après
 * demanderait de reprendre chaque fiche.
 */
class ImportController extends Controller
{
    private const TAILLE_MAX = 5 * 1024; // 5 Mo, en kilo-octets

    public function index(Request $request): Response
    {
        abort_unless($request->user()->peut('import.executer'), 403);

        return Inertia::render('Import/Index', $this->donnees($request));
    }

    public function analyser(Request $request, ImportLeads $import): Response
    {
        abort_unless($request->user()->peut('import.executer'), 403);

        $request->validate(['fichier' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:'.self::TAILLE_MAX]]);

        $lignes = $this->lignesDuFichier($request->file('fichier'));

        // L'aperçu ne DÉCLENCHE aucune écriture (§42).
        return Inertia::render('Import/Index', array_merge($this->donnees($request), [
            'apercu' => $import->apercu($lignes),
            'nomFichier' => $request->file('fichier')->getClientOriginalName(),
        ]));
    }

    public function executer(Request $request, ImportLeads $import): RedirectResponse
    {
        abort_unless($request->user()->peut('import.executer'), 403);

        $data = $request->validate([
            'fichier' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:'.self::TAILLE_MAX],
            'source_id' => ['required', 'integer', 'exists:sources,id'],
            'campagne_id' => ['nullable', 'integer'],
        ]);

        $lignes = $this->lignesDuFichier($request->file('fichier'));

        $lot = $import->importer(
            $request->user(),
            $lignes,
            (int) $data['source_id'],
            $data['campagne_id'] ?? null,
            $request->file('fichier')->getClientOriginalName(),
        );

        return to_route('import.index')->with('success',
            "Import terminé : {$lot->lignes_importees} lead".($lot->lignes_importees > 1 ? 's' : '')
            .", {$lot->lignes_rejetees} rejeté".($lot->lignes_rejetees > 1 ? 's' : '')
            .", {$lot->lignes_doublons} doublon".($lot->lignes_doublons > 1 ? 's' : '').'.');
    }

    /**
     * Un seul point sait de quel format vient la ligne — la suite (mapping,
     * doublons, aperçu) n'en dépend pas (§42). Le classeur passe par le fichier
     * sur disque ; le CSV par son contenu.
     *
     * @return list<array<string,string>>
     */
    private function lignesDuFichier(UploadedFile $fichier): array
    {
        if (strtolower($fichier->getClientOriginalExtension()) === 'xlsx') {
            return LectureClasseur::analyser($fichier->getRealPath())['lignes'];
        }

        return LectureTabulee::analyser($fichier->get())['lignes'];
    }

    /** @return array<string, mixed> */
    private function donnees(Request $request): array
    {
        return [
            'sources' => Source::query()->where('actif', true)->orderBy('ordre')->get(['id', 'libelle'])
                ->map(fn ($s) => ['id' => $s->id, 'libelle' => $s->libelle]),
            'campagnes' => Campagne::query()->where('actif', true)->orderByDesc('id')->get(['id', 'nom'])
                ->map(fn ($c) => ['id' => $c->id, 'nom' => $c->nom]),
            'lots' => ImportLot::query()->with('source:id,libelle')->latest('lance_le')->limit(20)->get()
                ->map(fn (ImportLot $l) => [
                    'id' => $l->id,
                    'nom_fichier' => $l->nom_fichier,
                    'statut' => $l->statut,
                    'total' => $l->lignes_total,
                    'importees' => $l->lignes_importees,
                    'rejetees' => $l->lignes_rejetees,
                    'doublons' => $l->lignes_doublons,
                    'lance_le' => $l->lance_le?->toIso8601String(),
                ]),
        ];
    }
}
