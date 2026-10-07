# Étapes intermédiaires d’une énigme

Le type de contenu privé `enigme_etape` porte les étapes ordonnées d’une énigme. Il n’a pas de page publique propre,
n’est pas exposé dans l’API REST et n’offre aucun accès supplémentaire au back-office aux organisateurs.

## Modèle éditorial actuel

Le groupe ACF local `group_enigme_etape_configuration` est enregistré par le plugin sur `acf/init`. Il reste la source
de vérité du stockage, même si l’organisateur utilise exclusivement le formulaire frontal.

| Donnée | Stockage | Rôle |
|---|---|---|
| Nom interne | `post_title` | Repère obligatoire réservé à l’organisateur, jamais affiché au joueur. |
| Énigme | `etape_enigme_associee` | Relation obligatoire vers l’énigme parente. |
| Texte | `etape_contenu` | Contenu affiché au déblocage de l’étape. |
| Image | `etape_image` | Page BD facultative (A4), stockée sous forme d’identifiant de média. Une fois débloquée, elle s’ajoute à la galerie de l’énigme comme page supplémentaire, plutôt que d’apparaître sous les visuels. Sur grand écran, la galerie feuillette par planche (deux pages), comme un album ; sur mobile, une seule page reste visible. |
| Ordre | `menu_order` | Position linéaire de l’étape dans l’énigme. |

Une étape enregistrée exige toujours un nom interne. Simple clic et Réponse texte exigent également au moins un texte
ou une image ; les widgets autonomes n’en ont pas besoin. Le simple clic possède un libellé personnalisable. La réponse
texte accepte plusieurs solutions et une comparaison optionnellement sensible à la casse. La configuration des widgets
est figée avec l’architecture du parcours.
Le pavé à huit directions accepte un ou plusieurs codes, un par ligne, et normalise les abréviations françaises
`O`, `NO`, `SO` vers `W`, `NW`, `SW`. Son apparence est personnalisable avec les variables CSS
`--direction-key-bg`, `--direction-key-color`, `--direction-key-shadow`, `--direction-key-size` et
`--direction-arrow-stroke`.
Le clavier de couleurs reprend les douze couleurs du modèle Lockee : rouge, orange, jaune, vert, bleu, violet, indigo,
rose, marron, gris, noir et blanc. Il accepte plusieurs séquences et affiche la saisie sous forme de pastilles.
Le pavé numérique conserve les zéros initiaux. La molette de coffre-fort couvre les valeurs 0 à 99 et enregistre chaque
mouvement au relâchement : `H` représente le sens horaire et `A` le sens antihoraire, par exemple `H11 A51`.

## Édition frontale

Depuis le panneau **Paramètres** de l’énigme, **Ajouter une étape** et **Modifier** remplacent temporairement la liste
par un formulaire dédié. **Annuler** restaure la liste sans créer de brouillon. **Enregistrer** crée ou met à jour
l’étape par AJAX, puis restaure la liste sans ouvrir ni recharger une page d’administration WordPress.

Le sélecteur d’image réutilise la médiathèque déjà autorisée aux organisateurs. Les opérations AJAX vérifient le nonce,
le droit de modifier l’énigme, l’appartenance de l’étape et la validité du média.
Le serveur valide le contenu et toute la configuration du widget avant de créer ou modifier l’étape. Les codes
directionnels, de couleurs, numériques et de coffre-fort contenant un symbole inconnu sont refusés au lieu d’être
corrigés silencieusement. Une erreur de configuration ne peut donc pas laisser une étape publiée partiellement.
La complétude globale de l’énigme inclut toutes les étapes existantes : une étape sans nom, sans contenu obligatoire ou
avec un widget incomplet empêche la validation de la chasse. Un parcours sans étape intermédiaire reste valide.

## Progression et tentatives

La table `wp_enigme_etapes_progression` mémorise les étapes réussies. Le parcours est linéaire : les étapes terminées
et la première étape incomplète sont visibles ; les étapes futures ne sont pas envoyées au navigateur. Après la dernière
étape, la réponse finale devient disponible.

Le clic sur l’étape courante est validé côté serveur, enregistré comme une interaction réussie liée à `etape_id`, puis
débloque l’étape suivante. Il ne coûte aucun point et ne consomme aucune tentative. Les étapes terminées restent
affichées, tandis que leur widget est remplacé par un état en lecture seule.

Une mauvaise réponse texte est enregistrée comme `faux` et consomme une tentative dans la limite globale de l’énigme.
Une bonne réponse termine l’étape et révèle la suivante. Aucun point n’est débité par une étape intermédiaire.
L’enregistrement de la tentative et l’avancement éventuel de l’étape utilisent une transaction commune. Une insertion
incomplète est annulée, et une seconde soumission de la même étape est refusée après réévaluation de la progression.
Les variantes personnalisées utilisent le format `réponse | message` : elles affichent leur message d’aide, ne terminent
pas l’étape et ne consomment aucune tentative.
Lorsque la casse est ignorée, les accents français le sont également. Les apostrophes typographiques et droites, ainsi
que les différentes variantes typographiques du tiret, sont considérées comme équivalentes. Les tirets ne sont pas
supprimés : `arc-en-ciel` reste donc distinct de `arc en ciel` afin de ne pas accepter des réponses trop éloignées.
Le compteur et le formulaire sont actualisés immédiatement après une erreur ; lorsque la limite est atteinte, la saisie
est désactivée sans attendre un rechargement. L’outil administratif de remise à zéro efface aussi la progression des
étapes afin que les parcours puissent être rejoués pendant les tests.

Après une réussite, l’étape suivante ou le formulaire de réponse finale est injecté dans le panneau par AJAX, sans
recharger la page. Les visuels protégés déjà affichés ne sont donc pas redemandés et ne déplacent plus la cible pendant
le changement d’étape.

La colonne nullable `etape_id` de `wp_enigme_tentatives` permet de rattacher une interaction à une étape. Seul le
résultat `faux` consomme actuellement le quota : une variante personnalisée et, à terme, un clic de confirmation ne
consomment rien. Le futur délai entre deux essais aura une portée globale à l’énigme.

## Verrouillage de l’architecture

Tant que l’énigme n’est pas en cours, les étapes peuvent être ajoutées, supprimées et réordonnées. L’architecture est
figée dès que la chasse quitte les statuts de création ou de correction, lorsqu’elle est en cours, payante ou terminée,
ainsi que dès qu’une progression de joueur existe. Le serveur refuse alors l’ajout, la suppression et le
réordonnancement, même si un appel AJAX est fabriqué manuellement. Le nom interne, le texte et l’image restent
modifiables pour permettre les corrections éditoriales sans altérer le parcours.


## Reprise du développement

Le bilan détaillé, la dette technique, les priorités et un message prêt à copier pour ouvrir le prochain fil sont
regroupés dans [`docs/etapes-enigme-transition.md`](etapes-enigme-transition.md).
La checklist de validation finale est disponible dans
[`docs/etapes-enigme-recette.md`](etapes-enigme-recette.md).
