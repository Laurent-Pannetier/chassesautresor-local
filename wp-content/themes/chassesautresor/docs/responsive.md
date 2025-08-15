# Guide responsive

Ce thème fournit un petit système de grille et des variables de breakpoints pour harmoniser le responsive.

## Breakpoints

| Variable | Valeur | Usage |
|---------|--------|-------|
| `--bp-sm` | 480px | mobiles |
| `--bp-md` | 768px | tablettes |
| `--bp-lg` | 1024px | ordinateurs |
| `--bp-xl` | 1440px | grands écrans |

Utilisez ces variables dans vos media queries :

```css
@media (max-width: var(--bp-md)) {
  /* styles pour tablettes et moins */
}
```

## Grille

- `.container` : centre le contenu et applique une largeur maximale adaptée.
- `.row` : conteneur flex pour aligner les colonnes.
- `.col-12` : colonne pleine largeur.
- `.col-md-6` : demi-largeur à partir du breakpoint `md`, pleine largeur en dessous.

Exemple :

```html
<div class="container">
  <div class="row">
    <div class="col-md-6">Colonne 1</div>
    <div class="col-md-6">Colonne 2</div>
  </div>
</div>
```
