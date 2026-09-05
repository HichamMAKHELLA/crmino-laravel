<?php

declare(strict_types=1);

use App\Models\Opportunite;
use App\Models\Referentiels\MotifPerte;
use App\Models\Societe;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
    $this->commercial = crminoUtilisateur('COMMERCIAL');
});

// ── Société §18 ──────────────────────────────────────────────────────────

it('§18 — modifie les champs et RECALCULE la normalisation §41', function () {
    $s = Societe::factory()->create(['proprietaire_id' => $this->commercial->id, 'raison_sociale' => 'Ancienne SARL']);

    $this->actingAs($this->commercial)->put("/societes/{$s->id}", [
        'raison_sociale' => 'Nouvelle Boîte SARL', 'email' => 'x@nouvelle.ma',
    ]);

    $s->refresh();
    expect($s->raison_sociale)->toBe('Nouvelle Boîte SARL');
    // §41 : la colonne normalisée suit (SARL retiré), sinon le doublon
    // trouverait encore l'ancienne raison sociale.
    expect($s->raison_sociale_normalisee)->toContain('NOUVELLE');
    expect($s->raison_sociale_normalisee)->not->toContain('ANCIENNE');
});

it('§18 — l ÉTAT et le PROPRIÉTAIRE sont EXCLUS du corps (routes/permissions dédiées)', function () {
    $autre = crminoUtilisateur('COMMERCIAL');
    $s = Societe::factory()->create(['proprietaire_id' => $this->commercial->id, 'etat' => 'Prospect']);

    $this->actingAs($this->commercial)->put("/societes/{$s->id}", [
        'raison_sociale' => 'X', 'etat' => 'Inactif', 'proprietaire_id' => $autre->id,
    ]);

    $s->refresh();
    expect($s->etat)->toBe('Prospect');                 // inchangé (§32 a sa route)
    expect($s->proprietaire_id)->toBe($this->commercial->id); // inchangé (§17 a sa route)
});

it('§58 — modifier une société hors périmètre → 404', function () {
    $autre = crminoUtilisateur('COMMERCIAL');
    $s = Societe::factory()->create(['proprietaire_id' => $autre->id]);

    $this->actingAs($this->commercial)->put("/societes/{$s->id}", ['raison_sociale' => 'X'])->assertNotFound();
});

// ── Opportunité §25 ──────────────────────────────────────────────────────

it('§25 — modifie l intitulé et le montant d une affaire ouverte', function () {
    $s = Societe::factory()->create(['proprietaire_id' => $this->commercial->id]);
    $o = Opportunite::factory()->create(['societe_id' => $s->id, 'proprietaire_id' => $this->commercial->id, 'statut' => 'Ouverte', 'montant_ht' => 100000]);

    $this->actingAs($this->commercial)->put("/opportunites/{$o->id}", ['intitule' => 'Affaire révisée', 'montant_ht' => 250000]);

    $o->refresh();
    expect($o->intitule)->toBe('Affaire révisée');
    expect((float) $o->montant_ht)->toBe(250000.0);
    expect((float) $o->montant_pondere)->toBe(250000.0 * $o->probabilite / 100); // pondéré généré suit
});

it('RG-OPP-007 — le montant d une affaire close ne se modifie plus', function () {
    $s = Societe::factory()->create(['proprietaire_id' => $this->commercial->id]);
    $motif = MotifPerte::query()->first();
    $o = Opportunite::factory()->create(['societe_id' => $s->id, 'proprietaire_id' => $this->commercial->id, 'statut' => 'Ouverte', 'montant_ht' => 100000]);
    $o->forceFill(['statut' => 'Perdue', 'motif_perte_id' => $motif->id, 'date_cloture' => now()])->save();

    $this->actingAs($this->commercial)
        ->put("/opportunites/{$o->id}", ['intitule' => 'X', 'montant_ht' => 999999])
        ->assertSessionHas('error');

    expect((float) $o->fresh()->montant_ht)->toBe(100000.0); // verrouillé
});

it('§25 — l intitulé d une affaire close reste corrigeable (montant inchangé)', function () {
    $s = Societe::factory()->create(['proprietaire_id' => $this->commercial->id]);
    $motif = MotifPerte::query()->first();
    $o = Opportunite::factory()->create(['societe_id' => $s->id, 'proprietaire_id' => $this->commercial->id, 'statut' => 'Ouverte', 'montant_ht' => 100000]);
    $o->forceFill(['statut' => 'Perdue', 'motif_perte_id' => $motif->id, 'date_cloture' => now()])->save();

    // Même montant → autorisé ; l'intitulé change.
    $this->actingAs($this->commercial)->put("/opportunites/{$o->id}", ['intitule' => 'Corrigé', 'montant_ht' => 100000]);

    expect($o->fresh()->intitule)->toBe('Corrigé');
});
