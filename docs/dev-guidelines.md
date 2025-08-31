# Guides de développement

## Bonnes pratiques

- Utiliser des clés de traduction (`message_key`) pour les messages et laisser WordPress effectuer la localisation.
- Renseigner le champ `expires_at` pour les messages temporaires et purger régulièrement via `UserMessageRepository::purgeExpired()`.
- Accéder à la table `wp_user_messages` exclusivement via `UserMessageRepository` plutôt que des requêtes SQL directes.
