# Transition — étapes intermédiaires et widgets de réponse

Ce document sert de point de reprise après la fusion du premier ensemble de travaux sur les étapes intermédiaires.
Il complète [`docs/etapes-enigme.md`](etapes-enigme.md), qui décrit le comportement fonctionnel livré.

## Objectif métier confirmé

Une énigme peut contenir un parcours linéaire d’étapes automatiques. Le joueur voit les étapes déjà réussies et l’étape
courante, mais aucune étape future. La réponse finale de l’énigme n’est disponible qu’après la dernière étape.

Les étapes terminées restent affichées et dépliées. Leur widget disparaît sans afficher de mention persistante de
réussite. Le nom d’une étape reste un repère interne et n’est jamais affiché au joueur.

## Fonctionnalités livrées

### Modèle et édition

- CPT privé `enigme_etape`, sans page publique autonome ni accès WordPress supplémentaire pour les organisateurs.
- Groupe ACF local déclaré en PHP par le plugin.
- Relation avec l’énigme, ordre par `menu_order`, nom interne, texte et image.
- Éditeur entièrement frontal dans le panneau de l’énigme : création différée jusqu’à la sauvegarde, modification,
  suppression et glisser-déposer.
- Validation : nom interne obligatoire et au moins un texte ou une image.
- Architecture figée après validation/démarrage ou dès qu’une progression existe.
- En mode figé, seuls le nom interne, le texte et l’image restent corrigibles.

### Parcours joueur

- Table `wp_enigme_etapes_progression` et service de progression linéaire.
- Colonne nullable `etape_id` dans `wp_enigme_tentatives`.
- Étapes futures non rendues dans le HTML.
- Réponse finale masquée jusqu’à la fin du parcours.
- Remise à zéro administrative étendue à la progression des étapes.
- Repositionnement après réussite vers l’étape révélée ou vers la réponse finale, après chargement des images et polices.

### Widgets disponibles

1. **Simple clic**
   - libellé personnalisable ;
   - réussite immédiate côté serveur ;
   - interaction enregistrée sans coût ni consommation de tentative.
2. **Réponse texte**
   - plusieurs réponses acceptées, une par ligne ;
   - casse sensible facultative ;
   - en mode insensible, casse et accents français sont ignorés ;
   - apostrophes et tirets typographiques sont normalisés, mais le tiret n’est pas assimilé à un espace ;
   - mauvaise réponse enregistrée comme `faux` et comptée dans la limite globale de l’énigme ;
   - variantes au format `réponse | message`, sans consommation de tentative.

## Décisions à conserver

- Toutes les étapes sont automatiques pour l’instant.
- Aucun coût en points pour une étape.
- La limite de tentatives est globale à l’énigme, pas propre à chaque étape.
- Seuls les résultats `faux` consomment la limite ; les réussites, clics et variantes ne la consomment pas.
- Une étape informative n’a pas de statut particulier : elle utilise le widget **Simple clic**.
- La configuration structurelle et les réponses sont immuables après validation ou progression.
- Une correction éditoriale à chaud du texte ou de l’image reste autorisée.
- Les solutions et étapes futures ne doivent jamais être envoyées au navigateur.
- Le futur délai de nouvel essai sera global à l’énigme et déclenché uniquement par une erreur.

## Points à reprendre dans le prochain fil

### Priorité 1 — stabilisation finale

- Retester le repositionnement après chargement d’images lentes, notamment après la dernière étape.
- Faire une passe mobile et accessibilité sur les widgets et les messages AJAX.
- Retester le comportement concurrent en conditions réelles de charge. Les soumissions passent désormais par une
  orchestration transactionnelle commune et une seconde soumission d’une étape terminée est refusée.
- Vérifier la protection des médias des étapes futures : ne pas se limiter à leur absence du HTML si leur URL reste
  devinable ou publiquement accessible.
- Ajouter des tests d’intégration des actions AJAX, au-delà des tests unitaires de services et d’enregistrement de hooks.

La sauvegarde éditoriale valide désormais le contenu et la configuration complète du widget avant toute écriture. Les
séquences mal formées sont refusées strictement, ce qui évite les créations fantômes et les modifications partielles
lorsqu’une configuration est invalide ou que la structure est figée.

### Priorité 2 — moteur partagé

Le code fonctionne pour les étapes, mais le moteur de widgets n’est pas encore réellement mutualisé avec la réponse
finale historique de l’énigme. La prochaine évolution structurante doit extraire un registre commun :

