# Déploiement de CRMino (cPanel + MariaDB)

Cible : hébergement mutualisé **cPanel**, base **MariaDB**, PHP **8.4**. L'auth est
LOCALE (email + mot de passe, session) ; il n'y a aucune dépendance externe
(pas de Redis, pas de service de file d'attente séparé — tout passe par la base).

## 1. Prérequis serveur

- PHP 8.4 avec les extensions : `pdo_mysql`, `mbstring`, `openssl`, `ctype`,
  `json`, `bcmath`, `fileinfo`, `zip`, `gd` (miniatures), `intl` (translittération
  des en-têtes d'import §42).
- Une base MariaDB et un utilisateur dédié, avec tous les droits **sur cette base
  seulement**.
- Accès **Terminal** cPanel (ou SSH) pour Composer et Artisan.
- Un certificat HTTPS (AutoSSL cPanel) sur `crm.imrasoft.ma`.

## 2. Fichiers

1. Déposer le code hors de `public_html` — par exemple `~/crmino`.
2. Faire pointer le **document root** du sous-domaine vers `~/crmino/public`
   (cPanel → Domaines → Document Root). Ne JAMAIS exposer la racine du projet :
   `.env`, `storage/` et `documents/` (§44) doivent rester hors du web.

## 3. Dépendances et configuration

```bash
cd ~/crmino
composer install --no-dev --optimize-autoloader
cp .env.production.example .env
php artisan key:generate
```

Éditer `.env` : `APP_URL`, les identifiants `DB_*`, le SMTP. Laisser
`APP_DEBUG=false` (le §81 interdit d'exposer une trace technique).

Construire le SPA (sur une machine avec Node, puis téléverser `public/build`, ou
en Terminal si Node est disponible) :

```bash
npm ci && npm run build
```

## 4. Schéma et amorçage

```bash
php artisan migrate --force
php artisan db:seed --class=ProductionSeeder --force
```

`ProductionSeeder` pose les rôles, la matrice §78 et les référentiels — **aucun
compte de démonstration ni catalogue d'exemple**. Le catalogue réel se saisit
dans l'application (§28).

## 5. Premier administrateur (§5)

Il n'existe aucune auto-inscription : le premier compte se pose hors application.

```bash
php artisan crmino:creer-admin admin@imrasoft.ma --prenom=Hicham --nom=Makhloufi
```

Le mot de passe est demandé en saisie masquée (min. 12 caractères). Ce compte
crée ensuite les autres depuis l'écran d'administration.

## 6. Cache de production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

À rejouer après tout changement de `.env` ou de routes (ou `php artisan
optimize:clear` pour tout purger).

## 7. Tâche planifiée (§36)

Les notifications d'état (rappel RDV, tâche en retard) sont produites par le
Scheduler. Ajouter UNE entrée cron cPanel qui bat chaque minute :

```
* * * * * cd ~/crmino && php artisan schedule:run >> /dev/null 2>&1
```

Le Scheduler lance `crmino:notifier` toutes les 15 minutes ; la commande est
idempotente (sûre à rejouer). Vérifier une fois à la main :

```bash
php artisan crmino:notifier
```

## 8. Permissions

- `storage/` et `bootstrap/cache/` inscriptibles par PHP (généralement 755, le
  propriétaire étant le compte cPanel).
- Le disque des documents (§44) est `storage/app/private/documents` — **jamais
  sous `public/`**. Aucun `storage:link` n'est nécessaire.

## 9. Vérification

- Ouvrir `https://crm.imrasoft.ma` → écran de connexion.
- Se connecter avec le compte admin, créer un utilisateur, un lead, une société.
- Déposer un document, vérifier qu'il se télécharge en pièce jointe.
- Lancer un import CSV de deux lignes ; vérifier l'aperçu puis l'import.
- Confirmer que `storage/logs` n'est pas accessible par le web.

## 10. Sauvegarde

- Base MariaDB : sauvegarde cPanel quotidienne (ou `mysqldump`).
- `storage/app/private/documents` : les pièces jointes ne sont PAS en base —
  une restauration qui les oublie rend des fiches aux documents absents.
