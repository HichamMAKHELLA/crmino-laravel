# Récapitulatif de parité — CRMino .NET → Laravel

Référence de parité : le dépôt **CRMino .NET** (ASP.NET Core 10 + Dapper + SQL
Server + React). Cible livrée : **Laravel 13 + Inertia 2 + Vue 3 + MariaDB**.
Base neuve (aucune reprise de données). L'authentification est locale (session).

## Chiffres

| | .NET (référence) | Laravel (livré) |
|---|---|---|
| Tests back | ~812 (.NET) | **285 (Pest)** — chaque règle prouvée par mutation |
| Tests front | ~917 (Vitest) | **70 (Vitest)**, 13 fichiers — écrans à logique |
| Migrations | DbUp | **33** |
| Contrôleurs | 24 | **18** |
| Modèles | — | **44** |
| Classes de domaine / support | 17 (`Domain/`) | **18** (`app/Support/`) |
| Commandes console | `--migrer/--notifier/…` | **3** (`crmino:notifier`, `crmino:purger`, `crmino:creer-admin`) |
| Pages Vue | 25 (React) | **33** |

Les nombres de tests diffèrent par CHOIX : le portage éprouve chaque règle
métier `RG-*` et chaque doctrine par un ou deux faits + une mutation, plutôt
que de reproduire un à un les ~1 700 tests du .NET. La discipline est la même —
un test qui ne tombe pas sur sa mutation ne compte pas.

## Modules — parité fonctionnelle

