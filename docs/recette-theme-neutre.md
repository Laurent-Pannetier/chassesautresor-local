# Recette de présentation avec un thème neutre

Cette recette couvre le premier socle portable des parcours publics. Elle doit être rejouée après chaque extension
des fallbacks Core, avec un thème WordPress standard actif et le plugin `chassesautresor-core` activé.

## Contrat de surcharge

Les templates internes résident dans `chassesautresor-core/templates`. Un thème tiers peut surcharger un template en
reproduisant son chemin sous `chassesautresor-core/`, par exemple :
`wp-content/themes/mon-theme/chassesautresor-core/public/single-chasse.php`.

Chaque template public reçoit un tableau `$viewModel` préparé par `PublicViewModelFactory`. Les clés communes sont
`id`, `post_type`, `title`, `content`, `image`, `permalink` et `can_edit`. Les relations propres au type de contenu
sont exposées sous `organizer_id`, `riddles`, `hunt_id`, `visible` ou `hunts`.

## Procédure reproductible

1. Activer un thème WordPress standard qui ne fournit aucun template `chasse`, `enigme` ou `organisateur`.
2. Ouvrir une chasse publiée et vérifier son titre, son contenu, son organisateur et ses liens vers les énigmes.
3. Ouvrir une énigme avec un joueur autorisé, envoyer une réponse et vérifier le retour accessible du contrôleur AJAX.
4. Ouvrir la même énigme déconnecté et vérifier la proposition de connexion.
5. Ouvrir un organisateur et vérifier son contenu ainsi que la liste de ses chasses.
6. Vérifier avec un compte organisateur que les liens d’édition sont présents uniquement sur ses contenus.
7. Rejouer `vendor/bin/phpunit -c tests/phpunit.xml` : les tests de résolution, surcharge, fallback, assets et frontière
   plugin/thème constituent la preuve automatisée de ce socle.

Les écrans de compte avancés, les formulaires frontaux complets d’édition et la recette WooCommerce restent hors du
périmètre de ce premier socle et doivent rester signalés comme dépendances tant que leurs fallbacks ne sont pas livrés.
