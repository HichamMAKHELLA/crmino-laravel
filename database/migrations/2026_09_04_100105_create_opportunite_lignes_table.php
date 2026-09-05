<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lignes d'opportunité (§28). Fidèle à app.OpportuniteLigne.
 *
 * RG-OPP-001 (par analogie, §75/§76) : le montant HT de la ligne est une
 * COLONNE GÉNÉRÉE (quantité × prix unitaire), jamais écrite par l'application —
 * le schéma la tient, aucun chemin ne peut l'oublier (garantie ADR-001). C'est
 * ce total que la ventilation du §76 somme.
 *
 * La désignation est OBLIGATOIRE et LIBRE même quand un produit est renseigné :
 * le libellé négocié diffère parfois du libellé catalogue, et le devis doit
 * refléter ce qui a été dit au client.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunite_lignes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('opportunite_id')->constrained('opportunites')->cascadeOnDelete();
            $t->foreignId('produit_id')->nullable()->constrained('produits');
            $t->string('designation', 200);
            $t->decimal('quantite', 18, 4)->default(1);
            $t->string('unite', 20)->nullable();
            $t->decimal('prix_unitaire', 19, 4)->default(0);
            // §76 : le total ventilé se lit sur cette colonne, jamais recalculé par l'app.
            $t->decimal('montant_ht', 19, 4)->storedAs('quantite * prix_unitaire');
            $t->integer('ordre')->default(0);
            $t->timestamps();

            $t->index(['opportunite_id', 'ordre']);
            $t->index('produit_id');
        });

        // Une quantité nulle ou négative n'a pas de sens sur un devis.
        DB::statement('ALTER TABLE opportunite_lignes ADD CONSTRAINT ck_oppligne_quantite
            CHECK (quantite > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunite_lignes');
    }
};
