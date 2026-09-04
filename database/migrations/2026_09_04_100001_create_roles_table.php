<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port de app.[Role] (référentiel des rôles). Systeme = 1 protège une valeur
 * socle du retrait (RG-REF-001) ; le Code est réservé à vie (UQ_Role_Code).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('libelle', 120);
            $table->boolean('systeme')->default(false);
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
