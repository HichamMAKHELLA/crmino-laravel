<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Models\Lead;
use App\Models\Referentiels\Source;
use App\Support\DetecteurDoublons;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
    $this->commercial = crminoUtilisateur('COMMERCIAL');
    $this->source = Source::query()->value('id');
});

function corpsLead(int $source, array $extra = []): array
{
    return array_merge([
        'source_id' => $source,
        'raison_sociale' => 'Nouvelle Société SARL',
        'contact' => ['nom' => 'Idrissi', 'gsm' => '0612345678'],
    ], $extra);
}

it('refuse la création sans lead.creer (403)', function () {
    $sansRole = \App\Models\User::factory()->create(['role_id' => null, 'actif' => true]);

    $this->actingAs($sansRole)->post('/leads', corpsLead($this->source))->assertForbidden();
});

it('crée un lead et son contact principal, avec propriétaire = créateur', function () {
    $this->actingAs($this->commercial)
        ->post('/leads', corpsLead($this->source))
        ->assertRedirect(route('leads.index'));

    $lead = Lead::first();
    expect($lead->raison_sociale)->toBe('Nouvelle Société SARL');
    expect($lead->proprietaire_id)->toBe($this->commercial->id);
    expect($lead->affecte_le)->not->toBeNull();

    $contact = Contact::first();
    expect($contact->lead_id)->toBe($lead->id);
    expect($contact->principal)->toBeTrue();
    expect($contact->gsm_normalise)->toBe('0612345678');
});

it('exige une origine (§38)', function () {
    $this->actingAs($this->commercial)
        ->post('/leads', corpsLead($this->source, ['source_id' => null]))
        ->assertSessionHasErrors('source_id');
});

it('RG-LEA-001 — sans identité ni nom de contact', function () {
    $this->actingAs($this->commercial)
        ->post('/leads', ['source_id' => $this->source, 'raison_sociale' => null,
            'contact' => ['gsm' => '0612345678']])
        ->assertSessionHasErrors('rg_lea_001');
});

it('RG-LEA-001 — sans moyen de joindre', function () {
    $this->actingAs($this->commercial)
        ->post('/leads', ['source_id' => $this->source, 'raison_sociale' => 'Société Sans Contact'])
        ->assertSessionHasErrors('rg_lea_001');
});

it('accepte une raison sociale avec un standard, sans nom de contact', function () {
    // Identité par la raison sociale, joignable par le téléphone : aucun contact
    // n'est créé (la table exige un nom), et c'est conforme.
    $this->actingAs($this->commercial)
        ->post('/leads', ['source_id' => $this->source, 'raison_sociale' => 'Société Standard',
            'contact' => ['telephone' => '0522000000']]);

    expect(Lead::count())->toBe(1);
    expect(Contact::count())->toBe(0);
});

it('MONTRE les doublons et ne crée pas au premier envoi (§41/§71)', function () {
    Lead::factory()->create(['ice' => '001234567890123']);

    $this->actingAs($this->commercial)
        ->post('/leads', corpsLead($this->source, ['ice' => '001234567890123']))
        ->assertSessionHas('doublons');

    expect(Lead::count())->toBe(1); // le doublon seul, aucune création
});

it('crée quand même avec forcer malgré un doublon', function () {
    Lead::factory()->create(['ice' => '001234567890123']);

    $this->actingAs($this->commercial)
        ->post('/leads', corpsLead($this->source, ['ice' => '001234567890123', 'forcer' => true]))
        ->assertRedirect(route('leads.index'));

    expect(Lead::count())->toBe(2);
});

it('crée quand même si le contrôle de doublon TOMBE (§41)', function () {
    // Une vérification en panne ne doit pas empêcher d'écrire.
    $panne = Mockery::mock(DetecteurDoublons::class);
    $panne->shouldReceive('rechercher')->andThrow(new RuntimeException('panne'));
    $this->instance(DetecteurDoublons::class, $panne);

    $this->actingAs($this->commercial)
        ->post('/leads', corpsLead($this->source))
        ->assertRedirect(route('leads.index'));

    expect(Lead::count())->toBe(1);
});
