<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Journal d'audit (§46). AJOUT SEUL. En production, un compte SQL dédié n'a ni
 * UPDATE ni DELETE sur cette table (l'équivalent du DENY UPDATE, DELETE ON
 * SCHEMA::audit) ; ici l'immuabilité est tenue par le code — le service n'écrit
 * QUE par insertion, et aucune correction ne modifie une ligne existante, elle
 * en ajoute une nouvelle. Fidèle à audit.Journal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_journal', function (Blueprint $t) {
            $t->id();
            $t->timestamp('le')->useCurrent();
            $t->foreignId('utilisateur_id')->nullable()->constrained('users');
            $t->string('action', 32);
            $t->string('entite_type', 32);
            $t->unsignedBigInteger('entite_id');
            $t->string('champ', 64)->nullable();
            $t->text('ancienne_valeur')->nullable();
            $t->text('nouvelle_valeur')->nullable();
            $t->string('adresse_ip', 45)->nullable();
            $t->string('trace_id', 64)->nullable();

            $t->index(['entite_type', 'entite_id']);
            $t->index(['utilisateur_id', 'le']);
            $t->index('action');
        });

        DB::statement("ALTER TABLE audit_journal ADD CONSTRAINT ck_journal_action CHECK (action IN (
            'Creation','Modification','ChangementProprietaire','ChangementStatut',
            'ChangementMontant','SuppressionLogique','Export','ChangementDroits'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_journal');
    }
};
