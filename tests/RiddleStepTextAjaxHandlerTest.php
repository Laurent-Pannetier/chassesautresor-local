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
}
