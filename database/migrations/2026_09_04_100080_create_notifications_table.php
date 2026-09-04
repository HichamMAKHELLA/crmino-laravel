<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notifications (§36). Destinées à un utilisateur. Cible polymorphe et NULLABLE
 * — un transfert de portefeuille ne mène nulle part. lue_le null = non lue.
 * Fidèle à app.Notification. (Table distincte de la table `notifications` par
 * défaut de Laravel, qui n'est pas utilisée.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications_crmino', function (Blueprint $t) {
            $t->id();
            $t->foreignId('utilisateur_id')->constrained('users');
            $t->string('titre', 200);
            $t->string('texte', 1000)->nullable();
            $t->string('cible_type', 16)->nullable();
            $t->unsignedBigInteger('cible_id')->nullable();
            $t->dateTime('lue_le')->nullable();
            $t->timestamp('cree_le')->useCurrent();

            $t->index(['utilisateur_id', 'lue_le']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications_crmino');
    }
};
