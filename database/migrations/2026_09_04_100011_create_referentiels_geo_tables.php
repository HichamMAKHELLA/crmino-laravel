<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Référentiels géographiques (hiérarchie Pays -> Région -> Ville) et fonctions
 * de contact. Le Lead (§13) les référence pour situer un prospect ; le §41 s'en
 * sert pour rapprocher les doublons par ville.
 */
return new class extends Migration
{
    private function commun(Blueprint $t): void
    {
        $t->integer('ordre')->default(0);
        $t->boolean('actif')->default(true);
        $t->boolean('systeme')->default(false);
    }

    public function up(): void
    {
        Schema::create('pays', function (Blueprint $t) {
            $t->id();
            $t->string('code', 32)->unique();
            $t->string('code_iso', 2); // ISO 3166-1 alpha-2
            $t->string('libelle', 120);
            $this->commun($t);
        });

        Schema::create('regions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pays_id')->constrained('pays')->cascadeOnDelete();
            $t->string('code', 32)->unique();
            $t->string('libelle', 120);
            $this->commun($t);
        });

        Schema::create('villes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('region_id')->constrained('regions')->cascadeOnDelete();
            $t->string('code', 32)->unique();
            $t->string('libelle', 120);
            $this->commun($t);
        });

        Schema::create('fonctions_contact', function (Blueprint $t) {
            $t->id();
            $t->string('code', 32)->unique();
            $t->string('libelle', 120);
            $this->commun($t);
        });
    }

    public function down(): void
    {
        foreach (['villes', 'regions', 'pays', 'fonctions_contact'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
