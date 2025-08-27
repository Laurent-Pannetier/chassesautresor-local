# Système de grille

Ce document décrit les grilles disponibles dans le thème afin de faciliter leur réutilisation.

## Grille de base (`.row` / `.col-*`)

La grille générique repose sur un conteneur `.row` utilisant **CSS Grid**. Chaque élément enfant reçoit une classe `.col-X` pour définir le nombre de colonnes occupées (de `1` à `12`).

- Largeur par défaut : `12` colonnes.
- Espacement configurable via la variable `--grid-gap`.
- Les colonnes se réorganisent automatiquement en fonction des points de rupture (`--bp-desktop`, `--bp-tablet`, `--bp-small`).

```html
<div class="row">
  <div class="col-6">Bloc A</div>
  <div class="col-6">Bloc B</div>
</div>
```

## Conteneurs

Les classes `.container`, `.container--narrow` et `.container.fullwidth` permettent de contrôler la largeur maximale de la zone de contenu.

## Grilles utilitaires pour cartes

Certaines pages proposent des grilles prêtes à l'emploi pour organiser des cartes :

- `.grille-liste` : une seule colonne.
- `.grille-3` : trois colonnes, puis deux en dessous de `--bp-desktop` et une en dessous de `--bp-tablet`.

## Grilles de cartes

- `.dashboard-grid` : grille adaptative pour les tuiles du tableau de bord (`minmax(240px, 1fr)`).
- `.stats-cards` : variante pour les cartes de statistiques (`minmax(220px, 1fr)`).

## Grille des pages d’énigme

La classe `.enigme-layout` crée un gabarit à deux colonnes (`320px 1fr`) pour l’édition d’énigmes. En dessous de certains points de rupture ou avec `.enigme-layout--aside-hidden`, la mise en page repasse sur une seule colonne.

## Références

Les styles correspondants se trouvent dans `assets/scss/_grid.scss`, `assets/scss/_cartes.scss`, `assets/scss/_mon-compte.scss`, `assets/scss/_edition.scss` et `assets/scss/_enigme.scss`.
