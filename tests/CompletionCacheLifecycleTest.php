<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\CompletionCacheSaveHookHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/CompletionCacheSaveHookHandler.php';

final class CompletionCacheLifecycleTest extends TestCase {
    public function testRegistersAcfSaveLifecycleOnce(): void {
        $hooks = [];
        CompletionCacheSaveHookHandler::register(
            static function ($hook, $callback, $priority, $acceptedArgs) use (&$hooks): void {
                $hooks[] = [$hook, $callback, $priority, $acceptedArgs];
            }
        );

        $this->assertSame([[
            'acf/save_post',
            [CompletionCacheSaveHookHandler::class, 'handle'],
            20,
            1,
        ]], $hooks);
    }

    public function testStepSavesRefreshTheirParentRiddle(): void {
        $source = file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/CompletionCacheSaveHookHandler.php'
        );

        self::assertStringContainsString('RiddleStepPostTypeRegistrar::POST_TYPE', $source);
        self::assertStringContainsString("get_field('etape_enigme_associee', \$postId)", $source);
    }
}
