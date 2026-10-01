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

function enigme_render_bar_row(string $label, int $rate, string $fill_style = ''): string {
    return (new ChassesAuTresor\Core\Progress\RiddleBarRenderer())->row($label, $rate, $fill_style);
}

function enigme_render_bar_section(string $title, int $user_rate, int $avg_rate, string $section_class): string {
    return (new ChassesAuTresor\Core\Progress\RiddleBarRenderer())
        ->section($title, $user_rate, $avg_rate, $section_class);
}

function enigme_render_bar_subsection(
    string $title,
    int $user_rate,
    int $avg_rate,
    string $section_class,
    string $help_message = '',
    string $help_label = ''
): string {
    return (new ChassesAuTresor\Core\Progress\RiddleBarRenderer())
        ->subsection($title, $user_rate, $avg_rate, $section_class, $help_message, $help_label);
}

function enigme_render_single_bar_subsection(
    string $title,
    int $rate,
    string $section_class,
    string $help_message = '',
    string $help_label = ''
): string {
    return (new ChassesAuTresor\Core\Progress\RiddleBarRenderer())
        ->singleSubsection($title, $rate, $section_class, $help_message, $help_label);
}

function enigme_sidebar_progression_html(?int $chasse_id, int $user_id): string {
    if (!$chasse_id || !$user_id) {
        return '';
    }

    global $wpdb;
    $data = CoreServiceFactory::riddleSidebarStatistics($wpdb)->progression($chasse_id, $user_id);

    return enigme_render_bar_subsection(
        __('Progression', 'chassesautresor-com'),
        $data['user'],
        $data['avg'],
        'enigme-progression',
        __(
            'Part moyenne des énigmes auxquelles chaque joueur a participé, rapportée au nombre total '
            . 'd’énigmes de la chasse. Vous : Part des énigmes auxquelles vous avez accédé. '
            . 'Moyenne : Moyenne sur l’ensemble des joueurs.',
            'chassesautresor-com'
        ),
        __('Définition de la progression', 'chassesautresor-com')
    );
}

function enigme_sidebar_resolution_html(int $enigme_id): string {
    if ($enigme_id <= 0) {
        return '';
    }

    global $wpdb;
    $rate = CoreServiceFactory::riddleSidebarStatistics($wpdb)->resolution($enigme_id);

    return enigme_render_single_bar_subsection(
        __('Résolution', 'chassesautresor-com'),
        $rate,
        'enigme-resolution',
        __(
            'Part moyenne des énigmes auxquelles chaque joueur a participé, rapportée au nombre total '
            . 'd’énigmes de la chasse.',
            'chassesautresor-com'
        ),
        __('Définition du taux de résolution', 'chassesautresor-com')
    );
}

function enigme_sidebar_metas_html(int $enigme_id): string {
    return ca_riddle_sidebar_renderer()->metas($enigme_id);
}

function enigme_sidebar_gagnants_html(int $enigme_id, int $user_id, int $page = 1): string {
    return ca_riddle_sidebar_renderer()->winners($enigme_id, $user_id, $page);
}

function ca_riddle_sidebar_renderer(): ChassesAuTresor\Core\Progress\RiddleSidebarRenderer {
    global $wpdb;

    return new ChassesAuTresor\Core\Progress\RiddleSidebarRenderer(
        CoreServiceFactory::riddleStatistics($wpdb),
        CoreServiceFactory::riddleSidebarStatistics($wpdb)
    );
}
