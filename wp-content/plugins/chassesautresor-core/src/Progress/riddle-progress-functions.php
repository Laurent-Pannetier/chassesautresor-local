<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\HuntProgressService;
use ChassesAuTresor\Core\Progress\RiddleAnswerService;
use ChassesAuTresor\Core\Relationships\RelationshipService;
use ChassesAuTresor\Core\Support\CoreServiceFactory;

if (!function_exists('cat_get_hunt_progress_service')) {
    function cat_get_hunt_progress_service(): HuntProgressService
    {
        global $wpdb;
        return CoreServiceFactory::huntProgress($wpdb);
    }
}

if (!function_exists('enigme_get_bonnes_reponses')) {
    function enigme_get_bonnes_reponses(int $riddleId): array
    {
        return (new RiddleAnswerService())->get($riddleId);
    }
}

if (!function_exists('enigme_get_statut_utilisateur')) {
    function enigme_get_statut_utilisateur(int $riddleId, int $userId): string
    {
        if ($riddleId <= 0 || $userId <= 0) {
            return 'non_commencee';
        }
        $status = cat_get_hunt_progress_service()->getRiddleStatus($userId, $riddleId);
        return $status ? strtolower(remove_accents($status)) : 'non_commencee';
    }
}

if (!function_exists('enigme_mettre_a_jour_statut_utilisateur')) {
    function enigme_mettre_a_jour_statut_utilisateur(
        int $riddleId,
        int $userId,
        string $status,
        bool $force = false
    ): bool {
        if ($riddleId <= 0 || $userId <= 0 || $status === '') {
            return false;
        }
        return cat_get_hunt_progress_service()->advanceRiddleStatus(
            $userId,
            $riddleId,
            strtolower(remove_accents($status)),
            current_time('mysql'),
            $force
        );
    }
}

if (!function_exists('enigme_pre_requis_remplis')) {
    function enigme_pre_requis_remplis(int $riddleId, int $userId): bool
    {
        $relationships = new RelationshipService();
        $prerequisites = $relationships->normalizeIds(
            (array) get_field('enigme_acces_pre_requis', $riddleId)
        );
        $condition = (string) (get_field('enigme_acces_condition', $riddleId) ?? 'immediat');
        return cat_get_hunt_progress_service()->areRiddlePrerequisitesMet($userId, $prerequisites, $condition);
    }
}

if (!function_exists('get_statut_utilisateur_enigme')) {
    function get_statut_utilisateur_enigme(int $userId, int $riddleId): ?string
    {
        $status = cat_get_hunt_progress_service()->getRiddleStatus($userId, $riddleId);
        return $status ? strtolower(remove_accents($status)) : null;
    }
}

if (!function_exists('est_enigme_resolue_par_utilisateur')) {
    function est_enigme_resolue_par_utilisateur(int $userId, int $riddleId): bool
    {
        return in_array(get_statut_utilisateur_enigme($userId, $riddleId), ['resolue', 'terminee'], true);
    }
}
