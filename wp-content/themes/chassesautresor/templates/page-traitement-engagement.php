<?php

/**
 * Template Name: Traitement Engagement
 * Route d’engagement – appelée uniquement via POST
 */

defined('ABSPATH') || exit;

$current_user_id = get_current_user_id();
if (!$current_user_id) {
    wp_redirect(home_url());
    exit;
}

$chasse_id = isset($_POST['chasse_id']) ? intval($_POST['chasse_id']) : 0;
$enigme_id = isset($_POST['enigme_id']) ? intval($_POST['enigme_id']) : 0;

// --------------------------------------------------
// 🎯 Traitement engagement chasse
// --------------------------------------------------
if ($chasse_id) {
    if (get_post_type($chasse_id) !== 'chasse') {
        wp_redirect(home_url());
        exit;
    }

    if (
        !isset($_POST['engager_chasse_nonce']) ||
        !wp_verify_nonce($_POST['engager_chasse_nonce'], 'engager_chasse_' . $chasse_id)
    ) {
        wp_die(__('Échec de vérification de sécurité', 'chassesautresor-com'));
    }

    require_once get_theme_file_path('inc/chasse-functions.php');

    $cout_points = (int) get_field('chasse_infos_cout_points', $chasse_id);

    if ($cout_points > 0 && !utilisateur_a_assez_de_points($current_user_id, $cout_points)) {
        wp_safe_redirect(
            add_query_arg('erreur', 'points_insuffisants', get_permalink($chasse_id))
        );
        exit;
    }

    enregistrer_engagement_chasse($current_user_id, $chasse_id);

    if ($cout_points > 0) {
        deduire_points_utilisateur($current_user_id, $cout_points);
    }

    wp_safe_redirect(get_permalink($chasse_id));
    exit;
}

// --------------------------------------------------
// 🧩 Traitement engagement énigme
// --------------------------------------------------

if (!$enigme_id || get_post_type($enigme_id) !== 'enigme') {
    wp_redirect(home_url());
    exit;
}

// Vérification du nonce
if (
    !isset($_POST['engager_enigme_nonce']) ||
    !wp_verify_nonce($_POST['engager_enigme_nonce'], 'engager_enigme_' . $enigme_id)
) {
    wp_die(__('Échec de vérification de sécurité', 'chassesautresor-com'));
}

// Chargement des fonctions critiques
require_once get_theme_file_path('inc/statut-functions.php');

// Vérifier si l’énigme est engageable
$etat_systeme = enigme_get_etat_systeme($enigme_id);
$statut_utilisateur = enigme_get_statut_utilisateur($enigme_id, $current_user_id);

$statuts_engageables = ['non_commencee', 'abandonnee', 'echouee'];

if ($etat_systeme !== 'accessible' || !in_array($statut_utilisateur, $statuts_engageables, true)) {
    wp_redirect(get_permalink($enigme_id)); // Redirection silencieuse
    exit;
}


// Déduction + enregistrement du statut
marquer_enigme_comme_engagee($current_user_id, $enigme_id);

// Redirection vers la page de l’énigme
wp_redirect(get_permalink($enigme_id));
exit;
