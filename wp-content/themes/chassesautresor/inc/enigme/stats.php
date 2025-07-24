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
