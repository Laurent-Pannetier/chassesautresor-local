<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleStatisticsService;
use ChassesAuTresor\Core\Support\CoreServiceFactory;

/** Create the service used by the historical riddle statistics views. */
function cat_get_riddle_statistics_service(): RiddleStatisticsService {
    global $wpdb;

    return CoreServiceFactory::riddleStatistics($wpdb);
}

/** Decide whether the riddle navigation menu is visible to a user. */
function enigme_user_can_see_menu(int $user_id, int $chasse_id, string $chasse_stat): bool {
    if ($chasse_id <= 0) {
        return false;
    }

    $validationStatus = (string) (get_field('chasse_cache_statut_validation', $chasse_id) ?? '');
    $isAdministrator = current_user_can('administrator');
    $isAssociated = utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id);
    $isOrganizer = est_organisateur($user_id);
    if (($isAdministrator || ($isOrganizer && $isAssociated)) && $validationStatus !== 'banni') {
        return true;
    }

    if (
        !function_exists('utilisateur_est_engage_dans_chasse')
        || !utilisateur_est_engage_dans_chasse($user_id, $chasse_id)
    ) {
        return false;
    }

    return !in_array($chasse_stat, ['revision', 'a_venir'], true);
}
