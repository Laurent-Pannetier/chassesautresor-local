# Audit des médias des étapes d’énigme

## État après sécurisation du rendu joueur

Le champ `etape_image` conserve un identifiant de pièce jointe WordPress. Le rendu joueur appelle directement
la route `/voir-image-enigme`, sans `srcset` pointant vers la médiathèque. Le contrôleur retrouve l’étape propriétaire,
vérifie l’accès à l’énigme puis limite l’image aux étapes déjà visibles dans la progression du joueur. Ses réponses sont
`private` (jamais de cache partagé CDN/proxy), avec un TTL navigateur court et une revalidation `304` via
`ETag` / `Last-Modified`, afin qu’un cache partagé ne puisse pas servir l’image à un autre joueur.

Cette sécurisation empêche la page joueur de divulguer directement l’URL d’une étape future, mais elle ne protège pas
encore le fichier physique si son ancienne URL est déjà connue. L’original et les tailles dérivées peuvent rester servis
par le serveur web. Une page de pièce jointe ou l’API REST peut également exposer ses métadonnées selon la configuration
du site et les extensions actives.

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

La migration du stockage physique reste un lot distinct. Le rendu joueur passe désormais par le contrôleur protégé,
sans déplacer ni casser les pièces jointes existantes.
