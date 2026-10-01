# Audit de portabilité du thème `chassesautresor`

Date de l'audit : 1er octobre 2026.

## Verdict

Le plugin `chassesautresor-core` porte désormais une part importante des services métier, des contrôleurs AJAX,
des routes et des tâches planifiées. Le thème n'est toutefois **pas encore un thème de présentation
interchangeable**.

Changer de thème en laissant uniquement le plugin actif altérerait des fonctionnalités. Le changement ne doit donc
pas être effectué en production avant la migration des responsabilités résiduelles décrites ci-dessous et un test
de recette avec un thème neutre.

## Avancement de la migration

Le premier lot issu de cet audit a supprimé les anciens workflows métier inutilisés de gestion des organisateurs,
de comptabilisation des paiements, de réinitialisation des énigmes et de souscription aux chasses. L’attribution du
rôle temporaire `organisateur_creation`, encore active, appartient désormais au plugin et son hook est couvert par
un test de propriété. Une garde automatisée interdit désormais le retour d’écritures métier directes dans le thème.

Le deuxième lot a transféré au plugin les politiques WordPress globales : visibilité de la médiathèque, isolement
des médias protégés des énigmes, désactivation conditionnelle de Gutenberg, filtrage des capacités sensibles et
visibilité des statuts privés dans la requête principale.

Le troisième lot a transféré au plugin la restriction du back-office pour les organisateurs.

Le quatrième lot a transféré au plugin la version du cache de permissions des énigmes et ses huit hooks
d’invalidation liés aux contenus, rôles et métadonnées utilisateur.

Le cinquième lot a transféré au plugin le template et les comportements des courriels d’inscription, de mot de passe
oublié et de WooCommerce. Les fichiers historiques du thème ne sont plus que des chargeurs de compatibilité.

Le sixième lot a terminé le transfert du cycle de vie du cache de rendu des énigmes : sauvegarde d’une solution,
résolution d’une énigme et invalidation de la progression latérale.

Le septième lot a transféré au plugin les contrôleurs des écrans WordPress natifs de création et de modification,
avec maintien des politiques historiques lorsqu’elles sont disponibles et repli sécurisé sur les capacités WordPress.

Le huitième lot a transféré au plugin les routes et les contrôleurs HTTP servant les fichiers de solution et les
images protégées des énigmes. Les contrôleurs échouent de façon fermée si un adaptateur d’accès manque.

Le neuvième lot a transféré au plugin les anciennes routes de l’espace compte, leurs variables de requête et leurs
redirections vers le tableau de bord canonique.

Le dixième lot a transféré au plugin la protection globale du site par mot de passe. Le thème ne conserve qu’un
chargeur de compatibilité pour les appels historiques directs.

Le onzième lot a transféré au plugin le contrôleur d’accès aux pages d’énigmes, y compris l’engagement automatique,
les prérequis et les redirections vers les panneaux d’édition ou de soumission.

Le douzième lot a transféré au plugin l’endpoint de contact des organisateurs et sa variable de requête.

Le treizième lot a retiré la dernière écriture métier directe détectée dans le thème : l’annulation d’une validation
de chasse est désormais intégralement persistée par le contrôleur du plugin.

Le quatorzième lot a rendu la route de confirmation d’un organisateur autonome : validation du jeton, création du
profil, attribution du rôle et suppression des messages sont désormais orchestrées par le plugin, sans callbacks
fournis par le thème.

Le quinzième lot a retiré du thème les politiques d’autorisation et de résolution de relation utilisées par
l’annulation et le rafraîchissement de la validation d’une chasse. Le thème ne fournit plus que le rendu HTML du CTA.

Le seizième lot a supprimé les callbacks de lecture et de comptage des tentatives injectés par le thème. Le contrôleur
du plugin interroge désormais directement son service métier et ne délègue au thème que l’autorisation et le rendu.

Le dix-septième lot a transféré au plugin les dernières autorisations des contrôleurs de tentatives : consultation
d’une proposition et accès à la liste d’une énigme. Le thème ne fournit désormais que le rendu HTML de la liste.

Le dix-huitième lot a transféré les autorisations des statistiques d’énigme au plugin, pour le panneau de synthèse
comme pour la liste détaillée des participants.

Le dix-neuvième lot a transféré la construction des statistiques, l’exclusion des comptes internes, la lecture et le
comptage des participants au plugin. Le thème ne fournit plus que le rendu HTML du tableau.

Le vingtième lot applique la même séparation aux statistiques de chasse : autorisation, agrégats, participants et
relations avec les énigmes appartiennent désormais au plugin, tandis que le thème conserve le rendu du tableau.

Le vingt-et-unième lot a transféré au plugin les lectures paginées des historiques de points et de conversion. Le
thème ne fournit plus que le rendu HTML des lignes correspondantes.

Le vingt-deuxième lot a transféré au plugin la lecture, le filtrage d’accès et la pagination des chasses engagées d’un
utilisateur. Le thème ne fournit plus que le rendu des cartes et de leur pagination.

Le vingt-troisième lot a transféré au plugin la recherche, les agrégats et la pagination des tentatives de l’espace
compte. Le thème ne fournit plus que le rendu des lignes et du pager.

Le vingt-quatrième lot a transféré au plugin la politique d’accès à la conversion de points : rôle, profil, demandes
en cours, délai depuis le dernier règlement, solde minimal et coordonnées bancaires.

Le vingt-cinquième lot a transféré au plugin l’autorisation de navigation dans une chasse selon le rôle, l’association
à l’organisateur et l’engagement du joueur. Le thème ne fournit plus que la construction visuelle du menu.

Le vingt-sixième lot a transféré au plugin la résolution de la chasse et l’invalidation des caches utilisées par la
progression latérale des énigmes. Le thème ne fournit plus que les deux fragments HTML.

