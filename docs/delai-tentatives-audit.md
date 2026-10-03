# Audit et feuille de route — délai entre deux tentatives

## Périmètre et décisions

Ce chantier remplace le quota quotidien par un délai commun aux étapes intermédiaires et à la réponse finale.
Il ne modifie ni le registre ni la liste des widgets. La clé fonctionnelle est toujours `(user_id, enigme_id)` : une
erreur commise sur une étape bloque donc aussi la réponse finale, et réciproquement.

Les choix suivants constituent le contrat d'implémentation :

- seul un résultat effectivement persisté avec la valeur `faux` crée ou renouvelle un délai ;
- `bon`, `variante`, `attente` et les interactions de clic ne changent jamais ce délai ;
- une soumission refusée pendant le délai ne crée aucune ligne de tentative et ne débite aucun point ;
- une durée absente, invalide ou égale à zéro désactive le délai ;
- la durée est exprimée en secondes dans le domaine, même si l'éditeur peut proposer une saisie plus lisible ;
- `retry_at` est un instant UTC inclusif : une soumission est autorisée lorsque `now >= retry_at` ;
- le contrat HTTP expose `blocked` (booléen), `retry_at` (RFC 3339 UTC, par exemple
  `2026-10-03T14:05:00Z`, ou `null`), `remaining_seconds` (entier positif ou zéro) et `message` localisé ;
- l'instant de référence est produit par le serveur. Le navigateur recalcule seulement l'affichage à partir de la
  réponse serveur et doit refaire valider chaque soumission.

## Audit du stockage existant

Toutes les réponses finales et interactions d'étape sont conservées dans `wp_enigme_tentatives`. La colonne nullable
`etape_id` distingue une étape d'une réponse finale. `resultat` contient notamment `bon`, `faux`, `variante` et
`attente`, tandis que `date_tentative` est un `datetime` alimenté par défaut par la base. L'historique est donc déjà
commun à l'énigme et suffit aux statistiques, mais il ne porte ni date d'évaluation d'une tentative manuelle ni
instant UTC non ambigu.

Le décompte actuel filtre les lignes `faux` entre minuit et 23 h 59 min 59 s en forçant le fuseau
`Europe/Paris`. Ses appels bloquants et d'affichage sont dispersés dans :

- `RiddleStepTextAjaxHandler`, avant la prise du verrou MySQL et dans son compteur de réponse ;
- `RiddleAnswerSubmissionAjaxHandler::validate()`, avant son verrou de cache, puis dans sa réponse ;
- `RiddleAnswerSubmissionPolicy`, qui renvoie le code historique `tentatives_epuisees` ;
- les vues joueur des étapes, le bloc de réponse finale et les informations de participation ;
- `riddle-step-player.js` et `reponse-automatique.js`, dont ce dernier calcule localement le prochain minuit ;
- la notification d'une réponse manuelle refusée et plusieurs résumés de chasse ;
- l'éditeur, sa politique de champs et la valeur initiale des nouvelles énigmes.

Les méthodes de statistiques générales qui comptent des tentatives doivent rester en place. Seules les méthodes
quotidiennes utilisées comme autorisation et leur présentation doivent disparaître.

La documentation de schéma ne mentionne actuellement aucun index secondaire pour `wp_enigme_tentatives`. La base
locale n'est pas interrogeable depuis l'environnement courant, faute de client MySQL ; les index réels devront donc
être confirmés sur une base de recette avant toute migration. Une requête répétée du type « dernière ligne `faux`
par joueur et énigme » demanderait au minimum un index `(user_id, enigme_id, resultat, date_tentative)`.

## Deux parcours actuellement distincts

### Étapes intermédiaires

Le handler texte applique les contrôles d'accès communs, consulte le quota, acquiert ensuite un verrou MySQL nommé
pour `(user_id, enigme_id)`, évalue le widget, puis appelle `RiddleStepSubmissionService`. Ce service réévalue l'étape
courante et regroupe l'insertion de la tentative et l'avancement dans une transaction. Les réussites, erreurs et
variantes sont historisées ; elles coûtent toutes zéro point. Le handler de clic utilise le même verrou et le même
service, sans consulter le quota.

La faiblesse principale est que la vérification bloquante se produit avant le verrou. Deux erreurs concurrentes peuvent
donc toutes deux franchir cette vérification. Le futur service doit être consulté une première fois pour répondre vite,
puis obligatoirement une seconde fois dans la zone verrouillée et avant toute insertion.

### Réponse finale

Les modes automatique et manuel passent par `RiddleAnswerSubmissionAjaxHandler`, mais leur orchestration reste
différente de celle des étapes. Le verrou est un verrou de cache de quinze secondes, dont la portée et l'atomicité
dépendent du backend de cache. Il n'est ni le verrou MySQL utilisé par les étapes ni une transaction englobant le
débit de points, la tentative et la progression. La validation du quota a lieu avant ce verrou.

