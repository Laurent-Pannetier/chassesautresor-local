<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntOrganizerAssignmentHookHandler;
use ChassesAuTresor\Core\Content\OrganizerRoleAssignmentHookHandler;
use ChassesAuTresor\Core\Content\HuntWelcomeModalViewHookHandler;
use ChassesAuTresor\Core\Content\HuntViewMaintenanceHookHandler;
use ChassesAuTresor\Core\Content\HuntModerationRequestHandler;
use ChassesAuTresor\Core\Points\ConversionSettingsRequestHandler;
use ChassesAuTresor\Core\Points\ConversionRequestHandler;
use ChassesAuTresor\Core\Points\ManualPointsAdjustmentHandler;
use ChassesAuTresor\Core\Points\PurchasePointsHookHandler;
use ChassesAuTresor\Core\Progress\HuntCompletionHookHandler;
use ChassesAuTresor\Core\Relationships\OrganizerConfirmationRouteHandler;
use PHPUnit\Framework\TestCase;

final class BusinessHookOwnershipTest extends TestCase
{
    public function testOrganizerRoleAssignmentHookIsRegisteredByCore(): void {
        $hooks = [];

        OrganizerRoleAssignmentHookHandler::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame(
            ['save_post', [OrganizerRoleAssignmentHookHandler::class, 'handle'], 10, 3],
            $hooks[0]
        );
    }

    public function testPurchasePointsHookIsRegisteredByCore(): void
    {
        $hooks = [];

        PurchasePointsHookHandler::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame(
            ['woocommerce_thankyou', [PurchasePointsHookHandler::class, 'handle']],
            $hooks[0]
        );
    }

    public function testHuntOrganizerAssignmentHookIsRegisteredByCore(): void
    {
        $hooks = [];

        HuntOrganizerAssignmentHookHandler::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame(
            ['save_post', [HuntOrganizerAssignmentHookHandler::class, 'handle'], 10, 2],
            $hooks[0]
        );
    }

    public function testHuntWelcomeModalViewHookIsRegisteredByCore(): void
    {
        $hooks = [];

        HuntWelcomeModalViewHookHandler::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame(
            ['wp_footer', [HuntWelcomeModalViewHookHandler::class, 'handle'], 99],
            $hooks[0]
        );
    }

    public function testHuntViewMaintenanceHookIsRegisteredByCore(): void
    {
        $hooks = [];

        HuntViewMaintenanceHookHandler::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame(
            ['template_redirect', [HuntViewMaintenanceHookHandler::class, 'handle']],
            $hooks[0]
        );
    }

    public function testConversionSettingsHooksAreRegisteredByCore(): void
    {
        $hooks = [];

        ConversionSettingsRequestHandler::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame(
            [
                ['init', [ConversionSettingsRequestHandler::class, 'initialize']],
                ['init', [ConversionSettingsRequestHandler::class, 'handleUpdate']],
            ],
            $hooks
        );
    }

    public function testConversionRequestHookIsRegisteredByCore(): void
    {
        $hooks = [];

        ConversionRequestHandler::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame([['init', [ConversionRequestHandler::class, 'handle']]], $hooks);
    }

    public function testManualPointsAdjustmentHookIsRegisteredByCore(): void
    {
        $hooks = [];

        ManualPointsAdjustmentHandler::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame([['init', [ManualPointsAdjustmentHandler::class, 'handle']]], $hooks);
    }

    public function testHuntModerationRequestHookIsRegisteredByCore(): void
    {
        $hooks = [];

        HuntModerationRequestHandler::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame(
            ['admin_post_traiter_validation_chasse', [HuntModerationRequestHandler::class, 'handle']],
            $hooks[0]
        );
    }

    public function testHuntCompletionHookIsRegisteredByCore(): void
    {
        $hooks = [];

        HuntCompletionHookHandler::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame(
            ['enigme_resolue', [HuntCompletionHookHandler::class, 'handle'], 10, 2],
            $hooks[0]
        );
    }

    public function testOrganizerConfirmationRoutesAreRegisteredByCore(): void
    {
        $hooks = [];

        OrganizerConfirmationRouteHandler::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame(
            [
                ['init', [OrganizerConfirmationRouteHandler::class, 'registerRoute']],
                ['template_redirect', [OrganizerConfirmationRouteHandler::class, 'handle']],
            ],
            $hooks
        );
    }
}
