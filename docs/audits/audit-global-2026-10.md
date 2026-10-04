# Audit global du site — octobre 2026

## Résumé exécutif

Le projet dispose déjà d'une base métier sérieuse : le domaine commence à être isolé dans l'extension
`chassesautresor-core`, la suite PHP couvre 1 316 scénarios et les flux sensibles (droits, nonces, images protégées,
points, indices, progression) ont de nombreux tests dédiés. Le site étant encore en développement, le meilleur
retour sur investissement consiste à fiabiliser maintenant la chaîne de livraison plutôt qu'à ajouter de nouvelles
fonctionnalités.

Les priorités recommandées sont, dans cet ordre :

1. **P0 — garder la suite JavaScript au vert et la rendre bloquante** : l'audit a révélé un défaut calendaire et une assertion obsolète, désormais corrigés ;
2. **P0 — retirer les secrets et données d'environnement de Git** : `wp-config.php` et des médias sont suivis ;
3. **P0 — corriger l'alerte de sécurité Composer** concernant la version de PHPUnit verrouillée ;
4. **P1 — créer une intégration continue reproductible** couvrant PHP, JavaScript, build et analyse statique ;
5. **P1 — réduire la dette de structure et le poids du dépôt** avant que l'équipe et le produit grandissent ;
6. **P1 — établir des budgets mesurables de performance et d'accessibilité** sur les parcours clés.

Cet audit est un audit statique du dépôt et de ses tests. Il ne remplace ni un test d'intrusion, ni une recette sur
un environnement connecté à une base représentative, ni une mesure Lighthouse/WebPageTest depuis le site déployé.

## Périmètre et contrôles réalisés

- inventaire Git, WordPress, thème enfant, extension métier, dépendances et tests ;
- lecture ciblée des points d'entrée, surfaces AJAX, accès aux données et configuration ;
- exécution des 1 316 tests PHPUnit et des 63 tests Jest ;
- audits de dépendances Composer et npm ;
- recherche des fichiers volumineux, secrets suivis, artefacts générés et outils de qualité/CI ;
- revue statique des sujets sécurité, maintenabilité, performance, accessibilité et internationalisation.

Les constats chiffrés correspondent à l'état du dépôt au 4 octobre 2026.

## Ce qui est déjà solide

### Couverture métier

- La suite PHPUnit passe : **1 316 tests et 3 182 assertions**.
- Les tests ciblent notamment les autorisations, les appels AJAX, les nonces, les suppressions, la progression, les
  points, les statistiques, les images protégées, les indices et les solutions.
- L'extension métier documente explicitement sa responsabilité et la séparation progressive entre logique métier et
  présentation. Cette direction architecturale est saine.

### Hygiène d'entrée et de sortie

- Une part importante des handlers examinés délègue validation, politique d'accès et persistence à des services
  spécialisés plutôt que de tout traiter dans les templates.
- Les tests de sécurité dédiés rendent les régressions sur les nonces et autorisations plus visibles.

### Internationalisation et responsive

- Le thème charge le domaine `chassesautresor-com` et de nombreuses chaînes passent déjà par les fonctions WordPress.
- Les sources SCSS sont organisées par domaines fonctionnels et permettent de conserver une démarche mobile-first.

## Priorités détaillées

### P0. Réparer et bloquer les régressions JavaScript

**Constat initial.** La commande Jest terminait avec 2 échecs sur 63 tests :

- le calcul de jour restant en fuseau `Australia/Adelaide` renvoie `0 jour` au lieu de `1 jour` ;
- un test de la molette attendait encore `H1,A0`, alors que le cadran à repère extérieur produit désormais `H99,A0`.

Le premier échec révélait une règle calendaire visible par l'utilisateur. Le second était une assertion devenue
obsolète après l'alignement du cadran sur son repère extérieur ; les tests de positionnement confirmaient déjà le nouveau
contrat. Les deux échecs ont été traités pendant l'audit : le calcul compare désormais des jours calendaires, sans être
faussé par un changement d'heure, et le test du widget vérifie la valeur réellement affichée par la molette.

**Actions.**

1. Conserver les tests de non-régression du calendrier et du cadran, avec un contrat unique entre affichage et soumission.
2. Compléter la couverture des changements de jour local, des décalages de 30/45 minutes et du passage 99 → 0 dans
   les deux sens de rotation.
3. Supprimer ou conditionner les `console.log` de diagnostic présents dans les parcours d'édition.
4. Rendre Jest bloquant dans la CI proposée ci-dessous.

**Critère de sortie.** `npm test -- --runInBand` passe sans échec et sans bruit de diagnostic inattendu.

### P0. Sortir la configuration locale et les secrets du dépôt

**Constat.** `wp-config.php` est suivi par Git malgré sa présence dans `.gitignore`. Il contient les identifiants de
base locale, les clés/salts WordPress et un identifiant de cookie. Le fait que les valeurs soient locales ne permet
pas de garantir qu'elles ne seront jamais réutilisées ou déployées par erreur. Par ailleurs, 154 fichiers sous
`wp-content/uploads` sont déjà suivis : ajouter le dossier à `.gitignore` n'efface pas son historique.

