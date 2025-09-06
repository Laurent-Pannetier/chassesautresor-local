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

## Mode « à la une »

Ce mode affiche une chasse mise en avant avec une image pleine largeur et un bouton d'appel à l'action.

### Exemple avec `WP_Query`

```php
$query = new WP_Query([
    'post_type'      => 'chasse',
    'posts_per_page' => 1,
    'meta_key'       => 'chasse_en_avant',
    'meta_value'     => 1,
]);

get_template_part(
    'template-parts/chasse/boucle-chasses',
    null,
    [
        'query'           => $query,
        'mode'            => 'a_la_une',
        'highlight_label' => __('À la une', 'chassesautresor-com'),
    ]
);
```

Le `grid_class` par défaut est `grille-liste`, mais on peut le modifier via l'argument `grid_class`.
