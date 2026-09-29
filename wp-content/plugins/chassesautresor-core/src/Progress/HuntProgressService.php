<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Calculate hunt progress from validatable and engagement-only riddles.
 */
class HuntProgressService
{
    private HuntProgressRepository $repository;

    public function __construct(HuntProgressRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param int[] $validatable
     * @param int[] $engagementOnly
     * @return array{completed:int,total:int,is_complete:bool}
     */
    public function calculate(int $userId, array $validatable, array $engagementOnly): array
    {
        if ($userId <= 0) {
            return ['completed' => 0, 'total' => 0, 'is_complete' => false];
        }

        $solved = $this->repository->countSolved($userId, $validatable);
        $engaged = $this->repository->countEngaged($userId, $engagementOnly);
        $total = count($validatable) + count($engagementOnly);
        $completed = $solved + $engaged;

        return [
            'completed' => $completed,
            'total' => $total,
            'is_complete' => $total > 0 && $completed === $total,
        ];
    }

    public function getRiddleStatus(int $userId, int $riddleId): ?string
    {
        if ($userId <= 0 || $riddleId <= 0) {
            return null;
        }

        return $this->repository->findStatus($userId, $riddleId);
    }

    public function getRiddleResolutionDate(int $userId, int $riddleId): ?string
    {
        if ($userId <= 0 || $riddleId <= 0) {
            return null;
        }

        return $this->repository->findResolutionDate($userId, $riddleId);
    }

    public function advanceRiddleStatus(
        int $userId,
        int $riddleId,
        string $newStatus,
        string $updatedAt,
        bool $force = false
    ): bool {
        $newStatus = $this->normalizeStatus($newStatus);
        $priorities = [
            'non_commencee' => 0,
            'soumis' => 1,
            'en_cours' => 2,
            'abandonnee' => 3,
            'echouee' => 4,
            'resolue' => 5,
            'terminee' => 6,
        ];
        if ($userId <= 0 || $riddleId <= 0 || !isset($priorities[$newStatus])) {
            return false;
        }

        $storedStatus = $this->repository->findStatus($userId, $riddleId);
        $currentStatus = $storedStatus !== null ? $this->normalizeStatus($storedStatus) : null;
        if (!$force && in_array($currentStatus, ['resolue', 'terminee'], true)) {
            return false;
        }
        if (!$force && $priorities[$newStatus] <= ($priorities[$currentStatus] ?? 0)) {
            return false;
        }

        $this->repository->persistStatus($userId, $riddleId, $newStatus, $updatedAt, $storedStatus !== null);
        return true;
    }

    private function normalizeStatus(string $status): string
    {
        return strtolower(strtr(trim($status), [
            'à' => 'a',
            'â' => 'a',
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'î' => 'i',
            'ï' => 'i',
            'ô' => 'o',
            'ù' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ç' => 'c',
        ]));
    }

    /**
     * @param int[] $validatable
     * @param int[] $engagementOnly
     * @return object[]
     */
    public function getCompletedUsers(array $validatable, array $engagementOnly): array
    {
        return $this->repository->findCompletedUsers($validatable, $engagementOnly);
    }

    /** @param int[] $riddleIds */
    public function countSolvedRiddles(int $userId, array $riddleIds): int
    {
        if ($userId <= 0) {
            return 0;
        }

        return $this->repository->countSolved($userId, $riddleIds);
    }

    /** @param int[] $riddleIds */
    public function countEngagedRiddles(int $userId, array $riddleIds): int
    {
        if ($userId <= 0) {
            return 0;
        }

        return $this->repository->countEngaged($userId, $riddleIds);
    }

    /** @param int[] $riddleIds */
    public function countValidatableRiddles(array $riddleIds): int
    {
        return $this->repository->countValidatable($riddleIds);
    }

    /**
     * @param int[] $riddleIds
     * @return array<int, int[]> User IDs indexed by riddle ID.
     */
    public function completeRiddles(array $riddleIds, string $completedAt): array
    {
        return $this->repository->completeRiddles($riddleIds, $completedAt);
    }
}
