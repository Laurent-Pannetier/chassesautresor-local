<?php

// 🚀 Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/badge-functions.php';





//
// 🧩 GESTION DES STATUTS ET DE L’ACCESSIBILITÉ DES ÉNIGMES
// 🧠 GESTION DES STATUTS DES CHASSES
// 🧭 CALCUL DU STATUT D’UN ORGANISATEUR
// 🧑‍💻 GESTION DES STATUTS DES JOUEURS (UTILISATEUR ↔ ÉNIGME)
//

// ==================================================
// 🧩 GESTION DES STATUTS ET DE L’ACCESSIBILITÉ DES ÉNIGMES
// ==================================================
/**
 * 
 * 🔹 enigme_get_statut_utilisateur        → Retourne le statut actuel de l’utilisateur pour une énigme.
 * 🔹 enigme_mettre_a_jour_statut_utilisateur() → Met à jour le statut d'un joueur dans la table personnalisée.
 * 🔹 enigme_pre_requis_remplis            → Vérifie les prérequis d’une énigme pour un utilisateur.
 * 🔹 enigme_verifier_verrouillage         → Détaille le verrouillage éventuel d’une énigme.
 * 🔹 traiter_statut_enigme                → Détermine le comportement global à adopter (formulaire, redirection…).
 * 🔹 enigme_est_visible_pour              → Vérifie si un utilisateur peut voir une énigme.
 * 🔹 mettre_a_jour_statuts_enigmes_de_la_chasse → Recalcule tous les statuts des énigmes liées à une chasse.
 * 🔹 enigme_mettre_a_jour_etat_systeme    → Calcule ou met à jour le champ `enigme_cache_etat_systeme`.
 * 🔹 enigme_mettre_a_jour_etat_systeme_automatiquement → Hook ACF (enregistrement admin ou front).
 * 🔹 forcer_recalcul_statut_enigme        → Recalcul AJAX côté front (édition directe).
 * 🔹 enigme_get_etat_systeme              → Retourne l’état système de l’énigme (champ ACF cache).
 * 🔹 utilisateur_peut_engager_enigme      → Vérifie si un joueur peut engager une énigme.
 */

/**
 * Récupère le statut actuel de l’utilisateur pour une énigme.
 *
 * Statuts possibles :
 * - non_souscrite : le joueur n'a jamais interagi avec l’énigme
 * - en_cours      : le joueur a commencé l’énigme
 * - resolue       : le joueur a trouvé la bonne réponse
 * - terminee      : l’énigme a été finalisée dans un autre contexte
 * - echouee       : le joueur a tenté et échoué
 * - abandonnee    : le joueur a abandonné explicitement ou par expiration
 *
 * @param int $enigme_id ID de l’énigme.
 * @param int $user_id   ID de l’utilisateur.
 * @return string Statut actuel (par défaut : 'non_souscrite').
 */



/**
 * Met à jour le statut d'un joueur pour une énigme dans la table personnalisée `wp_enigme_statuts_utilisateur`.
 * La mise à jour ne s'effectue que si le nouveau statut est plus avancé que l'ancien.
 *
 * @param int $enigme_id ID de l'énigme.
 * @param int $user_id   ID de l'utilisateur.
 * @param string $nouveau_statut Nouveau statut ('non_commencee', 'en_cours', 'abandonnee', 'echouee', 'resolue', 'terminee').
 * @return bool True si la mise à jour est faite, false sinon.
 */




/**
 * 🔍 Vérifie si les prérequis d'une énigme sont remplis pour un utilisateur donné.
 *
 * @param int $enigme_id ID de l'énigme à vérifier.
 * @param int $user_id   ID de l'utilisateur.
 * @return bool True si tous les prérequis sont remplis ou inexistants, false sinon.
 */






/**
 * Analyse le statut d’une énigme pour un utilisateur et détermine le comportement à adopter :
 * - redirection
 * - affichage ou non du formulaire
 * - affichage d’un message explicatif
 *
 * @param int $enigme_id
 * @param int|null $user_id
 *
 * @return array{
 *   etat: string,
 *   rediriger: bool,
 *   url: string|null,
 *   afficher_formulaire: bool,
 *   afficher_message: bool,
 *   message_html: string
 * }
 */










// ==================================================
// 🧠 GESTION DES STATUTS DES CHASSES
// ==================================================
function cat_render_hunt_status_badge(array $badge, string $status, ?string $validation): array
{
    return chasse_preparer_badge_statut($status, $validation);
}
add_filter('chassesautresor_render_hunt_status_badge', 'cat_render_hunt_status_badge', 10, 3);

/**
 * 🔹 is_canevas_creation → Vérifie si l’utilisateur est en train de créer son espace organisateur (aucun CPT associé, et sur la page dédiée).
 */

/**
 * Détermine si l’utilisateur est dans le parcours de création d’un organisateur (canevas).
 *
 * @return bool
 */
function is_canevas_creation()
{
    if (!is_user_logged_in()) {
        return false;
    }

    if (!is_page('devenir-organisateur')) {
        return false;
    }

    return !get_organisateur_from_user(get_current_user_id());
}


// ==================================================
// 🧑‍💻 GESTION DES STATUTS DES JOUEURS (UTILISATEUR ↔ ÉNIGME)
// ==================================================
/**
 * 🔹 get_statut_utilisateur_enigme() → Retourne le statut du joueur pour une énigme donnée.
 * 🔹 est_enigme_resolue_par_utilisateur() → Booléen : l’utilisateur a-t-il résolu l’énigme ?
 */

/**
 * Retourne le statut du joueur pour une énigme donnée (avec cache interne).
 *
 * @param int $user_id
 * @param int $enigme_id
 * @return string|null Le statut ('non_commencee', 'resolue', etc.) ou null si absent
 */


/**
 * Vérifie si l'utilisateur a résolu une énigme.
 *
 * @param int $user_id
 * @param int $enigme_id
 * @return bool
 */
