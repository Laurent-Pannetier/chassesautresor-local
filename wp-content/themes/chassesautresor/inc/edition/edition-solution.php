<?php
/**
 * Gestion de la publication différée des solutions.
 *
 * @package chassesautresor.com
 */

/**
 * Planifie la publication d'une solution.
 *
 * Calcule la date cible selon les champs ACF puis programme un événement cron
 * unique pour rendre la solution accessible.
 *
 * @param int $solution_id ID de la solution.
 * @return void
 */
function solution_planifier_publication(int $solution_id): void
{
    if (get_post_type($solution_id) !== 'solution') {
        return;
    }

    $cible = get_field('solution_cible_type', $solution_id);
    $chasse_id = 0;
    if ($cible === 'chasse') {
        $chasse = get_field('solution_chasse_linked', $solution_id);
        $chasse_id = is_array($chasse) ? (int) ($chasse[0] ?? 0) : (int) $chasse;
    } elseif ($cible === 'enigme') {
        $enigme = get_field('solution_enigme_linked', $solution_id);
        $enigme_id = is_array($enigme) ? (int) ($enigme[0] ?? 0) : (int) $enigme;
        $chasse_id = $enigme_id ? recuperer_id_chasse_associee($enigme_id) : 0;
    }

    if (!$chasse_id) {
        return;
    }

    $statut = get_field('statut_chasse', $chasse_id);
    $terminee = is_string($statut) && in_array(strtolower($statut), ['terminée', 'termine', 'terminé'], true);
    if (!$terminee) {
        return;
    }

    $dispo = get_field('solution_disponibilite', $solution_id) ?: 'fin_chasse';
    $decalage = (int) get_field('solution_decalage_jours', $solution_id);
    $heure = get_field('solution_heure_publication', $solution_id) ?: '00:00';

    $base = current_time('timestamp');
    $timestamp = $base;
    if ($dispo === 'differee') {
        $timestamp = strtotime("+{$decalage} days {$heure}", $base);
    }

    wp_clear_scheduled_hook('publier_solution_programmee', [$solution_id]);

    if (!$timestamp || $timestamp <= current_time('timestamp')) {
        solution_rendre_accessible($solution_id);
        return;
    }

    update_post_meta($solution_id, 'solution_date_disponibilite', gmdate('Y-m-d H:i:s', $timestamp));
    update_field('solution_cache_etat_systeme', 'programme', $solution_id);
    wp_schedule_single_event($timestamp, 'publier_solution_programmee', [$solution_id]);
}

/**
 * Rend une solution accessible immédiatement.
 *
 * @param int $solution_id ID de la solution.
 * @return void
 */
function solution_rendre_accessible(int $solution_id): void
{
    if (get_post_type($solution_id) !== 'solution') {
        return;
    }

    update_field('solution_cache_etat_systeme', 'accessible', $solution_id);
    delete_post_meta($solution_id, 'solution_date_disponibilite');
    if (get_post_status($solution_id) !== 'publish') {
        wp_update_post(['ID' => $solution_id, 'post_status' => 'publish']);
    }
}
add_action('publier_solution_programmee', 'solution_rendre_accessible');

/**
 * Basculer les solutions programmées dont la date est atteinte.
 *
 * @return void
 */
function basculer_solutions_programme(): void
{
    $solutions = get_posts([
        'post_type'      => 'solution',
        'post_status'    => ['publish', 'pending', 'draft', 'private', 'future'],
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'posts_per_page' => -1,
        'meta_query'     => [
            [
                'key'   => 'solution_cache_etat_systeme',
                'value' => 'programme',
            ],
            [
                'key'     => 'solution_date_disponibilite',
                'value'   => current_time('mysql'),
                'compare' => '<=',
                'type'    => 'DATETIME',
            ],
        ],
    ]);

    foreach ($solutions as $sid) {
        solution_rendre_accessible($sid);
    }
}
add_action('basculer_solutions_programme', 'basculer_solutions_programme');

/**
 * Planifie la tâche récurrente de basculement des solutions.
 *
 * @return void
 */
function planifier_tache_basculer_solutions_programme(): void
{
    if (!wp_next_scheduled('basculer_solutions_programme')) {
        wp_schedule_event(time(), 'hourly', 'basculer_solutions_programme');
    }
}
add_action('after_switch_theme', 'planifier_tache_basculer_solutions_programme');

/**
 * Hook ACF pour planifier la publication à la sauvegarde.
 *
 * @param int $post_id ID du post sauvegardé.
 * @return void
 */
function solution_acf_save_post(int $post_id): void
{
    if (get_post_type($post_id) !== 'solution') {
        return;
    }
    solution_planifier_publication($post_id);
}
add_action('acf/save_post', 'solution_acf_save_post', 40);
