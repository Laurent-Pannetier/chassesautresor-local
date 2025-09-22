<?php
/**
 * Homepage filters utilities.
 */

defined('ABSPATH') || exit();

/**
 * Retrieves hunt identifiers for the homepage according to optional filters.
 *
 * Filters accept the following values:
 * - statut: "tous", "en_cours", "a_venir", "termine".
 *   The "en_cours" filter groups hunts whose "chasse_cache_statut" is either "en_cours" or "payante".
 * - cout: array of "gratuit" and/or "points". Omit to keep both values by default.
 *   A hunt is considered "gratuit" when both "chasse_infos_cout_points" and "nb_enigmes_payantes" are equal to 0.
 *
 * @param array{statut?:string, cout?:string|string[]}|array $args Raw filters coming from the frontend.
 *
 * @return array{ids:int[], total:int, filters_normalises:array{statut:string, cout:string[]}}
 */
function ca_home_filter_chasse_ids(array $args): array
{
    $status_whitelist = ['tous', 'en_cours', 'a_venir', 'termine'];
    $cost_whitelist   = ['gratuit', 'points'];

    $default_filters = [
        'statut' => 'tous',
        'cout'   => $cost_whitelist,
    ];

    $normalized_status = $default_filters['statut'];
    if (isset($args['statut']) && is_string($args['statut']) && in_array($args['statut'], $status_whitelist, true)) {
        $normalized_status = $args['statut'];
    }

    $cost_filter_provided = array_key_exists('cout', $args);
    $raw_cost_values      = $args['cout'] ?? $default_filters['cout'];

    if (is_string($raw_cost_values)) {
        $raw_cost_values = [$raw_cost_values];
    }

    if (!is_array($raw_cost_values)) {
        $raw_cost_values = [];
    }

    $raw_cost_values = array_map('strval', $raw_cost_values);
    $normalized_cost = array_values(array_intersect($cost_whitelist, $raw_cost_values));

    if (!$cost_filter_provided) {
        $normalized_cost = $default_filters['cout'];
    }

    $query = new WP_Query([
        'post_type'      => 'chasse',
        'post_status'    => 'publish',
        'meta_query'     => [
            [
                'key'   => 'chasse_cache_statut_validation',
                'value' => 'valide',
            ],
        ],
        'fields'         => 'ids',
        'posts_per_page' => -1,
    ]);

    $chasse_ids = is_array($query->posts) ? array_map('intval', $query->posts) : [];
    wp_reset_postdata();

    $user_id = (int) get_current_user_id();

    if (function_exists('chasse_est_visible_pour_utilisateur')) {
        $chasse_ids = array_values(array_filter(
            $chasse_ids,
            static function (int $chasse_id) use ($user_id): bool {
                return chasse_est_visible_pour_utilisateur($chasse_id, $user_id);
            }
        ));
    }

    $status_map = [
        'en_cours' => ['en_cours', 'payante'],
        'a_venir'  => ['a_venir'],
        'termine'  => ['termine'],
    ];

    $filtered_ids = [];

    foreach ($chasse_ids as $chasse_id) {
        $infos_chasse = function_exists('preparer_infos_affichage_chasse')
            ? preparer_infos_affichage_chasse($chasse_id, $user_id)
            : [];

        $statut_metier = $infos_chasse['statut'] ?? get_field('chasse_cache_statut', $chasse_id);

        if (
            'tous' !== $normalized_status
            && !in_array($statut_metier, $status_map[$normalized_status] ?? [], true)
        ) {
            continue;
        }

        $cout_points        = (int) ($infos_chasse['champs']['cout_points'] ?? get_field('chasse_infos_cout_points', $chasse_id));
        $nb_enigmes_payantes = (int) ($infos_chasse['nb_enigmes_payantes'] ?? 0);

        $is_free = ($cout_points <= 0) && ($nb_enigmes_payantes <= 0);

        if ($cost_filter_provided) {
            if (empty($normalized_cost)) {
                continue;
            }

            if ($is_free && !in_array('gratuit', $normalized_cost, true)) {
                continue;
            }

            if (!$is_free && !in_array('points', $normalized_cost, true)) {
                continue;
            }
        }

        $filtered_ids[] = $chasse_id;
    }

    return [
        'ids'                => $filtered_ids,
        'total'              => count($filtered_ids),
        'filters_normalises' => [
            'statut' => $normalized_status,
            'cout'   => $normalized_cost,
        ],
    ];
}
