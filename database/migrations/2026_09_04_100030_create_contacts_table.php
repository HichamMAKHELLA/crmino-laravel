<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Contacts (§13). Rattachés à un Lead OU à une Société, jamais les deux, jamais
 * aucun (CK_Contact_Rattachement). La conversion (§31) BASCULE ce rattachement
 * au lieu de recopier, ce qui préserve l'historique. Fidèle à app.Contact.
 * La FK vers `societes` est différée (§6).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('lead_id')->nullable()->constrained('leads');
            $t->unsignedBigInteger('societe_id')->nullable(); // FK différée (§6)

            $t->string('civilite', 8)->nullable();
            $t->string('nom', 100);
            $t->string('prenom', 100)->nullable();
            $t->foreignId('fonction_id')->nullable()->constrained('fonctions_contact');
            $t->string('fonction_libre', 120)->nullable();

            $t->string('telephone', 32)->nullable();
            $t->string('gsm', 32)->nullable();
            $t->string('telephone_normalise', 20)->nullable(); // §41, calculée
            $t->string('gsm_normalise', 20)->nullable();       // §41, calculée
            $t->string('whatsapp', 32)->nullable();
            $t->string('email', 256)->nullable();
            $t->string('linkedin', 256)->nullable();

            $t->boolean('principal')->default(false);
            $t->boolean('decisionnaire')->default(false);
            $t->string('commentaire', 1000)->nullable();

            $t->boolean('actif')->default(true);
            $t->foreignId('cree_par')->nullable()->constrained('users');
            $t->foreignId('modifie_par')->nullable()->constrained('users');
            $t->timestamps();

            $t->index('telephone_normalise'); // §41
            $t->index('gsm_normalise');
            $t->index('email');
        });

        // CK_Contact_Rattachement : exactement un rattachement.
        DB::statement('ALTER TABLE contacts ADD CONSTRAINT ck_contact_rattachement CHECK (
            (lead_id IS NOT NULL AND societe_id IS NULL)
            OR (lead_id IS NULL AND societe_id IS NOT NULL))');
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
