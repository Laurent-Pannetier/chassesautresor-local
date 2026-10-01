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

Le trente-troisième lot a rendu autonome le contrôleur des images protégées. La décision d'accès à l'énigme, la
résolution des relations joueur/organisateur et la recherche du fichier image ou WebP sont maintenant réalisées par
un service du plugin. Cette route ne teste et n'appelle plus `utilisateur_peut_voir_enigme()` ni
`trouver_chemin_image()`, qui restent dans le thème uniquement pour ses propres vues historiques.

Le trente-quatrième lot a rendu autonome la seconde route de média protégé, celle des fichiers de solution. La recherche
de la solution active, les politiques d'accès aux solutions de chasse et d'énigme, les engagements, la progression et
la résolution de la pièce jointe sont désormais orchestrés dans le plugin. Le contrôleur ne dépend plus de
`solution_recuperer_par_objet()`, `utilisateur_peut_voir_solution_enigme()`,
`utilisateur_peut_voir_solution_chasse()` ni du logger du thème. Les deux routes de médias protégés sont donc maintenant
indépendantes du thème. Le contrôleur d'administration des conversions ne recherche plus non plus le logger
`cat_debug()` du thème.

Le trente-cinquième lot a rendu autonome le contrôleur de navigation des pages d'énigme. La résolution de la chasse,
la synchronisation du cache relationnel, les engagements chasse/énigme, les prérequis, la visibilité, la complétude,
l'association organisateur et les tentatives en attente sont maintenant résolus par les services du plugin. Le
contrôleur n'appelle plus les douze adaptateurs métier correspondants du thème ; celui-ci conserve les vues et les
helpers encore employés par ces vues.

Le trente-sixième lot a retiré du thème les politiques et callbacks de cycle de vie des mutations d'énigme. Les
contrôleurs vérifient maintenant directement la modification du contenu et l'accès aux champs via un résolveur du
plugin. L'initialisation, le recalcul d'état système et le rafraîchissement de complétude sont enregistrés et exécutés
par un gestionnaire de hooks du core. `edition-enigme.php` ne conserve plus que le chargement des scripts et l'adaptateur
de création utilisé par la présentation.

Le trente-septième lot applique la même séparation aux mutations de chasse. Les autorisations de dates et de champs,
la clôture, la publication planifiée des solutions et les recalculs de statut sont maintenant orchestrés par le plugin.
Les six filtres et callbacks métier correspondants ont été retirés de `edition-chasse.php`. Le hook général de
rafraîchissement du statut, auparavant encore relié au thème, est également détenu par le gestionnaire de cycle de vie
du core.

Le trente-huitième lot a transféré les politiques de mutation de l'organisateur et des indices. Le contrôleur
d'organisateur utilise directement les politiques de contenu du plugin. Les contrôleurs d'indices utilisent un
résolveur core qui applique les règles de création, modification et suppression à une chasse ou une énigme, ainsi que
le résolveur commun d'accès aux champs. Les cinq filtres d'autorisation correspondants ont été retirés des fichiers
d'édition du thème ; leurs filtres de rendu restent volontairement dans la présentation.

Le trente-neuvième lot a retiré les cinq injections de lecture encore utilisées par les tableaux et options d'indices.
Le plugin résout maintenant lui-même la chasse d'une énigme, les énigmes d'une chasse, le prochain rang d'indice et
l'existence d'une solution. Les seuls filtres conservés dans ce parcours sont les deux moteurs de rendu de carte et de
tableau, qui restent légitimement dans la couche de présentation.

Le quarantième lot a autonomisé les contrôleurs de solutions. L'autorisation des actions, la relation chasse/énigmes et
la détection des solutions existantes sont maintenant résolues directement par le plugin. Le résolveur précédemment
spécifique aux indices est devenu un résolveur générique des contenus liés. Les quatre filtres métier ont été retirés
de `edition-solution.php`, qui conserve uniquement le rendu du tableau et ses adaptateurs de templates.

Le quarante-et-unième lot a transféré le reste du cycle de vie des images protégées : répertoire d'upload dédié,
protection après sauvegarde et autorisation des contrôleurs temporaires. `edition-securite.php` ne conserve que le
formatage visuel des galeries vers la route protégée. Le contrôleur vérifie maintenant directement la politique de
modification du plugin, sans filtre d'autorisation fourni par le thème.

Le quarante-deuxième lot a transféré l'invalidation du cache d'affichage des chasses. Les sauvegardes de chasse,
d'énigme et d'organisateur, les changements de taxonomie, les engagements et les modifications des utilisateurs
associés invalident désormais ce cache depuis un gestionnaire de hooks du plugin. Le thème conserve la préparation du
view-model mis en cache, mais ne possède plus ses mutations, sa recherche des chasses affectées ni le callback déclenché
par le recalcul de complétude.

