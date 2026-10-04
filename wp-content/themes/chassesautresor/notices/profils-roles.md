# Notice — pages de profil par rôle

Fiche courte. Le cadrage complet (décisions, modes, lots, critères) est dans
[`docs/roadmap-profils-roles.md`](../../../../docs/roadmap-profils-roles.md).

## À retenir pour l’implémentation

- S’appuyer sur `chassesautresor_site_experience` / `SiteExperienceService`
  (`single_hunt`, `demo`, `platform`) — ne pas inventer un second flag.
- Menus : Accueil + (Tentatives si joueur) + Réglages ; Commandes dans Réglages ;
  déconnexion uniquement via la barre supérieure ; nav CPT org conservée à gauche.
- Points UI pilotée par un réglage d’expérience du site (défaut off).
- Switch Éditer/Activer sur l’accueil profil : activation directe en `demo` ;
  demande de validation + confirmation admin en `single_hunt`.
- Design : primitives Orgy uniquement (`dashboard-section`, `dashboard-card`,
  `dashboard-stat`, `dashboard-placeholder`, `dashboard-switch`).

## Lot A (livré)

- Helpers `myaccount_get_sidebar_nav_items()`, `myaccount_user_is_player()`,
  `myaccount_render_dashboard_section()`, `myaccount_render_dashboard_placeholder()`.
- Endpoint WooCommerce `tentatives` ; Tentatives retirées de l’Accueil.
- Menu compte header : `assets/js/header-account-menu.js`.
- Shells Accueil joueur / org / admin avec placeholders.
