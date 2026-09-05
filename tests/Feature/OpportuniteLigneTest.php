<?php

declare(strict_types=1);

use App\Models\Opportunite;
use App\Models\Produit;
use App\Models\Referentiels\MotifPerte;
use App\Models\Societe;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
    $this->seed(\Database\Seeders\CatalogueSeeder::class);
    $this->commercial = crminoUtilisateur('COMMERCIAL');
    $this->societe = Societe::factory()->create(['proprietaire_id' => $this->commercial->id, 'etat' => 'Prospect']);
    $this->opp = Opportunite::factory()->create([
        'societe_id' => $this->societe->id,
        'proprietaire_id' => $this->commercial->id,
        'statut' => 'Ouverte',
    ]);
    $this->produit = Produit::factory()->create(['designation' => 'Sage Compta']);
});

it('§28 — enregistre les lignes et le montant HT est GÉNÉRÉ (quantité × prix)', function () {
    $this->actingAs($this->commercial)->post("/opportunites/{$this->opp->id}/lignes", [
        'lignes' => [
            ['produit_id' => $this->produit->id, 'designation' => 'Sage Compta', 'quantite' => 3, 'prix_unitaire' => 5000],
            ['produit_id' => null, 'designation' => 'Prestation', 'quantite' => 2, 'prix_unitaire' => 1500],
        ],
    ]);

    $lignes = $this->opp->fresh()->lignes()->orderBy('ordre')->get();
    expect($lignes)->toHaveCount(2);
    expect((float) $lignes[0]->montant_ht)->toBe(15000.0); // jamais écrit par l'app
    expect((float) $lignes[1]->montant_ht)->toBe(3000.0);
    expect($lignes[1]->produit_id)->toBeNull(); // la ligne libre reste possible (§28)
});

it('§28 — remplace TOUTES les lignes (l\'envoi est l\'état entier)', function () {
    $this->opp->lignes()->create(['designation' => 'Ancienne', 'quantite' => 1, 'prix_unitaire' => 999, 'ordre' => 0]);

    $this->actingAs($this->commercial)->post("/opportunites/{$this->opp->id}/lignes", [
        'lignes' => [['produit_id' => null, 'designation' => 'Nouvelle', 'quantite' => 1, 'prix_unitaire' => 100]],
    ]);

    $lignes = $this->opp->fresh()->lignes;
    expect($lignes)->toHaveCount(1);
    expect($lignes[0]->designation)->toBe('Nouvelle');
});

it('RG-OPP-006 — une affaire close ne reçoit plus de lignes', function () {
    $motif = MotifPerte::query()->first();
    $this->opp->forceFill(['statut' => 'Perdue', 'motif_perte_id' => $motif->id, 'date_cloture' => now()])->save();

    $this->actingAs($this->commercial)
        ->post("/opportunites/{$this->opp->id}/lignes", [
            'lignes' => [['produit_id' => null, 'designation' => 'X', 'quantite' => 1, 'prix_unitaire' => 100]],
        ])
        ->assertSessionHas('error');

    expect($this->opp->fresh()->lignes)->toHaveCount(0);
});

it('§58 — hors périmètre → 404, jamais 403', function () {
    $autre = crminoUtilisateur('COMMERCIAL');
    $sienne = Societe::factory()->create(['proprietaire_id' => $autre->id]);
    $opp = Opportunite::factory()->create(['societe_id' => $sienne->id, 'proprietaire_id' => $autre->id, 'statut' => 'Ouverte']);

    $this->actingAs($this->commercial)
        ->post("/opportunites/{$opp->id}/lignes", ['lignes' => []])
        ->assertNotFound();
});
