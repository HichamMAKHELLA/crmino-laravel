<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Qualification d'un lead (§13). Satellite 1-1 (la clé primaire EST le lead_id),
 * écrit par remplacement — sa création est celle du lead, d'où l'absence de
 * created_at. Situation informatique et Sage. Fidèle à app.QualificationLead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qualification_leads', function (Blueprint $t) {
            $t->foreignId('lead_id')->primary()->constrained('leads');

            // Situation informatique.
            $t->integer('nb_sites')->nullable();
            $t->integer('nb_agences')->nullable();
            $t->string('logiciel_actuel', 150)->nullable();
            $t->string('erp_actuel', 150)->nullable();
            $t->string('version_actuelle', 50)->nullable();
            $t->integer('nb_utilisateurs')->nullable();
            $t->string('hebergement', 20)->nullable();
            $t->string('base_donnees', 80)->nullable();
            $t->string('prestataire_actuel', 150)->nullable();

            // Situation Sage.
            $t->string('usage_sage', 16)->default('NeSaitPas');
            $t->string('version_sage', 50)->nullable();
            $t->integer('nb_utilisateurs_sage')->nullable();
            $t->string('revendeur_actuel', 150)->nullable();
            $t->boolean('contrat_sage')->nullable();
            $t->date('date_renouvellement_sage')->nullable();

            $t->dateTime('modifie_le')->nullable();
            $t->foreignId('modifie_par')->nullable()->constrained('users');
        });

        DB::statement("ALTER TABLE qualification_leads ADD CONSTRAINT ck_qualif_hebergement
            CHECK (hebergement IS NULL OR hebergement IN ('Local','Cloud','Hybride','Inconnu'))");
        DB::statement("ALTER TABLE qualification_leads ADD CONSTRAINT ck_qualif_usage_sage
            CHECK (usage_sage IN ('Oui','Non','Ancien','NeSaitPas'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('qualification_leads');
    }
};
