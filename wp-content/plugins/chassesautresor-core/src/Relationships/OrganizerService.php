<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Relationships;

/**
 * Resolve organizer relationships for user accounts.
 */
class OrganizerService
{
    private OrganizerRepository $repository;

    public function __construct(OrganizerRepository $repository)
    {
        $this->repository = $repository;
    }

    public function findIdForUser(int $userId): ?int
    {
        return $userId > 0 ? $this->repository->findIdForUser($userId) : null;
    }
}