Une réponse automatique est immédiatement enregistrée avec `bon`, `faux` ou `variante`. Une réponse manuelle est
d'abord enregistrée avec `attente`, puis devient `bon` ou `faux` lors de la revue. Tant qu'elle est en attente, le statut
`soumis` bloque déjà une nouvelle réponse. Pour une réponse manuelle refusée, le délai doit commencer au moment de la
décision de refus, et non à la date de soumission potentiellement ancienne.

L'unification du verrou `(user_id, enigme_id)`, de la transaction et de la politique est donc un prérequis de sûreté,
pas une simple refactorisation de confort.

## Source unique de configuration

Le seul réglage existant proche du besoin est `enigme_tentative_max`. Il exprime un nombre de tentatives par jour et
ne peut pas être converti sans hypothèse arbitraire en durée. `enigme_tentative_cout_points` est indépendant et reste
applicable aux seules réponses finales selon les règles existantes.

La source recommandée est un nouveau champ ACF canonique sur l'énigme,
`enigme_tentative_delai_secondes`. Un service de configuration est seul autorisé à lire et normaliser ce champ. Les
handlers, vues et scripts ne lisent jamais directement ACF. Les étapes héritent ainsi automatiquement du réglage de
leur énigme, sans champ propre et sans divergence.

Pour les énigmes existantes, le champ absent vaut zéro. Aucune conversion automatique de
`enigme_tentative_max` n'est effectuée. Ce choix est prévisible, non destructif et évite d'inventer une correspondance
métier. Une migration additive peut initialiser explicitement le nouveau champ si une durée par défaut est validée
avant déploiement ; sinon les organisateurs l'activent énigme par énigme. Le vieux champ reste lisible pendant un lot
de compatibilité, puis disparaît des interfaces et des politiques sans supprimer sa métadonnée.

## Stocker ou calculer `retry_at`

La recommandation est de **stocker l'état actif** dans une table additive dédiée, et de conserver
`wp_enigme_tentatives` comme journal immuable :

| Colonne | Rôle |
|---|---|
| `user_id` | Joueur, première partie de la clé primaire. |
| `enigme_id` | Énigme, seconde partie de la clé primaire. |
| `retry_at_utc` | Instant UTC jusqu'auquel la soumission est bloquée. |
| `source_tentative_uid` | Lien facultatif vers l'erreur qui a renouvelé le délai. |
| `updated_at_utc` | Date technique d'audit. |

La clé primaire `(user_id, enigme_id)` rend la lecture et l'upsert constants et matérialise exactement la portée
métier. Un index sur `retry_at_utc` n'est utile que si une purge globale des lignes expirées est ajoutée ; il n'est pas
nécessaire pour l'autorisation synchrone.

Calculer `retry_at` à partir de la dernière ligne `faux` paraît plus simple, mais présente quatre ambiguïtés : les
anciens `datetime` n'indiquent pas leur fuseau, une revue manuelle peut survenir longtemps après la soumission, une
modification de durée changerait rétroactivement un délai déjà commencé, et la requête solliciterait tout l'historique.
L'état matérialisé fige au contraire la politique appliquée lors de l'erreur. L'upsert doit se produire dans la même
transaction que l'insertion automatique `faux`, ou que la transition manuelle `attente` vers `faux`.

Les dates de cette table sont écrites et comparées en UTC par PHP avec une horloge injectable dans les tests. Le SQL
ne doit pas dépendre de `NOW()` ni du fuseau de session MySQL. La conversion vers le fuseau WordPress est réservée au
texte destiné au joueur ; le transport reste en RFC 3339 UTC.

## Cache, remise à zéro et historique

La décision d'accès ne doit pas être cachée : la ligne SQL est lue sous le verrou avant l'écriture. Un cache peut
accélérer le rendu initial, mais seulement avec une expiration au plus tard à `retry_at` et une invalidation après
chaque erreur, revue manuelle et remise à zéro. Le premier lot doit privilégier l'absence de cache.

La remise à zéro globale supprime la nouvelle table comme les autres données de progression. La remise à zéro
ciblée d'une énigme supprime ses lignes de délai lorsqu'elle efface les tentatives. Une action dédiée peut aussi lever
un délai sans supprimer l'historique, mais ne doit pas être confondue avec une migration. Aucun déploiement ne supprime
ni ne réécrit les tentatives historiques.

## Service et contrat communs proposés

Un `RiddleRetryPolicyService` reçoit le repository d'état, la configuration et une horloge. Il fournit :

1. `getState(userId, riddleId)` pour le rendu et la réponse AJAX ;
2. `assertAllowed(userId, riddleId)` dans la zone protégée ;
3. `renewAfterFailure(...)`, appelé seulement après la persistance d'un résultat `faux` ;
4. `clearForRiddle(riddleId)` pour les outils administratifs ;
5. une représentation unique du contrat `blocked`, `retry_at`, `remaining_seconds`, `message`.

Les soumissions d'étape et finales doivent utiliser le même verrou MySQL, avec le même nom de clé. Après
acquisition, l'ordre est : revalider l'accès et la progression, relire le délai, commencer la transaction, effectuer
les écritures métier, renouveler le délai seulement pour `faux`, valider la transaction, puis produire le contrat.
Un rejet de délai intervient avant le débit de points et avant la création de tentative.

