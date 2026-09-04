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

it('§39 — l entonnoir compte des LEADS, contactés >= rendez-vous', function () {
    $this->withoutVite();
    $appel = \App\Models\Referentiels\TypeActivite::query()->where('code', 'APPEL')->value('id');
    $reunion = \App\Models\Referentiels\TypeActivite::query()->where('categorie', 'Rdv')->value('id');

    // L1 : contacté (un appel). L2 : contacté + rendez-vous (une réunion).
    $l1 = \App\Models\Lead::factory()->create(['proprietaire_id' => $this->u->id]);
    \App\Models\Activite::factory()->create(['lead_id' => $l1->id, 'type_id' => $appel, 'utilisateur_id' => $this->u->id]);
    $l2 = \App\Models\Lead::factory()->create(['proprietaire_id' => $this->u->id]);
    \App\Models\Activite::factory()->create(['lead_id' => $l2->id, 'type_id' => $reunion, 'utilisateur_id' => $this->u->id]);
    // L3 : converti (aucune activité). L4 : rien.
    $soc = Societe::factory()->create(['proprietaire_id' => $this->u->id]);
    \App\Models\Lead::factory()->create(['proprietaire_id' => $this->u->id, 'societe_id' => $soc->id, 'converti_le' => now(), 'converti_par' => $this->u->id]);
    \App\Models\Lead::factory()->create(['proprietaire_id' => $this->u->id]);

    $this->actingAs($this->u)->get('/rapports?onglet=entonnoir')
        ->assertInertia(function (AssertableInertia $p) {
            $e = collect($p->toArray()['props']['entonnoir'])->keyBy('palier');
            expect($e['Leads affectés']['nb'])->toBe(4);
            expect($e['Contactés']['nb'])->toBe(2);   // L1, L2
            expect($e['Rendez-vous']['nb'])->toBe(1);  // L2
            expect($e['Convertis']['nb'])->toBe(1);    // L3
            // Invariant : affectés >= contactés >= rendez-vous.
            expect($e['Leads affectés']['nb'])->toBeGreaterThanOrEqual($e['Contactés']['nb']);
            expect($e['Contactés']['nb'])->toBeGreaterThanOrEqual($e['Rendez-vous']['nb']);
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
