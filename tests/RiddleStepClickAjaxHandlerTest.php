<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleStepClickAjaxHandler;
use PHPUnit\Framework\TestCase;

final class RiddleStepClickAjaxHandlerTest extends TestCase {
    public function testRegistersAuthenticatedClickSubmission(): void {
        $hooks = [];
        RiddleStepClickAjaxHandler::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame(
            [['wp_ajax_confirmer_etape_enigme', [RiddleStepClickAjaxHandler::class, 'submit']]],
            $hooks
        );
    }

    public function testDelegatesTransactionalPersistenceToSharedSubmissionService(): void {
        $source = file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleStepClickAjaxHandler.php'
        );

        self::assertStringContainsString('new RiddleStepSubmissionService(', $source);
        self::assertStringNotContainsString("query('START TRANSACTION')", $source);
        self::assertStringContainsString('new RiddleStepSubmissionRequestPolicy()', $source);
        self::assertStringContainsString("check_ajax_referer('riddle_step_click', 'nonce')", $source);
        self::assertStringContainsString('} finally {', $source);
        self::assertSame(1, substr_count($source, '$lock->release($userId, $riddleId)'));
    }
}
