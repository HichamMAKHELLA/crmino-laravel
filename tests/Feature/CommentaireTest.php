<?php

declare(strict_types=1);

use App\Models\Commentaire;
use App\Models\Societe;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
    $this->commercial = crminoUtilisateur('COMMERCIAL');
    $this->societe = Societe::factory()->create(['proprietaire_id' => $this->commercial->id]);
});

it('§45 — ajoute une note à une fiche visible, avec trace', function () {
    $this->actingAs($this->commercial)->post('/commentaires', [
        'cible_type' => 'Societe', 'cible_id' => $this->societe->id, 'texte' => '  Client à rappeler  ',
    ]);

    $c = Commentaire::first();
    expect($c->texte)->toBe('Client à rappeler'); // élagué
    expect($c->auteur_id)->toBe($this->commercial->id);
    expect($c->modifie_le)->toBeNull(); // rien n'a encore bougé
    expect(\App\Models\AuditJournal::where('entite_type', 'Commentaire')->exists())->toBeTrue();
});

it('§45 — la note est gardée par la fiche parente : cible hors périmètre → 404', function () {
    $autre = crminoUtilisateur('COMMERCIAL');
    $sienne = Societe::factory()->create(['proprietaire_id' => $autre->id]);

    $this->actingAs($this->commercial)
        ->post('/commentaires', ['cible_type' => 'Societe', 'cible_id' => $sienne->id, 'texte' => 'x'])
        ->assertNotFound();
});

it('§45 — l auteur réécrit sa note ; modifie_le est posé, created_at ne bouge pas', function () {
    $c = Commentaire::factory()->create(['cible_type' => 'Societe', 'cible_id' => $this->societe->id, 'auteur_id' => $this->commercial->id, 'texte' => 'avant']);
    $creeInitial = $c->created_at;

    $this->actingAs($this->commercial)->put("/commentaires/{$c->id}", ['texte' => 'après']);

    $c->refresh();
    expect($c->texte)->toBe('après');
    expect($c->modifie_le)->not->toBeNull();
    expect($c->created_at->equalTo($creeInitial))->toBeTrue(); // garde sa place dans la discussion
});

it('§45 — un confrère ne réécrit PAS la note d autrui (404, pas 403)', function () {
    $autre = crminoUtilisateur('COMMERCIAL');
    $c = Commentaire::factory()->create(['cible_type' => 'Societe', 'cible_id' => $this->societe->id, 'auteur_id' => $autre->id, 'texte' => 'sienne']);

    $this->actingAs($this->commercial)
        ->put("/commentaires/{$c->id}", ['texte' => 'volée'])
        ->assertNotFound();

    expect($c->fresh()->texte)->toBe('sienne'); // inchangée
});

it('§45 — l auteur retire sa note (logique) ; un confrère ne peut pas (404)', function () {
    $autre = crminoUtilisateur('COMMERCIAL');
    $sienne = Commentaire::factory()->create(['cible_type' => 'Societe', 'cible_id' => $this->societe->id, 'auteur_id' => $this->commercial->id]);
    $autreNote = Commentaire::factory()->create(['cible_type' => 'Societe', 'cible_id' => $this->societe->id, 'auteur_id' => $autre->id]);

    $this->actingAs($this->commercial)->delete("/commentaires/{$sienne->id}");
    expect($sienne->fresh()->actif)->toBeFalse(); // désactivée, pas effacée

    $this->actingAs($this->commercial)->delete("/commentaires/{$autreNote->id}")->assertNotFound();
    expect($autreNote->fresh()->actif)->toBeTrue();
});
