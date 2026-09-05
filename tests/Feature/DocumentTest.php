<?php

declare(strict_types=1);

use App\Models\Document;
use App\Models\Referentiels\TypeDocument;
use App\Models\Societe;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
    $this->commercial = crminoUtilisateur('COMMERCIAL'); // document.* en Siennes
    $this->societe = Societe::factory()->create(['proprietaire_id' => $this->commercial->id]);
});

it('§44 — dépose un document ; le nom d origine ne compose PAS le nom de stockage (§58)', function () {
    $devis = TypeDocument::query()->where('code', 'DEVIS')->value('id');
    $fichier = UploadedFile::fake()->create('Devis client.pdf', 40, 'application/pdf');

    $this->actingAs($this->commercial)->post('/documents', [
        'cible_type' => 'Societe', 'cible_id' => $this->societe->id,
        'type_document_id' => $devis, 'fichier' => $fichier,
    ]);

    $doc = Document::first();
    expect($doc->nom)->toBe('Devis client.pdf');           // affichage
    expect($doc->nom_stockage)->not->toContain('Devis');   // engendré, sans lien avec l'origine
    expect($doc->nom_stockage)->toEndWith('.pdf');
    Storage::disk('local')->assertExists($doc->chemin_stockage);
});

it('§58 — refuse une extension hors liste blanche', function () {
    $fichier = UploadedFile::fake()->create('malveillant.exe', 10);

    $this->actingAs($this->commercial)->post('/documents', [
        'cible_type' => 'Societe', 'cible_id' => $this->societe->id, 'fichier' => $fichier,
    ])->assertStatus(422);

    expect(Document::query()->count())->toBe(0);
});

it('§58 — cible hors périmètre → 404 (aucun fichier orphelin écrit)', function () {
    $autre = crminoUtilisateur('COMMERCIAL');
    $sienne = Societe::factory()->create(['proprietaire_id' => $autre->id]);

    $this->actingAs($this->commercial)->post('/documents', [
        'cible_type' => 'Societe', 'cible_id' => $sienne->id,
        'fichier' => UploadedFile::fake()->create('x.pdf', 10),
    ])->assertNotFound();

    expect(Document::query()->count())->toBe(0);
});

it('§44 — télécharge en pièce jointe, type MIME neutre', function () {
    $this->actingAs($this->commercial)->post('/documents', [
        'cible_type' => 'Societe', 'cible_id' => $this->societe->id,
        'fichier' => UploadedFile::fake()->create('note.pdf', 10),
    ]);
    $doc = Document::first();

    $r = $this->actingAs($this->commercial)->get("/documents/{$doc->id}/telecharger");
    $r->assertOk();
    expect($r->headers->get('content-type'))->toContain('application/octet-stream');
    expect($r->headers->get('content-disposition'))->toContain('attachment');
});

it('§47 — le retrait est LOGIQUE : l octet reste, la ligne se désactive', function () {
    $this->actingAs($this->commercial)->post('/documents', [
        'cible_type' => 'Societe', 'cible_id' => $this->societe->id,
        'fichier' => UploadedFile::fake()->create('note.pdf', 10),
    ]);
    $doc = Document::first();

    // document.supprimer n'est pas donné au commercial : un ADMIN retire.
    $this->actingAs(crminoUtilisateur('ADMIN'))->delete("/documents/{$doc->id}");

    expect($doc->fresh()->actif)->toBeFalse();
    Storage::disk('local')->assertExists($doc->chemin_stockage); // l'octet reste (§47)
});

it('§44 — le reclassement est gardé par document.deposer, pas par le retrait', function () {
    $this->actingAs($this->commercial)->post('/documents', [
        'cible_type' => 'Societe', 'cible_id' => $this->societe->id,
        'fichier' => UploadedFile::fake()->create('note.pdf', 10),
    ]);
    $doc = Document::first();
    $contrat = TypeDocument::query()->where('code', 'CONTRAT')->value('id');

    // Un rôle qui peut supprimer mais PAS déposer ne reclasse pas.
    $role = \App\Models\Role::query()->create(['code' => 'SUPPR_SEUL', 'libelle' => 'x', 'actif' => true]);
    \Illuminate\Support\Facades\DB::table('role_permissions')->insert([
        ['role_id' => $role->id, 'permission_code' => 'document.supprimer', 'portee' => 'Toutes'],
        ['role_id' => $role->id, 'permission_code' => 'societe.consulter', 'portee' => 'Toutes'],
    ]);
    $u = \App\Models\User::factory()->create(['role_id' => $role->id, 'actif' => true]);

    $this->actingAs($u)->put("/documents/{$doc->id}", ['nom' => 'Volé', 'type_document_id' => $contrat])
        ->assertForbidden();

    expect($doc->fresh()->nom)->toBe('note.pdf');
});
