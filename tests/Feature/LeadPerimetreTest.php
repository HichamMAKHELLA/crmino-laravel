<?php

declare(strict_types=1);

use App\Models\Equipe;
use App\Models\Lead;

/**
 * La preuve du périmètre (RG-HAB-001) sur un VRAI modèle : le trait AvecPerimetre
 * appliqué à Lead. Un périmètre qui fuit ne lève aucune erreur — seul un test le
 * voit. Le jeu d'essai fait DIVERGER les trois portées.
 */
beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);

    $this->equipe = Equipe::create(['code' => 'E1', 'libelle' => 'Équipe 1', 'actif' => true]);
    $this->autreEquipe = Equipe::create(['code' => 'E2', 'libelle' => 'Équipe 2', 'actif' => true]);

    // Un commercial (Siennes), un coéquipier, un responsable (Équipe), un admin (Toutes).
    $this->commercial = crminoUtilisateur('COMMERCIAL', $this->equipe->id);
    $this->coequipier = crminoUtilisateur('COMMERCIAL', $this->equipe->id);
    $this->responsable = crminoUtilisateur('RESP_COMM', $this->equipe->id);
    $this->admin = crminoUtilisateur('ADMIN');
    $this->etranger = crminoUtilisateur('COMMERCIAL', $this->autreEquipe->id);

    // L_MIEN : au commercial. L_EQUIPE : au coéquipier (même équipe). L_ETRANGER : hors équipe.
    $this->lMien = Lead::factory()->create(['proprietaire_id' => $this->commercial->id, 'equipe_id' => $this->equipe->id]);
    $this->lEquipe = Lead::factory()->create(['proprietaire_id' => $this->coequipier->id, 'equipe_id' => $this->equipe->id]);
    $this->lEtranger = Lead::factory()->create(['proprietaire_id' => $this->etranger->id, 'equipe_id' => $this->autreEquipe->id]);
});

function idsVus(\App\Models\User $u): array
{
    return Lead::query()->dansPerimetre($u, 'lead.consulter')->pluck('id')->sort()->values()->all();
}

it('Siennes — le commercial ne voit que ses propres leads', function () {
    expect(idsVus($this->commercial))->toBe([$this->lMien->id]);
});

it('Équipe — le responsable voit son équipe, pas les étrangers', function () {
    expect(idsVus($this->responsable))->toBe(
        collect([$this->lMien->id, $this->lEquipe->id])->sort()->values()->all()
    );
    expect(idsVus($this->responsable))->not->toContain($this->lEtranger->id);
});

it('Toutes — l administrateur voit tout', function () {
    expect(idsVus($this->admin))->toHaveCount(3);
});

it('RG-HAB-001 — les siennes TOUJOURS, même hors de son équipe', function () {
    // Un lead confié au responsable mais rattaché à une AUTRE équipe : il le voit
    // par la propriété (OU), jamais par l'équipe.
    $sien = Lead::factory()->create([
        'proprietaire_id' => $this->responsable->id,
        'equipe_id' => $this->autreEquipe->id,
    ]);

    expect(idsVus($this->responsable))->toContain($sien->id);
});
