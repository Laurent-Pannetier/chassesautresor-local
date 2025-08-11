<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

function chasse_stats_date_range(string $periode): array
{
    $tz  = new DateTimeZone('Europe/Paris');
    $now = new DateTime('now', $tz);
    switch ($periode) {
        case 'jour':
            $start = (clone $now)->setTime(0, 0);
            break;
        case 'semaine':
            $start = (clone $now)->modify('monday this week')->setTime(0, 0);
            break;
        case 'mois':
            $start = (clone $now)->modify('first day of this month')->setTime(0, 0);
            break;
        default:
            return [null, null];
    }

    return [$start->format('Y-m-d H:i:s'), $now->format('Y-m-d H:i:s')];
}

function chasse_compter_joueurs_engages(int $chasse_id, string $periode = 'total'): int
{
    global $wpdb;
    if ($periode === 'total') {
        $total = get_post_meta($chasse_id, 'total_joueurs_souscription_chasse', true);
        return $total ? (int) $total : 0;
    }

    $table  = $wpdb->prefix . 'engagements';
    $where  = 'chasse_id = %d';
    $params = [$chasse_id];

    [$debut, $fin] = chasse_stats_date_range($periode);
    if ($debut && $fin) {
        $where .= ' AND date_engagement BETWEEN %s AND %s';
        $params[] = $debut;
        $params[] = $fin;
    }

    $sql = $wpdb->prepare("SELECT COUNT(DISTINCT user_id) FROM $table WHERE $where", ...$params);
    return (int) $wpdb->get_var($sql);
}

function chasse_compter_tentatives(int $chasse_id, string $periode = 'total'): int
{
    if ($periode === 'total') {
        $total = get_post_meta($chasse_id, 'total_tentatives_chasse_' . $chasse_id, true);
        return $total ? (int) $total : 0;
    }

    $enigmes = recuperer_ids_enigmes_pour_chasse($chasse_id);
    if (empty($enigmes)) {
        return 0;
    }

    global $wpdb;
    $table  = $wpdb->prefix . 'enigme_tentatives';
    $placeholders = implode(',', array_fill(0, count($enigmes), '%d'));
    $where  = "enigme_id IN ($placeholders)";
    $params = $enigmes;

    [$debut, $fin] = chasse_stats_date_range($periode);
    if ($debut && $fin) {
        $where .= ' AND date_tentative BETWEEN %s AND %s';
        $params[] = $debut;
        $params[] = $fin;
    }

    $sql = $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE $where", ...$params);
    return (int) $wpdb->get_var($sql);
}

function chasse_compter_points_depenses(int $chasse_id, string $periode = 'total'): int
{
    if ($periode === 'total') {
        $total = get_post_meta($chasse_id, 'total_points_depenses_chasse_' . $chasse_id, true);
        return $total ? (int) $total : 0;
    }

    $enigmes = recuperer_ids_enigmes_pour_chasse($chasse_id);
    if (empty($enigmes)) {
        return 0;
    }

    global $wpdb;
    $table  = $wpdb->prefix . 'enigme_tentatives';
    $placeholders = implode(',', array_fill(0, count($enigmes), '%d'));
    $where  = "enigme_id IN ($placeholders)";
    $params = $enigmes;

    [$debut, $fin] = chasse_stats_date_range($periode);
    if ($debut && $fin) {
        $where .= ' AND date_tentative BETWEEN %s AND %s';
        $params[] = $debut;
        $params[] = $fin;
    }

    $sql  = $wpdb->prepare("SELECT SUM(points_utilises) FROM $table WHERE $where", ...$params);
    $res = $wpdb->get_var($sql);
    return $res ? (int) $res : 0;
}

function chasse_compter_indices_debloques(int $chasse_id, string $periode = 'total'): int
{
    if ($periode !== 'total') {
        return 0;
    }

    $meta = get_post_meta($chasse_id, 'total_indices_debloques_chasse_' . $chasse_id, true);
    return $meta ? (int) $meta : 0;
}

function chasse_compter_joueurs_resolus(int $chasse_id, string $periode = 'total'): int
{
    if ($periode !== 'total') {
        return 0;
    }

    $progression_json = get_post_meta($chasse_id, 'progression_chasse', true);
    $progression      = $progression_json ? json_decode($progression_json, true) : [];
    if (!is_array($progression) || empty($progression)) {
        return 0;
    }

    $total_enigmes = count(recuperer_ids_enigmes_pour_chasse($chasse_id));
    if ($total_enigmes === 0) {
        return 0;
    }

    return count(array_filter($progression, function ($resolues) use ($total_enigmes) {
        return (int) $resolues >= $total_enigmes;
    }));
}
