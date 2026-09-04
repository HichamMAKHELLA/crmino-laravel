<?php

declare(strict_types=1);

use App\Models\Campagne;
use App\Models\Lead;
use App\Models\Opportunite;
use App\Models\Referentiels\TypeCampagne;
use App\Models\Societe;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
    $this->u = crminoUtilisateur('ADMIN'); // campagne.* accordées
    $this->type = TypeCampagne::query()->orderBy('ordre')->value('id');
});

function affaireGagnee(int $campagneId, int $proprio, float $montant): void
{
    $societe = Societe::factory()->create(['proprietaire_id' => $proprio]);
    Opportunite::factory()->create([
        'campagne_id' => $campagneId, 'societe_id' => $societe->id,
        'proprietaire_id' => $proprio, 'statut' => 'Gagnee', 'montant_ht' => $montant, 'date_cloture' => now(),
    ]);
}

it('crée une campagne numérotée CAM (§10)', function () {
    $annee = date('Y');
    $this->actingAs($this->u)->post('/campagnes', [
        'nom' => 'Salon Sage 2026', 'type_campagne_id' => $this->type,
        'date_debut' => '2026-01-01', 'responsable_id' => $this->u->id, 'budget' => 50000,
    ])->assertRedirect();

    $c = Campagne::first();
    expect($c->numero)->toBe("CAM-{$annee}-00001");
    expect($c->statut)->toBe('Planifiee');
});

it('§12 — calcule le retour à partir des affaires gagnées rattachées', function () {
    $this->withoutVite();
    $c = Campagne::factory()->create(['budget' => 12000, 'responsable_id' => $this->u->id, 'statut' => 'Planifiee']);
    affaireGagnee($c->id, $this->u->id, 30000);
    // Une affaire OUVERTE rattachée ne compte pas dans le CA gagné.
    $societe = Societe::factory()->create(['proprietaire_id' => $this->u->id]);
    Opportunite::factory()->create(['campagne_id' => $c->id, 'societe_id' => $societe->id, 'proprietaire_id' => $this->u->id, 'statut' => 'Ouverte', 'montant_ht' => 99999]);

    $this->actingAs($this->u)->get("/campagnes/{$c->id}")
        ->assertInertia(fn (AssertableInertia $p) => $p
            ->where('roi.ca_gagne', 30000)
            ->where('roi.nb_gagnees', 1)
            ->where('roi.retour', 2.5));
});

it('RG-IND-002 — sans budget, le retour n existe pas (≠ zéro)', function () {
    $this->withoutVite();
    $c = Campagne::factory()->create(['budget' => null, 'responsable_id' => $this->u->id]);
    affaireGagnee($c->id, $this->u->id, 40000);

    $this->actingAs($this->u)->get("/campagnes/{$c->id}")
        ->assertInertia(fn (AssertableInertia $p) => $p
            ->where('roi.retour', null)
            ->where('roi.ca_gagne', 40000));
});

it('le statut n arbitre pas — une campagne « Planifiée » compte ses leads', function () {
    $this->withoutVite();
    $c = Campagne::factory()->create(['responsable_id' => $this->u->id, 'statut' => 'Planifiee']);
    Lead::factory()->count(3)->create(['campagne_id' => $c->id, 'proprietaire_id' => $this->u->id]);

    $this->actingAs($this->u)->get("/campagnes/{$c->id}")
        ->assertInertia(fn (AssertableInertia $p) => $p->where('roi.nb_leads', 3));
});

it('refuse une date de fin antérieure au début', function () {
    $this->actingAs($this->u)->post('/campagnes', [
        'nom' => 'X', 'type_campagne_id' => $this->type, 'date_debut' => '2026-06-01',
        'date_fin' => '2026-05-01', 'responsable_id' => $this->u->id,
    ])->assertSessionHasErrors('date_fin');
});

it('refuse la consultation sans campagne.consulter (403)', function () {
    $sansRole = \App\Models\User::factory()->create(['role_id' => null, 'actif' => true]);

    $this->actingAs($sansRole)->get('/campagnes')->assertForbidden();
});
