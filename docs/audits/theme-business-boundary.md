# Audit de la frontière entre le thème et le plugin Core

Date de l'audit : 1er octobre 2026

Périmètre : `wp-content/themes/chassesautresor` et
`wp-content/plugins/chassesautresor-core`

## Conclusion

La migration n'est **pas terminée**. Le plugin contient désormais l'essentiel des services, repositories et
contrôleurs AJAX, mais le thème conserve encore des orchestrations et des mutations métier. Changer de thème
laisserait le plugin actif, mais ferait notamment disparaître les workflows de validation d'une chasse, de demande
organisateur, d'administration des paiements et une partie des traitements d'engagement et de progression.

Le thème compte 146 fichiers PHP hors tests (30 367 lignes). Vingt-huit fichiers référencent directement les
classes du plugin, pour 203 occurrences. Cette dépendance est acceptable dans une couche de présentation, mais elle
signale ici une couche d'intégration encore volumineuse. Les façades qui ne font que déléguer au plugin ne sont pas
considérées comme de la logique métier résiduelle ; elles restent toutefois une dette de couplage.

## Avancement au 1er octobre 2026

**Estimation : 80 % de la migration métier est terminée.** Cette valeur est une estimation architecturale, pas un
ratio de lignes : elle pondère la couverture des domaines Core, l'indépendance des points d'entrée WordPress, la
propriété de la persistance, l'absence d'effets de bord dans les vues et la couverture de tests.

Le lot de migration associé à cet audit a sorti du thème :

- l'installation et la mise à niveau de la table des messages, ainsi que le nettoyage ponctuel des anciens messages ;
- le hook WooCommerce qui attribue les points achetés et vide le panier ;
- le hook qui assigne automatiquement l'organisateur auteur d'une chasse.

### Prochain lot recommandé (taille maximale raisonnable)

Migrer en un seul lot le **back-office transactionnel** encore présent dans `inc/admin-functions.php` : reset des
statistiques, gestion des taux de conversion et demandes de paiement, puis workflow complet de validation,
correction et bannissement des chasses. Ce lot est la plus grande tranche cohérente qui puisse être revue et testée
rapidement : il centralise les mutations administrateur dans Core sans mélanger la demande organisateur ou les
politiques d'accès. Cible après ce lot : **88 %**.

## Critères utilisés

Le plugin Core déclare que les permissions métier, les chasses, les énigmes, les indices, les solutions, les
engagements, les points, les statistiques, les tables, les migrations et les tâches planifiées sont de sa
responsabilité. Le thème ne devrait conserver que :

- le rendu HTML et les view models strictement destinés au rendu ;
- les styles, scripts et ressources visuelles ;
- les adaptations de présentation propres à Astra et WooCommerce ;
- de minces adaptateurs appelant une API publique du plugin, sans règle, persistance ou orchestration.

Ont donc été recherchés dans le thème : écritures WordPress/ACF, SQL, installation de tables, contrôleurs de
requête, permissions, transitions d'état, planification, calculs de points/statistiques et workflows de courriel.

## Résultats

### P0 — persistance et cycle de vie encore pilotés par le thème

1. **Réinitialisation des statistiques.** `inc/admin-functions.php` supprime directement des lignes de
   `usermeta`/`postmeta` et plusieurs métadonnées de progression. Le plugin possède
   `AdminStatisticsResetService`, mais le thème conserve l'ancien workflow et des mutations SQL.
2. **Transitions de validation des chasses.** `inc/admin-functions.php` publie, dépublie et modifie les caches des
   chasses et énigmes. `templates/page-traitement-validation-chasse.php` modifie encore
   `chasse_cache_statut`. Ces transitions doivent être atomiques dans le plugin ; un template ne doit jamais
   persister un état.
3. **Progression et gagnants.** `inc/chasse-functions.php` écrit encore les souscriptions, gagnants, dates de
   découverte et statuts d'énigmes. Cela duplique les responsabilités des services `Progress` du plugin.

### P1 — workflows métier et contrôleurs encore dans le thème

1. **Administration commerciale.** `inc/admin-functions.php` traite la gestion des points, le taux de conversion,
   les demandes de paiement et la validation des chasses via des hooks `init` et `admin_post_*`. Les widgets de
   rendu peuvent rester dans le thème, mais validation, autorisation, calcul et mutation doivent migrer.
2. **Demande organisateur.** `inc/organisateur-functions.php` gère le token, sa date, les relances par courriel, la
   confirmation et la création de l'organisateur. Le endpoint et l'orchestration doivent appartenir au plugin ; le
   thème ne devrait rendre que le formulaire et le résultat.
3. **Engagement et maintenance de progression.** `templates/page-traitement-engagement.php` instancie directement
   `HuntEngagementApplicationService`. `templates/page-traitement-tentative.php` efface les tentatives et statuts.
   Ce sont des contrôleurs applicatifs placés dans des templates.
4. **Permissions et routage de contenus.** `inc/access-functions.php` contient encore les politiques d'ajout,
   modification, suppression et visibilité, ainsi que des routes de fichiers/solutions. Plusieurs fonctions
   délèguent déjà au plugin, mais l'enregistrement des routes et toute décision métier doivent être déplacés.
