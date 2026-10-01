<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\CompletionCacheManager;
use ChassesAuTresor\Core\Content\HuntCompletionService;
use ChassesAuTresor\Core\Progress\RiddleAnswerService;
use ChassesAuTresor\Core\Relationships\HuntRiddleQueryService;

if (!function_exists('cat_get_completion_cache_manager')) {
    function cat_get_completion_cache_manager(): CompletionCacheManager
    {
        return new CompletionCacheManager();
    }
}

if (!function_exists('organisateur_est_complet')) {
    function organisateur_est_complet(int $organizerId): bool
    {
        return cat_get_completion_cache_manager()->isOrganizerComplete($organizerId, 'titre_est_valide');
    }
}

if (!function_exists('organisateur_mettre_a_jour_complet')) {
    function organisateur_mettre_a_jour_complet(int $organizerId): bool
    {
        return cat_get_completion_cache_manager()->refresh($organizerId);
    }
}

if (!function_exists('chasse_has_validatable_enigme')) {
    function chasse_has_validatable_enigme(int $huntId): bool
    {
        $query = (new HuntRiddleQueryService())->getRiddleIdsQueryArgs($huntId);
        $riddleIds = $query === [] || !function_exists('get_posts')
            ? []
            : array_map('intval', (array) get_posts($query));
        $modes = array_map(
            static fn (int $riddleId): string => (string) get_field('enigme_mode_validation', $riddleId),
            $riddleIds
        );

        return (new HuntCompletionService())->hasValidatableRiddle($modes);
    }
}

if (!function_exists('chasse_est_complet')) {
    function chasse_est_complet(int $huntId): bool
    {
        return cat_get_completion_cache_manager()->isHuntComplete(
            $huntId,
            chasse_has_validatable_enigme($huntId),
            'titre_est_valide'
        );
    }
}

if (!function_exists('chasse_mettre_a_jour_complet')) {
    function chasse_mettre_a_jour_complet(int $huntId): bool
    {
        return cat_get_completion_cache_manager()->refresh($huntId);
    }
}

if (!function_exists('enigme_est_complet')) {
    function enigme_est_complet(int $riddleId): bool
    {
        return cat_get_completion_cache_manager()->isRiddleComplete(
            $riddleId,
            'titre_est_valide',
            static fn (int $id): bool => (new RiddleAnswerService())->get($id) !== []
        );
    }
}

if (!function_exists('enigme_mettre_a_jour_complet')) {
    function enigme_mettre_a_jour_complet(int $riddleId): bool
    {
        return cat_get_completion_cache_manager()->refresh($riddleId);
    }
}

if (!function_exists('mettre_a_jour_cache_complet_automatiquement')) {
    function mettre_a_jour_cache_complet_automatiquement($postId): void
    {
        if (is_numeric($postId)) {
            cat_get_completion_cache_manager()->refresh((int) $postId);
        }
    }
}

if (!function_exists('verifier_ou_mettre_a_jour_cache_complet')) {
    function verifier_ou_mettre_a_jour_cache_complet(int $postId): void
    {
        cat_get_completion_cache_manager()->ensureFresh($postId);
    }
}
