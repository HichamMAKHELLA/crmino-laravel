<?php

declare(strict_types=1);

use App\Models\Lead;
use App\Models\Opportunite;
use App\Models\Referentiels\MotifPerte;
use App\Models\Societe;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
    $this->commercial = crminoUtilisateur('COMMERCIAL');
    $this->autre = crminoUtilisateur('COMMERCIAL');
});

function oppPour(int $proprio, string $statut, float $montant, int $proba = 50, ?string $cloture = null): void
{
    $societe = Societe::factory()->create(['proprietaire_id' => $proprio]);
    Opportunite::factory()->create([
        'societe_id' => $societe->id, 'proprietaire_id' => $proprio,
        'statut' => $statut, 'montant_ht' => $montant, 'probabilite' => $proba,
        'date_cloture' => $cloture,
        // RG-OPP-002 : une affaire perdue porte un motif.
        'motif_perte_id' => $statut === 'Perdue' ? MotifPerte::query()->value('id') : null,
    ]);
}

it('salue le commercial et affiche ses tuiles (§77)', function () {
    $this->withoutVite();

    $this->actingAs($this->commercial)->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $p) => $p
            ->component('Dashboard')
            ->has('prenom')
            ->has('tuiles.pipeline_pondere'));
});

it('borne les tuiles au périmètre du commercial', function () {
    $this->withoutVite();
    Lead::factory()->create(['proprietaire_id' => $this->commercial->id]);
    Lead::factory()->create(['proprietaire_id' => $this->autre->id]);
    oppPour($this->commercial->id, 'Ouverte', 100000, 50); // pondéré 50000
    oppPour($this->autre->id, 'Ouverte', 999999, 50);

    $this->actingAs($this->commercial)->get('/dashboard')
        ->assertInertia(fn (AssertableInertia $p) => $p
            ->where('tuiles.mes_leads', 1)
            ->where('tuiles.nb_affaires_ouvertes', 1)
            ->where('tuiles.pipeline_pondere', 50000));
});

it('RG-IND-001 — sans affaire close, le taux de gain n existe pas (≠ 0)', function () {
    $this->withoutVite();
    oppPour($this->commercial->id, 'Ouverte', 100000);

    $this->actingAs($this->commercial)->get('/dashboard')
        ->assertInertia(fn (AssertableInertia $p) => $p->where('tuiles.taux_gain', null));
});

it('calcule le taux de gain sur les affaires closes', function () {
    $this->withoutVite();
    oppPour($this->commercial->id, 'Gagnee', 100000, 50, now()->toDateString());
    oppPour($this->commercial->id, 'Perdue', 50000, 50, now()->toDateString());
    oppPour($this->commercial->id, 'Perdue', 20000, 50, now()->toDateString());

    // 1 gagnée sur 3 closes -> 33,3 % (valeur fractionnaire, sans ambiguïté).
    $this->actingAs($this->commercial)->get('/dashboard')
        ->assertInertia(fn (AssertableInertia $p) => $p->where('tuiles.taux_gain', 33.3));
});

it('le CA gagné ne compte que le mois courant', function () {
    $this->withoutVite();
    oppPour($this->commercial->id, 'Gagnee', 30000, 100, now()->toDateString());
    oppPour($this->commercial->id, 'Gagnee', 80000, 100, now()->subMonths(2)->toDateString());

    $this->actingAs($this->commercial)->get('/dashboard')
        ->assertInertia(fn (AssertableInertia $p) => $p->where('tuiles.ca_gagne_mois', 30000));
});
