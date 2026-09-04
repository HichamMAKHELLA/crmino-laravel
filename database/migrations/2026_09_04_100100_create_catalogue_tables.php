<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catalogue (§28) : Famille -> Gamme -> Produit. Le CODE d'un produit est
 * réservé À VIE — l'unicité N'EST PAS filtrée sur actif : un code retiré reste
 * pris, exprès, pour qu'il ne désigne jamais deux produits dans l'historique
 * signé. C'est ce qui rend la réactivation du §47 nécessaire. Fidèle à
 * ref.FamilleProduit / ref.GammeProduit / app.Produit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('familles_produit', function (Blueprint $t) {
            $t->id();
            $t->string('code', 32)->unique();
            $t->string('libelle', 120);
            $t->integer('ordre')->default(0);
            $t->boolean('actif')->default(true);
            $t->boolean('systeme')->default(false);
        });

        Schema::create('gammes_produit', function (Blueprint $t) {
            $t->id();
            $t->foreignId('famille_id')->constrained('familles_produit');
            $t->string('code', 32)->unique();
            $t->string('libelle', 120);
            $t->integer('ordre')->default(0);
            $t->boolean('actif')->default(true);
            $t->boolean('systeme')->default(false);
        });

        Schema::create('produits', function (Blueprint $t) {
            $t->id();
            $t->foreignId('gamme_id')->constrained('gammes_produit');
            $t->string('code', 32)->unique(); // UQ_Produit_Code : réservé à vie, non filtré sur actif
            $t->string('designation', 200);
            $t->decimal('prix_catalogue', 19, 4)->nullable();
            $t->string('unite', 20)->nullable();
            $t->string('type', 16);
            $t->integer('ordre')->default(0);
            $t->boolean('actif')->default(true);
            $t->foreignId('cree_par')->nullable()->constrained('users');
            $t->foreignId('modifie_par')->nullable()->constrained('users');
            $t->timestamps();
        });

        DB::statement("ALTER TABLE produits ADD CONSTRAINT ck_produit_type
            CHECK (type IN ('Licence','Abonnement','Prestation','Formation','Materiel'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('produits');
        Schema::dropIfExists('gammes_produit');
        Schema::dropIfExists('familles_produit');
    }
};
