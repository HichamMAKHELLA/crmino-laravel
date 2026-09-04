<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Opportunités (§25). Une affaire naît sur une SOCIÉTÉ (societe_id NOT NULL) —
 * jamais sur un lead : c'est la conversion (§31) qui produit la société. Fidèle
 * à app.Opportunite.
 *
 * RG-OPP-001 : le montant pondéré est une COLONNE GÉNÉRÉE (montant × probabilité
 * / 100), jamais écrite par l'application — le schéma la tient, aucun chemin ne
 * peut l'oublier (garantie ADR-001).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunites', function (Blueprint $t) {
            $t->id();
            $t->string('numero', 20)->unique();
            $t->string('intitule', 200);

            $t->foreignId('societe_id')->constrained('societes');
            $t->foreignId('lead_id')->nullable()->constrained('leads');
            $t->foreignId('contact_principal_id')->nullable()->constrained('contacts');
            $t->foreignId('proprietaire_id')->constrained('users');
            $t->foreignId('equipe_id')->nullable()->constrained('equipes');
            $t->foreignId('source_id')->nullable()->constrained('sources');
            $t->unsignedBigInteger('campagne_id')->nullable(); // FK différée (§8)
            $t->foreignId('etape_id')->constrained('etapes_pipeline');

            $t->string('statut', 10)->default('Ouverte');
            $t->unsignedTinyInteger('probabilite')->default(0);
            $t->decimal('montant_ht', 19, 4)->default(0);
            // RG-OPP-001 : pondéré = montant × probabilité / 100, tenu par le schéma.
            $t->decimal('montant_pondere', 19, 4)->storedAs('montant_ht * probabilite / 100');

            $t->date('date_cloture_estimee')->nullable();
            $t->date('date_cloture')->nullable();
            $t->foreignId('motif_perte_id')->nullable()->constrained('motifs_perte');
            $t->string('commentaire_perte', 2000)->nullable();

            $t->dateTime('prochaine_action_le')->nullable();
            $t->string('prochaine_action_libelle', 200)->nullable();
            $t->dateTime('derniere_activite_le')->nullable();
            $t->string('commentaire', 2000)->nullable();

            $t->boolean('actif')->default(true);
            $t->foreignId('cree_par')->nullable()->constrained('users');
            $t->foreignId('modifie_par')->nullable()->constrained('users');
            $t->timestamps();

            $t->index(['proprietaire_id', 'created_at']); // périmètre
            $t->index(['societe_id', 'statut']);
        });

        DB::statement("ALTER TABLE opportunites ADD CONSTRAINT ck_opp_statut
            CHECK (statut IN ('Ouverte','Gagnee','Perdue'))");
        DB::statement('ALTER TABLE opportunites ADD CONSTRAINT ck_opp_proba
            CHECK (probabilite BETWEEN 0 AND 100)');
        // RG-OPP-002 : une affaire perdue exige un motif.
        DB::statement("ALTER TABLE opportunites ADD CONSTRAINT ck_opp_motif
            CHECK (statut <> 'Perdue' OR motif_perte_id IS NOT NULL)");
        // Une affaire close porte forcément sa date de clôture.
        DB::statement("ALTER TABLE opportunites ADD CONSTRAINT ck_opp_cloture
            CHECK (statut = 'Ouverte' OR date_cloture IS NOT NULL)");
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunites');
    }
};
