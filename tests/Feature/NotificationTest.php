<?php

declare(strict_types=1);

use App\Models\Notification;
use App\Models\Referentiels\PrioriteTache;
use App\Models\Referentiels\TypeTache;
use App\Support\Notifications;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
    $this->commercial = crminoUtilisateur('COMMERCIAL');
    $this->autre = crminoUtilisateur('COMMERCIAL');
    $this->type = TypeTache::query()->orderBy('ordre')->value('id');
    $this->prio = PrioriteTache::query()->orderBy('niveau')->value('id');
});

it('§36 — assigner une tâche à quelqu un d autre le notifie', function () {
    $this->actingAs($this->commercial)->post('/taches', [
        'titre' => 'Rappeler le client', 'type_id' => $this->type,
        'priorite_id' => $this->prio, 'assignee_id' => $this->autre->id,
    ]);

    $n = Notification::query()->where('utilisateur_id', $this->autre->id)->first();
    expect($n)->not->toBeNull();
    expect($n->titre)->toBe('Tâche assignée');
    expect($n->cible_type)->toBe('Tache');
});

it('§36 — s assigner à soi-même ne produit aucune notification', function () {
    $this->actingAs($this->commercial)->post('/taches', [
        'titre' => 'Ma tâche', 'type_id' => $this->type, 'priorite_id' => $this->prio,
    ]);

    expect(Notification::query()->count())->toBe(0);
});

it('marque une notification lue et redirige vers la cible', function () {
    $n = Notifications::notifier($this->commercial->id, 'Tâche assignée', 'X', 'Tache', 1);

    $this->actingAs($this->commercial)
        ->post("/notifications/{$n->id}/lu")
        ->assertRedirect('/taches');

    expect($n->fresh()->lue_le)->not->toBeNull();
});

it('marquer lu est idempotent — ne réécrit pas une notification déjà lue', function () {
    $n = Notifications::notifier($this->commercial->id, 'X', null, 'Tache', 1);
    $this->actingAs($this->commercial)->post("/notifications/{$n->id}/lu");
    $premier = $n->fresh()->lue_le;

    $this->travel(1)->hours();
    $this->actingAs($this->commercial)->post("/notifications/{$n->id}/lu");

    expect($n->fresh()->lue_le->equalTo($premier))->toBeTrue();
});

it('404 pour la notification d un autre (§36, appartenance)', function () {
    $n = Notifications::notifier($this->autre->id, 'X');

    $this->actingAs($this->commercial)->post("/notifications/{$n->id}/lu")->assertNotFound();
});

it('« Tout marquer lu » solde mes notifications non lues', function () {
    Notifications::notifier($this->commercial->id, 'A');
    Notifications::notifier($this->commercial->id, 'B');
    Notifications::notifier($this->autre->id, 'C'); // pas la mienne

    $this->actingAs($this->commercial)->post('/notifications/tout-lu');

    expect(Notification::query()->where('utilisateur_id', $this->commercial->id)->whereNull('lue_le')->count())->toBe(0);
    expect(Notification::query()->where('utilisateur_id', $this->autre->id)->whereNull('lue_le')->count())->toBe(1);
});

it('le compte non lu est partagé à chaque page (§36)', function () {
    $this->withoutVite();
    Notifications::notifier($this->commercial->id, 'A');
    Notifications::notifier($this->commercial->id, 'B');

    $this->actingAs($this->commercial)->get('/taches')
        ->assertInertia(fn (AssertableInertia $p) => $p->where('notificationsNonLues', 2));
});
