<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Site;

final class SiteExperienceService
{
    public const MODE_PLATFORM = 'platform';
    public const MODE_SINGLE_HUNT = 'single_hunt';
    public const MODE_DEMO = 'demo';
    public const OPTION_NAME = 'chassesautresor_site_experience';

    /**
     * @param array<string, mixed> $settings
     */
    public function isSingleHuntMode(array $settings): bool
    {
        return in_array(
            $settings['mode'] ?? self::MODE_SINGLE_HUNT,
            [self::MODE_SINGLE_HUNT, self::MODE_DEMO],
            true
        );
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function isDemoMode(array $settings): bool
    {
        return ($settings['mode'] ?? '') === self::MODE_DEMO;
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function areOrganizerApplicationsOpen(array $settings): bool
    {
        return !$this->isSingleHuntMode($settings)
            && !empty($settings['organizer_applications_open']);
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function getPrimaryHuntId(array $settings): int
    {
        return max(0, (int) ($settings['primary_hunt_id'] ?? 0));
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function canResetStatistics(bool $administrator, bool $loggedIn, array $settings): bool
    {
        return $administrator || ($loggedIn && $this->isDemoMode($settings));
    }

    /**
     * @param array<string, mixed> $settings
     *
     * @return array{mode:string,primary_hunt_id:int,organizer_applications_open:int}
     */
    public function sanitize(array $settings, callable $isHunt): array
    {
        $requestedMode = (string) ($settings['mode'] ?? '');
        $mode = in_array(
            $requestedMode,
            [self::MODE_PLATFORM, self::MODE_SINGLE_HUNT, self::MODE_DEMO],
            true
        ) ? $requestedMode : self::MODE_SINGLE_HUNT;
        $primaryHuntId = max(0, (int) ($settings['primary_hunt_id'] ?? 0));

        if ($primaryHuntId > 0 && !$isHunt($primaryHuntId)) {
            $primaryHuntId = 0;
        }

        return [
            'mode' => $mode,
            'primary_hunt_id' => $primaryHuntId,
            'organizer_applications_open' => $mode === self::MODE_PLATFORM
                && !empty($settings['organizer_applications_open'])
                ? 1
                : 0,
        ];
    }
}
