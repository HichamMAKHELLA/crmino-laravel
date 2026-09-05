<?php

declare(strict_types=1);

use App\Models\Activite;
use App\Models\Notification;
use App\Models\Referentiels\TypeActivite;
use App\Models\Societe;
use App\Models\Tache;
use App\Support\ProducteurNotificationsEtat;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
    $this->u = crminoUtilisateur('COMMERCIAL');
    $this->rdvType = TypeActivite::query()->where('categorie', 'Rdv')->value('id');
    $this->prod = app(ProducteurNotificationsEtat::class);
});

it('§36 — un RDV du jour produit UN rappel, et une seconde exécution n en produit pas', function () {
    $soc = Societe::factory()->create(['proprietaire_id' => $this->u->id]);
    Activite::factory()->create([
        'societe_id' => $soc->id, 'type_id' => $this->rdvType,
        'utilisateur_id' => $this->u->id, 'debut_le' => now(),
    ]);

    $r1 = $this->prod->produireEtats();
    expect($r1['rdv'])->toBe(1);

    // Idempotent : un événement daté ne se rappelle pas deux fois.
    $r2 = $this->prod->produireEtats();
    expect($r2['rdv'])->toBe(0);
    expect(Notification::query()->where('type', 'RappelRendezVous')->count())->toBe(1);
});

it('§36 — deux RDV la même société le même jour produisent DEUX rappels (garde sur l id de l activité)', function () {
    $soc = Societe::factory()->create(['proprietaire_id' => $this->u->id]);
    Activite::factory()->count(2)->create([
        'societe_id' => $soc->id, 'type_id' => $this->rdvType,
        'utilisateur_id' => $this->u->id, 'debut_le' => now(),
    ]);

    expect($this->prod->produireEtats()['rdv'])->toBe(2);
});

it('§36 — un RDV d un AUTRE jour ne produit rien', function () {
    $soc = Societe::factory()->create(['proprietaire_id' => $this->u->id]);
    Activite::factory()->create([
        'societe_id' => $soc->id, 'type_id' => $this->rdvType,
        'utilisateur_id' => $this->u->id, 'debut_le' => now()->addDays(3),
    ]);

    expect($this->prod->produireEtats()['rdv'])->toBe(0);
});

it('§36 — une tâche en retard produit UN rappel, une fois PAR JOUR', function () {
    Tache::factory()->create([
        'assignee_id' => $this->u->id, 'statut' => 'AFaire', 'echeance_le' => now()->subDay(),
    ]);

    expect($this->prod->produireEtats()['taches'])->toBe(1);
    expect($this->prod->produireEtats()['taches'])->toBe(0); // pas deux fois le même jour
});

it('§36 — « tâche assignée » sur la même tâche ne masque PAS le retard (le type distingue)', function () {
    $tache = Tache::factory()->create([
        'assignee_id' => $this->u->id, 'statut' => 'AFaire', 'echeance_le' => now()->subDay(),
    ]);
    // Une notification d'événement existe déjà sur cette tâche.
    Notification::create([
        'utilisateur_id' => $this->u->id, 'type' => Notification::TACHE_ASSIGNEE,
        'titre' => 'Tâche assignée', 'cible_type' => 'Tache', 'cible_id' => $tache->id, 'cree_le' => now(),
    ]);

    // Le retard est quand même signalé — la garde porte sur le TYPE.
    expect($this->prod->produireEtats()['taches'])->toBe(1);
});

it('§36 — une tâche terminée ou non échue ne produit rien', function () {
    Tache::factory()->create(['assignee_id' => $this->u->id, 'statut' => 'Terminee', 'echeance_le' => now()->subDay(), 'terminee_le' => now()]);
    Tache::factory()->create(['assignee_id' => $this->u->id, 'statut' => 'AFaire', 'echeance_le' => now()->addDay()]);

    expect($this->prod->produireEtats()['taches'])->toBe(0);
});

it('§36 — la commande crmino:notifier tourne et rapporte', function () {
    Tache::factory()->create(['assignee_id' => $this->u->id, 'statut' => 'AFaire', 'echeance_le' => now()->subDay()]);

    $this->artisan('crmino:notifier')->assertSuccessful();
    expect(Notification::query()->where('type', 'TacheEnRetard')->count())->toBe(1);
});
