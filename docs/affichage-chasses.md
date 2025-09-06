# Affichage des chasses

Le partiel `template-parts/chasse/boucle-chasses.php` permet d'afficher une liste de chasses.

## Mode « carte »

Passer l'argument `mode` à `carte` génère des cartes compactes disposées dans une grille adaptative (`cards-grid`).

### Exemple basique

```php
get_template_part(
    'template-parts/chasse/boucle-chasses',
    null,
    [
        'chasse_ids' => [1, 2, 3],
        'mode'       => 'carte',
    ]
);
```

### Personnalisation de la grille

La classe de grille par défaut est `cards-grid`. On peut la remplacer ou l'étendre via l'argument `grid_class` :

```php
get_template_part(
    'template-parts/chasse/boucle-chasses',
    null,
    [
        'chasse_ids' => [1, 2, 3],
        'mode'       => 'carte',
        'grid_class' => 'cards-grid ma-grille-personnalisee',
    ]
);
```
