<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Normalize and validate mutable riddle fields.
 */
class RiddleFieldPolicyService {
    private const MAX_ANSWERS = 5;
    private const MAX_ANSWER_LENGTH = 75;

    public function isForbiddenAccessCondition(string $condition): bool {
        return $condition === 'pre_requis';
    }

    public function isAllowedManualAccessCondition(string $condition): bool {
        return in_array($condition, ['immediat', 'date_programmee'], true);
    }

    /**
     * @param mixed[] $answers
     * @return string[]
     */
    public function normalizeAnswers(array $answers, callable $sanitize): array {
        $normalized = array_map(
            static fn ($answer): string => (string) $sanitize($answer),
            $answers
        );

        return array_values(array_filter(
            $normalized,
            static fn (string $answer): bool => $answer !== ''
        ));
    }

    /**
     * @param string[] $answers
     */
    public function getAnswersError(array $answers): ?string {
        if (count($answers) > self::MAX_ANSWERS) {
            return 'trop_de_reponses';
        }

        foreach ($answers as $answer) {
            if (mb_strlen($answer) > self::MAX_ANSWER_LENGTH) {
                return 'longueur_max';
            }
        }

        return null;
    }

    /**
     * @return int[]
     */
    public function normalizePrerequisiteIds($value): array {
        $values = is_array($value) ? $value : explode(',', (string) $value);

        return array_values(array_filter(array_map('intval', $values)));
    }

    /**
     * @param int[] $prerequisiteIds
     */
    public function getAccessConditionForPrerequisites(array $prerequisiteIds): string {
        return $prerequisiteIds === [] ? 'immediat' : 'pre_requis';
    }
}
