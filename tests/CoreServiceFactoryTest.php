<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Messages\AccountMessageService;
use ChassesAuTresor\Core\Media\RiddleImageService;
use ChassesAuTresor\Core\Points\ConversionService;
use ChassesAuTresor\Core\Points\PointsService;
use ChassesAuTresor\Core\Progress\HintUnlockService;
use ChassesAuTresor\Core\Progress\HuntProgressService;
use ChassesAuTresor\Core\Progress\RiddleAttemptService;
use ChassesAuTresor\Core\Progress\RiddleRetryPolicyService;
use ChassesAuTresor\Core\Progress\RiddleSidebarStatisticsService;
use ChassesAuTresor\Core\Relationships\OrganizerService;
use ChassesAuTresor\Core\Support\CoreServiceFactory;
use PHPUnit\Framework\TestCase;

final class CoreServiceFactoryTest extends TestCase
{
    public function testItBuildsDatabaseBackedApplicationServices(): void
    {
        $database = new class {
            public string $prefix = 'wp_';
        };

        self::assertInstanceOf(PointsService::class, CoreServiceFactory::points($database));
        self::assertInstanceOf(ConversionService::class, CoreServiceFactory::conversion($database));
        self::assertInstanceOf(HuntProgressService::class, CoreServiceFactory::huntProgress($database));
        self::assertInstanceOf(RiddleAttemptService::class, CoreServiceFactory::riddleAttempts($database));
        self::assertInstanceOf(RiddleRetryPolicyService::class, CoreServiceFactory::riddleRetry($database));
        self::assertInstanceOf(HintUnlockService::class, CoreServiceFactory::hintUnlock($database));
        self::assertInstanceOf(OrganizerService::class, CoreServiceFactory::organizer($database));
        self::assertInstanceOf(AccountMessageService::class, CoreServiceFactory::accountMessages($database));
        self::assertInstanceOf(RiddleImageService::class, CoreServiceFactory::riddleImages($database));
        self::assertInstanceOf(
            \ChassesAuTresor\Core\Media\RiddleStepImageService::class,
            CoreServiceFactory::riddleStepImages($database)
        );
        self::assertInstanceOf(
            RiddleSidebarStatisticsService::class,
            CoreServiceFactory::riddleSidebarStatistics($database)
        );
    }
}
