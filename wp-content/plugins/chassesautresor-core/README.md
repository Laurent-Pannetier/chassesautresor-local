# Chasses au Trésor Core

Cette extension contient les fonctionnalités métier de chassesautresor.com qui doivent rester disponibles
indépendamment du thème actif.

## Responsabilités

- accès et permissions métier ;
- chasses, énigmes, indices et solutions ;
- engagements, points et statistiques ;
- tables personnalisées et migrations ;
- tâches planifiées, commandes WP-CLI et intégrations externes.

Les templates, styles, scripts d'interface et adaptations visuelles d'Astra restent dans le thème
`chassesautresor`.

## Migration progressive

Le code est extrait du thème par petits domaines. Pendant la transition, des fichiers de compatibilité peuvent
rester dans le thème afin de préserver les noms de classes et fonctions existants. Ces façades ne doivent contenir
aucune nouvelle logique métier.
