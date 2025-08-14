<?php
/**
 * Statistics helpers for hunts.
 */

defined('ABSPATH') || exit;

require_once __DIR__ . '/../enigme/stats.php';

/**
 * Count distinct participants engaged in a hunt.
 */
function chasse_compter_participants(int $chasse_id, string $periode = 'total'): int
{
    global $wpdb;
    $table = $wpdb->prefix . 'engagements';
    $where = 'chasse_id = %d AND enigme_id IS NULL';
    $params = [$chasse_id];
    if ($periode !== 'total') {
        [$debut, $fin] = enigme_stats_date_range($periode);
        if ($debut && $fin) {
            $where .= ' AND date_engagement BETWEEN %s AND %s';
            $params[] = $debut;
            $params[] = $fin;
        }
    }
    $sql = $wpdb->prepare("SELECT COUNT(DISTINCT user_id) FROM $table WHERE $where", ...$params);
    return (int) $wpdb->get_var($sql);
}

/**
 * Sum attempts for all riddles of a hunt.
 */
function chasse_compter_tentatives(int $chasse_id, string $periode = 'total'): int
{
    $total = 0;
    foreach (recuperer_ids_enigmes_pour_chasse($chasse_id) as $enigme_id) {
        $total += enigme_compter_tentatives($enigme_id, 'automatique', $periode);
    }
    return $total;
}

/**
 * Sum collected points for all riddles of a hunt.
 */
function chasse_compter_points_collectes(int $chasse_id, string $periode = 'total'): int
{
    $total = 0;
    foreach (recuperer_ids_enigmes_pour_chasse($chasse_id) as $enigme_id) {
        $total += enigme_compter_points_depenses($enigme_id, 'automatique', $periode);
    }
    return $total;
}

/**
 * Count total engagements (hunt and riddles) for a hunt.
 */
function chasse_compter_engagements(int $chasse_id): int
{
    global $wpdb;
    $table = $wpdb->prefix . 'engagements';
    $sql = $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE chasse_id = %d", $chasse_id);
    return (int) $wpdb->get_var($sql);
}

/**
 * List engagements for a hunt with pagination.
 *
 * @return array<int, array{username:string,date_chasse:?string,date_enigme:?string,nb_tentatives:int}>
 */
function chasse_lister_participants(int $chasse_id, int $limit, int $offset, string $orderby, string $order): array
{
    global $wpdb;
    $table_eng = $wpdb->prefix . 'engagements';
    $table_tent = $wpdb->prefix . 'enigme_tentatives';

    $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
    $orderby = $orderby === 'tentatives' ? 'tentatives' : 'date';
    $order_by_sql = $orderby === 'tentatives' ? 'nb_tentatives' : 'e.date_engagement';

    $sql = $wpdb->prepare(
        "SELECT e.user_id, e.enigme_id, e.date_engagement,
                COALESCE(t.nb_tentatives, 0) AS nb_tentatives
         FROM $table_eng e
         LEFT JOIN (
            SELECT user_id, enigme_id, COUNT(*) AS nb_tentatives
            FROM $table_tent
            GROUP BY user_id, enigme_id
         ) t ON t.user_id = e.user_id AND t.enigme_id = e.enigme_id
         WHERE e.chasse_id = %d
         ORDER BY $order_by_sql $order
         LIMIT %d OFFSET %d",
        $chasse_id,
        $limit,
        $offset
    );
    $rows = $wpdb->get_results($sql, ARRAY_A);
    $participants = [];
    foreach ($rows as $row) {
        $participants[] = [
            'username'      => get_the_author_meta('user_login', (int) $row['user_id']),
            'date_chasse'   => $row['enigme_id'] ? null : $row['date_engagement'],
            'date_enigme'   => $row['enigme_id'] ? $row['date_engagement'] : null,
            'nb_tentatives' => (int) $row['nb_tentatives'],
        ];
    }
    return $participants;
}

/**
 * AJAX handler retrieving hunt statistics.
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
    $periode = in_array($periode, ['jour', 'semaine', 'mois', 'total'], true) ? $periode : 'total';

    $stats = [
        'participants' => chasse_compter_participants($chasse_id, $periode),
        'tentatives'   => chasse_compter_tentatives($chasse_id, $periode),
        'points'       => chasse_compter_points_collectes($chasse_id, $periode),
    ];

    wp_send_json_success($stats);
}
add_action('wp_ajax_chasse_recuperer_stats', 'ajax_chasse_recuperer_stats');

/**
 * AJAX handler listing hunt participants.
 */
function ajax_chasse_lister_participants()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('non_connecte');
    }

    $chasse_id = isset($_POST['chasse_id']) ? (int) $_POST['chasse_id'] : 0;
    $page = max(1, (int) ($_POST['page'] ?? 1));
    $orderby = isset($_POST['orderby']) ? sanitize_text_field($_POST['orderby']) : 'date';
    $order = isset($_POST['order']) ? sanitize_text_field($_POST['order']) : 'ASC';

    if (!$chasse_id || get_post_type($chasse_id) !== 'chasse') {
        wp_send_json_error('post_invalide');
    }

    if (!utilisateur_est_organisateur_associe_a_chasse(get_current_user_id(), $chasse_id)) {
        wp_send_json_error('acces_refuse');
    }

    $par_page = 25;
    $offset = ($page - 1) * $par_page;
    $participants = chasse_lister_participants($chasse_id, $par_page, $offset, $orderby, $order);
    $total = chasse_compter_engagements($chasse_id);
    $pages = (int) ceil($total / $par_page);

    ob_start();
    get_template_part('template-parts/chasse/partials/chasse-partial-participants', null, [
        'participants' => $participants,
        'page' => $page,
        'par_page' => $par_page,
        'total' => $total,
        'pages' => $pages,
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
add_action('wp_ajax_chasse_lister_participants', 'ajax_chasse_lister_participants');
