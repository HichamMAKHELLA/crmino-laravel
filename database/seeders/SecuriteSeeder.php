<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Rôles, permissions et matrice du §78 — port fidèle du seed .NET.
 *
 * La matrice est CALCULÉE par ensembles (comme le CROSS JOIN + CASE du .NET),
 * jamais recopiée ligne à ligne : recopiée, elle se contredirait au premier
 * ajout de permission. Convention (RG-HAB-003) : UNE ligne par couple
 * rôle × permission, « Aucune » comprise — c'est ce qui distingue « retiré » de
 * « jamais paramétré ».
 */
class SecuriteSeeder extends Seeder
{
    /** @var array<string,string> code => libellé */
    private const ROLES = [
        'ADMIN' => 'Administrateur',
        'DIRECTION' => 'Direction',
        'RESP_COMM' => 'Responsable commercial',
        'COMMERCIAL' => 'Commercial',
    ];

    /** @var list<array{0:string,1:string,2:string}> [code, libellé, module] */
    private const PERMISSIONS = [
        ['lead.consulter', 'Consulter les leads', 'lead'],
        ['lead.creer', 'Créer un lead', 'lead'],
        ['lead.modifier', 'Modifier un lead', 'lead'],
        ['lead.supprimer', 'Désactiver un lead', 'lead'],
        ['lead.affecter', 'Affecter un lead', 'lead'],
        ['lead.convertir', 'Convertir un lead en société', 'lead'],
        ['societe.consulter', 'Consulter les sociétés', 'societe'],
        ['societe.creer', 'Créer une société', 'societe'],
        ['societe.modifier', 'Modifier une société', 'societe'],
        ['societe.supprimer', 'Désactiver une société', 'societe'],
        ['societe.affecter', 'Affecter une société', 'societe'],
        ['contact.consulter', 'Consulter les contacts', 'contact'],
        ['contact.creer', 'Créer un contact', 'contact'],
        ['contact.modifier', 'Modifier un contact', 'contact'],
        ['contact.supprimer', 'Désactiver un contact', 'contact'],
        ['opportunite.consulter', 'Consulter les opportunités', 'opportunite'],
        ['opportunite.creer', 'Créer une opportunité', 'opportunite'],
        ['opportunite.modifier', 'Modifier une opportunité', 'opportunite'],
        ['opportunite.supprimer', 'Désactiver une opportunité', 'opportunite'],
        ['opportunite.affecter', 'Affecter une opportunité', 'opportunite'],
        ['opportunite.cloturer', 'Clôturer une opportunité', 'opportunite'],
        ['activite.consulter', 'Consulter les activités', 'activite'],
        ['activite.creer', 'Enregistrer une activité', 'activite'],
        ['activite.modifier', 'Modifier une activité', 'activite'],
        ['activite.supprimer', 'Désactiver une activité', 'activite'],
        ['tache.consulter', 'Consulter les tâches', 'tache'],
        ['tache.creer', 'Créer une tâche', 'tache'],
        ['tache.modifier', 'Modifier une tâche', 'tache'],
        ['tache.supprimer', 'Désactiver une tâche', 'tache'],
        ['campagne.consulter', 'Consulter les campagnes', 'campagne'],
        ['campagne.creer', 'Créer une campagne', 'campagne'],
        ['campagne.modifier', 'Modifier une campagne', 'campagne'],
        ['campagne.supprimer', 'Désactiver une campagne', 'campagne'],
        ['catalogue.consulter', 'Consulter le catalogue', 'catalogue'],
        ['catalogue.gerer', 'Gérer le catalogue', 'catalogue'],
        ['document.consulter', 'Consulter les documents', 'document'],
        ['document.deposer', 'Déposer un document', 'document'],
        ['document.supprimer', 'Désactiver un document', 'document'],
        ['rapport.consulter', 'Consulter ses rapports', 'rapport'],
        ['rapport.equipe', "Consulter les rapports d'équipe", 'rapport'],
        ['rapport.global', 'Consulter les rapports globaux', 'rapport'],
        ['objectif.consulter', 'Consulter les objectifs', 'objectif'],
        ['objectif.definir', 'Définir des objectifs', 'objectif'],
        ['import.executer', 'Importer des prospects', 'import'],
        ['export.executer', 'Exporter des données', 'export'],
        ['referentiel.consulter', 'Consulter les référentiels', 'referentiel'],
        ['referentiel.gerer', 'Gérer les référentiels', 'referentiel'],
        ['utilisateur.consulter', 'Consulter les utilisateurs', 'utilisateur'],
        ['utilisateur.gerer', 'Gérer les utilisateurs', 'utilisateur'],
        ['role.gerer', 'Gérer les rôles et les droits', 'role'],
        ['journal.consulter', "Consulter le journal d'activité", 'journal'],
        ['parametre.gerer', 'Gérer les paramètres', 'parametre'],
        ['donneespersonnelles.dossier', "Produire le dossier de données personnelles d'une personne (§72)", 'donneespersonnelles'],
    ];

