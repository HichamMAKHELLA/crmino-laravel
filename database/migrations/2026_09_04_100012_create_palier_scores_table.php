<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Paliers de score (§15). Un référentiel PORTEUR DE COMPORTEMENT : ses bornes
 * décident du palier (Froid / Tiède / Chaud / Très chaud). Administrable par
 * migration seulement — une saisie libre y casserait le classement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('palier_scores', function (Blueprint $t) {
            $t->id();
            $t->string('code', 32)->unique();
            $t->string('libelle', 120);
            $t->unsignedTinyInteger('borne_min');
            $t->unsignedTinyInteger('borne_max');
            $t->char('couleur', 7)->nullable();
            $t->integer('ordre')->default(0);
            $t->boolean('actif')->default(true);
            $t->boolean('systeme')->default(false);
        });

        DB::statement('ALTER TABLE palier_scores ADD CONSTRAINT ck_palier_bornes
            CHECK (borne_min <= borne_max AND borne_max <= 100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('palier_scores');
    }
};
