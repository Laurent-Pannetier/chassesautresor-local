<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Classify hunt riddles according to their validation mode.
 */
class HuntRiddleClassifier
{
    /**
     * @param array<int, int|string> $riddleIds
     * @return array{validatable:int[],engagement_only:int[]}
     */
    public function classify(array $riddleIds): array
    {
        $validatable = [];
        $engagementOnly = [];

        foreach ($riddleIds as $riddleId) {
            $riddleId = (int) $riddleId;

            if ($riddleId <= 0) {
                continue;
            }

            if ($this->getValidationMode($riddleId) === 'aucune') {
                $engagementOnly[] = $riddleId;
            } else {
                $validatable[] = $riddleId;
            }
        }

        return [
            'validatable' => $validatable,
            'engagement_only' => $engagementOnly,
        ];
    }

    protected function getValidationMode(int $riddleId): string
    {
        return (string) get_field('enigme_mode_validation', $riddleId);
    }
}
