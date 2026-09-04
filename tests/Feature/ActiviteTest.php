<?php

declare(strict_types=1);

use App\Models\Activite;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Referentiels\TypeActivite;
use App\Models\Societe;
use App\Support\ConversionLead;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
    $this->commercial = crminoUtilisateur('COMMERCIAL');
    $this->lead = Lead::factory()->create(['proprietaire_id' => $this->commercial->id]);
    $this->type = TypeActivite::query()->orderBy('ordre')->value('id');
});

function journaliser(array $extra = []): array
{
    return array_merge([
        'cible_type' => 'lead',
        'cible_id' => test()->lead->id,
        'type_id' => test()->type,
        'debut_le' => '2026-09-01 10:00',
        'objet' => 'Appel de découverte',
    ], $extra);
}

it('journalise une activité et la répercute sur la fiche (§20)', function () {
    $this->actingAs($this->commercial)
        ->post('/activites', journaliser([
            'prochaine_action_le' => '2026-09-05 09:00',
            'prochaine_action_libelle' => 'Envoyer la proposition',
        ]));

    $a = Activite::first();
    expect($a->lead_id)->toBe($this->lead->id);
    expect($a->utilisateur_id)->toBe($this->commercial->id);

    $lead = $this->lead->fresh();
    expect($lead->derniere_activite_le->toDateString())->toBe('2026-09-01');
    expect($lead->prochaine_action_libelle)->toBe('Envoyer la proposition');
});

it('§20 — DerniereActiviteLe ne recule jamais', function () {
    $this->actingAs($this->commercial)->post('/activites', journaliser(['debut_le' => '2026-09-10 10:00']));
    $this->actingAs($this->commercial)->post('/activites', journaliser(['debut_le' => '2026-08-01 10:00']));

    // La date reste sur l'activité la plus récente, malgré une saisie antérieure.
    expect($this->lead->fresh()->derniere_activite_le->toDateString())->toBe('2026-09-10');
});

it('rattache une activité à une société', function () {
    $societe = Societe::factory()->create(['proprietaire_id' => $this->commercial->id]);

    $this->actingAs($this->commercial)
        ->post('/activites', ['cible_type' => 'societe', 'cible_id' => $societe->id,
            'type_id' => $this->type, 'debut_le' => '2026-09-01 10:00', 'objet' => 'RDV']);

    expect(Activite::first()->societe_id)->toBe($societe->id);
    expect(Activite::first()->lead_id)->toBeNull();
});

it('rend 404 pour une cible hors périmètre (§58)', function () {
    $autre = crminoUtilisateur('COMMERCIAL');
    $leadAutre = Lead::factory()->create(['proprietaire_id' => $autre->id]);

    $this->actingAs($this->commercial)
        ->post('/activites', journaliser(['cible_id' => $leadAutre->id]))
        ->assertNotFound();
});

it('refuse sans activite.creer (403)', function () {
    $sansRole = \App\Models\User::factory()->create(['role_id' => null, 'actif' => true]);

    $this->actingAs($sansRole)->post('/activites', journaliser())->assertForbidden();
});

it('la conversion bascule aussi les activités (§31, §73)', function () {
    Contact::factory()->create(['lead_id' => $this->lead->id]);
    Activite::factory()->count(2)->create(['lead_id' => $this->lead->id]);

    $resultat = (new ConversionLead)->convertir($this->lead, null, $this->commercial);

    expect($resultat['activites'])->toBe(2);
    expect(Activite::where('societe_id', $resultat['societe']->id)->count())->toBe(2);
    expect(Activite::where('lead_id', $this->lead->id)->count())->toBe(0);
});
