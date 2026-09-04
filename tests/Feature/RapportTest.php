<?php

declare(strict_types=1);

use App\Models\Opportunite;
use App\Models\Referentiels\EtapePipeline;
use App\Models\Referentiels\MotifPerte;
use App\Models\Societe;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
    $this->u = crminoUtilisateur('COMMERCIAL'); // rapport.consulter en Siennes
});

function opp(int $proprio, array $attrs): void
{
    $societe = Societe::factory()->create(['proprietaire_id' => $proprio]);
    Opportunite::factory()->create(array_merge([
        'societe_id' => $societe->id, 'proprietaire_id' => $proprio,
    ], $attrs));
}

it('§75 — le prévisionnel affiche la probabilité EFFECTIVE, pas celle de l étape', function () {
    $this->withoutVite();
    $proposition = EtapePipeline::query()->where('code', 'PROPOSITION')->value('id'); // étape à 60 %
    // Montant 100000, probabilité surchargée à 45 -> pondéré 45000 -> effective 45 %.
    opp($this->u->id, ['etape_id' => $proposition, 'statut' => 'Ouverte', 'montant_ht' => 100000, 'probabilite' => 45]);

    $this->actingAs($this->u)->get('/rapports?onglet=previsionnel')
        ->assertInertia(function (AssertableInertia $p) {
            $ligne = collect($p->toArray()['props']['previsionnel'])->firstWhere('etape', 'Proposition');
            expect($ligne['pondere'])->toBe(45000);
            expect($ligne['probabilite_effective'])->toBe(45);
        });
});

it('§38 — les motifs de perte, groupés et datés sur la clôture', function () {
    $this->withoutVite();
    $prix = MotifPerte::query()->where('code', 'PRIX')->value('id');
    $concurrent = MotifPerte::query()->where('code', 'CONCURRENT')->value('id');
    opp($this->u->id, ['statut' => 'Perdue', 'montant_ht' => 10000, 'motif_perte_id' => $prix, 'date_cloture' => '2026-03-10']);
    opp($this->u->id, ['statut' => 'Perdue', 'montant_ht' => 20000, 'motif_perte_id' => $prix, 'date_cloture' => '2026-03-15']);
    opp($this->u->id, ['statut' => 'Perdue', 'montant_ht' => 5000, 'motif_perte_id' => $concurrent, 'date_cloture' => '2026-03-20']);
    // Hors période : ne doit pas compter.
    opp($this->u->id, ['statut' => 'Perdue', 'montant_ht' => 99999, 'motif_perte_id' => $prix, 'date_cloture' => '2026-01-01']);

    $this->actingAs($this->u)->get('/rapports?onglet=motifs&du=2026-03-01&au=2026-04-01')
        ->assertInertia(function (AssertableInertia $p) {
            $motifs = collect($p->toArray()['props']['motifs']);
            $premier = $motifs->first(); // trié par nb desc
            expect($premier['motif'])->toBe('Prix');
            expect($premier['nb'])->toBe(2); // le 4e (janvier) est hors période
            expect($premier['montant'])->toBe(30000);
        });
});

it('l onglet vit dans l adresse et retombe sur le prévisionnel', function () {
    $this->withoutVite();
    $this->actingAs($this->u)->get('/rapports?onglet=inconnu')
        ->assertInertia(fn (AssertableInertia $p) => $p->where('onglet', 'previsionnel'));
});

it('refuse la consultation sans rapport.consulter (403)', function () {
    $sansRole = \App\Models\User::factory()->create(['role_id' => null, 'actif' => true]);

    $this->actingAs($sansRole)->get('/rapports')->assertForbidden();
});