Le quarante-troisième lot a transféré la politique des avatars personnalisés. Le plugin détenait déjà le contrôleur
d'upload et possède maintenant aussi la liste des formats autorisés, la résolution de l'utilisateur et le remplacement
de l'avatar WordPress à partir de la métadonnée persistée. Le thème ne conserve que le chargement du script de
présentation sur l'espace personnel.

Le quarante-quatrième lot a retiré deux derniers hooks métier isolés du thème. La validation des dates de fin des
chasses soumises via ACF est maintenant une politique du plugin, y compris les règles liées à la date de début et au
démarrage immédiat. La redirection des pages individuelles d'indice vers leur chasse ou leur énigme est désormais
enregistrée directement par le contrôleur core, sans adaptateur dans `edition-indice.php`.

Le quarante-cinquième lot a transféré les fonctions publiques du cycle de demande organisateur. La création et le
renvoi du jeton, son état, sa suppression et l'envoi de l'email sont maintenant exposés par le plugin sans callbacks du
thème. La confirmation effective reste traitée par la route autonome du core, qui crée le profil et attribue le rôle.
L'ancien template de confirmation ne lit plus le jeton, ne crée plus de CPT et ne modifie plus les rôles ; il ne conserve
qu'un message de présentation de secours.

Le quarante-sixième lot a autonomisé la politique de demande de validation d'une chasse et le nettoyage de ses messages
de correction. Le plugin résout maintenant le rôle, l'organisateur associé, ses utilisateurs, la complétude de la chasse
et de ses énigmes ainsi que leurs statuts, sans helpers du thème. La route de validation appelle directement ce résolveur
et le service de messages du core. Les fonctions globales restent disponibles pour les vues historiques, mais leurs
implémentations résident désormais dans le plugin.

Le quarante-septième lot a déplacé le cycle du message informant qu'une chasse est éligible à la validation. La
résolution chasse/énigme, le rafraîchissement de complétude, la politique d'éligibilité et la persistance ou suppression
du message sont maintenant orchestrés par un gestionnaire de hook du plugin. `user-functions.php` ne possède plus ce
traitement exécuté sur `template_redirect` et le plugin ne dépend plus des helpers de chasse du thème pour ce parcours.

Le quarante-huitième lot a transféré les dernières fonctions d'écriture des messages du compte : ajout persistant,
message éphémère, suppression et construction du service sont maintenant fournis par le plugin. Les templates
historiques peuvent conserver leurs appels à cette API globale sans que sa persistance appartienne au thème. L'ancien
adaptateur de création d'un organisateur, devenu inutilisé depuis l'autonomisation de la route de confirmation, a aussi
été supprimé d'`edition-organisateur.php`, avec ses injections de `wp_insert_post` et `update_field`.

Le quarante-neuvième lot a supprimé les politiques d'accès au back-office encore enregistrées par `edition-core.php`.
La redirection hors de l'administration est déjà détenue par `BackOfficeAccessHookHandler` et les anciens callbacks du
thème, concurrents et plus permissifs, ont été retirés. La limitation du champ ACF des utilisateurs associés à l'auteur
de l'organisateur est maintenant enregistrée par `WordPressAccessPolicyHookHandler`. Les classes et styles purement
visuels des écrans d'édition restent dans le thème.

Le cinquantième lot a transféré les six fonctions de compatibilité du cycle des solutions : planification, publication,
traitement cron, mise à jour du cache et sauvegarde ACF sont maintenant exposés par le plugin. `edition-solution.php` ne
conserve plus ces points d'entrée mutationnels. Le filtre de classes CSS d'`edition-core.php` ne force également plus de
recalcul de complétude pendant le rendu ; les gestionnaires de sauvegarde et de vue du core possèdent déjà ce cycle.

Le cinquante-et-unième lot a retiré les derniers adaptateurs de route de `edition-solution.php`. L'enregistrement et le
rafraîchissement des règles de réécriture ainsi que la redirection des pages de solution étaient déjà enregistrés par le
plugin ; leurs wrappers inutilisés ont été supprimés. La fonction historique de création reste disponible, mais vit
maintenant avec les autres fonctions de compatibilité dans `solution-functions.php` côté core. Le fichier du thème ne
conserve plus que le moteur de rendu du tableau de solutions.

