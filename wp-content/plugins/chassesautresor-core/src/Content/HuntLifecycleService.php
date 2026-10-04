<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Pure decisions for the profile-home Edit / Activate switch.
 */
final class HuntLifecycleService
{
    public const STATE_EDITABLE = 'editable';
    public const STATE_PENDING = 'pending';
    public const STATE_ACTIVE = 'active';
    public const STATE_BLOCKED = 'blocked';

    public function resolveState(string $validationStatus): string
    {
        if (in_array($validationStatus, ['valide', 'active'], true)) {
            return self::STATE_ACTIVE;
        }
        if ($validationStatus === 'en_attente') {
            return self::STATE_PENDING;
        }
        if (in_array($validationStatus, ['creation', 'correction'], true)) {
            return self::STATE_EDITABLE;
        }

        return self::STATE_BLOCKED;
    }

    public function isSwitchOn(string $state): bool
    {
        return $state === self::STATE_ACTIVE;
    }

    /**
     * Whether the actor may turn the switch ON (activate / request activation).
     */
    public function canActivate(
        bool $isAdministrator,
        bool $isAssociatedOrganizer,
        bool $isDemoMode,
        string $state,
        bool $canRequestValidation
    ): bool {
        if (!($isAdministrator || $isAssociatedOrganizer)) {
            return false;
        }

        if ($state === self::STATE_ACTIVE || $state === self::STATE_BLOCKED) {
            return false;
        }

        if ($isDemoMode) {
            return $state === self::STATE_EDITABLE || $state === self::STATE_PENDING;
        }

        if ($isAdministrator && $state === self::STATE_PENDING) {
            return true;
        }

        if ($isAssociatedOrganizer && $state === self::STATE_EDITABLE && $canRequestValidation) {
            return true;
        }

        return false;
    }

    /**
     * Whether the actor may turn the switch OFF (edit / cancel request).
     */
    public function canDeactivate(
        bool $isAdministrator,
        bool $isAssociatedOrganizer,
        bool $isDemoMode,
        string $state
    ): bool {
        if (!($isAdministrator || $isAssociatedOrganizer)) {
            return false;
        }

        if ($state === self::STATE_EDITABLE || $state === self::STATE_BLOCKED) {
            return false;
        }

        if ($isDemoMode) {
            return true;
        }

        if ($state === self::STATE_PENDING) {
            return true;
        }

        // Outside demo, only administrators may reopen an active hunt for edition.
        return $isAdministrator && $state === self::STATE_ACTIVE;
    }

    /**
     * Action to execute when toggling toward Activate.
     *
     * @return 'request'|'validate'|null
     */
    public function activateAction(bool $isDemoMode, bool $isAdministrator, string $state): ?string
    {
        if ($isDemoMode || ($isAdministrator && $state === self::STATE_PENDING)) {
            return 'validate';
        }

        if ($state === self::STATE_EDITABLE) {
            return 'request';
        }

        return null;
    }

    /**
     * Action to execute when toggling toward Edit.
     *
     * @return 'cancel'|'reopen'|null
     */
    public function deactivateAction(string $state): ?string
    {
        if ($state === self::STATE_PENDING) {
            return 'cancel';
        }
        if ($state === self::STATE_ACTIVE) {
            return 'reopen';
        }

        return null;
    }
}
