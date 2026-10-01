<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntOrganizerAssignmentHookHandler;
use ChassesAuTresor\Core\Points\ConversionSettingsRequestHandler;
use ChassesAuTresor\Core\Points\ConversionRequestHandler;
use ChassesAuTresor\Core\Points\PurchasePointsHookHandler;
use PHPUnit\Framework\TestCase;

final class BusinessHookOwnershipTest extends TestCase
{
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
}
