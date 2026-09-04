<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tâches (§20, §21). Une tâche s'ASSIGNE (assignee_id) — elle notifie quelqu'un
 * (§36). Peut se rattacher à un lead OU une société (jamais les deux), ou à
 * aucun (tâche autonome). Fidèle à app.Tache.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taches', function (Blueprint $t) {
            $t->id();
            $t->foreignId('type_id')->constrained('types_tache');
            $t->string('titre', 200);
            $t->string('description', 2000)->nullable();

            $t->foreignId('lead_id')->nullable()->constrained('leads');
            $t->unsignedBigInteger('societe_id')->nullable();
            $t->foreignId('contact_id')->nullable()->constrained('contacts');
            $t->foreignId('opportunite_id')->nullable()->constrained('opportunites');

            $t->foreignId('assignee_id')->constrained('users');
            $t->dateTime('echeance_le')->nullable();
            $t->foreignId('priorite_id')->constrained('priorites_tache');
            $t->string('statut', 12)->default('AFaire');
            $t->dateTime('terminee_le')->nullable();

            $t->boolean('actif')->default(true);
            $t->foreignId('cree_par')->nullable()->constrained('users');
            $t->foreignId('modifie_par')->nullable()->constrained('users');
            $t->timestamps();

            $t->index(['assignee_id', 'statut', 'echeance_le']); // « Mes tâches »
        });

        Schema::table('taches', function (Blueprint $t) {
            $t->foreign('societe_id')->references('id')->on('societes');
        });

        DB::statement("ALTER TABLE taches ADD CONSTRAINT ck_tache_statut
            CHECK (statut IN ('AFaire','EnCours','Terminee','Annulee'))");
        // Un lead OU une société, jamais les deux (mais aucun est permis).
        DB::statement('ALTER TABLE taches ADD CONSTRAINT ck_tache_rattachement
            CHECK (NOT (lead_id IS NOT NULL AND societe_id IS NOT NULL))');
        // Terminer pose la date d'achèvement.
        DB::statement("ALTER TABLE taches ADD CONSTRAINT ck_tache_terminee
            CHECK (statut <> 'Terminee' OR terminee_le IS NOT NULL)");
    }

    public function down(): void
    {
        Schema::dropIfExists('taches');
    }
};
