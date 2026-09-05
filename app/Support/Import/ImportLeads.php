<?php

declare(strict_types=1);

namespace App\Support\Import;

use App\Models\ImportLot;
use App\Models\Lead;
use App\Models\Referentiels\StatutLead;
use App\Models\User;
use App\Support\DetecteurDoublons;
use App\Support\ReglesLead;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Import de leads (§42). Assistant en DEUX temps : apercu() classe sans RIEN
 * écrire (l'utilisateur voit ce que l'import produirait) ; importer() écrit dans
 * une transaction ATOMIQUE. Un rejet est une ligne MALFORMÉE (RG-LEA-001) ; un
 * doublon a été RECONNU (§41) — deux gestes différents, jamais confondus.
 */
final class ImportLeads
{
    public function __construct(private DetecteurDoublons $detecteur) {}

    /**
     * @param  list<array<string,string>>  $lignes
     * @return array{total:int, importables:int, rejetees:int, doublons:int, apercu:list<array{ligne:int, resultat:string, raison_sociale:?string, motif:?string}>}
     */
    public function apercu(array $lignes): array
    {
        $total = 0;
        $importables = 0;
        $rejetees = 0;
        $doublons = 0;
        $apercu = [];

        foreach ($lignes as $i => $ligne) {
            $total++;
            [$resultat, $motif] = $this->classer($ligne);
            match ($resultat) {
                'Importee' => $importables++,
                'Rejetee' => $rejetees++,
                'Doublon' => $doublons++,
            };
            $apercu[] = [
                'ligne' => $i + 1,
                'resultat' => $resultat,
                'raison_sociale' => $this->champ($ligne, 'raison_sociale'),
                'motif' => $motif,
            ];
        }

        return compact('total', 'importables', 'rejetees', 'doublons', 'apercu');
    }

    /**
     * @param  list<array<string,string>>  $lignes
     */
    public function importer(User $auteur, array $lignes, int $sourceId, ?int $campagneId, string $nomFichier): ImportLot
    {
        try {
            return DB::transaction(function () use ($auteur, $lignes, $sourceId, $campagneId, $nomFichier): ImportLot {
                $lot = ImportLot::create([
                    'nom_fichier' => $nomFichier, 'type' => 'Lead',
                    'source_id' => $sourceId, 'campagne_id' => $campagneId,
                    'lance_par' => $auteur->id, 'statut' => 'EnCours',
                ]);

                $importees = $rejetees = $doublons = 0;
                $statutNouveau = StatutLead::query()->where('code', 'NOUVEAU')->value('id');

                foreach ($lignes as $i => $ligne) {
                    [$resultat, $motif] = $this->classer($ligne);
                    $cibleId = null;

                    if ($resultat === 'Importee') {
                        $lead = Lead::create([
                            'source_id' => $sourceId,
                            'raison_sociale' => $this->champ($ligne, 'raison_sociale'),
                            'ice' => $this->champ($ligne, 'ice'),
                            'site_web' => $this->champ($ligne, 'site_web'),
                            'statut_id' => $statutNouveau,
                            'proprietaire_id' => $auteur->id,
                            'equipe_id' => $auteur->equipe_id,
                            'campagne_id' => $campagneId,
                            'affecte_le' => now(),
                            'cree_par' => $auteur->id,
                        ]);
                        $nom = $this->champ($ligne, 'nom_contact') ?? $this->champ($ligne, 'nom');
                        if ($nom !== null) {
                            $lead->contacts()->create([
                                'nom' => $nom,
                                'prenom' => $this->champ($ligne, 'prenom_contact') ?? $this->champ($ligne, 'prenom'),
                                'telephone' => $this->champ($ligne, 'telephone'),
                                'gsm' => $this->champ($ligne, 'gsm'),
                                'email' => $this->champ($ligne, 'email'),
                                'principal' => true,
                                'cree_par' => $auteur->id,
                            ]);
                        }
                        $cibleId = $lead->id;
                        $importees++;
                    } elseif ($resultat === 'Doublon') {
                        $doublons++;
                    } else {
                        $rejetees++;
                    }

                    $lot->lignes()->create([
                        'numero_ligne' => $i + 1,
                        'contenu' => json_encode($ligne, JSON_UNESCAPED_UNICODE),
                        'resultat' => $resultat,
                        'motif' => $motif,
                        'cible_id' => $cibleId,
                    ]);
                }

                $lot->update([
                    'lignes_total' => count($lignes),
                    'lignes_importees' => $importees,
                    'lignes_rejetees' => $rejetees,
                    'lignes_doublons' => $doublons,
                    'statut' => 'Termine',
                    'termine_le' => now(),
                ]);

                return $lot->fresh();
            });
        } catch (Throwable $e) {
            // L'import est ATOMIQUE : rien n'est écrit sur échec. Mais un lot
            // « Echoue » laisse une trace — sinon l'utilisateur voit une erreur
            // et un historique vide, sans savoir si quelque chose a été créé.
            ImportLot::create([
                'nom_fichier' => $nomFichier, 'type' => 'Lead',
                'source_id' => $sourceId, 'campagne_id' => $campagneId,
                'lance_par' => $auteur->id, 'statut' => 'Echoue',
                'lignes_total' => count($lignes), 'termine_le' => now(),
            ]);
            throw $e;
        }
    }

    /** @return array{0:string, 1:?string} [resultat, motif] — sans aucune écriture. */
    private function classer(array $ligne): array
    {
        $erreur = ReglesLead::controlerDonneesMinimales(
            $this->champ($ligne, 'raison_sociale'),
            $this->champ($ligne, 'nom_contact') ?? $this->champ($ligne, 'nom'),
            $this->champ($ligne, 'telephone'),
            $this->champ($ligne, 'gsm'),
            $this->champ($ligne, 'email'),
        );
        if ($erreur !== null) {
            return ['Rejetee', $erreur['message']];
        }

        $candidats = $this->detecteur->rechercher(
            $this->champ($ligne, 'raison_sociale'),
            $this->champ($ligne, 'ice'),
            $this->champ($ligne, 'telephone') ?? $this->champ($ligne, 'gsm'),
            $this->champ($ligne, 'email'),
            $this->champ($ligne, 'site_web'),
        );
        if ($candidats !== []) {
            return ['Doublon', 'Rapproché d\'une fiche existante (§41).'];
        }

        return ['Importee', null];
    }

    private function champ(array $ligne, string $cle): ?string
    {
        $v = $ligne[$cle] ?? null;

        return ($v === null || trim($v) === '') ? null : trim($v);
    }
}
