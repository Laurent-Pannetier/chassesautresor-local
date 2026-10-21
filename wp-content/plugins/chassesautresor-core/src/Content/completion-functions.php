<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\CompletionCacheManager;
use ChassesAuTresor\Core\Content\HuntCompletionService;
use ChassesAuTresor\Core\Progress\RiddleAnswerService;
use ChassesAuTresor\Core\Relationships\HuntRiddleQueryService;
use ChassesAuTresor\Core\Relationships\RelationshipService;

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

if (!function_exists('cat_completion_gap_label')) {
    function cat_completion_gap_label(string $key): string
    {
        $labels = [
            'title' => __('Titre', 'chassesautresor-com'),
            'illustrations' => __('Illustrations', 'chassesautresor-com'),
            'description' => __('Description', 'chassesautresor-com'),
            'image' => __('Image', 'chassesautresor-com'),
            'final_answer' => __('Réponse finale', 'chassesautresor-com'),
            'prerequisites' => __('Prérequis', 'chassesautresor-com'),
            'steps' => __('Étapes', 'chassesautresor-com'),
            'validatable_riddle' => __('Une énigme avec validation', 'chassesautresor-com'),
            'logo' => __('Logo', 'chassesautresor-com'),
        ];

        return $labels[$key] ?? $key;
    }
}

if (!function_exists('cat_riddle_missing_labels')) {
    /**
     * @return string[]
     */
    function cat_riddle_missing_labels(int $riddleId): array
    {
        $labels = [];
        foreach (cat_get_completion_cache_manager()->riddleMissingKeys($riddleId) as $key) {
            $labels[] = cat_completion_gap_label($key);
        }

        return $labels;
    }
}

if (!function_exists('cat_completion_edition_url')) {
    function cat_completion_edition_url(string $type, int $id): string
    {
        $url = apply_filters('chassesautresor_edition_url', '', $type, $id, 'parametres');

        return is_string($url) ? $url : '';
    }
}

if (!function_exists('cat_collect_hunt_activation_gaps')) {
    /**
     * Missing hunt fields, organizer fields, and incomplete riddle titles.
     *
     * @return array<int, array{label:string,url:string}>
     */
    function cat_collect_hunt_activation_gaps(int $huntId): array
    {
        if ($huntId <= 0 || !function_exists('get_field')) {
            return [];
        }

        $cache = cat_get_completion_cache_manager();
        $gaps = [];
        foreach ($cache->huntMissingKeys($huntId) as $key) {
            $gaps[] = [
                'label' => sprintf(
                    /* translators: %s: missing hunt field. */
                    __('Chasse : %s', 'chassesautresor-com'),
                    cat_completion_gap_label($key)
                ),
                'url' => cat_completion_edition_url('chasse', $huntId),
            ];
        }

        $organizerId = (new RelationshipService())->normalizeId(
            get_field('chasse_cache_organisateur', $huntId)
        ) ?? 0;
        if ($organizerId > 0) {
            foreach ($cache->organizerMissingKeys($organizerId) as $key) {
                $gaps[] = [
                    'label' => sprintf(
                        /* translators: %s: missing organizer field. */
                        __('Organisateur : %s', 'chassesautresor-com'),
                        cat_completion_gap_label($key)
                    ),
                    'url' => '',
                ];
            }
        }

        $query = (new HuntRiddleQueryService())->getRiddleIdsQueryArgs($huntId);
        $riddleIds = $query === [] || !function_exists('get_posts')
            ? []
            : array_map('intval', (array) get_posts($query));
        foreach ($riddleIds as $riddleId) {
            if ($riddleId <= 0 || $cache->riddleMissingKeys($riddleId) === []) {
                continue;
            }
            $title = function_exists('get_the_title') ? (string) get_the_title($riddleId) : '';
            $gaps[] = [
                'label' => $title !== '' ? $title : __('Énigme', 'chassesautresor-com'),
                'url' => cat_completion_edition_url('enigme', $riddleId),
            ];
        }

        return $gaps;
    }
}
