# Agent Instructions

## Code Style
- Follow **PSR-12** for PHP files: 4 spaces for indentation, and lines under 120 characters.
- Keep function and variable names in English when possible.
- Place opening braces on the same line as declarations.
- Wrap all user-facing strings in WordPress internationalization functions and use the `chassesautresor-com` text domain.

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
