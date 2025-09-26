# Mode démo

## Cache du statut de chasse démo

La fonction `ca_demo_is_demo_hunt()` conserve un cache mémoire pour éviter de recalculer le statut "démo" d'une chasse à chaque appel. La clé du cache inclut désormais l'identifiant de l'utilisateur courant (via `get_current_user_id()`) afin que chaque personne obtienne son propre statut.

Un filtre `ca_demo_cache_context` permet d'ajuster les fragments utilisés pour générer la clé. Le filtre reçoit le contexte courant (par défaut `['user_id' => get_current_user_id()]`) et l'identifiant de la chasse. Vous pouvez y ajouter ou retirer des éléments en fonction de vos besoins d'intégration, par exemple pour mutualiser le cache entre plusieurs comptes techniques ou segmenter le résultat selon d'autres attributs.

```php
add_filter('ca_demo_cache_context', function (array $context, int $chasse_id): array {
    // Ex.: partage le cache pour les utilisateurs d'un même tenant.
    $context['tenant'] = get_current_tenant_key();

    return $context;
});
```

Si le filtre retourne une valeur vide ou non valide, `ca_demo_is_demo_hunt()` retombe automatiquement sur le comportement par défaut basé sur l'identifiant utilisateur.
