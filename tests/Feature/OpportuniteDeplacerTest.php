<?php

declare(strict_types=1);

use App\Models\Opportunite;
use App\Models\Referentiels\EtapePipeline;
use App\Models\Referentiels\MotifPerte;
use App\Models\Societe;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
    $this->commercial = crminoUtilisateur('COMMERCIAL');
    $this->societe = Societe::factory()->create(['proprietaire_id' => $this->commercial->id]);
    $this->ouvertes = EtapePipeline::query()->where('categorie', 'Ouverte')->orderBy('ordre')->get();
    $this->depart = $this->ouvertes->first();
    $this->cible = $this->ouvertes->get(1);
    $this->opp = Opportunite::factory()->create([
        'societe_id' => $this->societe->id, 'proprietaire_id' => $this->commercial->id,
        'statut' => 'Ouverte', 'etape_id' => $this->depart->id,
    ]);
});

it('§64 — déplace une affaire ouverte vers une autre étape ouverte', function () {
    $this->actingAs($this->commercial)->post("/opportunites/{$this->opp->id}/etape", ['etape_id' => $this->cible->id]);

    expect($this->opp->fresh()->etape_id)->toBe($this->cible->id);
});

it('§64 — le déplacement ne réécrit PAS la probabilité surchargée (RG-OPP-004)', function () {
    $this->opp->forceFill(['probabilite' => 33])->save(); // surchargée, != proba des étapes

    $this->actingAs($this->commercial)->post("/opportunites/{$this->opp->id}/etape", ['etape_id' => $this->cible->id]);

    expect($this->opp->fresh()->probabilite)->toBe(33);
});

it('RG-OPP-005 — une affaire close ne change plus d étape', function () {
    $motif = MotifPerte::query()->first();
    $this->opp->forceFill(['statut' => 'Perdue', 'motif_perte_id' => $motif->id, 'date_cloture' => now()])->save();

    $this->actingAs($this->commercial)
        ->post("/opportunites/{$this->opp->id}/etape", ['etape_id' => $this->cible->id])
        ->assertSessionHas('error');

    expect($this->opp->fresh()->etape_id)->toBe($this->depart->id); // inchangée
});

it('§64 — on ne dépose PAS sur une étape close (gain/perte ont leurs routes)', function () {
    $close = EtapePipeline::query()->where('categorie', 'Gagnee')->first();

    $this->actingAs($this->commercial)
        ->post("/opportunites/{$this->opp->id}/etape", ['etape_id' => $close->id])
        ->assertSessionHas('error');

    expect($this->opp->fresh()->etape_id)->toBe($this->depart->id);
});

it('§58 — hors périmètre → 404', function () {
    $autre = crminoUtilisateur('COMMERCIAL');
    $sienne = Societe::factory()->create(['proprietaire_id' => $autre->id]);
    $opp = Opportunite::factory()->create(['societe_id' => $sienne->id, 'proprietaire_id' => $autre->id, 'statut' => 'Ouverte', 'etape_id' => $this->depart->id]);

    $this->actingAs($this->commercial)
        ->post("/opportunites/{$opp->id}/etape", ['etape_id' => $this->cible->id])
        ->assertNotFound();
});
