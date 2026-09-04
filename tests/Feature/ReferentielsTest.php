<?php

declare(strict_types=1);

use App\Models\Referentiels\EtapePipeline;
use App\Models\Referentiels\MotifPerte;
use App\Models\Referentiels\PrioriteTache;
use App\Models\Referentiels\Source;
use App\Models\Referentiels\StatutLead;
use App\Models\Referentiels\TypeActivite;

beforeEach(function () {
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
});

it('sème chaque référentiel avec son compte attendu', function () {
    expect(Source::query()->count())->toBe(21);
    expect(StatutLead::query()->count())->toBe(14);
    expect(EtapePipeline::query()->count())->toBe(9);
    expect(MotifPerte::query()->count())->toBe(12);
    expect(TypeActivite::query()->count())->toBe(11);
    expect(PrioriteTache::query()->count())->toBe(4);
    expect(\App\Models\Referentiels\Secteur::query()->count())->toBe(17);
    expect(\App\Models\Referentiels\TypeTache::query()->count())->toBe(9);
});

// ── Les gardes de cohérence du seed .NET ───────────────────────────────────

it('le pipeline porte une étape Gagnée ET une Perdue (sans quoi le CA ne se calcule pas)', function () {
    expect(EtapePipeline::query()->where('categorie', 'Gagnee')->exists())->toBeTrue();
    expect(EtapePipeline::query()->where('categorie', 'Perdue')->exists())->toBeTrue();
});

it('les leads ont un statut Converti (la conversion du §31 s’y appuie)', function () {
    expect(StatutLead::query()->where('categorie', 'Converti')->exists())->toBeTrue();
});

it('toute probabilité d’étape est dans 0..100', function () {
    expect(EtapePipeline::query()->whereBetween('probabilite', [0, 100])->count())
        ->toBe(EtapePipeline::query()->count());
});

// ── Les colonnes de COMPORTEMENT ───────────────────────────────────────────

it('lit la probabilité effective de l’étape, pas son libellé', function () {
    expect(EtapePipeline::query()->where('code', 'PROPOSITION')->value('probabilite'))->toBe(60);
    expect(EtapePipeline::query()->where('code', 'GAGNE')->value('probabilite'))->toBe(100);
});

it('le motif exige un commentaire quand le référentiel le dit (§30)', function () {
    expect(MotifPerte::query()->find(codeVers('motifs_perte', 'CONCURRENT'))->commentaire_obligatoire)->toBeTrue();
    expect(MotifPerte::query()->find(codeVers('motifs_perte', 'PRIX'))->commentaire_obligatoire)->toBeFalse();
});

it('la catégorie du type d’activité est stable, distincte du libellé (§39)', function () {
    expect(TypeActivite::query()->where('code', 'REUNION')->value('categorie'))->toBe('Rdv');
    expect(TypeActivite::query()->where('code', 'DEMONSTRATION')->value('categorie'))->toBe('Demonstration');
});

it('les priorités se trient sur le niveau, pas sur le libellé', function () {
    $ordre = PrioriteTache::query()->orderBy('niveau')->pluck('code')->all();
    expect($ordre)->toBe(['BASSE', 'NORMALE', 'HAUTE', 'URGENTE']);
});

// ── RG-REF-001 : une valeur système ne se retire pas ───────────────────────

it('une valeur système n’est pas retirable, une valeur ordinaire l’est', function () {
    $systeme = StatutLead::query()->where('code', 'NOUVEAU')->first();
    $ordinaire = StatutLead::query()->where('code', 'A_CONTACTER')->first();

    expect($systeme->systeme)->toBeTrue();
    expect($systeme->estRetirable())->toBeFalse();

    expect($ordinaire->systeme)->toBeFalse();
    expect($ordinaire->estRetirable())->toBeTrue();
});

it('le scope Actif exclut les valeurs éteintes', function () {
    StatutLead::query()->where('code', 'ABANDONNE')->update(['actif' => false]);
    expect(StatutLead::query()->actif()->where('code', 'ABANDONNE')->exists())->toBeFalse();
    expect(StatutLead::query()->actif()->count())->toBe(13);
});

function codeVers(string $table, string $code): int
{
    return (int) \Illuminate\Support\Facades\DB::table($table)->where('code', $code)->value('id');
}
