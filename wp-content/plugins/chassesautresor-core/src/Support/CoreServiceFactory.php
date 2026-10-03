<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Support;

use ChassesAuTresor\Core\Messages\AccountMessageService;
use ChassesAuTresor\Core\Messages\SiteMessageService;
use ChassesAuTresor\Core\Messages\UserMessageRepository;
use ChassesAuTresor\Core\Media\RiddleImageRepository;
use ChassesAuTresor\Core\Media\RiddleImageService;
use ChassesAuTresor\Core\Media\RiddleStepImageRepository;
use ChassesAuTresor\Core\Media\RiddleStepImageService;
use ChassesAuTresor\Core\Points\ConversionService;
use ChassesAuTresor\Core\Points\PointsRepository;
use ChassesAuTresor\Core\Points\PointsService;
use ChassesAuTresor\Core\Points\PurchasePointsService;
use ChassesAuTresor\Core\Progress\HintUnlockRepository;
use ChassesAuTresor\Core\Progress\HintUnlockService;
use ChassesAuTresor\Core\Progress\HuntEngagementRepository;
use ChassesAuTresor\Core\Progress\HuntEngagementService;
use ChassesAuTresor\Core\Progress\HuntProgressRepository;
use ChassesAuTresor\Core\Progress\HuntProgressService;
use ChassesAuTresor\Core\Progress\HuntStatisticsRepository;
use ChassesAuTresor\Core\Progress\HuntStatisticsService;
use ChassesAuTresor\Core\Progress\HuntWinnerRepository;
use ChassesAuTresor\Core\Progress\RiddleAttemptRepository;
use ChassesAuTresor\Core\Progress\RiddleAttemptService;
use ChassesAuTresor\Core\Progress\RiddleRetryConfiguration;
use ChassesAuTresor\Core\Progress\RiddleRetryPolicyService;
use ChassesAuTresor\Core\Progress\RiddleRetryRepository;
use ChassesAuTresor\Core\Progress\RiddleEngagementRepository;
use ChassesAuTresor\Core\Progress\RiddleEngagementService;
use ChassesAuTresor\Core\Progress\RiddleStatisticsRepository;
use ChassesAuTresor\Core\Progress\RiddleStatisticsService;
use ChassesAuTresor\Core\Progress\RiddleStepProgressRepository;
use ChassesAuTresor\Core\Progress\RiddleStepProgressService;
use ChassesAuTresor\Core\Progress\RiddleSidebarStatisticsService;
use ChassesAuTresor\Core\Progress\UserAttemptStatisticsRepository;
use ChassesAuTresor\Core\Progress\UserAttemptStatisticsService;
use ChassesAuTresor\Core\Relationships\OrganizerRepository;
use ChassesAuTresor\Core\Relationships\OrganizerService;

final class CoreServiceFactory
{
    public static function points(object $database): PointsService
    {
        return new PointsService(new PointsRepository($database));
    }

    public static function purchasePoints(object $database): PurchasePointsService
    {
        return new PurchasePointsService(self::points($database));
    }

    public static function conversion(object $database): ConversionService
    {
        $repository = new PointsRepository($database);
        return new ConversionService($repository, new PointsService($repository));
    }

    public static function huntProgress(object $database): HuntProgressService
    {
        return new HuntProgressService(new HuntProgressRepository($database));
    }

    public static function huntEngagement(object $database): HuntEngagementService
    {
        return new HuntEngagementService(new HuntEngagementRepository($database));
    }

    public static function huntStatistics(object $database): HuntStatisticsService
    {
        return new HuntStatisticsService(new HuntStatisticsRepository($database));
    }

    public static function huntWinners(object $database): HuntWinnerRepository
    {
        return new HuntWinnerRepository($database);
    }

    public static function riddleEngagement(object $database): RiddleEngagementService
    {
        return new RiddleEngagementService(new RiddleEngagementRepository($database));
    }

    public static function riddleAttempts(object $database): RiddleAttemptService
    {
        return new RiddleAttemptService(new RiddleAttemptRepository($database));
    }

    public static function riddleRetry(object $database): RiddleRetryPolicyService
    {
        return new RiddleRetryPolicyService(
            new RiddleRetryRepository($database),
            new RiddleRetryConfiguration()
        );
    }

    public static function riddleStepProgress(object $database): RiddleStepProgressService
    {
        return new RiddleStepProgressService(new RiddleStepProgressRepository($database));
    }

    public static function riddleStatistics(object $database): RiddleStatisticsService
    {
        return new RiddleStatisticsService(new RiddleStatisticsRepository($database));
    }

    public static function riddleSidebarStatistics(object $database): RiddleSidebarStatisticsService
    {
        return new RiddleSidebarStatisticsService(
            self::huntProgress($database),
            self::huntEngagement($database),
            self::huntStatistics($database),
            self::riddleStatistics($database)
        );
    }

    public static function userAttemptStatistics(object $database): UserAttemptStatisticsService
    {
        return new UserAttemptStatisticsService(new UserAttemptStatisticsRepository($database));
    }

    public static function hintUnlock(object $database): HintUnlockService
    {
        return new HintUnlockService(
            new HintUnlockRepository($database),
            self::points($database)
        );
    }

    public static function organizer(object $database): OrganizerService
    {
        return new OrganizerService(new OrganizerRepository($database));
    }

    public static function siteMessages(object $database): SiteMessageService
    {
        return new SiteMessageService(new UserMessageRepository($database));
    }

    public static function accountMessages(object $database): AccountMessageService
    {
        return new AccountMessageService(new UserMessageRepository($database));
    }

    public static function riddleImages(object $database): RiddleImageService
    {
        return new RiddleImageService(new RiddleImageRepository($database));
    }

    public static function riddleStepImages(object $database): RiddleStepImageService
    {
        return new RiddleStepImageService(new RiddleStepImageRepository($database));
    }
}
