<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Activite;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Referentiels\StatutLead;
use App\Models\Societe;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Conversion d'un lead en société (§31). Port de DepotSocietes.Convertir.
 *
 * La bascule DÉPLACE, elle ne recopie pas : c'est tout l'intérêt du rattachement
 * polymorphe. Deux tables, une seule relation commerciale ; recopier ferait
 * exister l'historique deux fois. Seuls les CONTACTS basculent ici — activités,
 * tâches, documents et commentaires suivront avec leurs tables (§8).
 */
final class ConversionLead
{
    /**
     * @return array{societe: Societe, contacts: int, activites: int}
     */
    public function convertir(Lead $lead, ?int $societeExistanteId, User $auteur): array
    {
        return DB::transaction(function () use ($lead, $societeExistanteId, $auteur): array {
            $societe = $societeExistanteId !== null
                ? Societe::query()->findOrFail($societeExistanteId)
                : $this->creerDepuisLead($lead, $auteur);

            // ── La bascule. Aucune ligne n'est recopiée. ──
            $contacts = Contact::query()->where('lead_id', $lead->id)
                ->update(['societe_id' => $societe->id, 'lead_id' => null]);

            // Les activités suivent (§73 : la frise de la société commencerait
            // au jour de la conversion si on les oubliait).
            $activites = Activite::query()->where('lead_id', $lead->id)
                ->update(['societe_id' => $societe->id, 'lead_id' => null]);

            // ── Le lead porte désormais sa marque de conversion (§31). ──
            $statutConverti = StatutLead::query()->where('categorie', 'Converti')
                ->orderBy('ordre')->value('id') ?? $lead->statut_id;

            $lead->forceFill([
                'societe_id' => $societe->id,
                'converti_le' => now(),
                'converti_par' => $auteur->id,
                'statut_id' => $statutConverti,
                'modifie_par' => $auteur->id,
            ])->save();

            return ['societe' => $societe, 'contacts' => $contacts, 'activites' => $activites];
        });
    }

    private function creerDepuisLead(Lead $lead, User $auteur): Societe
    {
        // La raison sociale est obligatoire côté société : un lead qui n'était
        // qu'un contact prend le nom de ce contact, faute de mieux.
        $premier = $lead->contacts()->where('actif', true)
            ->orderByDesc('principal')->orderBy('created_at')->first();
        $nomContact = $premier ? trim(($premier->prenom ?? '').' '.$premier->nom) : null;

        return Societe::create([
            'raison_sociale' => $lead->raison_sociale ?: ($nomContact ?: $lead->numero),
            'ice' => $lead->ice,
            'rc' => $lead->rc,
            'secteur_id' => $lead->secteur_id,
            'activite_entreprise_id' => $lead->activite_entreprise_id,
            'effectif' => $lead->effectif,
            'ca_estime' => $lead->ca_estime,
            'adresse' => $lead->adresse,
            'ville_id' => $lead->ville_id,
            'region_id' => $lead->region_id,
            'pays_id' => $lead->pays_id,
            'site_web' => $lead->site_web,
            'etat' => 'Prospect',
            'proprietaire_id' => $lead->proprietaire_id,
            'equipe_id' => $lead->equipe_id,
            'source_id' => $lead->source_id,
            'campagne_id' => $lead->campagne_id,
            'prochaine_action_le' => $lead->prochaine_action_le,
            'derniere_activite_le' => $lead->derniere_activite_le,
            'commentaire' => $lead->commentaire,
            'cree_par' => $auteur->id,
        ]);
    }
}
