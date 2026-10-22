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
        bool $automatic,
        bool $finalAnswerUnlocked = true
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

        if (!$finalAnswerUnlocked) {
            return 'etapes_incompletes';
        }

        if ($dailyLimit > 0 && $attemptsToday >= $dailyLimit) {
            return 'tentatives_epuisees';
        }

        if (max(0, $cost) > $balance) {
            return 'points_insuffisants';
        }

        return null;
    }

    /**
     * A player with no status row has not started, as on the riddle page.
     */
    public function effectiveUserStatus(?string $storedStatus): string {
        if ($storedStatus === null || trim($storedStatus) === '') {
            return 'non_commencee';
        }

        return $this->normalizeStatus($storedStatus);
    }

    /**
     * Match the riddle page: a missing cache is accessible, and satisfied
     * prerequisites unlock a riddle still cached as bloquee_pre_requis.
     */
    public function effectiveSystemState(?string $storedState, bool $prerequisitesMet = false): string {
        if ($storedState === null) {
            return 'accessible';
        }

        if ($storedState === 'bloquee_pre_requis' && $prerequisitesMet) {
            return 'accessible';
        }

        return $storedState;
    }

    private function normalizeStatus(string $status): string {
        $status = strtr(trim($status), [
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ä' => 'A',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a',
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'Î' => 'I', 'Ï' => 'I', 'î' => 'i', 'ï' => 'i',
            'Ô' => 'O', 'Ö' => 'O', 'ô' => 'o', 'ö' => 'o',
            'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'Ç' => 'C', 'ç' => 'c',
        ]);

        return function_exists('mb_strtolower') ? mb_strtolower($status) : strtolower($status);
    }
}
