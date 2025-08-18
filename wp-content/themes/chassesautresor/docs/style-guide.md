# Guide de style

## Points de rupture

Les feuilles de style utilisent des variables CSS définies dans `assets/css/general.css` pour les tailles d'écran.

| Variable | Valeur | Description |
| --- | --- | --- |
| `--bp-xs` | 374px | Très petits écrans |
| `--bp-sm` | 480px | Mobiles |
| `--bp-540` | 540px | Petits mobiles larges |
| `--bp-600` | 600px | Petites tablettes |
| `--bp-md` | 768px | Tablettes |
| `--bp-921` | 921px | Tablettes larges |
| `--bp-lg` | 1024px | Bureau |

Exemple d'utilisation :

```css
@media (max-width: var(--bp-md)) {
  /* styles pour les tablettes */
}
```

## Asides

Les blocs `aside` utilisent un style commun défini dans `assets/css/aside.css`.
L'opacité de leur fond est centralisée par la variable globale
`--aside-opacity` (valeur par défaut `0.4` dans `assets/css/variables.css`).
Cette variable facilite l'harmonisation des asides sur l'ensemble du site.
