<?php

declare(strict_types=1);

use App\Models\Societe;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
    $this->commercial = crminoUtilisateur('COMMERCIAL');
});

it('numérote les sociétés en SOC-YYYY-00001 (§10)', function () {
    $annee = date('Y');
    $s1 = Societe::factory()->create();
    $s2 = Societe::factory()->create();

    expect($s1->numero)->toBe("SOC-{$annee}-00001");
    expect($s2->numero)->toBe("SOC-{$annee}-00002");
});

it('recalcule la raison sociale normalisée (§41)', function () {
    $s = Societe::factory()->create(['raison_sociale' => 'Société Cible SARL']);

    expect($s->raison_sociale_normalisee)->toBe('SOCIETE CIBLE');
});

it('refuse un visiteur sans societe.consulter (403)', function () {
    $sansRole = \App\Models\User::factory()->create(['role_id' => null, 'actif' => true]);

    $this->actingAs($sansRole)->get('/societes')->assertForbidden();
});

it('borne la liste au périmètre du commercial', function () {
    $this->withoutVite();
    $autre = crminoUtilisateur('COMMERCIAL');
    Societe::factory()->create(['proprietaire_id' => $this->commercial->id, 'raison_sociale' => 'La Mienne']);
    Societe::factory()->create(['proprietaire_id' => $autre->id, 'raison_sociale' => 'Étrangère']);

    $this->actingAs($this->commercial)
        ->get('/societes')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $p) => $p
            ->component('Societes/Index')
            ->has('societes.data', 1)
            ->where('societes.data.0.raison_sociale', 'La Mienne'));
});

it('un administrateur voit toutes les sociétés', function () {
    $admin = crminoUtilisateur('ADMIN');
    Societe::factory()->count(3)->create();

    expect(Societe::query()->dansPerimetre($admin, 'societe.consulter')->count())->toBe(3);
});
