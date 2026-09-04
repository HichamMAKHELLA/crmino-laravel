<?php

declare(strict_types=1);

use App\Models\Opportunite;
use App\Models\Referentiels\EtapePipeline;
use App\Models\Societe;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
    $this->commercial = crminoUtilisateur('COMMERCIAL');
    $this->societe = Societe::factory()->create(['proprietaire_id' => $this->commercial->id]);
    $this->etape = EtapePipeline::query()->where('code', 'PROPOSITION')->first(); // 60 %
});

it('RG-OPP-001 — le montant pondéré est calculé par le schéma', function () {
    $o = Opportunite::factory()->create([
        'societe_id' => $this->societe->id,
        'proprietaire_id' => $this->commercial->id,
        'montant_ht' => 200000,
        'probabilite' => 60,
    ]);

    expect((float) $o->fresh()->montant_pondere)->toBe(120000.0);
});

it('le pondéré suit le montant et la probabilité (colonne générée)', function () {
    $o = Opportunite::factory()->create([
        'societe_id' => $this->societe->id, 'proprietaire_id' => $this->commercial->id,
        'montant_ht' => 100000, 'probabilite' => 25,
    ]);
    expect((float) $o->fresh()->montant_pondere)->toBe(25000.0);

    $o->update(['montant_ht' => 100000, 'probabilite' => 50]);
    expect((float) $o->fresh()->montant_pondere)->toBe(50000.0);
});

it('ouvre une affaire sur une société (§25)', function () {
    $this->actingAs($this->commercial)
        ->post('/opportunites', [
            'intitule' => 'Déploiement Sage 100',
            'societe_id' => $this->societe->id,
            'etape_id' => $this->etape->id,
            'montant_ht' => 300000,
        ])
        ->assertRedirect(route('opportunites.index'));

    $o = Opportunite::first();
    expect($o->intitule)->toBe('Déploiement Sage 100');
    expect($o->statut)->toBe('Ouverte');
    expect($o->proprietaire_id)->toBe($this->commercial->id);
    // RG-OPP-004 : à défaut de saisie, la probabilité de l'étape (60 %).
    expect($o->probabilite)->toBe(60);
});

it('RG-OPP-004 — la probabilité saisie surcharge celle de l étape', function () {
    $this->actingAs($this->commercial)
        ->post('/opportunites', [
            'intitule' => 'Affaire surchargée',
            'societe_id' => $this->societe->id,
            'etape_id' => $this->etape->id,
            'probabilite' => 90,
        ]);

    expect(Opportunite::first()->probabilite)->toBe(90);
});

it('exige un intitulé et une société', function () {
    $this->actingAs($this->commercial)
        ->post('/opportunites', ['etape_id' => $this->etape->id])
        ->assertSessionHasErrors(['intitule', 'societe_id']);
});

it('refuse la création sans opportunite.creer (403)', function () {
    $sansRole = \App\Models\User::factory()->create(['role_id' => null, 'actif' => true]);

    $this->actingAs($sansRole)
        ->post('/opportunites', ['intitule' => 'X', 'societe_id' => $this->societe->id, 'etape_id' => $this->etape->id])
        ->assertForbidden();
});

it('le board borne les affaires au périmètre', function () {
    $this->withoutVite();
    $autre = crminoUtilisateur('COMMERCIAL');
    $societeAutre = Societe::factory()->create(['proprietaire_id' => $autre->id]);

    Opportunite::factory()->create(['societe_id' => $this->societe->id, 'proprietaire_id' => $this->commercial->id, 'etape_id' => $this->etape->id]);
    Opportunite::factory()->create(['societe_id' => $societeAutre->id, 'proprietaire_id' => $autre->id, 'etape_id' => $this->etape->id]);

    $this->actingAs($this->commercial)
        ->get('/opportunites')
        ->assertOk()
        ->assertInertia(function (AssertableInertia $p) {
            $colonnes = $p->toArray()['props']['colonnes'];
            $total = collect($colonnes)->sum(fn ($c) => count($c['cartes']));
            expect($total)->toBe(1); // seule la mienne
        });
});
