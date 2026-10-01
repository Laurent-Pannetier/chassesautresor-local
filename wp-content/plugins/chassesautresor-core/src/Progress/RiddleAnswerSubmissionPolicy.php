<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Validate the reusable business rules that precede an answer submission.
 */
class RiddleAnswerSubmissionPolicy {
    /**
     * Return the historical AJAX error code, or null when submission is allowed.
     */
    public function validate(
        bool $loggedIn,
        bool $nonceValid,
        int $riddleId,
        string $postType,
        string $answer,
        string $systemState,
        string $userStatus,
        int $dailyLimit,
        int $attemptsToday,
        int $cost,
        int $balance,
        bool $automatic
    ): ?string {
        if (!$loggedIn) {
            return 'non_connecte';
        }

        if (!$nonceValid || $riddleId <= 0 || $postType !== 'enigme' || $answer === '') {
            return 'invalide';
        }

        $allowedStatuses = $automatic
            ? ['non_commencee', 'en_cours', 'abandonnee', 'echouee']
            : ['en_cours', 'echouee', 'abandonnee'];

        if ($systemState !== 'accessible' || !in_array($userStatus, $allowedStatuses, true)) {
            return in_array($userStatus, ['resolue', 'terminee'], true) ? 'deja_resolue' : 'interdit';
        }

        if ($dailyLimit > 0 && $attemptsToday >= $dailyLimit) {
            return 'tentatives_epuisees';
        }

        if (max(0, $cost) > $balance) {
            return 'points_insuffisants';
        }

        return null;
    }
}
