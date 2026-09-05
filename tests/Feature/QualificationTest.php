<?php

declare(strict_types=1);

use App\Models\Lead;
use App\Models\QualificationLead;
use App\Models\Societe;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
    $this->commercial = crminoUtilisateur('COMMERCIAL');
    $this->lead = Lead::factory()->create(['proprietaire_id' => $this->commercial->id]);
});

it('§13 — enregistre la qualification (satellite 1-1) et pose modifie_le/par', function () {
    $this->actingAs($this->commercial)->put("/leads/{$this->lead->id}/qualification", [
        'usage_sage' => 'Oui', 'version_sage' => 'Sage 100 v9', 'nb_utilisateurs' => 12,
        'logiciel_actuel' => 'Ciel',
    ]);

    $q = QualificationLead::query()->where('lead_id', $this->lead->id)->first();
    expect($q)->not->toBeNull();
    expect($q->usage_sage)->toBe('Oui');
    expect($q->version_sage)->toBe('Sage 100 v9');
    expect($q->nb_utilisateurs)->toBe(12);
    expect($q->modifie_le)->not->toBeNull();
    expect($q->modifie_par)->toBe($this->commercial->id);
});

it('§13 — réécrit par REMPLACEMENT (updateOrCreate sur lead_id)', function () {
    $this->actingAs($this->commercial)->put("/leads/{$this->lead->id}/qualification", ['usage_sage' => 'Oui', 'logiciel_actuel' => 'A']);
    $this->actingAs($this->commercial)->put("/leads/{$this->lead->id}/qualification", ['usage_sage' => 'Non', 'logiciel_actuel' => 'B']);

    expect(QualificationLead::query()->where('lead_id', $this->lead->id)->count())->toBe(1); // pas de doublon
    expect(QualificationLead::query()->where('lead_id', $this->lead->id)->value('logiciel_actuel'))->toBe('B');
});

it('§13 — hors « Oui »/« Ancien », le bloc Sage repart à NUL', function () {
    // D'abord un parc Sage renseigné.
    $this->actingAs($this->commercial)->put("/leads/{$this->lead->id}/qualification", [
        'usage_sage' => 'Oui', 'version_sage' => 'v9', 'revendeur_actuel' => 'Revendeur X', 'contrat_sage' => true,
    ]);
    // Puis « Non » : les champs Sage doivent être vidés.
    $this->actingAs($this->commercial)->put("/leads/{$this->lead->id}/qualification", ['usage_sage' => 'Non']);

    $q = QualificationLead::query()->where('lead_id', $this->lead->id)->first();
    expect($q->version_sage)->toBeNull();
    expect($q->revendeur_actuel)->toBeNull();
    expect($q->contrat_sage)->toBeNull();
});

it('§13 — « Ancien utilisateur » conserve le bloc Sage (comme « Oui »)', function () {
    $this->actingAs($this->commercial)->put("/leads/{$this->lead->id}/qualification", [
        'usage_sage' => 'Ancien', 'version_sage' => 'v7', 'revendeur_actuel' => 'Ancien revendeur',
    ]);

    $q = QualificationLead::query()->where('lead_id', $this->lead->id)->first();
    expect($q->usage_sage)->toBe('Ancien');
    expect($q->version_sage)->toBe('v7'); // conservé
    expect($q->revendeur_actuel)->toBe('Ancien revendeur');
});

it('RG-LEA-003 — un lead converti ne se qualifie plus', function () {
    $soc = Societe::factory()->create(['proprietaire_id' => $this->commercial->id]);
    $this->lead->forceFill(['societe_id' => $soc->id, 'converti_le' => now(), 'converti_par' => $this->commercial->id])->save();

    $this->actingAs($this->commercial)
        ->put("/leads/{$this->lead->id}/qualification", ['usage_sage' => 'Oui'])
        ->assertSessionHas('error');

    expect(QualificationLead::query()->where('lead_id', $this->lead->id)->exists())->toBeFalse();
});

it('§58 — qualifier un lead hors périmètre → 404', function () {
    $autre = crminoUtilisateur('COMMERCIAL');
    $lead = Lead::factory()->create(['proprietaire_id' => $autre->id]);

    $this->actingAs($this->commercial)
        ->put("/leads/{$lead->id}/qualification", ['usage_sage' => 'Oui'])
        ->assertNotFound();
});
