<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Models\Lead;
use App\Models\QualificationLead;
use App\Models\Referentiels\PalierScore;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
    $this->commercial = crminoUtilisateur('COMMERCIAL');
    $this->lead = Lead::factory()->create(['proprietaire_id' => $this->commercial->id]);
});

it('résout le palier par ses bornes (§15)', function () {
    expect(PalierScore::pour(0)->libelle)->toBe('Froid');
    expect(PalierScore::pour(30)->libelle)->toBe('Froid');
    expect(PalierScore::pour(31)->libelle)->toBe('Tiède');
    expect(PalierScore::pour(80)->libelle)->toBe('Chaud');
    expect(PalierScore::pour(100)->libelle)->toBe('Très chaud');
});

it('rend la fiche du lead dans son périmètre', function () {
    $this->withoutVite();

    $this->actingAs($this->commercial)
        ->get("/leads/{$this->lead->id}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $p) => $p
            ->component('Leads/Show')
            ->where('lead.numero', $this->lead->numero));
});

it('rend 404 pour une fiche hors périmètre (§58)', function () {
    $autre = crminoUtilisateur('COMMERCIAL');
    $leadAutre = Lead::factory()->create(['proprietaire_id' => $autre->id]);

    $this->actingAs($this->commercial)
        ->get("/leads/{$leadAutre->id}")
        ->assertNotFound();
});

it('rend 403 sans la permission', function () {
    $sansRole = \App\Models\User::factory()->create(['role_id' => null, 'actif' => true]);

    $this->actingAs($sansRole)->get("/leads/{$this->lead->id}")->assertForbidden();
});

it('« Non scoré » n est PAS un score de zéro (RG-IND-001)', function () {
    $this->withoutVite();

    // Score nul -> aucun palier.
    $this->actingAs($this->commercial)
        ->get("/leads/{$this->lead->id}")
        ->assertInertia(fn (AssertableInertia $p) => $p->where('palier', null)->where('lead.score', null));

    // Score zéro -> palier Froid (0 tombe dans 0–30).
    $this->lead->update(['score' => 0]);
    $this->actingAs($this->commercial)
        ->get("/leads/{$this->lead->id}")
        ->assertInertia(fn (AssertableInertia $p) => $p->where('palier.libelle', 'Froid')->where('lead.score', 0));
});

it('affiche la qualification quand elle existe (§13)', function () {
    $this->withoutVite();
    QualificationLead::create(['lead_id' => $this->lead->id, 'usage_sage' => 'Ancien', 'revendeur_actuel' => 'ACME']);

    $this->actingAs($this->commercial)
        ->get("/leads/{$this->lead->id}")
        ->assertInertia(fn (AssertableInertia $p) => $p
            ->where('qualification.usage_sage', 'Ancien')
            ->where('qualification.revendeur_actuel', 'ACME'));
});

it('liste les contacts, le principal en tête', function () {
    $this->withoutVite();
    Contact::factory()->create(['lead_id' => $this->lead->id, 'nom' => 'Second', 'principal' => false]);
    Contact::factory()->create(['lead_id' => $this->lead->id, 'nom' => 'Principal', 'principal' => true]);

    $this->actingAs($this->commercial)
        ->get("/leads/{$this->lead->id}")
        ->assertInertia(fn (AssertableInertia $p) => $p
            ->has('contacts', 2)
            ->where('contacts.0.principal', true));
});
