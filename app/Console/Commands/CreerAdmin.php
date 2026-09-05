<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;

/**
 * Crée le PREMIER compte administrateur (§5). En production, les comptes sont
 * créés par un administrateur — il n'existe aucune auto-inscription. Ce
 * bootstrap pose le tout premier compte, hors application, sous un compte
 * habilité. Refuse d'écraser un compte existant.
 */
class CreerAdmin extends Command
{
    protected $signature = 'crmino:creer-admin {email} {--prenom=} {--nom=} {--mot-de-passe=}';

    protected $description = 'Crée le premier compte administrateur (§5). Le mot de passe peut venir de --mot-de-passe ou d\'une saisie masquée.';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $motDePasse = (string) ($this->option('mot-de-passe') ?: $this->secret('Mot de passe'));

        $validation = Validator::make(
            ['email' => $email, 'mot_de_passe' => $motDePasse],
            ['email' => ['required', 'email'], 'mot_de_passe' => ['required', Password::min(12)]],
        );
        if ($validation->fails()) {
            foreach ($validation->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->error("Un compte existe déjà pour {$email}. Ce bootstrap ne l'écrase pas.");

            return self::FAILURE;
        }

        $roleAdmin = Role::query()->where('code', 'ADMIN')->value('id');
        if ($roleAdmin === null) {
            $this->error('Le rôle ADMIN est absent. Exécutez d\'abord les seeders (SecuriteSeeder).');

            return self::FAILURE;
        }

        $prenom = (string) ($this->option('prenom') ?: 'Administrateur');
        $nom = (string) ($this->option('nom') ?: 'CRMino');

        User::query()->create([
            'name' => trim("$prenom $nom"),
            'prenom' => $prenom,
            'nom' => $nom,
            'email' => $email,
            'password' => Hash::make($motDePasse),
            'role_id' => $roleAdmin,
            'actif' => true,
            'email_verified_at' => now(),
        ]);

        $this->info("Compte administrateur créé : {$email}.");

        return self::SUCCESS;
    }
}
