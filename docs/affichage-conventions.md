# Conventions d'affichage

## Partiel `boucle-chasses`

Ce partiel affiche une liste de chasses sous forme de grille.

### Paramètres

- `chasse_ids` (`int[]`) : liste d'IDs de chasses à afficher.
- `query` (`WP_Query`) : requête WordPress retournant des IDs de chasses. Ignoré si `chasse_ids` est défini.
- `show_header` (`bool`) : afficher le titre "Chasses" (défaut `true`).
- `header_text` (`string`) : texte du titre affiché lorsque `show_header` est activé.
- `grid_class` (`string`) : classe CSS de la grille (défaut `grille-liste`).
- `before_items` (`string`) : contenu HTML inséré avant les cartes.
- `after_items` (`string`) : contenu HTML inséré après les cartes.

### Exemple

```php
get_template_part(
    'template-parts/chasse/boucle-chasses',
    null,
    [
        'chasse_ids' => [1, 2, 3],
        'grid_class' => 'ma-grille',
    ]
);
```
