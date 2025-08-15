# Charte graphique – Mode édition

Ce document regroupe les styles utilisés pour l'affichage en **mode édition**. Ce mode est appliqué aux panneaux d'édition des CPT et aux pages `mon-compte/*`.

## Activation

Le mode édition est actif lorsque la classe `mode-edition` est présente sur la balise `<body>`. Cette classe est ajoutée automatiquement :

- sur les pages `mon-compte/*` ;
- lorsque l'utilisateur ouvre un panneau d'édition (`organisateur`, `chasse`, `énigme`).

## Feuilles de style

- `assets/css/edition.css` : styles des panneaux d'édition frontaux.
- `assets/css/mon-compte.css` : mise en page du tableau de bord utilisateur.
- `assets/css/general.css` : variables CSS du mode édition sous la classe `.mode-edition`.

## Structure et composants

- **Panneaux d'édition** : `.edition-panel` et dérivés.
- **Tableau de bord** : `.myaccount-layout`, `.myaccount-sidebar`, `.dashboard-nav`.
- **Cartes et tableaux** : utilisation de bordures `1px` et rayons `8px`.

## Typographie

Le mode édition utilise la police principale définie par `var(--font-main)`.

## Palette de couleurs

Les variables ci‑dessous sont disponibles sous `.mode-edition` :

```css
--color-editor-background
--color-editor-border
--color-editor-text
--color-editor-text-muted
--color-editor-heading
--color-editor-accent
--color-editor-button
--color-editor-button-hover
--color-editor-error
--color-editor-success
--color-editor-success-hover
--color-editor-field-hover
--color-editor-placeholder
```

Elles assurent une cohérence visuelle entre les panneaux et le tableau de bord.
