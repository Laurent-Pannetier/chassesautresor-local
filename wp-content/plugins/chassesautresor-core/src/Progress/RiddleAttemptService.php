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

    public function countForRiddle(int $riddleId): int
    {
        return $riddleId > 0 ? $this->repository->countForRiddle($riddleId) : 0;
    }

    public function countPendingForRiddle(int $riddleId): int
    {
        return $riddleId > 0 ? $this->repository->countPendingForRiddle($riddleId) : 0;
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
