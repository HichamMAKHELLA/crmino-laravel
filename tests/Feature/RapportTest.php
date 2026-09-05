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
    $this->seed(\Database\Seeders\CatalogueSeeder::class);
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

it('§76 — ventile le chiffre par produit, et compare aux TOTAUX d en-tête', function () {
    $this->withoutVite();
    $p = \App\Models\Produit::factory()->create(['designation' => 'Sage Compta']);
    $soc = Societe::factory()->create(['proprietaire_id' => $this->u->id]);

    // A ouverte, en-tête 200000 : une ligne produit 200000 + une ligne LIBRE 50000
    // (non ventilable, pour éprouver le filtre produit_id).
    $a = Opportunite::factory()->create(['societe_id' => $soc->id, 'proprietaire_id' => $this->u->id,
        'statut' => 'Ouverte', 'montant_ht' => 200000]);
    $a->lignes()->create(['produit_id' => $p->id, 'designation' => 'P', 'quantite' => 1, 'prix_unitaire' => 200000, 'ordre' => 0]);
    $a->lignes()->create(['produit_id' => null, 'designation' => 'Libre', 'quantite' => 1, 'prix_unitaire' => 50000, 'ordre' => 1]);

    // C ouverte, en-tête 100000, AUCUNE ligne ventilable.
    Opportunite::factory()->create(['societe_id' => $soc->id, 'proprietaire_id' => $this->u->id,
        'statut' => 'Ouverte', 'montant_ht' => 100000]);

    // B gagnée, en-tête 600000, une ligne produit 400000.
    $b = Opportunite::factory()->create(['societe_id' => $soc->id, 'proprietaire_id' => $this->u->id,
        'statut' => 'Gagnee', 'montant_ht' => 600000, 'probabilite' => 100, 'date_cloture' => now()]);
    $b->lignes()->create(['produit_id' => $p->id, 'designation' => 'P', 'quantite' => 1, 'prix_unitaire' => 400000, 'ordre' => 0]);

    $this->actingAs($this->u)->get('/rapports?onglet=ventilation')
        ->assertInertia(function (AssertableInertia $p) {
            $v = $p->toArray()['props']['ventilation'];
            $ligne = collect($v['lignes'])->firstWhere('produit', 'Sage Compta');
            expect($ligne['pipeline'])->toBe(200000);      // ouverte A, produit seul
            expect($ligne['ca_gagne'])->toBe(400000);      // gagnée B
            expect($ligne['opportunites_ouvertes'])->toBe(1);
            expect($ligne['gagnees'])->toBe(1);

            // Couverture : le total d'en-tête, jamais la somme des lignes.
            expect($v['pipeline_total'])->toBe(300000);    // A 200000 + C 100000
            expect($v['pipeline_ventile'])->toBe(200000);  // la ligne LIBRE ne compte pas
            expect($v['taux_pipeline'])->toBe(66.7);
            expect($v['ca_gagne_total'])->toBe(600000);
            expect($v['ca_gagne_ventile'])->toBe(400000);
            expect($v['taux_ca_gagne'])->toBe(66.7);
            expect($v['affaires_ouvertes_sans_ligne'])->toBe(1); // C
        });
});

it('§76 RG-IND-001 — un taux sans base est NULL, jamais zéro', function () {
    $this->withoutVite();
    // Aucune affaire : pas de base -> le taux n'est pas 0, il n'existe pas.
    $this->actingAs($this->u)->get('/rapports?onglet=ventilation')
        ->assertInertia(function (AssertableInertia $p) {
            $v = $p->toArray()['props']['ventilation'];
            expect($v['taux_pipeline'])->toBeNull();
            expect($v['taux_ca_gagne'])->toBeNull();
            expect($v['pipeline_total'])->toBe(0);
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
