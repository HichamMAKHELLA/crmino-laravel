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
  Catalogue des **53 permissions** + **matrice §78** (calculée par ensembles) +
  §72 (dossier = ADMIN seul) + §68 (8 permissions éteintes) : fait, avec la
  résolution de bout en bout où le §68 prime (prouvé par mutation).
  Reste sur cette phase : réconcilier l'inscription du kit avec le §5 (comptes
  créés par un admin, rôle obligatoire, pas d'auto-inscription).
- **Phase 4 — référentiels géographiques faits.** Hiérarchie Pays → Région →
  Ville (FK) + fonctions de contact. Seeds fidèles (7/12/21/13). 3 tests.
- **Phase 5 (Leads) — tranche 1 : le premier écran Inertia.** Table `leads`
  fidèle à `app.Lead`, modèle avec trait `AvecPerimetre`, numérotation §10
  (`Numerotation` + table `sequences`, verrou de ligne), `LeadPolicy`,
  `LeadController` (Inertia `Leads/Index`), page Vue, route et entrée de nav.
  **Le périmètre RG-HAB-001 est prouvé sur un VRAI modèle** (Siennes/Équipe/
  Toutes divergent), mutation à l'appui (2 chutes). 11 tests. Reste :
  qualification §13, score §15, création/édition.
- **Phase 5/2 (doublons §41) — fait.** Port de `Domain/Texte` et
  `Domain/Doublons` (`app/Support/Texte.php`, `Doublons.php`) : normalisation de
  la raison sociale (retrait des formes juridiques, recollage des sigles
  pointés), du téléphone marocain, extraction de domaine (domaines grand public
  écartés). Colonnes `raison_sociale_normalisee` / `domaine_web` recalculées à
  CHAQUE écriture du Lead (hook `saving`). `DetecteurDoublons` croise ICE exact,
  domaine exact et raison sociale EN PRÉFIXE, dédup par critère le plus fort.
  19 tests, 2 mutations à l'appui. **Téléphone et courriel passent par le
  Contact (§6)** — leurs branches arriveront avec la table `contacts`.
- **Phase 5/3 (création d'un lead) — fait.** Table `contacts` avancée (§6 en
  partie) : rattachement lead OU société (CHECK), `telephone_normalise` /
  `gsm_normalise` recalculés à l'écriture. `DetecteurDoublons` complété — branches
  téléphone et courriel via les contacts. `ReglesLead::controlerDonneesMinimales`
  (RG-LEA-001), `StoreLeadRequest`, `LeadController@store` : contrôle de doublon
  AVANT écriture, doctrine §41/§71 « on montre, créer quand même reste possible »,
  et la vérification en panne ne bloque PAS la création. Le contact n'est créé
  que s'il porte un nom. 10 tests, 3 mutations (RG-LEA-001, garde de panne,
  contact-si-nom). **Écart noté** : `domaine_web` du lead se calcule à partir du
  seul `site_web` (le .NET ajoute un repli sur le courriel du contact) ; la
  détection fait déjà ce repli au moment de la requête.

## À faire — par phases (§30)
| Phase | Module | État |
|---|---|---|
| 1 | Schéma (58 tables) + seeds des 25 référentiels + portage des garanties SQL Server (ADR-001) | À faire |
| 2 | Auth locale (session) + `RG-AUTH` (verrou anti-force) | À faire |
| 3 | Rôles + périmètre (`PerimetreScope`, `RG-HAB-*`) | **Tranche 1 faite** (identité, portée, scope) ; reste permissions + matrice §78 |
| 4 | Référentiels administrables (§50, `RG-REF-*`) | **Tranches 1-2 faites** (8 auto-contenus + géographique + fonctions) ; reste score, besoins, catalogue, alertes |
| 5 | Leads + doublons + qualification + score (`RG-LEA-*`, `RG-DOU`, `RG-IND`) | **Tranches 1-2 faites** (table, périmètre prouvé, numérotation, écran Inertia, doublons §41) ; reste édition qualif./score, suggestion de score |
| 6 | Sociétés + contacts + conversion (`RG-SOC`, §31) | **Terminée** (table, périmètre, liste, conversion §31 + bascule §8 complète, fiches, état §32, notes §45, documents §44) ; reste édition champs société §18 |
| 7 | Opportunités + lignes + pipeline (`RG-OPP-001..007`) | **Terminée** (pondéré généré, création, board, fiche, clôture RG-OPP-002/003/005, lignes §28 + RG-OPP-006, glisser-déposer §64 RG-OPP-005) |
| 8 | Activités, tâches, notifications, campagnes, catalogue | **Terminée** (activités, tâches, notifications, campagnes, catalogue §28 + §47) |
| 9 | Jobs/planif (`--notifier` → Scheduler/Queue) + Outbox Sage | **Outbox §48 + notifications d'état §36 faites** (Scheduler `crmino:notifier`, idempotent) ; reste purge de rétention |
| 10 | Rapports, tableaux de bord, import Excel, droit d'accès (§72) | **Terminée** (accueil §77, rapports §75/§38/§39/§76, droit d'accès §72, import CSV + .xlsx §42 deux temps + atomique) |
| 11 | Tests de parité Pest + Vitest | **Socle + écrans clés faits** (26 tests : NotesInternes, Documents, Kanban §64, Import §42) ; reste fiches/listes |
| 12 | Bascule production | À faire |

