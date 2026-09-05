<?php

declare(strict_types=1);

use App\Models\Opportunite;
use App\Models\Outbox;
use App\Models\Societe;
use App\Support\ClotureOpportunite;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
    $this->commercial = crminoUtilisateur('COMMERCIAL');
    $this->societe = Societe::factory()->create(['proprietaire_id' => $this->commercial->id, 'etat' => 'Prospect']);
    $this->opp = Opportunite::factory()->create([
        'societe_id' => $this->societe->id, 'proprietaire_id' => $this->commercial->id,
        'statut' => 'Ouverte', 'montant_ht' => 120000, 'probabilite' => 40,
    ]);
});

it('§48 — gagner dépose UN message Sage, avec la charge attendue', function () {
    app(ClotureOpportunite::class)->gagner($this->opp, $this->commercial);

    $messages = Outbox::query()->where('type', 'OpportuniteGagnee')->get();
    expect($messages)->toHaveCount(1);

    $charge = json_decode($messages->first()->charge, true);
    expect($charge['opportunite_id'])->toBe($this->opp->id);
    expect($charge['societe_id'])->toBe($this->societe->id);
    expect($messages->first()->etat)->toBe('EnAttente');
});

it('§48 — la PERTE n envoie rien à Sage', function () {
    $motif = \App\Models\Referentiels\MotifPerte::query()->first();
    app(ClotureOpportunite::class)->perdre($this->opp, $motif->id, null, $this->commercial);

    expect(Outbox::query()->count())->toBe(0);
});

it('§48 — le message partage le sort du gain : une annulation ne laisse rien', function () {
    // Le gain gère sa propre transaction ; imbriqué dans une transaction externe
    // annulée, tout retombe — le message ne survit pas à l'échec (savepoint).
    try {
        DB::transaction(function () {
            app(ClotureOpportunite::class)->gagner($this->opp, $this->commercial);
            throw new RuntimeException('échec après le dépôt');
        });
    } catch (RuntimeException) {
        // attendu
    }

    expect(Outbox::query()->count())->toBe(0);
    expect($this->opp->fresh()->statut)->toBe('Ouverte'); // rien n'a été validé
});
