<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Users\AccountDashboardHookHandler;
use PHPUnit\Framework\TestCase;

final class AccountDashboardHookHandlerTest extends TestCase {
    public function testRegistersPortableDashboardSections(): void {
        $hooks = [];
        AccountDashboardHookHandler::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame([
            ['woocommerce_account_dashboard', [AccountDashboardHookHandler::class, 'renderPortableDashboard'], 5],
            ['woocommerce_account_dashboard', [AccountDashboardHookHandler::class, 'renderEngagedHunts'], 10],
            ['woocommerce_account_dashboard', [AccountDashboardHookHandler::class, 'renderAttempts'], 20],
        ], $hooks);
    }
}
