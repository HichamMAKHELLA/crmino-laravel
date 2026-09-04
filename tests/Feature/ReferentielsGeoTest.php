<?php

declare(strict_types=1);

use App\Models\Referentiels\FonctionContact;
use App\Models\Referentiels\Pays;
use App\Models\Referentiels\Region;
use App\Models\Referentiels\Ville;

beforeEach(function () {
    $this->seed(\Database\Seeders\ReferentielsGeoSeeder::class);
});

it('sème la géographie et les fonctions', function () {
    expect(Pays::query()->count())->toBe(7);
    expect(Region::query()->count())->toBe(12);
    expect(Ville::query()->count())->toBe(21);
    expect(FonctionContact::query()->count())->toBe(13);
});

it('relie la hiérarchie Pays -> Région -> Ville', function () {
    $casa = Ville::query()->where('code', 'CASABLANCA')->first();
    expect($casa->region->code)->toBe('MA_CASA');
    expect($casa->region->pays->code)->toBe('MA');
    expect($casa->region->pays->code_iso)->toBe('MA');
});

it('le Maroc porte ses 12 régions', function () {
    $maroc = Pays::query()->where('code', 'MA')->first();
    expect($maroc->regions()->count())->toBe(12);
    expect($maroc->systeme)->toBeTrue();
});
