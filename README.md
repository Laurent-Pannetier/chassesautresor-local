# WordPress Project

Ce dépôt contient une installation locale de WordPress. Pour exécuter du code PHP ou utiliser Composer dans un environnement reproductible, deux solutions sont proposées :

## Script `setup.sh`

Ce script installe PHP et Composer sur la machine locale :

```bash
./setup.sh
```

Il télécharge PHP via le gestionnaire de paquets `apt` puis installe Composer et le place dans `/usr/local/bin`.

Le script permet d'obtenir rapidement un environnement prêt pour exécuter des commandes PHP ou Composer.
