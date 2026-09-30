<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Initialize the relationships and defaults that must exist when a hunt is saved.
 */
class HuntInitializationService {
    public const DEFAULT_END_DATE_OFFSET = '+2 years';

    /**
     * @param callable(int): int $findOrganizer
     * @param callable(int, int): bool $persistOrganizer
     * @param callable(int): mixed $readEndDate
     * @param callable(int, string): bool $persistEndDate
     * @return array{handled: bool, organizer_id: int, organizer_persisted: bool|null, end_date: string|null}
     */
    public function initialize(
        int $huntId,
        string $postType,
        bool $isAutosave,
        int $currentTimestamp,
        callable $findOrganizer,
        callable $persistOrganizer,
        callable $readEndDate,
        callable $persistEndDate
    ): array {
        $result = [
            'handled' => false,
            'organizer_id' => 0,
            'organizer_persisted' => null,
            'end_date' => null,
        ];

        if ($huntId <= 0 || $postType !== 'chasse' || $isAutosave) {
            return $result;
        }

        $result['handled'] = true;
        $organizerId = (int) $findOrganizer($huntId);
        $result['organizer_id'] = $organizerId;
        if ($organizerId > 0) {
            $result['organizer_persisted'] = (bool) $persistOrganizer($huntId, $organizerId);
        }

        if (!$readEndDate($huntId)) {
            $endDate = date('Y-m-d', strtotime(self::DEFAULT_END_DATE_OFFSET, $currentTimestamp));
            if ($persistEndDate($huntId, $endDate)) {
                $result['end_date'] = $endDate;
            }
        }

        return $result;
    }
}
