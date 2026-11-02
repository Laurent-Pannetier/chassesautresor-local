<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Users\AccountTentativesEndpoint;
use PHPUnit\Framework\TestCase;

final class AccountTentativesEndpointTest extends TestCase
{
    public function testRegistersTheEndpointIndependentlyOfTheTheme(): void
    {
        $actions = [];
        $filters = [];
        AccountTentativesEndpoint::register(
            static function (...$arguments) use (&$actions): void {
                $actions[] = $arguments;
            },
            static function (...$arguments) use (&$filters): void {
                $filters[] = $arguments;
            }
        );

        self::assertSame('init', $actions[0][0]);
        self::assertSame([AccountTentativesEndpoint::class, 'registerEndpoint'], $actions[0][1]);
        self::assertSame([AccountTentativesEndpoint::class, 'maybeFlush'], $actions[1][1]);
        self::assertSame(99, $actions[1][2]);
        self::assertSame('woocommerce_get_query_vars', $filters[0][0]);
        self::assertSame(
            ['orders' => 'orders', 'tentatives' => 'tentatives'],
            AccountTentativesEndpoint::registerQueryVar(['orders' => 'orders'])
        );
    }
}
