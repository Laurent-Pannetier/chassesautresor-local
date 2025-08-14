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
    $enigme_ids = recuperer_ids_enigmes_pour_chasse($chasse_id);
    if (!$enigme_ids) {
        return 0;
    }

    global $wpdb;
    $table        = $wpdb->prefix . 'enigme_tentatives';
    $placeholders = implode(',', array_fill(0, count($enigme_ids), '%d'));
    $where        = "enigme_id IN ({$placeholders})";
    $params       = $enigme_ids;

    if ($periode !== 'total') {
        [$debut, $fin] = enigme_stats_date_range($periode);
        if ($debut && $fin) {
            $where   .= ' AND date_tentative BETWEEN %s AND %s';
            $params[] = $debut;
            $params[] = $fin;
        }
    }

    $sql = $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where}", ...$params);

    return (int) $wpdb->get_var($sql);
}

/**
 * Sum collected points for all riddles of a hunt.
 */
function chasse_compter_points_collectes(int $chasse_id, string $periode = 'total'): int
{
    $enigme_ids = recuperer_ids_enigmes_pour_chasse($chasse_id);
    if (!$enigme_ids) {
        return 0;
    }

    global $wpdb;
    $table        = $wpdb->prefix . 'enigme_tentatives';
    $placeholders = implode(',', array_fill(0, count($enigme_ids), '%d'));
    $where        = "enigme_id IN ({$placeholders})";
    $params       = $enigme_ids;

    if ($periode !== 'total') {
        [$debut, $fin] = enigme_stats_date_range($periode);
        if ($debut && $fin) {
            $where   .= ' AND date_tentative BETWEEN %s AND %s';
            $params[] = $debut;
            $params[] = $fin;
        }
    }

    $sql = $wpdb->prepare("SELECT SUM(points_utilises) FROM {$table} WHERE {$where}", ...$params);
    $res = $wpdb->get_var($sql);

    return $res ? (int) $res : 0;
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
 * Calculate engagement rate for a hunt.
 */
function chasse_calculer_taux_engagement(int $chasse_id, string $periode = 'total'): float
{
    $participants  = chasse_compter_participants($chasse_id, $periode);
    $enigme_ids    = recuperer_ids_enigmes_pour_chasse($chasse_id);
    $total_enigmes = count($enigme_ids);
    if ($participants === 0 || $total_enigmes === 0) {
        return 0.0;
    }

    global $wpdb;
    $table        = $wpdb->prefix . 'engagements';
    $placeholders = implode(',', array_fill(0, count($enigme_ids), '%d'));
    $where        = "enigme_id IN ({$placeholders})";
    $params       = $enigme_ids;

    if ($periode !== 'total') {
        [$debut, $fin] = enigme_stats_date_range($periode);
        if ($debut && $fin) {
            $where   .= ' AND date_engagement BETWEEN %s AND %s';
            $params[] = $debut;
            $params[] = $fin;
        }
    }

    $sql = $wpdb->prepare(
        "SELECT SUM(cnt) FROM (SELECT COUNT(DISTINCT user_id) AS cnt FROM {$table} WHERE {$where} GROUP BY enigme_id) t",
        ...$params
    );
    $total = (int) $wpdb->get_var($sql);

    return (100 * $total) / ($participants * $total_enigmes);
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
    $orderby = in_array($orderby, ['username', 'inscription', 'participation', 'resolution'], true) ? $orderby : 'inscription';

    $sql = $wpdb->prepare(
        "SELECT e.user_id, u.user_login AS username, MIN(e.date_engagement) AS date_inscription"
        . " FROM {$table_eng} e"
        . " JOIN {$wpdb->users} u ON u.ID = e.user_id"
        . " WHERE e.chasse_id = %d AND e.enigme_id IS NULL"
        . " GROUP BY e.user_id",
        $chasse_id
    );

    $rows = $wpdb->get_results($sql, ARRAY_A);
    if (!$rows) {
        return [];
    }

    $user_ids     = array_map(static fn($row) => (int) $row['user_id'], $rows);
    $placeholders_users = implode(',', array_fill(0, count($user_ids), '%d'));

    $enigme_ids = recuperer_ids_enigmes_pour_chasse($chasse_id);
    $placeholders_enigmes = $enigme_ids ? implode(',', array_fill(0, count($enigme_ids), '%d')) : '';

    $engagement_rows = [];
    $resolution_rows = [];

    if ($enigme_ids) {
        $engagements_sql = $wpdb->prepare(
            "SELECT user_id, enigme_id FROM {$table_eng}"
            . " WHERE enigme_id IN ({$placeholders_enigmes})"
            . " AND user_id IN ({$placeholders_users})",
            array_merge($enigme_ids, $user_ids)
        );
        $engagement_rows = $wpdb->get_results($engagements_sql, ARRAY_A);

        $resolutions_sql = $wpdb->prepare(
            "SELECT user_id, enigme_id FROM {$table_stat}"
            . " WHERE statut IN ('resolue','terminee')"
            . " AND enigme_id IN ({$placeholders_enigmes})"
            . " AND user_id IN ({$placeholders_users})",
            array_merge($enigme_ids, $user_ids)
        );
        $resolution_rows = $wpdb->get_results($resolutions_sql, ARRAY_A);
    }

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

    usort($participants, static function ($a, $b) use ($orderby, $order) {
        switch ($orderby) {
            case 'username':
                $cmp = strcmp($a['username'], $b['username']);
                break;
            case 'participation':
                $cmp = $a['nb_engagees'] <=> $b['nb_engagees'];
                break;
            case 'resolution':
                $cmp = $a['nb_resolues'] <=> $b['nb_resolues'];
                break;
            case 'inscription':
            default:
                $cmp = strcmp($a['date_inscription'], $b['date_inscription']);
                break;
        }
        return $order === 'ASC' ? $cmp : -$cmp;
    });

    return array_slice($participants, $offset, $limit);
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

    $cache_key = chasse_stats_cache_key($chasse_id, $periode);
    $stats = wp_cache_get($cache_key, 'chasse_stats');
    if ($stats === false) {
        $stats = get_transient($cache_key);
    }

    if ($stats === false) {
        $stats = [
            'participants'   => chasse_compter_participants($chasse_id, $periode),
            'tentatives'     => chasse_compter_tentatives($chasse_id, $periode),
            'points'         => chasse_compter_points_collectes($chasse_id, $periode),
            'engagement_rate' => (int) round(chasse_calculer_taux_engagement($chasse_id, $periode)),
        ];
        $ttl = HOUR_IN_SECONDS;
        wp_cache_set($cache_key, $stats, 'chasse_stats', $ttl);
        set_transient($cache_key, $stats, $ttl);
    }

    wp_send_json_success($stats);
}
add_action('wp_ajax_chasse_recuperer_stats', 'ajax_chasse_recuperer_stats');

function chasse_stats_cache_key(int $chasse_id, string $periode): string
{
    return "chasse_stats_{$chasse_id}_{$periode}";
}

function chasse_clear_stats_cache(int $chasse_id): void
{
    foreach (['jour', 'semaine', 'mois', 'total'] as $p) {
        $key = chasse_stats_cache_key($chasse_id, $p);
        wp_cache_delete($key, 'chasse_stats');
        delete_transient($key);
    }
}

function chasse_invalidate_cache_from_enigme(int $enigme_id): void
{
    $chasse_id = recuperer_id_chasse_associee($enigme_id);
    if ($chasse_id) {
        chasse_clear_stats_cache((int) $chasse_id);
    }
}

add_action('chasse_engagement_created', 'chasse_clear_stats_cache');
add_action('enigme_engagement_created', 'chasse_invalidate_cache_from_enigme');
add_action('enigme_tentative_created', 'chasse_invalidate_cache_from_enigme');

/**
 * AJAX handler listing hunt participants.
 */
function ajax_chasse_lister_participants()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('non_connecte');
    }

    $chasse_id = isset($_POST['chasse_id']) ? (int) $_POST['chasse_id'] : 0;
    $page      = max(1, (int) ($_POST['page'] ?? 1));
    $order     = isset($_POST['order']) ? sanitize_text_field($_POST['order']) : 'ASC';
    $orderby   = isset($_POST['orderby']) ? sanitize_text_field($_POST['orderby']) : 'inscription';
    $order     = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
    $orderby   = in_array($orderby, ['inscription', 'username', 'participation', 'resolution'], true) ? $orderby : 'inscription';

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
