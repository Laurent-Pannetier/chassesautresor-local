<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\HuntRiddleQueryService;
use ChassesAuTresor\Core\Relationships\RelationshipService;

/**
 * Keep the hunt feature flags aligned with its direct and riddle content.
 */
class HuntFeatureCacheManager
{
    private HuntFeatureService $featureService;
    private HuntRiddleQueryService $riddleQueryService;
    private RelationshipService $relationshipService;
    private SolutionQueryService $solutionQueryService;
    private HintQueryService $hintQueryService;
    /** @var callable */
    private $getPostType;
    /** @var callable */
    private $getField;
    /** @var callable */
    private $updateField;
    /** @var callable */
    private $getPosts;
    /** @var callable */
    private $cacheDelete;

    public function __construct(
        ?HuntFeatureService $featureService = null,
        ?HuntRiddleQueryService $riddleQueryService = null,
        ?RelationshipService $relationshipService = null,
        ?SolutionQueryService $solutionQueryService = null,
        ?HintQueryService $hintQueryService = null,
        ?callable $getPostType = null,
        ?callable $getField = null,
        ?callable $updateField = null,
        ?callable $getPosts = null,
        ?callable $cacheDelete = null
    ) {
        $this->featureService = $featureService ?? new HuntFeatureService();
        $this->riddleQueryService = $riddleQueryService ?? new HuntRiddleQueryService();
        $this->relationshipService = $relationshipService ?? new RelationshipService();
        $this->solutionQueryService = $solutionQueryService ?? new SolutionQueryService();
        $this->hintQueryService = $hintQueryService ?? new HintQueryService();
        $this->getPostType = $getPostType ?? 'get_post_type';
        $this->getField = $getField ?? 'get_field';
        $this->updateField = $updateField ?? 'update_field';
        $this->getPosts = $getPosts ?? 'get_posts';
        $this->cacheDelete = $cacheDelete ?? 'wp_cache_delete';
    }

    public function recalculate(int $huntId): bool
    {
        if ($huntId <= 0 || ($this->getPostType)($huntId) !== 'chasse') {
            return false;
        }

        $riddleIds = ($this->getPosts)($this->riddleQueryService->getRiddleIdsQueryArgs($huntId));
        $features = $this->featureService->summarize(
            $this->hasSolution($huntId, 'chasse'),
            $this->hasHints($huntId, 'chasse'),
            $riddleIds,
            fn (int $riddleId): bool => $this->hasSolution($riddleId, 'enigme'),
            fn (int $riddleId): bool => $this->hasHints($riddleId, 'enigme')
        );

        ($this->updateField)('chasse_cache_has_solutions', $features['has_solutions'] ? 1 : 0, $huntId);
        ($this->updateField)('chasse_cache_has_indices', $features['has_indices'] ? 1 : 0, $huntId);
        return true;
    }

    public function handleRiddleSave(int $riddleId): void
    {
        if (($this->getPostType)($riddleId) !== 'enigme') {
            return;
        }

        $huntId = (int) ($this->getField)('enigme_chasse_associee', $riddleId);
        if ($huntId > 0) {
            ($this->cacheDelete)('enigmes_chasse_' . $huntId, 'chassesautresor');
            $this->recalculate($huntId);
        }
    }

    public function handleHintSave(int $hintId): void
    {
        $this->handleRelatedContentSave($hintId, 'indice');
    }

    public function handleSolutionSave(int $solutionId): void
    {
        $this->handleRelatedContentSave($solutionId, 'solution');
    }

    private function handleRelatedContentSave(int $postId, string $contentType): void
    {
        if (($this->getPostType)($postId) !== $contentType) {
            return;
        }

        $prefix = $contentType === 'indice' ? 'indice' : 'solution';
        $targetType = (string) ($this->getField)($prefix . '_cible_type', $postId);
        $riddleId = $targetType === 'enigme'
            ? (int) ($this->getField)($prefix . '_enigme_linked', $postId)
            : 0;
        $directHunt = $targetType === 'chasse'
            ? ($this->getField)($prefix . '_chasse_linked', $postId)
            : null;
        $riddleHunt = $riddleId > 0
            ? ($this->getField)('enigme_chasse_associee', $riddleId)
            : null;
        $huntId = $this->relationshipService->resolveTargetHuntId($targetType, $directHunt, $riddleHunt);
        if ($huntId !== null) {
            $this->recalculate($huntId);
        }
    }

    private function hasSolution(int $targetId, string $targetType): bool
    {
        $args = $this->solutionQueryService->getExistingSolutionIdsQueryArgs($targetId, $targetType);
        return $args !== [] && ($this->getPosts)($args) !== [];
    }

    private function hasHints(int $targetId, string $targetType): bool
    {
        $args = $this->hintQueryService->getExistingHintIdsQueryArgs($targetId, $targetType);
        return $args !== [] && ($this->getPosts)($args) !== [];
    }
}
