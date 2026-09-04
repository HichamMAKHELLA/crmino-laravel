<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Models\Lead;
use App\Models\Referentiels\StatutLead;
use App\Models\Societe;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
    $this->commercial = crminoUtilisateur('COMMERCIAL');
    $this->lead = Lead::factory()->create([
        'raison_sociale' => 'Cible SARL',
        'ice' => '009988776655443',
        'proprietaire_id' => $this->commercial->id,
    ]);
    Contact::factory()->count(2)->create(['lead_id' => $this->lead->id]);
});

it('crée une société héritée et bascule les contacts (§31)', function () {
    $this->actingAs($this->commercial)
        ->post("/leads/{$this->lead->id}/convertir")
        ->assertRedirect();

    $societe = Societe::first();
    expect($societe->raison_sociale)->toBe('Cible SARL');
    expect($societe->ice)->toBe('009988776655443');
    expect($societe->etat)->toBe('Prospect');
    expect($societe->proprietaire_id)->toBe($this->commercial->id);

    // Les contacts ont basculé (déplacés, pas recopiés).
    expect(Contact::where('societe_id', $societe->id)->count())->toBe(2);
    expect(Contact::where('lead_id', $this->lead->id)->count())->toBe(0);
});

it('marque le lead converti (§31, RG-LEA-003)', function () {
    $this->actingAs($this->commercial)->post("/leads/{$this->lead->id}/convertir");

    $lead = $this->lead->fresh();
    $converti = StatutLead::query()->where('categorie', 'Converti')->value('id');
    expect($lead->societe_id)->not->toBeNull();
    expect($lead->converti_le)->not->toBeNull();
    expect($lead->statut_id)->toBe($converti);
});

it('RG-LEA-003 — un lead déjà converti ne se reconvertit pas', function () {
    $this->actingAs($this->commercial)->post("/leads/{$this->lead->id}/convertir");
    expect(Societe::count())->toBe(1);

    $this->actingAs($this->commercial)
        ->post("/leads/{$this->lead->id}/convertir")
        ->assertSessionHas('error');

    expect(Societe::count())->toBe(1); // aucune seconde société
});

it('refuse sans lead.convertir (403)', function () {
    $sansRole = \App\Models\User::factory()->create(['role_id' => null, 'actif' => true]);

    $this->actingAs($sansRole)->post("/leads/{$this->lead->id}/convertir")->assertForbidden();
});

it('rend 404 pour un lead hors périmètre (§58)', function () {
    $autre = crminoUtilisateur('COMMERCIAL');
    $leadAutre = Lead::factory()->create(['proprietaire_id' => $autre->id]);

    $this->actingAs($this->commercial)
        ->post("/leads/{$leadAutre->id}/convertir")
        ->assertNotFound();
});

it('un lead sans raison sociale prend le nom de son contact', function () {
    $lead = Lead::factory()->create(['raison_sociale' => null, 'proprietaire_id' => $this->commercial->id]);
    Contact::factory()->create(['lead_id' => $lead->id, 'prenom' => 'Salma', 'nom' => 'Benali', 'principal' => true]);

    $this->actingAs($this->commercial)->post("/leads/{$lead->id}/convertir");

    expect(Societe::query()->where('raison_sociale', 'Salma Benali')->exists())->toBeTrue();
});

it('rattache à une société existante sans en créer une seconde', function () {
    $existante = Societe::factory()->create(['proprietaire_id' => $this->commercial->id]);

    $this->actingAs($this->commercial)
        ->post("/leads/{$this->lead->id}/convertir", ['societe_existante_id' => $existante->id]);

    expect(Societe::count())->toBe(1);
    expect(Contact::where('societe_id', $existante->id)->count())->toBe(2);
    expect($this->lead->fresh()->societe_id)->toBe($existante->id);
});
