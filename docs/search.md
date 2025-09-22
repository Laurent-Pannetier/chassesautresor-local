# Recherche dans les tableaux

Ce module centralise la configuration des recherches back-office afin de pouvoir
réutiliser la même logique pour les chasses, tentatives ou tout autre tableau
administratif.

## Convention de nommage

- Les paramètres de recherche sont regroupés sous `search[...]`.
- La clé de contexte se retrouve dans `search[context]` (ex. `search[context]=tentatives`).
- Le terme saisi est exposé via `search[<contexte>]` (ex. `search[tentatives]=beurre`).
- Les scripts JS et PHP utilisent la valeur de `search[context]` pour déduire le
  comportement attendu.

## API PHP

### `ca_register_search_context(string $key, array $args)`

Enregistre un contexte de recherche avec :

- `fields.sql` : colonnes SQL à filtrer (`table.colonne`).
- `fields.wp_query` : fragments de meta-query pour `WP_Query` (chaîne `meta_key`
  ou tableau de configuration).
- `count_callback` : callable optionnel pour calculer le nombre de résultats.
- `ui` : métadonnées d'interface (`label`, `placeholder`, `submit_label`,
  `submit_icon`, `submit_icon_only`, `capability`, `nonce_action`,
  `nonce_name`, `hidden_fields`, `description`).
- `hidden_fields` : champs cachés ajoutés systématiquement au formulaire.
- `pagination_params` : liste de paramètres à purger lors d'une nouvelle
  recherche (`tentatives-page`, `page`, etc.).
- `ui.show_reset_button` : afficher un bouton de réinitialisation lorsque
  l'utilisateur a déjà saisi une recherche.
- `ui.reset_label` : personnalise l'intitulé du bouton de réinitialisation.

> ℹ️ **Interface standard** : pour reproduire l'affichage utilisé sur les
> listes front-office (loupe seule dans le bouton), associer toujours
> `submit_icon` à `search` et activer `submit_icon_only`.

### `ca_resolve_search_context(string $key): array`

Retourne la configuration normalisée enregistrée pour le contexte.

### `ca_get_search_term(string $key): string`

Récupère le terme de recherche provenant de `$_GET`, `$_POST` ou d'une requête
AJAX (`filter_input(INPUT_POST, ...)`), puis le normalise via `wp_unslash()`.

### `ca_apply_search_filters(string $key, array|string $base_query, ?string $term = null)`

Prépare les fragments `WHERE` (requêtes SQL) ou enrichit le `meta_query`
(`WP_Query`) avec un `LIKE %term%` sécurisé (`$wpdb->esc_like()`). La fonction
retourne les arguments augmentés et expose des hooks spécifiques
(`ca_apply_search_filters_sql*`, `ca_apply_search_filters_wp_query`).

### `cta_render_search_form(string $key, array $overrides = []): string`

Génère le formulaire `<form class="table-search">` associé à un contexte :

- rend le champ `<input type="search">` avec placeholder, label et valeur
  pré-remplie (`ca_get_search_term()`).
- ajoute automatiquement `search[context]`, préserve le paramètre `section` et
  fusionne les `hidden_fields` déclarés.
- accepte des surcharges (`method`, `action`, `hidden_fields`, `submit_label`,
  `show_reset_button`, `reset_label`, `data_attributes`, etc.) et insère un nonce si demandé.
- garantit la présence de la classe de base `table-search` même lorsque des
  modificateurs (`table-search--inline`, `table-search--compact`, ...) sont fournis.
- renseigne `data-reset-pagination` pour que le script JS supprime les anciens
  paramètres de pagination.

## API JavaScript

Le fichier `assets/js/core/table-search.js` automatise les interactions côté
navigateur :

- supprime les paramètres `page`, `paged` et ceux déclarés dans
  `data-reset-pagination` avant soumission ;
- propage `search[context]` dans l'URL générée ;
- déclenche l'événement `tablesearch:submit` (bubbling, annulable) contenant
  `{ form, searchKey, term, actionUrl, resetParams }`. Annuler l'événement
  empêche l'envoi natif du formulaire (intégrations AJAX).

### Intégration AJAX

- Ajouter des attributs `data-ajax-action` et `data-ajax-target` sur le
  formulaire pour brancher un traitement dynamique.
- Intercepter `tablesearch:submit` et `tablesearch:reset` pour envoyer la
  requête avec `fetch()` puis rafraîchir le tableau sans rechargement.
- Mettre à jour l'URL via `history.replaceState` afin de conserver les
  paramètres de recherche et de pagination dans l'historique.

## Exemple minimal

```php
ca_register_search_context('tentatives', [
    'fields' => [
        'sql' => ['t.reponse', 'u.display_name'],
    ],
    'ui' => [
        'label'       => __('Rechercher une tentative', 'chassesautresor-com'),
        'placeholder' => __('Texte, joueur, ...', 'chassesautresor-com'),
        'submit_icon' => 'search',
        'submit_icon_only' => true,
    ],
    'pagination_params' => ['tentatives-page'],
]);

echo cta_render_search_form('tentatives');
```
