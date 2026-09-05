# CRMino — Laravel

CRM commercial d'IMRASOFT : prospection, sociétés, opportunités, activités,
campagnes, catalogue, rapports. Portage du CRMino **.NET** (ASP.NET Core +
Dapper + SQL Server + React) vers **Laravel 13 + Inertia 2 + Vue 3 + MariaDB**,
le dépôt .NET servant de référence de parité. Base neuve, authentification
locale (session). Code, données et interface en **français**.

## Pile technique

- **Back** : Laravel 13, MariaDB, Pest.
- **Front** : Inertia 2, Vue 3 `<script setup>`, TypeScript, Tailwind v4,
  shadcn-vue, Vitest.
- **Outillage** : Laravel Herd (PHP 8.4), Vite (`vite-plus`).

## Couverture

- **285 tests Pest** (back) — chaque règle de gestion `RG-*` et chaque doctrine
  éprouvée par mutation (casser la garde fait tomber le fait).
- **70 tests Vitest** (front) — composants, Kanban, import, fiches, listes,
  agenda, dashboard, rapports.

## Modules livrés

Rôles + périmètre (§78, `RG-HAB-001`), leads + doublons §41 + qualification §13
+ score §15, sociétés + conversion §31 + bascule §8, opportunités (pipeline §64,
lignes §28, clôtures `RG-OPP-*`), activités/tâches, notifications §36 + jobs
planifiés, campagnes §12, catalogue §28, rapports §75/§38/§39/§76, accueil §77,
import CSV/Excel §42, documents §44, notes §45, audit §46, droit d'accès §72,
alertes §35, Outbox Sage §48, purge de rétention §72.

## Démarrer en local

```bash
composer install
cp .env.example .env && php artisan key:generate
# renseigner DB_* (MariaDB) dans .env
php artisan migrate --seed
npm install && npm run dev
```

Lancer les suites :

```bash
php artisan test      # Pest (back)
npm test              # Vitest (front)
npm run types:check   # vue-tsc
```

## Documentation

- [`docs/parite.md`](docs/parite.md) — récapitulatif de parité .NET → Laravel.
- [`docs/recette.md`](docs/recette.md) — checklist de recette (§86).
- [`docs/deploiement.md`](docs/deploiement.md) — déploiement cPanel + MariaDB.
- [`docs/architecture-decisions.md`](docs/architecture-decisions.md) — ADR.
- [`MIGRATION_STATUS.md`](MIGRATION_STATUS.md) — état d'avancement détaillé.

## Déploiement (résumé)

Cible cPanel + MariaDB. Amorçage de production sans données de démo, premier
administrateur hors application (§5), notifications d'état planifiées :

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --class=ProductionSeeder --force
php artisan crmino:creer-admin admin@imrasoft.ma --prenom=… --nom=…
npm ci && npm run build
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Cron du Scheduler (produit les alertes/notifications, §36) :

```
* * * * * cd ~/crmino && php artisan schedule:run >> /dev/null 2>&1
```

Voir [`docs/deploiement.md`](docs/deploiement.md) pour la procédure complète.
