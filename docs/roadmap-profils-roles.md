# Feuille de route — pages de profil par rôle

Document de cadrage. Aucune implémentation tant que ce document n’est pas validé
comme base de travail pour les tickets suivants.

Branche de référence fonctionnelle : `2026` (présence de
`SiteExperienceService` et de la page **Réglages > Expérience du site**).

---

## 1. Fil directeur

Transformer **Mon compte** en cockpit d’expérience selon le rôle :

| Rôle | Accueil sert à… |
|------|-----------------|
| Joueur | Suivre sa progression (individuelle, puis équipe), ses tentatives, ses infos d’équipe |
| Organisateur | Piloter la chasse : accès édition, cycle éditer/activer, statistiques |
| Administrateur | Superviser : édition, cycle de validation/activation, statistiques, outils |

Principes transverses :

- **Accueil** = activité et pilotage.
- **Réglages** = compte (profil WooCommerce) + commandes.
- Les interactions d’équipe se font hors site (Discord) ; le site expose l’info et la progression.
- Les fonctionnalités non prêtes passent par des **placeholders** homogènes.
- Le design reste dans le système **Orgy** (`mon-compte`, `dashboard-card`), pour pouvoir réordonner les blocs sans refonte.

---

## 2. Décisions validées

### 2.1 Mode du site (flag existant — à ne pas recréer)

Page admin : `options-general.php?page=chassesautresor-site-experience`
(**Réglages > Expérience du site**).

| Élément | Valeur |
|---------|--------|
| Option WP | `chassesautresor_site_experience` |
| Service | `ChassesAuTresor\Core\Site\SiteExperienceService` |
| Helpers | `cat_get_site_experience_settings()`, `cat_is_single_hunt_mode()`, `cat_is_demo_mode()`, `cat_get_primary_hunt_id()`, `cat_are_organizer_applications_open()` |

Modes (`mode`) :

| Valeur stockée | Libellé UI | Notes |
|----------------|------------|-------|
| `single_hunt` | Chasse unique | Présentation mono-chasse ; candidatures org fermées |
| `demo` | Démo ou prévisualisation | Variante mono-chasse d’accueil/édition ; `cat_is_single_hunt_mode()` retourne aussi `true` |
| `platform` | Plateforme | Catalogue / candidatures selon réglage |

Chasse principale : `primary_hunt_id` (sinon fallback documenté dans le service).

### 2.2 Navigation latérale

- L’entrée menu s’appelle **Profil** (elle regroupe profil WooCommerce + commandes).
- **Commandes** quitte le menu latéral et vit **dans Profil**.
- **Déconnexion** quitte le menu latéral ; elle n’existe plus que dans le menu compte de la **barre supérieure** (survol + comportement tactile).
- Le **nav CPT organisateur** (arbre organisateur → chasses → énigmes) **reste à gauche**, toujours visible pour org/admin concernés.
- **Points** : UI pilotée par `points_ui_enabled` (défaut off) — voir §2.4.

Menus cibles :

**Joueur**

1. Accueil
2. Tentatives
3. Profil

**Organisateur**

1. Accueil
2. Nav CPT (existant, à gauche)
3. Profil

**Administrateur** (hors besoin d’entrées admin dédiées)

1. Accueil
2. Nav CPT si pertinent
3. Profil

Supprimés du menu latéral admin :

- titre / label **Administration**
- **Organisateurs**
- **Statistiques**
- **Outils**

Leur contenu utile est absorbé par l’**Accueil** (ou rendu inactif en bas de page).

### 2.3 Switch Éditer / Activer (cycle de vie chasse)

Remplace l’affichage du CTA « demande de validation » et des messages associés
sur les pages d’entités (chasse, etc.). **Un seul chemin UI** : le switch sur
l’accueil profil (composant commun organisateur + administrateur).

Comportement selon le mode :

