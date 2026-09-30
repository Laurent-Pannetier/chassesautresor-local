<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Normalize editable solution fields independently from the HTTP transport.
 */
class SolutionFieldPolicyService {
    public function hasRequiredContent(bool $hasFile, string $explanation): bool {
        return $hasFile || trim($explanation) !== '';
    }

    public function hasConsistentRiddleTarget(string $targetType, int $targetId, int $linkedRiddleId): bool {
        return $targetType !== 'enigme' || ($targetId > 0 && $linkedRiddleId === $targetId);
    }

    /**
     * @return array{availability:string, delay_days:int, publication_time:string}
     */
    public function normalizeSchedule(string $availability, int $delayDays, string $publicationTime): array {
        return [
            'availability' => $availability === 'differee' ? 'differee' : 'fin_chasse',
            'delay_days' => $delayDays,
            'publication_time' => $publicationTime !== '' ? $publicationTime : '00:00',
        ];
    }
}
