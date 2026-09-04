<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Compteurs de numérotation (§10, §25). Une ligne par préfixe (LEAD, SOC, OPP,
 * CAM). Le numéro se lit par verrou de ligne, jamais par MAX(...)+1 : deux
 * entités ne partagent jamais une séquence, et les trous ressembleraient à des
 * fiches perdues. L'année du numéro est décorative — la séquence ne se
 * réinitialise pas et c'est elle seule qui garantit l'unicité.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sequences', function (Blueprint $t) {
            $t->string('code', 16)->primary();
            $t->unsignedBigInteger('valeur')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sequences');
    }
};
