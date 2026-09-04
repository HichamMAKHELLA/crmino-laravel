<?php

declare(strict_types=1);

use App\Models\Lead;
use App\Support\DetecteurDoublons;

beforeEach(function () {
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
    $this->detecteur = new DetecteurDoublons;

    // Fiche de référence. Le hook de sauvegarde calcule les colonnes §41.
    $this->lead = Lead::factory()->create([
        'raison_sociale' => 'Société Exemple SARL',
        'ice' => '001234567890123',
        'site_web' => 'https://www.exemple.ma',
    ]);
});

it('recalcule les colonnes §41 à la création', function () {
    expect($this->lead->raison_sociale_normalisee)->toBe('SOCIETE EXEMPLE');
    expect($this->lead->domaine_web)->toBe('exemple.ma');
});

it('recalcule les colonnes §41 à la modification (renommage)', function () {
    $this->lead->update(['raison_sociale' => 'Autre Nom SA']);

    expect($this->lead->fresh()->raison_sociale_normalisee)->toBe('AUTRE NOM');
});

it('rapproche par raison sociale normalisée en préfixe', function () {
    $r = $this->detecteur->rechercher('societe exemple', null, null);

    expect($r)->toHaveCount(1);
    expect($r[0]->critere)->toBe('Raison sociale proche');
    expect($r[0]->poids)->toBe(50);
});

it('rapproche par ICE exact', function () {
    $r = $this->detecteur->rechercher(null, '001234567890123', null);

    expect($r)->toHaveCount(1);
    expect($r[0]->critere)->toBe('ICE identique');
    expect($r[0]->poids)->toBe(100);
});

it('rapproche par domaine Internet', function () {
    $r = $this->detecteur->rechercher(null, null, 'contact@exemple.ma');

    expect($r)->toHaveCount(1);
    expect($r[0]->critere)->toBe('Même domaine Internet');
});

it('garde le critère le plus fort quand plusieurs jouent', function () {
    $r = $this->detecteur->rechercher('societe exemple', '001234567890123', 'https://exemple.ma');

    expect($r)->toHaveCount(1);
    expect($r[0]->critere)->toBe('ICE identique'); // 100 l'emporte sur 60 et 50
});

it('le préfixe est asymétrique : l existant commence par le candidat', function () {
    // « societe exemple maroc atlas » -> l'existant « SOCIETE EXEMPLE » ne
    // commence PAS par ce candidat plus long : aucun rapprochement.
    expect($this->detecteur->rechercher('societe exemple maroc atlas', null, null))->toBe([]);

    // L'inverse rapproche : le candidat court est préfixe de l'existant.
    Lead::factory()->create(['raison_sociale' => 'Société Exemple Maroc']);
    $r = $this->detecteur->rechercher('societe exemple', null, null);
    expect($r)->toHaveCount(2);
});

it('exclut la fiche elle-même en modification', function () {
    $r = $this->detecteur->rechercher('societe exemple', '001234567890123', null, $this->lead->id);

    expect($r)->toBe([]);
});

it('rend une liste vide sans critère exploitable', function () {
    expect($this->detecteur->rechercher(null, null, null))->toBe([]);
    expect($this->detecteur->rechercher('  ', '  ', 'bonjour'))->toBe([]);
});
