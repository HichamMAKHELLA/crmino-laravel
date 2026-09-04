# Migration CRMino .NET → Laravel + Inertia + Vue

Référence de parité : le dépôt **CRMino .NET** (ASP.NET Core 10 + Dapper + SQL
Server + React). Cible : **Laravel 13 + Inertia + Vue 3 + MySQL**. Décisions
structurantes : voir `docs/architecture-decisions.md`.

Périmètre mesuré du .NET : **24 contrôleurs**, **29 dépôts** (~374 requêtes SQL),
**17 fichiers de règles** (`Domain/`), **31 règles `RG-*`**, **58 tables**,
**25 pages** + **62 composants** React, **~1 667 tests**.

## Terminé
- **Phase 0 — Socle.** Laravel 13 + kit Vue (Inertia/Vue/shadcn-vue/TS) + Pest +
  MySQL. Toolchain via Herd (PHP 8.4, Composer 2.10). **39 tests du kit verts.**
- Registre de décisions (`docs/architecture-decisions.md`, ADR-001 à 005).
- **Environnement DB** : MariaDB 12.3 (winget), base `crmino_laravel` utf8mb4, migrations par défaut appliquées. Base NEUVE (pas de reprise de données).

## En cours
- **Phase 3 (identité + périmètre) — tranche 1 faite.** Tables `roles`,
  `permissions`, `role_permissions` (portée), `equipes` + extension de `users` ;
  enum `Portee`, résolution `User::porteePour/peut`, trait `AvecPerimetre`
  (`scopeDansPerimetre`, port de `ClausePerimetre`). Seed des 4 rôles socles.
  **12 tests, filet RG-HAB-001 prouvé par mutation** (4 chutes).
  Reste sur cette phase : catalogue des 53 permissions + matrice §78, réconcilier
  l'inscription du kit avec le §5 (comptes créés par un admin, rôle obligatoire).

## À faire — par phases (§30)
| Phase | Module | État |
|---|---|---|
| 1 | Schéma (58 tables) + seeds des 25 référentiels + portage des garanties SQL Server (ADR-001) | À faire |
| 2 | Auth locale (session) + `RG-AUTH` (verrou anti-force) | À faire |
| 3 | Rôles + périmètre (`PerimetreScope`, `RG-HAB-*`) | **Tranche 1 faite** (identité, portée, scope) ; reste permissions + matrice §78 |
| 4 | Référentiels administrables (§50, `RG-REF-*`) | À faire |
| 5 | Leads + doublons + qualification + score (`RG-LEA-*`, `RG-DOU`, `RG-IND`) | À faire |
| 6 | Sociétés + contacts + conversion (`RG-SOC`, §31) | À faire |
| 7 | Opportunités + lignes + pipeline (`RG-OPP-001..007`) | À faire |
| 8 | Activités, tâches, notifications, campagnes, catalogue | À faire |
| 9 | Jobs/planif (`--notifier` → Scheduler/Queue) + Outbox Sage | À faire |
| 10 | Rapports, tableaux de bord, import Excel, droit d'accès (§72) | À faire |
| 11 | Tests de parité Pest + Vitest | À faire |
| 12 | Bascule production | À faire |

## Matrice de parité fonctionnelle (§31 — à remplir module par module)
| Fonction | .NET | Laravel | Test | Statut |
|---|---|---|---|---|
| Socle / build | Oui | Oui | OK | Terminé |
| Rôles + équipes (schéma) | Oui | Oui | OK | Terminé |
| Résolution de portée (`porteePour`) | Oui | Oui | OK | Terminé |
| Périmètre par ligne (`scopeDansPerimetre`, RG-HAB-001) | Oui | Oui | OK | Terminé |
| Catalogue permissions + matrice §78 | Oui | Non | — | À faire |
| Authentification | Oui | (kit) | — | À reprendre en Phase 2 |

## Problèmes / à confirmer
- **P0** : portage des 8 garanties SQL Server → MySQL (ADR-001). Chaque ligne
  devient un test dans sa phase.
- **P0** : stratégie de migration des DONNÉES existantes (le .NET a un volume de
  recette ; MySQL repart de zéro — définir l'export/import si reprise voulue).

## Dette technique
- (aucune pour l'instant)
