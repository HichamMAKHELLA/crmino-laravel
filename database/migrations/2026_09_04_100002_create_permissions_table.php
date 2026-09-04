<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port de app.Permission. Le Code EST la clé (référencé par role_permissions).
 * `actif` porte l'extinction logique du §68 (0012 côté .NET) : une permission
 * éteinte ne donne plus aucun droit, sans être supprimée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->string('code', 64)->primary();
            $table->string('libelle', 200);
            $table->string('module', 32);
            $table->boolean('actif')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
