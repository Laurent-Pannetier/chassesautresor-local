<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Resolve the presentation state of organizer account navigation entries.
 */
class OrganizerNavigationService
{
    public function getOrganizerClasses(bool $complete, string $postStatus): string
    {
        if (!$complete) {
            return 'dashboard-nav-link status-important';
        }

        return 'dashboard-nav-link ' . ($postStatus === 'pending' ? 'status-pending' : 'status-published');
    }

    /**
     * @return array{classes:string,pending_icon:bool}|null
     */
    public function getHuntPresentation(
        bool $complete,
        string $postStatus,
        string $validationStatus,
        bool $canValidate
    ): ?array {
        if ($postStatus === 'pending' && $validationStatus === 'banni') {
            return null;
        }

        $classes = 'dashboard-nav-sublink';
        $pendingIcon = false;

        if (!$complete) {
            $classes .= ' status-important';
        } elseif ($postStatus === 'publish' && $validationStatus === 'valide') {
            $classes .= ' status-published';
        } else {
            $classes .= ' status-pending';
            $pendingIcon = $postStatus === 'pending' && $validationStatus === 'en_attente';
        }

        if ($canValidate) {
            $classes .= ' status-eligible';
        }

        return ['classes' => $classes, 'pending_icon' => $pendingIcon];
    }

    public function getRiddleClasses(
        bool $complete,
        string $postStatus,
        string $systemState,
        bool $hasPendingAttempt
    ): ?string {
        $classes = 'dashboard-nav-subitem';
        if (!$complete) {
            return $classes . ' status-important';
        }

        if ($postStatus === 'pending' && in_array($systemState, ['invalide', 'cache_invalide'], true)) {
            return null;
        }

        if ($postStatus === 'publish' && $systemState === 'accessible') {
            $classes .= ' status-published';
        } else {
            $classes .= ' status-pending';
        }

        return $classes . ($hasPendingAttempt ? ' status-important' : '');
    }
}
