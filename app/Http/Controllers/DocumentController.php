<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Lead;
use App\Models\Opportunite;
use App\Models\Societe;
use App\Support\Audit;
use App\Support\StockageDocuments;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Documents (§44). Le nom d'origine sert à l'AFFICHAGE, jamais au disque
 * (§58). Le téléchargement est TOUJOURS en pièce jointe, avec un type MIME
 * neutre : servir un fichier dans la page ouvrirait une faille d'exécution.
 *
 * Le crayon (reclassement) est gardé par `document.deposer`, PAS par le retrait
 * (`document.supprimer`) : aligner « par symétrie » retirerait la correction à
 * qui peut pourtant reclasser.
 */
class DocumentController extends Controller
{
    /** cible_type → [modèle, permission de consultation]. */
    private const CIBLES = [
        'Lead' => [Lead::class, 'lead.consulter'],
        'Societe' => [Societe::class, 'societe.consulter'],
        'Opportunite' => [Opportunite::class, 'opportunite.consulter'],
    ];

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->peut('document.deposer'), 403);

        $data = $request->validate([
            'cible_type' => ['required', 'string', 'in:'.implode(',', array_keys(self::CIBLES))],
            'cible_id' => ['required', 'integer'],
            'type_document_id' => ['nullable', 'integer', 'exists:types_document,id'],
            'fichier' => ['required', 'file', 'max:'.(StockageDocuments::TAILLE_MAX / 1024)],
        ]);

        // Liste BLANCHE d'extensions (§58).
        $fichier = $request->file('fichier');
        abort_unless(StockageDocuments::extensionAdmise($fichier->getClientOriginalName()), 422,
            'Format non accepté.');

        // La visibilité de la cible AVANT d'écrire sur disque : un refus après
        // écriture laisserait un fichier orphelin.
        abort_unless($this->cibleVisible($request, $data['cible_type'], (int) $data['cible_id']), 404);

        [$nomStockage, $chemin] = StockageDocuments::ecrire($fichier);

        $doc = Document::create([
            'cible_type' => $data['cible_type'],
            'cible_id' => (int) $data['cible_id'],
            'type_document_id' => $data['type_document_id'] ?? null,
            'nom' => $fichier->getClientOriginalName(), // affichage seulement
            'nom_stockage' => $nomStockage,
            'chemin_stockage' => $chemin,
            'content_type' => $fichier->getClientMimeType(),
            'taille_octets' => $fichier->getSize(),
            'depose_par' => $request->user()->id,
        ]);

        Audit::tracer($request->user()->id, Audit::CREATION, 'Document', $doc->id, 'nom', null, $doc->nom);

        return back()->with('success', 'Document déposé.');
    }

    /** §44/§58 : télécharge un document, TOUJOURS en pièce jointe, MIME neutre. */
    public function download(Request $request, Document $document): StreamedResponse
    {
        abort_unless($request->user()->peut('document.consulter'), 403);
        abort_unless($document->actif, 404);
        abort_unless($this->cibleVisible($request, $document->cible_type, $document->cible_id), 404);

        $disque = StockageDocuments::disque();
        abort_unless($disque->exists($document->chemin_stockage), 404);

        return $disque->download($document->chemin_stockage, $document->nom, [
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    /**
     * §44 : reclasse un document (type, nom affiché). Gardé par document.deposer.
     * Le nom de STOCKAGE, la taille, le type MIME sont des faits sur l'octet
     * déposé — jamais dans le corps (le §58 s'appuie dessus pour servir).
     */
    public function update(Request $request, Document $document): RedirectResponse
    {
        abort_unless($request->user()->peut('document.deposer'), 403);
        abort_unless($document->actif, 404);
        abort_unless($this->cibleVisible($request, $document->cible_type, $document->cible_id), 404);

        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'type_document_id' => ['nullable', 'integer', 'exists:types_document,id'],
        ]);

        $document->update([
            'nom' => trim($data['nom']),
            'type_document_id' => $data['type_document_id'] ?? null,
        ]);

        Audit::tracer($request->user()->id, Audit::MODIFICATION, 'Document', $document->id);

        return back()->with('success', 'Document reclassé.');
    }

    /** §47 : retirer n'est pas détruire. La suppression est LOGIQUE ; l'octet reste. */
    public function destroy(Request $request, Document $document): RedirectResponse
    {
        abort_unless($request->user()->peut('document.supprimer'), 403);
        abort_unless($this->cibleVisible($request, $document->cible_type, $document->cible_id), 404);

        $document->update(['actif' => false]);
        Audit::tracer($request->user()->id, Audit::SUPPRESSION_LOGIQUE, 'Document', $document->id);

        return back()->with('success', 'Document retiré.');
    }

    private function cibleVisible(Request $request, string $cibleType, int $cibleId): bool
    {
        [$modele, $permission] = self::CIBLES[$cibleType];

        /** @var Builder<covariant \Illuminate\Database\Eloquent\Model> $q */
        $q = $modele::query()->whereKey($cibleId);

        return $q->dansPerimetre($request->user(), $permission)->exists();
    }
}
