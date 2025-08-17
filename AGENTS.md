# Agent Instructions

## Code Style
- Follow **PSR-12** for PHP files: 4 spaces for indentation, and lines under 120 characters.
- Keep function and variable names in English when possible.
- Place opening braces on the same line as declarations.

## Internationalization
- Enclose all user-facing text in WordPress internationalization functions (`__()`, `_e()`, `esc_html__()`, etc.).
- Use the translation domain `chassesautresor` or `chassesautresor-com` where appropriate.

## Testing
- Before committing any change, run the project tests.
- Use the provided helper script to get the correct PHP and Composer executables:
  ```bash
  source ./setup-env.sh
  composer install
  vendor/bin/phpunit -c tests/phpunit.xml
  ```
- Ensure the test suite passes.

## Pull Request Messages
- Begin the PR body with a short summary in French.
- Provide a bullet list of notable changes.
- Add a **Testing** section summarizing the commands executed and their results.

