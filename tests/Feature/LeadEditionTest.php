<?php

declare(strict_types=1);

use App\Models\Lead;
use App\Models\Referentiels\Source;
use App\Models\Societe;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
    $this->commercial = crminoUtilisateur('COMMERCIAL');
    $this->source = Source::query()->where('actif', true)->first();
    $this->lead = Lead::factory()->create([
        'proprietaire_id' => $this->commercial->id, 'raison_sociale' => 'Ancienne SARL',
        'ice' => '001122334455667', 'score' => 40,
    ]);
});

it('§5 — modifie les champs et RECALCULE la normalisation §41', function () {
    $this->actingAs($this->commercial)->put("/leads/{$this->lead->id}", [
        'raison_sociale' => 'Nouvelle Boîte SARL', 'source_id' => $this->source->id, 'effectif' => 50,
    ]);

    $l = $this->lead->fresh();
    expect($l->raison_sociale)->toBe('Nouvelle Boîte SARL');
    expect($l->effectif)->toBe(50); // champ non affiché, écrit quand même
    expect($l->raison_sociale_normalisee)->toContain('NOUVELLE');
    expect($l->raison_sociale_normalisee)->not->toContain('ANCIENNE');
});

it('§41/§15 — un ICE vidé part à NUL, jamais à chaîne vide ; un score effacé aussi', function () {
    // Un ICE fait d'ESPACES (le middleware Laravel ne nulle que la chaîne vide
    // EXACTE ; l'élagage du contrôleur couvre les espaces).
    $this->actingAs($this->commercial)->put("/leads/{$this->lead->id}", [
        'raison_sociale' => 'X', 'source_id' => $this->source->id, 'ice' => '   ', 'score' => null,
    ]);

    $l = $this->lead->fresh();
    expect($l->ice)->toBeNull();   // pas '   ' — sinon toutes les fiches sans ICE se rapprochent
    expect($l->score)->toBeNull(); // « Non scoré » ≠ score de zéro (§15)
});

it('§17 — le PROPRIÉTAIRE est EXCLU du corps (réaffecter a sa route)', function () {
    $autre = crminoUtilisateur('COMMERCIAL');

    $this->actingAs($this->commercial)->put("/leads/{$this->lead->id}", [
        'raison_sociale' => 'X', 'source_id' => $this->source->id, 'proprietaire_id' => $autre->id,
    ]);

    expect($this->lead->fresh()->proprietaire_id)->toBe($this->commercial->id);
});

it('RG-LEA-003 — un lead converti ne se modifie plus', function () {
    $soc = Societe::factory()->create(['proprietaire_id' => $this->commercial->id]);
    $this->lead->forceFill(['societe_id' => $soc->id, 'converti_le' => now(), 'converti_par' => $this->commercial->id])->save();

    $this->actingAs($this->commercial)
        ->put("/leads/{$this->lead->id}", ['raison_sociale' => 'Volée', 'source_id' => $this->source->id])
        ->assertSessionHas('error');

    expect($this->lead->fresh()->raison_sociale)->toBe('Ancienne SARL'); // inchangé
});

it('§58 — modifier un lead hors périmètre → 404', function () {
    $autre = crminoUtilisateur('COMMERCIAL');
    $lead = Lead::factory()->create(['proprietaire_id' => $autre->id]);

    $this->actingAs($this->commercial)
        ->put("/leads/{$lead->id}", ['raison_sociale' => 'X', 'source_id' => $this->source->id])
        ->assertNotFound();
});
