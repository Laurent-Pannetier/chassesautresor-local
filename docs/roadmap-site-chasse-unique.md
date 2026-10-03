# Feuille de route — passage à un site consacré à une chasse unique

## Décisions validées

- Le site public présente une chasse unique plutôt qu'un catalogue.
- Le moteur conserve toutes ses capacités multi-chasses et organisateur.
- Il n'existe plus de page publique de l'organisateur.
- Les nouvelles candidatures organisateur sont fermées.
- L'organisateur existant et les administrateurs conservent leurs outils de gestion.
- L'accueil présente l'aventure ; la fiche chasse reste l'espace opérationnel des joueurs.

## Lot 1 — Socle de configuration et fermeture des entrées organisateur

Statut : réalisé.

- Ajouter un mode configurable `chasse unique` / `plateforme`.
- Permettre de choisir la chasse principale sans identifiant codé en dur.
- Proposer un mode `démo ou prévisualisation` capable d'afficher sur l'accueil une chasse encore en édition.
- Utiliser temporairement la dernière chasse publiée et validée si aucun choix explicite n'a été enregistré.
- Fermer les candidatures organisateur en mode chasse unique.
- Rediriger les anciennes pages de candidature et de confirmation vers la chasse principale.
- Rediriger les visiteurs d'une fiche organisateur publique, tout en laissant son propriétaire et les administrateurs
  accéder aux outils existants.
- Faire utiliser la chasse principale au hero existant.

Après déploiement, sélectionner la chasse dans **Réglages > Expérience du site**.

Le mode démo rend uniquement la présentation d'accueil accessible et l'identifie clairement comme un aperçu. Il ne
publie pas le contenu WordPress et ne contourne pas les permissions des énigmes ou des fichiers protégés.

## Lot 2 — Nouvel accueil éditorial mono-chasse

Statut : implémentation réalisée ; recette visuelle à effectuer sur une instance reliée à la base de données.

- Remplacer le catalogue, la recherche et les filtres par une landing page dédiée à la chasse principale.
- Construire les sections : promesse, informations essentielles, univers, fonctionnement et aperçu des énigmes.
- Prévoir un emplacement éditorial facultatif pouvant notamment accueillir une FAQ.
- Réutiliser les données de la chasse sans dupliquer la logique métier dans le thème.
- Prévoir des états éditoriaux propres avant le lancement, pendant la chasse et après sa clôture.
- Réaliser une recette visuelle mobile et ordinateur.

Le contenu saisi dans l'éditeur de la page d'accueil est conservé comme section éditoriale facultative. Le catalogue
historique reste disponible en mode `plateforme`, ce qui garantit la réversibilité de la présentation.

## Lot 3 — Navigation et CTA contextuels

Statut : réalisé ; la composition finale du menu reste à vérifier avec les menus WordPress de production.

- Réduire le menu public à la chasse, aux énigmes, au règlement, à la FAQ et au compte.
- Supprimer les liens publics vers les organisateurs et les parcours de candidature.
- Adapter le CTA principal à l'état du joueur : découvrir, participer, commencer, reprendre ou revoir.
- Donner à l'organisateur connecté un accès explicite à la gestion, sans l'exposer au public.

En mode chasse unique ou démo, les entrées de chasse et d'organisateur devenues obsolètes sont retirées des menus
WordPress. Les liens canoniques **La chasse** et **Les énigmes** sont ajoutés aux menus principal et mobile. Un lien
**Gérer la chasse** est réservé à l'organisateur associé et aux administrateurs. Les CTA du hero et du corps de
l'accueil partagent désormais la même décision de présentation.

## Lot 4 — Consolidation du parcours joueur

Statut : réalisé ; recette des états de compte à effectuer sur l'instance de production ou de préproduction.

- Clarifier la transition entre présentation, inscription, engagement et première énigme.
- Mettre en avant la progression et la reprise de partie.
- Vérifier les états verrouillés, les prérequis, les indices, les solutions et la fin de chasse.
- Vérifier l'accessibilité clavier, la structure des titres, les intitulés d'action et les annonces dynamiques.

L'accueil distingue désormais les visiteurs, les joueurs connectés non engagés, les participants et les gestionnaires.
Il propose la création de compte lorsqu'elle est autorisée, réutilise le CTA de participation et affiche aux joueurs
engagés une progression native accessible ainsi qu'un accès direct à la reprise du parcours.

## Lot 5 — Référencement, mesure et nettoyage

- Réécrire les titres, descriptions sociales et données structurées autour de la chasse principale.
- Mesurer les étapes du tunnel : découverte, inscription, engagement, première ouverture et première résolution.
- Retirer les assets de catalogue devenus inutilisés de la présentation, sans supprimer les capacités du plugin.
- Actualiser la documentation d'architecture et effectuer une recette de non-régression du parcours organisateur privé.

## Transmission entre fils de discussion

Un fil par lot est recommandé afin de conserver un contexte lisible. Chaque fin de lot doit ajouter ou actualiser un
compte rendu versionné comprenant :

1. l'objectif et les décisions fonctionnelles ;
2. le périmètre réalisé et les fichiers structurants ;
3. les migrations ou réglages manuels à appliquer ;
4. les tests exécutés et leurs résultats ;
5. les captures ou points de recette visuelle ;
6. les choix reportés, risques et dettes connues ;
7. le prochain lot proposé et ses critères d'acceptation ;
8. le commit et la pull request de référence.

Le nouveau fil doit commencer avec ce document, le compte rendu du lot précédent et les éventuelles décisions prises
depuis. Le dépôt reste ainsi la source de vérité, plutôt qu'un résumé présent uniquement dans la conversation.
