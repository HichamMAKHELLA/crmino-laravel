# Recette — les critères du §86 (CRMino Laravel)

> Le §86 fixe les critères d'acceptation. Ce document les rend exécutables :
> pour chacun, ce qui le prouve, comment le vérifier, et ce que les tests
> automatisés en couvrent déjà (285 Pest + 70 Vitest).
>
> **Trois critères ne sont pas applicatifs** — HTTPS, sauvegarde, exploitation
> des journaux relèvent du déploiement (§12). Ils sont marqués ⚙.
>
> Un critère « à moitié vérifié » compte comme **non vérifié**. La recette est
> SÉQUENTIELLE : chaque étape s'appuie sur la donnée créée par la précédente.

## Avant de commencer

| Prérequis | Comment l'obtenir |
|---|---|
| Instance déployée (HTTPS) | `docs/deploiement.md` |
| Base migrée + référentiels | `php artisan migrate --force` puis `db:seed --class=ProductionSeeder --force` |
| Premier administrateur | `php artisan crmino:creer-admin admin@imrasoft.ma …` (§5) |
| **Deux** comptes commerciaux d'équipes différentes | Écran Administration → Utilisateurs, après connexion |

> Le deuxième commercial n'est pas facultatif : sans lui, aucun critère de
> **cloisonnement** ne se vérifie — on ne prouve pas qu'un périmètre ferme avec
> un seul utilisateur.

---

## 1. Connexion locale opérationnelle (§5)

- [ ] Ouvrir l'URL → écran de connexion (email + mot de passe), sous-titre §82.
- [ ] Se connecter avec le compte admin → nom affiché, menu complet.
- [ ] Se déconnecter, tenter un email inconnu / un compte **désactivé** → refus,
      message qui le dit (jamais un accès muet).

*Automatisé :* auth de session, refus d'un compte absent/inactif (Pest).
**Non automatisé :** l'expérience de connexion réelle sur le serveur.

## 2. Utilisateurs et droits = donnée (§78)

- [ ] Habiliter un compte `COMMERCIAL`, équipe Nord → son menu ne montre ni
      Journal, ni Import, ni Critères de score.
- [ ] En admin, passer une permission du rôle `COMMERCIAL` à « Toutes »,
      enregistrer ; le commercial recharge → le bouton correspondant apparaît.
- [ ] Le changement de droit n'a demandé **aucun déploiement**.

*Automatisé :* permissions par route, extinction §68, résolution du périmètre.

## 3. Création prospect rapide (§87, `RG-LEA-001`)

- [ ] Nouveau lead avec une seule raison sociale, sans moyen de joindre → refus,
      champ fautif nommé.
- [ ] Ajouter un téléphone → lead créé, **numéro attribué** (§10).
- [ ] Chronométrer : ouverture → fiche créée en moins d'une minute.

*Automatisé :* `RG-LEA-001` (identité OU contact, et un moyen de joindre).

## 4. Doublons détectés (§41)

- [ ] Créer « Zellige Industries », tél `0522334455`.
- [ ] Recréer même raison sociale → « un prospect similaire existe déjà »,
      création **quand même possible**.
- [ ] Variante « Zellige Industries SARL » → détecté (forme juridique retirée).

*Automatisé :* ICE / téléphone / courriel / domaine / raison en préfixe.

## 5. Conversion + bascule d'historique (§31, §8)

- [ ] Sur un lead qualifié avec un contact et une activité → **Convertir**.
- [ ] La société est créée, le décompte annonce contacts + activités + tâches +
      documents + notes basculés.
- [ ] Le lead affiche « converti » et renvoie vers la société ; il ne se
      modifie plus (`RG-LEA-003`).

*Automatisé :* bascule des 5 familles, `RG-LEA-003` (mutations).

## 6. Pipeline et clôtures (§64, `RG-OPP-*`)

- [ ] Ouvrir une affaire sur la société ; elle apparaît dans sa colonne.
- [ ] **Glisser-déposer** la carte vers une autre étape → déplacement immédiat.
- [ ] Ajouter des **lignes** (§28) ; le total suit.
- [ ] **Gagner** → la société devient cliente (`RG-OPP-003`). L'affaire close
      ne change plus d'étape ni de montant (`RG-OPP-005/007`).

