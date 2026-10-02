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
}
