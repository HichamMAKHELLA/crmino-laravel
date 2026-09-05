<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Les référentiels auto-contenus (port du seed .NET). Valeurs socles marquées
 * `systeme = true` (RG-REF-001). Idempotent : upsert sur le code.
 */
class ReferentielsSeeder extends Seeder
{
    public function run(): void
    {
        // [code, libellé, ordre, systeme]
        $this->commun('sources', [
            ['SITE_WEB', 'Site Web IMRASOFT', 10, true],
            ['FORM_CONTACT', 'Formulaire de contact', 20, true],
            ['APPEL_ENTRANT', 'Appel entrant', 30, true],
            ['GOOGLE', 'Google', 40, false],
            ['LINKEDIN', 'LinkedIn', 50, false],
            ['FACEBOOK', 'Facebook', 60, false],
            ['INSTAGRAM', 'Instagram', 70, false],
            ['WHATSAPP', 'WhatsApp', 80, false],
            ['SALON', 'Salon', 90, false],
            ['EVENEMENT', 'Événement', 100, false],
            ['RECOMMANDATION', 'Recommandation', 110, false],
            ['PARTENAIRE', 'Partenaire', 120, false],
            ['BNI', 'BNI', 130, false],
            ['SAGE', 'Sage', 140, false],
            ['CAMPAGNE_EMAIL', 'Campagne e-mail', 150, false],
            ['CAMPAGNE_TEL', 'Campagne téléphonique', 160, false],
            ['TERRAIN', 'Prospection terrain', 170, false],
            ['BASE_EXTERNE', 'Base de données externe', 180, false],
            ['ANCIEN_CLIENT', 'Ancien client', 190, false],
            ['IMPORT', 'Import de fichier', 200, true],
            ['AUTRE', 'Autre', 999, true],
        ]);

        $this->commun('secteurs', [
            ['INDUSTRIE', 'Industrie', 10, false],
            ['AGROALIM', 'Agroalimentaire', 20, false],
            ['DISTRIBUTION', 'Distribution et négoce', 30, false],
            ['BTP', 'BTP et construction', 40, false],
            ['TRANSPORT', 'Transport et logistique', 50, false],
            ['SERVICES', 'Services aux entreprises', 60, false],
            ['SANTE', 'Santé', 70, false],
            ['EDUCATION', 'Éducation et formation', 80, false],
            ['IMMOBILIER', 'Immobilier', 90, false],
            ['TOURISME', 'Tourisme et hôtellerie', 100, false],
            ['TEXTILE', 'Textile et cuir', 110, false],
            ['AUTOMOBILE', 'Automobile', 120, false],
            ['ENERGIE', 'Énergie et environnement', 130, false],
            ['FINANCE', 'Banque et assurance', 140, false],
            ['IT', 'Informatique et télécoms', 150, false],
            ['ASSOCIATIF', 'Associatif et public', 160, false],
            ['AUTRE', 'Autre', 999, false],
        ]);

        $this->commun('types_tache', [
            ['APPELER', 'Appeler', 10, true],
            ['EMAIL', 'Envoyer un e-mail', 20, true],
            ['DOCUMENTATION', 'Envoyer documentation', 30, false],
            ['OFFRE', 'Préparer une offre', 40, false],
            ['DEMO', 'Préparer démonstration', 50, false],
            ['RELANCER', 'Relancer', 60, true],
            ['RDV', 'Organiser un rendez-vous', 70, false],
            ['INFORMATION', 'Récupérer information', 80, false],
            ['AUTRE', 'Autre', 999, true],
        ]);

        // priorites_tache : [code, libellé, niveau, ordre, systeme]
        $this->upsert('priorites_tache', ['code', 'libelle', 'niveau', 'ordre', 'systeme'], [
            ['BASSE', 'Basse', 1, 10, true],
            ['NORMALE', 'Normale', 2, 20, true],
            ['HAUTE', 'Haute', 3, 30, true],
            ['URGENTE', 'Urgente', 4, 40, true],
        ]);

        // types_activite : [code, libellé, categorie, ordre, systeme]
        $this->upsert('types_activite', ['code', 'libelle', 'categorie', 'ordre', 'systeme'], [
            ['APPEL', 'Appel téléphonique', 'Appel', 10, true],
            ['EMAIL', 'E-mail', 'Email', 20, true],
            ['WHATSAPP', 'WhatsApp', 'Autre', 30, false],
            ['REUNION', 'Réunion', 'Rdv', 40, true],
            ['VISIO', 'Visioconférence', 'Rdv', 50, false],
            ['RDV_CLIENT', 'Rendez-vous client', 'Rdv', 60, true],
            ['DEMONSTRATION', 'Démonstration', 'Demonstration', 70, true],
            ['VISITE', 'Visite', 'Rdv', 80, false],
            ['RELANCE', 'Relance', 'Autre', 90, false],
            ['NOTE', 'Note', 'Autre', 100, false],
            ['AUTRE', 'Autre', 'Autre', 999, true],
        ]);

        // types_document : [code, libellé, ordre, systeme]
        // §15 : DEVIS et PROPOSITION nourrissent le score -> systeme = 1.
        $this->upsert('types_document', ['code', 'libelle', 'ordre', 'systeme'], [
            ['DEVIS', 'Devis', 10, true],
            ['PROPOSITION', 'Proposition commerciale', 20, true],
            ['BON_COMMANDE', 'Bon de commande', 30, false],
            ['CONTRAT', 'Contrat', 40, false],
            ['CAHIER_CHARGES', 'Cahier des charges', 50, false],
            ['AUTRE', 'Autre', 999, true],
        ]);

        // parametres_alerte §35 : [code, libellé, délai (j), ordre, systeme].
        // Les huit codes sont nommés par le calcul — ni création ni suppression.
        $this->upsert('parametres_alerte', ['code', 'libelle', 'delai_jours', 'ordre', 'systeme'], [
            ['LEAD_SANS_ACTIVITE', 'Prospect sans activité depuis X jours', 7, 10, true],
            ['OPP_SANS_ACTIVITE', 'Opportunité sans activité depuis X jours', 10, 20, true],
            ['DEVIS_SANS_RELANCE', 'Devis sans relance depuis X jours', 3, 30, true],
            ['OPP_DATE_DEPASSEE', 'Opportunité dépassant sa date de clôture', 0, 40, true],
            ['TACHE_EN_RETARD', 'Tâche en retard', 0, 50, true],
            ['CONTRAT_ECHEANCE', 'Contrat arrivant à échéance dans X jours', 60, 60, true],
            ['OPP_SANS_ACTION', 'Opportunité importante sans prochaine action', 5, 70, true],
            ['RELANCE_SANS_REPONSE', 'Relance sans réponse depuis X jours', 7, 80, true],
        ]);
        // OPP_SANS_ACTION lit un SEUIL de montant (§35).
        DB::table('parametres_alerte')->where('code', 'OPP_SANS_ACTION')->update(['seuil_montant' => 100000]);

        // criteres_score §15 : [code, libellé, poids, ordre, systeme] — total = 100.
        $this->upsert('criteres_score', ['code', 'libelle', 'poids', 'ordre', 'systeme'], [
            ['BUDGET', 'Budget identifié', 15, 15, true],
            ['BESOIN', 'Besoin exprimé', 15, 10, true],
            ['DECISIONNAIRE', 'Décisionnaire identifié', 15, 20, true],
            ['PROJET', 'Projet défini', 10, 25, true],
            ['DELAI', 'Délai de décision connu', 10, 35, true],
            ['RDV', 'Rendez-vous effectué', 15, 40, true],
            ['DEVIS', 'Devis ou proposition déposé', 15, 45, true],
            ['INTERACTION', 'Interaction récente', 5, 50, true],
        ]);

        // tranches_budget §15 : [code, libellé, ordre, systeme]
        $this->upsert('tranches_budget', ['code', 'libelle', 'ordre', 'systeme'], [
            ['MOINS_50K', 'Moins de 50 000 MAD', 10, false],
            ['50K_200K', '50 000 à 200 000 MAD', 20, false],
            ['200K_500K', '200 000 à 500 000 MAD', 30, false],
            ['PLUS_500K', 'Plus de 500 000 MAD', 40, false],
        ]);

        // horizons_decision §15 : [code, libellé, ordre, systeme]
        $this->upsert('horizons_decision', ['code', 'libelle', 'ordre', 'systeme'], [
            ['IMMEDIAT', 'Immédiat (moins de 3 mois)', 10, false],
            ['COURT', 'Court terme (3 à 6 mois)', 20, false],
            ['MOYEN', 'Moyen terme (6 à 12 mois)', 30, false],
            ['LONG', 'Long terme (plus de 12 mois)', 40, false],
        ]);

        // besoins §14 : [code, libellé, ordre, systeme]
        $this->upsert('besoins', ['code', 'libelle', 'ordre', 'systeme'], [
            ['COMPTA', 'Comptabilité', 10, false],
            ['GESTION_CO', 'Gestion commerciale', 20, false],
            ['PAIE', 'Paie', 30, false],
            ['IMMO', 'Immobilisations', 40, false],
            ['CRM', 'CRM', 50, false],
        ]);

        // statuts_lead : [code, libellé, categorie, ordre, systeme]
        $this->upsert('statuts_lead', ['code', 'libelle', 'categorie', 'ordre', 'systeme'], [
            ['NOUVEAU', 'Nouveau', 'Ouvert', 10, true],
            ['A_CONTACTER', 'À contacter', 'Ouvert', 20, false],
            ['TENTATIVE', 'Tentative de contact', 'Ouvert', 30, false],
            ['CONTACTE', 'Contacté', 'Ouvert', 40, false],
            ['A_QUALIFIER', 'À qualifier', 'Ouvert', 50, false],
            ['QUALIFIE', 'Qualifié', 'Ouvert', 60, false],
            ['A_RAPPELER', 'À rappeler', 'Ouvert', 70, false],
            ['RDV_PROGRAMME', 'RDV programmé', 'Ouvert', 80, false],
            ['PROJET_FUTUR', 'Projet futur', 'Ouvert', 90, false],
            ['CONVERTI', 'Opportunité créée', 'Converti', 100, true],
            ['NON_INTERESSE', 'Non intéressé', 'Perdu', 110, false],
            ['INJOIGNABLE', 'Injoignable', 'Perdu', 120, false],
            ['ABANDONNE', 'Abandonné', 'Perdu', 130, false],
            ['DOUBLON', 'Doublon', 'Doublon', 140, true],
        ]);

        // etapes_pipeline : [code, libellé, probabilite, categorie, ordre, systeme]
        $this->upsert('etapes_pipeline', ['code', 'libelle', 'probabilite', 'categorie', 'ordre', 'systeme'], [
            ['QUALIFICATION', 'Qualification', 10, 'Ouverte', 10, true],
            ['DECOUVERTE', 'Découverte', 20, 'Ouverte', 20, false],
            ['RDV', 'RDV planifié', 30, 'Ouverte', 30, false],
            ['DEMONSTRATION', 'Démonstration', 40, 'Ouverte', 40, false],
            ['PROPOSITION', 'Proposition', 60, 'Ouverte', 50, false],
            ['NEGOCIATION', 'Négociation', 75, 'Ouverte', 60, false],
            ['DECISION', 'Décision', 90, 'Ouverte', 70, false],
            ['GAGNE', 'Gagné', 100, 'Gagnee', 80, true],
            ['PERDU', 'Perdu', 0, 'Perdue', 90, true],
        ]);

        // motifs_perte : [code, libellé, commentaire_obligatoire, ordre, systeme]
        $this->upsert('motifs_perte', ['code', 'libelle', 'commentaire_obligatoire', 'ordre', 'systeme'], [
            ['PRIX', 'Prix', false, 10, true],
            ['CONCURRENT', 'Concurrent', true, 20, true],
            ['BUDGET', 'Budget insuffisant', false, 30, false],
            ['PROJET_ANNULE', 'Projet annulé', false, 40, false],
            ['DECISION_REPORTEE', 'Décision reportée', true, 50, false],
            ['NON_JOIGNABLE', 'Client non joignable', false, 60, false],
            ['NON_ADAPTEE', 'Solution non adaptée', true, 70, false],
            ['PROJET_INTERNE', 'Projet interne', false, 80, false],
            ['AUCUN_BUDGET', 'Aucun budget', false, 90, false],
            ['DELAI', 'Délai', false, 100, false],
            ['MAUVAISE_QUALIF', 'Mauvaise qualification', true, 110, false],
            ['AUTRE', 'Autre', true, 999, true],
        ]);

        // Paliers de score (§15) — bornes porteuses de comportement.
        $this->upsert('palier_scores',
            ['code', 'libelle', 'borne_min', 'borne_max', 'couleur', 'ordre', 'systeme'], [
                ['FROID', 'Froid', 0, 30, '#666666', 10, true],
                ['TIEDE', 'Tiède', 31, 60, '#E0A020', 20, true],
                ['CHAUD', 'Chaud', 61, 80, '#007AA8', 30, true],
                ['TRES_CHAUD', 'Très chaud', 81, 100, '#D64545', 40, true],
            ]);

        // Types de campagne (§12) — forme commune.
        $this->commun('types_campagne', [
            ['EMAILING', 'Emailing', 10, true],
            ['TELEPHONE', 'Campagne téléphonique', 20, true],
            ['SALON', 'Salon', 30, false],
            ['WEBINAIRE', 'Webinaire', 40, false],
            ['RESEAUX', 'Réseaux sociaux', 50, false],
            ['PARTENAIRE', 'Co-marketing partenaire', 60, false],
            ['TERRAIN', 'Prospection terrain', 70, false],
            ['AUTRE', 'Autre', 999, true],
        ]);
    }

    /** Référentiel de forme commune : [code, libellé, ordre, systeme]. */
    private function commun(string $table, array $lignes): void
    {
        $this->upsert($table, ['code', 'libelle', 'ordre', 'systeme'], $lignes);
    }

    private function upsert(string $table, array $colonnes, array $lignes): void
    {
        $rows = array_map(fn (array $l) => array_combine($colonnes, $l), $lignes);
        DB::table($table)->upsert($rows, ['code'], array_values(array_diff($colonnes, ['code'])));
    }
}
