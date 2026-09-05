<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute le TYPE à la notification (§36). Sans lui, la garde d'idempotence des
 * notifications d'état ne distingue pas « tâche en retard » (une fois par jour)
 * de « tâche assignée » (un événement) : la seconde masquerait la première, et
 * le retard ne serait jamais signalé.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications_crmino', function (Blueprint $t) {
            $t->string('type', 32)->default('Generique')->after('utilisateur_id');
            $t->index(['type', 'cible_id']); // la garde d'idempotence s'y appuie
        });
    }

    public function down(): void
    {
        Schema::table('notifications_crmino', function (Blueprint $t) {
            $t->dropIndex(['type', 'cible_id']);
            $t->dropColumn('type');
        });
    }
};
