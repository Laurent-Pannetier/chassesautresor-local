<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\HuntRiddleQueryService;
use ChassesAuTresor\Core\Relationships\RelationshipService;
use ChassesAuTresor\Core\Progress\RiddleAnswerService;

/**
 * Calculate and persist completion caches for editable content.
 */
class CompletionCacheManager {
    private static array $checked = [];

    public function isOrganizerComplete(int $organizerId, ?callable $hasValidTitle = null): bool {
        if (get_post_type($organizerId) !== 'organisateur') {
            return false;
        }

        return (new OrganizerCompletionService())->isComplete(
            $hasValidTitle ? (bool) $hasValidTitle($organizerId) : $this->hasValidTitle($organizerId),
            !empty(get_field('logo_organisateur', $organizerId)),
            (string) get_field('description_longue', $organizerId)
        );
    }

    public function isHuntComplete(
        int $huntId,
        ?bool $hasValidatableRiddle = null,
        ?callable $hasValidTitle = null
    ): bool {
        if (get_post_type($huntId) !== 'chasse') {
            return false;
        }
        $image = get_field('chasse_principale_image', $huntId);
        $imageId = is_array($image) ? (int) ($image['ID'] ?? 0) : (int) $image;
        $service = new HuntCompletionService();
        if ($hasValidatableRiddle === null) {
            $riddleIds = get_posts((new HuntRiddleQueryService())->getRiddleIdsQueryArgs($huntId));
            $validationModes = array_map(
                static fn ($riddleId): string => (string) get_field('enigme_mode_validation', (int) $riddleId),
                $riddleIds
            );
            $hasValidatableRiddle = $service->hasValidatableRiddle($validationModes);
        }

        return $service->isComplete(
            $hasValidTitle ? (bool) $hasValidTitle($huntId) : $this->hasValidTitle($huntId),
            (string) get_field('chasse_principale_description', $huntId),
            $imageId,
            3902,
            (string) (get_field('chasse_mode_fin', $huntId) ?: 'automatique'),
            $hasValidatableRiddle
        );
    }

    public function isRiddleComplete(
        int $riddleId,
        ?callable $hasValidTitle = null,
        ?callable $hasAnswers = null
    ): bool {
        if (get_post_type($riddleId) !== 'enigme') {
            return false;
        }
        $images = get_field('enigme_visuel_image', $riddleId);
        $imageId = is_array($images) && !empty($images[0]['ID']) ? (int) $images[0]['ID'] : 0;
        $prerequisites = get_field('enigme_acces_pre_requis', $riddleId);
        $stepIds = (new RiddleStepQueryService())->findOrderedIds($riddleId);
        $hasCompleteSteps = (new RiddleStepCompletenessService())->areStepsComplete($stepIds);

        return (new RiddleCompletionService())->isComplete(
            $hasValidTitle ? (bool) $hasValidTitle($riddleId) : $this->hasValidTitle($riddleId),
            $imageId,
            defined('ID_IMAGE_PLACEHOLDER_ENIGME') ? ID_IMAGE_PLACEHOLDER_ENIGME : 3925,
            (string) get_field('enigme_mode_validation', $riddleId),
            $hasAnswers ? (bool) $hasAnswers($riddleId) : $this->hasAnswers($riddleId),
            (string) (get_field('enigme_acces_condition', $riddleId) ?? 'immediat'),
            is_array($prerequisites) && $prerequisites !== [],
            $hasCompleteSteps
        );
    }

    public function refresh(int $postId): bool {
        $type = (string) get_post_type($postId);
        if ($type === 'organisateur') {
            return $this->persist('organisateur_cache_complet', $postId, $this->isOrganizerComplete($postId));
        }
        if ($type === 'chasse') {
            return $this->persist('chasse_cache_complet', $postId, $this->isHuntComplete($postId));
        }
        if ($type === 'enigme') {
            $complete = $this->persist('enigme_cache_complet', $postId, $this->isRiddleComplete($postId));
            $huntId = (new RelationshipService())->normalizeId(get_field('enigme_chasse_associee', $postId));
            if ($huntId !== null) {
                $this->refresh($huntId);
            }
            return $complete;
        }

        return false;
    }

    public function ensureFresh(int $postId): void {
        if ($postId <= 0 || isset(self::$checked[$postId])) {
            return;
        }
        self::$checked[$postId] = true;
        $type = (string) get_post_type($postId);
        $fields = [
            'organisateur' => 'organisateur_cache_complet',
            'chasse' => 'chasse_cache_complet',
            'enigme' => 'enigme_cache_complet',
        ];
        if (!isset($fields[$type])) {
            return;
        }
        $actual = $type === 'organisateur'
            ? $this->isOrganizerComplete($postId)
            : ($type === 'chasse' ? $this->isHuntComplete($postId) : $this->isRiddleComplete($postId));
        if ((bool) get_field($fields[$type], $postId) === $actual) {
            return;
        }
        update_field($fields[$type], $actual ? 1 : 0, $postId);
        $huntId = $type === 'enigme'
            ? (new RelationshipService())->normalizeId(get_field('enigme_chasse_associee', $postId))
            : ($type === 'chasse' ? $postId : null);
        if ($huntId !== null) {
            do_action('chassesautresor_hunt_display_cache_clear_requested', $huntId);
        }
    }

    private function persist(string $field, int $postId, bool $complete): bool {
        update_field($field, $complete ? 1 : 0, $postId);
        return $complete;
    }

    private function hasValidTitle(int $postId): bool {
        $title = trim((string) get_post_field('post_title', $postId));
        $defaults = [
            'organisateur' => 'Votre nom d’organisateur',
            'chasse' => 'Nouvelle chasse',
            'enigme' => 'en création',
        ];
        $default = $defaults[(string) get_post_type($postId)] ?? '';
        return $title !== '' && ($default === '' || strcasecmp($title, $default) !== 0);
    }

    private function hasAnswers(int $riddleId): bool {
        return (new RiddleAnswerService())->get($riddleId) !== [];
    }
}
