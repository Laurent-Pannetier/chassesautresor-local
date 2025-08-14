<?php
/**
 * Statistics helpers for organizers.
 */

defined('ABSPATH') || exit;

require_once __DIR__ . '/../chasse/stats.php';

/**
 * Count distinct players engaged in all hunts of an organizer.
 */
function organisateur_compter_joueurs_uniques(int $organisateur_id): int
{
    $query = get_chasses_de_organisateur($organisateur_id);
    if (!$query || empty($query->posts)) {
        return 0;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'engagements';
    $ids   = [];

    foreach ($query->posts as $post) {
        $sql = $wpdb->prepare(
            "SELECT DISTINCT user_id FROM {$table} WHERE chasse_id = %d AND enigme_id IS NULL",
            $post->ID
        );
        $rows = $wpdb->get_col($sql);
        if ($rows) {
            $ids = array_merge($ids, array_map('intval', $rows));
        }
    }

    $ids = array_filter(array_unique($ids), static function ($id) {
        return $id > 0;
    });

    return count($ids);
}

/**
 * Sum collected points for all hunts of an organizer.
 */
function organisateur_compter_points_collectes(int $organisateur_id): int
{
    $query = get_chasses_de_organisateur($organisateur_id);
    if (!$query || empty($query->posts)) {
        return 0;
    }

    $total = 0;
    foreach ($query->posts as $post) {
        $total += chasse_compter_points_collectes($post->ID);
    }

    return $total;
}
