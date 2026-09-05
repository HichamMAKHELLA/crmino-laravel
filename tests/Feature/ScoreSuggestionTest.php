<?php

declare(strict_types=1);

use App\Models\Activite;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Lead;
use App\Models\QualificationLead;
use App\Models\Referentiels\Besoin;
use App\Models\Referentiels\CritereScore;
use App\Models\Referentiels\HorizonDecision;
use App\Models\Referentiels\TrancheBudget;
use App\Models\Referentiels\TypeActivite;
use App\Models\Referentiels\TypeDocument;
use App\Support\ScoreSuggestion;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
    $this->u = crminoUtilisateur('COMMERCIAL');
    $this->service = app(ScoreSuggestion::class);
});

it('§15 — la somme des poids fait 100, et tout est évaluable par défaut', function () {
    $lead = Lead::factory()->create(['proprietaire_id' => $this->u->id]);
    $r = $this->service->pour($lead);

    expect($r['total'])->toBe(100);
    expect($r['total_atteignable'])->toBe(100);
    expect($r['suggestion'])->toBe(0); // rien de renseigné -> aucun critère acquis
});

it('§15 — chaque critère acquis ajoute son poids exact', function () {
    $lead = Lead::factory()->create(['proprietaire_id' => $this->u->id]);

    // BUDGET (15) + DELAI (10) + PROJET (10) via la qualification.
    QualificationLead::query()->create([
        'lead_id' => $lead->id, 'usage_sage' => 'Oui',
        'tranche_budget_id' => TrancheBudget::query()->value('id'),
        'horizon_decision_id' => HorizonDecision::query()->value('id'),
        'projet_defini' => true,
    ]);
    // BESOIN (15).
    $lead->besoins()->attach(Besoin::query()->value('id'));
    // DECISIONNAIRE (15).
    Contact::factory()->create(['lead_id' => $lead->id, 'decisionnaire' => true]);
    // RDV effectué (15) : activité de catégorie Rdv, date passée.
    $rdv = TypeActivite::query()->where('categorie', 'Rdv')->value('id');
    Activite::factory()->create(['lead_id' => $lead->id, 'type_id' => $rdv, 'utilisateur_id' => $this->u->id, 'debut_le' => now()->subDay()]);
    // DEVIS (15).
    $devis = TypeDocument::query()->where('code', 'DEVIS')->value('id');
    Document::factory()->create(['cible_type' => 'Lead', 'cible_id' => $lead->id, 'type_document_id' => $devis, 'depose_par' => $this->u->id]);
    // INTERACTION récente (5).
    $lead->forceFill(['derniere_activite_le' => now()->subDay()])->save();

    $r = $this->service->pour($lead->fresh());
    expect($r['suggestion'])->toBe(100); // 15+10+10+15+15+15+15+5
});

it('§15 — un RDV À VENIR ne compte pas (seul le RDV effectué prouve)', function () {
    $lead = Lead::factory()->create(['proprietaire_id' => $this->u->id]);
    $rdv = TypeActivite::query()->where('categorie', 'Rdv')->value('id');
    Activite::factory()->create(['lead_id' => $lead->id, 'type_id' => $rdv, 'utilisateur_id' => $this->u->id, 'debut_le' => now()->addDays(3)]);

    $ligne = collect($this->service->pour($lead->fresh())['criteres'])->firstWhere('code', 'RDV');
    expect($ligne['acquis'])->toBeFalse();
});

it('§15 RG-LEA-004 — un critère sans vocabulaire est HORS D ATTEINTE (plafond baisse), pas non acquis', function () {
    $lead = Lead::factory()->create(['proprietaire_id' => $this->u->id]);
    // Plus aucune tranche de budget active : BUDGET (15) devient inévaluable.
    TrancheBudget::query()->update(['actif' => false]);

    $r = $this->service->pour($lead);
    $budget = collect($r['criteres'])->firstWhere('code', 'BUDGET');
    expect($budget['evaluable'])->toBeFalse();
    expect($r['total'])->toBe(100);                 // le poids existe toujours
    expect($r['total_atteignable'])->toBe(85);      // mais il sort du plafond (100 - 15)
});
