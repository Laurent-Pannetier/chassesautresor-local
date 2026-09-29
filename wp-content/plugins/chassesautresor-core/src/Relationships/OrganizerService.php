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
     * Normalize the different return formats supported by ACF relationship fields.
     *
     * @param mixed $value
     */
    public function normalizeId($value): ?int
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        if (is_object($value) && isset($value->ID)) {
            $value = $value->ID;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $organizerId = (int) $value;

        return $organizerId > 0 ? $organizerId : null;
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
