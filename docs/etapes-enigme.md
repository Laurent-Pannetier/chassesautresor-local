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
| Image | `etape_image` | Illustration facultative, stockée sous forme d’identifiant de média. |
| Ordre | `menu_order` | Position linéaire de l’étape dans l’énigme. |

Une étape enregistrée exige un nom interne et au moins un texte ou une image. Deux widgets sont disponibles : le simple
clic, dont le libellé est personnalisable, et la réponse texte avec plusieurs réponses acceptées et une comparaison
optionnellement sensible à la casse. Leur configuration est figée avec l’architecture du parcours.

## Édition frontale

Depuis le panneau **Paramètres** de l’énigme, **Ajouter une étape** et **Modifier** remplacent temporairement la liste
par un formulaire dédié. **Annuler** restaure la liste sans créer de brouillon. **Enregistrer** crée ou met à jour
l’étape par AJAX, puis restaure la liste sans ouvrir ni recharger une page d’administration WordPress.

Le sélecteur d’image réutilise la médiathèque déjà autorisée aux organisateurs. Les opérations AJAX vérifient le nonce,
le droit de modifier l’énigme, l’appartenance de l’étape et la validité du média.

## Progression et tentatives

La table `wp_enigme_etapes_progression` mémorise les étapes réussies. Le parcours est linéaire : les étapes terminées
et la première étape incomplète sont visibles ; les étapes futures ne sont pas envoyées au navigateur. Après la dernière
étape, la réponse finale devient disponible.

Le clic sur l’étape courante est validé côté serveur, enregistré comme une interaction réussie liée à `etape_id`, puis
débloque l’étape suivante. Il ne coûte aucun point et ne consomme aucune tentative. Les étapes terminées restent
affichées, tandis que leur widget est remplacé par un état en lecture seule.

Une mauvaise réponse texte est enregistrée comme `faux` et consomme une tentative dans la limite globale de l’énigme.
Une bonne réponse termine l’étape et révèle la suivante. Aucun point n’est débité par une étape intermédiaire.
Le compteur et le formulaire sont actualisés immédiatement après une erreur ; lorsque la limite est atteinte, la saisie
est désactivée sans attendre un rechargement. L’outil administratif de remise à zéro efface aussi la progression des
étapes afin que les parcours puissent être rejoués pendant les tests.

La colonne nullable `etape_id` de `wp_enigme_tentatives` permet de rattacher une interaction à une étape. Seul le
résultat `faux` consomme actuellement le quota : une variante personnalisée et, à terme, un clic de confirmation ne
consomment rien. Le futur délai entre deux essais aura une portée globale à l’énigme.

## Verrouillage de l’architecture

Tant que l’énigme n’est pas en cours, les étapes peuvent être ajoutées, supprimées et réordonnées. L’architecture est
figée dès que la chasse quitte les statuts de création ou de correction, lorsqu’elle est en cours, payante ou terminée,
ainsi que dès qu’une progression de joueur existe. Le serveur refuse alors l’ajout, la suppression et le
réordonnancement, même si un appel AJAX est fabriqué manuellement. Le nom interne, le texte et l’image restent
modifiables pour permettre les corrections éditoriales sans altérer le parcours.
