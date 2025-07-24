<?php
defined('ABSPATH') || exit;

/**
 * Compte le nombre de joueurs ayant engagé une énigme.
 *
 * @param int         $id     ID de l'énigme.
 * @param string|null $debut  Date de début au format 'Y-m-d H:i:s'.
 * @param string|null $fin    Date de fin au format 'Y-m-d H:i:s'.
 * @return int Nombre de joueurs distincts.
 */
function compter_joueurs_engages_enigme(int $id, ?string $debut = null, ?string $fin = null): int
{
    if (!$id || get_post_type($id) !== 'enigme') {
        return 0;
    }

    global $wpdb;
    $table  = $wpdb->prefix . 'engagements';
    $sql    = "SELECT COUNT(DISTINCT user_id) FROM $table WHERE enigme_id = %d";
    $params = [$id];

    if ($debut && $fin) {
        $sql .= " AND date_engagement BETWEEN %s AND %s";
        $params[] = $debut;
        $params[] = $fin;
    } elseif ($debut) {
        $sql .= " AND date_engagement >= %s";
        $params[] = $debut;
    } elseif ($fin) {
        $sql .= " AND date_engagement <= %s";
        $params[] = $fin;
    }

    return (int) $wpdb->get_var($wpdb->prepare($sql, ...$params));
}

/**
 * Compte le nombre de tentatives pour une énigme durant une période.
 *
 * @param int         $id     ID de l'énigme.
 * @param string|null $debut  Date de début au format 'Y-m-d H:i:s'.
 * @param string|null $fin    Date de fin au format 'Y-m-d H:i:s'.
 * @return int Nombre de tentatives.
 */
function compter_tentatives_enigme_periode(int $id, ?string $debut = null, ?string $fin = null): int
{
    if (!$id || get_post_type($id) !== 'enigme') {
        return 0;
    }

    global $wpdb;
    $table  = $wpdb->prefix . 'enigme_tentatives';
    $sql    = "SELECT COUNT(*) FROM $table WHERE enigme_id = %d";
    $params = [$id];

    if ($debut && $fin) {
        $sql .= " AND date_tentative BETWEEN %s AND %s";
        $params[] = $debut;
        $params[] = $fin;
    } elseif ($debut) {
        $sql .= " AND date_tentative >= %s";
        $params[] = $debut;
    } elseif ($fin) {
        $sql .= " AND date_tentative <= %s";
        $params[] = $fin;
    }

    return (int) $wpdb->get_var($wpdb->prepare($sql, ...$params));
}

/**
 * Calcule la somme des points dépensés pour une énigme durant une période.
 *
 * @param int         $id     ID de l'énigme.
 * @param string|null $debut  Date de début au format 'Y-m-d H:i:s'.
 * @param string|null $fin    Date de fin au format 'Y-m-d H:i:s'.
 * @return int Somme des points dépensés.
 */
function somme_points_depenses_enigme_periode(int $id, ?string $debut = null, ?string $fin = null): int
{
    if (!$id || get_post_type($id) !== 'enigme') {
        return 0;
    }

    global $wpdb;
    $table  = $wpdb->prefix . 'enigme_tentatives';
    $sql    = "SELECT COALESCE(SUM(points_utilises),0) FROM $table WHERE enigme_id = %d";
    $params = [$id];

    if ($debut && $fin) {
        $sql .= " AND date_tentative BETWEEN %s AND %s";
        $params[] = $debut;
        $params[] = $fin;
    } elseif ($debut) {
        $sql .= " AND date_tentative >= %s";
        $params[] = $debut;
    } elseif ($fin) {
        $sql .= " AND date_tentative <= %s";
        $params[] = $fin;
    }

    return (int) $wpdb->get_var($wpdb->prepare($sql, ...$params));
}

/**
 * Compte les bonnes solutions enregistrées pour une énigme durant une période.
 *
 * @param int         $id     ID de l'énigme.
 * @param string|null $debut  Date de début au format 'Y-m-d H:i:s'.
 * @param string|null $fin    Date de fin au format 'Y-m-d H:i:s'.
 * @return int Nombre de solutions correctes.
 */
function compter_bonnes_solutions_enigme_periode(int $id, ?string $debut = null, ?string $fin = null): int
{
    if (!$id || get_post_type($id) !== 'enigme') {
        return 0;
    }

    global $wpdb;
    $table  = $wpdb->prefix . 'enigme_tentatives';
    $sql    = "SELECT COUNT(*) FROM $table WHERE enigme_id = %d AND resultat = 'bon'";
    $params = [$id];

    if ($debut && $fin) {
        $sql .= " AND date_tentative BETWEEN %s AND %s";
        $params[] = $debut;
        $params[] = $fin;
    } elseif ($debut) {
        $sql .= " AND date_tentative >= %s";
        $params[] = $debut;
    } elseif ($fin) {
        $sql .= " AND date_tentative <= %s";
        $params[] = $fin;
    }

    return (int) $wpdb->get_var($wpdb->prepare($sql, ...$params));
}
