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

Une étape enregistrée exige un nom interne et au moins un texte ou une image. Sa réponse n’appartient volontairement
pas à ce lot : elle sera configurée par le futur moteur commun de widgets de réponse pour les énigmes et leurs étapes,
y compris le widget de simple clic.

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

La colonne nullable `etape_id` de `wp_enigme_tentatives` permet de rattacher une interaction à une étape. Seul le
résultat `faux` consomme actuellement le quota : une variante personnalisée et, à terme, un clic de confirmation ne
consomment rien. Le futur délai entre deux essais aura une portée globale à l’énigme.

## Règles prévues avant mise en ligne

Tant que l’énigme n’est pas en cours, les étapes peuvent être ajoutées, supprimées et réordonnées. Dès le démarrage de
la chasse ou l’existence d’une progression, l’architecture et les réponses devront être figées. Seuls le nom interne,
le texte et l’image resteront corrigibles. Une étape en brouillon bloquera la validation de l’énigme.
