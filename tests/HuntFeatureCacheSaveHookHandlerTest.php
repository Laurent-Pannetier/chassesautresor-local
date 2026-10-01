<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntFeatureCacheSaveHookHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntFeatureCacheSaveHookHandler.php';

final class HuntFeatureCacheSaveHookHandlerTest extends TestCase
{
    public function testRegistersEachLifecycleHookExactlyOnce(): void
    {
        $hooks = [];
        HuntFeatureCacheSaveHookHandler::register(
            static function (...$args) use (&$hooks): void {
                $hooks[] = $args;
            }
        );

        self::assertSame(['save_post_enigme', 'save_post_indice', 'save_post_solution'], array_column($hooks, 0));
        self::assertSame([20, 20, 20], array_column($hooks, 2));
        self::assertSame([1, 1, 1], array_column($hooks, 3));
    }
}
