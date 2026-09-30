<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Prepare normalized data used by hint management AJAX responses.
 */
class HintManagementService {
    /**
     * @return array{page:int,pages:int}
     */
    public function paginate(int $requestedPage, int $totalItems, int $perPage): array {
        $perPage = max(1, $perPage);
        $pages = (int) ceil(max(0, $totalItems) / $perPage);
        $page = max(1, $requestedPage);

        if ($pages > 0) {
            $page = min($page, $pages);
        }

        return ['page' => $page, 'pages' => $pages];
    }

    /**
     * @param array<int, mixed> $hintIds
     * @param callable(int): string $getTargetType
     * @return array{total:int,hunt:int,riddle:int}
     */
    public function countByTargetType(array $hintIds, callable $getTargetType): array {
        $huntCount = 0;
        $riddleCount = 0;

        foreach ($hintIds as $hintId) {
            $targetType = $getTargetType((int) $hintId);
            if ($targetType === 'chasse') {
                ++$huntCount;
            } elseif ($targetType === 'enigme') {
                ++$riddleCount;
            }
        }

        return [
            'total' => $huntCount + $riddleCount,
            'hunt' => $huntCount,
            'riddle' => $riddleCount,
        ];
    }

    /**
     * @param array<int, object> $riddles
     * @param callable(object): bool $excludeRiddle
     * @param callable(object): string $getTitle
     * @return array<int, array{id:int,title:string,indice_rang:int}>
     */
    public function buildRiddleOptions(
        array $riddles,
        int $nextRank,
        callable $excludeRiddle,
        callable $getTitle
    ): array {
        $options = [];
        foreach ($riddles as $riddle) {
            if ($excludeRiddle($riddle)) {
                continue;
            }

            $options[] = [
                'id' => (int) $riddle->ID,
                'title' => html_entity_decode($getTitle($riddle), ENT_QUOTES, 'UTF-8'),
                'indice_rang' => max(1, $nextRank),
            ];
        }

        return $options;
    }
}