**Actions.**

1. Conserver un `wp-config.example.php` sans secret et alimenter le vrai fichier par variables d'environnement.
2. Retirer `wp-config.php` et les uploads du suivi Git avec `git rm --cached`, puis renouveler tous les secrets qui ont
   pu être utilisés hors du poste local.
3. Rechercher les secrets dans tout l'historique avant la première mise en production ; réécrire l'historique si le
   dépôt a été partagé hors de l'équipe de confiance.
4. Interdire au déploiement `WP_ENVIRONMENT_TYPE=development`, l'affichage des erreurs et `FS_METHOD=direct`.
5. Ajouter un scanner de secrets en pre-commit et dans la CI.

**Critère de sortie.** Une installation neuve fonctionne à partir de l'exemple documenté, aucun secret ni upload
utilisateur n'est suivi et une vérification automatique empêche leur retour.

### P0. Mettre à jour la dépendance PHPUnit vulnérable

**Constat.** `composer audit` signale une vulnérabilité de sévérité haute (CVE-2026-24765) sur PHPUnit 9.6.23 ; la
branche 9.6 est corrigée à partir de 9.6.33. PHPUnit est une dépendance de développement, ce qui réduit l'exposition
du site en production, mais une dépendance CI vulnérable reste un risque de chaîne d'approvisionnement, notamment si
des tests non fiables sont exécutés.

**Actions.**

1. Mettre à jour `phpunit/phpunit` vers une version corrigée compatible et régénérer `composer.lock`.
2. Rejouer toute la suite PHP et rendre `composer audit` bloquant.
3. Ne jamais installer les dépendances de développement dans l'artefact de production (`--no-dev`).

**Critère de sortie.** `composer audit` ne retourne aucune alerte connue et les 1 316 tests restent verts.

### P1. Installer une vraie chaîne de qualité continue

**Constat.** Aucun workflow CI ni configuration PHPCS, PHPStan ou ESLint n'a été trouvé. Les tests sont nombreux mais
leur valeur diminue s'ils ne sont pas exécutés automatiquement. Le script Composer `test` pointe en outre vers la
suite d'un composant Hostinger, tandis que la consigne projet utilise `tests/phpunit.xml` : deux portes d'entrée
différentes favorisent les erreurs humaines.

**Actions.**

1. Faire de `composer test` l'unique commande PHP du projet et lui faire lancer `tests/phpunit.xml`.
2. Ajouter des scripts explicites : `test:php`, `test:js`, `lint:php`, `lint:js`, `audit` et `build:css`.
3. Ajouter une CI sur chaque pull request avec versions PHP et Node fixées, cache des dépendances et commandes
   verrouillées (`composer install`, `npm ci`).
4. Introduire WordPress Coding Standards/PHPCS puis PHPStan par paliers, avec une baseline temporaire datée.
5. Vérifier que le build CSS ne produit aucun diff non committé.

**Critère de sortie.** Une pull request ne peut être fusionnée si tests, audits, lint ou build échouent.

### P1. Alléger le dépôt et rendre les mises à jour WordPress reproductibles

**Constat.** Le dépôt suit 23 541 fichiers, dont environ 17 925 sous `wp-content/plugins` et 3 320 dans le cœur
WordPress (`wp-admin`/`wp-includes`). Le dossier `.git` pèse environ 379 Mo. Cette stratégie ralentit clones, revues,
CI et audits, tout en mélangeant code produit et code tiers. Elle rend aussi les correctifs de sécurité plus
difficiles à distinguer des changements métier.

**Actions.**

1. Décider et documenter une stratégie unique : idéalement gérer le cœur et les extensions publiques avec Composer
   (par exemple une structure Bedrock ou un équivalent), tout en conservant le thème et l'extension métier en Git.
2. Conserver les extensions premium dans un registre/stockage privé contrôlé, jamais comme copie opaque non mise à
   jour.
3. Générer un inventaire SBOM et automatiser la veille de vulnérabilités sur toutes les extensions, pas seulement
   les dépendances racine Composer/npm.
4. Planifier la réécriture de l'historique seulement après sauvegarde et coordination de l'équipe.

**Critère de sortie.** Une version précise du site peut être reconstruite depuis les manifests et l'artefact de
déploiement, sans versionner le cœur, les uploads ou les sauvegardes.

### P1. Réduire les points de concentration architecturaux

**Constat.** Le code personnalisé représente environ 80 000 lignes. Plusieurs fichiers dépassent 1 000 lignes :
`_edition.scss` (2 884), `chasse-edit.js` (1 574), `enigme-edit.js` (1 482), `_mon-compte.scss` (1 459),
`_enigme.scss` (1 429), `_components.scss` (1 400) et `inc/chasse-functions.php` (1 208). Le bootstrap de l'extension
charge manuellement plusieurs centaines de fichiers et atteint lui-même plus de 700 lignes.

**Risques.** Couplage implicite, ordre de chargement fragile, conflits CSS, coût élevé des revues et impossibilité de
charger seulement les fonctions utiles à une requête.

