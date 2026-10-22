<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

/**
 * Decide whether a signed riddle-image URL may be delivered.
 *
 * The signature stays bound to the viewer id embedded when the page was
 * rendered. That id does not have to be the current session: a shared cache
 * can replay an organizer URL to a player. Those vignettes then fail together
 * until a purge, such as the tools reset, stores a fresh page. The current
 * viewer is authorized separately, after this check.
 */
final class ProtectedRiddleImageRequestPolicy
{
    public function acceptsSignature(bool $signatureValid, int $signedUserId, int $currentUserId): bool
    {
        if ($signedUserId < 0 || $currentUserId < 0) {
            return false;
        }

        return $signatureValid;
    }
}
