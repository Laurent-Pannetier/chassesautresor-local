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
 * @return array<int, array{username:string,date_chasse:?string,date_enigme:?string,enigme_titre:string}>
 */
function chasse_lister_participants(int $chasse_id, int $limit, int $offset, string $orderby, string $order): array
{
    global $wpdb;
    $table = $wpdb->prefix . 'engagements';

    $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

    $sql = $wpdb->prepare(
        "SELECT user_id, enigme_id, date_engagement
         FROM $table
         WHERE chasse_id = %d
         ORDER BY date_engagement $order
         LIMIT %d OFFSET %d",
        $chasse_id,
        $limit,
        $offset
    );

    $rows = $wpdb->get_results($sql, ARRAY_A);
    $participants = [];
    foreach ($rows as $row) {
        $participants[] = [
            'username'    => get_the_author_meta('user_login', (int) $row['user_id']),
            'date_chasse' => $row['enigme_id'] ? null : $row['date_engagement'],
            'date_enigme' => $row['enigme_id'] ? $row['date_engagement'] : null,
            'enigme_titre' => $row['enigme_id'] ? get_the_title((int) $row['enigme_id']) : '',
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
    $order = isset($_POST['order']) ? sanitize_text_field($_POST['order']) : 'ASC';

    if (!$chasse_id || get_post_type($chasse_id) !== 'chasse') {
        wp_send_json_error('post_invalide');
    }

    if (!utilisateur_est_organisateur_associe_a_chasse(get_current_user_id(), $chasse_id)) {
        wp_send_json_error('acces_refuse');
    }

    $par_page = 25;
    $offset = ($page - 1) * $par_page;
    $participants = chasse_lister_participants($chasse_id, $par_page, $offset, 'date', $order);
    $total = chasse_compter_engagements($chasse_id);
    $pages = (int) ceil($total / $par_page);

    ob_start();
    get_template_part('template-parts/chasse/partials/chasse-partial-participants', null, [
        'participants' => $participants,
        'page' => $page,
        'par_page' => $par_page,
        'total' => $total,
        'pages' => $pages,
        'orderby' => 'date',
        'order' => $order,
        'chasse_titre' => get_the_title($chasse_id),
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
