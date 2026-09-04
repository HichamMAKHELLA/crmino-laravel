<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Leads (§13). Entité de prospection, propriétaire d'un périmètre commercial
 * (proprietaire_id / equipe_id). Fidèle à app.Lead du .NET. Les FK vers des
 * tables non encore créées — société (§6), campagne (§8), activité
 * d'entreprise — restent des colonnes nullables sans contrainte, complétées
 * à leur phase.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $t) {
            $t->id();
            $t->string('numero', 20)->unique();

            // Identité (RG-LEA-001 : raison sociale OU contact, au niveau règle).
            $t->string('raison_sociale', 200)->nullable();
            $t->string('raison_sociale_normalisee', 200)->nullable(); // §41, calculée
            $t->string('ice', 15)->nullable();
            $t->string('rc', 32)->nullable();

            // Qualification d'entreprise.
            $t->foreignId('secteur_id')->nullable()->constrained('secteurs');
            $t->unsignedBigInteger('activite_entreprise_id')->nullable(); // FK différée
            $t->integer('effectif')->nullable();
            $t->decimal('ca_estime', 19, 4)->nullable();

            // Localisation (§41 : rapprochement par ville).
            $t->string('adresse', 300)->nullable();
            $t->foreignId('ville_id')->nullable()->constrained('villes');
            $t->foreignId('region_id')->nullable()->constrained('regions');
            $t->foreignId('pays_id')->nullable()->constrained('pays');
            $t->string('site_web', 256)->nullable();
            $t->string('domaine_web', 128)->nullable(); // §41, calculée

            // Origine et statut (obligatoires).
            $t->foreignId('source_id')->constrained('sources');
            $t->unsignedBigInteger('campagne_id')->nullable(); // FK différée (§8)
            $t->foreignId('statut_id')->constrained('statuts_lead');
            $t->unsignedTinyInteger('score')->nullable();

            // Périmètre commercial (RG-HAB-001).
            $t->foreignId('proprietaire_id')->nullable()->constrained('users');
            $t->foreignId('equipe_id')->nullable()->constrained('equipes');
            $t->dateTime('affecte_le')->nullable();

            // Suivi (§20, §35).
            $t->dateTime('prochaine_action_le')->nullable();
            $t->string('prochaine_action_libelle', 200)->nullable();
            $t->dateTime('derniere_activite_le')->nullable();

            // Conversion (§31) — FK société différée.
            $t->unsignedBigInteger('societe_id')->nullable();
            $t->dateTime('converti_le')->nullable();
            $t->foreignId('converti_par')->nullable()->constrained('users');

            $t->string('commentaire', 2000)->nullable();

            // Suppression logique (§47) + traçabilité.
            $t->boolean('actif')->default(true);
            $t->foreignId('cree_par')->nullable()->constrained('users');
            $t->foreignId('modifie_par')->nullable()->constrained('users');
            $t->timestamps();

            $t->index(['proprietaire_id', 'created_at']); // chemin d'accès du périmètre
            $t->index('raison_sociale_normalisee'); // §41
        });

        // CK_Lead_Score et CK_Lead_Conversion (§30/§31), portés en base.
        DB::statement('ALTER TABLE leads ADD CONSTRAINT ck_lead_score CHECK (score IS NULL OR score BETWEEN 0 AND 100)');
        DB::statement('ALTER TABLE leads ADD CONSTRAINT ck_lead_conversion CHECK (
            (societe_id IS NULL AND converti_le IS NULL AND converti_par IS NULL)
            OR (societe_id IS NOT NULL AND converti_le IS NOT NULL AND converti_par IS NOT NULL))');
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
