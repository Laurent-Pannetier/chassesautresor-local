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

function enigme_lister_resolveurs(int $enigme_id): array
{
    global $wpdb;
    $table   = $wpdb->prefix . 'enigme_tentatives';
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT user_id, MIN(date_tentative) AS resolution_date
             FROM $table
             WHERE enigme_id = %d AND resultat = 'bon'
             GROUP BY user_id
             ORDER BY resolution_date ASC",
            $enigme_id
        ),
        ARRAY_A
    );

    $solvers = [];
    foreach ($results as $row) {
        $attempts = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table
                 WHERE enigme_id = %d AND user_id = %d AND date_tentative <= %s",
                $enigme_id,
                $row['user_id'],
                $row['resolution_date']
            )
        );

        $solvers[] = [
            'user_id'   => (int) $row['user_id'],
            'username'  => get_the_author_meta('user_login', (int) $row['user_id']),
            'date'      => $row['resolution_date'],
            'tentatives' => (int) $attempts,
        ];
    }

    return $solvers;
}

function enigme_lister_participants(
    int $enigme_id,
    string $mode = 'automatique',
    int $limit = 25,
    int $offset = 0,
    string $orderby = 'date',
    string $order = 'ASC'
): array {
    global $wpdb;

    $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
    $order_by_sql = $orderby === 'tentatives' ? 'nb_tentatives' : 'date_engagement';

    $table_eng = $wpdb->prefix . 'engagements';
    $table_tent = $wpdb->prefix . 'enigme_tentatives';
    $table_stat = $wpdb->prefix . 'enigme_statuts_utilisateur';

    $sql = $wpdb->prepare(
        "SELECT e.user_id, u.user_login AS username, e.date_engagement,
                COALESCE(t.nb_tentatives, 0) AS nb_tentatives,
                IF(s.statut IN ('resolue','terminee'), 1, 0) AS trouve
         FROM {$table_eng} e
         JOIN {$wpdb->users} u ON e.user_id = u.ID
         LEFT JOIN (
             SELECT user_id, COUNT(*) AS nb_tentatives
             FROM {$table_tent}
             WHERE enigme_id = %d
             GROUP BY user_id
         ) t ON t.user_id = e.user_id
         LEFT JOIN {$table_stat} s ON s.user_id = e.user_id AND s.enigme_id = e.enigme_id
         WHERE e.enigme_id = %d
         ORDER BY {$order_by_sql} {$order}
         LIMIT %d OFFSET %d",
        $enigme_id,
        $enigme_id,
        $limit,
        $offset
    );

    return $wpdb->get_results($sql, ARRAY_A);
}

/**
 * AJAX handler retrieving statistics for a riddle.
 *
 * Expects the following POST parameters:
 * - `enigme_id` (int, required) The ID of the riddle to inspect.
 * - `periode` (string, optional) One of `jour`, `semaine`, `mois` or `total`.
 *   Defaults to `total`.
 *
 * Sends a JSON success response containing at least:
 * - `joueurs` (int) Number of engaged players for the selected period.
 * - `tentatives` (int) Number of attempts. Present only when the validation
 *   mode is not `aucune`.
 * - `solutions` (int) Number of correct answers. Present only when the
 *   validation mode is not `aucune`.
 * - `points` (int) Total points spent. Present only when the cost per attempt
 *   is greater than zero.
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

function ajax_enigme_lister_participants() {
    if (!is_user_logged_in()) {
        wp_send_json_error('non_connecte');
    }

    $enigme_id = isset($_POST['enigme_id']) ? (int) $_POST['enigme_id'] : 0;
    $page = max(1, (int) ($_POST['page'] ?? 1));
    $orderby = isset($_POST['orderby']) ? sanitize_text_field($_POST['orderby']) : 'date';
    $order = isset($_POST['order']) ? sanitize_text_field($_POST['order']) : 'ASC';

    if (!$enigme_id || get_post_type($enigme_id) !== 'enigme') {
        wp_send_json_error('post_invalide');
    }

    if (!utilisateur_peut_modifier_post($enigme_id)) {
        wp_send_json_error('acces_refuse');
    }

    $mode = get_field('enigme_mode_validation', $enigme_id) ?? 'aucune';
    $par_page = 25;
    $offset = ($page - 1) * $par_page;
    $participants = enigme_lister_participants($enigme_id, $mode, $par_page, $offset, $orderby, $order);
    $total = enigme_compter_joueurs_engages($enigme_id);
    $pages = (int) ceil($total / $par_page);

    ob_start();
    get_template_part('template-parts/enigme/partials/enigme-partial-participants', null, [
        'participants' => $participants,
        'page' => $page,
        'par_page' => $par_page,
        'total' => $total,
        'pages' => $pages,
        'mode_validation' => $mode,
        'orderby' => $orderby,
        'order' => $order,
    ]);
    $html = ob_get_clean();

    wp_send_json_success([
        'html' => $html,
        'total' => $total,
        'page' => $page,
        'pages' => $pages,
    ]);
}
add_action('wp_ajax_enigme_lister_participants', 'ajax_enigme_lister_participants');

