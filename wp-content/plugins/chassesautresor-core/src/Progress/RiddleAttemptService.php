<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/**
 * Manage individual riddle attempts.
 */
class RiddleAttemptService
{
    private RiddleAttemptRepository $repository;

    public function __construct(RiddleAttemptRepository $repository)
    {
        $this->repository = $repository;
    }

    public function create(
        string $uid,
        int $userId,
        int $riddleId,
        string $answer,
        string $result,
        int $spentPoints,
        ?string $ipAddress,
        ?string $userAgent
    ): bool {
        if ($uid === '' || $userId <= 0 || $riddleId <= 0) {
            return false;
        }

        return $this->repository->insert([
            'tentative_uid' => $uid,
            'user_id' => $userId,
            'enigme_id' => $riddleId,
            'reponse_saisie' => $answer,
            'resultat' => $result,
            'points_utilises' => max(0, $spentPoints),
            'ip' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    public function findByUid(string $uid): ?object
    {
        $uid = trim($uid);

        return $uid !== '' ? $this->repository->findByUid($uid) : null;
    }

    public function getStateByUid(string $uid): string
    {
        $attempt = $this->findByUid($uid);

        return $this->getStateFromAttempt($attempt);
    }

    public function describeByUid(string $uid): ?array
    {
        $attempt = $this->findByUid($uid);
        if ($attempt === null) {
            return null;
        }

        $state = $this->getStateFromAttempt($attempt);
        $result = isset($attempt->resultat) ? (string) $attempt->resultat : '';
        $processed = (int) ($attempt->traitee ?? 0) === 1;

        return [
            'attempt' => $attempt,
            'state' => $state,
            'result' => $result,
            'processed' => $processed,
            'already_processed' => $state !== 'attente',
            'just_processed' => $processed && $state !== 'attente',
        ];
    }

    public function canViewAttempt(
        object $attempt,
        int $currentUserId,
        bool $isAdministrator,
        callable $isOrganizerForRiddle
    ): bool {
        if ($currentUserId <= 0) {
            return false;
        }

        if ((int) ($attempt->user_id ?? 0) === $currentUserId || $isAdministrator) {
            return true;
        }

        $riddleId = (int) ($attempt->enigme_id ?? 0);

        return $riddleId > 0 && (bool) $isOrganizerForRiddle($currentUserId, $riddleId);
    }

    public function canProcessManualAttempt(
        int $currentUserId,
        bool $isAdministrator,
        array $organizerUserIds
    ): bool {
        if ($currentUserId <= 0) {
            return false;
        }

        if ($isAdministrator) {
            return true;
        }

        return in_array($currentUserId, array_map('intval', $organizerUserIds), true);
    }

    private function getStateFromAttempt(?object $attempt): string
    {
        if ($attempt === null || !isset($attempt->resultat)) {
            return 'inexistante';
        }

        $states = [
            'attente' => 'attente',
            'bon' => 'validee',
            'faux' => 'refusee',
        ];

        return $states[$attempt->resultat] ?? 'invalide';
    }

    public function findForRiddle(int $riddleId, int $limit = 5, int $offset = 0): array
    {
        if ($riddleId <= 0 || $limit <= 0 || $offset < 0) {
            return [];
        }

        return $this->repository->findForRiddle($riddleId, $limit, $offset);
    }

    public function isRiddleSolvedForUser(int $userId, int $riddleId): bool
    {
        if ($userId <= 0 || $riddleId <= 0) {
            return false;
        }

        return $this->repository->findUserRiddleStatus($userId, $riddleId) === 'resolue';
    }

    public function processPending(string $uid, string $result): bool
    {
        $uid = trim($uid);
        if ($uid === '' || !in_array($result, ['bon', 'faux'], true)) {
            return false;
        }

        return $this->repository->markPendingAsProcessed($uid, $result);
    }

    public function hasSuccessfulAttempt(int $userId, int $riddleId): bool
    {
        if ($userId <= 0 || $riddleId <= 0) {
            return false;
        }

        return $this->repository->hasSuccessfulAttempt($userId, $riddleId);
    }

    public function canCreateAttempt(int $userId, int $riddleId, string $result): bool
    {
        if ($result !== 'bon') {
            return true;
        }

        return !$this->hasSuccessfulAttempt($userId, $riddleId);
    }

    public function getChargeAmount(int $configuredCost, bool $createAttempt): int
    {
        return $createAttempt ? max(0, $configuredCost) : 0;
    }

    public function buildProcessingPlan(
        int $userId,
        int $riddleId,
        string $result,
        int $configuredCost,
        bool $createAttempt,
        bool $notifyFailure
    ): ?array {
        if ($createAttempt && !$this->canCreateAttempt($userId, $riddleId, $result)) {
            return null;
        }

        return [
            'charge' => $this->getChargeAmount($configuredCost, $createAttempt),
            'outcome' => $this->getOutcome($result, $notifyFailure),
        ];
    }

    public function getOutcome(string $result, bool $notifyFailure = false): array
    {
        if ($result === 'bon') {
            return [
                'user_status' => 'resolue',
                'resolved' => true,
                'notify' => true,
            ];
        }

        if ($result === 'faux') {
            return [
                'user_status' => 'echouee',
                'resolved' => false,
                'notify' => $notifyFailure,
            ];
        }

        return [
            'user_status' => 'en_cours',
            'resolved' => false,
            'notify' => false,
        ];
    }

    public function countForRiddle(int $riddleId): int
    {
        return $riddleId > 0 ? $this->repository->countForRiddle($riddleId) : 0;
    }

    public function countPendingForRiddle(int $riddleId): int
    {
        return $riddleId > 0 ? $this->repository->countPendingForRiddle($riddleId) : 0;
    }

    public function findPendingManualRiddleIds(array $riddleModes): array
    {
        $pendingRiddleIds = [];

        foreach ($riddleModes as $riddleId => $mode) {
            $riddleId = (int) $riddleId;
            if ($riddleId <= 0 || $mode !== 'manuelle') {
                continue;
            }

            if ($this->repository->countPendingForRiddle($riddleId) > 0) {
                $pendingRiddleIds[] = $riddleId;
            }
        }

        return $pendingRiddleIds;
    }

    public function countTodayForUser(int $userId, int $riddleId, ?DateTimeInterface $now = null): int
    {
        if ($userId <= 0 || $riddleId <= 0) {
            return 0;
        }

        $timezone = new DateTimeZone('Europe/Paris');
        $current = $now === null
            ? new DateTimeImmutable('now', $timezone)
            : DateTimeImmutable::createFromInterface($now)->setTimezone($timezone);

        return $this->repository->countForUserAndRiddleBetween(
            $userId,
            $riddleId,
            $current->setTime(0, 0)->format('Y-m-d H:i:s'),
            $current->setTime(23, 59, 59)->format('Y-m-d H:i:s')
        );
    }
}