| Mode | Action org « Activer » | Côté admin |
|------|------------------------|------------|
| `demo` | Active **directement** (court-circuit du tunnel de permission) | Même liberté on/off |
| `single_hunt` | Envoie une **demande de validation** (moteur actuel) ; le switch côté org reste en état d’attente | Voit le switch en attente ; bascule sur **Activer** = validation / publication |
| `platform` | Conserve le moteur de demande de validation ; l’UI passe aussi par l’accueil profil (pas les pages entité) | Confirme via le même switch / file d’actions |

États UI à prévoir (bandeau ou état du switch) :

- Éditable
- Demande de validation en attente
- Correction demandée (réutiliser le flux admin existant de message de correction)
- Active / publiée

À supprimer des pages chasse (et messages compte liés) dès que le switch est en place :

- CTA « demande de validation »
- message du type « votre chasse est prête / éligible à une demande de validation »
- tout message redondant attaché à cet ancien parcours UI

Le moteur métier de validation (`HuntValidationService` / routes / statuts
`chasse_cache_statut_validation`) est **réutilisé**, pas réécrit.

### 2.4 Points — paramètre d’expérience du site

- Désactiver **partout** les entrées / surfaces Points pour l’instant (menu, blocs stats points sur l’accueil admin, etc.).
- Ajouter un réglage sur **Expérience du site**, par ex. clé
  `points_ui_enabled` (bool, défaut `0`) dans l’option
  `chassesautresor_site_experience`.
- Quand désactivé : pas d’entrée menu, pas de blocs points sur les dashboards ;
  le moteur de points peut rester en place.
- Les outils liés (taux, etc.) restent listés en zone **inactive** sur l’accueil admin.

### 2.5 Contenu Accueil par rôle

**Joueur**

- Progression individuelle
- Progression équipe → **placeholder** (équipes hors scope)
- Infos équipe + lien Discord éventuel → **placeholder** / emplacement prévu
- Easter eggs / récompenses → **placeholder**
- Pas d’interactions d’adhésion d’équipe sur le site dans ce lot

**Organisateur**

- Accès rapide panneaux d’édition (organisateur, chasse, énigmes)
- Statistiques détaillées de la chasse
- Switch Éditer / Activer
- En `demo` : Reset stats (plus de pastille « partout »)

**Administrateur**

- Accès rapide édition des entités
- Switch Éditer / Activer + indication qu’une demande org est en attente
- Statistiques (sans les 3 blocs points) : stats pertinentes **par énigme**
  (participants, étapes intermédiaires, trouvées, classement, etc.)
- Outils en bas de page :
  - **Actifs** : protection globale du site
  - **Inactifs** (grisés, non cliquables) : Points, taux, ACF — réactivables plus tard via le paramètre / Commandes

### 2.6 Suppressions / nettoyages

- Bloc du type « Vous ne participez à aucune chasse… idées pour démarrer… » :
  sans objet en expérience mono-chasse ; à retirer s’il apparaît encore.
- Pastilles / boutons **Reset stats** hors accueil profil en mode démo : à retirer.

### 2.7 Hors scope (reporté)

- Système d’équipes (créer, rejoindre, capitaine, quitter) — documenté pour plus tard ; placeholders seulement.
- Easter eggs / récompenses réels.
- Réactivation complète de l’économie de points.

Principes métiers équipes (pour implémentation future, non livrable ici) :

- La plupart des chasses se jouent en équipe.
- Stats publiques comparées entre équipes.
- Capitaine = créateur de l’équipe.
- Joueur : créer une équipe s’il n’en a pas, demander à rejoindre, ou rester seul.
- Capitaine accepte / refuse.
- Un membre ne peut plus créer d’équipe ; il peut quitter la sienne.

---

## 3. Design — primitives Orgy à stabiliser

Objectif : éléments et conteneurs homogènes, lisibles, modernes, faciles à
réordonner.

| Primitive | Rôle |
|-----------|------|
| `dashboard-section` | Une section = un job (titre + courte phrase) |
| `dashboard-card` / `carte-orgy` | Conteneur d’interaction ou de lecture (déjà en place) |
| `dashboard-stat` | Chiffre + libellé |
| `dashboard-placeholder` | Fonctionnalité à venir ou outil inactif (grisé, non cliquable) |
| `dashboard-switch` | Contrôle Éditer / Activer (+ états d’attente) |

