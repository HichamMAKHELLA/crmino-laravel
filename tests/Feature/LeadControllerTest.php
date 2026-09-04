<?php

declare(strict_types=1);

use App\Models\Equipe;
use App\Models\Lead;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
});

it('refuse un visiteur anonyme', function () {
    $this->get('/leads')->assertRedirect('/login');
});

it('refuse un utilisateur sans lead.consulter (403)', function () {
    // Un compte sans rôle : porteePour rend Aucune, peut() rend faux.
    $sansRole = \App\Models\User::factory()->create(['role_id' => null, 'actif' => true]);

    $this->actingAs($sansRole)->get('/leads')->assertForbidden();
});

it('rend Leads/Index borné au périmètre du commercial', function () {
    $equipe = Equipe::create(['code' => 'E1', 'libelle' => 'Équipe 1', 'actif' => true]);
    $commercial = crminoUtilisateur('COMMERCIAL', $equipe->id);
    $autre = crminoUtilisateur('COMMERCIAL', $equipe->id);

    $lMien = Lead::factory()->create(['proprietaire_id' => $commercial->id, 'equipe_id' => $equipe->id]);
    Lead::factory()->create(['proprietaire_id' => $autre->id, 'equipe_id' => $equipe->id]);

    $this->withoutVite();

    $this->actingAs($commercial)
        ->get('/leads')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Leads/Index')
            ->has('leads.data', 1)
            ->where('leads.data.0.numero', $lMien->numero)
        );
});
