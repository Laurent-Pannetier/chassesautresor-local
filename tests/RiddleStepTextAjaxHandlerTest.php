<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleStepTextAjaxHandler;
use PHPUnit\Framework\TestCase;

final class RiddleStepTextAjaxHandlerTest extends TestCase {
    public function testRegistersAuthenticatedTextSubmission(): void {
        $hooks = [];
        RiddleStepTextAjaxHandler::register(
            static function (...$arguments) use (&$hooks): void {
                $hooks[] = $arguments;
            }
        );

        self::assertSame(
            [['wp_ajax_soumettre_reponse_etape', [RiddleStepTextAjaxHandler::class, 'submit']]],
            $hooks
        );
    }

    public function testAcceptsEveryWidgetSubmittedThroughTheAnswerHandler(): void {
        foreach (['text', 'directions', 'colors', 'numbers', 'safe_dial'] as $type) {
            self::assertTrue(RiddleStepTextAjaxHandler::supportsWidgetType($type));
        }

        self::assertFalse(RiddleStepTextAjaxHandler::supportsWidgetType('click'));
        self::assertFalse(RiddleStepTextAjaxHandler::supportsWidgetType('unknown'));
    }

    public function testDelegatesTransactionalPersistenceToSharedSubmissionService(): void {
        $source = file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleStepTextAjaxHandler.php'
        );

        self::assertStringContainsString('new RiddleStepSubmissionService(', $source);
        self::assertStringNotContainsString("query('START TRANSACTION')", $source);
        self::assertStringContainsString('new RiddleStepSubmissionRequestPolicy()', $source);
        self::assertStringContainsString("check_ajax_referer('riddle_step_answer', 'nonce')", $source);
        self::assertStringContainsString('} finally {', $source);
        self::assertSame(1, substr_count($source, '$lock->release($userId, $riddleId)'));
    }
}
