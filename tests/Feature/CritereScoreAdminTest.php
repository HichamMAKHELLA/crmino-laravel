<?php

declare(strict_types=1);

use App\Models\Referentiels\TrancheBudget;
use App\Support\ScoreSuggestion;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
    $this->admin = crminoUtilisateur('ADMIN');
});

it('§15/§50 — le diagnostic somme les poids à 100 et tout est atteignable', function () {
    $d = app(ScoreSuggestion::class)->diagnostic();
    expect($d['total'])->toBe(100);
    expect($d['total_atteignable'])->toBe(100);
    expect($d['criteres'])->toHaveCount(8);
});

it('§15 — un critère sans vocabulaire est signalé HORS D ATTEINTE avec sa raison', function () {
    TrancheBudget::query()->update(['actif' => false]); // BUDGET (15) devient inévaluable

    $d = app(ScoreSuggestion::class)->diagnostic();
    $budget = collect($d['criteres'])->firstWhere('code', 'BUDGET');
    expect($budget['evaluable'])->toBeFalse();
    expect($budget['manque'])->toContain('tranches_budget'); // nomme le référentiel à rétablir
    expect($d['total_atteignable'])->toBe(85);              // le plafond baisse
});

it('§50 — l écran rend le diagnostic et exige referentiel.gerer', function () {
    $this->withoutVite();

    $this->actingAs($this->admin)->get('/criteres-score')
        ->assertInertia(fn (AssertableInertia $p) => $p
            ->component('CriteresScore/Index')
            ->where('total', 100)
            ->has('criteres', 8));

    $commercial = crminoUtilisateur('COMMERCIAL');
    $this->actingAs($commercial)->get('/criteres-score')->assertForbidden();
});
