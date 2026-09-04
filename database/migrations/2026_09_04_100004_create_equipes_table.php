<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port de app.Equipe. Le Code est réservé à vie (UQ_Equipe_Code) : il sert de
 * repère stable dans l'historique d'affectation (§83). Le responsable est
 * facultatif ; il gouverne la portée « Équipe » du §78.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('libelle', 120);
            // Cycle avec users (responsable) : nullable, posé après coup.
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipes');
    }
};
