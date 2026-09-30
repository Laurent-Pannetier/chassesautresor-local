<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use DateTimeInterface;

/**
 * Synchronize the cached state and publication status of a hint.
 */
class HintCacheUpdater {
    private HintCacheService $cacheService;

    public function __construct(?HintCacheService $cacheService = null) {
        $this->cacheService = $cacheService ?? new HintCacheService();
    }

    /**
     * @param callable(string, int): mixed $getField
     * @param callable(string): ?DateTimeInterface $parseDate
     * @param callable(int): string $getPostStatus
     * @param callable(int): mixed $getPost
     * @param callable(string, mixed, int): mixed $updateField
     * @param callable(array<string, mixed>): mixed $updatePost
     * @return array{complete:int,state:string,publication_status:?string}
     */
    public function update(
        int $hintId,
        int $currentTimestamp,
        callable $getField,
        callable $parseDate,
        callable $getPostStatus,
        callable $getPost,
        callable $updateField,
        callable $updatePost
    ): array {
        $content = trim((string) $getField('indice_contenu', $hintId));
        $imageId = $getField('indice_image', $hintId);
        $availability = (string) $getField('indice_disponibilite', $hintId);
        $availabilityTimestamp = null;

        if ($availability === 'differe') {
            $date = $parseDate((string) $getField('indice_date_disponibilite', $hintId));
            $availabilityTimestamp = $date instanceof DateTimeInterface ? $date->getTimestamp() : null;
        }

        $cacheUpdate = $this->cacheService->buildUpdate(
            $content !== '',
            !empty($imageId),
            $availability,
            $availabilityTimestamp,
            $currentTimestamp,
            $getPostStatus($hintId)
        );
        $updateField('indice_cache_complet', $cacheUpdate['complete'], $hintId);
        $updateField('indice_cache_etat_systeme', $cacheUpdate['state'], $hintId);

        if ($cacheUpdate['publication_status'] === null) {
            return $cacheUpdate;
        }

        $post = $getPost($hintId);
        if (!$post) {
            return $cacheUpdate;
        }

        $updatePost([
            'ID' => $hintId,
            'post_status' => $cacheUpdate['publication_status'],
            'post_date' => $post->post_date,
            'post_date_gmt' => $post->post_date_gmt,
            'edit_date' => true,
        ]);

        return $cacheUpdate;
    }
}