Règles :

- Pas de troisième design system.
- Mobile-first.
- Pas de cartes purement décoratives.
- Zone outils inactifs distincte, en bas de l’accueil admin.

---

## 4. Découpage en lots / tickets

### Lot P0 — Cadrage (ce document)

- [x] Décisions modes / menus / switch / points / placeholders
- [x] Validation explicite de ce document avant code

### Lot A — Socle navigation & coque

Statut : livré (à recetter sur instance).

1. [x] Menus latéraux par rôle (Accueil, Tentatives joueur, Réglages ; logout retiré).
2. [x] Commandes intégrées à Réglages.
3. [x] Menu compte barre supérieure (hover + tactile) avec déconnexion.
4. [x] Shell Accueil + primitives Orgy (sections, cards, placeholders).
5. [x] Conservation du nav CPT à gauche.

### Lot B — Paramètre Points + nettoyage surfaces Points

Statut : livré (à recetter).

1. [x] Ajouter `points_ui_enabled` (défaut off) dans Expérience du site.
2. [x] Masquer soldes/modales/blocs stats points quand désactivé.
3. [x] Helper `cat_is_points_ui_enabled()`.

### Lot C — Accueil joueur + Tentatives

1. Progression individuelle (données engagements existantes).
2. Section Tentatives (extraire / adapter l’historique actuel de `content-chasses`).
3. Placeholders équipe / Discord / récompenses.
4. Suppression empty-state catalogue si encore présent.

### Lot D — Lifecycle switch + accueil org/admin

1. Composant switch commun + bandeau d’état.
2. Branchement `demo` (activation directe) vs `single_hunt` / `platform` (demande + confirmation admin).
3. Suppression CTA / messages validation sur pages entités.
4. File d’actions admin (validation / correction) sur Accueil (remplace la page Organisateurs pour ce flux).
5. Accès rapide édition entités.
6. Reset stats uniquement sur Accueil, selon `canResetStatistics` / mode démo.

### Lot E — Statistiques & outils

1. Stats par énigme (V1 : participants, réussites / trouvées, tentatives, classement simple ; étapes intermédiaires si données dispo).
2. Masquer les 3 blocs points.
3. Outils bas de page : protection active ; Points / taux / ACF inactifs.

### Lot F — Plus tard

- Équipes complètes.
- Récompenses.
- Réactivation Points via le paramètre d’expérience.

---

## 5. Critères d’acceptation transverses

- Aucune entrée Points visible si `points_ui_enabled` est off.
- Aucune déconnexion dans le menu latéral.
- Nav CPT organisateur toujours visible à gauche pour les profils concernés.
- En `demo`, org/admin activent/éditent sans demande de validation ; Reset stats seulement sur Accueil.
- En `single_hunt`, org « Activer » = demande ; admin confirme via le même contrôle.
- Plus de CTA / messages de demande de validation sur la fiche chasse.
- Placeholders présents pour équipe / récompenses côté joueur.
- Pas de régression du moteur de validation ni des permissions Members hors des court-circuits `demo` prévus.

---

## 6. Fichiers structurants existants (repères)

- Plugin : `wp-content/plugins/chassesautresor-core/src/Site/*`
- Compte : `wp-content/themes/chassesautresor/templates/myaccount/layout.php`
- Dashboards : `content-dashboard-*.php`, `template-parts/myaccount/*`
- Outils / reset : `content-outils.php`, `cta_reset_stats`
- Validation chasse : `HuntValidationService`, messages compte, CTA chasse
- Styles : `assets/scss/_mon-compte.scss`, charte Orgy
- Roadmap amont : `docs/roadmap-site-chasse-unique.md`

---

## 7. Prochaine étape

Valider ce document, puis ouvrir le **Lot A** (navigation & coque) en ticket
dédié, sans y mélanger le switch ni les stats.
