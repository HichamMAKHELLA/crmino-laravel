<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les référentiels AUTO-CONTENUS (port de ref.* du .NET). Forme commune :
 * code (unique, réservé à vie), libelle, ordre, actif, systeme. `systeme = true`
 * protège une valeur socle du retrait (RG-REF-001). Certains ajoutent une
 * colonne de COMPORTEMENT (probabilité, catégorie, niveau…) — c'est ce qui les
 * distingue d'un simple vocabulaire.
 */
return new class extends Migration
{
    /** Les trois colonnes de queue, communes à tout référentiel. */
    private function commun(Blueprint $t): void
    {
        $t->integer('ordre')->default(0);
        $t->boolean('actif')->default(true);
        $t->boolean('systeme')->default(false);
    }

    public function up(): void
    {
        Schema::create('sources', function (Blueprint $t) {
            $t->id();
            $t->string('code', 32)->unique();
            $t->string('libelle', 120);
            $this->commun($t);
        });

        Schema::create('secteurs', function (Blueprint $t) {
            $t->id();
            $t->string('code', 32)->unique();
            $t->string('libelle', 120);
            $this->commun($t);
        });

        Schema::create('types_tache', function (Blueprint $t) {
            $t->id();
            $t->string('code', 32)->unique();
            $t->string('libelle', 120);
            $this->commun($t);
        });

        Schema::create('priorites_tache', function (Blueprint $t) {
            $t->id();
            $t->string('code', 32)->unique();
            $t->string('libelle', 120);
            $t->unsignedTinyInteger('niveau')->default(2); // le TRI se fait dessus, jamais sur le libellé
            $this->commun($t);
        });

        Schema::create('types_activite', function (Blueprint $t) {
            $t->id();
            $t->string('code', 32)->unique();
            $t->string('libelle', 120);
            // La pastille/le décompte du §39 se lisent sur la CATÉGORIE (stable),
            // jamais sur le libellé (administrable).
            $t->enum('categorie', ['Appel', 'Rdv', 'Demonstration', 'Email', 'Autre']);
            $this->commun($t);
        });

        Schema::create('statuts_lead', function (Blueprint $t) {
            $t->id();
            $t->string('code', 32)->unique();
            $t->string('libelle', 120);
            $t->enum('categorie', ['Ouvert', 'Converti', 'Perdu', 'Doublon']);
            $this->commun($t);
        });

        Schema::create('etapes_pipeline', function (Blueprint $t) {
            $t->id();
            $t->string('code', 32)->unique();
            $t->string('libelle', 120);
            $t->unsignedTinyInteger('probabilite')->default(0); // 0..100
            $t->enum('categorie', ['Ouverte', 'Gagnee', 'Perdue']);
            $this->commun($t);
        });

        Schema::create('motifs_perte', function (Blueprint $t) {
            $t->id();
            $t->string('code', 32)->unique();
            $t->string('libelle', 120);
            $t->boolean('commentaire_obligatoire')->default(false); // ref.MotifPerte : qui doit s'expliquer
            $this->commun($t);
        });
    }

    public function down(): void
    {
        foreach (['motifs_perte', 'etapes_pipeline', 'statuts_lead', 'types_activite', 'priorites_tache', 'types_tache', 'secteurs', 'sources'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
