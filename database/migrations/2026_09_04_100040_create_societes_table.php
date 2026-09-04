<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sociétés (§6). Une société naît de la CONVERSION d'un lead (§31), jamais
 * d'une création directe (societe.creer n'a aucune route). Porte l'état de la
 * relation (Prospect / Client / Inactif) et le point d'accroche Sage. Fidèle à
 * app.Societe. FK campagne et activité-entreprise différées.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('societes', function (Blueprint $t) {
            $t->id();
            $t->string('numero', 20)->unique();

            $t->string('raison_sociale', 200);
            $t->string('raison_sociale_normalisee', 200)->nullable(); // §41
            $t->string('ice', 15)->nullable();
            $t->string('rc', 32)->nullable();
            $t->string('identifiant_fiscal', 32)->nullable();

            $t->foreignId('secteur_id')->nullable()->constrained('secteurs');
            $t->unsignedBigInteger('activite_entreprise_id')->nullable(); // FK différée
            $t->integer('effectif')->nullable();
            $t->decimal('ca_estime', 19, 4)->nullable();
            $t->string('adresse', 300)->nullable();
            $t->foreignId('ville_id')->nullable()->constrained('villes');
            $t->foreignId('region_id')->nullable()->constrained('regions');
            $t->foreignId('pays_id')->nullable()->constrained('pays');
            $t->string('site_web', 256)->nullable();
            $t->string('domaine_web', 128)->nullable(); // §41

            $t->string('telephone', 32)->nullable();
            $t->string('telephone_normalise', 20)->nullable(); // §41 (recherche §40)
            $t->string('email', 256)->nullable();

            // État de la RELATION commerciale (§32) — distinct de actif (§47).
            $t->string('etat', 12)->default('Prospect');
            $t->date('devenu_client_le')->nullable();

            $t->foreignId('proprietaire_id')->nullable()->constrained('users');
            $t->foreignId('equipe_id')->nullable()->constrained('equipes');
            $t->foreignId('source_id')->nullable()->constrained('sources');
            $t->unsignedBigInteger('campagne_id')->nullable(); // FK différée (§8)

            $t->string('sage_ct_num', 17)->nullable(); // point d'accroche Sage (§48), pas une saisie

            $t->dateTime('prochaine_action_le')->nullable();
            $t->dateTime('derniere_activite_le')->nullable();
            $t->string('commentaire', 2000)->nullable();

            $t->boolean('actif')->default(true);
            $t->foreignId('cree_par')->nullable()->constrained('users');
            $t->foreignId('modifie_par')->nullable()->constrained('users');
            $t->timestamps();

            $t->index(['proprietaire_id', 'created_at']); // chemin d'accès du périmètre
            $t->index('raison_sociale_normalisee'); // §41
        });

        DB::statement("ALTER TABLE societes ADD CONSTRAINT ck_societe_etat
            CHECK (etat IN ('Prospect','Client','Inactif'))");
        // CK_Societe_Client : un client porte forcément sa date de bascule.
        DB::statement("ALTER TABLE societes ADD CONSTRAINT ck_societe_client
            CHECK (etat <> 'Client' OR devenu_client_le IS NOT NULL)");
    }

    public function down(): void
    {
        Schema::dropIfExists('societes');
    }
};