## Matrice de parité fonctionnelle (§31 — à remplir module par module)
| Fonction | .NET | Laravel | Test | Statut |
|---|---|---|---|---|
| Socle / build | Oui | Oui | OK | Terminé |
| Rôles + équipes (schéma) | Oui | Oui | OK | Terminé |
| Résolution de portée (`porteePour`) | Oui | Oui | OK | Terminé |
| Périmètre par ligne (`scopeDansPerimetre`, RG-HAB-001) | Oui | Oui | OK | Terminé |
| Catalogue permissions (53) + matrice §78 | Oui | Oui | OK | Terminé |
| Extinction §68 + dossier §72 | Oui | Oui | OK | Terminé |
| Référentiels moteur + 8 auto-contenus (sources, statuts, étapes, motifs…) | Oui | Oui | OK | Terminé |
| Référentiels géographiques (Pays/Région/Ville) + fonctions | Oui | Oui | OK | Terminé |
| Référentiel types_document (§44, DEVIS/PROPOSITION socle §15) | Oui | Oui | OK | Terminé |
| Référentiels score / besoins / alertes | Oui | Non | — | À faire |
| Leads : schéma + modèle + numérotation §10 | Oui | Oui | OK | Terminé |
| Leads : périmètre par ligne (RG-HAB-001, vrai modèle) | Oui | Oui | OK | Terminé |
| Leads : écran liste (Inertia + Policy) | Oui | Oui | OK | Terminé |
| Normalisation §41 (Texte, Doublons : raison sociale, téléphone, domaine) | Oui | Oui | OK | Terminé |
| Détection de doublons §41 (ICE / domaine / raison en préfixe) — leads | Oui | Oui | OK | Terminé (tél./courriel → Contact §6) |
| Contacts : table + normalisation §41 (téléphone) | Oui | Oui | OK | Terminé (société §6 différée) |
| Détection §41 complète (ICE/tél./courriel/domaine/raison) | Oui | Oui | OK | Terminé |
| Création d'un lead (RG-LEA-001, doublons, contact principal) | Oui | Oui | OK | Terminé |
| Écran de création + panneau de doublons « Créer quand même » | Oui | Oui | Navigateur | Terminé |
| Paliers de score §15 (Froid/Tiède/Chaud/Très chaud, bornes) | Oui | Oui | OK | Terminé |
| Qualification §13 (satellite 1-1) — lecture | Oui | Oui | OK | Terminé |
| Fiche 360° du lead (§58, palier, contacts, qualif) | Oui | Oui | Navigateur | Terminé |
| Édition qualification §13 + score §15 (écriture, suggestion) | Oui | Non | — | À faire |
| Leads : qualification §13 / score §15 | Oui | Non | — | À faire |
| Sociétés : table + modèle + numérotation SOC + normalisation §41 | Oui | Oui | OK | Terminé |
| Sociétés : écran liste + périmètre (RG-HAB-001) | Oui | Oui | Navigateur | Terminé |
| Conversion §31 (lead -> société, bascule des contacts) | Oui | Oui | Navigateur | Terminé (autres familles -> §8) |
| RG-LEA-003 (lead converti : bandeau + renvoi société) | Oui | Oui | Navigateur | Terminé |
| Fiche société (§58) | Oui | Oui | Navigateur | Terminé |
| État de la relation §32 (RG-SOC-001, trace ChangementStatut) | Oui | Oui | OK | Terminé (mutation : Client→Prospect) |
| Édition société §18 (liste blanche, état/propriétaire/Sage exclus, recalcul §41) | Oui | Oui | OK | Terminé (mutation : exclusion) |
| Édition opportunité §25 (liste blanche, RG-OPP-007 montant close) | Oui | Oui | OK | Terminé (mutation : RG-OPP-007) |
| Conversion §31 : bascule des tâches (§8, §20) | Oui | Oui | OK | Terminé (mutation : bascule retirée) |
| Tables documents §44 + commentaires §45 (+ types_document socle §15) | Oui | Oui | OK | Terminé |
| Conversion §31 : bascule documents/commentaires (§8, polymorphe) | Oui | Oui | OK | Terminé (mutation : bascule retirée) |
| Notes internes §45 (auteur seul, modifie_le, 404 pas 403, texte échappé) | Oui | Oui | OK | Terminé (mutation : garde auteur) |
| Documents §44 (dépôt liste blanche, download pièce jointe MIME neutre, retrait §47) | Oui | Oui | OK | Terminé (mutation : liste blanche) |
| Opportunités : table + modèle + numérotation OPP | Oui | Oui | OK | Terminé |
| RG-OPP-001 : montant pondéré (colonne générée) | Oui | Oui | OK | Terminé (mutation schéma) |
| Ouverture d'une affaire (§25) + RG-OPP-004 (proba surchargée) | Oui | Oui | Navigateur | Terminé |
| Pipeline : board par étape (§64, lecture) + périmètre | Oui | Oui | Navigateur | Terminé |
| Fiche opportunité (§58) + clôture RG-OPP-002/003/005 | Oui | Oui | OK | Terminé |
| Lignes d'opportunité §28 (montant HT généré, RG-OPP-006, panneau fiche) | Oui | Oui | OK | Terminé (2 mutations : filtre produit, RG-IND-001) |
| Glisser-déposer §64 (déplacement d'étape, optimiste, RG-OPP-005) | Oui | Oui | OK | Terminé (2 mutations : filtre étape ouverte, RG-OPP-005) |
| Activités §19/§20 : journalisation + répercussion fiche + frise §73 | Oui | Oui | OK | Terminé |
| Conversion §31 : bascule des activités (en plus des contacts) | Oui | Oui | OK | Terminé |
| Tâches §20/§21 : « Mes tâches », tri, terminaison | Oui | Oui | OK | Terminé |
| Notifications §36 : production, centre, lu/tout-lu | Oui | Oui | OK | Terminé |
| Notifications d'état §36 (rappel RDV, tâche en retard) + Scheduler | Oui | Oui | OK | Terminé (2 mutations : idempotence, garde de type) |
| Campagnes §12 : liste, fiche ROI (RG-IND-002), création | Oui | Oui | OK | Terminé |
| Catalogue §28 (Famille/Gamme/Produit, retrait, réactivation §47) | Oui | Oui | OK | Terminé |
| Audit §46 : journal ajout-seul, tracerChamps, écran de lecture | Oui | Oui | OK | Terminé (socle ; producteurs à étendre) |
| Accueil commercial §77 (tuiles) | Oui | Oui | OK | Terminé |
| Rapports §75 (prévisionnel), §38 (motifs de perte), §39 (entonnoir) | Oui | Oui | OK | Terminé (entonnoir prouvé par mutation) |
| Droit d'accès §72 (dossier, trace Export, portée Toutes) | Oui | Oui | OK | Terminé |
| Rapports §76 (ventilation par produit + couverture, RG-IND-001) | Oui | Oui | OK | Terminé (mutation : filtre produit, garde null) |
| Import CSV §42 (deux temps analyse→import, atomique, doublons §41, lot) | Oui | Oui | OK | Terminé (mutations : doublon, trace Echoue) |
| Import .xlsx §42 (LectureClasseur PhpSpreadsheet, valeur calculée) | Oui | Oui | OK | Terminé (mutation : formule vs valeur) |
| Outbox Sage §48 (dépôt au gain dans la transaction, RG-OPP-003) | Oui | Oui | OK | Terminé (mutation : dépôt retiré) |
| Authentification | Oui | (kit) | — | À reprendre en Phase 2 |

## Problèmes / à confirmer
- **P0** : portage des 8 garanties SQL Server → MySQL (ADR-001). Chaque ligne
  devient un test dans sa phase.
- **P0** : stratégie de migration des DONNÉES existantes (le .NET a un volume de
  recette ; MySQL repart de zéro — définir l'export/import si reprise voulue).

## Tests front (Vitest)
- Socle : `vitest.config.ts` séparé de `vite.config.ts` (greffons Laravel/Inertia
  inutiles sous jsdom), `resources/js/test/preparer.ts` mocke `@inertiajs/vue3`.
  Scripts `npm test` / `npm run test:watch`. Vitest 4 (fourni par vite-plus).
- Couverts : `NotesInternes` (8), `Documents` (6), Kanban §64 (5, glisser-déposer
  optimiste), Import §42 (7, deux temps). 26 tests. Filet prouvé par mutation.
- Reste : fiches (lead/société/opportunité), listes (prospection, sociétés).
- Note : le Kanban clonait ses colonnes par `structuredClone` — fragile sur un
  proxy réactif (DataCloneError sous jsdom) ; passé à un clone JSON, robuste.

## Dette technique
- (aucune pour l'instant)
