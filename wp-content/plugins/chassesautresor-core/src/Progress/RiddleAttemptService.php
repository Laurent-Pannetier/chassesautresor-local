<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

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
}
