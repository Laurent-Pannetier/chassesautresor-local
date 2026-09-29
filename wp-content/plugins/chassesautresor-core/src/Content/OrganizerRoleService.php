<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Evaluate organizer roles without depending on WordPress user objects.
 */
class OrganizerRoleService
{
    public function isOrganizer(
        array $roles,
        string $organizerRole,
        string $organizerCreationRole
    ): bool {
        return in_array($organizerRole, $roles, true)
            || in_array($organizerCreationRole, $roles, true);
    }
}
