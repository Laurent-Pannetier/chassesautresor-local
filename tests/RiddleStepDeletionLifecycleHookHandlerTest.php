<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleStepDeletionLifecycleHookHandler;
use PHPUnit\Framework\TestCase;

final class RiddleStepDeletionLifecycleHookHandlerTest extends TestCase {
    public function testRegistersAfterTheContextCapturingDeletionHooks(): void {
        $hooks = [];
        RiddleStepDeletionLifecycleHookHandler::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame(
            [
                ['before_delete_post', [RiddleStepDeletionLifecycleHookHandler::class, 'handle'], 20, 1],
                [
                    'deleted_post',
                    [RiddleStepDeletionLifecycleHookHandler::class, 'refreshParentCompleteness'],
                    20,
                    1,
                ],
            ],
            $hooks
        );
    }
}
