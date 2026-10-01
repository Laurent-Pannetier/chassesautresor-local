<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Relationships;

use ChassesAuTresor\Core\Content\AcfRelationshipMutationService;

/**
 * Synchronize the hunt riddle cache with canonical riddle relationships.
 */
class HuntRiddleCacheSynchronizer {
    private HuntRiddleCacheService $cacheService;
    private HuntRiddleQueryService $queryService;
    private RelationshipService $relationshipService;

    public function __construct(
        ?HuntRiddleCacheService $cacheService = null,
        ?HuntRiddleQueryService $queryService = null,
        ?RelationshipService $relationshipService = null
    ) {
        $this->cacheService = $cacheService ?? new HuntRiddleCacheService();
        $this->queryService = $queryService ?? new HuntRiddleQueryService();
        $this->relationshipService = $relationshipService ?? new RelationshipService();
    }

    /** @return array<string, mixed> */
    public function compareReality(
        int $huntId,
        bool $correct = false,
        ?callable $getPostType = null,
        ?callable $getPosts = null,
        ?callable $getField = null,
        ?callable $updateField = null
    ): array {
        $getPostType = $getPostType ?? 'get_post_type';
        $getPosts = $getPosts ?? 'get_posts';
        $getField = $getField ?? 'get_field';
        $updateField = $updateField ?? 'update_field';
        if ($getPostType($huntId) !== 'chasse') {
            return ['valide' => false, 'erreur' => 'ID de chasse invalide.', 'attendu' => [], 'cache' => []];
        }

        $expected = $getPosts($this->queryService->getSynchronizedRiddleIdsQueryArgs($huntId));
        $cached = $this->relationshipService->normalizeIds(
            (array) $getField('chasse_cache_enigmes', $huntId, false)
        );
        $result = $this->cacheService->compare(array_map('intval', $expected), $cached, $correct);
        if ($result['correction']) {
            $updateField('chasse_cache_enigmes', $result['expected'], $huntId);
        }

        return [
            'valide' => true,
            'synchro' => $result['synced'],
            'attendu' => $result['expected'],
            'cache' => $result['cached'],
            'correction' => $result['correction'],
        ];
    }

    /** @return array<string, mixed> */
    public function validateCache(int $huntId, bool $correct = false): array {
        if (get_post_type($huntId) !== 'chasse') {
            return [
                'valide' => false,
                'erreur' => 'ID de chasse invalide.',
                'liste_cache' => [],
                'invalides' => [],
            ];
        }

        $cached = $this->relationshipService->normalizeIds(
            (array) get_field('chasse_cache_enigmes', $huntId, false)
        );
        $relatedHunts = [];
        foreach ($cached as $riddleId) {
            $relatedHunts[$riddleId] = get_post_type($riddleId) === 'enigme'
                ? $this->relationshipService->normalizeId(get_field('enigme_chasse_associee', $riddleId))
                : null;
        }
        $result = $this->cacheService->validate($huntId, $cached, $relatedHunts, $correct);
        if ($result['correction']) {
            update_field('chasse_cache_enigmes', $result['corrected'], $huntId);
        }

        return [
            'valide' => true,
            'synchro' => $result['synced'],
            'liste_cache' => $result['cached'],
            'invalides' => $result['invalid'],
            'correction' => $result['correction'],
        ];
    }

    public function synchronizeRelations(int $huntId): bool {
        if (get_post_type($huntId) !== 'chasse') {
            return false;
        }

        $expected = array_map(
            'intval',
            get_posts($this->queryService->getSynchronizedRiddleIdsQueryArgs($huntId))
        );
        $cached = $this->relationshipService->normalizeIds(
            (array) get_field('chasse_cache_enigmes', $huntId, false)
        );
        $comparison = $this->cacheService->compare($expected, $cached, false);
        if ($comparison['synced']) {
            return true;
        }

        return (bool) update_field('chasse_cache_enigmes', $comparison['expected'], $huntId);
    }

    /** @return array<string, mixed> */
    public function synchronize(int $huntId, bool $recalculate = false, bool $clean = false): array {
        $reality = $this->compareReality($huntId, $recalculate);
        $cache = $this->validateCache($huntId, $clean);
        $correction = (bool) ($reality['correction'] ?? false) || (bool) ($cache['correction'] ?? false);

        return [
            'valide' => (bool) ($reality['valide'] ?? false) && (bool) ($cache['valide'] ?? false),
            'chasse_id' => $huntId,
            'synchro_realite_vs_cache' => (bool) ($reality['synchro'] ?? false),
            'synchro_cache_vs_realite' => (bool) ($cache['synchro'] ?? false),
            'correction_effectuee' => $correction,
            'liste_attendue' => (array) ($reality['attendu'] ?? []),
            'liste_cache' => (array) ($reality['cache'] ?? []),
            'invalides_dans_cache' => (array) ($cache['invalides'] ?? []),
        ];
    }

    public function maybeSynchronize(int $huntId, bool $authorized): bool {
        if (!$authorized || get_post_type($huntId) !== 'chasse') {
            return false;
        }
        $key = 'verif_sync_chasse_' . $huntId;
        if (get_transient($key)) {
            return false;
        }

        $this->synchronize($huntId, true, true);
        set_transient($key, 'done', 30 * MINUTE_IN_SECONDS);
        return true;
    }

    public function ensureRiddleCached(int $riddleId): bool {
        if (get_post_type($riddleId) !== 'enigme') {
            return false;
        }
        $key = 'verif_chasse_relation_' . $riddleId;
        if (get_transient($key)) {
            return false;
        }
        set_transient($key, 'done', 5 * MINUTE_IN_SECONDS);

        $huntId = $this->relationshipService->normalizeId(
            get_field('enigme_chasse_associee', $riddleId, false)
        );
        if ($huntId === null || get_post_type($huntId) !== 'chasse') {
            return false;
        }
        $cached = $this->relationshipService->normalizeIds(
            (array) get_field('chasse_cache_enigmes', $huntId)
        );
        if (in_array($riddleId, $cached, true)) {
            return false;
        }

        return (new AcfRelationshipMutationService())->mutate(
            $huntId,
            'chasse_cache_enigmes',
            $riddleId,
            'field_67b740025aae0',
            'add',
            'get_post_meta',
            'update_post_meta'
        );
    }

    public function synchronizeAll(int $batchSize = 100, ?callable $getPosts = null, ?callable $synchronize = null): int {
        $batchSize = max(1, $batchSize);
        $getPosts = $getPosts ?? 'get_posts';
        $synchronize = $synchronize ?? fn (int $huntId) => $this->synchronize($huntId, true, true);
        $processed = 0;
        $offset = 0;

        do {
            $huntIds = $getPosts([
                'post_type' => 'chasse',
                'post_status' => ['publish', 'pending', 'draft'],
                'posts_per_page' => $batchSize,
                'offset' => $offset,
                'fields' => 'ids',
                'orderby' => 'ID',
                'order' => 'ASC',
                'no_found_rows' => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            ]);
            foreach ($huntIds as $huntId) {
                $synchronize((int) $huntId);
                $processed++;
            }
            $offset += $batchSize;
        } while (count($huntIds) === $batchSize);

        return $processed;
    }
}
