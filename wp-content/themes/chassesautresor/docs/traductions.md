# Traductions

Ce document regroupe les instructions de développement concernant les fichiers de langue du thème.

## Chargement des traductions

Ajouter dans `functions.php` :

```php
add_action( 'after_setup_theme', function () {
    load_child_theme_textdomain( 'chassesautresor-com', get_stylesheet_directory() . '/languages' );
} );
```

Pour un thème autonome, utiliser `load_theme_textdomain` à la place.

## Compilation des fichiers .po

Compiler un fichier unique :

```bash
msgfmt languages/fr_FR.po -o languages/fr_FR.mo
```

Compiler toutes les traductions :

```bash
for po in languages/*.po; do
  msgfmt "$po" -o "${po%.po}.mo"
done
```

Ne pas versionner les fichiers `.mo` compilés.
