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

    /**
     * Determine whether a user belongs to an organizer's associated-user list.
     *
     * @param mixed[] $users
     */
    public function isUserAssociated(int $userId, array $users): bool
    {
        if ($userId <= 0) {
            return false;
        }

        foreach ($users as $user) {
            if (is_object($user) && isset($user->ID)) {
                $associatedUserId = (int) $user->ID;
            } elseif (is_numeric($user)) {
                $associatedUserId = (int) $user;
            } else {
                continue;
            }

            if ($associatedUserId === $userId) {
                return true;
            }
        }

        return false;
    }
}
