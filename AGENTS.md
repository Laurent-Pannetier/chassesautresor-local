# Agent Instructions

## Code Style
- Follow **PSR-12** for PHP files: 4 spaces for indentation, and lines under 120 characters.
- Keep function and variable names in English when possible.
- Place opening braces on the same line as declarations.
- Wrap all user-facing strings in WordPress internationalization functions and use the `chassesautresor-com` text domain.
- Write CSS and SCSS using a mobile-first approach: base styles for small screens, then extend via media queries.

## CSS / SCSS (obligatoire dans ce monorepo)
- Le build CSS vit **ici** (`chassesautresor-local`), pas dans `chassesautresor-wp` (qui n’a pas de `package.json` / `build:css`).
- Feuille servie en front : `wp-content/themes/chassesautresor/dist/style.css` (le `style.css` racine du thème n’est que l’en-tête WP).
- **Dès qu’un fichier SCSS du thème est modifié** (ou qu’un changement doit se refléter dans `dist/`), exécuter **systématiquement** avant commit / fin de lot :
  ```bash
  npm install
  npm run build:css
  ```
- Committer le `dist/style.css` régénéré avec les sources SCSS.
- Ne pas demander à Laurent de lancer ce build : c’est la responsabilité de l’agent sur ce dépôt.

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
- Le plugin Core installe aussi via `dbDelta` : `wp_engagements`, `wp_indices_deblocages`,
  `wp_enigme_tentatives`, `wp_enigme_statuts_utilisateur` (activation + `plugins_loaded` maybeUpgrade).

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
1. Lancer les tests ici (voir section Testing).
2. Si le SCSS a changé : `npm run build:css` **dans ce dépôt** (voir section CSS / SCSS), puis inclure `dist/style.css` dans le copier-coller thème → `chassesautresor-wp`.
3. Synchroniser le thème vers `chassesautresor-wp`, puis déployer via Hostinger Git.
4. Si le Core a changé : déployer le **même état** de `chassesautresor-core` via l’UI Hostinger.
5. Vérifier ACF / migrations SQL si le lot en dépend.

## Cursor Cloud specific instructions

- PHP 8.3, Composer (`bin/composer` via `source ./setup-env.sh`), Node, MariaDB et WP-CLI sont fournis par l’environnement. MariaDB et `wp server` démarrent au boot sur `http://127.0.0.1:8080`.
- La base suit `wp-config.php` : base `local`, utilisateur `root`, mot de passe `root`, hôte `localhost`. Sur cette image, le socket MariaDB est `/run/mysqld/mysqld.sock` (`/var/run` n’est pas un lien vers `/run`).
- Une base vide est installée au boot : compte `admin` / `admin`, thème `chassesautresor`, plugins ACF Pro et `chassesautresor-core`. Le mot de passe du site (visiteurs non connectés) est `citizen` par défaut (`ca_site_password`).
- WooCommerce n’est pas activé : des paquets `vendor/` sont ignorés par Git et le plugin ne démarre pas. Le script de boot écrit `wp-content/mu-plugins/cloud-woocommerce-shim.php` pour fournir `is_account_page()`, `is_cart()` et `wc_get_account_endpoint_url()`. Ne pas committer ce fichier.
- Tests : `source ./setup-env.sh && vendor/bin/phpunit -c tests/phpunit.xml`, puis `npm test`. CSS : `npm run build:css`.
- Deux tests PHPUnit échouent sur PHP 8.3 (`RiddleSolutionFilePolicyServiceTest::testPastPublicationIsDeferredByAtLeastFiveSeconds` et `RiddleSolutionFileSchedulerTest::testSchedulerAppliesMinimumDelay`) : `strtotime('+-1 days ...')` est lu comme +1 jour. Ce n’est pas un échec d’installation.
