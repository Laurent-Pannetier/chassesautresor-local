<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Site\SiteExperienceService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Site/SiteExperienceService.php';

final class SiteExperienceServiceTest extends TestCase
{
    public function testSingleHuntModeAndClosedApplicationsAreSafeDefaults(): void
    {
        $service = new SiteExperienceService();

        self::assertTrue($service->isSingleHuntMode([]));
        self::assertFalse($service->areOrganizerApplicationsOpen([]));
        self::assertSame(0, $service->getPrimaryHuntId([]));
    }

    public function testApplicationsCanOnlyOpenInPlatformMode(): void
    {
        $service = new SiteExperienceService();

        self::assertFalse($service->areOrganizerApplicationsOpen([
            'mode' => SiteExperienceService::MODE_SINGLE_HUNT,
            'organizer_applications_open' => 1,
        ]));
        self::assertTrue($service->areOrganizerApplicationsOpen([
            'mode' => SiteExperienceService::MODE_PLATFORM,
            'organizer_applications_open' => 1,
        ]));
    }

    public function testDemoModeUsesSingleHuntPresentationAndKeepsApplicationsClosed(): void
    {
        $service = new SiteExperienceService();
        $settings = [
            'mode' => SiteExperienceService::MODE_DEMO,
            'organizer_applications_open' => 1,
        ];

        self::assertTrue($service->isDemoMode($settings));
        self::assertTrue($service->isSingleHuntMode($settings));
        self::assertFalse($service->areOrganizerApplicationsOpen($settings));
    }

    public function testStatisticsResetIsAvailableToLoggedInDemoUsersAndAdministratorsOnly(): void
    {
        $service = new SiteExperienceService();
        $demo = ['mode' => SiteExperienceService::MODE_DEMO];
        $singleHunt = ['mode' => SiteExperienceService::MODE_SINGLE_HUNT];

        self::assertTrue($service->canResetStatistics(false, true, $demo));
        self::assertFalse($service->canResetStatistics(false, false, $demo));
        self::assertFalse($service->canResetStatistics(false, true, $singleHunt));
        self::assertTrue($service->canResetStatistics(true, true, $singleHunt));
    }

    public function testSanitizeRejectsAnInvalidPrimaryHuntAndClosesSingleHuntApplications(): void
    {
        $service = new SiteExperienceService();
        $settings = $service->sanitize(
            [
                'mode' => SiteExperienceService::MODE_SINGLE_HUNT,
                'primary_hunt_id' => 42,
                'organizer_applications_open' => 1,
            ],
            static fn(int $postId): bool => $postId === 7
        );

        self::assertSame([
            'mode' => SiteExperienceService::MODE_SINGLE_HUNT,
            'primary_hunt_id' => 0,
            'organizer_applications_open' => 0,
        ], $settings);
    }

    public function testSanitizeKeepsAValidPlatformConfiguration(): void
    {
        $service = new SiteExperienceService();
        $settings = $service->sanitize(
            [
                'mode' => SiteExperienceService::MODE_PLATFORM,
                'primary_hunt_id' => 42,
                'organizer_applications_open' => 1,
            ],
            static fn(int $postId): bool => $postId === 42
        );

        self::assertSame([
            'mode' => SiteExperienceService::MODE_PLATFORM,
            'primary_hunt_id' => 42,
            'organizer_applications_open' => 1,
        ], $settings);
    }

    public function testSanitizeKeepsDemoModeWithAValidDraftHunt(): void
    {
        $service = new SiteExperienceService();
        $settings = $service->sanitize(
            [
                'mode' => SiteExperienceService::MODE_DEMO,
                'primary_hunt_id' => 84,
                'organizer_applications_open' => 1,
            ],
            static fn(int $postId): bool => $postId === 84
        );

        self::assertSame([
            'mode' => SiteExperienceService::MODE_DEMO,
            'primary_hunt_id' => 84,
            'organizer_applications_open' => 0,
        ], $settings);
    }
}