- définition PHP du widget et validation serveur ;
- adaptateur d’édition ;
- adaptateur joueur ;
- rendu en lecture seule ;
- configuration versionnée ;
- cible commune `enigme` ou `enigme_etape`.

Il faudra ensuite migrer progressivement la réponse texte finale de l’énigme vers ce moteur sans casser les données
existantes.

#### Première fondation livrée

Le registre PHP commun prend désormais en charge **Simple clic** et **Réponse texte**. Un adaptateur transforme les
champs ACF historiques des énigmes et des étapes en une configuration versionnée commune, identifiée par une cible
`enigme` ou `enigme_etape`. L’évaluation de la réponse finale automatique et celle des étapes passent par ce même
registre, sans modifier le stockage existant. Le rendu joueur des étapes consomme maintenant un modèle de vue commun
qui fournit l’action AJAX,
le nonce, le champ de saisie, le libellé et l’état de limite ; le JavaScript ne choisit plus l’action à partir d’une
classe CSS. Le sélecteur et les champs de l’éditeur d’étape sont également produits à partir de descriptions communes
de widgets. Le moteur dispose donc de ses adaptateurs serveur, joueur et édition avant l’ajout d’un nouveau widget.

### Priorité 3 — nouveaux widgets

- Pavé à huit directions.
- Clavier de couleurs.
- Molette ou combinaison de coffre-fort.
- Autres widgets Lockee, ajoutés comme adaptateurs indépendants plutôt que comme conditions dispersées.

### Priorité 4 — politique de nouvel essai

Remplacer à terme la limite quotidienne par une politique de délai :

- portée `(user_id, enigme_id)` ;
- déclenchée seulement par `faux` ;
- réponse serveur avec `retry_at` ;
- compteur visuel commun aux étapes et à la réponse finale ;
- clics et variantes toujours gratuits.

## Dette technique connue

- Les champs de réponses texte et de variantes sont actuellement stockés sous forme de texte multiligne. Une future
  configuration versionnée devra prévoir leur migration.
- Le format de variante `réponse | message` est volontairement simple ; il faudra un adaptateur éditorial plus robuste
  avant d’autoriser des messages complexes.
- Les tests d’intégration du service de soumission couvrent le commit, le rollback, le rejet d’une seconde soumission,
  le déblocage de la réponse finale et la séparation entre joueurs.
- Le rendu joueur est intégré au bloc de réponse historique ; l’extraction du moteur partagé devra clarifier cette
  responsabilité.
- La validité globale d’une énigme avant publication doit encore intégrer explicitement la complétude de toutes ses
  étapes et de leurs widgets.

## Tests de non-régression essentiels

1. Créer, modifier, réordonner et supprimer des étapes avant validation.
2. Vérifier le verrouillage structurel après validation et après une première progression.
3. Vérifier que les corrections de texte et d’image restent possibles après verrouillage.
4. Parcourir une alternance clic / texte / variante / texte jusqu’à la réponse finale.
5. Vérifier que seules les erreurs augmentent la limite globale.
6. Vérifier la séparation des progressions entre deux joueurs.
7. Vérifier la remise à zéro administrative complète.
8. Vérifier accents, casse, apostrophes et tirets.
9. Vérifier que les étapes futures et leurs solutions sont absentes du HTML et des réponses AJAX.
10. Vérifier la réponse finale automatique et manuelle après la dernière étape.

## Message conseillé pour ouvrir le prochain fil

```text
Je reprends le développement des étapes intermédiaires d’énigme après fusion du premier lot.

Commence par lire :
- docs/etapes-enigme.md
- docs/etapes-enigme-transition.md

État actuel :
- CPT privé `enigme_etape` et édition entièrement frontale ;
- parcours joueur linéaire persistant ;
- widgets Simple clic et Réponse texte opérationnels ;
- variantes texte sans consommation de tentative ;
- architecture verrouillée après validation/progression ;
- limite d’erreurs globale à l’énigme ;
- réponse finale débloquée après la dernière étape.

Je veux poursuivre sans remettre en cause les décisions consignées dans le document de transition.
Avant de coder, fais un audit rapide de l’état réel du dépôt et propose le prochain lot le plus cohérent parmi :
1. stabilisation et tests d’intégration du parcours existant ;
2. extraction d’un moteur de widgets réellement commun aux énigmes et aux étapes ;
3. premier widget à huit directions.

Signale clairement les risques de migration et les éventuelles contradictions avant toute modification.
À chaque livraison, donne-moi aussi la liste précise des tests manuels à effectuer.
```
