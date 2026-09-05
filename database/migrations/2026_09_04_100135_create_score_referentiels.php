<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Référentiels et colonnes du score §15. Le score est SUGGÉRÉ (jamais écrit
 * d'office) : ces tables nourrissent le calcul des huit critères. Un critère
 * dont le vocabulaire disparaît devient « hors d'atteinte » (RG-LEA-004), jamais
 * « non acquis » — deux états distincts à l'écran.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['criteres_score', 'tranches_budget', 'horizons_decision', 'besoins'] as $table) {
            Schema::create($table, function (Blueprint $t) use ($table) {
                $t->id();
                $t->string('code', 32)->unique();
                $t->string('libelle', 120);
                if ($table === 'criteres_score') {
                    $t->unsignedTinyInteger('poids')->default(0); // pondération, total = 100
                }
                $t->integer('ordre')->default(0);
                $t->boolean('actif')->default(true);
                $t->boolean('systeme')->default(false);
            });
        }

        // Les trois critères d'intention se lisent sur la qualification (§15).
        Schema::table('qualification_leads', function (Blueprint $t) {
            $t->foreignId('tranche_budget_id')->nullable()->constrained('tranches_budget');
            $t->foreignId('horizon_decision_id')->nullable()->constrained('horizons_decision');
            $t->boolean('projet_defini')->nullable();
        });

        // Le décisionnaire se lit sur contacts.decisionnaire (déjà présent, §15).

        // Les besoins exprimés (n-n), §14.
        Schema::create('lead_besoins', function (Blueprint $t) {
            $t->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $t->foreignId('besoin_id')->constrained('besoins');
            $t->primary(['lead_id', 'besoin_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_besoins');
        Schema::table('qualification_leads', function (Blueprint $t) {
            $t->dropConstrainedForeignId('tranche_budget_id');
            $t->dropConstrainedForeignId('horizon_decision_id');
            $t->dropColumn('projet_defini');
        });
        foreach (['besoins', 'horizons_decision', 'tranches_budget', 'criteres_score'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
