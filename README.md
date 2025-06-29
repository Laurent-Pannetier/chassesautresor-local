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

