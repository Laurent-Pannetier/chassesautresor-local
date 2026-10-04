# Agent Instructions

## Code Style
- Follow **PSR-12** for PHP files: 4 spaces for indentation, and lines under 120 characters.
- Keep function and variable names in English when possible.
- Place opening braces on the same line as declarations.
- Wrap all user-facing strings in WordPress internationalization functions and use the `chassesautresor-com` text domain.
- Write CSS and SCSS using a mobile-first approach: base styles for small screens, then extend via media queries.

## Testing
- Before committing any change, run the project tests.
- Use the provided helper script to get the correct PHP and Composer executables:
  ```bash
  source ./setup-env.sh
  composer install
  vendor/bin/phpunit -c tests/phpunit.xml
  ```
- Ensure the test suite passes.

## Internationalisation
- Ne jamais committer les fichiers compilés `.mo` (seuls les fichiers `.po` doivent être versionnés).

## Pull Request Messages
- Begin the PR body with a short summary in French.
- Provide a bullet list of notable changes.
- Add a **Testing** section summarizing the commands executed and their results.

## Notes métier
- Un CPT `indices` gère les indices associés à une chasse ou une énigme.
- Les tables personnalisées incluent `wp_indices_deblocages` et la colonne `indice_id` dans `wp_engagements`.
- Le champ `origin_type` de `wp_user_points` accepte désormais la valeur `indice`.

## Environnements et déploiement (ne pas confondre)

Détail : [`docs/deploy.md`](docs/deploy.md).

| Élément | Rôle |
|---------|------|
| **Ce dépôt** (`chassesautresor-local`) | Bac à sable **Local WP** + tests agents (PHPUnit, Jest, `bin/`, `setup-env.sh`). **Ce n’est pas l’artefact de production.** |
| **`chassesautresor-wp`** | Dépôt Git du **thème seul** déployé en prod Hostinger. |
| **Plugin `chassesautresor-core`** | Mis à jour en prod **séparément**, via l’interface Hostinger (pas via le sync Git du thème). |

### Prod Hostinger (inchangé volontairement)
- Sync Git Hostinger : origine = dépôt `chassesautresor-wp`, branche typique `dev_60` → destination = dossier du thème (`wp-content/themes/chassesautresor`).
- **Ne pas** pointer la destination Git Hostinger sur `wp-content` : cela risquerait d’écraser les autres plugins / thèmes.
- Le tooling de tests de ce monorepo (`vendor/`, `bin/`, suite PHPUnit racine) **n’existe pas** en ligne.

### Avant une mise en ligne
1. Lancer les tests ici (voir section Testing) et `npm run build:css` si le CSS a changé.
2. Synchroniser le thème vers `chassesautresor-wp`, puis déployer via Hostinger Git.
3. Si le Core a changé : déployer le **même état** de `chassesautresor-core` via l’UI Hostinger.
4. Vérifier ACF / migrations SQL si le lot en dépend.
