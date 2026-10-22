# Trophées et notifications

## Principes

- Un trophée possède une seule règle.
- Un destinataire ne peut obtenir un trophée qu’une seule fois.
- Les destinataires possibles sont le joueur, son équipe active, ou les deux.
- Une attribution d’équipe reste attachée à l’équipe, y compris après sa dissolution.
- Les membres notifiés sont ceux qui sont actifs au moment de l’attribution.
- L’attribution est la source de vérité ; l’affichage de la notification est indépendant.

## Administration

Le CPT privé `trophee` est enregistré par le plugin Core et réservé aux administrateurs.
La fiche utilise :

- le titre WordPress ;
- les champs ACF locaux pour l’image, le texte court, l’état, le déclencheur, la portée et la notification.

Le seul déclencheur livré initialement est `code_event`.
La participation à une chasse envoie la clé `bienvenue` au joueur qui vient de s’engager.
Le trophée correspondant doit être publié, actif, et porter exactement cette clé.
Son champ « Clé de l’événement » doit correspondre à la clé envoyée par le code :

```php
cat_dispatch_trophy_event(
    'ma_cle_stable',
    $userId,
    [
        'type' => 'chasse',
        'id' => $huntId,
    ]
);
```

De futurs fournisseurs implémentent `TrophyTriggerProvider` puis s’enregistrent avec le filtre
`cat_trophy_trigger_providers`. Leurs champs ACF sont ajoutés automatiquement à la fiche trophée.

## Stockage

`wp_trophy_attributions` contient le registre immuable des obtentions. La contrainte
`(trophy_id, recipient_type, recipient_id)` assure l’unicité.

`wp_notifications` contient les événements de notification. `wp_notification_recipients` contient
l’état propre à chaque utilisateur : lecture et présentation de l’overlay.

Les tables sont installées à l’activation du plugin et mises à niveau sur `plugins_loaded`.
Le reset global efface les attributions et les notifications reçues. Les fiches trophées restent en place.

## Interfaces joueur

- `/trophees/` affiche les trophées personnels et ceux de l’équipe active.
- `/notifications/` affiche les notifications destinées au centre.
- La sidebar indique le nombre de notifications non lues.
- Les notifications `overlay` ou `both` sont présentées par priorité, une à la fois.

## Déploiement

Le plugin Core et le thème doivent être déployés dans le même lot. Le Core fournit les tables,
le CPT, les règles et les endpoints ; le thème fournit les pages, le compteur et l’overlay.
