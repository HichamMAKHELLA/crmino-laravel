<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Campagnes (§12) + leur référentiel de type. Le ROI se calcule à partir des
 * leads et affaires RATTACHÉS (campagne_id). Le produit lié est différé (§28).
 * Fidèle à app.Campagne / ref.TypeCampagne.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('types_campagne', function (Blueprint $t) {
            $t->id();
            $t->string('code', 32)->unique();
            $t->string('libelle', 120);
            $t->integer('ordre')->default(0);
            $t->boolean('actif')->default(true);
            $t->boolean('systeme')->default(false);
        });

        Schema::create('campagnes', function (Blueprint $t) {
            $t->id();
            $t->string('numero', 20)->unique();
            $t->string('nom', 200);
            $t->foreignId('type_campagne_id')->constrained('types_campagne');
            $t->date('date_debut');
            $t->date('date_fin')->nullable();
            $t->foreignId('responsable_id')->constrained('users');
            $t->decimal('budget', 19, 4)->nullable();
            $t->string('cible', 500)->nullable();
            $t->unsignedBigInteger('produit_id')->nullable(); // FK différée (§28)
            $t->string('statut', 12)->default('Planifiee');
            $t->string('commentaire', 2000)->nullable();
            $t->boolean('actif')->default(true);
            $t->foreignId('cree_par')->nullable()->constrained('users');
            $t->foreignId('modifie_par')->nullable()->constrained('users');
            $t->timestamps();
        });

        DB::statement("ALTER TABLE campagnes ADD CONSTRAINT ck_campagne_statut
            CHECK (statut IN ('Planifiee','EnCours','Terminee','Annulee'))");
        DB::statement('ALTER TABLE campagnes ADD CONSTRAINT ck_campagne_dates
            CHECK (date_fin IS NULL OR date_fin >= date_debut)');

        // Les rattachements de campagne, posés nullables, trouvent leur cible.
        foreach (['leads', 'societes', 'opportunites'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreign('campagne_id')->references('id')->on('campagnes');
            });
        }
    }

    public function down(): void
    {
        foreach (['leads', 'societes', 'opportunites'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropForeign(['campagne_id']));
        }
        Schema::dropIfExists('campagnes');
        Schema::dropIfExists('types_campagne');
    }
};
