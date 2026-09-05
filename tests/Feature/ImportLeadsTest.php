<?php

declare(strict_types=1);

use App\Models\ImportLot;
use App\Models\Lead;
use App\Models\Referentiels\Source;
use App\Support\Import\ImportLeads;
use App\Support\Import\LectureTabulee;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
    $this->admin = crminoUtilisateur('ADMIN'); // import.executer
    $this->source = Source::query()->where('actif', true)->first();
});

function lignesCsv(string $csv): array
{
    return LectureTabulee::analyser($csv)['lignes'];
}

it('§42 — LectureTabulee détecte le séparateur et lit les en-têtes', function () {
    $r = LectureTabulee::analyser("raison_sociale;telephone\nAlpha SARL;0522000000");
    expect($r['separateur'])->toBe(';');
    expect($r['entetes'])->toContain('raison_sociale');
    expect($r['lignes'][0]['raison_sociale'])->toBe('Alpha SARL');
});

it('§42 — l aperçu CLASSE sans rien écrire (deux temps)', function () {
    $lignes = lignesCsv("raison_sociale,telephone\nAlpha SARL,0522111111\n,\nBeta SA,0522222222");
    $apercu = app(ImportLeads::class)->apercu($lignes);

    expect($apercu['total'])->toBe(3);
    expect($apercu['importables'])->toBe(2);
    expect($apercu['rejetees'])->toBe(1);       // la ligne sans identité ni contact
    expect(Lead::query()->count())->toBe(0);    // RIEN n'est écrit
});

it('§42 — l import crée les leads et journalise un lot Termine', function () {
    $csv = "raison_sociale,telephone,nom_contact\nAlpha SARL,0522111111,Idrissi\nBeta SA,0522222222,Benali";
    $lot = app(ImportLeads::class)->importer($this->admin, lignesCsv($csv), $this->source->id, null, 'prospects.csv');

    expect($lot->statut)->toBe('Termine');
    expect($lot->lignes_importees)->toBe(2);
    expect(Lead::query()->count())->toBe(2);
    expect($lot->lignes()->where('resultat', 'Importee')->count())->toBe(2);
});

it('§42 — une ligne malformée est REJETÉE (RG-LEA-001), pas importée', function () {
    $csv = "raison_sociale,telephone\nSansContact,\nAvecTel,0522333333";
    $lot = app(ImportLeads::class)->importer($this->admin, lignesCsv($csv), $this->source->id, null, 'x.csv');

    expect($lot->lignes_importees)->toBe(1);
    expect($lot->lignes_rejetees)->toBe(1);
    // La ligne rejetée garde son CONTENU brut pour expliquer le rejet.
    $rejet = $lot->lignes()->where('resultat', 'Rejetee')->first();
    expect($rejet->contenu)->toContain('SansContact');
    expect($rejet->motif)->not->toBeNull();
});

it('§42 — un doublon §41 n est PAS importé, il est reconnu', function () {
    // Une fiche existante avec le même ICE : la seconde ligne est un doublon.
    Lead::factory()->create(['raison_sociale' => 'Déjà là', 'ice' => '001122334455667', 'proprietaire_id' => $this->admin->id]);

    $csv = "raison_sociale,ice,telephone\nNouvelle,000000000000000,0522444444\nMême boîte,001122334455667,0522555555";
    $lot = app(ImportLeads::class)->importer($this->admin, lignesCsv($csv), $this->source->id, null, 'x.csv');

    expect($lot->lignes_importees)->toBe(1);
    expect($lot->lignes_doublons)->toBe(1);
});

it('§42 — l import est ATOMIQUE : un échec ne laisse aucun lead, mais un lot Echoue', function () {
    // La 2e ligne porte un ICE de 20 caractères (colonne = 15) : l'insertion du
    // lead échoue en cours de transaction, tout est annulé.
    $csv = "raison_sociale,ice,telephone\nAlpha,000000000000000,0522111111\nBeta,01234567890123456789,0522222222";

    try {
        app(ImportLeads::class)->importer($this->admin, lignesCsv($csv), $this->source->id, null, 'x.csv');
    } catch (\Throwable) {
        // attendu
    }

    expect(Lead::query()->count())->toBe(0);            // même Alpha n'est pas resté (atomique)
    $lot = ImportLot::query()->where('statut', 'Echoue')->first();
    expect($lot)->not->toBeNull();                       // mais la trace existe
});

it('§42 — la route exige import.executer (403 pour un commercial)', function () {
    $commercial = crminoUtilisateur('COMMERCIAL');
    $this->actingAs($commercial)->get('/import')->assertForbidden();
});
