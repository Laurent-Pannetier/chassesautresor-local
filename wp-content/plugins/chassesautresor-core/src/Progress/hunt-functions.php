<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\HuntEngagementService;
use ChassesAuTresor\Core\Progress\HuntWinnerRepository;
use ChassesAuTresor\Core\Progress\HuntWinnersTable;
use ChassesAuTresor\Core\Support\CoreServiceFactory;

function recuperer_infos_chasse($huntId): array
{
    $fields = get_fields($huntId);

    return [
        'lot' => $fields['lot'] ?? __('Non spécifié', 'chassesautresor-com'),
        'date_de_debut' => $fields['date_de_debut'] ?? __('Non spécifiée', 'chassesautresor-com'),
        'date_de_fin' => $fields['date_de_fin'] ?? __('Non spécifiée', 'chassesautresor-com'),
    ];
}

function chasse_install_winners_table(): void
{
    HuntWinnersTable::install();
}

function cat_get_hunt_winner_repository(): HuntWinnerRepository
{
    global $wpdb;

    return CoreServiceFactory::huntWinners($wpdb);
}

function cat_get_hunt_engagement_service(): HuntEngagementService
{
    global $wpdb;

    return CoreServiceFactory::huntEngagement($wpdb);
}

function enregistrer_gagnant_chasse(int $userId, int $huntId, string $winDate): void
{
    cat_get_hunt_winner_repository()->save($userId, $huntId, $winDate);
}

function compter_chasses_gagnees(int $userId): int
{
    return cat_get_hunt_winner_repository()->countByUser($userId);
}

function chasse_get_champs($huntId): array
{
    $startDate = get_field('chasse_infos_date_debut', $huntId);
    $endDate = get_field('chasse_infos_date_fin', $huntId);

    return [
        'lot' => get_field('chasse_infos_recompense_texte', $huntId, false) ?? '',
        'titre_recompense' => get_field('chasse_infos_recompense_titre', $huntId) ?? '',
        'valeur_recompense' => get_field('chasse_infos_recompense_valeur', $huntId) ?? '',
        'cout_points' => get_field('chasse_infos_cout_points', $huntId) ?? 0,
        'date_debut' => $startDate ?: get_post_meta($huntId, 'chasse_infos_date_debut', true),
        'date_fin' => $endDate ?: get_post_meta($huntId, 'chasse_infos_date_fin', true),
        'illimitee' => get_field('chasse_infos_duree_illimitee', $huntId) ?? false,
        'nb_max' => get_field('chasse_infos_nb_max_gagants', $huntId) ?? 0,
        'date_decouverte' => get_field('chasse_cache_date_decouverte', $huntId),
        'gagnants' => get_field('chasse_cache_gagnants', $huntId) ?? '',
        'mode_fin' => get_field('chasse_mode_fin', $huntId) ?? 'automatique',
        'current_stored_statut' => get_field('chasse_cache_statut', $huntId),
    ];
}

function utilisateur_est_engage_dans_chasse(int $userId, int $huntId): bool
{
    return cat_get_hunt_engagement_service()->isEngaged($userId, $huntId);
}
