<?php
/**
 * Stats helpers for hunts.
 */

defined('ABSPATH') || exit;

require_once __DIR__ . '/../enigme/stats.php';

/**
 * Récupère les statistiques globales et détaillées d'une chasse.
 *
 * @param int    $chasse_id ID de la chasse.
 * @param string $periode   Période d'analyse: jour, semaine, mois ou total.
 *
 * @return array{ kpis: array{joueurs_engages:int, points_depenses:int, indices_debloques:int}, detail: array<int, array<string, mixed>> }
 */
function chasse_recuperer_stats(int $chasse_id, string $periode = 'total'): array
{
    $periode_valide = in_array($periode, ['jour', 'semaine', 'mois', 'total'], true) ? $periode : 'total';
    $cache_key = "chasse_stats_{$chasse_id}_{$periode_valide}";
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return $cached;
    }

    $enigmes_ids = recuperer_ids_enigmes_pour_chasse($chasse_id);
    $kpis = [
        'joueurs_engages' => 0,
        'points_depenses' => 0,
        'indices_debloques' => (int) get_field('total_indices_debloques_chasse', $chasse_id),
    ];
    $detail = [];

    foreach ($enigmes_ids as $enigme_id) {
        $joueurs = enigme_compter_joueurs_engages($enigme_id, $periode_valide);
        $tentatives = enigme_compter_tentatives($enigme_id, 'automatique', $periode_valide);
        $points = enigme_compter_points_depenses($enigme_id, 'automatique', $periode_valide);
        $resolus = enigme_compter_bonnes_solutions($enigme_id, 'automatique', $periode_valide);

        $kpis['joueurs_engages'] += $joueurs;
        $kpis['points_depenses'] += $points;

        $detail[] = [
            'id' => $enigme_id,
            'titre' => get_the_title($enigme_id),
            'joueurs' => $joueurs,
            'tentatives' => $tentatives,
            'points' => $points,
            'resolus' => $resolus,
            'edit_url' => get_edit_post_link($enigme_id),
        ];
    }

    $resultat = [
        'kpis' => $kpis,
        'detail' => $detail,
    ];
    set_transient($cache_key, $resultat, 5 * MINUTE_IN_SECONDS);
    return $resultat;
}

/**
 * AJAX handler to retrieve hunt stats.
 *
 * @return void
 */
function ajax_chasse_recuperer_stats()
{
    $chasse_id = isset($_POST['chasse_id']) ? (int) $_POST['chasse_id'] : 0;
    if ($chasse_id <= 0) {
        wp_send_json_error('missing_chasse', 400);
    }

    if (!utilisateur_est_organisateur_associe_a_chasse(get_current_user_id(), $chasse_id)) {
        wp_send_json_error('forbidden', 403);
    }

    $periode = isset($_POST['periode']) ? sanitize_text_field($_POST['periode']) : 'total';
    $stats = chasse_recuperer_stats($chasse_id, $periode);

    wp_send_json_success($stats);
}
add_action('wp_ajax_chasse_recuperer_stats', 'ajax_chasse_recuperer_stats');
