<?php

declare(strict_types=1);

use App\Models\Opportunite;
use App\Models\Referentiels\MotifPerte;
use App\Models\Societe;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
    $this->commercial = crminoUtilisateur('COMMERCIAL');
    $this->societe = Societe::factory()->create(['proprietaire_id' => $this->commercial->id, 'etat' => 'Prospect']);
    $this->opp = Opportunite::factory()->create([
        'societe_id' => $this->societe->id,
        'proprietaire_id' => $this->commercial->id,
        'statut' => 'Ouverte',
        'montant_ht' => 200000,
        'probabilite' => 50,
    ]);
});

it('RG-OPP-003 — gagner rend la société cliente', function () {
    $this->actingAs($this->commercial)->post("/opportunites/{$this->opp->id}/gagner");

    expect($this->opp->fresh()->statut)->toBe('Gagnee');
    expect($this->societe->fresh()->etat)->toBe('Client');
    expect($this->societe->fresh()->devenu_client_le)->not->toBeNull();
});

it('le gain porte la probabilité à 100 (pondéré = montant)', function () {
    $this->actingAs($this->commercial)->post("/opportunites/{$this->opp->id}/gagner");

    $o = $this->opp->fresh();
    expect($o->probabilite)->toBe(100);
    expect((float) $o->montant_pondere)->toBe(200000.0);
});

it('RG-OPP-002 — perdre exige un motif', function () {
    $this->actingAs($this->commercial)
        ->post("/opportunites/{$this->opp->id}/perdre", [])
        ->assertSessionHasErrors('motif_perte_id');

    expect($this->opp->fresh()->statut)->toBe('Ouverte');
});

it('RG-OPP-002 — un motif à commentaire obligatoire exige le commentaire', function () {
    $concurrent = MotifPerte::query()->where('code', 'CONCURRENT')->first(); // commentaire obligatoire

    $this->actingAs($this->commercial)
        ->post("/opportunites/{$this->opp->id}/perdre", ['motif_perte_id' => $concurrent->id])
        ->assertSessionHasErrors('commentaire');
});

it('clôture en perte avec un motif valable', function () {
    $prix = MotifPerte::query()->where('code', 'PRIX')->first(); // pas de commentaire requis

    $this->actingAs($this->commercial)
        ->post("/opportunites/{$this->opp->id}/perdre", ['motif_perte_id' => $prix->id]);

    $o = $this->opp->fresh();
    expect($o->statut)->toBe('Perdue');
    expect($o->motif_perte_id)->toBe($prix->id);
    expect($o->date_cloture)->not->toBeNull();
});

it('RG-OPP-005 — une affaire close ne se referme pas', function () {
    $this->actingAs($this->commercial)->post("/opportunites/{$this->opp->id}/gagner");
    expect($this->opp->fresh()->statut)->toBe('Gagnee');

    // Tenter de perdre une affaire déjà gagnée : refus, statut inchangé.
    $prix = MotifPerte::query()->where('code', 'PRIX')->first();
    $this->actingAs($this->commercial)
        ->post("/opportunites/{$this->opp->id}/perdre", ['motif_perte_id' => $prix->id])
        ->assertSessionHas('error');

    expect($this->opp->fresh()->statut)->toBe('Gagnee');
});

it('refuse la clôture sans opportunite.cloturer (403)', function () {
    $sansRole = \App\Models\User::factory()->create(['role_id' => null, 'actif' => true]);

    $this->actingAs($sansRole)->post("/opportunites/{$this->opp->id}/gagner")->assertForbidden();
});

it('rend 404 pour une affaire hors périmètre (§58)', function () {
    $this->withoutVite();
    $autre = crminoUtilisateur('COMMERCIAL');
    $societeAutre = Societe::factory()->create(['proprietaire_id' => $autre->id]);
    $oppAutre = Opportunite::factory()->create(['societe_id' => $societeAutre->id, 'proprietaire_id' => $autre->id]);

    $this->actingAs($this->commercial)->get("/opportunites/{$oppAutre->id}")->assertNotFound();
});