Le code d'erreur transport peut rester stable pendant la transition, mais toutes les erreurs de délai doivent fournir
le même objet structuré. Le message localisé n'est jamais utilisé par JavaScript comme code de décision.

## Interface joueur

Un module JavaScript commun prend un instant serveur et utilise `Date.now()` uniquement pour l'affichage. À chaque
tick, au retour d'un onglet suspendu (`visibilitychange`) et au retour d'une requête, il recalcule la différence absolue
avec `retry_at` au lieu de décrémenter un compteur local. Il désactive tous les contrôles de saisie et de soumission du
formulaire concerné, maintient `aria-busy` uniquement pendant le réseau, annonce les changements dans une zone
`aria-live`, puis réactive automatiquement les contrôles à zéro.

Une horloge locale fausse peut dégrader l'affichage, jamais l'autorisation. Pour limiter cet effet, le contrat pourra
aussi fournir `server_now` en RFC 3339 et le module conserver un décalage serveur/client. Toute réponse réseau tardive
remplace l'état local seulement avec les instants renvoyés par le serveur.

## Feuille de route en lots courts

### État d'avancement

- **Lot 1 terminé** : le service de configuration, la table additive, le repository, l'horloge injectable et la
  politique partagée sont posés, couverts par des tests unitaires et accessibles depuis la fabrique centrale.
- **Remise à zéro livrée en avance sur le lot 5** : les nettoyages ciblé et global suppriment également l'état actif du
  délai, sans ajouter de suppression destructive à la migration.
- **Lot 2 en cours** : les soumissions d'étape et de réponse finale utilisent désormais le même verrou MySQL nommé,
  avec une compatibilité temporaire pour l'ancien nom de classe réservé aux étapes. Les étapes et réponses finales
  relisent maintenant le délai sous ce verrou. Une erreur automatique renouvelle l'état dans la transaction qui crée
  la tentative ; un rejet actif intervient avant insertion et avant débit de points. La revue manuelle est également
  sérialisée et un refus renouvelle le délai au moment de la décision dans la transaction `attente` vers `faux`.
- **Lots 3 à 5 non commencés** : le quota quotidien reste la politique active tant que les handlers, les transactions,
  le contrat AJAX et l'interface n'ont pas été migrés ensemble. La présence de la nouvelle table ne change donc pas
  encore le comportement joueur.

Cet ordre évite un déploiement intermédiaire où une étape et la réponse finale appliqueraient deux politiques
différentes. Chaque lot doit conserver les suites complètes vertes et peut être relu indépendamment avant le suivant.

### Lot 1 — domaine, stockage et migration additive

- Ajouter le champ ACF canonique et son accès via un service de configuration.
- Créer la table d'état, son repository, sa migration versionnée et l'horloge injectable.
- Implémenter la politique et son contrat, y compris durée nulle, borne inclusive et UTC.
- Couvrir joueurs/énigmes distincts, renouvellement, expiration et absence de configuration.

### Lot 2 — orchestration transactionnelle commune

- Renommer et partager le verrou MySQL actuel entre étapes et réponses finales.
- Déplacer la seconde vérification de droit dans la zone verrouillée.
- Intégrer l'upsert du délai à la transaction des erreurs automatiques et à la revue manuelle refusée.
- Garantir qu'un blocage n'insère rien et ne débite rien ; tester concurrence et rollback.

### Lot 3 — contrats AJAX et rendu serveur

- Remplacer le quota dans les deux handlers, leurs politiques et leurs modèles de vue.
- Retourner le contrat commun sur succès, erreur et rendu initial.
- Adapter notification manuelle, informations de participation et résumés sans retirer les statistiques historiques.
- Tester les enveloppes AJAX et la portée croisée étape/réponse finale.

### Lot 4 — compte à rebours accessible commun

- Extraire un contrôleur JavaScript utilisé par les formulaires d'étape et final.
- Gérer réactivation, focus, annonces, onglet suspendu et réponse retardée.
- Retirer le calcul du prochain minuit et les compteurs quotidiens du DOM.
- Ajouter les tests Jest et la recette clavier/lecteurs d'écran.

### Lot 5 — administration et retrait de compatibilité

- Ajouter le nettoyage du délai aux remises à zéro globale et ciblée.
- Retirer `enigme_tentative_max` de l'éditeur et des valeurs initiales, sans effacer les métadonnées existantes.
- Supprimer les branches `tentatives_epuisees` après migration de tous les clients.
- Exécuter les suites complètes PHP/JavaScript et la recette de non-régression des deux parcours.

## Critères de passage à l'implémentation

Le modèle de données, la configuration et la sémantique de `retry_at` sont définis ci-dessus. Avant le premier lot,
il reste uniquement à confirmer sur la base de recette le moteur transactionnel et les index réels de
`wp_enigme_tentatives`, puis à valider la durée initiale à appliquer, le cas échéant, aux énigmes existantes. Sans
valeur métier explicite, la stratégie sûre demeure `0` (délai désactivé) et aucune reprise historique.
