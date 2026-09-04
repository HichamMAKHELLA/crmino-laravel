<?php

declare(strict_types=1);

use App\Models\Lead;
use App\Support\Numerotation;

beforeEach(function () {
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
});

it('génère un numéro de séquence croissant à la création (§10)', function () {
    $annee = date('Y');
    $l1 = Lead::factory()->create();
    $l2 = Lead::factory()->create();

    expect($l1->numero)->toBe("LEAD-{$annee}-00001");
    expect($l2->numero)->toBe("LEAD-{$annee}-00002");
});

it('rend un numéro unique — aucune collision', function () {
    $numeros = collect(range(1, 5))->map(fn () => Lead::factory()->create()->numero);

    expect($numeros->unique())->toHaveCount(5);
});

it('compose au format PREFIXE-ANNEE-SEQUENCE sur 5 chiffres', function () {
    expect(Numerotation::composer('LEAD', 2026, 42))->toBe('LEAD-2026-00042');
});

it('refuse une séquence inférieure à 1', function () {
    Numerotation::composer('LEAD', 2026, 0);
})->throws(InvalidArgumentException::class);