| Module (CDC) | .NET | Laravel | Preuve |
|---|---|---|---|
| Rôles + périmètre `RG-HAB-001` (§78) | Oui | **Oui** | `ClausePerimetre` → trait `AvecPerimetre::scopeDansPerimetre` ; mutation |
| Catalogue 53 permissions + matrice §78 + extinction §68 | Oui | **Oui** | résolution où §68 prime, mutation |
| Périmètre → 404 pas 403 (§58) | Oui | **Oui** | `abort_unless(… dansPerimetre …, 404)` |
| Référentiels §50 (forme commune, `RG-REF`) | Oui | **Oui** | base `Referentiel`, 25 référentiels semés |
| Leads : création `RG-LEA-001`, doublons §41 | Oui | **Oui** | `ReglesLead`, `DetecteurDoublons`, mutations |
| Leads : édition §5 (liste blanche, propriétaire exclu, `RG-LEA-003`) | Oui | **Oui** | mutations (exclusion, RG-LEA-003) |
| Qualification §13 (satellite, bloc Sage) | Oui | **Oui** | mutations (bloc Sage, RG-LEA-003) |
| Score §15 (8 critères pondérés, hors d'atteinte `RG-LEA-004`) | Oui | **Oui** | `ScoreSuggestion` ; mutations (RDV effectué, évaluabilité) |
| Admin critères de score §15/§50 (diagnostic) | Oui | **Oui** | mutation (évaluabilité) |
| Sociétés : liste, fiche, édition §18 (état/propriétaire/Sage exclus) | Oui | **Oui** | mutation (exclusion) |
| État de la relation §32 (`RG-SOC-001`) | Oui | **Oui** | `TransitionSociete` ; mutation |
| Conversion §31 + bascule §8 (5 familles) | Oui | **Oui** | `ConversionLead` ; mutations (tâches, docs/notes) |
| Contacts (rattachement `CK`, décisionnaire) | Oui | **Oui** | via fiches parentes |
| Opportunités : pondéré généré `RG-OPP-001` | Oui | **Oui** | colonne `storedAs` (schéma) |
| Opportunités : création §25, `RG-OPP-004` | Oui | **Oui** | mutation |
| Pipeline board §64 + glisser-déposer (`RG-OPP-005`) | Oui | **Oui** | déplacement optimiste ; mutations |
| Clôture gain/perte `RG-OPP-002/003/005` | Oui | **Oui** | `ClotureOpportunite` ; mutations |
| Lignes §28 + `RG-OPP-006` | Oui | **Oui** | montant HT généré ; mutations |
| Édition affaire §25 + `RG-OPP-007` (montant close) | Oui | **Oui** | mutation |
| Activités §19/§20 + répercussion + frise §73 | Oui | **Oui** | Pest |
| Tâches §20/§21 : « Mes tâches », tri, terminaison | Oui | **Oui** | Pest + Vitest |
| Notifications §36 : centre, lu/tout-lu | Oui | **Oui** | Pest |
| Notifications d'état §36 (rappel RDV, retard) + Scheduler | Oui | **Oui** | `crmino:notifier` ; mutations (idempotence, type) |
| Campagnes §12 : liste, ROI `RG-IND-002`, fiche | Oui | **Oui** | Pest + Vitest |
| Catalogue §28 + réactivation §47 | Oui | **Oui** | Pest |
| Rapports §75/§38/§39/§76 (4 onglets) | Oui | **Oui** | mutations ; Vitest |
| Accueil §77 (tuiles, `RG-IND-001`) | Oui | **Oui** | Pest + Vitest |
| Import CSV **et Excel** §42 (deux temps, atomique) | Oui | **Oui** | `LectureTabulee`/`LectureClasseur` ; mutations |
| Documents §44 (liste blanche, pièce jointe, retrait §47) | Oui | **Oui** | `StockageDocuments` ; mutation |
| Notes internes §45 (auteur seul, 404 pas 403) | Oui | **Oui** | mutation |
| Audit §46 (ajout seul, tracerChamps) | Oui | **Oui** | `Audit` ; Pest |
| Droit d'accès §72 (dossier, trace Export, portée Toutes) | Oui | **Oui** | mutations |
| Alertes §35 (paramétrage, désactivée→null, sans producteur) | Oui | **Oui** | `Alertes` ; mutations |
| Outbox Sage §48 (dépôt au gain, transaction) | Oui | **Oui** | mutation ; atomicité éprouvée |
| Purge de rétention §72 (`crmino:purger`) | Oui | **Oui** | mutations (simulation, filtre lue) |
| Déploiement §12 (cPanel/MariaDB, bootstrap admin §5) | — | **Oui** | 3 tests admin ; caches vérifiés |

## Écarts assumés (Laravel)

- **Score §15 — automatisme suggéré, jamais écrit d'office.** Fidèle au .NET :
  `ScoreSuggestion` calcule, le commercial applique. Aucun chemin n'écrit le
  score d'office.
- **Alertes sans producteur.** `CONTRAT_ECHEANCE` (parc installé §34 non porté)
  et `RELANCE_SANS_REPONSE` (l'activité n'enregistre aucun sens) sont NOMMÉES
  comme non calculables — même posture que le .NET.
- **Parc installé §34 / suggestions ventes additionnelles §33** : non portés
  (hors périmètre du CRUD commercial livré). Le critère de score DECISIONNAIRE
  se lit sur `contacts.decisionnaire`.
- **WhatsApp §70, formulaire public §69, recherche globale §40** : non portés —
  fonctions de phase ultérieure côté .NET, hors du socle commercial.
- **Intégration Sage 100 §48** : point d'extension RÉSERVÉ (table `outbox`
  alimentée au gain), aucun répartiteur — comme le Lot 1 .NET.

## Doctrines de fidélité tenues

- **Un seul endroit produit la clause de périmètre** (trait `AvecPerimetre`).
- **Hors périmètre → 404, jamais 403** (§58).
- **Un taux/décompte sans base est NULL, jamais zéro** (`RG-IND-001/002`), y
  compris une alerte désactivée.
- **Suppression logique** partout (`actif`), audit append-only.
- **Migrations append-only**, colonnes générées tenues par le schéma.
- **Chaque règle prouvée par mutation** : casser la garde fait tomber le fait,
  restaurer le remonte.
