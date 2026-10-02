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
