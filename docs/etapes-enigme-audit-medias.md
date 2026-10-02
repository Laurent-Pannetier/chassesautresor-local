# Audit des médias des étapes d’énigme

## Constat du dépôt

Le champ `etape_image` conserve un identifiant de pièce jointe WordPress. Le rendu joueur appelle directement
`wp_get_attachment_image()`. Les URL générées sont donc celles de la médiathèque standard et ne passent pas par le
contrôleur d’images protégées des énigmes.

L’absence d’une étape future dans le HTML empêche la découverte directe de son image depuis la page joueur, mais elle
ne protège pas le fichier si son URL est déjà connue. L’original et les tailles dérivées peuvent rester servis par le
serveur web. Une page de pièce jointe ou l’API REST peut également exposer ses métadonnées selon la configuration du
site et les extensions actives.

## Risque de migration

Le mécanisme existant de protection des images d’énigme repose sur un répertoire dédié et un fichier `.htaccess`.
L’appliquer automatiquement aux étapes sans migration contrôlée risquerait de casser les tailles dérivées, les images
partagées entre plusieurs contenus et les installations qui n’utilisent pas Apache.

## Stratégie recommandée

1. Refuser la sélection d’une image déjà rattachée à une cible publique ou partagée, ou la dupliquer lors de l’ajout.
2. Déplacer les médias propres aux étapes dans un stockage protégé indépendant du serveur web.
3. Servir chaque taille par un contrôleur qui vérifie l’accès à l’énigme et la position actuelle du joueur.
4. Bloquer ou filtrer en parallèle les réponses REST et les pages de pièce jointe pour ces médias.
5. Migrer les pièces jointes existantes par lots, avec une table de correspondance et une procédure de retour arrière.

La protection complète doit faire l’objet d’un lot distinct. Le présent lot ne modifie aucune URL existante.
