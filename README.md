WordPress Project

Ce dépôt contient une installation locale de WordPress. Pour exécuter du code PHP ou utiliser Composer dans un environnement reproductible, deux solutions sont possibles :

Initialisation de l’environnement (setup-env.sh)

Un script setup-env.sh est fourni pour configurer l’environnement local. Il ajoute temporairement les exécutables php et composer (présents dans le dossier bin/) au PATH :

source setup-env.sh

Cette étape est obligatoire avant d’utiliser Composer ou PHP dans ce projet. Elle ne fait qu’ajouter les chemins, sans installation système.

Utilisation de Composer pour les tests

Un fichier composer.json est présent à la racine pour installer PHPUnit et les dépendances de développement. Une fois l’environnement initialisé avec setup-env.sh, exécutez :

composer install
composer test

La commande composer test exécute PHPUnit à l’aide de la configuration du plugin hostinger.

Avant chaque commit, lancez la suite de tests :

```bash
vendor/bin/phpunit -c tests/phpunit.xml
```

Avant tout déploiement, exécutez `npm run build:css` pour générer les feuilles de style.

Gestion des traductions du thème
--------------------------------

Toutes les chaînes visibles par les utilisateurs doivent être encapsulées dans les fonctions de traduction WordPress (`__`, `_e`, `esc_html__`, etc.) en utilisant le domaine `chassesautresor-com`.
Les fichiers de langues du thème sont placés dans `wp-content/themes/chassesautresor/languages/`.
Pour générer ou mettre à jour le fichier POT, utilisez WP‑CLI :

```bash
wp i18n make-pot ./wp-content/themes/chassesautresor ./wp-content/themes/chassesautresor/languages/chassesautresor-com.pot
```

Ajoutez ensuite vos fichiers `.mo` compilés dans ce même dossier pour charger les traductions en front‑end.

## Tables personnalisées

Le projet utilise plusieurs tables SQL dédiées pour suivre l'activité des joueurs :

- `wp_engagements` enregistre les engagements liés aux énigmes, chasses ou indices (`indice_id`).
- `wp_indices_deblocages` trace le déblocage des indices et les points dépensés.
- `wp_user_points` inclut la valeur `indice` dans le champ `origin_type` pour comptabiliser ces dépenses.
- `wp_user_messages` stocke les messages associés aux utilisateurs ou au site.

### Table `wp_user_messages`

| Colonne    | Type                                     | Commentaire                                      |
|------------|------------------------------------------|--------------------------------------------------|
| id         | bigint unsigned AUTO_INCREMENT           | clé primaire                                     |
| user_id    | bigint unsigned                          | identifiant du joueur, `0` pour un message site |
| message    | longtext                                 | contenu JSON du message                          |
| status     | varchar(20)                              | type (`persistent`, `flash`, `site`…)           |
| created_at | datetime DEFAULT CURRENT_TIMESTAMP       | date d'enregistrement                            |
| expires_at | datetime NULL                            | expiration optionnelle                           |
| locale     | varchar(10) NULL                         | locale associée                                  |

Index :

- `PRIMARY(id)`
- `INDEX(user_id)`
- `INDEX(status)`
- `INDEX(expires_at)`

#### APIs

Les fonctions utilitaires s'appuient sur `UserMessageRepository` pour manipuler la table :

- `myaccount_add_persistent_message()` et `myaccount_remove_persistent_message()` pour les messages durables.
- `myaccount_add_flash_message()` et `myaccount_get_flash_messages()` pour les notifications temporaires.
- `add_site_message()` (avec expiration optionnelle) et `get_site_messages()` pour les messages globaux.
- Le repository expose également `insert`, `update`, `delete`, `get` et `purgeExpired()`.

#### Workflow de migration

1. Lancer `wp cat migrate-messages` via WP‑CLI.
2. Vérifier la suppression des métadonnées `_myaccount_messages`, `_myaccount_flash_messages` et du transient `cat_site_messages`.
3. Exécuter `vendor/bin/phpunit -c tests/phpunit.xml`.

#### Rollback

En cas d’échec, restaurer la base depuis un backup puis supprimer la table avec :

```sql
DROP TABLE wp_user_messages;
```

## Création d’indice

Un endpoint dédié permet de créer rapidement un indice :

- **URL :** `/creer-indice/`
- **Paramètres :** `chasse_id`, `enigme_id` (optionnel) et `nonce`.
- **Champs ACF initialisés :** `indice_cible_type`, `indice_enigme_linked`, `indice_chasse_linked`, `indice_disponibilite`, `indice_date_disponibilite`, `indice_cout_points` et `indice_cache_complet`.
- **Comportement :** après création, l’utilisateur est redirigé vers la chasse ou l’énigme associée.

Les liens déclenchant la modale de création peuvent utiliser la classe `.cta-indice-enigme`
avec l’attribut `data-objet-type="enigme"`. Un attribut optionnel `data-default-enigme`
permet de présélectionner une énigme. L’attribut `data-chasse-id` doit être fourni pour
charger via l’endpoint AJAX `chasse_lister_enigmes` la liste des énigmes admissibles,
ainsi que le rang du prochain indice pour chacune. La modale affiche alors un sélecteur
d’énigme pour choisir la cible de l’indice.

### Titres d’indice et langues

Le champ `post_title` des indices conserve uniquement un libellé neutre. Le rang est
stocké dans la métadonnée `indice_rank` et l’intitulé final est généré dynamiquement
(`Indice #n`) en fonction de la langue active lors de l’affichage.

### Accessibilité

Les libellés du formulaire utilisent `color: var(--color-editor-text)` afin de rester lisibles sur fond clair. Évitez d'appliquer `--color-text-primary` dans ce contexte pour garantir un contraste suffisant.