*Automatisé :* pondéré généré, clôtures, drag-drop, verrous (mutations).

## 7. Cloisonnement par périmètre (§78, §58)

- [ ] Avec le **second** commercial (autre équipe), ouvrir la liste des leads →
      il ne voit QUE les siens.
- [ ] Coller dans l'URL l'identifiant d'une fiche de l'autre commercial →
      **404** (pas 403 : ne pas révéler l'existence).

*Automatisé :* `RG-HAB-001`, §58 (mutations).

## 8. Rapports (§75, §38, §39, §76)

- [ ] Onglet Prévisionnel → probabilité **effective** par étape.
- [ ] Motifs de perte sur une période ; Entonnoir (paliers décroissants).
- [ ] Ventilation par produit + couverture ; un taux sans base s'affiche « — »,
      jamais 0 % (`RG-IND-002`).

*Automatisé :* les 4 onglets (Pest + Vitest, mutations).

## 9. Import CSV et Excel (§42)

- [ ] Importer un `.csv` de 3 lignes (une valide, une incomplète, un doublon) →
      **aperçu** : 1 importable, 1 rejetée, 1 doublon. Rien n'est écrit encore.
- [ ] Confirmer → import atomique, lot « Terminé » dans l'historique.
- [ ] Recommencer avec un `.xlsx` (une cellule formule) → la **valeur calculée**
      est importée, jamais le texte de la formule.

*Automatisé :* deux temps, atomicité, doublons, lecture classeur (mutations).

## 10. Documents et notes (§44, §45)

- [ ] Déposer un `.pdf` sur une fiche → il se **télécharge en pièce jointe** ;
      un `.exe` est refusé (liste blanche).
- [ ] Ajouter une note ; un autre commercial ne peut ni la corriger ni la
      retirer (auteur seul, 404).

*Automatisé :* liste blanche, retrait logique, auteur seul (mutations).

## 11. Notifications et jobs planifiés (§36)

- [ ] Assigner une tâche à un autre commercial → il reçoit une notification.
- [ ] Créer une tâche échue hier ; lancer `php artisan crmino:notifier` →
      une notification « Tâche en retard » ; relancer → **aucune de plus** (une
      fois par jour).

*Automatisé :* production, idempotence, garde de type (mutations).

## 12. Alertes commerciales (§35)

- [ ] Écran **Alertes** → chaque alerte porte son compteur (borné au périmètre).
- [ ] Désactiver une alerte → elle affiche « désactivée — rien n'est
      surveillé », **jamais 0** (`RG-IND-001`).
- [ ] `CONTRAT_ECHEANCE` / `RELANCE_SANS_REPONSE` sont **nommées** comme non
      calculables, jamais tues.

*Automatisé :* désactivée→null, sans producteur, seuil réservé (mutations).

## 13. Droit d'accès et audit (§72, §46)

- [ ] En admin (portée **Toutes**), produire le dossier d'un contact → il porte
      ses limites et sa date ; une trace `Export` est écrite.
- [ ] Un commercial (portée Siennes) est refusé (403).
- [ ] Journal d'audit → les modifications récentes s'y lisent, champ par champ.

*Automatisé :* dossier non filtré, portée Toutes, trace Export, tracerChamps.

---

## Critères de déploiement (⚙, hors application)

## 14. ⚙ HTTPS

- [ ] L'URL répond en `https://` avec un certificat valide (AutoSSL cPanel) ;
      `http://` redirige vers `https://`.

## 15. ⚙ Sauvegarde

- [ ] Une sauvegarde récente de la base ET de `storage/app/private/documents`
      existe et se restaure (§44 : les pièces jointes ne sont pas en base).

## 16. ⚙ Exploitation des journaux

- [ ] Les journaux Laravel (`storage/logs`) tournent (quotidiens, 14 jours) et
      **ne sont PAS accessibles par le web** (§81).
- [ ] `crmino:purger` (simulation) rapporte des lignes à purger au-delà de la
      rétention (§72).
