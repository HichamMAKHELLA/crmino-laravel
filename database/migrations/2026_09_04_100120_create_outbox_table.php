<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * File de sortie vers Sage 100 (§48, ÉCART-003). Fidèle à app.Outbox.
 *
 * Le message est déposé dans la MÊME transaction que le passage d'une affaire à
 * « Gagné » : il partage le sort du fait qui l'a produit. Un message déposé hors
 * transaction survivrait à une annulation, et Sage recevrait un « gagné » pour
 * une affaire qui ne l'est pas. Aucun répartiteur en Lot 1 — la phase 3 le
 * videra ; rien ne le nomme encore, mais le point d'extension est réservé.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbox', function (Blueprint $t) {
            $t->id();
            $t->string('type', 64);
            $t->longText('charge');
            $t->string('etat', 12)->default('EnAttente');
            $t->integer('tentatives')->default(0);
            $t->string('derniere_erreur', 2000)->nullable();
            $t->dateTime('cree_le')->useCurrent();
            $t->dateTime('traite_le')->nullable();

            $t->index(['etat', 'cree_le']);
        });

        // §48 : les trois états de la file. « Traite »/« Echoue » sont RÉSERVÉS
        // pour le répartiteur de la phase 3 (valeurs sans producteur en Lot 1).
        DB::statement("ALTER TABLE outbox ADD CONSTRAINT ck_outbox_etat
            CHECK (etat IN ('EnAttente','Traite','Echoue'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox');
    }
};
