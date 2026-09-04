<?php

declare(strict_types=1);

use App\Models\Produit;
use App\Models\Referentiels\GammeProduit;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
    $this->seed(\Database\Seeders\CatalogueSeeder::class);
    $this->admin = crminoUtilisateur('ADMIN');
    $this->gamme = GammeProduit::query()->where('code', 'SAGE100')->value('id');
});

it('ajoute un produit au catalogue (code en majuscules)', function () {
    $this->actingAs($this->admin)->post('/catalogue', [
        'gamme_id' => $this->gamme, 'code' => 'nouveau1',
        'designation' => 'Module e-commerce', 'type' => 'Licence', 'prix_catalogue' => 9000,
    ])->assertRedirect(route('catalogue.index'));

    $p = Produit::query()->where('designation', 'Module e-commerce')->first();
    expect($p->code)->toBe('NOUVEAU1');
    expect($p->actif)->toBeTrue();
});

it('§28/§47 — le code reste réservé même après retrait', function () {
    $p = Produit::factory()->create(['gamme_id' => $this->gamme, 'code' => 'RESERVE1']);
    $this->actingAs($this->admin)->post("/catalogue/{$p->id}/desactiver");
    expect($p->fresh()->actif)->toBeFalse();

    // Recréer sous le même code échoue — l'unicité n'est pas filtrée sur actif.
    $this->actingAs($this->admin)->post('/catalogue', [
        'gamme_id' => $this->gamme, 'code' => 'RESERVE1',
        'designation' => 'Doublon', 'type' => 'Licence',
    ])->assertSessionHasErrors('code');
});

it('§47 — un produit retiré revient par réactivation', function () {
    $p = Produit::factory()->create(['gamme_id' => $this->gamme, 'actif' => false]);

    $this->actingAs($this->admin)->post("/catalogue/{$p->id}/reactiver");

    expect($p->fresh()->actif)->toBeTrue();
});

it('refuse la gestion sans catalogue.gerer (403)', function () {
    $sansRole = \App\Models\User::factory()->create(['role_id' => null, 'actif' => true]);

    $this->actingAs($sansRole)->post('/catalogue', [
        'gamme_id' => $this->gamme, 'code' => 'X1', 'designation' => 'X', 'type' => 'Licence',
    ])->assertForbidden();
});

it('refuse la consultation sans catalogue.consulter (403)', function () {
    $sansRole = \App\Models\User::factory()->create(['role_id' => null, 'actif' => true]);

    $this->actingAs($sansRole)->get('/catalogue')->assertForbidden();
});
