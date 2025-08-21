WordPress Project

Ce dépôt contient une installation locale de WordPress. Pour exécuter du code PHP ou utiliser Composer dans un environnement reproductible, deux solutions sont possibles :

Initialisation de l’environnement (setup-env.sh)

Un script setup-env.sh est fourni pour configurer l’environnement local. Il ajoute temporairement les exécutables php et composer (présents dans le dossier bin/) au PATH :

source setup-env.sh

Cette étape est obligatoire avant d’utiliser Composer ou PHP dans ce projet. Elle ne fait qu’ajouter les chemins, sans installation système.

Utilisation de Composer pour les tests

Un fichier composer.json est présent à la racine pour installer PHPUnit et les dépendances de développement. Une fois l’environnement initialisé avec setup-env.sh, exécutez :

composer install
composer test

La commande composer test exécute PHPUnit à l’aide de la configuration du plugin hostinger.

Avant tout déploiement, exécutez `npm run build:css` pour générer les feuilles de style.

Gestion des traductions du thème
--------------------------------

Toutes les chaînes visibles par les utilisateurs doivent être encapsulées dans les fonctions de traduction WordPress (`__`, `_e`, `esc_html__`, etc.) en utilisant le domaine `chassesautresor-com`.
Les fichiers de langues du thème sont placés dans `wp-content/themes/chassesautresor/languages/`.
Pour générer ou mettre à jour le fichier POT, utilisez WP‑CLI :

```bash
wp i18n make-pot ./wp-content/themes/chassesautresor ./wp-content/themes/chassesautresor/languages/chassesautresor-com.pot
```

Ajoutez ensuite vos fichiers `.mo` compilés dans ce même dossier pour charger les traductions en front‑end.

## Tables personnalisées

Le projet utilise plusieurs tables SQL dédiées pour suivre l'activité des joueurs :

- `wp_engagements` enregistre les engagements liés aux énigmes, chasses ou indices (`indice_id`).
- `wp_indices_deblocages` trace le déblocage des indices et les points dépensés.
- `wp_user_points` inclut la valeur `indice` dans le champ `origin_type` pour comptabiliser ces dépenses.

