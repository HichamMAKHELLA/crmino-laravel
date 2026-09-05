<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Import CSV / Excel (§42). Fidèle à app.ImportLot et app.ImportLigne.
 *
 * L'assistant est en DEUX temps : analyser (aperçu, aucune écriture) puis
 * exécuter (import ATOMIQUE). Un lot ÉCHOUÉ laisse quand même une trace — sans
 * la colonne statut, un lot échoué se lirait comme un import réussi sur un
 * fichier vide. Chaque ligne garde son CONTENU BRUT pour expliquer un rejet plus
 * tard, quand le fichier d'origine n'est plus là.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_lots', function (Blueprint $t) {
            $t->id();
            $t->string('nom_fichier', 255);
            $t->string('type', 16); // Lead / Societe / Contact
            $t->integer('lignes_total')->default(0);
            $t->integer('lignes_importees')->default(0);
            $t->integer('lignes_rejetees')->default(0);
            $t->integer('lignes_doublons')->default(0);
            $t->string('statut', 12)->default('EnCours');
            $t->unsignedBigInteger('campagne_id')->nullable(); // FK différée (§8)
            $t->foreignId('source_id')->nullable()->constrained('sources');
            $t->dateTime('lance_le')->useCurrent();
            $t->foreignId('lance_par')->constrained('users');
            $t->dateTime('termine_le')->nullable();
        });

        DB::statement("ALTER TABLE import_lots ADD CONSTRAINT ck_importlot_type
            CHECK (type IN ('Lead','Societe','Contact'))");
        // Annule est réservé (l'import est SYNCHRONE — rien n'attend, §42).
        DB::statement("ALTER TABLE import_lots ADD CONSTRAINT ck_importlot_statut
            CHECK (statut IN ('EnCours','Termine','Echoue','Annule'))");

        Schema::create('import_lignes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('import_lot_id')->constrained('import_lots')->cascadeOnDelete();
            $t->integer('numero_ligne');
            $t->text('contenu')->nullable(); // le contenu BRUT, pour expliquer un rejet
            $t->string('resultat', 12);
            $t->string('motif', 500)->nullable();
            $t->unsignedBigInteger('cible_id')->nullable();

            $t->index(['import_lot_id', 'numero_ligne']);
        });

        DB::statement("ALTER TABLE import_lignes ADD CONSTRAINT ck_importligne_resultat
            CHECK (resultat IN ('Importee','Rejetee','Doublon'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('import_lignes');
        Schema::dropIfExists('import_lots');
    }
};