Le cinquante-deuxième lot a transféré la dernière politique ACF et les derniers adaptateurs de route
d'`edition-indice.php`. Le préremplissage de la chasse liée résout maintenant directement dans le plugin la cible chasse
ou la relation énigme/chasse. La création globale historique vit désormais dans `hint-functions.php` ; l'enregistrement
et le rafraîchissement de la route sont déjà détenus par le core. Enfin, les contrôleurs de création et de suppression
n'utilisent plus le filtre `chassesautresor_can_manage_hint` : ils appliquent directement `RelatedContentAccessResolver`.
Le fichier du thème ne conserve que les moteurs de rendu et leurs view-models.

Le cinquante-troisième lot a transféré au plugin le filtre ACF de la condition d'accès des énigmes. Le core
résout désormais la chasse, lit son cache relationnel, qualifie les modes de validation des énigmes candidates et
retire l'option « pré-requis » lorsqu'aucune candidate n'est éligible. Le thème ne possède plus ni le hook ni le helper
de sélection correspondant.

Le cinquante-quatrième lot a supprimé la dépendance du cron de statuts envers le thème. Le planificateur appelle
maintenant directement `HuntStatusUpdater` dans le plugin au lieu d'émettre un événement dont le seul abonné vivait
dans `statut-functions.php`. La normalisation des valeurs ACF du lot précédent accepte aussi explicitement les ID,
tableaux et objets WordPress renvoyés selon le format du champ.

Le cinquante-cinquième lot a transféré au plugin la résolution locale des images protégées. La sélection de la taille,
la préférence WebP, le repli vers l'original, la détection MIME et le cache objet appartiennent maintenant à
`ProtectedImagePathService`. La fonction globale historique reste disponible depuis le core pour les vues existantes,
mais `inc/enigme/visuels.php` ne contient plus cette lecture de fichiers ni ses six écritures de cache.

Le cinquante-sixième lot a transféré au plugin les données statistiques de la barre latérale des énigmes. Le calcul de
la progression du joueur, de l'engagement moyen et du taux de résolution, avec exclusions et caches, appartient à
`RiddleSidebarStatisticsService`. Le thème ne reçoit plus que les valeurs nécessaires à ses deux moteurs de rendu.

Le cinquante-septième lot a déplacé dans le plugin les cinq fonctions globales de compatibilité du statut des chasses et
a supprimé deux anciens wrappers du planificateur. Le contrôleur de modération appelle maintenant directement les
updaters du core pour les statuts de chasse et d'énigme, sans transmettre leurs noms de fonctions historiques.

Le cinquante-huitième lot a supprimé les trois dépendances globales restantes du contrôleur de modération. Les énigmes
et l'organisateur sont maintenant résolus par les services de relation du plugin, et la suppression réutilise le
workflow autonome de `HuntDeletionAjaxHandler`. Le chemin de modération ne dépend donc plus du thème.

Le cinquante-neuvième lot a déplacé dans le plugin la persistance du view-model d'affichage d'une chasse. Le thème
continue provisoirement à assembler ce modèle, mais sa lecture et ses écritures dans le cache objet et le transient
passent désormais par `HuntDisplayViewCacheService`. Une seule écriture de cache, strictement HTML, reste dans le thème.

Le soixantième lot a déplacé cette dernière écriture dans `RiddleRenderCacheHookHandler`. Le thème construit toujours le
fragment HTML de solution, mais le core possède maintenant sa lecture, sa durée de vie, son écriture et son invalidation.
La garde de frontière interdit désormais toute écriture de cache ou de transient dans le thème.

Le soixante-et-unième lot a déplacé dans le plugin les cinq fonctions de compatibilité du statut système des énigmes :
rafraîchissement individuel ou par chasse, callback historique de sauvegarde, endpoint de recalcul et lecture de l'état.
`statut-functions.php` conserve les décisions de participation encore utilisées par les vues, mais plus ce cycle de vie.

Le soixante-deuxième lot, volontairement plus large, a transféré les dix fonctions publiques de complétude des
organisateurs, chasses et énigmes. Le plugin résout lui-même les énigmes d'une chasse, les modes de validation, les
bonnes réponses et le cycle du cache de complétude. Le thème ne conserve plus ces wrappers métier.

Le soixante-troisième lot conserve cette taille accrue et transfère ensemble les sept fonctions publiques de progression
des énigmes : construction du service, bonnes réponses, lecture et avancement du statut joueur, prérequis et résolution.
Le plugin normalise directement les relations ACF ; `statut-functions.php` est ramené à six fonctions mixtes ou visuelles.

Le soixante-quatrième lot complète ce bloc en transférant les quatre décisions restantes : verrouillage, état de
participation, visibilité et autorisation d'engagement. Le plugin résout directement chasse, organisateur, engagements
et prérequis. `statut-functions.php` ne conserve plus que le filtre de rendu du badge et le contexte visuel de création.

