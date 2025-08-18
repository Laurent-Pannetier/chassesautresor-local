# Plan

## Goal
Adapt the codebase so that the renamed table `wp_engagements` is used
throughout the project instead of the old `wp_enigme_engagements` table.

## Steps
1. Search for all usages of `wp_enigme_engagements` or `enigme_engagements`.
2. Replace these occurrences with the new table name `wp_engagements` when
   building queries with `$wpdb->prefix`.
3. Run the existing test suite via `composer test` (if possible) to ensure no
   regression.
4. Commit the changes.

This plan will keep business logic intact and simply update the table name
references.


## Breakpoint preprocessing

To ensure CSS breakpoints declared as custom properties are resolved before minification, introduce a preprocessing step with PostCSS custom media.

### Tasks
1. Install the `postcss-custom-media` plugin as a dev dependency.
2. Update `build-css.js` to include the plugin before autoprefixing.
3. Define breakpoints using `@custom-media` rules in `variables.css`.
4. Replace usages of `var(--bp-*)` in media queries with the corresponding `@media (--bp-*)` syntax across CSS files.
5. Run the existing CSS build and ensure the compiled output contains concrete pixel values for breakpoints.
6. Execute the PHP and JS test suites to confirm no regressions.
