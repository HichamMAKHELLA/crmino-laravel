<?php

declare(strict_types=1);

use App\Models\Lead;
use App\Models\Referentiels\ParametreAlerte;
use App\Support\Alertes;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
    $this->u = crminoUtilisateur('COMMERCIAL');
    $this->service = app(Alertes::class);
});

function ligneAlerte(array $t, string $code): array
{
    return collect($t['lignes'])->firstWhere('code', $code);
}

it('§35 — un lead dormant est compté par LEAD_SANS_ACTIVITE (délai du paramétrage)', function () {
    // Le délai par défaut est 7 jours ; un lead créé il y a 30 jours, jamais touché.
    $lead = Lead::factory()->create(['proprietaire_id' => $this->u->id]);
    $lead->forceFill(['created_at' => now()->subDays(30), 'derniere_activite_le' => null])->save();

    $t = $this->service->pour($this->u);
    expect(ligneAlerte($t, 'LEAD_SANS_ACTIVITE')['compte'])->toBe(1);
});

it('§35 — une alerte DÉSACTIVÉE rend NULL, jamais zéro (RG-IND-001)', function () {
    ParametreAlerte::query()->where('code', 'LEAD_SANS_ACTIVITE')->update(['actif' => false]);

    $ligne = ligneAlerte($this->service->pour($this->u), 'LEAD_SANS_ACTIVITE');
    expect($ligne['compte'])->toBeNull();   // « désactivée — rien n'est surveillé »
    expect($ligne['actif'])->toBeFalse();
});

it('§35 — un paramètre actif mais INCALCULABLE est nommé (sans producteur)', function () {
    // CONTRAT_ECHEANCE et RELANCE_SANS_REPONSE n'ont pas de producteur ici.
    $t = $this->service->pour($this->u);
    expect($t['sans_producteur'])->toContain('CONTRAT_ECHEANCE');
    expect($t['sans_producteur'])->toContain('RELANCE_SANS_REPONSE');
    // Leur compte est nul, et surveille = false.
    expect(ligneAlerte($t, 'CONTRAT_ECHEANCE')['surveille'])->toBeFalse();
    expect(ligneAlerte($t, 'CONTRAT_ECHEANCE')['compte'])->toBeNull();
});

it('§35 — le seuil ne se lit QUE sur OPP_SANS_ACTION', function () {
    $t = $this->service->pour($this->u);
    expect(ligneAlerte($t, 'OPP_SANS_ACTION')['seuil_lu'])->toBeTrue();
    expect(ligneAlerte($t, 'LEAD_SANS_ACTIVITE')['seuil_lu'])->toBeFalse();
});

it('§35 — le réglage borne le délai à 3650 jours', function () {
    $admin = crminoUtilisateur('ADMIN');
    $p = ParametreAlerte::query()->where('code', 'LEAD_SANS_ACTIVITE')->first();

    $this->actingAs($admin)->put("/alertes/{$p->id}", ['actif' => true, 'delai_jours' => 999999])
        ->assertSessionHasErrors('delai_jours');
});

it('§35 — le réglage n écrit le seuil QUE sur une alerte qui le lit', function () {
    $admin = crminoUtilisateur('ADMIN');
    $lead = ParametreAlerte::query()->where('code', 'LEAD_SANS_ACTIVITE')->first();

    $this->actingAs($admin)->put("/alertes/{$lead->id}", ['actif' => true, 'delai_jours' => 5, 'seuil_montant' => 50000]);

    // LEAD_SANS_ACTIVITE ne lit pas de seuil : il reste nul.
    expect($lead->fresh()->seuil_montant)->toBeNull();
});

it('§35 — le réglage est réservé à referentiel.gerer (403 pour un commercial)', function () {
    $p = ParametreAlerte::query()->first();
    $this->actingAs($this->u)->put("/alertes/{$p->id}", ['actif' => true, 'delai_jours' => 5])->assertForbidden();
});
