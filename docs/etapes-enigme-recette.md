# Recette de clôture — étapes intermédiaires

Cette recette clôt le périmètre **étapes intermédiaires et widgets actuellement livrés**. L’ajout de nouveaux widgets,
la mutualisation complète avec la réponse finale et le remplacement du quota quotidien par un délai `retry_at` sont des
chantiers séparés.

## Préconditions

- Utiliser une chasse en création contenant une énigme automatique et une énigme manuelle.
- Préparer deux comptes joueurs, un organisateur et un administrateur.
- Activer les journaux PHP et SQL sans afficher les erreurs aux joueurs.
- Vérifier que les tables de progression et de tentatives utilisent un moteur transactionnel.

## Édition organisateur

- [ ] Créer une étape pour chacun des widgets : clic, texte, directions, couleurs, nombres et coffre-fort.
- [ ] Vérifier les erreurs pour un nom absent, un contenu obligatoire absent et chaque configuration mal formée.
- [ ] Modifier le contenu et la configuration, puis vérifier le résultat côté joueur.
- [ ] Réordonner et supprimer des étapes avant le démarrage de la chasse.
- [ ] Vérifier qu’une étape incomplète rend l’énigme et la chasse incomplètes.
- [ ] Démarrer une progression, puis vérifier le blocage des changements structurels.
- [ ] Vérifier que les corrections de nom, texte et image restent possibles après verrouillage.

## Parcours joueur

- [ ] Vérifier que seules les étapes terminées et l’étape courante sont présentes dans le HTML et les réponses AJAX.
- [ ] Parcourir tous les widgets, dont un code numérique commençant par zéro.
- [ ] Vérifier qu’une variante texte affiche son aide sans consommer d’erreur.
- [ ] Vérifier qu’une erreur augmente le compteur global sans débiter de points.
- [ ] Vérifier que le clic, une variante et une réussite ne consomment pas le quota.
- [ ] Vérifier le déblocage immédiat de l’étape suivante sans rechargement.
- [ ] Vérifier le déblocage des réponses finales automatique et manuelle après la dernière étape.
- [ ] Vérifier l’indépendance complète des progressions des deux joueurs.
- [ ] Remettre les statistiques à zéro et rejouer le parcours complet.

## AJAX et concurrence

- [ ] Tester les deux actions avec nonce valide, absent, expiré et appartenant à une autre session.
- [ ] Tester un joueur anonyme, sans accès, un organisateur et un joueur autorisé.
- [ ] Soumettre une étape future, terminée, étrangère et configurée avec un autre widget.
- [ ] Envoyer deux soumissions simultanées : une seule tentative et une seule progression doivent être persistées.
- [ ] Forcer un échec d’insertion et vérifier le rollback ainsi que la libération du verrou MySQL.
- [ ] Vérifier les champs JSON `resultat`, `message`, `compteur`, `current_step_id`,
  `final_answer_unlocked` et `response_html`.

## Affichage, mobile et accessibilité

- [ ] Tester une image lente, une image en erreur et le passage de la dernière étape à la réponse finale.
- [ ] Tester Chrome Android et Safari iOS en orientation portrait et paysage.
- [ ] Tester au clavier, à 200 % et 400 % de zoom, avec réduction des animations et contraste renforcé.
- [ ] Vérifier les annonces avec NVDA/Firefox et VoiceOver/Safari.
- [ ] Vérifier l’annonce des erreurs AJAX, des séquences saisies et de la réponse finale débloquée.

## Médias et décision de sécurité

- [ ] Vérifier qu’une étape future est refusée par `/voir-image-enigme` et qu’une étape visible est autorisée.
- [ ] Vérifier l’absence d’URL directe et de `srcset` de médiathèque dans le HTML joueur.
- [ ] Décider si les anciennes URL physiques constituent un risque bloquant.
- [ ] Si oui, réaliser la migration vers un stockage non public avant clôture.
- [ ] Si non, enregistrer explicitement l’acceptation temporaire du risque et le ticket de migration.

## Critères de clôture

La thématique peut être clôturée lorsque les suites automatisées passent, que tous les points non différés de cette
recette sont validés et que la décision sur le stockage physique des médias est enregistrée. Les résultats doivent
indiquer la version testée, l’environnement, le navigateur, le compte utilisé et la date de recette.
