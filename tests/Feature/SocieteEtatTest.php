<?php

declare(strict_types=1);

use App\Models\AuditJournal;
use App\Models\Societe;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
    $this->commercial = crminoUtilisateur('COMMERCIAL');
});

it('RG-SOC-001 — Prospect devient Client, avec sa date de passage (§32)', function () {
    $s = Societe::factory()->create(['proprietaire_id' => $this->commercial->id, 'etat' => 'Prospect', 'devenu_client_le' => null]);

    $this->actingAs($this->commercial)->post("/societes/{$s->id}/etat", ['etat' => 'Client']);

    $s->refresh();
    expect($s->etat)->toBe('Client');
    expect($s->devenu_client_le)->not->toBeNull(); // RG-SOC-001 : la date survit (§72)
});

it('§46 — le changement d état trace ChangementStatut, pas Modification', function () {
    $s = Societe::factory()->create(['proprietaire_id' => $this->commercial->id, 'etat' => 'Prospect']);

    $this->actingAs($this->commercial)->post("/societes/{$s->id}/etat", ['etat' => 'Inactif']);

    $j = AuditJournal::query()->where('entite_type', 'Societe')->where('entite_id', $s->id)->first();
    expect($j)->not->toBeNull();
    expect($j->action)->toBe('ChangementStatut');
    expect($j->ancienne_valeur)->toBe('Prospect');
    expect($j->nouvelle_valeur)->toBe('Inactif');
});

it('RG-SOC-001 — un Client ne redevient PAS prospect', function () {
    $s = Societe::factory()->create(['proprietaire_id' => $this->commercial->id, 'etat' => 'Client', 'devenu_client_le' => now()]);

    $this->actingAs($this->commercial)
        ->post("/societes/{$s->id}/etat", ['etat' => 'Prospect'])
        ->assertSessionHas('error');

    expect($s->fresh()->etat)->toBe('Client'); // inchangé
});

it('RG-SOC-001 — depuis Inactif, les DEUX reprises sont ouvertes', function () {
    $a = Societe::factory()->create(['proprietaire_id' => $this->commercial->id, 'etat' => 'Inactif']);
    $b = Societe::factory()->create(['proprietaire_id' => $this->commercial->id, 'etat' => 'Inactif']);

    $this->actingAs($this->commercial)->post("/societes/{$a->id}/etat", ['etat' => 'Prospect']);
    $this->actingAs($this->commercial)->post("/societes/{$b->id}/etat", ['etat' => 'Client']);

    expect($a->fresh()->etat)->toBe('Prospect');
    expect($b->fresh()->etat)->toBe('Client');
});

it('§58 — hors périmètre → 404, jamais 403', function () {
    $autre = crminoUtilisateur('COMMERCIAL');
    $s = Societe::factory()->create(['proprietaire_id' => $autre->id, 'etat' => 'Prospect']);

    $this->actingAs($this->commercial)
        ->post("/societes/{$s->id}/etat", ['etat' => 'Inactif'])
        ->assertNotFound();
});
