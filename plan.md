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

