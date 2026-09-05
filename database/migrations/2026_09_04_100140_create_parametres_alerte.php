<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Paramétrage des alertes commerciales (§35). Les délais, seuils et la LISTE
 * même des alertes viennent de cette table — jamais du code. Une ligne
 * désactivée ÉTEINT son alerte ; elle ne retombe pas sur un défaut codé en dur.
 * Ni création ni suppression : les huit codes sont nommés par le calcul.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametres_alerte', function (Blueprint $t) {
            $t->id();
            $t->string('code', 32)->unique();
            $t->string('libelle', 150);
            $t->integer('delai_jours')->default(0);
            $t->decimal('seuil_montant', 19, 4)->nullable();
            $t->integer('ordre')->default(0);
            $t->boolean('actif')->default(true);
            $t->boolean('systeme')->default(true);
        });

        // Le délai part dans une addition de dates : borné à 3650 jours (10 ans),
        // sinon une saisie à neuf chiffres ferait lever le calcul.
        DB::statement('ALTER TABLE parametres_alerte ADD CONSTRAINT ck_alerte_delai
            CHECK (delai_jours BETWEEN 0 AND 3650)');
    }

    public function down(): void
    {
        Schema::dropIfExists('parametres_alerte');
    }
};
