<!--
Modèle de PR CRMino. Remplissez chaque section ; cochez ce qui s'applique.
Une case laissée vide vaut « non fait » — ne cochez que ce que vous avez vérifié.
-->

## Objet

<!-- Que fait cette PR, et pourquoi ? Une ou deux phrases. -->

## Parité .NET

<!--
CRMino Laravel porte le CRMino .NET, qui fait foi. Nommez ici le §CDC et/ou
le code de règle (RG-*, ET-*) concernés, et le fichier .NET de référence.
Écrivez « sans objet » si la PR ne touche à aucun comportement métier.
-->

- Sections / règles : <!-- ex. §64, RG-OPP-005 -->
- Référence .NET :

## Nature du changement

- [ ] Nouveau module / écran
- [ ] Correction de bug
- [ ] Refactorisation (comportement inchangé)
- [ ] Documentation
- [ ] Outillage / CI

## Preuve par mutation

<!--
Chaque règle métier introduite ou modifiée doit tomber quand on casse sa garde :
sauvegarde du fichier → altération de la garde → test → le fait tombe → restauration.
Décrivez la ou les mutations jouées, ou « sans objet » pour un changement non métier.
-->

- [ ] Chaque garde ajoutée/modifiée a été prouvée par mutation.
- [ ] Aucune règle métier n'est concernée (sans objet).

Mutations jouées :

## Vérifications locales

- [ ] `php artisan test` — suite Pest verte
- [ ] `npm test` — Vitest vert
- [ ] `npm run types:check` — vue-tsc sans erreur
- [ ] `npm run build` — build sans erreur
- [ ] `MIGRATION_STATUS.md` mis à jour (si un module avance)

## Contraintes de sécurité

<!-- Cochez uniquement ce que la PR respecte, quand c'est pertinent. -->

- [ ] Aucune donnée commerciale supprimée physiquement (suppression logique `actif`)
- [ ] Périmètre produit au seul endroit prévu (`scopeDansPerimetre`)
- [ ] Hors périmètre → 404, jamais 403 (§58)
- [ ] Migrations en ajout seul (aucun script livré modifié)
- [ ] Aucun secret dans le dépôt
- [ ] Audit en ajout seul

## Notes pour la relecture

<!-- Points d'attention, décisions à valider, écarts assumés. -->
