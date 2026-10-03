<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Normalize and validate mutable riddle fields.
 */
class RiddleFieldPolicyService {
    private const MAX_ANSWERS = 5;
    private const MAX_ANSWER_LENGTH = 75;

    public function isEditableField(string $field): bool {
        return in_array($field, [
            'post_title',
            'enigme_visuel_image',
            'enigme_visuel_legende',
            'enigme_visuel_texte',
            'enigme_mode_validation',
            'enigme_reponse_bonne',
            'enigme_reponse_casse',
            'enigme_tentative_cout_points',
            'enigme_tentative.enigme_tentative_cout_points',
            'enigme_tentative_delai_secondes',
            'enigme_tentative.enigme_tentative_delai_secondes',
            'enigme_acces_condition',
            'enigme_acces_date',
            'enigme_acces_pre_requis',
            'enigme_style_affichage',
        ], true);
    }

    public function isForbiddenAccessCondition(string $condition): bool {
        return $condition === 'pre_requis';
    }

    public function isAllowedManualAccessCondition(string $condition): bool {
        return in_array($condition, ['immediat', 'date_programmee'], true);
    }

    public function getAttemptStorageField(string $submittedField): ?string {
        $fields = [
            'enigme_tentative.enigme_tentative_cout_points' => 'enigme_tentative_cout_points',
            'enigme_tentative.enigme_tentative_delai_secondes' => 'enigme_tentative_delai_secondes',
        ];

        return $fields[$submittedField] ?? null;
    }

    public function shouldResetScheduledAccess(int $timestamp, int $startOfToday, string $condition): bool {
        return $condition === 'date_programmee' && $timestamp < $startOfToday;
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