Le soixante-cinquième lot corrige la collision fatale observée en environnement WordPress complet :
`enigme_get_bonnes_reponses()` existait encore sans garde dans `inc/enigme/reponses.php`. Cette copie et les deux copies
gardées de `cat_get_hunt_progress_service()` ont été retirées. Un test transversal compare maintenant toutes les
fonctions globales déclarées par le thème et le plugin pour empêcher toute nouvelle redéclaration.

Le soixante-sixième lot termine `statut-functions.php` et `badge-functions.php`. La composition portable des badges de
statut appartient maintenant à `HuntStatusBadgeService` et le contrôleur AJAX ne dépend plus d'un filtre de rendu du
thème. Le helper de contexte `is_canevas_creation()`, sans appel de production, a été supprimé. Ces deux fichiers du
thème ne déclarent désormais plus aucune fonction.

Le soixante-septième lot ouvre un bloc plus large sur les relations en transférant sept fonctions publiques : fabrique
des services organisateur/relation, résolution organisateur depuis utilisateur, chasse ou contexte, et vérification de
l'association d'un utilisateur. Le plugin ne dépend d'aucun helper du thème pour ces fonctions et la garde anti-collision
couvre automatiquement cette nouvelle API. Les estimations restent inchangées tant que les autres relations demeurent.

Le soixante-huitième lot termine ce bloc en transférant les vingt-trois fonctions restantes de
`inc/relations-functions.php` : requêtes chasse/énigmes, listes organisateur, indicateurs calculés et synchronisation
du cache relationnel. Le fichier du thème ne déclare désormais plus aucune fonction et le plugin n'appelle pas le
helper de journalisation `cat_debug()` du thème. Un test de frontière vérifie à la fois l'absence de déclarations dans
le thème et la présence des principaux points d'entrée dans le core. Les estimations restent inchangées : cette
migration améliore l'autonomie déjà notée à 100 %, sans réduire les quatre view-models ni fournir de parcours neutre.

Le soixante-neuvième lot transfère également les huit points d'entrée de données et de progression de chasse encore
placés en tête de `inc/chasse-functions.php` : champs structurés, table et dépôt des gagnants, engagement et compteurs.
Le thème ne conserve dans ce fichier que les assembleurs et fonctions de rendu. La frontière vérifie désormais
explicitement la propriété core de cette API.

Le soixante-dixième lot complète cette API avec le calcul de progression d'un joueur, le comptage des participants et
l'enregistrement d'un engagement. Ces trois décisions utilisent désormais directement les services du plugin ; leur
absence dans le thème et leur présence dans le core sont couvertes par le test de frontière transversal.

Le soixante-et-onzième lot transfère les quatre fonctions de requête et de visibilité des solutions. Elles complètent
le fichier de compatibilité déjà possédé par le plugin, sans créer une seconde API ni modifier le rendu HTML conservé
dans le thème. La frontière interdit désormais leur redéclaration dans `inc/chasse-functions.php`.

Le soixante-douzième lot déplace le view-model CTA des chasses et ses deux formulaires de validation dans le plugin.
Il retire ainsi `inc/chasse-functions.php` de la liste des grands assembleurs mixtes : le thème en conserve désormais
trois. Le plugin fournit en contrepartie ce fragment d'interface minimal, nécessaire pour éviter toute dépendance
silencieuse vers un callback de rendu du thème.

### Indicateur de progression à jour

| Objectif | Progression | Évolution de ce lot |
|---|---:|---|
| Extraction du métier PHP inventorié | **95 %** | Un grand view-model mixte retiré du thème ; trois restent |
| Remplaçabilité effective du thème | **38 %** | Le plugin fournit le CTA, mais aucun parcours complet de secours |

Ces indicateurs sont recalculés avec la grille détaillée plus bas, et non à partir du nombre de fonctions déplacées.
Ils seront modifiés uniquement lorsqu'un axe pondéré de cette grille progresse effectivement.

### Inventaire reproductible au 1er octobre 2026

Les recherches ci-dessous portent sur les fichiers de production du thème (les fixtures sous `tests/` sont exclues).
Elles doivent être relancées ensemble : un nombre brut de hooks ou de fichiers ne constitue pas seul un pourcentage.

