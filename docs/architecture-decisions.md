# Registre des décisions d'architecture — Migration CRMino .NET → Laravel

> CRMino existe en **ASP.NET Core 10 + Dapper + SQL Server + React** (dépôt
> séparé). Ce projet le **réécrit** en **Laravel + Inertia + Vue + MySQL**. Le
> .NET reste la **référence de parité** : aucune fonctionnalité ne disparaît
> sans décision écrite ici.

---

## ADR-001 — MySQL remplace SQL Server

**Décision.** La base cible est **MySQL** (8.0.16+ requis pour les `CHECK`).

**Raison.** L'hébergement visé (cPanel) ne fournit que MySQL/MariaDB. Choix de
Hicham, 04/09/2026.

**Conséquence — la plus lourde de tout le projet.** Le schéma SQL Server de
CRMino encode des GARANTIES au niveau base que MySQL ne reproduit pas. Chacune
doit être **re-garantie en code**, sinon la règle métier qu'elle protège est
silencieusement perdue. Registre de portage :

| Garantie SQL Server (CRMino .NET) | MySQL | Portage retenu |
|---|---|---|
| `DENY UPDATE, DELETE ON SCHEMA::audit` (audit **immuable**, §46) | Pas de DENY de schéma | **Utilisateur MySQL applicatif SANS `UPDATE`/`DELETE`** sur `audit_*` (GRANT limité) + aucune écriture d'update en code + test qui prouve le refus |
| **Full-Text** sur 4 tables (§40, recherche globale) | `FULLTEXT` InnoDB, sémantique différente | `FULLTEXT ... MATCH/AGAINST` MySQL, **re-tester** le comportement du §40 (préfixe, accents, nombres) |
| **4 `SEQUENCE`** (`LEAD-2026-00001`, §10) | Pas de `SEQUENCE` | Table de compteurs + `SELECT ... FOR UPDATE` en transaction (génération atomique), jamais `MAX()+1` |
| **2 colonnes `PERSISTED`** (`Opportunite.MontantPondere`, `OpportuniteLigne.MontantHt`) | `GENERATED ... STORED` (8.0+) | **Colonne générée STORED** MySQL, + test d'échelle (`decimal(19,4)`) |
| **40 index uniques filtrés** (`WHERE Actif = 1`) | Pas d'index partiel | Unicité **applicative** (`Rule::unique()->where('actif',1)`) + index non-unique ; à défaut colonne générée nulle-si-inactif |
| **46 `CHECK`** (énumérations) | `CHECK` dès 8.0.16 | Garder en `CHECK` **et** doubler par `Enum` PHP + Form Request |
| `datetime2(3)` / `decimal(19,4)` / `nvarchar` | `DATETIME(3)` / `DECIMAL(19,4)` / `VARCHAR` **utf8mb4** | Types équivalents, collation `utf8mb4` (accents) |
| `newsequentialid()` (PK `uniqueidentifier`) | `CHAR(36)` UUID ou `BIGINT` auto-inc | **UUID (`ulid`/`uuid`)** conservé pour garder les mêmes clés que le .NET |

**Statut.** Registre établi. Chaque ligne devient un test Pest lors de la phase
concernée.

---

## ADR-002 — Session Laravel + Sanctum remplace le jeton HMAC maison

**Décision.** Authentification par **session Laravel** (cookies, CSRF) via le
kit Vue ; Sanctum réservé à une éventuelle API externe.

**Raison.** Application interne servie par Inertia : la session suffit et
supprime la gestion manuelle du jeton HMAC (`JetonLocal`) et du hachage
(`HachageMotDePasse` → `Hash::` bcrypt/argon2).

**Impact.** Le verrouillage anti-force (`RG-AUTH-002`) se porte sur le limiteur
Laravel + `throttle`. L'identifiant stable `EntraObjectId` du .NET devient un
simple `uuid` de compte (plus d'Entra).

---

## ADR-003 — Le périmètre par ligne devient Global Scope + Policies

**Décision.** `ClausePerimetre` (Siennes / Équipe / Toutes) → **Global Scope**
Eloquent (`PerimetreScope`) appliqué aux modèles de volume + **Policies/Gates**
pour l'écriture.

**Raison.** C'est le point de sécurité le plus sensible (`RG-HAB-*`). Un scope
global centralise la clause `WHERE`, comme `ClausePerimetre` le faisait — un
seul endroit produit la restriction. **Règle conservée : on voit TOUJOURS ses
propres fiches, en `OU` avec le périmètre, jamais en `ET`.**

**Impact.** Chaque lecture passe par le scope ; chaque écriture par une Policy.
Test obligatoire par mutation (casser le scope doit faire tomber un test).

---

## ADR-004 — Dépôt séparé, .NET comme référence de parité

**Décision.** Laravel vit dans **son propre dépôt** (`crmino-laravel`) ; le
CRMino .NET reste **intact** et sert de référence de parité fonctionnelle.

**Raison.** Migration progressive, module par module (§30). On compare chaque
module au .NET avant de le déclarer terminé (§34).

---

## ADR-005 — Kit Vue officiel comme socle front

**Décision.** Front bâti sur le **Starter Kit Vue** de Laravel (Inertia 2 +
Vue 3 `<script setup>` + shadcn-vue + TypeScript + Wayfinder), pas un SPA
API-centric.

**Raison.** §16/§17 du cahier de migration : Inertia comme pont, pas de
séparation API/SPA inutile. Le kit fournit déjà login, réinitialisation de mot
de passe et réglages — la Phase 2 s'appuie dessus.
