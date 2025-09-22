# Procédure de test – profil organisateur

## Pré-requis
- Un compte WooCommerce existant.
- Accès à la page « Devenir organisateur » (`/devenir-organisateur/`).

## Cas 1 : profil utilisateur incomplet
1. Connectez-vous avec un compte qui n’a pas renseigné tous les champs obligatoires (prénom, nom, nom d’affichage, adresse e-mail…).
2. Rendez-vous sur « Devenir organisateur ».
3. Vérifiez que le message d’alerte liste les champs à compléter et que le bouton principal affiche « Compléter mon profil » avec un lien vers l’édition du compte WooCommerce.
4. Cliquez sur « Créer mon profil » (`/creer-mon-profil/`).
5. Confirmez que vous êtes redirigé vers l’édition du compte WooCommerce et qu’un message persistant rappelle les champs manquants.

## Cas 2 : profil utilisateur complet
1. Renseignez tous les champs requis du compte WooCommerce.
2. Retournez sur « Devenir organisateur ».
3. Vérifiez que le message d’alerte a disparu et que le CTA indique « Créer mon profil » avec le lien d’origine.
4. Accédez à `/creer-mon-profil/` et confirmez que la demande est envoyée (mail de confirmation, redirection vers « Devenir organisateur »…).

## Nettoyage
- Supprimez le message persistant via la mise à jour du profil si nécessaire.
