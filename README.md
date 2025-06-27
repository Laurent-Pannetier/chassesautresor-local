# WordPress Project

Ce dépôt contient une installation locale de WordPress. Pour exécuter du code PHP ou utiliser Composer dans un environnement reproductible, deux solutions sont proposées :

## Script `setup.sh`

Ce script installe PHP et Composer sur la machine locale :

```bash
./setup.sh
```

Il télécharge PHP via le gestionnaire de paquets `apt` puis installe Composer et le place dans `/usr/local/bin`.

Le script permet d'obtenir rapidement un environnement prêt pour exécuter des commandes PHP ou Composer.

## Utilisation de Composer pour les tests

Un fichier `composer.json` est présent à la racine pour installer PHPUnit et les dépendances de développement. Une fois PHP et Composer disponibles (via `setup.sh`), exécutez :

```bash
composer install
composer test
```

La commande `composer test` lance PHPUnit à l'aide de la configuration du plugin **hostinger**.
