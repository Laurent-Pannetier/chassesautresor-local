# Déploiement et environnements

Document de référence pour les humains et les agents. Ne pas redemander ces infos à Laurent sauf changement de workflow.

## Vue d’ensemble

```
chassesautresor-local  (ce dépôt — Local + tests)
   │
   ├─ thème chassesautresor  ──sync──►  chassesautresor-wp  ──Git Hostinger──►  prod thème
   └─ plugin chassesautresor-core  ──UI / zip Hostinger──►  prod plugin
```

Les deux canaux de prod sont **volontairement séparés**. On ne fusionne pas thème + plugin dans un seul sync Git Hostinger vers `wp-content`.

## Dépôts

### `chassesautresor-local` (ici)

- Installation WordPress complète pour développer avec l’appli **Local**.
- Contient thème, plugin Core, plugins tiers, WP core, suite de tests, `setup-env.sh`, `bin/`.
- Sert aussi de bac à sable pour les agents (Cursor / Codex) qui y ont ajouté tooling de tests.
- **Ne se déploie pas tel quel** sur Hostinger.

### `chassesautresor-wp`

- Contient uniquement le thème enfant Astra `chassesautresor` (fichiers thème à la racine du dépôt : `style.css`, `functions.php`, etc.).
- Branche de déploiement Hostinger habituelle : **`dev_60`** (à confirmer dans hPanel si renommée).
- Destination Hostinger Git : **`wp-content/themes/chassesautresor`** (pas `wp-content`, pas le dossier `themes` parent).

### Plugin `chassesautresor-core`

- Développé et testé dans ce monorepo sous `wp-content/plugins/chassesautresor-core/`.
- En production : mise à jour **manuelle** via l’interface Hostinger (pas via le sync Git du thème).

## Contrainte Hostinger Git

- Une connexion Git = **un dépôt → un dossier destination**.
- Le déploiement **remplace** le contenu de ce dossier.
- Pointer la destination sur `wp-content` pour y coller thème + Core est **refusé** : risque d’écraser WooCommerce, ACF, Astra, etc.
- Décision produit actuelle : **ne rien changer** à ce mapping ; garder Git = thème only.

## Build CSS (Local uniquement)

- Commande : `npm run build:css` à la **racine de `chassesautresor-local`**.
- Entrée : `wp-content/themes/chassesautresor/assets/scss/` → sortie : `wp-content/themes/chassesautresor/dist/style.css`.
- Le dépôt `chassesautresor-wp` **ne compile pas** le CSS : il reçoit le `dist/` déjà généré via le copier-coller Windows du thème.
- Les agents qui modifient du SCSS dans ce monorepo doivent lancer le build **systématiquement** avant commit (voir `AGENTS.md`).

## Checklist avant mise en ligne

1. Sur Local (`chassesautresor-local`) :
   - `source ./setup-env.sh && composer install`
   - `vendor/bin/phpunit -c tests/phpunit.xml`
   - `npm run build:css` si SCSS / styles modifiés (commit / copier aussi `dist/style.css`)
   - éventuellement `npm test` (Jest) si JS touché
2. Propagrer le thème vers le dépôt **`chassesautresor-wp`**, puis déployer la branche Hostinger (`dev_60`).
3. Si le lot touche le Core : déployer le **même état** de `chassesautresor-core` via Hostinger.
4. Ordre conseillé si les deux changent : **plugin puis thème** (ou les deux dans la même session).
5. Si besoin : champs ACF, CPT ACF, migrations / tables SQL custom.
6. Smoke test prod : chasse, énigme, soumission de réponse, mon-compte.

## Ce qui reste hors Git de prod

- Configuration ACF (souvent en base).
- Tables custom et données.
- `wp-config.php`, uploads, cache.
- Plugins tiers (WooCommerce, ACF Pro, Hostinger, etc.).
- Tooling de tests du monorepo local.

## Architecture liée

- Thème = UI (templates, assets, mon-compte).
- Plugin Core = métier (AJAX, services, tables, points, indices, etc.).
- État de la frontière thème / plugin : [`docs/audits/theme-business-boundary.md`](audits/theme-business-boundary.md).