```bash
rg -g '*.php' -g '!**/tests/**' -o "add_(action|filter)\s*\(" wp-content/themes/chassesautresor | wc -l
rg -g '*.php' -g '!**/tests/**' -o "\b(wp_(insert|update|delete)_post|update_(field|post_meta|user_meta|option)|add_(post_meta|user_meta|option)|delete_(post_meta|user_meta|option|transient)|set_transient|wp_cache_(set|add|delete))\s*\(|\$wpdb->(insert|update|delete|query)\s*\(" wp-content/themes/chassesautresor | wc -l
rg -g '*.php' -g '!**/tests/**' -o "add_filter\s*\(\s*['\"]acf/" wp-content/themes/chassesautresor | wc -l
rg -g '*.php' -g '!**/tests/**' -o "[A-Za-z]+Handler::configure\s*\(" wp-content/themes/chassesautresor | wc -l
find wp-content/themes/chassesautresor -type f \( -name '*.js' -o -name '*.css' -o -name '*.scss' \) ! -path '*/tests/*' | wc -l
find wp-content/themes/chassesautresor -type f -name '*.php' | rg '/(templates|template-parts)/|/(single|page)-[^/]*\.php$' | wc -l
```

Résultats obtenus après ce lot :

- **68 enregistrements de hooks** demeurent dans le thème. Leur revue ligne par ligne ne trouve **plus aucun hook métier**. Les 68 hooks concernent le rendu,
  l'intégration Astra/WooCommerce/ACF, les shortcodes ou le chargement d'assets ;
- **aucune écriture** WordPress, SQL, de cache ou de transient n’est désormais détectée dans le thème et aucune
  planification de tâche n’y demeure ;
- **5 filtres ACF** demeurent après retrait du filtre métier de condition d'accès. Ils préparent ou formatent des
  champs de présentation ; aucune autre politique d'accès ACF enregistrée par le thème n'a été trouvée ;
- le contrôleur de modération ne contient désormais **aucun appel direct** aux fonctions globales du thème qui avaient
  été inventoriées. Le plugin conserve **15 configurations de contrôleurs par le thème**, toutes destinées à des
  moteurs de rendu ;
- la revue ciblée relève **3 grands assembleurs de view-models mixtes** :
  `inc/enigme/affichage.php`, `inc/sidebar.php` et `inc/user-functions.php`. Ils combinent
  encore données WordPress, progression ou accès avec CTA et HTML ;
- **83 templates/parcours PHP** et **93 assets JavaScript/CSS/SCSS** restent fournis exclusivement par le thème. Le
  dépôt ne contient toujours aucune preuve de recette complète avec un thème neutre.

### Estimations recalculées

Une grille fixe, plutôt qu'un décompte des lots, est utilisée. Pour l'extraction PHP, les cinq axes ont le même poids :
propriété des hooks métier, absence de mutations dans le rendu, politiques d'accès, autonomie vis-à-vis des fonctions
globales du thème et séparation des view-models. Les preuves ci-dessus donnent respectivement 100 %, 100 %, 100 %,
100 % et 75 %, soit **environ 95 % pour l'extraction du métier PHP inventorié**.

Pour la remplaçabilité, la grille pondère l'extraction PHP à 40 %, la présence de parcours de secours à 25 %, les
assets indépendants à 15 %, l'absence de callbacks de rendu fournis par le thème à 10 % et une recette neutre réussie
à 10 %. Seul le premier axe est partiellement satisfait : **environ 38 % de remplaçabilité effective du thème**.

Ces deux valeurs sont des estimations prudentes et reproductibles à partir de la grille déclarée. Elles ne reprennent
ni l'ancienne valeur de 98 %, ni automatiquement les ordres de grandeur de 90 % et 70 %. Les compteurs mesurent une
surface résiduelle ; la revue qualitative documentée détermine les notes de chaque axe.

## Éléments bloquants observés

### Contrôleurs, politiques et routes encore attachés au thème

Le thème conserve encore notamment :

- des view-models volumineux qui assemblent directement règles d'accès, progression, CTA et données WordPress ;
- plusieurs caches de données construits pendant le rendu dans `inc/chasse-functions.php` et `inc/enigme/*` ;
- l'ensemble des parcours produit et de leurs assets, qui n'ont pas encore de rendu de secours fourni par le plugin.

L'audit ciblé des messages importants et des écrans WordPress natifs ne relève plus de dépendance vers les helpers
globaux du thème. Les configurations `*Handler::configure()` inventoriées ne transmettent actuellement que des
fonctions de rendu. Les adaptateurs d'accès encore utilisés par les vues restent en revanche à qualifier et migrer.

Les prochains lots doivent relancer cet inventaire et conserver la grille annoncée pour faire évoluer les deux
estimations. Aucun gain de remplaçabilité ne doit être annoncé sans recherche statique, tests de frontière et recette
sous thème neutre.

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
