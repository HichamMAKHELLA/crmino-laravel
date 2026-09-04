<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Activités (§19, §73). Rattachées à un Lead OU à une Société
 * (CK_Activite_Rattachement) — la conversion (§31) les bascule. Portent la
 * prochaine action, qui remonte sur la fiche parente (§20). Fidèle à
 * app.Activite. GraphEventId réservé pour Outlook (§23).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activites', function (Blueprint $t) {
            $t->id();
            $t->foreignId('type_id')->constrained('types_activite');
            $t->foreignId('lead_id')->nullable()->constrained('leads');
            $t->unsignedBigInteger('societe_id')->nullable(); // FK ajoutée plus bas
            $t->foreignId('contact_id')->nullable()->constrained('contacts');
            $t->foreignId('opportunite_id')->nullable()->constrained('opportunites');
            $t->foreignId('utilisateur_id')->constrained('users');

            $t->dateTime('debut_le');
            $t->integer('duree_minutes')->nullable();
            $t->string('objet', 200)->nullable();
            $t->string('commentaire', 4000)->nullable();
            $t->string('resultat', 500)->nullable();
            $t->dateTime('prochaine_action_le')->nullable();
            $t->string('prochaine_action_libelle', 200)->nullable();
            $t->string('graph_event_id', 256)->nullable(); // §23, réservé

            $t->boolean('actif')->default(true);
            $t->foreignId('cree_par')->nullable()->constrained('users');
            $t->foreignId('modifie_par')->nullable()->constrained('users');
            $t->timestamps();

            $t->index(['lead_id', 'debut_le']);
            $t->index(['societe_id', 'debut_le']);
        });

        Schema::table('activites', function (Blueprint $t) {
            $t->foreign('societe_id')->references('id')->on('societes');
        });

        // CK_Activite_Rattachement : un lead OU une société, jamais les deux.
        DB::statement('ALTER TABLE activites ADD CONSTRAINT ck_activite_rattachement CHECK (
            (lead_id IS NOT NULL AND societe_id IS NULL)
            OR (lead_id IS NULL AND societe_id IS NOT NULL))');
        // Une activité rattachée à une opportunité l'est via une société.
        DB::statement('ALTER TABLE activites ADD CONSTRAINT ck_activite_opportunite
            CHECK (opportunite_id IS NULL OR societe_id IS NOT NULL)');
    }

    public function down(): void
    {
        Schema::dropIfExists('activites');
    }
};
