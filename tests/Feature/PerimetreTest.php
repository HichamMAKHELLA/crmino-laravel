<?php

declare(strict_types=1);

use App\Enums\Portee;
use App\Models\Concerns\AvecPerimetre;
use App\Models\Equipe;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Modèle jouet porteur du périmètre. On n'a pas encore d'entité métier
 * (Lead arrive en Phase 5) : ce modèle sert à éprouver le scope. On teste le
 * CONTRAT SQL de la clause (comme les tests de ClausePerimetre côté .NET), sans
 * table réelle — `toSql()` compile la requête sans la jouer.
 */
class FichePerimetre extends Model
{
    use AvecPerimetre;

    protected $table = 'fiches_perimetre';

    public $timestamps = false;

    protected $guarded = [];
}

/**
 * Fabrique un utilisateur dont le rôle accorde `$permission` à `$portee`.
 * `$portee = null` : aucune ligne role_permission (permission non accordée).
 */
function utilisateurAvecPortee(
    string $permission,
    ?Portee $portee,
    bool $roleActif = true,
    bool $permActif = true,
    ?int $equipeId = null,
    bool $userActif = true,
): User {
    $role = Role::query()->create([
        'code' => 'R'.uniqid(),
        'libelle' => 'Rôle test',
        'systeme' => false,
        'actif' => $roleActif,
    ]);

    if ($portee !== null) {
        Permission::query()->create([
            'code' => $permission,
            'libelle' => 'Permission test',
            'module' => 'test',
            'actif' => $permActif,
        ]);
        DB::table('role_permissions')->insert([
            'role_id' => $role->id,
            'permission_code' => $permission,
            'portee' => $portee->value,
        ]);
    }

    return User::factory()->create([
        'role_id' => $role->id,
        'equipe_id' => $equipeId,
        'actif' => $userActif,
    ]);
}

// ── Résolution de portée (port de Habilitations.PorteeDe) ──────────────────

it('rend la portée exacte accordée au rôle', function () {
    $u = utilisateurAvecPortee('lead.consulter', Portee::Equipe);
    expect($u->porteePour('lead.consulter'))->toBe(Portee::Equipe);
    expect($u->peut('lead.consulter'))->toBeTrue();
});

it('refuse (Aucune) quand la permission n’est pas accordée', function () {
    $u = utilisateurAvecPortee('lead.consulter', null);
    expect($u->porteePour('lead.consulter'))->toBe(Portee::Aucune);
    expect($u->peut('lead.consulter'))->toBeFalse();
});

it('refuse quand la permission est éteinte (§68)', function () {
    $u = utilisateurAvecPortee('lead.consulter', Portee::Toutes, permActif: false);
    expect($u->porteePour('lead.consulter'))->toBe(Portee::Aucune);
});

it('refuse quand le rôle est éteint', function () {
    $u = utilisateurAvecPortee('lead.consulter', Portee::Toutes, roleActif: false);
    expect($u->porteePour('lead.consulter'))->toBe(Portee::Aucune);
});

it('refuse quand le compte est inactif (RG-HAB-002)', function () {
    $u = utilisateurAvecPortee('lead.consulter', Portee::Toutes, userActif: false);
    expect($u->porteePour('lead.consulter'))->toBe(Portee::Aucune);
});

it('refuse quand l’utilisateur n’a aucun rôle', function () {
    $u = User::factory()->create(['role_id' => null]);
    expect($u->porteePour('lead.consulter'))->toBe(Portee::Aucune);
});

// ── Le scope de périmètre (port de ClausePerimetre.Construire) ──────────────

it('portée Toutes : aucun filtre', function () {
    $u = utilisateurAvecPortee('lead.consulter', Portee::Toutes);
    $q = FichePerimetre::query()->dansPerimetre($u, 'lead.consulter');
    expect(str_contains(strtolower($q->toSql()), 'where'))->toBeFalse();
});

it('portée Siennes : uniquement les siennes (RG-HAB-001)', function () {
    $u = utilisateurAvecPortee('lead.consulter', Portee::Siennes);
    $q = FichePerimetre::query()->dansPerimetre($u, 'lead.consulter');
    expect($q->getBindings())->toBe([$u->id]);
    expect(strtolower($q->toSql()))->toContain('proprietaire_id')
        ->not->toContain('equipe_id');
});

it('portée Équipe : les siennes OU celles de l’équipe', function () {
    $equipe = Equipe::query()->create(['code' => 'EQ1', 'libelle' => 'Équipe 1', 'actif' => true]);
    $u = utilisateurAvecPortee('lead.consulter', Portee::Equipe, equipeId: $equipe->id);
    $q = FichePerimetre::query()->dansPerimetre($u, 'lead.consulter');

    // RG-HAB-001 : la propriété d'abord (OU), jamais un ET.
    expect($q->getBindings())->toBe([$u->id, $equipe->id]);
    expect(strtolower($q->toSql()))
        ->toContain('proprietaire_id')
        ->toContain('equipe_id')
        ->toContain(' or ');
});

it('portée Aucune : réduit aux siennes, jamais rien (RG-HAB-001)', function () {
    $u = utilisateurAvecPortee('lead.consulter', Portee::Aucune);
    $q = FichePerimetre::query()->dansPerimetre($u, 'lead.consulter');
    // Même privé de tout droit, l'auteur voit son propre travail.
    expect($q->getBindings())->toBe([$u->id]);
    expect(strtolower($q->toSql()))->toContain('proprietaire_id');
});

it('portée Équipe sans équipe : retombe sur les siennes', function () {
    $u = utilisateurAvecPortee('lead.consulter', Portee::Equipe, equipeId: null);
    $q = FichePerimetre::query()->dansPerimetre($u, 'lead.consulter');
    // effective() a réduit Équipe -> Siennes : pas de clause d'équipe.
    expect($q->getBindings())->toBe([$u->id]);
    expect(strtolower($q->toSql()))->not->toContain('equipe_id');
});

// ── Le seed des rôles socles ───────────────────────────────────────────────

it('sème les quatre rôles socles, tous « système »', function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);

    expect(Role::query()->count())->toBe(4);
    foreach (['ADMIN', 'DIRECTION', 'RESP_COMM', 'COMMERCIAL'] as $code) {
        $r = Role::query()->where('code', $code)->first();
        expect($r)->not->toBeNull();
        expect($r->systeme)->toBeTrue();
    }
});
