<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Opportunite;
use App\Models\Referentiels\EtapePipeline;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Clôture d'une opportunité (§25, §30). Gagner (RG-OPP-003) et perdre
 * (RG-OPP-002). L'appelant garantit d'abord que l'affaire est OUVERTE
 * (RG-OPP-005) — une affaire close ne se referme pas.
 */
final class ClotureOpportunite
{
    public function gagner(Opportunite $o, User $auteur): void
    {
        DB::transaction(function () use ($o, $auteur): void {
            $etape = EtapePipeline::query()->where('categorie', 'Gagnee')->orderBy('ordre')->value('id');
            $o->forceFill([
                'statut' => 'Gagnee',
                'etape_id' => $etape ?? $o->etape_id,
                'probabilite' => 100,
                'date_cloture' => today(),
                'modifie_par' => $auteur->id,
            ])->save();

            // RG-OPP-003 : le gain convertit le prospect en client (§31, §85-5).
            // DevenuClientLe n'est jamais effacé : il porte un fait daté (§72).
            $societe = $o->societe;
            if ($societe !== null && $societe->etat !== 'Client') {
                $societe->forceFill([
                    'etat' => 'Client',
                    'devenu_client_le' => $societe->devenu_client_le ?? today(),
                    'modifie_par' => $auteur->id,
                ])->save();
            }
        });
    }

    public function perdre(Opportunite $o, int $motifPerteId, ?string $commentaire, User $auteur): void
    {
        DB::transaction(function () use ($o, $motifPerteId, $commentaire, $auteur): void {
            $etape = EtapePipeline::query()->where('categorie', 'Perdue')->orderBy('ordre')->value('id');
            $o->forceFill([
                'statut' => 'Perdue',
                'etape_id' => $etape ?? $o->etape_id,
                'motif_perte_id' => $motifPerteId,
                'commentaire_perte' => $commentaire,
                'date_cloture' => today(),
                'modifie_par' => $auteur->id,
            ])->save();
        });
    }
}
