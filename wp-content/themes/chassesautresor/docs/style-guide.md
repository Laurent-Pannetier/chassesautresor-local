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