Le vingt-septième lot a supprimé l’injection du service de conversion par le thème dans le contrôleur d’administration.
Le plugin construit désormais lui-même ce service ; seuls le tableau et son cache de présentation restent délégués.

Le vingt-huitième lot a transféré au plugin la requête, la visibilité, la recherche et les facettes des filtres de
chasses de la page d’accueil. Le thème ne fournit plus que le rendu de la grille de résultats.

Le vingt-neuvième lot a rendu autonome l’invalidation du cache d’affichage des chasses lors de la remise à zéro des
statistiques. Le contrôleur d’administration ne reçoit désormais du thème que le moteur de rendu du tableau des
paiements ; le plugin connaît et invalide lui-même son cache objet et son transient.

Le trentième lot a déplacé dans le plugin la composition et le rendu des messages importants de l'espace personnel.
Le contrôleur AJAX des sections de compte ne reçoit plus ce traitement du thème, qui conserve uniquement le rendu
du fragment de section demandé.

Le trente-et-unième lot a rendu ces messages réellement autonomes. Le plugin lit maintenant lui-même les messages
persistants et éphémères, résout leur contexte chasse/énigme, interroge les conversions et la relation organisateur,
et recherche les chasses à modérer. Il ne dépend plus des helpers globaux du thème pour ces opérations. Les deux
lecteurs de messages ont été retirés du thème et la frontière interdit le retour de ces dépendances implicites.

Le trente-deuxième lot a transféré au plugin les politiques générales de création et de modification des contenus.
Le contrôleur des écrans WordPress natifs ne cherche plus les fonctions du thème et ne possède plus de repli permissif
sur les capacités génériques `edit_posts` et `edit_post`. Les fonctions globales de compatibilité sont désormais
définies par le plugin et construisent elles-mêmes les relations organisateur, chasse, énigme et indice.

Estimation prudente après ce lot : **environ 80 % de l'autonomie métier vérifiée**. Il ne s'agit plus d'un calcul au
centième fondé sur les lots déjà traités : cette valeur applique une décote aux dépendances runtime encore observées,
aux politiques d'édition encore enregistrées par le thème, aux routes de médias qui appellent des helpers du thème et
à l'absence de recette sous thème neutre. Le pourcentage ne remontera qu'après suppression vérifiée de ces catégories.

## Éléments bloquants observés

### Contrôleurs, politiques et routes encore attachés au thème

Le thème enregistre toujours notamment :

- des endpoints, variables de requête et sélections de templates dans `inc/user-functions.php` et
  `inc/organisateur-functions.php` ;
- les adaptateurs de politique appelés par le contrôle d’accès aux énigmes dans plusieurs fichiers `inc/` ;
- des politiques d'édition et des callbacks métier échangés avec le plugin dans les fichiers `inc/edition/*.php` ;
- les contrôleurs de médias protégés appellent encore des fonctions d'accès, de résolution de fichiers et de solutions
  définies par le thème ;

L'audit ciblé des messages importants et des écrans WordPress natifs ne relève plus de dépendance vers les helpers
globaux du thème. Les configurations `*Handler::configure()` inventoriées ne transmettent actuellement que des
fonctions de rendu. Les adaptateurs d'accès aux énigmes, solutions et médias restent en revanche à migrer complètement.

Une partie de ces éléments produit de l'interface, mais leur absence change aussi les droits, les parcours ou le
comportement du site.

### Expérience fonctionnelle fournie exclusivement par le thème

Les vues des chasses, énigmes et organisateurs, l'espace « Mon compte », les formulaires d'édition, les tableaux,
les modales, les scripts JavaScript et leurs styles résident encore dans le thème. Même si tous les traitements
métier étaient dans le plugin, un autre thème ne fournirait pas automatiquement ces écrans.

Le thème charge par ailleurs l'ensemble de ses modules `inc/` depuis `functions.php`. Ces fichiers ne constituent
donc pas du code historique inactif : leurs hooks sont enregistrés lorsque le thème est actif.

## Ce que garantit la couverture actuelle

`tests/ThemeCoreBoundaryTest.php` protège plusieurs migrations déjà réalisées : absence de contrôleurs AJAX dans le
thème, absence de chargement direct des implémentations du plugin, retrait de certains hooks métier et interdiction
d'écrire depuis les fichiers de templates.

Cette couverture est utile, mais elle ne prouve pas que le thème ne contient plus aucun métier : elle cible une
liste limitée de responsabilités déjà migrées et autorise encore les écritures présentes dans les fichiers `inc/`.

## Conditions minimales avant un changement de thème

1. Déplacer vers le plugin toutes les écritures persistantes, attributions de rôles, politiques d'accès, routes,
   endpoints, traitements de formulaires, tâches de cache et personnalisations fonctionnelles encore listés ici.
2. Faire en sorte que le plugin ne dépende d'aucun fichier, asset, template ou callback du thème
   `chassesautresor`.
3. Décider où vit l'interface spécifique au produit : templates surchargeables fournis par le plugin, blocs,
   shortcodes ou intégration documentée au nouveau thème.
4. Étendre le test de frontière pour refuser les mutations persistantes et les hooks métier dans tout le thème,
   et non dans les seuls templates.
5. Activer un thème WordPress neutre sur un environnement de recette et valider au minimum les parcours joueur,
   organisateur et administrateur, les courriels, les tâches planifiées et les écrans WooCommerce.

## Conclusion opérationnelle

Le plugin doit rester actif, mais ce n'est pas suffisant aujourd'hui. Tant que ces conditions ne sont pas remplies,
le thème `chassesautresor` reste une dépendance fonctionnelle du site et ne peut pas être remplacé sans régression.
