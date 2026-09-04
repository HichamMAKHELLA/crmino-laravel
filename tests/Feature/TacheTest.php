<?php

declare(strict_types=1);

use App\Models\Referentiels\PrioriteTache;
use App\Models\Referentiels\TypeTache;
use App\Models\Tache;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
    $this->commercial = crminoUtilisateur('COMMERCIAL');
    $this->type = TypeTache::query()->orderBy('ordre')->value('id');
    $this->prio = PrioriteTache::query()->orderBy('niveau')->value('id');
});

it('crée une tâche assignée au créateur', function () {
    $this->actingAs($this->commercial)
        ->post('/taches', ['titre' => 'Relancer Idrissi', 'type_id' => $this->type, 'priorite_id' => $this->prio]);

    $t = Tache::first();
    expect($t->titre)->toBe('Relancer Idrissi');
    expect($t->assignee_id)->toBe($this->commercial->id);
    expect($t->statut)->toBe('AFaire');
});

it('terminer pose le statut et la date d achèvement (§20)', function () {
    $t = Tache::factory()->create(['assignee_id' => $this->commercial->id, 'statut' => 'AFaire']);

    $this->actingAs($this->commercial)->post("/taches/{$t->id}/terminer");

    $t->refresh();
    expect($t->statut)->toBe('Terminee');
    expect($t->terminee_le)->not->toBeNull();
});

it('§20 — échues d abord, sans échéance en dernier', function () {
    $this->withoutVite();
    $retard = Tache::factory()->create(['assignee_id' => $this->commercial->id, 'titre' => 'RETARD', 'echeance_le' => now()->subDays(2)]);
    $avenir = Tache::factory()->create(['assignee_id' => $this->commercial->id, 'titre' => 'AVENIR', 'echeance_le' => now()->addDays(3)]);
    $sansDate = Tache::factory()->create(['assignee_id' => $this->commercial->id, 'titre' => 'SANS', 'echeance_le' => null]);

    $this->actingAs($this->commercial)->get('/taches')->assertInertia(function (AssertableInertia $p) use ($retard, $avenir, $sansDate) {
        $ids = collect($p->toArray()['props']['taches'])->pluck('id')->all();
        expect($ids)->toBe([$retard->id, $avenir->id, $sansDate->id]);
    });
});

it('§20 — à échéance égale, la priorité la plus haute passe devant', function () {
    $this->withoutVite();
    $haute = PrioriteTache::query()->orderByDesc('niveau')->value('id');
    $basse = PrioriteTache::query()->orderBy('niveau')->value('id');
    $jour = now()->addDay();
    $tBasse = Tache::factory()->create(['assignee_id' => $this->commercial->id, 'titre' => 'BASSE', 'echeance_le' => $jour, 'priorite_id' => $basse]);
    $tHaute = Tache::factory()->create(['assignee_id' => $this->commercial->id, 'titre' => 'HAUTE', 'echeance_le' => $jour, 'priorite_id' => $haute]);

    $this->actingAs($this->commercial)->get('/taches')->assertInertia(function (AssertableInertia $p) use ($tHaute, $tBasse) {
        $ids = collect($p->toArray()['props']['taches'])->pluck('id')->all();
        expect($ids)->toBe([$tHaute->id, $tBasse->id]);
    });
});

it('« Mes tâches » ne montre que les miennes, non terminées', function () {
    $this->withoutVite();
    $autre = crminoUtilisateur('COMMERCIAL');
    Tache::factory()->create(['assignee_id' => $this->commercial->id, 'titre' => 'MIENNE']);
    Tache::factory()->create(['assignee_id' => $autre->id, 'titre' => 'AUTRE']);
    Tache::factory()->create(['assignee_id' => $this->commercial->id, 'statut' => 'Terminee', 'terminee_le' => now()]);

    $this->actingAs($this->commercial)->get('/taches')->assertInertia(fn (AssertableInertia $p) => $p->has('taches', 1));
});

it('refuse de terminer la tâche d un autre (403)', function () {
    $autre = crminoUtilisateur('COMMERCIAL');
    $t = Tache::factory()->create(['assignee_id' => $autre->id]);

    // COMMERCIAL n'a pas tache.modifier au-delà des siennes -> 403 sur celle d'autrui.
    $this->actingAs($this->commercial)->post("/taches/{$t->id}/terminer")->assertForbidden();
});

it('refuse la création sans tache.creer (403)', function () {
    $sansRole = \App\Models\User::factory()->create(['role_id' => null, 'actif' => true]);

    $this->actingAs($sansRole)
        ->post('/taches', ['titre' => 'X', 'type_id' => $this->type, 'priorite_id' => $this->prio])
        ->assertForbidden();
});
