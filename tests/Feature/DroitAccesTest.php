<?php

declare(strict_types=1);

use App\Models\AuditJournal;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->seed(\Database\Seeders\SecuriteSeeder::class);
    $this->seed(\Database\Seeders\ReferentielsSeeder::class);
    $this->admin = crminoUtilisateur('ADMIN'); // donneespersonnelles.dossier = Toutes
    // Un contact rattaché à un lead d'un AUTRE commercial (hors du portefeuille admin).
    $autre = crminoUtilisateur('COMMERCIAL');
    $lead = Lead::factory()->create(['proprietaire_id' => $autre->id]);
    $this->contact = Contact::factory()->create(['lead_id' => $lead->id, 'nom' => 'Personne', 'prenom' => null, 'email' => 'p@ex.ma']);
});

it('produit le dossier complet, sans filtre de périmètre (§72)', function () {
    $this->withoutVite();

    $this->actingAs($this->admin)
        ->post("/donnees-personnelles/contact/{$this->contact->id}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $p) => $p
            ->component('DonneesPersonnelles/Dossier')
            ->where('contact.nom', 'Personne')
            ->has('limites', 3)      // le dossier NOMME ses limites
            ->has('produitLe'));      // et porte sa date
});

it('§72 — la production laisse une trace Export (POST, pas une lecture)', function () {
    $this->actingAs($this->admin)->post("/donnees-personnelles/contact/{$this->contact->id}");

    $j = AuditJournal::query()->where('action', 'Export')->where('entite_type', 'Contact')->first();
    expect($j)->not->toBeNull();
    expect($j->entite_id)->toBe($this->contact->id);
});

it('§72 — exige la portée « Toutes », pas seulement « non Aucune »', function () {
    // Un rôle avec la permission en portée SIENNES : peut() serait vrai, mais le
    // §72 exige Toutes. Un dossier partiel serait une attestation fausse.
    $role = Role::query()->create(['code' => 'PARTIEL', 'libelle' => 'Partiel', 'actif' => true]);
    DB::table('role_permissions')->insert([
        'role_id' => $role->id, 'permission_code' => 'donneespersonnelles.dossier', 'portee' => 'Siennes',
    ]);
    $u = User::factory()->create(['role_id' => $role->id, 'actif' => true]);

    expect($u->peut('donneespersonnelles.dossier'))->toBeTrue(); // Siennes != Aucune
    $this->actingAs($u)->post("/donnees-personnelles/contact/{$this->contact->id}")->assertForbidden();
});

it('refuse un utilisateur sans la permission (403)', function () {
    $commercial = crminoUtilisateur('COMMERCIAL');

    $this->actingAs($commercial)->post("/donnees-personnelles/contact/{$this->contact->id}")->assertForbidden();
    $this->actingAs($commercial)->get('/donnees-personnelles')->assertForbidden();
});
