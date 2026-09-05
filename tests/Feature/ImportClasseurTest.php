<?php

declare(strict_types=1);

use App\Models\ImportLot;
use App\Models\Lead;
use App\Models\Referentiels\Source;
use App\Support\Import\ImportLeads;
use App\Support\Import\LectureClasseur;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as EcrivainXlsx;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
    $this->admin = crminoUtilisateur('ADMIN');
    $this->source = Source::query()->where('actif', true)->first();
});

/** Fabrique un .xlsx sur disque et rend son chemin. */
function classeurEssai(): string
{
    $classeur = new Spreadsheet();
    $f = $classeur->getActiveSheet();
    $f->fromArray(['raison_sociale', 'telephone', 'nom_contact'], null, 'A1');
    $f->fromArray(['Alpha SARL', '0522111111', 'Idrissi'], null, 'A2');
    // Une cellule FORMULE : on doit lire la valeur calculée, pas « =CONCATENATE… ».
    $f->setCellValue('A3', 'Beta SA');
    $f->setCellValue('B3', '=CONCATENATE("0522","222222")');
    $f->setCellValue('C3', 'Benali');

    $chemin = tempnam(sys_get_temp_dir(), 'crmino').'.xlsx';
    (new EcrivainXlsx($classeur))->save($chemin);
    $classeur->disconnectWorksheets();

    return $chemin;
}

it('§42 — LectureClasseur lit la première feuille et rend la MÊME forme que le CSV', function () {
    $chemin = classeurEssai();
    $r = LectureClasseur::analyser($chemin);
    @unlink($chemin);

    expect($r['separateur'])->toBe('xlsx');
    expect($r['entetes'])->toContain('raison_sociale');
    expect($r['lignes'])->toHaveCount(2);
    expect($r['lignes'][0]['raison_sociale'])->toBe('Alpha SARL');
});

it('§42 — une cellule FORMULE rend sa valeur calculée, jamais son texte', function () {
    $chemin = classeurEssai();
    $r = LectureClasseur::analyser($chemin);
    @unlink($chemin);

    // La 2e ligne porte le téléphone issu de la formule.
    expect($r['lignes'][1]['telephone'])->toBe('0522222222');
    expect($r['lignes'][1]['telephone'])->not->toContain('CONCATENATE');
});

it('§42 — un classeur s importe comme un CSV et laisse un lot Termine', function () {
    $chemin = classeurEssai();
    $lignes = LectureClasseur::analyser($chemin)['lignes'];
    @unlink($chemin);

    $lot = app(ImportLeads::class)->importer($this->admin, $lignes, $this->source->id, null, 'prospects.xlsx');

    expect($lot->statut)->toBe('Termine');
    expect($lot->lignes_importees)->toBe(2);
    expect(Lead::query()->count())->toBe(2);
});
