<?php
defined('ABSPATH') || exit;

function enigme_stats_date_range(string $periode): array {
    $tz = new DateTimeZone('Europe/Paris');
    $now = new DateTime('now', $tz);
    switch ($periode) {
        case 'jour':
            $start = (clone $now)->setTime(0,0);
            break;
        case 'semaine':
            $start = (clone $now)->modify('monday this week')->setTime(0,0);
            break;
        case 'mois':
            $start = (clone $now)->modify('first day of this month')->setTime(0,0);
            break;
        default:
            return [null, null];
    }
    return [$start->format('Y-m-d H:i:s'), $now->format('Y-m-d H:i:s')];
}

function enigme_compter_joueurs_engages(int $enigme_id, string $periode = 'total'): int {
    global $wpdb;
    $table = $wpdb->prefix . 'engagements';
    $where = 'enigme_id = %d';
    $params = [$enigme_id];
    if ($periode !== 'total') {
        list($debut, $fin) = enigme_stats_date_range($periode);
        if ($debut && $fin) {
            $where .= ' AND date_engagement BETWEEN %s AND %s';
            array_push($params, $debut, $fin);
        }
    }
    $sql = $wpdb->prepare("SELECT COUNT(DISTINCT user_id) FROM $table WHERE $where", ...$params);
    return (int) $wpdb->get_var($sql);
}

function enigme_compter_tentatives(int $enigme_id, string $mode = 'automatique', string $periode = 'total'): int {
    global $wpdb;
    $table = $wpdb->prefix . 'enigme_tentatives';
    $where = 'enigme_id = %d';
    $params = [$enigme_id];
    if ($periode !== 'total') {
        list($debut, $fin) = enigme_stats_date_range($periode);
        if ($debut && $fin) {
            $where .= ' AND date_tentative BETWEEN %s AND %s';
            array_push($params, $debut, $fin);
        }
    }
    $sql = $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE $where", ...$params);
    return (int) $wpdb->get_var($sql);
}

function enigme_compter_points_depenses(int $enigme_id, string $mode = 'automatique', string $periode = 'total'): int {
    global $wpdb;
    $table = $wpdb->prefix . 'enigme_tentatives';
    $where = 'enigme_id = %d';
    $params = [$enigme_id];
    if ($periode !== 'total') {
        list($debut, $fin) = enigme_stats_date_range($periode);
        if ($debut && $fin) {
            $where .= ' AND date_tentative BETWEEN %s AND %s';
            array_push($params, $debut, $fin);
        }
    }
    $sql = $wpdb->prepare("SELECT SUM(points_utilises) FROM $table WHERE $where", ...$params);
    $res = $wpdb->get_var($sql);
    return $res ? (int) $res : 0;
}

function enigme_compter_bonnes_solutions(int $enigme_id, string $mode = 'automatique', string $periode = 'total'): int {
    global $wpdb;
    $table = $wpdb->prefix . 'enigme_tentatives';
    $where = "enigme_id = %d AND resultat = 'bon'";
    $params = [$enigme_id];
    if ($periode !== 'total') {
        list($debut, $fin) = enigme_stats_date_range($periode);
        if ($debut && $fin) {
            $where .= ' AND date_tentative BETWEEN %s AND %s';
            array_push($params, $debut, $fin);
        }
    }
    $sql = $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE $where", ...$params);
    return (int) $wpdb->get_var($sql);
}

/**
 * AJAX handler to retrieve stats for an enigme.
 *
 * @return void
 */
function ajax_enigme_recuperer_stats()
{
    $enigme_id = isset($_POST['enigme_id']) ? (int) $_POST['enigme_id'] : 0;
    if ($enigme_id <= 0) {
        wp_send_json_error('missing_enigme', 400);
    }

    if (!utilisateur_peut_voir_panneau($enigme_id)) {
        wp_send_json_error('forbidden', 403);
    }

    $periode = isset($_POST['periode']) ? sanitize_text_field($_POST['periode']) : 'total';
    $periode = in_array($periode, ['jour', 'semaine', 'mois', 'total'], true) ? $periode : 'total';

    $mode = get_field('enigme_mode_validation', $enigme_id) ?? 'automatique';
    $cout = (int) get_field('enigme_tentative_cout_points', $enigme_id);

    $stats = [
        'joueurs' => enigme_compter_joueurs_engages($enigme_id, $periode),
    ];

    if ($mode !== 'aucune') {
        $stats['tentatives'] = enigme_compter_tentatives($enigme_id, $mode, $periode);
        $stats['solutions'] = enigme_compter_bonnes_solutions($enigme_id, $mode, $periode);
    }

    if ($cout > 0) {
        $stats['points'] = enigme_compter_points_depenses($enigme_id, $mode, $periode);
    }

    wp_send_json_success($stats);
}
add_action('wp_ajax_enigme_recuperer_stats', 'ajax_enigme_recuperer_stats');

