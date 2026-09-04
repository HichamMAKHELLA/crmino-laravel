<?php

declare(strict_types=1);

use App\Enums\Portee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
});

function porteeDe(string $roleCode, string $permission): ?string
{
    $roleId = Role::query()->where('code', $roleCode)->value('id');

    return DB::table('role_permissions')
        ->where('role_id', $roleId)
        ->where('permission_code', $permission)
        ->value('portee');
}

it('sème les 53 permissions, dont exactement 8 éteintes (§68)', function () {
    expect(Permission::query()->count())->toBe(53);

    $inactives = Permission::query()->where('actif', false)->pluck('code')->sort()->values()->all();
    expect($inactives)->toBe(collect([
        'lead.supprimer', 'societe.supprimer', 'opportunite.supprimer',
        'activite.supprimer', 'tache.supprimer', 'campagne.supprimer',
        'societe.creer', 'parametre.gerer',
    ])->sort()->values()->all());

    // contact.supprimer et document.supprimer RESTENT actives (§68).
    expect(Permission::query()->find('contact.supprimer')->actif)->toBeTrue();
    expect(Permission::query()->find('document.supprimer')->actif)->toBeTrue();
});

it('pose une ligne par couple rôle × permission (RG-HAB-003)', function () {
    expect(DB::table('role_permissions')->count())->toBe(4 * 53);

    // Aucun rôle sans permission (garde de cohérence du seed .NET).
    foreach (Role::query()->pluck('id') as $roleId) {
        expect(DB::table('role_permissions')->where('role_id', $roleId)->count())->toBe(53);
    }
});

it('ADMIN : tout en Toutes', function () {
    $roleId = Role::query()->where('code', 'ADMIN')->value('id');
    expect(DB::table('role_permissions')->where('role_id', $roleId)->where('portee', '<>', 'Toutes')->count())->toBe(0);
});

it('DIRECTION : Toutes, sauf les 5 permissions réservées à l’administrateur', function () {
    $roleId = Role::query()->where('code', 'DIRECTION')->value('id');
    $aucune = DB::table('role_permissions')->where('role_id', $roleId)->where('portee', 'Aucune')->pluck('permission_code')->sort()->values()->all();
    expect($aucune)->toBe(collect([
        'role.gerer', 'parametre.gerer', 'referentiel.gerer', 'utilisateur.gerer', 'donneespersonnelles.dossier',
    ])->sort()->values()->all());
    expect(DB::table('role_permissions')->where('role_id', $roleId)->where('portee', 'Toutes')->count())->toBe(48);
});

it('RESP_COMM : Équipe par défaut, Toutes sur catalogue/référentiel/campagne, rien sur rapport.global', function () {
    expect(porteeDe('RESP_COMM', 'lead.consulter'))->toBe('Equipe');
    expect(porteeDe('RESP_COMM', 'opportunite.cloturer'))->toBe('Equipe');
    expect(porteeDe('RESP_COMM', 'campagne.consulter'))->toBe('Toutes');
    expect(porteeDe('RESP_COMM', 'catalogue.consulter'))->toBe('Toutes');
    expect(porteeDe('RESP_COMM', 'rapport.global'))->toBe('Aucune');
    expect(porteeDe('RESP_COMM', 'role.gerer'))->toBe('Aucune');
});

it('COMMERCIAL : Siennes, avec les exclusions du §78', function () {
    expect(porteeDe('COMMERCIAL', 'lead.consulter'))->toBe('Siennes');
    expect(porteeDe('COMMERCIAL', 'opportunite.creer'))->toBe('Siennes');
    expect(porteeDe('COMMERCIAL', 'lead.supprimer'))->toBe('Aucune');
    expect(porteeDe('COMMERCIAL', 'lead.affecter'))->toBe('Aucune');
    expect(porteeDe('COMMERCIAL', 'campagne.creer'))->toBe('Aucune');
    expect(porteeDe('COMMERCIAL', 'journal.consulter'))->toBe('Aucune');
    expect(porteeDe('COMMERCIAL', 'catalogue.consulter'))->toBe('Toutes');
    expect(porteeDe('COMMERCIAL', 'donneespersonnelles.dossier'))->toBe('Aucune');
});

it('le dossier §72 n’est ouvert qu’à un seul rôle (ADMIN)', function () {
    $ouverts = DB::table('role_permissions')
        ->where('permission_code', 'donneespersonnelles.dossier')
        ->where('portee', '<>', 'Aucune')
        ->count();
    expect($ouverts)->toBe(1);
    expect(porteeDe('ADMIN', 'donneespersonnelles.dossier'))->toBe('Toutes');
});

it('résout la matrice de bout en bout, ET le §68 prime sur la matrice', function () {
    $admin = User::factory()->create(['role_id' => Role::query()->where('code', 'ADMIN')->value('id')]);
    $comm = User::factory()->create(['role_id' => Role::query()->where('code', 'COMMERCIAL')->value('id')]);

    expect($admin->porteePour('lead.consulter'))->toBe(Portee::Toutes);
    expect($comm->porteePour('lead.consulter'))->toBe(Portee::Siennes);
    expect($comm->porteePour('lead.affecter'))->toBe(Portee::Aucune);

    // La matrice donne Siennes à un commercial sur societe.creer — mais la
    // permission est ÉTEINTE (§68), donc la résolution rend Aucune. Le §68
    // prime sur ce que la matrice accorde.
    expect(porteeDe('COMMERCIAL', 'societe.creer'))->toBe('Siennes');
    expect($comm->porteePour('societe.creer'))->toBe(Portee::Aucune);
});