**Actions.**

1. Finaliser la frontière thème/extension déjà engagée : présentation dans le thème, règles et données dans le core.
2. Introduire un autoload PSR-4 pour les classes et des registrars par domaine au lieu d'une longue liste de
   `require_once`.
3. Découper les scripts d'édition en modules testables et initialisés à partir d'un point d'entrée par écran.
4. Scinder les feuilles SCSS par composants, documenter les tokens et supprimer les sélecteurs morts avant d'ajouter
   un framework ou une nouvelle couche d'abstraction.
5. Fixer une règle de revue (par exemple alerte à 400 lignes) plutôt qu'un découpage mécanique sans cohésion.

**Critère de sortie.** Les nouveaux domaines peuvent être testés et chargés indépendamment ; aucun nouveau fichier
monolithique n'est ajouté.

### P1. Mesurer performance et accessibilité sur les vrais parcours

**Constat.** Le dépôt possède des tests ciblés d'accessibilité, mais aucun budget de performance ni audit de page
automatisé n'a été identifié. Une revue statique ne permet pas de conclure sur les Core Web Vitals, le poids réel des
assets, les contrastes calculés ou le parcours complet au clavier.

**Actions.**

1. Définir cinq parcours de référence : accueil/liste des chasses, fiche chasse, énigme, déblocage d'indice et compte
   organisateur.
2. Mesurer sur mobile les LCP, INP, CLS, poids JS/CSS/images, nombre de requêtes et requêtes SQL.
3. Ajouter axe-core sur ces parcours et une recette clavier/lecteur d'écran, notamment sur les modales, réordonnements,
   messages dynamiques et la molette.
4. Charger les assets par écran/composant, différer le JavaScript non critique et vérifier les tailles d'images
   responsive avant d'ajouter du cache.
5. Définir des budgets versionnés et faire échouer la CI lors d'une régression significative.

**Critère de sortie.** Chaque parcours a une baseline, un budget, un propriétaire et un test reproductible.

### P2. Consolider les conventions de configuration et d'exploitation

**Constats.** En développement, `WP_DEBUG` et `WP_DEBUG_LOG` sont désactivés alors que `display_errors` est activé
directement. Cela masque la journalisation WordPress tout en risquant d'afficher des détails dans la réponse. La
configuration autorise également les écritures directes du système de fichiers.

**Actions.**

- activer le log WordPress en local sans affichage navigateur, et traiter les warnings dans les tests ;
- ajouter un environnement de préproduction proche de la production avec erreurs non affichées ;
- centraliser les tâches planifiées dans un cron système et superviser leurs échecs ;
- documenter sauvegarde **et restauration**, rotation des journaux, cache, email et procédure de déploiement/rollback ;
- ajouter des données de démonstration anonymisées pour les tests manuels et de performance.

## Feuille de route proposée

### Semaine 1 — rendre le socle fiable

- conserver les 2 corrections JavaScript et compléter leurs cas limites ;
- mettre PHPUnit à jour ;
- unifier les commandes de test ;
- créer une CI minimale et bloquante ;
- préparer la configuration sans secret et renouveler les clés.

### Semaines 2 à 4 — préparer une préproduction sûre

- retirer uploads/configuration du suivi et choisir la stratégie de dépendances WordPress ;
- ajouter PHPCS, PHPStan et le scan de secrets par paliers ;
- inventorier toutes les extensions et définir leur cadence de mise à jour ;
- créer les cinq scénarios end-to-end et les premières baselines accessibilité/performance ;
- documenter déploiement, rollback et restauration.

### Ensuite — rembourser la dette sans bloquer le produit

- réserver une capacité récurrente à la modularisation des gros fichiers ;
- poursuivre la migration métier du thème vers `chassesautresor-core` ;
- conditionner l'optimisation à des mesures réelles et suivre les budgets à chaque livraison.

## Décisions à éviter

- ne pas masquer les échecs Jest en modifiant uniquement les attentes ;
- ne pas considérer `.gitignore` comme une suppression de fichiers déjà suivis ou de leur historique ;
- ne pas ajouter un plugin de sécurité/cache comme substitut à la mise à jour, aux droits minimaux et à la mesure ;
- ne pas lancer une refonte globale des 80 000 lignes : extraire progressivement autour de tests existants ;
- ne pas promettre une conformité accessibilité ou de bonnes performances avant un audit du site exécuté.

## Indicateurs de suivi

| Indicateur | État observé | Cible avant préproduction |
| --- | ---: | ---: |
| Tests PHPUnit | 1 316/1 316 passent | 100 % |
| Tests Jest | 62 passent, 1 ignoré après correction | 100 % hors exceptions documentées |
| Alertes `composer audit` | 1 haute | 0 |
| Vulnérabilités npm de production | 0 | 0 |
| Fichiers upload suivis | 154 | 0 |
| Configuration locale suivie | oui | non |
| CI bloquante | absente | tests + lint + audits + build |
| Baselines parcours réels | absentes | 5 parcours |
