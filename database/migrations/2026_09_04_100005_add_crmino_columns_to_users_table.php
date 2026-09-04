<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Réconcilie le `users` du kit avec app.Utilisateur du .NET.
 *
 * On GARDE `name`/`email`/`password` (le kit s'en sert pour l'auth de session,
 * ADR-002) et on ajoute les champs CRMino. `role_id`/`equipe_id` sont NULLABLES
 * pour ne pas casser l'inscription du kit ; un compte sans rôle n'a AUCUN droit
 * (Portee::Aucune = refus), donc le défaut est sûr. La création d'un compte
 * CRMino (§5, par un administrateur) exigera un rôle au niveau application.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nom', 100)->nullable()->after('name');
            $table->string('prenom', 100)->nullable()->after('nom');
            $table->string('fonction', 120)->nullable()->after('prenom');
            $table->foreignId('role_id')->nullable()->after('fonction')->constrained('roles')->nullOnDelete();
            $table->foreignId('equipe_id')->nullable()->after('role_id')->constrained('equipes')->nullOnDelete();
            $table->foreignId('responsable_id')->nullable()->after('equipe_id')->constrained('users')->nullOnDelete();
            $table->boolean('actif')->default(true)->after('responsable_id');
            $table->timestamp('derniere_connexion_le')->nullable()->after('actif');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
            $table->dropConstrainedForeignId('equipe_id');
            $table->dropConstrainedForeignId('responsable_id');
            $table->dropColumn(['nom', 'prenom', 'fonction', 'actif', 'derniere_connexion_le']);
        });
    }
};
