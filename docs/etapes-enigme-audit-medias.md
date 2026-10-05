# Audit des médias des étapes d’énigme

## État après sécurisation du rendu joueur

Le champ `etape_image` conserve un identifiant de pièce jointe WordPress. Le rendu joueur appelle directement
la route `/voir-image-enigme`, sans `srcset` pointant vers la médiathèque. Le contrôleur retrouve l’étape propriétaire,
vérifie l’accès à l’énigme puis limite l’image aux étapes déjà visibles dans la progression du joueur. Ses réponses sont
`private` (jamais de cache partagé CDN/proxy), avec un TTL navigateur court et une revalidation `304` via
`ETag` / `Last-Modified`, afin qu’un cache partagé ne puisse pas servir l’image à un autre joueur.

Sur LiteSpeed (Hostinger), le contrôleur tente ensuite un envoi via `X-LiteSpeed-Location` pour éviter
`readfile()` PHP, avec repli automatique sur `readfile` si le serveur n’est pas LiteSpeed. Les `.htaccess`
des dossiers `_enigmes/` utilisent `%{ORG_REQ_URI}` pour refuser l’accès direct tout en autorisant cet
envoi interne.

Les URLs du proxy sont signées (HMAC + expiry + uid) via `cta_voir_image_enigme_url()`. Les sources WebP
du `<picture>` passent aussi par ce proxy (plus d’URL directe `uploads/`).

## Stockage des images d’étapes

À l’enregistrement d’une étape, l’image est dupliquée si besoin puis déplacée vers
`uploads/_enigmes/enigme-{ID}/etapes/`, sous le même `.htaccess` que les visuels d’énigme. Une migration
one-shot (`cta_migrate_riddle_step_images`) traite les pièces jointes déjà présentes.

## Risque de migration

Les images d’étapes encore en médiathèque publique restent accessibles jusqu’à migration / resauvegarde
de l’étape. La duplication évite de casser un média partagé avec un contenu public.

## Stratégie recommandée

1. Refuser la sélection d’une image déjà rattachée à une cible publique ou partagée, ou la dupliquer lors de l’ajout.
2. Déplacer les médias propres aux étapes dans un stockage protégé indépendant du serveur web.
3. Servir chaque taille par un contrôleur qui vérifie l’accès à l’énigme et la position actuelle du joueur.
4. Bloquer ou filtrer en parallèle les réponses REST et les pages de pièce jointe pour ces médias.
5. Migrer les pièces jointes existantes par lots, avec une table de correspondance et une procédure de retour arrière.

La migration du stockage physique reste un lot distinct. Le rendu joueur passe désormais par le contrôleur protégé,
sans déplacer ni casser les pièces jointes existantes.
