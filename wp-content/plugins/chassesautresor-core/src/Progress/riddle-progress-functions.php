<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\HuntProgressService;
use ChassesAuTresor\Core\Progress\RiddleAnswerService;
use ChassesAuTresor\Core\Progress\RiddleParticipationPolicyService;
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

if (!function_exists('enigme_verifier_verrouillage')) {
    function enigme_verifier_verrouillage(int $riddleId, int $userId): array
    {
        $status = $userId > 0 ? enigme_get_statut_utilisateur($riddleId, $userId) : '';
        $timestamp = null;
        $formattedDate = null;
        if ($status === 'bloquee_date') {
            $access = get_field('enigme_acces', $riddleId);
            $dateValue = is_array($access) ? ($access['enigme_acces_date'] ?? '') : '';
            $date = is_string($dateValue) && $dateValue !== '' ? date_create_immutable($dateValue) : false;
            if ($date !== false) {
                $timestamp = $date->getTimestamp();
                $formattedDate = $date->format('d/m/Y à H\hi');
            }
        }
        return cat_get_hunt_progress_service()->getRiddleLockState(
            $userId,
            $status,
            $timestamp,
            $formattedDate
        );
    }
}

if (!function_exists('traiter_statut_enigme')) {
    function traiter_statut_enigme(int $riddleId, ?int $userId = null): array
    {
        global $wpdb;
        $userId = $userId ?: get_current_user_id();
        $relationships = new RelationshipService();
        $huntId = $relationships->normalizeId(get_field('enigme_chasse_associee', $riddleId)) ?? 0;
        $organizerId = $relationships->normalizeId(get_field('chasse_cache_organisateur', $huntId));
        $organizerUsers = $organizerId !== null
            ? $relationships->normalizeIds((array) get_field('utilisateurs_associes', $organizerId))
            : [];
        $condition = (string) (get_field('enigme_acces_condition', $riddleId) ?? 'immediat');
        $state = (new RiddleParticipationPolicyService())->decide(
            enigme_get_statut_utilisateur($riddleId, $userId),
            current_user_can('manage_options'),
            get_post_status($riddleId) === 'draft',
            in_array($userId, $organizerUsers, true),
            $huntId > 0 && get_field('chasse_cache_statut', $huntId) === 'termine',
            $huntId > 0 && CoreServiceFactory::huntEngagement($wpdb)->isEngaged($userId, $huntId),
            CoreServiceFactory::riddleEngagement($wpdb)->isEngaged($userId, $riddleId),
            $condition !== 'pre_requis' || enigme_pre_requis_remplis($riddleId, $userId)
        );
        $state['url'] = $state['rediriger']
            ? ($huntId > 0 ? get_permalink($huntId) : home_url('/'))
            : null;
        return $state;
    }
}

if (!function_exists('enigme_est_visible_pour')) {
    function enigme_est_visible_pour(int $userId, int $riddleId): bool
    {
        return !traiter_statut_enigme($riddleId, $userId)['rediriger'];
    }
}

if (!function_exists('utilisateur_peut_engager_enigme')) {
    function utilisateur_peut_engager_enigme(int $riddleId, ?int $userId = null): bool
    {
        $userId = $userId ?? get_current_user_id();
        $systemStatus = enigme_get_etat_systeme($riddleId);
        return cat_get_hunt_progress_service()->canEngageRiddle(
            $systemStatus,
            enigme_get_statut_utilisateur($riddleId, $userId),
            $systemStatus === 'bloquee_pre_requis' && enigme_pre_requis_remplis($riddleId, $userId)
        );
    }
}
