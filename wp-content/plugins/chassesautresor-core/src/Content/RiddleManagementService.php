<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Evaluate whether a user can add, edit, or remove riddles from a hunt.
 */
class RiddleManagementService
{
    public const MAX_RIDDLES_PER_HUNT = 40;

    /**
     * @return array{champ:string,valeur:mixed,complet:bool,complet_changed:bool,chasse_id:int}
     */
    public function getFieldUpdateResponse(
        string $field,
        $value,
        bool $wasComplete,
        bool $isComplete,
        int $huntId
    ): array {
        return [
            'champ' => $field,
            'valeur' => $value,
            'complet' => $isComplete,
            'complet_changed' => $wasComplete !== $isComplete,
            'chasse_id' => max(0, $huntId),
        ];
    }

    /**
     * @param int[] $submittedIds
     * @param int[] $huntRiddleIds
     * @return array<int, int> Riddle IDs indexed by their new menu order.
     */
    public function getReorderUpdates(array $submittedIds, array $huntRiddleIds): array
    {
        $allowedIds = array_fill_keys(
            array_filter(array_map('intval', $huntRiddleIds), static fn(int $id): bool => $id > 0),
            true
        );
        $updates = [];
        $seen = [];

        foreach ($submittedIds as $submittedId) {
            $riddleId = (int) $submittedId;
            if ($riddleId <= 0 || !isset($allowedIds[$riddleId]) || isset($seen[$riddleId])) {
                continue;
            }

            $updates[] = $riddleId;
            $seen[$riddleId] = true;
        }

        return $updates;
    }

    public function canAdd(
        bool $isHunt,
        bool $isAuthenticated,
        bool $isOrganizer,
        string $huntPublicationStatus,
        string $huntStatus,
        string $validationStatus,
        bool $isAssociatedOrganizer,
        int $riddleCount
    ): bool {
        return $isHunt
            && $isAuthenticated
            && $isOrganizer
            && $huntPublicationStatus !== 'publish'
            && $huntStatus === 'revision'
            && in_array($validationStatus, ['creation', 'correction'], true)
            && $isAssociatedOrganizer
            && $riddleCount < self::MAX_RIDDLES_PER_HUNT;
    }

    public function canDelete(
        bool $isRiddle,
        bool $isAuthenticated,
        bool $isOrganizer,
        bool $hasHunt,
        string $huntStatus,
        string $validationStatus,
        bool $isAssociatedOrganizer
    ): bool {
        return $isRiddle
            && $isAuthenticated
            && $isOrganizer
            && $hasHunt
            && $huntStatus === 'revision'
            && in_array($validationStatus, ['creation', 'correction'], true)
            && $isAssociatedOrganizer;
    }

    public function canEdit(
        bool $isRiddle,
        bool $isAdministrator,
        bool $hasHunt,
        bool $isAssociatedOrganizer
    ): bool {
        if (!$isRiddle) {
            return false;
        }

        return $isAdministrator || ($hasHunt && $isAssociatedOrganizer);
    }
}