5. **Réponses, tentatives et notifications.** `inc/enigme/reponses.php` orchestre la soumission et les courriels ;
   `inc/enigme/tentatives.php` conserve le traitement d'une tentative. Le plugin fournit déjà les services et
   handlers correspondants : le thème devrait uniquement afficher leurs résultats.
6. **Fin de chasse.** `inc/gamify-functions.php` branche encore la fin de chasse au hook `enigme_resolue`. Ce hook
   garantit un invariant métier et doit rester actif indépendamment du thème.

### P2 — façades de compatibilité et logique de requête à clarifier

- `inc/chasse-functions.php`, `inc/gamify-functions.php`, `inc/relations-functions.php`, `inc/statut-functions.php`,
  `inc/chasse/stats.php` et les fichiers `inc/enigme/*` exposent de nombreuses fonctions globales qui construisent
  ou appellent les services Core. Les simples délégations peuvent rester temporairement, mais doivent être
  documentées `@deprecated` et supprimées après migration de leurs appelants.
- Les gros préparateurs de données de `inc/chasse-functions.php` et `inc/enigme/affichage.php` mélangent rendu,
  cache, accès et classification. Les tableaux destinés aux templates peuvent rester ; les requêtes, permissions
  et invalidations doivent rejoindre le plugin.
- `inc/search/helpers.php` assemble du SQL pour la recherche. Si le registre de recherche reste une fonctionnalité
  propre au thème, ce code est présentationnel. S'il alimente une capacité produit utilisée ailleurs, le plugin
  doit posséder la construction de requête. Une décision d'architecture explicite est nécessaire.
- `single-chasse.php` persiste l'affichage de la modale de bienvenue. Il s'agit au minimum d'un effet de bord dans
  une vue ; il faut le remplacer par un handler, dans le plugin si cette information a une valeur fonctionnelle.

## Points déjà correctement migrés

- Le thème ne charge plus directement de fichiers d'implémentation depuis `chassesautresor-core/src`.
- Aucun endpoint `wp_ajax_*` n'est enregistré par le thème ; les handlers AJAX sont enregistrés par le plugin.
- Aucun repository Core n'est construit directement dans le thème ; les accès concernés passent par
  `CoreServiceFactory`.
- Le test `ThemeCoreBoundaryTest` protège ces trois acquis et vérifie la suppression des anciens loaders.

Ces garanties sont utiles mais insuffisantes : elles contrôlent la forme du couplage, pas la présence de logique
métier WordPress dans le thème.

## Plan de migration recommandé

1. **Fermer P0 :** déplacer le cycle de vie de la table des messages, tout le reset de statistiques et les
   transitions de validation/progression. Ajouter des tests d'intégration prouvant que ces fonctions marchent avec
   un autre thème actif.
2. **Déplacer les points d'entrée P1 :** enregistrer dans le plugin les hooks WooCommerce, `admin_post_*`,
   `template_redirect`, confirmation organisateur et cohérence des relations. Les templates ne transmettent alors
   que des commandes au plugin ou affichent des view models.
3. **Séparer lecture et rendu :** introduire dans le plugin les query services/view-data providers manquants, puis
   réduire les fonctions globales du thème à du balisage et du formatage sans effet de bord.
4. **Retirer les façades :** marquer les fonctions de compatibilité, migrer leurs appelants par domaine, puis
   supprimer les fichiers devenus vides.
5. **Renforcer la CI :** étendre la frontière avec une liste d'exceptions décroissante interdisant dans le thème
   l'installation de tables, SQL de mutation, hooks métier, mutations de posts/metas et planification.

## Définition de fin de migration

La migration pourra être considérée terminée lorsque :

- activer un thème WordPress standard ne désactive aucun workflow, hook, endpoint, migration ou tâche planifiée ;
- le thème ne crée ni table ni repository et n'exécute aucune mutation SQL ;
- les templates et fichiers `single-*` n'écrivent aucune donnée ;
- les règles de permission, transitions d'état, points, engagements et statistiques sont testées dans Core ;
- les seules références au plugin depuis le thème concernent des API publiques de lecture ou de commande,
  appelées par de minces adaptateurs de présentation.

## Commandes de reproduction

```bash
# Mesure du volume PHP du thème, hors tests.
find wp-content/themes/chassesautresor -type f -name '*.php' ! -path '*/tests/*' -print0 \
  | xargs -0 wc -l

# Couplage direct vers Core.
rg -n 'ChassesAuTresor\\Core|CoreServiceFactory' wp-content/themes/chassesautresor --glob '*.php'

# Persistance et mutations candidates.
rg -n '\$wpdb|wp_(insert|update|delete)_post|update_(post|user)_meta|delete_(post|user)_meta|update_field' \
  wp-content/themes/chassesautresor --glob '*.php'

# Contrôleurs enregistrés par le thème.
rg -n "admin_post_|template_redirect|wp_ajax_" wp-content/themes/chassesautresor --glob '*.php'
```