    /**
     * Permissions ÉTEINTES par le §68 (migration 0012 côté .NET). Les six
     * *.supprimer des entités commerciales — mais PAS contact/document, qui
     * restent actives —, plus societe.creer (une société naît d'une conversion)
     * et parametre.gerer (remplacée par referentiel.gerer).
     *
     * @var list<string>
     */
    private const INACTIVES = [
        'lead.supprimer', 'societe.supprimer', 'opportunite.supprimer',
        'activite.supprimer', 'tache.supprimer', 'campagne.supprimer',
        'societe.creer', 'parametre.gerer',
    ];

    public function run(): void
    {
        foreach (self::ROLES as $code => $libelle) {
            Role::query()->updateOrCreate(
                ['code' => $code],
                ['libelle' => $libelle, 'systeme' => true, 'actif' => true],
            );
        }

        foreach (self::PERMISSIONS as [$code, $libelle, $module]) {
            Permission::query()->updateOrCreate(
                ['code' => $code],
                ['libelle' => $libelle, 'module' => $module, 'actif' => ! in_array($code, self::INACTIVES, true)],
            );
        }

        $roles = Role::query()->get(['id', 'code']);
        $lignes = [];
        foreach ($roles as $role) {
            foreach (self::PERMISSIONS as [$code, , $module]) {
                $lignes[] = [
                    'role_id' => $role->id,
                    'permission_code' => $code,
                    'portee' => $this->portee($role->code, $code, $module),
                ];
            }
        }

        DB::table('role_permissions')->upsert($lignes, ['role_id', 'permission_code'], ['portee']);
    }

    /**
     * La portée d'un couple rôle × permission — le CASE du §78, à l'identique.
     */
    private function portee(string $roleCode, string $permCode, string $module): string
    {
        // Réservées à l'administrateur (dont le dossier §72, ajouté par 0006 :
        // ADMIN=Toutes, tout le reste=Aucune).
        $adminSeules = ['role.gerer', 'parametre.gerer', 'referentiel.gerer', 'utilisateur.gerer', 'donneespersonnelles.dossier'];
        if (in_array($permCode, $adminSeules, true) && $roleCode !== 'ADMIN') {
            return 'Aucune';
        }

        return match ($roleCode) {
            'ADMIN', 'DIRECTION' => 'Toutes',
            'RESP_COMM' => match (true) {
                $permCode === 'rapport.global' => 'Aucune',
                in_array($module, ['catalogue', 'referentiel', 'campagne'], true) => 'Toutes',
                default => 'Equipe',
            },
            'COMMERCIAL' => match (true) {
                str_ends_with($permCode, '.supprimer') => 'Aucune',
                str_ends_with($permCode, '.affecter') => 'Aucune',
                in_array($permCode, [
                    'rapport.equipe', 'rapport.global', 'objectif.definir',
                    'import.executer', 'journal.consulter', 'utilisateur.consulter',
                    'catalogue.gerer', 'campagne.creer', 'campagne.modifier',
                ], true) => 'Aucune',
                in_array($module, ['catalogue', 'referentiel', 'campagne'], true) => 'Toutes',
                default => 'Siennes',
            },
            default => 'Aucune',
        };
    }
}
