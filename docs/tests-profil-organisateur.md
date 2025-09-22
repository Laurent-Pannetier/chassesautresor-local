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

## Cas 3 : demande de création expirée
1. Après avoir généré une demande (Cas 2), forcez son expiration en positionnant la métadonnée `organisateur_demande_date` à une valeur antérieure de plus de 48 heures :
   ```bash
   wp user meta update <USER_ID> organisateur_demande_date "2023-01-01 00:00:00"
   ```
2. Rechargez « Devenir organisateur » et vérifiez qu’un message informe de l’expiration et que le CTA redevient « Créer mon profil ».
3. Retournez sur `/creer-mon-profil/` et confirmez qu’un nouvel email est envoyé automatiquement et que le message persistant précise que l’ancienne demande avait expiré.

## Nettoyage
- Supprimez le message persistant via la mise à jour du profil si nécessaire.
