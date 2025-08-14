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
 * List hunt participants with aggregated statistics.
 *
 * Each participant includes registration date, engaged riddles and counts of
 * engaged and solved riddles.
 *
 * @return array<int, array{
 *     username:string,
 *     date_inscription:string,
 *     enigmes:array<int, array{id:int,title:string,url:string}>,
 *     nb_engagees:int,
 *     nb_resolues:int,
 * }>
 */
function chasse_lister_participants(int $chasse_id, int $limit, int $offset, string $orderby, string $order): array
{
    global $wpdb;
    $table_eng  = $wpdb->prefix . 'engagements';
    $table_stat = $wpdb->prefix . 'enigme_statuts_utilisateur';

    $order   = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
    $orderby = $orderby === 'username' ? 'u.user_login' : 'date_inscription';

    $sql = $wpdb->prepare(
        "SELECT e.user_id, u.user_login AS username, MIN(e.date_engagement) AS date_inscription"
        . " FROM {$table_eng} e"
        . " JOIN {$wpdb->users} u ON u.ID = e.user_id"
        . " WHERE e.chasse_id = %d AND e.enigme_id IS NULL"
        . " GROUP BY e.user_id"
        . " ORDER BY {$orderby} {$order}"
        . " LIMIT %d OFFSET %d",
        $chasse_id,
        $limit,
        $offset
    );

    $rows = $wpdb->get_results($sql, ARRAY_A);
    if (!$rows) {
        return [];
    }

    $user_ids     = array_map(static fn($row) => (int) $row['user_id'], $rows);
    $placeholders = implode(',', array_fill(0, count($user_ids), '%d'));

    $engagements_sql = $wpdb->prepare(
        "SELECT user_id, enigme_id FROM {$table_eng}"
        . " WHERE chasse_id = %d AND enigme_id IS NOT NULL"
        . " AND user_id IN ({$placeholders})",
        array_merge([$chasse_id], $user_ids)
    );
    $engagement_rows = $wpdb->get_results($engagements_sql, ARRAY_A);

    $enigmes_by_user = [];
    foreach ($engagement_rows as $row) {
        $uid = (int) $row['user_id'];
        $eid = (int) $row['enigme_id'];
        if (!isset($enigmes_by_user[$uid])) {
            $enigmes_by_user[$uid] = [];
        }
        if (!in_array($eid, $enigmes_by_user[$uid], true)) {
            $enigmes_by_user[$uid][] = $eid;
        }
    }

    $resolutions_sql = $wpdb->prepare(
        "SELECT s.user_id, s.enigme_id"
        . " FROM {$table_stat} s"
        . " JOIN {$table_eng} e ON e.user_id = s.user_id AND e.enigme_id = s.enigme_id"
        . " WHERE e.chasse_id = %d AND s.statut IN ('resolue','terminee')"
        . " AND s.user_id IN ({$placeholders})",
        array_merge([$chasse_id], $user_ids)
    );
    $resolution_rows = $wpdb->get_results($resolutions_sql, ARRAY_A);

    $resolues_by_user = [];
    foreach ($resolution_rows as $row) {
        $uid = (int) $row['user_id'];
        $eid = (int) $row['enigme_id'];
        if (!isset($resolues_by_user[$uid])) {
            $resolues_by_user[$uid] = [];
        }
        if (!in_array($eid, $resolues_by_user[$uid], true)) {
            $resolues_by_user[$uid][] = $eid;
        }
    }

    $participants = [];
    foreach ($rows as $row) {
        $uid         = (int) $row['user_id'];
        $engaged_ids = $enigmes_by_user[$uid] ?? [];
        $enigmes     = [];
        foreach ($engaged_ids as $eid) {
            $enigmes[] = [
                'id'    => $eid,
                'title' => get_the_title($eid),
                'url'   => get_permalink($eid),
            ];
        }
        $resolus_ids = $resolues_by_user[$uid] ?? [];
        $participants[] = [
            'username'         => $row['username'],
            'date_inscription' => $row['date_inscription'],
            'enigmes'          => $enigmes,
            'nb_engagees'      => count($engaged_ids),
            'nb_resolues'      => count(array_intersect($engaged_ids, $resolus_ids)),
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
    $orderby = isset($_POST['orderby']) ? sanitize_text_field($_POST['orderby']) : 'inscription';

    if (!$chasse_id || get_post_type($chasse_id) !== 'chasse') {
        wp_send_json_error('post_invalide');
    }

    if (!utilisateur_est_organisateur_associe_a_chasse(get_current_user_id(), $chasse_id)) {
        wp_send_json_error('acces_refuse');
    }

    $par_page = 25;
    $offset = ($page - 1) * $par_page;
    $participants = chasse_lister_participants($chasse_id, $par_page, $offset, $orderby, $order);
    $total = chasse_compter_participants($chasse_id);
    $pages = (int) ceil($total / $par_page);

    ob_start();
    get_template_part('template-parts/chasse/partials/chasse-partial-participants', null, [
        'participants' => $participants,
        'page' => $page,
        'par_page' => $par_page,
        'total' => $total,
        'pages' => $pages,
        'chasse_titre' => get_the_title($chasse_id),
        'total_enigmes' => count(recuperer_ids_enigmes_pour_chasse($chasse_id)),
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
