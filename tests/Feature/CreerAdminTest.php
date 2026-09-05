<?php

declare(strict_types=1);

use App\Models\User;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
});

it('§5 — crée le premier administrateur avec un mot de passe haché', function () {
    $this->artisan('crmino:creer-admin', [
        'email' => 'admin@imrasoft.ma', '--prenom' => 'Hicham', '--nom' => 'Makhloufi',
        '--mot-de-passe' => 'MotDePasseSolide1',
    ])->assertSuccessful();

    $u = User::query()->where('email', 'admin@imrasoft.ma')->first();
    expect($u)->not->toBeNull();
    expect($u->actif)->toBeTrue();
    expect(\Illuminate\Support\Facades\Hash::check('MotDePasseSolide1', $u->password))->toBeTrue();
    expect($u->peut('utilisateur.gerer'))->toBeTrue(); // rôle ADMIN
});

it('§5 — refuse un mot de passe trop court', function () {
    $this->artisan('crmino:creer-admin', ['email' => 'a@b.ma', '--mot-de-passe' => 'court'])
        ->assertFailed();

    expect(User::query()->where('email', 'a@b.ma')->exists())->toBeFalse();
});

it('§5 — n écrase pas un compte existant', function () {
    User::factory()->create(['email' => 'admin@imrasoft.ma']);

    $this->artisan('crmino:creer-admin', ['email' => 'admin@imrasoft.ma', '--mot-de-passe' => 'MotDePasseSolide1'])
        ->assertFailed();
});
