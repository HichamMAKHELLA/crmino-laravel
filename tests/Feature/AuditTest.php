<?php

declare(strict_types=1);

use App\Models\AuditJournal;
use App\Models\Opportunite;
use App\Models\Referentiels\Source;
use App\Models\Societe;
use App\Support\Audit;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
    $this->admin = crminoUtilisateur('ADMIN');
    $this->commercial = crminoUtilisateur('COMMERCIAL');
});

it('trace la création d un lead (§46)', function () {
    $source = Source::query()->value('id');

    $this->actingAs($this->commercial)->post('/leads', [
        'source_id' => $source, 'raison_sociale' => 'Traçable SARL',
        'contact' => ['nom' => 'Idrissi', 'gsm' => '0611223344'],
    ]);

    $j = AuditJournal::query()->where('entite_type', 'Lead')->first();
    expect($j)->not->toBeNull();
    expect($j->action)->toBe('Creation');
    expect($j->utilisateur_id)->toBe($this->commercial->id);
});

it('trace le changement de statut au gain (§46)', function () {
    $societe = Societe::factory()->create(['proprietaire_id' => $this->commercial->id]);
    $opp = Opportunite::factory()->create(['societe_id' => $societe->id, 'proprietaire_id' => $this->commercial->id, 'statut' => 'Ouverte']);

    $this->actingAs($this->commercial)->post("/opportunites/{$opp->id}/gagner");

    $j = AuditJournal::query()->where('action', 'ChangementStatut')->first();
    expect($j)->not->toBeNull();
    expect($j->champ)->toBe('statut');
    expect($j->nouvelle_valeur)->toBe('Gagnee');
});

it('§46 — ne trace QUE les champs qui ont réellement changé', function () {
    Audit::tracerChamps($this->admin->id, 'Lead', 1, [
        'nom' => ['Ancien', 'Nouveau'],
        'ice' => ['001', '001'], // inchangé -> aucune ligne
    ]);

    expect(AuditJournal::query()->count())->toBe(1);
    expect(AuditJournal::query()->first()->champ)->toBe('nom');
});

it('§46 — le vide est ramené à l absence (une case blanche ne trace rien)', function () {
    Audit::tracerChamps($this->admin->id, 'Lead', 1, [
        'commentaire' => [null, '   '], // null et « » sont indistincts -> aucune ligne
    ]);

    expect(AuditJournal::query()->count())->toBe(0);
});

it('journal.consulter garde la lecture (§46)', function () {
    $this->withoutVite();
    Audit::tracer($this->admin->id, Audit::CREATION, 'Lead', 1);

    $this->actingAs($this->admin)->get('/journal')->assertOk();
    $this->actingAs($this->commercial)->get('/journal')->assertForbidden();
});
