<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Documents (§44) et commentaires / notes internes (§45). Fidèle à app.Document
 * et app.Commentaire.
 *
 * Le rattachement est POLYMORPHE (cible_type / cible_id) sur Lead / Societe /
 * Contact / Opportunite : c'est ce qui permet à la conversion §31 de basculer un
 * document ou une note du lead vers la société par un simple changement de
 * cible_type, sans recopie. Pas de FK sur cible_id — le polymorphisme l'interdit.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ref.TypeDocument. §15 : le score lit code IN ('DEVIS','PROPOSITION'),
        // donc ces valeurs sont SOCLE (systeme = 1, irretirables).
        Schema::create('types_document', function (Blueprint $t) {
            $t->id();
            $t->string('code', 32)->unique();
            $t->string('libelle', 120);
            $t->integer('ordre')->default(0);
            $t->boolean('actif')->default(true);
            $t->boolean('systeme')->default(false);
        });

        Schema::create('commentaires', function (Blueprint $t) {
            $t->id();
            $t->string('cible_type', 16);
            $t->unsignedBigInteger('cible_id');
            $t->string('texte', 4000);
            $t->foreignId('auteur_id')->constrained('users');
            $t->boolean('actif')->default(true);
            // §45 : ModifieLe reste NUL tant que rien n'a bougé — un défaut ferait
            // naître chaque note « déjà modifiée ».
            $t->dateTime('modifie_le')->nullable();
            $t->timestamps();

            $t->index(['cible_type', 'cible_id', 'created_at']);
        });

        DB::statement("ALTER TABLE commentaires ADD CONSTRAINT ck_comm_cible_type
            CHECK (cible_type IN ('Lead','Societe','Contact','Opportunite'))");

        Schema::create('documents', function (Blueprint $t) {
            $t->id();
            $t->string('cible_type', 16);
            $t->unsignedBigInteger('cible_id');
            $t->foreignId('type_document_id')->nullable()->constrained('types_document');
            $t->string('nom', 255);            // nom d'origine, affiché
            $t->string('nom_stockage', 64)->unique(); // nom sur disque, sans lien avec l'origine (§58)
            $t->string('chemin_stockage', 512);
            $t->string('content_type', 128)->nullable();
            $t->unsignedBigInteger('taille_octets')->default(0);
            $t->boolean('actif')->default(true);
            $t->dateTime('depose_le')->useCurrent();
            $t->foreignId('depose_par')->constrained('users');
            $t->timestamps();

            $t->index(['cible_type', 'cible_id', 'depose_le']);
        });

        DB::statement("ALTER TABLE documents ADD CONSTRAINT ck_doc_cible_type
            CHECK (cible_type IN ('Lead','Societe','Contact','Opportunite'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
        Schema::dropIfExists('commentaires');
        Schema::dropIfExists('types_document');
    }
};
