<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntLifecycleService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntLifecycleService.php';

final class HuntLifecycleServiceTest extends TestCase
{
    public function testResolvesStatesFromValidationStatus(): void
    {
        $service = new HuntLifecycleService();

        self::assertSame(HuntLifecycleService::STATE_EDITABLE, $service->resolveState('creation'));
        self::assertSame(HuntLifecycleService::STATE_EDITABLE, $service->resolveState('correction'));
        self::assertSame(HuntLifecycleService::STATE_PENDING, $service->resolveState('en_attente'));
        self::assertSame(HuntLifecycleService::STATE_ACTIVE, $service->resolveState('valide'));
        self::assertSame(HuntLifecycleService::STATE_BLOCKED, $service->resolveState('banni'));
    }

    public function testDemoAllowsFreeActivateAndDeactivate(): void
    {
        $service = new HuntLifecycleService();

        self::assertTrue($service->canActivate(
            false,
            true,
            true,
            HuntLifecycleService::STATE_EDITABLE,
            false
        ));
        self::assertTrue($service->canDeactivate(
            false,
            true,
            true,
            HuntLifecycleService::STATE_ACTIVE
        ));
        self::assertSame('validate', $service->activateAction(true, false, HuntLifecycleService::STATE_EDITABLE));
    }

    public function testSingleHuntRequiresValidationRequestForOrganizer(): void
    {
        $service = new HuntLifecycleService();

        self::assertTrue($service->canActivate(
            false,
            true,
            false,
            HuntLifecycleService::STATE_EDITABLE,
            true
        ));
        self::assertFalse($service->canActivate(
            false,
            true,
            false,
            HuntLifecycleService::STATE_EDITABLE,
            false
        ));
        self::assertSame('request', $service->activateAction(false, false, HuntLifecycleService::STATE_EDITABLE));
        self::assertFalse($service->canDeactivate(
            false,
            true,
            false,
            HuntLifecycleService::STATE_ACTIVE
        ));
    }

    public function testAdminCanConfirmPendingRequestOutsideDemo(): void
    {
        $service = new HuntLifecycleService();

        self::assertTrue($service->canActivate(
            true,
            false,
            false,
            HuntLifecycleService::STATE_PENDING,
            false
        ));
        self::assertSame('validate', $service->activateAction(false, true, HuntLifecycleService::STATE_PENDING));
        self::assertSame('cancel', $service->deactivateAction(HuntLifecycleService::STATE_PENDING));
        self::assertSame('reopen', $service->deactivateAction(HuntLifecycleService::STATE_ACTIVE));
    }

    public function testStateIconsMapToHeaderBadgeIcons(): void
    {
        $service = new HuntLifecycleService();

        self::assertSame('fa-pen', $service->stateIcon(HuntLifecycleService::STATE_EDITABLE));
        self::assertSame('fa-hourglass-half', $service->stateIcon(HuntLifecycleService::STATE_PENDING));
        self::assertSame('fa-circle-check', $service->stateIcon(HuntLifecycleService::STATE_ACTIVE));
        self::assertSame('fa-ban', $service->stateIcon(HuntLifecycleService::STATE_BLOCKED));
    }
}
