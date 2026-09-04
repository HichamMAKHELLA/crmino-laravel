<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Models\Lead;
use App\Support\DetecteurDoublons;

beforeEach(function () {
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
    $this->detecteur = new DetecteurDoublons;

    // Fiche de référence + son contact. Les hooks calculent les colonnes §41.
    $this->lead = Lead::factory()->create([
        'raison_sociale' => 'Société Exemple SARL',
        'ice' => '001234567890123',
        'site_web' => 'https://www.exemple.ma',
    ]);
    Contact::factory()->create([
        'lead_id' => $this->lead->id,
        'gsm' => '06 12 34 56 78',
        'email' => 'contact@exemple.ma',
    ]);
});

it('recalcule les colonnes §41 à la création', function () {
    expect($this->lead->raison_sociale_normalisee)->toBe('SOCIETE EXEMPLE');
    expect($this->lead->domaine_web)->toBe('exemple.ma');
    expect(Contact::first()->gsm_normalise)->toBe('0612345678');
});

it('recalcule les colonnes §41 à la modification (renommage)', function () {
    $this->lead->update(['raison_sociale' => 'Autre Nom SA']);

    expect($this->lead->fresh()->raison_sociale_normalisee)->toBe('AUTRE NOM');
});

it('rapproche par raison sociale normalisée en préfixe', function () {
    $r = $this->detecteur->rechercher('societe exemple', null, null, null, null);

    expect($r)->toHaveCount(1);
    expect($r[0]->critere)->toBe('Raison sociale proche');
    expect($r[0]->poids)->toBe(50);
});

it('rapproche par ICE exact', function () {
    $r = $this->detecteur->rechercher(null, '001234567890123', null, null, null);

    expect($r)->toHaveCount(1);
    expect($r[0]->critere)->toBe('ICE identique');
    expect($r[0]->poids)->toBe(100);
});

it('rapproche par téléphone via le contact', function () {
    $r = $this->detecteur->rechercher(null, null, '0612345678', null, null);

    expect($r)->toHaveCount(1);
    expect($r[0]->critere)->toBe('Téléphone identique');
    expect($r[0]->poids)->toBe(80);
});

it('rapproche par courriel via le contact', function () {
    $r = $this->detecteur->rechercher(null, null, null, 'contact@exemple.ma', null);

    expect($r)->toHaveCount(1);
    // 80 (courriel) l'emporte sur 60 (même domaine, déduit du courriel).
    expect($r[0]->critere)->toBe('Courriel identique');
});

it('rapproche par domaine Internet', function () {
    $r = $this->detecteur->rechercher(null, null, null, null, 'https://exemple.ma');

    expect($r)->toHaveCount(1);
    expect($r[0]->critere)->toBe('Même domaine Internet');
});

it('garde le critère le plus fort quand plusieurs jouent', function () {
    $r = $this->detecteur->rechercher('societe exemple', '001234567890123', null, null, 'https://exemple.ma');

    expect($r)->toHaveCount(1);
    expect($r[0]->critere)->toBe('ICE identique'); // 100 l'emporte
});

it('le préfixe est asymétrique : l existant commence par le candidat', function () {
    expect($this->detecteur->rechercher('societe exemple maroc atlas', null, null, null, null))->toBe([]);

    Lead::factory()->create(['raison_sociale' => 'Société Exemple Maroc']);
    expect($this->detecteur->rechercher('societe exemple', null, null, null, null))->toHaveCount(2);
});

it('exclut la fiche elle-même en modification', function () {
    $r = $this->detecteur->rechercher('societe exemple', '001234567890123', null, null, null, $this->lead->id);

    expect($r)->toBe([]);
});

it('rend une liste vide sans critère exploitable', function () {
    expect($this->detecteur->rechercher(null, null, null, null, null))->toBe([]);
    expect($this->detecteur->rechercher('  ', '  ', null, null, 'bonjour'))->toBe([]);
});
