# Étapes intermédiaires d’une énigme

## Premier lot

Le type de contenu privé `enigme_etape` porte la définition éditoriale d’une étape intermédiaire. Il n’est ni
interrogeable publiquement, ni exposé dans la recherche, les menus ou l’API REST. Son interface WordPress reste
active à des fins d’inspection, sans entrée autonome dans le menu d’administration.

Les étapes n’ont pas de page publique propre : elles seront rendues dans la page de leur énigme après contrôle de
la progression du joueur. Leur ordre utilisera la propriété WordPress `menu_order` fournie par le support
`page-attributes`.

## Champs ACF enregistrés par le plugin

Le groupe local `group_enigme_etape_configuration` est enregistré sur `acf/init`. Il constitue la source de vérité
du schéma et ne doit pas être recréé dans l’interface ACF.

| Champ | Type | Rôle |
|---|---|---|
| `etape_enigme_associee` | Objet de publication | Relation obligatoire vers une `enigme`, retournée sous forme d’ID. |
| `etape_libelle` | Texte | Nom interne de l’étape, limité à 120 caractères. |
| `etape_afficher_titre` | Vrai/faux | Indique si le nom sera visible par le joueur. |
| `etape_contenu` | WYSIWYG | Texte présenté une fois l’étape débloquée. |
| `etape_image` | Image | Illustration facultative, retournée sous forme d’ID. |
| `etape_widget_type` | Sélection | Widget `texte` ou `directions_8`. |
| `etape_reponses_texte` | Répéteur | Une à cinq réponses acceptées pour le widget texte. |
| `etape_reponse_respecter_casse` | Vrai/faux | Active la comparaison sensible à la casse. |
| `etape_directions_sequence` | Répéteur | Une à vingt directions parmi les huit valeurs canoniques. |
| `etape_widget_afficher_saisie` | Vrai/faux | Affiche la séquence directionnelle en cours. |
| `etape_widget_autoriser_effacement` | Vrai/faux | Affiche le bouton permettant de recommencer la séquence. |

Les valeurs canoniques du pavé directionnel sont `N`, `NE`, `E`, `SE`, `S`, `SW`, `W` et `NW`.

## Limites de ce lot

Ce premier lot n’ajoute volontairement pas encore :

- la table de progression des joueurs ;
- la création et la réorganisation depuis l’éditeur frontal d’une énigme ;
- le rendu public des étapes ;
- la soumission et l’évaluation des réponses ;
- la protection spécifique des images d’étape.

Ces éléments dépendront du CPT et du schéma ACF introduits ici.

## Deuxième lot : socle de progression

La table `wp_enigme_etapes_progression` mémorise uniquement les étapes trouvées. Une étape sans ligne de progression
est soit l’étape courante, soit une étape future ; le service de progression distingue ces deux cas à partir de l’ordre
du parcours.

Le parcours est strictement linéaire : les étapes trouvées et la première étape incomplète sont visibles, tandis que
les suivantes restent absentes. La réponse finale de l’énigme n’est disponible qu’après la réussite de la dernière
étape. Une énigme sans étape conserve immédiatement sa réponse finale disponible.

La colonne nullable `etape_id` est ajoutée à `wp_enigme_tentatives`. Une valeur nulle désigne une tentative sur la
réponse finale historique ; un ID désignera une tentative sur une étape. Le compteur quotidien propre au parcours
compte uniquement les réponses non validées (`faux` ou `variante`), afin que plusieurs étapes réussies avant une
erreur ne consomment qu’une seule tentative.

## Troisième lot : cycle de vie éditorial

Les étapes sont désormais requêtées par leur relation `etape_enigme_associee`, puis triées par `menu_order` et par
identifiant. La création produit un brouillon lié à l’énigme avec le widget texte par défaut. Une réorganisation n’est
acceptée que si elle contient exactement tous les identifiants actuels, sans ajout, omission ni duplication.

Lors de la suppression définitive d’une étape, ses progressions et ses tentatives sont supprimées. La suppression
définitive d’une énigme supprime également toutes ses étapes et les progressions correspondantes. Ce socle sera utilisé
par les futurs contrôleurs de l’éditeur frontal ; il n’ajoute pas encore de bouton visible sur la page de l’énigme.

## Quatrième lot : gestion depuis l’éditeur d’énigme

Le panneau de paramètres d’une énigme affiche maintenant ses étapes sous forme de liste ordonnée. Un utilisateur
autorisé peut créer une étape, ouvrir sa configuration ACF, la supprimer définitivement ou la déplacer par
glisser-déposer. Les opérations passent par des actions AJAX authentifiées, protégées par nonce et par la politique
de modification de l’énigme.

La configuration détaillée continue provisoirement à utiliser l’écran WordPress du CPT `enigme_etape`. Une prochaine
itération pourra intégrer ce formulaire dans une modale frontale sans modifier les services métier de ce lot.

Les comptes organisateurs sont autorisés à ouvrir l’écran natif `post.php` uniquement pour une `enigme_etape` liée à
une énigme qu’ils peuvent effectivement modifier. Les autres écrans de l’administration restent interdits par la
politique générale d’accès au back-office.
