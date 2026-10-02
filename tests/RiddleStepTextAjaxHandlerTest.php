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
}
