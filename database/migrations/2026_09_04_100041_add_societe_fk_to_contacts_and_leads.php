<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Relie les colonnes `societe_id` de `contacts` et `leads` — posées nullables
 * en attendant la table `societes` — à leur cible désormais présente. La
 * conversion (§31) écrit dans ces colonnes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $t) {
            $t->foreign('societe_id')->references('id')->on('societes');
        });
        Schema::table('leads', function (Blueprint $t) {
            $t->foreign('societe_id')->references('id')->on('societes');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', fn (Blueprint $t) => $t->dropForeign(['societe_id']));
        Schema::table('leads', fn (Blueprint $t) => $t->dropForeign(['societe_id']));
    }
};
