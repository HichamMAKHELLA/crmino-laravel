<?php

declare(strict_types=1);

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->u = User::factory()->create(['actif' => true]);
});

function auditLe(string $date): void
{
    DB::table('audit_journal')->insert([
        'le' => $date, 'action' => 'Modification', 'entite_type' => 'Lead', 'entite_id' => 1,
    ]);
}

it('§72 — la simulation (par défaut) ne supprime RIEN', function () {
    auditLe(now()->subYears(6)->toDateTimeString()); // au-delà des 5 ans

    $this->artisan('crmino:purger')->assertSuccessful();

    expect(DB::table('audit_journal')->count())->toBe(1); // intact
});

it('§72 — --executer purge le journal au-delà de la rétention, garde le récent', function () {
    auditLe(now()->subYears(6)->toDateTimeString()); // échu
    auditLe(now()->subMonths(2)->toDateTimeString()); // récent

    $this->artisan('crmino:purger', ['--executer' => true])->assertSuccessful();

    expect(DB::table('audit_journal')->count())->toBe(1); // seul le récent reste
});

it('§72 — seules les notifications LUES sont purgées ; une non lue reste', function () {
    Notification::create(['utilisateur_id' => $this->u->id, 'titre' => 'Vieille lue', 'cree_le' => now()->subMonths(6), 'lue_le' => now()->subMonths(6)]);
    Notification::create(['utilisateur_id' => $this->u->id, 'titre' => 'Vieille NON lue', 'cree_le' => now()->subMonths(6), 'lue_le' => null]);

    $this->artisan('crmino:purger', ['--executer' => true])->assertSuccessful();

    expect(Notification::query()->count())->toBe(1);
    expect(Notification::query()->whereNull('lue_le')->exists())->toBeTrue(); // la non lue survit
});

it('§72 — la rétention vient de la CONFIG (paramétrable)', function () {
    config()->set('crmino.retention.audit_annees', 1);
    auditLe(now()->subMonths(18)->toDateTimeString()); // > 1 an

    $this->artisan('crmino:purger', ['--executer' => true])->assertSuccessful();

    expect(DB::table('audit_journal')->count())->toBe(0);
});
